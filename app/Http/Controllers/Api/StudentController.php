<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class StudentController extends Controller
{
    public function getDashboardData(Request $request)
    {
        // هنا نجلب بيانات الطالب الذي سجل دخوله حالياً
        $user = $request->user();

        return response()->json([
            'status' => true,
            'student_name' => $user->full_name,
            'level' => 'السنة الثالثة - هندسة',
            'gpa' => 3.85,
            'upcoming_lecture' => [
                'subject' => 'الذكاء الاصطناعي',
                'time' => '10:00 AM',
                'room' => 'قاعة 4'
            ]
        ]);
    }
}
