<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Announcement;

class StudentController extends Controller
{
  public function getDashboardData(Request $request)
    {
        // 1. جلب بيانات الطالب الذي سجل دخوله
        $user = $request->user();

        // 2. جلب آخر الأخبار من قاعدة البيانات (لتغذية واجهتك)
        // تأكدي من عمل import للمودل فوق: use App\Models\Announcement;
        $latestNews = \App\Models\Announcement::latest()
            ->take(5)
            ->get()
            ->map(function ($news) {
                return [
                    'id' => $news->id,
                    'type' => $news->type ?? 'عام', 
                    'title' => $news->title,
                    'content' => $news->content,
                    'time_ago' => $news->created_at->locale('ar')->diffForHumans(), 
                ];
            });

        // 3. إرجاع الاستجابة المدمجة (بيانات واجهتك + بيانات فريقك الوهمية مؤقتاً)
        return response()->json([
            'status' => true,
            'data' => [ // الأفضل دائماً وضع البيانات داخل كائن data
                'student_name' => $user->full_name,
                
                // --- البيانات الخاصة بواجهتك (آخر الأخبار) ---
                'latest_news' => $latestNews,

                // --- بيانات فريقك (تركناها عشان ما يخرب شغلهم إذا رابطينه بمكان تاني) ---
                'level' => 'السنة الثالثة - هندسة',
                'gpa' => 3.85,
                'upcoming_lecture' => [
                    'subject' => 'الذكاء الاصطناعي',
                    'time' => '10:00 AM',
                    'room' => 'قاعة 4'
                ]
            ]
        ], 200);
    }
}
