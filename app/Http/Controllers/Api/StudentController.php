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
        $announcements = Announcement::latest()->take(5)->get()->map(function ($item) {
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
                'name' => $student->full_name ?? $student->name ?? 'طالب',
                'upcoming_lecture' => $upcomingLecture,
                'latest_news' => $announcements,
            ]
        ], 200);
    }
}