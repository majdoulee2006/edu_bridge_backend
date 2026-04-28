<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Models\Lesson;
use App\Models\Course;
use App\Models\Department;
use App\Models\Teacher;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class LectureController extends Controller
{
    // عرض صفحة المحاضرات مع المواد والأقسام وآخر المحاضرات
    public function index()
    {
        $user = Auth::user();
        
        // جلب بيانات المعلم من جدول teachers
        $teacher = Teacher::where('user_id', $user->user_id)->first();
        
        // جلب المواد اللي بدرّسها المعلم (أو كل المواد إذا ما عنده مواد مخصصة)
        $courses = $teacher && $teacher->courses->count() > 0 
            ? $teacher->courses 
            : Course::all();
        
        // جلب كل الأقسام
        $departments = Department::all();
        
        // جلب آخر المحاضرات اللي أضافها المعلم
        $lessons = Lesson::with(['course', 'department'])
            ->where('teacher_id', $teacher ? $teacher->teacher_id : null)
            ->latest()
            ->get();

        return view('teacher.lectures', compact('courses', 'departments', 'lessons'));
    }

    // حفظ المحاضرة الجديدة
    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'course_id' => 'required|exists:courses,course_id',
            'department_id' => 'required|exists:departments,department_id',
            'description' => 'nullable|string',
            'content_file' => 'required|file|mimes:pdf,mp4,mov,avi,mkv|max:102400', // 100MB
        ]);

        try {
            $user = Auth::user();
            $teacher = Teacher::where('user_id', $user->user_id)->first();

            // رفع الملف
            $file = $request->file('content_file');
            $fileName = time() . '_' . $file->getClientOriginalName();
            $filePath = $file->storeAs('lectures', $fileName, 'public');

            // إنشاء المحاضرة
            Lesson::create([
                'title' => $request->title,
                'course_id' => $request->course_id,
                'teacher_id' => $teacher ? $teacher->teacher_id : null,
                'department_id' => $request->department_id,
                'description' => $request->description,
                'content_url' => $filePath,
            ]);

            return redirect()->route('lectures')->with('success', 'تم رفع المحاضرة بنجاح! 🎉');
        } catch (\Exception $e) {
            return redirect()->route('lectures')->with('error', 'حدث خطأ: ' . $e->getMessage());
        }
    }
}
