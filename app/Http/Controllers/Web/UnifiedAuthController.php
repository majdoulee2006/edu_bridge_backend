<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use App\Models\Student;
use App\Models\ParentModel;
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
            'login'    => 'required|string',
            'password' => 'required|string',
        ], [
            'login.required'    => 'يرجى إدخال البريد الإلكتروني، اسم المستخدم، رقم الهاتف، أو الرقم الجامعي.',
            'password.required' => 'كلمة المرور مطلوبة.',
        ]);

        $input = trim($request->login);

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

            Auth::login($user);
            $request->session()->regenerate();
            UserActivity::log('تسجيل دخول', 'تسجيل دخول ناجح عبر البوابة الموحدة', $user);

            return $this->redirectUserByRole($user);
        }

        return back()->withErrors(['login' => 'بيانات الدخول غير صحيحة، يرجى التأكد من اسم المستخدم/الرقم الجامعي وكلمة المرور.'])->withInput($request->only('login'));
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
        $role = strtolower($user->role ?? '');

        if ($role === 'student' || Student::where('user_id', $user->user_id)->exists()) {
            return redirect()->intended('/student/dashboard');
        }
        if ($role === 'affairs') {
            return redirect()->intended('/affairs/dashboard');
        }
        if ($role === 'hod') {
            return redirect()->intended('/hod/dashboard');
        }
        if ($role === 'teacher') {
            return redirect()->intended('/teacher/dashboard');
        }
        if ($role === 'parent' || ParentModel::where('user_id', $user->user_id)->exists()) {
            return redirect()->intended('/parent/dashboard');
        }
        if ($role === 'admin' || $user->is_admin) {
            return redirect()->intended('/admin/dashboard');
        }

        return redirect('/login');
    }
}
