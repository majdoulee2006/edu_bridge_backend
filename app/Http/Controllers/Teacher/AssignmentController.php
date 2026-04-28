<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Assignment;
use App\Models\Course;
use App\Models\Department;
use App\Models\Teacher;

class AssignmentController extends Controller
{
    /**
     * عرض صفحة الواجبات
     */
    public function index()
    {
        $teacherUser = Auth::user();
        
        // جلب بيانات المعلم
        $teacher = Teacher::where('user_id', $teacherUser->user_id)->first();
        
        // جلب مواد المعلم
        $teacherCourseIds = $teacher ? $teacher->courses()->pluck('courses.course_id') : collect();
        
        // جلب الواجبات الخاصة بمواد المعلم مع عدد التسليمات
        $assignments = Assignment::with(['course', 'submissions'])
            ->whereIn('course_id', $teacherCourseIds)
            ->orWhere('teacher_id', $teacher ? $teacher->teacher_id : 0)
            ->latest()
            ->get();
            
        // جلب جميع المواد والأقسام للفورم
        $courses = Course::all();
        $departments = Department::all();

        return view('teacher.assignments', compact('teacherUser', 'teacher', 'assignments', 'courses', 'departments'));
    }

    /**
     * حفظ واجب جديد
     */
    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'course_id' => 'required|exists:courses,course_id',
            'description' => 'nullable|string',
            'due_date' => 'required|date',
            'max_points' => 'nullable|integer|min:1|max:100',
        ]);

        try {
            $teacherUser = Auth::user();
            $teacher = Teacher::where('user_id', $teacherUser->user_id)->first();

            Assignment::create([
                'title' => $request->title,
                'course_id' => $request->course_id,
                'teacher_id' => $teacher ? $teacher->teacher_id : null,
                'description' => $request->description,
                'due_date' => $request->due_date,
                'max_points' => $request->max_points ?? 100,
            ]);

            return redirect()->route('assignments')->with('success', 'تم إضافة الواجب بنجاح! 🎉');
        } catch (\Exception $e) {
            return redirect()->route('assignments')->with('error', 'حدث خطأ: ' . $e->getMessage());
        }
    }
}
