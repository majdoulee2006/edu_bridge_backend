<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class DepartmentHeadController extends Controller
{
    // ─── Dashboard ───────────────────────────────────────────────
    public function dashboard(Request $request)
    {
        $user    = $request->user();
        $pending = DB::table('leave_requests')->where('status', 'pending')->count();

        $announcements = DB::table('announcements')
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get(['announcement_id', 'title', 'content', 'created_at'])
            ->map(fn($a) => [
                'id'      => $a->announcement_id,
                'title'   => $a->title,
                'content' => $a->content,
            ]);

        return response()->json([
            'success' => true,
            'data'    => [
                'name'                  => $user->full_name,
                'pending_leave_requests'=> $pending,
                'announcements'         => $announcements,
            ],
        ]);
    }

    // ─── Profile ─────────────────────────────────────────────────
    public function getProfile(Request $request)
    {
        $user = $request->user();
        return response()->json([
            'success' => true,
            'data'    => [
                'full_name'   => $user->full_name,
                'email'       => $user->email ?? '',
                'phone'       => $user->phone ?? '',
                'department'  => $user->department ?? '',
                'role_label'  => 'رئيس القسم الأكاديمي',
                'avatar'      => $user->avatar ? storageUrl($user->avatar) : null,
            ],
        ]);
    }

    // ─── Notifications ────────────────────────────────────────────
    public function getNotifications(Request $request)
    {
        $notifications = DB::table('notifications')
            ->where('user_id', $request->user()->user_id)
            ->orderBy('created_at', 'desc')
            ->limit(30)
            ->get()
            ->map(fn($n) => [
                'id'         => $n->id,
                'title'      => $n->title,
                'message'    => $n->message,
                'type'       => $n->type ?? 'general',
                'is_read'    => (bool) $n->is_read,
                'created_at' => $n->created_at,
            ]);

        return response()->json(['success' => true, 'data' => $notifications]);
    }

    // ─── Users – Trainers / Students / Parents ────────────────────
    public function getTrainers()
    {
        $teachers = DB::table('teachers')
            ->join('users', 'teachers.user_id', '=', 'users.user_id')
            ->select(
                'teachers.teacher_id as id',
                'users.full_name',
                'users.email',
                'users.phone',
                'teachers.specialization'
            )
            ->get();

        return response()->json(['success' => true, 'data' => $teachers]);
    }

    public function getStudents()
    {
        $students = DB::table('students')
            ->join('users', 'students.user_id', '=', 'users.user_id')
            ->select(
                'students.student_id as id',
                'users.full_name',
                'users.email',
                'students.student_code',
                'students.level'
            )
            ->get();

        return response()->json(['success' => true, 'data' => $students]);
    }

    public function getParents()
    {
        $parents = DB::table('parents')
            ->join('users', 'parents.user_id', '=', 'users.user_id')
            ->select(
                'parents.parent_id as id',
                'users.full_name',
                'users.email',
                'users.phone'
            )
            ->get();

        return response()->json(['success' => true, 'data' => $parents]);
    }

    public function createStudent(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'full_name'       => 'required|string|max:255',
            'academic_number' => 'nullable|string',
            'level'           => 'nullable|string',
            'email'           => 'required|email|unique:users,email',
            'password'        => 'required|string|min:6',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => $validator->errors()->first()], 422);
        }

        DB::beginTransaction();
        try {
            $user = User::create([
                'full_name'     => $request->full_name,
                'email'         => $request->email,
                'password'      => Hash::make($request->password),
                'role_id'       => 3,
                'university_id' => $request->academic_number,
                'status'        => 'active',
                'username'      => 'student_' . time(),
            ]);

            $studentId = DB::table('students')->insertGetId([
                'user_id'      => $user->user_id,
                'student_code' => $request->academic_number ?? ('S' . time()),
                'level'        => $request->level ?? '',
                'created_at'   => now(),
                'updated_at'   => now(),
            ]);

            // Auto-enroll: سجّل الطالب بكل مواد قسم الرئيس
            $head = DB::table('heads')->where('user_id', $request->user()->user_id)->first();
            if ($head) {
                $courseIds = DB::table('course_program')
                    ->join('programs', 'course_program.program_id', '=', 'programs.id')
                    ->where('programs.department_id', $head->department_id)
                    ->pluck('course_program.course_id')
                    ->unique();

                foreach ($courseIds as $courseId) {
                    DB::table('enrollments')->insertOrIgnore([
                        'student_id'      => $studentId,
                        'course_id'       => $courseId,
                        'status'          => 'active',
                        'enrollment_date' => now(),
                        'created_at'      => now(),
                        'updated_at'      => now(),
                    ]);
                }
            }

            DB::commit();
            return response()->json(['success' => true, 'message' => 'تم إنشاء حساب الطالب بنجاح'], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function createParent(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'full_name' => 'required|string|max:255',
            'phone'     => 'required|string|unique:users,phone',
            'email'     => 'nullable|email|unique:users,email',
            'password'  => 'required|string|min:6',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => $validator->errors()->first()], 422);
        }

        DB::beginTransaction();
        try {
            $user = User::create([
                'full_name' => $request->full_name,
                'phone'     => $request->phone,
                'email'     => $request->email,
                'password'  => Hash::make($request->password),
                'role_id'   => 4,
                'status'    => 'active',
                'username'  => 'parent_' . time(),
            ]);

            DB::table('parents')->insert([
                'user_id'    => $user->user_id,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::commit();
            return response()->json(['success' => true, 'message' => 'تم إنشاء حساب ولي الأمر بنجاح'], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    // ─── Courses ──────────────────────────────────────────────────
    public function getCourses()
    {
        $courses = Course::select('course_id as id', 'title')->orderBy('title')->get();
        return response()->json(['success' => true, 'data' => $courses]);
    }

    // ─── Trainers – Create ────────────────────────────────────────
    public function createTrainer(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'full_name'      => 'required|string|max:255',
            'phone'          => 'required|string|unique:users,phone',
            'email'          => 'nullable|email|unique:users,email',
            'password'       => 'required|string|min:6',
            'specialization' => 'required|string',
            'course_ids'     => 'nullable|array',
            'course_ids.*'   => 'integer|exists:courses,course_id',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => $validator->errors()->first()], 422);
        }

        DB::beginTransaction();
        try {
            $user = User::create([
                'full_name' => $request->full_name,
                'phone'     => $request->phone,
                'email'     => $request->email,
                'password'  => Hash::make($request->password),
                'role_id'   => 2,
                'status'    => 'active',
                'username'  => 'teacher_' . time(),
            ]);

            $teacher = Teacher::create([
                'user_id'        => $user->user_id,
                'specialization' => $request->specialization,
            ]);

            foreach ((array) $request->course_ids as $courseId) {
                DB::table('course_teachers')->insert([
                    'course_id'  => $courseId,
                    'teacher_id' => $teacher->teacher_id,
                    'role'       => 'teacher',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            DB::commit();
            return response()->json(['success' => true, 'message' => 'تم إنشاء حساب المعلم بنجاح'], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    // ─── Leave Requests ───────────────────────────────────────────
    public function getLeaveRequests()
    {
        $requests = DB::table('leave_requests')
            ->leftJoin('students', 'leave_requests.student_id', '=', 'students.student_id')
            ->leftJoin('teachers', 'leave_requests.teacher_id', '=', 'teachers.teacher_id')
            ->leftJoin('users as su', 'students.user_id', '=', 'su.user_id')
            ->leftJoin('users as tu', 'teachers.user_id', '=', 'tu.user_id')
            ->select(
                'leave_requests.id',
                'leave_requests.type',
                'leave_requests.date',
                'leave_requests.reason',
                'leave_requests.status',
                DB::raw('COALESCE(su.full_name, tu.full_name) as requester_name'),
                DB::raw('CASE WHEN leave_requests.student_id IS NOT NULL THEN "طالب" ELSE "معلم" END as requester_type')
            )
            ->orderBy('leave_requests.created_at', 'desc')
            ->get();

        return response()->json(['success' => true, 'data' => $requests]);
    }

    public function respondLeaveRequest(Request $request, $id)
    {
        $request->validate(['status' => 'required|in:approved,rejected']);

        $affected = DB::table('leave_requests')
            ->where('id', $id)
            ->update(['status' => $request->status, 'updated_at' => now()]);

        if (!$affected) {
            return response()->json(['success' => false, 'message' => 'الطلب غير موجود'], 404);
        }

        return response()->json(['success' => true, 'message' => 'تم تحديث حالة الطلب']);
    }

    // ─── Teachers list (for report dropdown) ─────────────────────
    public function getTeachers()
    {
        $teachers = DB::table('teachers')
            ->join('users', 'teachers.user_id', '=', 'users.user_id')
            ->select('teachers.teacher_id as id', 'users.full_name as name', 'teachers.specialization')
            ->get();

        return response()->json(['success' => true, 'data' => $teachers]);
    }

    // ─── Report Requests ──────────────────────────────────────────
    public function createReportRequest(Request $request)
    {
        $request->validate([
            'teacher_id'  => 'required|exists:teachers,teacher_id',
            'report_type' => 'required|string',
        ]);

        DB::table('report_requests')->insert([
            'head_id'     => $request->user()->user_id,
            'teacher_id'  => $request->teacher_id,
            'report_type' => $request->report_type,
            'notes'       => $request->notes ?? '',
            'status'      => 'pending',
            'created_at'  => now(),
            'updated_at'  => now(),
        ]);

        return response()->json(['success' => true, 'message' => 'تم إرسال طلب التقرير بنجاح']);
    }

    // ─── Schedule ─────────────────────────────────────────────────
    public function getSchedule(Request $request)
    {
        $entries = DB::table('head_schedule_entries')
            ->where('head_user_id', $request->user()->user_id)
            ->orderBy('day')
            ->orderBy('class_name')
            ->get(['id', 'day', 'class_name', 'content']);

        return response()->json(['success' => true, 'data' => $entries]);
    }

    public function createSchedule(Request $request)
    {
        $request->validate(['entries' => 'required|array|min:1']);

        $userId = $request->user()->user_id;

        // حذف القديم وإعادة الإدراج
        DB::table('head_schedule_entries')->where('head_user_id', $userId)->delete();

        $rows = array_map(fn($e) => [
            'head_user_id' => $userId,
            'day'          => $e['day']        ?? '',
            'class_name'   => $e['class_name'] ?? '',
            'content'      => $e['content']    ?? '',
            'created_at'   => now(),
            'updated_at'   => now(),
        ], $request->entries);

        DB::table('head_schedule_entries')->insert($rows);

        return response()->json(['success' => true, 'message' => 'تم حفظ الجدول بنجاح']);
    }

    public function updateSchedule(Request $request, $id)
    {
        $request->validate(['content' => 'required|string']);

        DB::table('head_schedule_entries')
            ->where('id', $id)
            ->where('head_user_id', $request->user()->user_id)
            ->update(['content' => $request->content, 'updated_at' => now()]);

        return response()->json(['success' => true, 'message' => 'تم التحديث بنجاح']);
    }
}
