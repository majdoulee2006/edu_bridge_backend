<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use App\Models\Student;
use App\Models\Parents;
use App\Models\UserActivity;

class UnifiedAuthController extends Controller
{
    /**
     * Role configurations for dedicated login routes.
     */
    protected $roleConfigs = [
        'admin'   => ['title' => 'تسجيل دخول إدارة النظام', 'icon' => 'fa-user-shield', 'badge' => 'مدير النظام'],
        'affairs' => ['title' => 'تسجيل دخول شؤون الطلاب', 'icon' => 'fa-clipboard-user', 'badge' => 'موظف الشؤون'],
        'hod'     => ['title' => 'تسجيل دخول رئيس القسم', 'icon' => 'fa-user-tie', 'badge' => 'رئيس القسم'],
        'teacher' => ['title' => 'تسجيل دخول الكادر التدريسي', 'icon' => 'fa-chalkboard-user', 'badge' => 'المعلم'],
        'student' => ['title' => 'تسجيل دخول الطالب', 'icon' => 'fa-graduation-cap', 'badge' => 'الطالب'],
        'parent'  => ['title' => 'تسجيل دخول ولي الأمر', 'icon' => 'fa-users', 'badge' => 'ولي الأمر'],
        'unified' => ['title' => 'بوابة تسجيل الدخول الموحدة', 'icon' => 'fa-shield-halved', 'badge' => 'الدخول الموحد'],
    ];

    /**
     * Show the login form for specific role or unified.
     */
    public function showLoginForm(Request $request, $roleKey = 'unified')
    {
        if (Auth::check()) {
            return $this->redirectUserByRole(Auth::user());
        }

        $role = $this->roleConfigs[$roleKey] ?? $this->roleConfigs['unified'];
        $role['key'] = $roleKey;

        return view('login', compact('role'));
    }

    /**
     * Handle login request for any actor.
     */
    public function login(Request $request)
    {
        $request->validate([
            'login'    => 'required|string',
            'password' => 'required|string',
            'role_key' => 'nullable|string',
        ], [
            'login.required'    => 'يرجى إدخال اسم المستخدم، البريد الإلكتروني، أو الرقم الجامعي.',
            'password.required' => 'كلمة المرور مطلوبة.',
        ]);

        $input   = trim($request->login);
        $roleKey = $request->input('role_key', 'unified');

        // 1. Search in User table by email, phone, or username
        $user = User::where('email', $input)
            ->orWhere('phone', $input)
            ->orWhere('username', $input)
            ->first();

        // 2. If not found, search in Student table by student_code or university_id
        if (!$user) {
            $student = Student::where('student_code', $input)
                ->orWhere('university_id', $input)
                ->first();
            if ($student && $student->user) {
                $user = $student->user;
            }
        }

        // 3. Validate credentials
        if ($user && Hash::check($request->password, $user->password)) {
            if ($user->status !== 'active') {
                UserActivity::log('محاولة دخول مرفوضة', 'الحساب موقوف مؤقتاً', $user);
                return back()->withErrors(['login' => 'عذراً، هذا الحساب موقوف مؤقتاً.'])->withInput($request->only('login'));
            }

            // 4. If logged in through a dedicated role route, ensure role matches
            if ($roleKey !== 'unified') {
                $userRoleId = (int) ($user->role_id ?? 0);
                $userRole   = strtolower($user->role ?? '');

                $isMatch = false;
                switch ($roleKey) {
                    case 'admin':
                        $isMatch = ($userRoleId === 1 || $userRole === 'admin' || !empty($user->is_admin));
                        break;
                    case 'affairs':
                        $isMatch = ($userRoleId === 6 || $userRole === 'affairs');
                        break;
                    case 'hod':
                        $isMatch = ($userRoleId === 5 || $userRole === 'head' || $userRole === 'hod');
                        break;
                    case 'teacher':
                        $isMatch = ($userRoleId === 2 || $userRole === 'teacher' || $userRole === 'instructor');
                        break;
                    case 'student':
                        $isMatch = ($userRoleId === 3 || $userRole === 'student' || Student::where('user_id', $user->user_id)->exists());
                        break;
                    case 'parent':
                        $isMatch = ($userRoleId === 4 || $userRole === 'parent' || Parents::where('user_id', $user->user_id)->exists());
                        break;
                    default:
                        $isMatch = true;
                }

                if (!$isMatch) {
                    $expectedBadge = $this->roleConfigs[$roleKey]['badge'] ?? '';
                    return back()->withErrors(['login' => "هذا الحساب غير مسجل بصفة ($expectedBadge). يرجى التأكد من رابط الدخول الصحيح."])->withInput($request->only('login'));
                }
            }

            Auth::login($user);
            $request->session()->regenerate();
            UserActivity::log('تسجيل دخول', 'تسجيل دخول ناجح', $user);

            return $this->redirectUserByRole($user);
        }

        return back()->withErrors(['login' => 'بيانات الدخول غير صحيحة، يرجى التأكد من اسم المستخدم وكلمة المرور.'])->withInput($request->only('login'));
    }

    /**
     * Logout user.
     */
    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect('/login')->with('success', 'تم تسجيل الخروج بنجاح.');
    }

    /**
     * Helper to redirect users to their respective dashboards based on role.
     */
    public function redirectUserByRole($user)
    {
        $roleId  = (int) ($user->role_id ?? 0);
        $roleStr = strtolower($user->role ?? '');

        if ($roleId === 6 || $roleStr === 'affairs') {
            return redirect()->intended('/affairs/dashboard');
        }
        if ($roleId === 5 || $roleStr === 'head' || $roleStr === 'hod') {
            return redirect()->intended('/hod/dashboard');
        }
        if ($roleId === 1 || $roleStr === 'admin' || !empty($user->is_admin)) {
            return redirect()->intended('/admin/dashboard');
        }
        if ($roleId === 2 || $roleStr === 'teacher' || $roleStr === 'instructor') {
            return redirect()->intended('/teacher/dashboard');
        }
        if ($roleId === 3 || $roleStr === 'student' || Student::where('user_id', $user->user_id)->exists()) {
            return redirect()->intended('/student/dashboard');
        }
        if ($roleId === 4 || $roleStr === 'parent' || Parents::where('user_id', $user->user_id)->exists()) {
            return redirect()->intended('/parent/dashboard');
        }

        return redirect('/login');
    }
}
