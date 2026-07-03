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
            ->join('users', 'announcements.user_id', '=', 'users.user_id')
            ->orderBy('announcements.created_at', 'desc')
            ->limit(5)
            ->get(['announcements.announcement_id', 'announcements.title', 'announcements.content', 'announcements.image', 'announcements.link_url', 'announcements.created_at', 'announcements.user_id', 'users.full_name as author_name'])
            ->map(fn($a) => [
                'id'          => $a->announcement_id,
                'title'       => $a->title,
                'content'     => $a->content,
                'body'        => $a->content,
                'image_url'   => $a->image ? url('storage/' . $a->image) : null,
                'link_url'    => $a->link_url,
                'author_name' => $a->author_name,
                'time_ago'    => \Carbon\Carbon::parse($a->created_at)->diffForHumans(),
                'is_mine'     => $a->user_id === $request->user()->user_id,
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
            ->where('notifications.user_id', $request->user()->user_id)
            ->leftJoin('leave_requests', function ($join) {
                $join->on('leave_requests.id', '=', 'notifications.related_id')
                     ->where('notifications.type', '=', 'leave_request');
            })
            ->orderBy('notifications.created_at', 'desc')
            ->limit(30)
            ->get([
                'notifications.id',
                'notifications.title',
                'notifications.message',
                'notifications.type',
                'notifications.related_id',
                'notifications.is_read',
                'notifications.created_at',
                'leave_requests.status as leave_status',
            ])
            ->map(fn($n) => [
                'id'           => $n->id,
                'title'        => $n->title,
                'message'      => $n->message,
                'type'         => $n->type ?? 'general',
                'related_id'   => $n->related_id,
                'is_read'      => (bool) $n->is_read,
                'created_at'   => $n->created_at,
                'leave_status' => $n->leave_status ?? null,
            ]);

        return response()->json(['success' => true, 'data' => $notifications]);
    }

    public function markNotificationRead(Request $request, $id)
    {
        DB::table('notifications')
            ->where('id', $id)
            ->where('user_id', $request->user()->user_id)
            ->update(['is_read' => true, 'updated_at' => now()]);

        return response()->json(['success' => true]);
    }

    public function markAllNotificationsRead(Request $request)
    {
        DB::table('notifications')
            ->where('user_id', $request->user()->user_id)
            ->update(['is_read' => true, 'updated_at' => now()]);

        return response()->json(['success' => true]);
    }

    // ─── Users – Trainers / Students / Parents ────────────────────
    public function getTrainers(Request $request)
    {
        $studentId = $request->query('student_id');
        $teachers = collect();

        if ($studentId) {
            $courseIds = DB::table('enrollments')
                ->where('student_id', $studentId)
                ->pluck('course_id');

            if ($courseIds->isNotEmpty()) {
                $teachers = DB::table('teachers')
                    ->join('users', 'teachers.user_id', '=', 'users.user_id')
                    ->join('course_teachers', 'teachers.teacher_id', '=', 'course_teachers.teacher_id')
                    ->whereIn('course_teachers.course_id', $courseIds)
                    ->select(
                        'teachers.teacher_id as id',
                        'users.full_name',
                        'users.email',
                        'users.phone',
                        'teachers.specialization'
                    )
                    ->distinct()
                    ->get();
            }
        }

        // Fallback: If no student selected or no teachers found for this student, return all department teachers
        if ($teachers->isEmpty()) {
            $head = DB::table('heads')->where('user_id', auth()->user()->user_id)->first();
            if ($head) {
                $courseIds = DB::table('course_program')
                    ->join('programs', 'course_program.program_id', '=', 'programs.id')
                    ->where('programs.department_id', $head->department_id)
                    ->pluck('course_program.course_id');

                $teachers = DB::table('teachers')
                    ->join('users', 'teachers.user_id', '=', 'users.user_id')
                    ->join('course_teachers', 'teachers.teacher_id', '=', 'course_teachers.teacher_id')
                    ->whereIn('course_teachers.course_id', $courseIds)
                    ->select(
                        'teachers.teacher_id as id',
                        'users.full_name',
                        'users.email',
                        'users.phone',
                        'teachers.specialization'
                    )
                    ->distinct()
                    ->get();
            }
        }

        // Ultimate fallback: If still empty, return all teachers
        if ($teachers->isEmpty()) {
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
        }

        return response()->json(['success' => true, 'data' => $teachers]);
    }

    public function getStudents(Request $request)
    {
        $head = DB::table('heads')->where('user_id', $request->user()->user_id)->first();
        if (!$head) {
            return response()->json(['success' => false, 'message' => 'HOD not found'], 404);
        }

        $programIds = DB::table('programs')
            ->where('department_id', $head->department_id)
            ->pluck('id');

        $deptName = DB::table('departments')
            ->where('department_id', $head->department_id)
            ->value('name');

        $courseIds = DB::table('course_program')
            ->join('programs', 'course_program.program_id', '=', 'programs.id')
            ->where('programs.department_id', $head->department_id)
            ->pluck('course_program.course_id');

        $students = DB::table('students')
            ->join('users', 'students.user_id', '=', 'users.user_id')
            ->leftJoin('enrollments', 'students.student_id', '=', 'enrollments.student_id')
            ->where(function ($query) use ($programIds, $deptName, $courseIds) {
                $query->whereIn('students.program_id', $programIds);
                if ($deptName) {
                    $query->orWhere('users.department', $deptName);
                }
                if ($courseIds->isNotEmpty()) {
                    $query->orWhereIn('enrollments.course_id', $courseIds);
                }
            })
            ->select(
                'students.student_id as id',
                'users.full_name',
                'users.email',
                'students.student_code',
                'students.level'
            )
            ->distinct()
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
        // student_id في leave_requests يخزّن user_id مباشرة
        $requests = DB::table('leave_requests')
            ->leftJoin('users as su', 'leave_requests.student_id', '=', 'su.user_id')
            ->leftJoin('teachers', 'leave_requests.teacher_id', '=', 'teachers.teacher_id')
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

        $leaveRequest = DB::table('leave_requests')->where('id', $id)->first();
        if (!$leaveRequest) {
            return response()->json(['success' => false, 'message' => 'الطلب غير موجود'], 404);
        }

        $newStatus = $request->status === 'approved' ? 'approved' : 'rejected';

        DB::table('leave_requests')
            ->where('id', $id)
            ->update(['status' => $newStatus, 'updated_at' => now()]);

        if ($leaveRequest->student_id) {
            $title   = $newStatus === 'approved' ? 'تمت الموافقة على طلب الإجازة' : 'تم رفض طلب الإجازة';
            $message = $newStatus === 'approved'
                ? 'وافق رئيس القسم على طلب إجازتك بتاريخ ' . $leaveRequest->date
                : 'تم رفض طلب إجازتك بتاريخ ' . $leaveRequest->date . ' من قِبل رئيس القسم';

            DB::table('notifications')->insert([
                'user_id'    => $leaveRequest->student_id,
                'title'      => $title,
                'message'    => $message,
                'type'       => 'leave_request',
                'related_id' => $id,
                'is_read'    => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            // FCM — نجيب user_id من جدول الطلاب
            $studentUserId = DB::table('students')->where('student_id', $leaveRequest->student_id)->value('user_id');
            if ($studentUserId) {
                \App\Services\FcmService::sendToUser($studentUserId, $title, $message, ['type' => 'leave_request']);
            }
        }

        return response()->json(['success' => true, 'message' => 'تم تحديث حالة الطلب']);
    }

    // ─── Teachers list (for report dropdown) ─────────────────────
    public function getTeachers(Request $request)
    {
        $head = DB::table('heads')->where('user_id', $request->user()->user_id)->first();
        if (!$head) {
            return response()->json(['success' => false, 'message' => 'HOD not found'], 404);
        }

        $courseIds = DB::table('course_program')
            ->join('programs', 'course_program.program_id', '=', 'programs.id')
            ->where('programs.department_id', $head->department_id)
            ->pluck('course_program.course_id');

        $teachers = DB::table('course_teachers')
            ->join('teachers', 'course_teachers.teacher_id', '=', 'teachers.teacher_id')
            ->join('users', 'teachers.user_id', '=', 'users.user_id')
            ->whereIn('course_teachers.course_id', $courseIds)
            ->select('teachers.teacher_id as id', 'users.full_name as name', 'teachers.specialization')
            ->distinct()
            ->get();

        return response()->json(['success' => true, 'data' => $teachers]);
    }

    // ─── Teachers by Course ───────────────────────────────────────
    public function getTeachersByCourse($courseId)
    {
        $teachers = DB::table('course_teachers')
            ->join('teachers', 'course_teachers.teacher_id', '=', 'teachers.teacher_id')
            ->join('users', 'teachers.user_id', '=', 'users.user_id')
            ->where('course_teachers.course_id', $courseId)
            ->get(['teachers.teacher_id as id', 'users.user_id', 'users.full_name as name', 'users.full_name']);

        if ($teachers->isEmpty()) {
            $teachers = DB::table('teachers')
                ->join('users', 'teachers.user_id', '=', 'users.user_id')
                ->get(['teachers.teacher_id as id', 'users.user_id', 'users.full_name as name', 'users.full_name']);
        }

        return response()->json(['success' => true, 'data' => $teachers]);
    }

    // ─── Students by Course ───────────────────────────────────────
    public function getStudentsByCourse($courseId)
    {
        $head = DB::table('heads')->where('user_id', auth()->user()->user_id)->first();
        
        if ($head) {
            $courseIds = DB::table('course_program')
                ->join('programs', 'course_program.program_id', '=', 'programs.id')
                ->where('programs.department_id', $head->department_id)
                ->pluck('course_program.course_id');

            $students = DB::table('enrollments')
                ->join('students', 'enrollments.student_id', '=', 'students.student_id')
                ->join('users', 'students.user_id', '=', 'users.user_id')
                ->whereIn('enrollments.course_id', $courseIds)
                ->select('students.student_id as id', 'users.full_name', 'students.student_code', 'students.level')
                ->distinct()
                ->get();
        } else {
            $students = collect();
        }

        // fallback: if no students found in department, return all students in system
        if ($students->isEmpty()) {
            $students = DB::table('students')
                ->join('users', 'students.user_id', '=', 'users.user_id')
                ->select('students.student_id as id', 'users.full_name', 'students.student_code', 'students.level')
                ->get();
        }

        return response()->json(['success' => true, 'data' => $students]);
    }

    // ─── Report Requests ──────────────────────────────────────────
    public function getReportRequests(Request $request)
    {
        $head = DB::table('heads')->where('user_id', $request->user()->user_id)->first();
        $departmentId = $head ? $head->department_id : null;

        $type = $request->query('type'); // 'my' or 'advisor'

        $query = DB::table('report_requests')
            ->leftJoin('teachers', 'report_requests.teacher_id', '=', 'teachers.teacher_id')
            ->leftJoin('users as tu', 'teachers.user_id', '=', 'tu.user_id')
            ->join('students', 'report_requests.student_id', '=', 'students.student_id')
            ->join('users as su', 'students.user_id', '=', 'su.user_id')
            ->leftJoin('courses', 'report_requests.course_id', '=', 'courses.course_id')
            ->join('users as ru', 'report_requests.head_id', '=', 'ru.user_id')
            ->leftJoin('performance_reports', 'report_requests.id', '=', 'performance_reports.report_request_id');

        if ($type === 'advisor') {
            $query->where('report_requests.head_id', '!=', $request->user()->user_id);
            if ($departmentId) {
                $programIds = DB::table('programs')
                    ->where('department_id', $departmentId)
                    ->pluck('id');
                $deptName = DB::table('departments')->where('department_id', $departmentId)->value('name')
                    ?? $request->user()->department;

                $query->where(function ($q) use ($programIds, $deptName) {
                    if ($programIds->isNotEmpty()) {
                        $q->whereIn('students.program_id', $programIds);
                    }
                    if ($deptName) {
                        $q->orWhere('su.department', $deptName);
                    }
                });
            }
        } else {
            $query->where('report_requests.head_id', $request->user()->user_id);
        }

        $requests = $query->orderBy('report_requests.created_at', 'desc')
            ->get([
                'report_requests.id',
                'report_requests.report_type',
                DB::raw("CASE WHEN report_requests.status = 'completed' THEN COALESCE(performance_reports.recommendations, '') ELSE COALESCE(report_requests.notes, '') END as notes"),
                'report_requests.status',
                'report_requests.sent_to_parent',
                'report_requests.year',
                'report_requests.created_at',
                'tu.full_name as teacher_name',
                'su.full_name as student_name',
                'courses.title as course_name',
            ]);

        return response()->json(['success' => true, 'data' => $requests]);
    }

    public function createReportRequest(Request $request)
    {
        $request->validate([
            'student_id'  => 'required|exists:students,student_id',
            'teacher_id'  => 'nullable|exists:teachers,teacher_id',
            'report_type' => 'required|in:academic,behavioral',
            'course_id'   => 'nullable|exists:courses,course_id',
            'year'        => 'nullable|integer|in:1,2',
        ]);

        $studentId = $request->student_id;
        $courseId  = $request->course_id;

        $studentRow = DB::table('students')
            ->join('users', 'students.user_id', '=', 'users.user_id')
            ->where('students.student_id', $studentId)
            ->first(['users.full_name as name', 'students.student_id']);

        $studentName = $studentRow->name ?? 'الطالب';

        // ─── أكاديمي: النظام يحسب تلقائياً ─────────────────────────
        if ($request->report_type === 'academic') {
            // متوسط العلامات
            $gradesQ = DB::table('grades')->where('student_id', $studentId);
            if ($courseId) $gradesQ->where('course_id', $courseId);
            $grades   = $gradesQ->avg('score');
            $avgGrade = $grades !== null ? round($grades, 1) : null;

            // نسبة الحضور
            $attQ  = DB::table('attendance')->where('student_id', $studentId);
            if ($courseId) $attQ->where('course_id', $courseId);
            $att     = $attQ->get(['status']);
            $total   = $att->count();
            $present = $att->where('status', 'present')->count();
            $attRate = $total > 0 ? round(($present / $total) * 100, 1) : null;

            // توليد نص التقرير
            $courseName = $courseId
                ? DB::table('courses')->where('course_id', $courseId)->value('title')
                : null;

            $notes = 'التقرير الأكاديمي للطالب: ' . $studentName;
            if ($courseName) $notes .= " | المادة: $courseName";
            if ($request->year) $notes .= " | السنة: {$request->year}";
            $notes .= "\n";
            $notes .= $avgGrade !== null
                ? "متوسط العلامات: $avgGrade / 100\n"
                : "لا توجد علامات مسجّلة.\n";
            $notes .= $attRate !== null
                ? "نسبة الحضور: $attRate% ($present حضور من $total جلسة)"
                : "لا توجد بيانات حضور مسجّلة.";

            DB::table('report_requests')->insert([
                'head_id'     => $request->user()->user_id,
                'student_id'  => $studentId,
                'teacher_id'  => $request->teacher_id,
                'report_type' => 'academic',
                'course_id'   => $courseId,
                'year'        => $request->year,
                'notes'       => $notes,
                'status'      => 'completed',
                'sent_to_parent' => false,
                'created_at'  => now(),
                'updated_at'  => now(),
            ]);

            return response()->json(['success' => true, 'message' => 'تم توليد التقرير الأكاديمي بنجاح بقسم سجل التقارير']);
        }

        // ─── سلوكي: أرسل للمعلم ──────────────────────────────────
        DB::table('report_requests')->insert([
            'head_id'     => $request->user()->user_id,
            'student_id'  => $studentId,
            'teacher_id'  => $request->teacher_id,
            'report_type' => 'behavioral',
            'course_id'   => $courseId,
            'year'        => $request->year,
            'notes'       => $request->notes ?? '',
            'status'      => 'pending',
            'sent_to_parent' => false,
            'created_at'  => now(),
            'updated_at'  => now(),
        ]);

        $teacher = $request->teacher_id
            ? DB::table('teachers')->where('teacher_id', $request->teacher_id)->first()
            : null;
        if ($teacher) {
            DB::table('notifications')->insert([
                'user_id'    => $teacher->user_id,
                'title'      => 'طلب تقرير سلوكي',
                'message'    => 'طُلب منك تقرير سلوكي عن الطالب ' . $studentName,
                'type'       => 'report',
                'is_read'    => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            \App\Services\FcmService::sendToUser(
                $teacher->user_id,
                'طلب تقرير سلوكي',
                'طُلب منك تقرير سلوكي عن الطالب ' . $studentName,
                ['type' => 'report']
            );
        }

        return response()->json(['success' => true, 'message' => 'تم إرسال طلب التقرير السلوكي للمدرب']);
    }

    public function sendReportToParent(Request $request, $id)
    {
        $requestRow = DB::table('report_requests')->where('id', $id)->first();
        if (!$requestRow) {
            return response()->json(['success' => false, 'message' => 'الطلب غير موجود'], 404);
        }

        if ($requestRow->status !== 'completed') {
            return response()->json(['success' => false, 'message' => 'التقرير لم يتم إتمامه بعد من قبل المدرب'], 400);
        }

        $studentRow = DB::table('students')
            ->join('users', 'students.user_id', '=', 'users.user_id')
            ->where('students.student_id', $requestRow->student_id)
            ->first(['users.full_name as name']);
        $studentName = $studentRow->name ?? 'الطالب';

        DB::table('report_requests')
            ->where('id', $id)
            ->update([
                'sent_to_parent' => true,
                'updated_at' => now()
            ]);

        $parentIds = DB::table('parent_students')
            ->where('student_id', $requestRow->student_id)
            ->pluck('parent_id');

        $performanceReport = DB::table('performance_reports')->where('report_request_id', $id)->first();
        $notificationMessage = $performanceReport ? $performanceReport->recommendations : $requestRow->notes;

        foreach ($parentIds as $parentId) {
            $parentUserId = DB::table('parents')->where('parent_id', $parentId)->value('user_id');
            if ($parentUserId) {
                DB::table('notifications')->insert([
                    'user_id'    => $parentUserId,
                    'title'      => 'تقرير أداء للطالب ' . $studentName,
                    'message'    => $notificationMessage,
                    'type'       => 'report',
                    'related_id' => $id,
                    'is_read'    => 0,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                try {
                    \App\Services\FcmService::sendToUser($parentUserId, 'تقرير أداء للطالب ' . $studentName, $notificationMessage, ['type' => 'report', 'related_id' => (string)$id]);
                } catch (\Exception $e) {
                    \Log::error("FCM failed: " . $e->getMessage());
                }
            }
        }

        return response()->json(['success' => true, 'message' => 'تم إرسال التقرير للأهل بنجاح']);
    }

    public function deleteReportRequest(Request $request, $id)
    {
        $deleted = DB::table('report_requests')->where('id', $id)->delete();
        if ($deleted) {
            return response()->json(['success' => true, 'message' => 'تم حذف طلب التقرير بنجاح']);
        }
        return response()->json(['success' => false, 'message' => 'الطلب غير موجود'], 404);
    }

    // ─── Announcements ────────────────────────────────────────────
    public function createAnnouncement(Request $request)
    {
        $request->validate([
            'title'   => 'required|string|max:255',
            'content' => 'required|string',
        ]);

        // mapping: 'teachers'→'teacher', 'students'→'student', 'all'→null
        $audienceInput = $request->target_audience ?? 'all';
        $targetRole = match($audienceInput) {
            'teachers' => 'teacher',
            'students' => 'student',
            default    => null,
        };

        $imagePath = null;
        if ($request->hasFile('image')) {
            $imagePath = $request->file('image')->store('announcements', 'public');
        }

        $announcementId = DB::table('announcements')->insertGetId([
            'user_id'     => $request->user()->user_id,
            'title'       => $request->title,
            'content'     => $request->content,
            'target_role' => $targetRole,
            'image'       => $imagePath,
            'link_url'    => $request->link_url ?? null,
            'created_at'  => now(),
            'updated_at'  => now(),
        ]);

        $audience = $audienceInput;
        $userIds  = collect();

        if (in_array($audience, ['teachers', 'all'])) {
            $teacherIds = DB::table('teachers')
                ->join('users', 'teachers.user_id', '=', 'users.user_id')
                ->pluck('users.user_id');
            $userIds = $userIds->merge($teacherIds);
        }

        if (in_array($audience, ['students', 'all'])) {
            $studentIds = DB::table('students')
                ->join('users', 'students.user_id', '=', 'users.user_id')
                ->pluck('users.user_id');
            $userIds = $userIds->merge($studentIds);
        }

        if ($audience === 'all') {
            $parentIds = DB::table('parents')
                ->join('users', 'parents.user_id', '=', 'users.user_id')
                ->pluck('users.user_id');
            $userIds = $userIds->merge($parentIds);
        }

        $now = now();
        $rows = $userIds->unique()->map(fn($uid) => [
            'user_id'    => $uid,
            'sender_id'  => $request->user()->user_id,
            'title'      => 'إعلان جديد من رئيس القسم',
            'message'    => $request->title,
            'type'       => 'announcement',
            'category'   => 'administrative',
            'related_id' => $announcementId,
            'is_read'    => 0,
            'created_at' => $now,
            'updated_at' => $now,
        ])->all();
        if (!empty($rows)) {
            DB::table('notifications')->insert($rows);
            foreach ($userIds->unique() as $uid) {
                \App\Services\FcmService::sendToUser($uid, 'إعلان جديد من رئيس القسم', $request->title, [
                    'type' => 'announcement', 'related_id' => (string) $announcementId,
                ]);
            }
        }

        return response()->json(['success' => true, 'message' => 'تم نشر الإعلان بنجاح', 'id' => $announcementId]);
    }

    public function updateAnnouncement(Request $request, $id)
    {
        $request->validate([
            'title'   => 'required|string|max:255',
            'content' => 'required|string',
        ]);

        $ann = DB::table('announcements')
            ->where('announcement_id', $id)
            ->where('user_id', $request->user()->user_id)
            ->first();

        if (!$ann) return response()->json(['success' => false, 'message' => 'الإعلان غير موجود'], 404);

        $imagePath = $ann->image;
        if ($request->hasFile('image')) {
            $imagePath = $request->file('image')->store('announcements', 'public');
        }

        $targetRole = match($request->target_audience ?? 'all') {
            'teachers' => 'teacher',
            'students' => 'student',
            default    => null,
        };

        DB::table('announcements')->where('announcement_id', $id)->update([
            'title'       => $request->title,
            'content'     => $request->content,
            'target_role' => $targetRole,
            'image'       => $imagePath,
            'link_url'    => $request->link_url ?? $ann->link_url,
            'updated_at'  => now(),
        ]);

        return response()->json(['success' => true, 'message' => 'تم تحديث الإعلان بنجاح']);
    }

    public function deleteAnnouncement(Request $request, $id)
    {
        $ann = DB::table('announcements')
            ->where('announcement_id', $id)
            ->where('user_id', $request->user()->user_id)
            ->first();

        if (!$ann) return response()->json(['success' => false, 'message' => 'الإعلان غير موجود'], 404);

        DB::table('announcements')->where('announcement_id', $id)->delete();
        DB::table('notifications')->where('related_id', $id)->where('type', 'announcement')->delete();

        return response()->json(['success' => true, 'message' => 'تم حذف الإعلان بنجاح']);
    }

    public function sendNotification(Request $request)
    {
        $request->validate([
            'title'   => 'required|string|max:255',
            'message' => 'required|string',
            'target'  => 'required|in:students,students_teachers,all',
        ]);

        $userIds = collect();

        // always include students
        $studentIds = DB::table('students')
            ->join('users', 'students.user_id', '=', 'users.user_id')
            ->pluck('users.user_id');
        $userIds = $userIds->merge($studentIds);

        // include teachers
        if (in_array($request->target, ['students_teachers', 'all'])) {
            $teacherIds = DB::table('teachers')
                ->join('users', 'teachers.user_id', '=', 'users.user_id')
                ->pluck('users.user_id');
            $userIds = $userIds->merge($teacherIds);
        }

        // include parents + affairs + admin
        if ($request->target === 'all') {
            $parentIds = DB::table('parents')
                ->join('users', 'parents.user_id', '=', 'users.user_id')
                ->pluck('users.user_id');
            $staffIds = DB::table('users')
                ->whereIn('role_id', [5, 6, 7]) // hod, affairs, admin
                ->pluck('user_id');
            $userIds = $userIds->merge($parentIds)->merge($staffIds);
        }

        $senderId = $request->user()->user_id;
        $now = now();
        $rows = $userIds->unique()->map(fn($uid) => [
            'user_id'    => $uid,
            'sender_id'  => $senderId,
            'title'      => $request->title,
            'message'    => $request->message,
            'type'       => 'administrative',
            'category'   => 'administrative',
            'is_read'    => 0,
            'created_at' => $now,
            'updated_at' => $now,
        ])->all();

        DB::table('notifications')->insert($rows);
        foreach ($userIds->unique() as $uid) {
            \App\Services\FcmService::sendToUser($uid, $request->title, $request->message, ['type' => 'academic']);
        }

        return response()->json([
            'success' => true,
            'message' => 'تم إرسال الإشعار بنجاح',
            'count'   => count($rows),
        ]);
    }

    public function getAnnouncements()
    {
        $announcements = DB::table('announcements')
            ->join('users', 'announcements.user_id', '=', 'users.user_id')
            ->orderBy('announcements.created_at', 'desc')
            ->limit(20)
            ->get([
                'announcements.announcement_id as id',
                'announcements.title',
                'announcements.content',
                'announcements.image',
                'announcements.link_url',
                'announcements.created_at',
                'users.full_name as author_name',
            ])
            ->map(fn($a) => array_merge((array)$a, [
                'image_url' => $a->image ? url('storage/' . $a->image) : null,
                'time_ago'  => \Carbon\Carbon::parse($a->created_at)->diffForHumans(),
            ]));

        return response()->json(['success' => true, 'data' => $announcements]);
    }

    // ─── Programs with Schedule (for organization screen) ────────
    public function getProgramsSchedule()
    {
        $programs = DB::table('programs')->orderBy('name')->get(['id', 'name']);

        $result = $programs->map(function ($program) {
            $years = [1, 2];
            $yearData = [];
            foreach ($years as $year) {
                $schedules = DB::table('schedules')
                    ->join('courses', 'schedules.course_id', '=', 'courses.course_id')
                    ->join('course_program', 'courses.course_id', '=', 'course_program.course_id')
                    ->where('course_program.program_id', $program->id)
                    ->where('courses.year', $year)
                    ->orderByRaw("FIELD(schedules.day,'Sunday','Monday','Tuesday','Wednesday','Thursday','Friday','Saturday')")
                    ->orderBy('schedules.start_time')
                    ->get([
                        'schedules.schedule_id as id',
                        'schedules.day',
                        'schedules.start_time',
                        'schedules.end_time',
                        'schedules.room',
                        'courses.title as course_name',
                        'courses.year',
                    ]);
                $yearData[(string)$year] = $schedules;
            }
            return [
                'id'       => $program->id,
                'name'     => $program->name,
                'schedule' => $yearData,
            ];
        });

        return response()->json(['success' => true, 'data' => $result]);
    }

    // ─── All Courses Schedule (for display) ───────────────────────
    public function getAllSchedule()
    {
        $schedules = DB::table('schedules')
            ->join('courses', 'schedules.course_id', '=', 'courses.course_id')
            ->orderByRaw("FIELD(schedules.day,'Sunday','Monday','Tuesday','Wednesday','Thursday','Friday','Saturday')")
            ->orderBy('schedules.start_time')
            ->get([
                'schedules.schedule_id as id',
                'schedules.day',
                'schedules.start_time',
                'schedules.end_time',
                'schedules.room',
                'courses.title as course_name',
            ]);

        return response()->json(['success' => true, 'data' => $schedules]);
    }

    public function getAllExams()
    {
        $exams = DB::table('exams')
            ->join('courses', 'exams.course_id', '=', 'courses.course_id')
            ->leftJoin('course_program', 'courses.course_id', '=', 'course_program.course_id')
            ->leftJoin('programs', 'course_program.program_id', '=', 'programs.id')
            ->orderBy('exams.exam_date')
            ->selectRaw('
                exams.exam_id as id,
                exams.exam_name,
                exams.exam_date,
                exams.max_score,
                exams.room,
                courses.title as course_name,
                courses.year,
                MIN(programs.name) as program_name
            ')
            ->groupBy('exams.exam_id', 'exams.exam_name', 'exams.exam_date', 'exams.max_score', 'exams.room', 'courses.title', 'courses.year')
            ->get();

        return response()->json(['success' => true, 'data' => $exams]);
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

    // ─── طلبات تقارير العلامات ───────────────────────────────────

    public function requestGradeReport(Request $request)
    {
        $validated = $request->validate([
            'course_id'   => 'required|integer|exists:courses,course_id',
            'teacher_user_id' => 'required|integer|exists:users,user_id',
            'notes'       => 'nullable|string|max:500',
        ]);

        $boss = $request->user();

        // تحقق أن بيانات العلامات موجودة مسبقاً
        $hasEntries = DB::table('grade_events')
            ->join('grade_entries', 'grade_events.id', '=', 'grade_entries.grade_event_id')
            ->where('grade_events.course_id', $validated['course_id'])
            ->whereNotNull('grade_entries.score')
            ->exists();

        $course      = DB::table('courses')->where('course_id', $validated['course_id'])->first();
        $courseTitle = $course?->title ?? 'المادة';

        // احفظ الطلب دائماً
        $status = $hasEntries ? 'completed' : 'pending';
        $reqId  = DB::table('grade_report_requests')->insertGetId([
            'boss_user_id'    => $boss->user_id,
            'teacher_user_id' => $validated['teacher_user_id'],
            'course_id'       => $validated['course_id'],
            'notes'           => $validated['notes'] ?? null,
            'status'          => $status,
            'created_at'      => now(),
            'updated_at'      => now(),
        ]);

        if ($hasEntries) {
            // البيانات موجودة — أبلغ المسؤول مباشرة
            return response()->json([
                'success'           => true,
                'already_available' => true,
                'message'           => 'البيانات موجودة، يمكنك الاطلاع على التقرير مباشرة.',
                'course_id'         => $validated['course_id'],
            ]);
        }

        // لا توجد بيانات — أرسل إشعاراً للمعلم
        $notifMsg = "رئيس القسم يطلب إدخال علامات مادة: $courseTitle\n"
                  . "المطلوب: مذاكرة + امتحان + شفهي لكل طالب";

        DB::table('notifications')->insert([
            'user_id'    => $validated['teacher_user_id'],
            'title'      => "طلب إدخال علامات: $courseTitle",
            'message'    => $notifMsg,
            'type'       => 'grade_report_request',
            'related_id' => $reqId,
            'is_read'    => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        \App\Services\FcmService::sendToUser(
            $validated['teacher_user_id'],
            "طلب إدخال علامات: $courseTitle",
            "رئيس القسم يطلب: مذاكرة + امتحان + شفهي لمادة $courseTitle",
            [
                'type'         => 'grade_report_request',
                'request_id'   => (string) $reqId,
                'course_id'    => (string) $validated['course_id'],
                'course_title' => $courseTitle,
            ]
        );

        return response()->json(['success' => true, 'message' => 'تم إرسال الطلب للمعلم']);
    }

    public function getGradeReports(Request $request)
    {
        $boss = $request->user();

        $requests = DB::table('grade_report_requests')
            ->join('courses', 'grade_report_requests.course_id', '=', 'courses.course_id')
            ->join('users', 'grade_report_requests.teacher_user_id', '=', 'users.user_id')
            ->where('grade_report_requests.boss_user_id', $boss->user_id)
            ->select(
                'grade_report_requests.id',
                'grade_report_requests.status',
                'grade_report_requests.notes',
                'grade_report_requests.created_at',
                'courses.course_id',
                'courses.title as course_title',
                'users.full_name as teacher_name',
                'grade_report_requests.teacher_user_id',
            )
            ->orderByDesc('grade_report_requests.created_at')
            ->get();

        return response()->json(['success' => true, 'data' => $requests]);
    }

    public function remindTeacher(Request $request, $courseId)
    {
        $boss = $request->user();

        $req = DB::table('grade_report_requests')
            ->where('course_id', $courseId)
            ->where('boss_user_id', $boss->user_id)
            ->where('status', 'pending')
            ->latest()
            ->first();

        if (!$req) return response()->json(['success' => false, 'message' => 'لا يوجد طلب معلق'], 404);

        $course      = DB::table('courses')->where('course_id', $courseId)->first();
        $courseTitle = $course?->title ?? 'المادة';

        $notifMsg = "تذكير: رئيس القسم ينتظر إدخال علامات مادة: $courseTitle\n"
                  . "المطلوب: مذاكرة + امتحان + شفهي لكل طالب";

        DB::table('notifications')->insert([
            'user_id'    => $req->teacher_user_id,
            'title'      => "تذكير: علامات $courseTitle",
            'message'    => $notifMsg,
            'type'       => 'grade_report_request',
            'related_id' => $req->id,
            'is_read'    => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        \App\Services\FcmService::sendToUser(
            $req->teacher_user_id,
            "تذكير: علامات $courseTitle",
            "رئيس القسم ينتظر: مذاكرة + امتحان + شفهي لمادة $courseTitle",
            ['type' => 'grade_report_request', 'course_id' => (string)$courseId, 'course_title' => $courseTitle]
        );

        return response()->json(['success' => true, 'message' => 'تم إرسال التذكير للمعلم']);
    }

    public function getCourseGradeEntries(Request $request, $courseId)
    {
        $rows = DB::table('grade_events')
            ->join('grade_entries',  'grade_events.id',       '=', 'grade_entries.grade_event_id')
            ->join('students',       'grade_entries.student_id', '=', 'students.student_id')
            ->join('users as u',     'students.user_id',      '=', 'u.user_id')
            ->where('grade_events.course_id', $courseId)
            ->whereNotNull('grade_entries.score')
            ->select(
                'students.student_id',
                'u.full_name as student_name',
                'u.university_id',
                'grade_events.type',
                'grade_events.max_score',
                'grade_entries.score'
            )
            ->get();

        // تجميع حسب الطالب
        $grouped = [];
        foreach ($rows as $row) {
            $sid = $row->student_id;
            if (!isset($grouped[$sid])) {
                $grouped[$sid] = [
                    'student_id'    => $sid,
                    'student_name'  => $row->student_name,
                    'university_id' => $row->university_id,
                    'quiz'      => null, 'quiz_max'  => null,
                    'exam'      => null, 'exam_max'  => null,
                    'oral'      => null, 'oral_max'  => null,
                ];
            }
            $score = (float) $row->score;
            $max   = (float) $row->max_score;
            if ($row->type === 'quiz') {
                $grouped[$sid]['quiz']     = ($grouped[$sid]['quiz']     ?? 0) + $score;
                $grouped[$sid]['quiz_max'] = ($grouped[$sid]['quiz_max'] ?? 0) + $max;
            } elseif ($row->type === 'exam') {
                $grouped[$sid]['exam']     = ($grouped[$sid]['exam']     ?? 0) + $score;
                $grouped[$sid]['exam_max'] = ($grouped[$sid]['exam_max'] ?? 0) + $max;
            } elseif ($row->type === 'oral') {
                $grouped[$sid]['oral']     = ($grouped[$sid]['oral']     ?? 0) + $score;
                $grouped[$sid]['oral_max'] = ($grouped[$sid]['oral_max'] ?? 0) + $max;
            }
        }

        $result = array_values(array_map(function ($s) {
            $totalScore = ($s['quiz'] ?? 0) + ($s['exam'] ?? 0) + ($s['oral'] ?? 0);
            $totalMax   = ($s['quiz_max'] ?? 0) + ($s['exam_max'] ?? 0) + ($s['oral_max'] ?? 0);
            $s['total_score'] = $totalScore;
            $s['total_max']   = $totalMax;
            $s['average']     = $totalMax > 0 ? round($totalScore / $totalMax * 100, 1) : null;
            $s['pass']        = $s['average'] !== null && $s['average'] >= 50;
            return $s;
        }, $grouped));

        usort($result, fn($a, $b) => strcmp($a['student_name'], $b['student_name']));

        return response()->json(['success' => true, 'data' => $result]);
    }
}
