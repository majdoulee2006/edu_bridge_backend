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
     * Show the unified login form.
     */
    public function showLoginForm()
    {
        if (Auth::check()) {
            return $this->redirectUserByRole(Auth::user());
        }
        return view('login');
    }

    /**
     * Handle the unified login request.
     */
    public function login(Request $request)
    {
        $request->validate([
            'login'     => 'required|string',
            'password'  => 'required|string',
            'role_type' => 'nullable|string',
        ], [
            'login.required'    => 'يرجى إدخال بيانات حسابك (اسم المستخدم، البريد، الرقم الجامعي أو الهاتف).',
            'password.required' => 'كلمة المرور مطلوبة.',
        ]);

        $input    = trim($request->login);
        $roleType = $request->input('role_type'); // admin, hod, affairs, teacher, student, parent

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

        // 3. Check credentials
        if ($user && Hash::check($request->password, $user->password)) {
            if ($user->status !== 'active') {
                UserActivity::log('محاولة دخول مرفوضة', 'الحساب موقوف مؤقتاً', $user);
                return back()->withErrors(['login' => 'عذراً، هذا الحساب موقوف مؤقتاً.'])->withInput($request->only('login', 'role_type'));
            }

            // 4. Validate selected role type if provided by user
            if ($roleType) {
                $userRoleId = (int) ($user->role_id ?? 0);
                $userRole   = strtolower($user->role ?? '');

                $isMatch = false;
                switch ($roleType) {
                    case 'admin':
                        $isMatch = ($userRoleId === 1 || $userRole === 'admin' || !empty($user->is_admin));
                        $roleNameAr = 'مدير نظام (Admin)';
                        break;
                    case 'hod':
                        $isMatch = ($userRoleId === 5 || $userRole === 'head' || $userRole === 'hod');
                        $roleNameAr = 'رئيس قسم (HOD)';
                        break;
                    case 'affairs':
                        $isMatch = ($userRoleId === 6 || $userRole === 'affairs');
                        $roleNameAr = 'موظف شؤون';
                        break;
                    case 'teacher':
                        $isMatch = ($userRoleId === 2 || $userRole === 'teacher' || $userRole === 'instructor');
                        $roleNameAr = 'معلم / محاضر';
                        break;
                    case 'student':
                        $isMatch = ($userRoleId === 3 || $userRole === 'student' || Student::where('user_id', $user->user_id)->exists());
                        $roleNameAr = 'طالب';
                        break;
                    case 'parent':
                        $isMatch = ($userRoleId === 4 || $userRole === 'parent' || Parents::where('user_id', $user->user_id)->exists());
                        $roleNameAr = 'ولي أمر';
                        break;
                    default:
                        $isMatch = true;
                }

                if (!$isMatch) {
                    return back()->withErrors(['login' => "هذا الحساب غير مسجل بصفة ($roleNameAr). يرجى اختيار الصفة الصحيحة من القائمة."])->withInput($request->only('login', 'role_type'));
                }
            }

            Auth::login($user);
            $request->session()->regenerate();
            UserActivity::log('تسجيل دخول', 'تسجيل دخول ناجح عبر البوابة الموحدة', $user);

            return $this->redirectUserByRole($user);
        }

        return back()->withErrors(['login' => 'بيانات الدخول غير صحيحة، يرجى التأكد من المحدّدات وكلمة المرور.'])->withInput($request->only('login', 'role_type'));
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
