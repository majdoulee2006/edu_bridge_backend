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
            ->get(['announcements.announcement_id', 'announcements.title', 'announcements.content', 'announcements.image', 'announcements.created_at', 'users.full_name as author_name'])
            ->map(fn($a) => [
                'id'          => $a->announcement_id,
                'title'       => $a->title,
                'content'     => $a->content,
                'body'        => $a->content,
                'image_url'   => $a->image ? url('storage/' . $a->image) : null,
                'author_name' => $a->author_name,
                'time_ago'    => \Carbon\Carbon::parse($a->created_at)->diffForHumans(),
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

    // ─── Teachers by Course ───────────────────────────────────────
    public function getTeachersByCourse($courseId)
    {
        $teachers = DB::table('course_teachers')
            ->join('teachers', 'course_teachers.teacher_id', '=', 'teachers.teacher_id')
            ->join('users', 'teachers.user_id', '=', 'users.user_id')
            ->where('course_teachers.course_id', $courseId)
            ->get(['teachers.teacher_id as id', 'users.full_name as name']);

        // fallback: if no teachers assigned to this course, return all
        if ($teachers->isEmpty()) {
            $teachers = DB::table('teachers')
                ->join('users', 'teachers.user_id', '=', 'users.user_id')
                ->get(['teachers.teacher_id as id', 'users.full_name as name']);
        }

        return response()->json(['success' => true, 'data' => $teachers]);
    }

    // ─── Students by Course ───────────────────────────────────────
    public function getStudentsByCourse($courseId)
    {
        $students = DB::table('enrollments')
            ->join('students', 'enrollments.student_id', '=', 'students.student_id')
            ->join('users', 'students.user_id', '=', 'users.user_id')
            ->where('enrollments.course_id', $courseId)
            ->where('enrollments.status', 'active')
            ->get(['students.student_id as id', 'users.full_name', 'students.student_code', 'students.level']);

        // fallback: if no enrollments, return all students
        if ($students->isEmpty()) {
            $students = DB::table('students')
                ->join('users', 'students.user_id', '=', 'users.user_id')
                ->get(['students.student_id as id', 'users.full_name', 'students.student_code', 'students.level']);
        }

        return response()->json(['success' => true, 'data' => $students]);
    }

    // ─── Report Requests ──────────────────────────────────────────
    public function getReportRequests(Request $request)
    {
        $requests = DB::table('report_requests')
            ->join('teachers', 'report_requests.teacher_id', '=', 'teachers.teacher_id')
            ->join('users as tu', 'teachers.user_id', '=', 'tu.user_id')
            ->join('students', 'report_requests.student_id', '=', 'students.student_id')
            ->join('users as su', 'students.user_id', '=', 'su.user_id')
            ->leftJoin('courses', 'report_requests.course_id', '=', 'courses.course_id')
            ->where('report_requests.head_id', $request->user()->user_id)
            ->orderBy('report_requests.created_at', 'desc')
            ->get([
                'report_requests.id',
                'report_requests.report_type',
                'report_requests.notes',
                'report_requests.status',
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
            $attQ  = DB::table('attendances')->where('student_id', $studentId);
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
                'created_at'  => now(),
                'updated_at'  => now(),
            ]);

            // إشعار لأولياء الأمر
            $parentIds = DB::table('parent_students')
                ->where('student_id', $studentId)->pluck('parent_id');
            foreach ($parentIds as $parentId) {
                $parentUserId = DB::table('parents')->where('parent_id', $parentId)->value('user_id');
                if ($parentUserId) {
                    DB::table('notifications')->insert([
                        'user_id'    => $parentUserId,
                        'title'      => 'تقرير أكاديمي للطالب ' . $studentName,
                        'message'    => $notes,
                        'type'       => 'report',
                        'is_read'    => 0,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }

            return response()->json(['success' => true, 'message' => 'تم توليد التقرير الأكاديمي وإرساله للأهل']);
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
        }

        return response()->json(['success' => true, 'message' => 'تم إرسال طلب التقرير السلوكي للمدرب']);
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

        foreach ($userIds->unique() as $uid) {
            DB::table('notifications')->insert([
                'user_id'    => $uid,
                'title'      => 'إعلان جديد من رئيس القسم',
                'message'    => $request->title,
                'type'       => 'announcement',
                'is_read'    => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        return response()->json(['success' => true, 'message' => 'تم نشر الإعلان بنجاح', 'id' => $announcementId]);
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
            'type'       => 'academic',
            'category'   => 'academic',
            'is_read'    => 0,
            'created_at' => $now,
            'updated_at' => $now,
        ])->all();

        DB::table('notifications')->insert($rows);

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
}
