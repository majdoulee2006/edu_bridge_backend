<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TeacherAuthController extends Controller
{
    /**
     * عرض صفحة تسجيل الدخول
     */
    public function showLoginForm()
    {
        return view('teacher.login');
    }

    /**
     * معالجة طلب تسجيل الدخول
     */
    public function login(Request $request)
    {
        // 1. التحقق من المدخلات (استخدام username ليتطابق مع الـ Blade)
        $credentials = $request->validate([
            'username' => 'required|string',
            'password' => 'required|string',
        ]);

        // 2. محاولة تسجيل الدخول
        if (Auth::attempt($credentials)) {
            $user = Auth::user();

            // 3. التحقق من صلاحية المعلم
            if ($user->role === 'teacher') {
                $request->session()->regenerate();
                return redirect()->route('dashboard');
            }

            // إذا كان المستخدم ليس معلماً
            Auth::logout();
            return back()->withErrors([
                'username' => 'عذراً، هذا الحساب لا يملك صلاحيات معلم.',
            ]);
        }

        // 4. في حال فشل البيانات
        return back()->withErrors([
            'username' => 'اسم المستخدم أو كلمة المرور غير صحيحة.',
        ]);
    }

    /**
     * تسجيل الخروج
     */
    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('teacher.login');
    }
}