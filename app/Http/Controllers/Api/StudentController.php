<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Announcement; 

class StudentController extends Controller
{
public function getDashboardData(Request $request)
    {
        // 1. جلب بيانات الطالب اللي عامل تسجيل دخول حالياً
        $student = $request->user();

        // 2. جلب آخر 5 أخبار/إعلانات من قاعدة البيانات وتجهيزها للفلاتر
        $announcements = \App\Models\Announcement::latest()->take(5)->get()->map(function ($item) {
            return [
                'type' => $item->type ?? 'إعلان عام',
                'title' => $item->title ?? 'بدون عنوان',
                'content' => $item->content ?? '',
                // حماية إضافية في حال كان حقل التاريخ فارغ
                'time_ago' => $item->created_at ? $item->created_at->diffForHumans() : 'منذ قليل',
            ];
        });

        // 3. بيانات مؤقتة للمحاضرة القادمة (لتشغيل واجهة الفلاتر بدون أخطاء)
        $upcomingLecture = [
            'subject' => 'برمجة تطبيقات الموبايل',
            'room' => 'مخبر الحاسوب 1',
            'time' => '10:00 صباحاً'
        ];

        // 4. إرسال البيانات بشكل مرتب (JSON) للفلاتر
        return response()->json([
            'status' => true,
            'message' => 'تم جلب البيانات بنجاح',
            'data' => [
                // 🔥 تم إصلاح المشكلة هنا: استخدام اسم الحقل الدقيق في قاعدة البيانات
                'full_name' => $student->full_name ?? 'طالب', 
                'name' => $student->full_name ?? 'طالب', // للفلاتر الذي يبحث عن name
                'university_id' => $student->university_id ?? $student->id ?? '0000',
                'phone' => $student->phone ?? 'غير متوفر',
                'email' => $student->email ?? 'غير متوفر',
                'department' => $student->department ?? 'هندسة الحاسوب',
                'academic_year' => $student->academic_year ?? 'غير محدد',
                'birth_date' => $student->birth_date ?? '00/00/0000',
                'gender' => $student->gender ?? 'غير محدد',
                
                // 🔥 المفاجأة المنتظرة: تم إضافة الإعلانات والمحاضرات ليراها الفلاتر!
                'announcements' => $announcements,
                'upcoming_lecture' => $upcomingLecture,
            ]
        ], 200);
    }


    public function getProfileData(Request $request)
    {
        // جلب بيانات الطالب المرتبط بالتوكن الحالي
        $student = $request->user();

        // إرسال البيانات بشكل مرتب للفلاتر
        return response()->json([
            'status' => true,
            'message' => 'تم جلب بيانات الملف الشخصي بنجاح',
            'data' => [
                // ملاحظة: تأكدي من مطابقة هذه الأسماء مع أسماء الأعمدة في قاعدة البيانات لديك
                'name' => $student-> full_name ?? 'طالب',
                'university_id' => $student->university_id ?? $student->id ?? '0000',
                'phone' => $student->phone ?? 'غير متوفر',
                'email' => $student->email ?? 'غير متوفر',
                'department' => $student->department ?? 'هندسة الحاسوب',
                'academic_year' => $student->academic_year ?? 'غير محدد',
                'birth_date' => $student->birth_date ?? '00/00/0000',
                'gender' => $student->gender ?? 'غير محدد',
            ]
        ], 200);
    }
}