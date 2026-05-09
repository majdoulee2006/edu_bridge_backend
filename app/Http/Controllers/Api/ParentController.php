<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Student;
use App\Models\StudentParent;
use App\Models\Notification;
use App\Models\Attendance;
use App\Models\Grade;
use App\Models\Schedule;
use App\Models\Assignment;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class ParentController extends Controller
{
    /**
     * 1. لوحة تحكم الأهل (Dashboard)
     * جلب ملخص لأبناء الأهل (آخر الدرجات، الحضور، الرسائل غير المقروءة)
     */
    public function dashboard()
    {
        $user = auth()->user();
        $parent = StudentParent::where('user_id', $user->user_id)->first();

        if (!$parent) {
            return response()->json(['message' => 'سجل ولي الأمر غير موجود'], 404);
        }

        $children = Student::where('parent_id', $parent->parent_id)
            ->with(['user', 'enrollments.course'])
            ->get();

        $summary = $children->map(function ($child) {
            return [
                'student_id' => $child->student_id,
                'name' => $child->user->full_name,
                'level' => $child->level,
                'last_grade' => Grade::where('student_id', $child->student_id)->latest()->first(),
                'attendance_rate' => $this->calculateAttendanceRate($child->student_id),
            ];
        });

        return response()->json([
            'parent_name' => $user->full_name,
            'children' => $summary,
            'notifications_count' => Notification::where('user_id', $user->user_id)->where('is_read', 0)->count()
        ]);
    }

    /**
     * 2. جلب قائمة الأبناء
     */
    public function getChildren()
    {
        $user = auth()->user();
        $parent = StudentParent::where('user_id', $user->user_id)->first();

        if (!$parent) return response()->json([], 404);

        $children = DB::table('parents')
            ->join('students', 'parents.parent_id', '=', 'students.parent_id')
            ->join('users', 'students.user_id', '=', 'users.user_id')
            ->where('parents.parent_id', $parent->parent_id)
            ->select('students.student_id', 'users.full_name', 'students.level', 'users.branch as department')
            ->get();

        return response()->json($children);
    }

    /**
     * 3. جلب تفاصيل ابن محدد
     */
    public function getChildDetails($studentId)
    {
        $student = DB::table('students')
            ->join('users', 'students.user_id', '=', 'users.user_id')
            ->where('students.student_id', $studentId)
            ->select('users.full_name', 'users.branch as department', 'students.level', 'students.student_code')
            ->first();

        return $student ? response()->json($student) : response()->json(['message' => 'الطالب غير موجود'], 404);
    }

    /**
     * 4. جلب حضور ابن محدد
     */
    public function getChildAttendance($studentId)
    {
        $attendance = DB::table('attendance')
            ->leftJoin('lessons', 'attendance.lesson_id', '=', 'lessons.lesson_id')
            ->leftJoin('courses', 'lessons.course_id', '=', 'courses.course_id')
            ->where('attendance.student_id', $studentId)
            ->select('courses.title as course_name', 'attendance.status', 'attendance.attendance_date')
            ->orderBy('attendance.attendance_date', 'desc')
            ->get();

        return response()->json($attendance);
    }

    /**
     * 5. جلب درجات ابن محدد
     */
    public function getChildGrades($studentId)
    {
        $grades = DB::table('grades')
            ->join('exams', 'grades.exam_id', '=', 'exams.exam_id')
            ->join('courses', 'exams.course_id', '=', 'courses.course_id')
            ->where('grades.student_id', $studentId)
            ->select('courses.title as subject', 'exams.title as exam_type', 'grades.score', 'exams.max_score')
            ->get();

        return response()->json($grades);
    }

    /**
     * 6. جلب جدول ابن محدد
     */
    public function getChildSchedule($studentId)
    {
        $schedule = DB::table('schedules')
            ->join('courses', 'schedules.course_id', '=', 'courses.course_id')
            ->join('teachers', 'courses.teacher_id', '=', 'teachers.teacher_id')
            ->join('users', 'teachers.user_id', '=', 'users.user_id')
            ->whereIn('schedules.course_id', function($query) use ($studentId) {
                $query->select('course_id')->from('enrollments')->where('student_id', $studentId);
            })
            ->select('schedules.day_of_week', 'schedules.start_time', 'schedules.end_time', 'courses.title as course_name', 'users.full_name as teacher_name', 'schedules.room')
            ->get();

        return response()->json($schedule);
    }

    /**
     * 7. جلب واجبات ابن محدد
     */
    public function getChildAssignments($studentId)
    {
        $assignments = DB::table('assignments')
            ->join('courses', 'assignments.course_id', '=', 'courses.course_id')
            ->leftJoin('assignment_submissions', function($join) use ($studentId) {
                $join->on('assignments.assignment_id', '=', 'assignment_submissions.assignment_id')
                     ->where('assignment_submissions.student_id', '=', $studentId);
            })
            ->whereIn('assignments.course_id', function($query) use ($studentId) {
                $query->select('course_id')->from('enrollments')->where('student_id', $studentId);
            })
            ->select(
                'assignments.title', 
                'courses.title as course_name', 
                'assignments.due_date',
                DB::raw('IF(assignment_submissions.submission_id IS NULL, "pending", "submitted") as status')
            )
            ->get();

        return response()->json($assignments);
    }

    /**
     * 8. جلب الإعلانات العامة
     */
    public function getAnnouncements()
    {
        $announcements = DB::table('announcements')
            ->whereIn('target_audience', ['all', 'parents'])
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json($announcements);
    }

    /**
     * 9. ربط ابن جديد عبر الكود
     */
    public function linkStudent(Request $request)
    {
        $request->validate(['student_code' => 'required|string']);

        $user = auth()->user();
        $parent = StudentParent::where('user_id', $user->user_id)->first();

        if (!$parent) return response()->json(['message' => 'سجل الأب غير موجود'], 404);

        $student = Student::where('student_code', $request->student_code)->first();

        if (!$student) return response()->json(['message' => 'كود الطالب غير موجود'], 404);

        $student->parent_id = $parent->parent_id;
        $student->save();

        return response()->json(['message' => 'تم ربط الطالب بنجاح', 'student_name' => $student->user->full_name]);
    }

    /**
     * ميزات إضافية: الرسائل
     */
    public function getMessages()
    {
        $userId = auth()->id();
        $messages = DB::table('chats')
            ->join('users as senders', 'chats.sender_id', '=', 'senders.user_id')
            ->where('chats.receiver_id', $userId)
            ->orWhere('chats.sender_id', $userId)
            ->select('chats.*', 'senders.full_name as sender_name')
            ->orderBy('chats.created_at', 'desc')
            ->get();

        return response()->json($messages);
    }

    public function sendMessage(Request $request)
    {
        $request->validate([
            'receiver_id' => 'required|exists:users,user_id',
            'content' => 'required|string'
        ]);

        DB::table('chats')->insert([
            'sender_id' => auth()->id(),
            'receiver_id' => $request->receiver_id,
            'content' => $request->content,
            'sent_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return response()->json(['message' => 'تم إرسال الرسالة']);
    }

    private function calculateAttendanceRate($studentId)
    {
        $total = DB::table('attendance')->where('student_id', $studentId)->count();
        if ($total == 0) return 100;
        $present = DB::table('attendance')->where('student_id', $studentId)->where('status', 'present')->count();
        return round(($present / $total) * 100, 1);
    }
}
