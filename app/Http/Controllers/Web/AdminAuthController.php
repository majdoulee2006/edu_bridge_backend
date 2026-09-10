<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AdminAuthController extends Controller
{
    public function logout(Request $request)
    {
        if (Auth::check()) {
            if ($request->has('is_inactivity_logout')) {
                \App\Models\UserActivity::log('خروج تلقائي (خمول)', 'تم تسجيل الخروج تلقائياً بعد 20 دقيقة من الخمول');
            } else {
                \App\Models\UserActivity::log('تسجيل خروج', 'قام المستخدم بتسجيل الخروج يدوياً من لوحة الإدارة');
            }
        }
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect('/admin/login');
    }
}
