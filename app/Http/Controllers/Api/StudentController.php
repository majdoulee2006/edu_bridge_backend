<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Student; // ✅ ضروري جداً لاستخدام موديل الطالب

class StudentController extends Controller
{
    public function linkStudent(Request $request) 
{
    // التأكد من وصول البيانات (Validation)
    $request->validate([
        'student_code' => 'required',
        'parent_id' => 'required'
    ]);

    // البحث باستخدام العمود student_code
    $student = \App\Models\Student::where('student_code', $request->student_code)->first();

    if (!$student) {
        return response()->json([
            'status' => false,
            'message' => 'عذراً، الكود الجامعي غير صحيح!'
        ], 404);
    }

    // الربط باستخدام العمود parent_id كما ذكرتِ
    $student->parent_id = $request->parent_id; 
    $student->save();

    return response()->json([
        'status' => true,
        'message' => 'تم إضافة الابن بنجاح', 
        'student' => $student
    ], 200);
}

    public function getDashboardData(Request $request)
    {
        $user = $request->user();

        return response()->json([
            'status' => true,
            'student_name' => $user->full_name ?? 'طالب غير معروف',
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