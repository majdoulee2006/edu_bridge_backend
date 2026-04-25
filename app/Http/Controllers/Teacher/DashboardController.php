<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Models\Announcement; // ضروري جداً استدعاء الموديل هنا

class DashboardController extends Controller
{
    /**
     * عرض لوحة تحكم المعلم مع الإعلانات
     */
    public function index()
    {
        // 1. جلب بيانات المعلم المسجل حالياً
        $teacher = Auth::user();

        // 2. جلب آخر إعلان مضاف في قاعدة البيانات
        // استخدمنا latest() لترتيبهم من الأحدث، و first() لأخذ أول واحد فقط
        $announcement = Announcement::latest()->first();

        // 3. تمرير البيانات (المعلم والإعلان) لملف الـ view
        return view('teacher.dashboard', compact('teacher', 'announcement'));
    }
}