<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\StudentController;
use App\Http\Controllers\Api\TeacherController;
use App\Http\Controllers\Api\AnnouncementController;
use App\Http\Controllers\Api\AdminController;
use App\Http\Controllers\Api\HODController;
use App\Http\Controllers\Api\ScheduleController;
use App\Http\Controllers\Api\ExamScheduleController;
use App\Http\Controllers\Api\TeacherReportController;
use App\Http\Controllers\Api\ParentController;
use App\Http\Controllers\Api\DepartmentHeadController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\StudentParentController;
use App\Http\Controllers\ChatController;
use App\Http\Controllers\Api\ParentController;

// خدمة ملفات التخزين (بديل الـ symlink على Windows)
Route::get('/file/{path}', function (string $path) {
    $decoded  = urldecode($path);
    $absolute = storage_path('app/public/' . $decoded);
    abort_if(!file_exists($absolute), 404);
    return response()->file($absolute);
})->where('path', '.*');

// روابط عامة
Route::post('/login', [AuthController::class, 'login'])->name('login');
Route::post('/register', [AuthController::class, 'register']);
Route::post('/verify-otp', [AuthController::class, 'verifyOtp']);
Route::post('/resend-otp', [AuthController::class, 'resendOtp']);
Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);
Route::post('/reset-password', [AuthController::class, 'resetPassword']);
Route::post('/login-otp/send', [AuthController::class, 'sendLoginOtp']);
Route::post('/login-otp/verify', [AuthController::class, 'verifyLoginOtp']);

// روابط محمية (تحتاج توكن)
Route::middleware('auth:sanctum')->group(function () {

    Route::post('/logout', [AuthController::class, 'logout']);

    // FCM Token
    Route::post('/user/fcm-token', function (\Illuminate\Http\Request $request) {
        $request->validate(['fcm_token' => 'required|string']);
        $request->user()->update(['device_token' => $request->fcm_token]);
        return response()->json(['success' => true]);
    });
    Route::post('/profile/update', [AuthController::class, 'updateProfile']);
    Route::post('/profile/avatar', [AuthController::class, 'updateAvatar']);
    Route::post('/profile/request-change-email', [AuthController::class, 'requestChangeEmail']);
    Route::post('/profile/confirm-change-email', [AuthController::class, 'confirmChangeEmail']);
    Route::post('/profile/send-otp', [AuthController::class, 'sendProfileOtp']);
    Route::post('/profile/verify-otp', [AuthController::class, 'verifyProfileOtp']);

    // مسار البروفايل
    Route::get('/user/profile', function (Request $request) {
        $user = $request->user();
        return response()->json([
            'success' => true,
            'data' => [
                'full_name' => $user->full_name ?? $user->name ?? '',
                'email'     => $user->email     ?? '',
                'phone'     => $user->phone      ?? '',
                'role'      => $user->role       ?? '',
                'avatar'    => $user->avatar ? storageUrl($user->avatar) : null,
            ]
        ]);
    });
    #========= روابط الدردشة المشتركة ==========
    Route::post('/send-message', [ChatController::class, 'sendMessage']);
    Route::get('/messages/unread-count', [ChatController::class, 'getUnreadCount']); 
    Route::get('/messages/{otherUserId}', [ChatController::class, 'getMessages']);
    Route::put('/messages/{otherUserId}/mark-read', [ChatController::class, 'markAsRead']);
    Route::get('/messages/{otherUserId}/search', [ChatController::class, 'searchMessages']);   
    Route::delete('/messages/{messageId}', [ChatController::class, 'deleteMessage']);  
    Route::put('/messages/{messageId}/edit', [ChatController::class, 'editMessage']);
    Route::post('/groups/{groupId}/messages', [ChatController::class, 'sendGroupMessage']);
    Route::post('/groups', [ChatController::class, 'createGroup']);
    Route::get('/groups/{groupId}/messages', [ChatController::class, 'getGroupMessages']);            

    #========= روابط واجهات الطالب ==========
    Route::get('/student/dashboard', [StudentController::class, 'getDashboardData']);
    Route::get('/student/profile', [StudentController::class, 'getProfileData']);
    Route::post('/student/profile/update', [StudentController::class, 'updateProfile']);
    Route::get('/student/announcements', [AnnouncementController::class, 'getHomeAnnouncements']);
    Route::get('/student/my-schedule', [StudentController::class, 'getMySchedule']);
    Route::get('/student/my-exams', [StudentController::class, 'getMyExams']);
    Route::get('/student/my-exams/pdf', [StudentController::class, 'exportExamsPdf']);
    Route::get('/student/my-exams/excel', [StudentController::class, 'exportExamsExcel']);
    Route::get('/student/assignments', [StudentController::class, 'getMyAssignments']);
    Route::post('/student/assignments/{id}/submit', [StudentController::class, 'submitAssignment']);
    Route::get('/student/lectures', [StudentController::class, 'getMyLectures']);
    Route::get('/student/courses', [StudentController::class, 'getMyCourses']);
    Route::get('/student/grades', [StudentController::class, 'getMyGrades']);
    Route::get('/student/courses/{courseId}/materials', [StudentController::class, 'getCourseMaterials']);

    // مسارات الحضور والإجازات
    Route::get('/student/attendance', [StudentController::class, 'getMyAttendance']);
    Route::post('/student/attendance/scan', [StudentController::class, 'scanAttendanceQr']);
    Route::post('/student/attendance/{attendance_id}/excuse', [StudentController::class, 'submitAttendanceExcuse']);
    Route::get('/student/leave-requests', [StudentController::class, 'getMyAbsenceRequests']);
    Route::post('/student/leave-requests', [StudentController::class, 'requestAbsence']);

    // مسارات الإشعارات للطالب
    Route::get('/student/notifications', [StudentController::class, 'getNotifications']);
    Route::put('/student/notifications/{id}/read', [StudentController::class, 'markNotificationAsRead']);
    Route::put('/student/notifications/read-all', [StudentController::class, 'markAllNotificationsAsRead']);

    #========= روابط واجهات ولي الأمر ==========
    Route::get('/parent/children', function (Request $request) {
        $parent = \App\Models\Parents::where('user_id', $request->user()->user_id)->first();
        if (!$parent) return response()->json(['success' => true, 'data' => []]);
        $children = $parent->students()->with(['user', 'attendances', 'grades'])->get()->map(function ($student) {
            $att = $student->attendances;
            return [
                'student_id'      => $student->student_id,
                'full_name'       => $student->user->full_name ?? '',
                'level'           => $student->level ?? '',
                'attendance_rate' => $att->count() > 0
                    ? round(($att->where('status', 'present')->count() / $att->count()) * 100, 1)
                    : 0,
                'average_grade'   => round($student->grades->avg('score') ?? 0, 1),
            ];
        });
        return response()->json(['success' => true, 'data' => $children]);
    });
    Route::get('/parent/announcements', function () {
        $announcements = \DB::table('announcements')
            ->leftJoin('users', 'announcements.user_id', '=', 'users.user_id')
            ->latest('announcements.created_at')
            ->limit(10)
            ->get(['announcements.*', 'users.full_name as author_name'])
            ->map(fn($a) => [
                'id'          => $a->announcement_id,
                'title'       => $a->title,
                'content'     => $a->content,
                'body'        => $a->content,
                'image_url'   => $a->image ? url('storage/' . $a->image) : null,
                'link_url'    => $a->link_url ?? null,
                'author_name' => $a->author_name ?? 'الإدارة',
                'time_ago'    => $a->created_at ? \Carbon\Carbon::parse($a->created_at)->diffForHumans() : 'منذ قليل',
                'created_at'  => $a->created_at,
            ]);
        return response()->json(['success' => true, 'data' => $announcements]);
    });
    Route::post('/parent/request-report', [StudentParentController::class, 'requestReport']);
    Route::get('/parent/performance/{studentId}', [StudentParentController::class, 'getFullPerformance']);
    Route::get('/parent/student/{studentId}/assignments', [StudentParentController::class, 'getAssignments']);
    Route::get('/parent/student/{studentId}/permissions', [StudentParentController::class, 'getPermissions']);
    Route::post('/parent/permissions/{requestId}/respond', [StudentParentController::class, 'respondPermission']);
    Route::get('/parent/notifications', [NotificationController::class, 'getNotifications']);
    Route::post('/parent/add-student', [ParentController::class, 'linkStudent']);
    Route::get('/parent/leave-requests', [StudentParentController::class, 'getLeaveRequests']);
    Route::post('/parent/leave-requests/{id}/respond', [StudentParentController::class, 'respondLeaveRequest']);
    Route::post('/parent/leave-requests/submit', [StudentParentController::class, 'submitParentLeaveRequest']);
    Route::get('/parent/reports/history', [StudentParentController::class, 'getReportHistory']);

    #========= روابط واجهات المعلم ==========
    Route::prefix('teacher')->middleware('role:teacher')->group(function () {

        // Dashboard
        Route::get('/dashboard', [TeacherController::class, 'dashboard']);

        // المواد والطلاب
        Route::get('/courses', [TeacherController::class, 'myCourses']);
        Route::get('/programs', [TeacherController::class, 'myDepartmentPrograms']);
        Route::get('/courses/{courseId}/students', [TeacherController::class, 'courseStudents']);

        // الجدول الدراسي
        Route::get('/schedule', [TeacherController::class, 'getSchedule']);


        // المحاضرات
        Route::get('/lessons', [TeacherController::class, 'getLessons']);
        Route::post('/lessons', [TeacherController::class, 'createLesson']);
        Route::post('/lessons/{lessonId}', [TeacherController::class, 'updateLesson']);
        Route::delete('/lessons/{lessonId}', [TeacherController::class, 'deleteLesson']);

        // الحضور والغياب (الروابط الثابتة قبل الـ wildcards)
        Route::post('/attendance', [TeacherController::class, 'markAttendance']);
        Route::post('/attendance/generate-qr', [TeacherController::class, 'generateQrSession']);
        Route::get('/attendance/session/{sessionId}/list', [TeacherController::class, 'getSessionAttendance']);
        Route::post('/attendance/session/{sessionId}/end', [TeacherController::class, 'endSession']);
        Route::get('/attendance/export', [TeacherController::class, 'exportAttendance']);
        Route::get('/attendance/export-pdf', [TeacherController::class, 'exportFilteredPdf']);
        Route::get('/attendance/advisor-export', [TeacherController::class, 'advisorExportAttendance']);
        Route::get('/attendance/{courseId}', [TeacherController::class, 'getAttendance']);

        // طلبات الغياب
        Route::get('/absence-requests', [TeacherController::class, 'getAbsenceRequests']);
        Route::put('/absence-requests/{requestId}/respond', [TeacherController::class, 'respondAbsenceRequest']);

        // العلامات
        Route::post('/grades', [TeacherController::class, 'enterGrades']);
        Route::get('/grades/{courseId}', [TeacherController::class, 'getGrades']);

        // الامتحانات
        Route::get('/exams', [TeacherController::class, 'getExams']);
        Route::post('/exams', [TeacherController::class, 'createExam']);

        // الواجبات
        Route::get('/assignments', [TeacherController::class, 'getAssignments']);
        Route::get('/submissions', [TeacherController::class, 'getSubmissions']);
        Route::post('/assignments', [TeacherController::class, 'createAssignment']);
        Route::put('/assignments/{assignmentId}', [TeacherController::class, 'updateAssignment']);
        Route::delete('/assignments/{assignmentId}', [TeacherController::class, 'deleteAssignment']);
        Route::get('/assignments/{assignmentId}/submissions', [TeacherController::class, 'getAssignmentSubmissions']);
        Route::post('/assignments/{submissionId}/grade', [TeacherController::class, 'gradeAssignment']);

        // الإعلانات
        Route::get('/announcements', [TeacherController::class, 'getAnnouncements']);
        Route::post('/announcements', [TeacherController::class, 'createAnnouncement']);

        // الإشعارات
        Route::get('/notifications', [TeacherController::class, 'getNotifications']);
        Route::put('/notifications/{notificationId}/read', [TeacherController::class, 'markNotificationRead']);
        Route::put('/notifications/read-all', [TeacherController::class, 'markAllNotificationsRead']);

        // الرسائل
        Route::get('/messages', [TeacherController::class, 'getMessages']);
        Route::post('/messages', [TeacherController::class, 'sendMessage']);

        // الملف الشخصي
        Route::get('/profile', [TeacherController::class, 'getTeacherProfile']);
        Route::put('/profile', [TeacherController::class, 'updateTeacherProfile']);
        Route::post('/profile/avatar', [TeacherController::class, 'updateAvatar']);
    });

    #========= روابط رئيس القسم ==========
    Route::prefix('department-head')->middleware('role:head')->group(function () {
        // Dashboard & Profile & Notifications
        Route::get('/dashboard',     [DepartmentHeadController::class, 'dashboard']);
        Route::get('/profile',       [DepartmentHeadController::class, 'getProfile']);
        Route::get('/notifications', [DepartmentHeadController::class, 'getNotifications']);
        Route::put('/notifications/read-all', [DepartmentHeadController::class, 'markAllNotificationsRead']);
        Route::put('/notifications/{id}/read', [DepartmentHeadController::class, 'markNotificationRead']);

        // Accounts
        Route::get('/users/trainers', [DepartmentHeadController::class, 'getTrainers']);
        Route::get('/users/students', [DepartmentHeadController::class, 'getStudents']);
        Route::get('/users/parents',  [DepartmentHeadController::class, 'getParents']);
        Route::post('/users/trainer', [DepartmentHeadController::class, 'createTrainer']);
        Route::post('/users/student', [DepartmentHeadController::class, 'createStudent']);
        Route::post('/users/parent',  [DepartmentHeadController::class, 'createParent']);

        // Courses
        Route::get('/courses', [DepartmentHeadController::class, 'getCourses']);
        Route::get('/courses/{id}/teachers', [DepartmentHeadController::class, 'getTeachersByCourse']);
        Route::get('/courses/{id}/students', [DepartmentHeadController::class, 'getStudentsByCourse']);

        // Leave Requests
        Route::get('/leave-requests',               [DepartmentHeadController::class, 'getLeaveRequests']);
        Route::put('/leave-requests/{id}/respond',  [DepartmentHeadController::class, 'respondLeaveRequest']);

        // Teachers list & Report Requests
        Route::get('/teachers',        [DepartmentHeadController::class, 'getTeachers']);
        Route::get('/report-requests',  [DepartmentHeadController::class, 'getReportRequests']);
        Route::post('/report-requests', [DepartmentHeadController::class, 'createReportRequest']);
        Route::post('/report-requests/{id}/send-to-parent', [DepartmentHeadController::class, 'sendReportToParent']);
        Route::delete('/report-requests/{id}', [DepartmentHeadController::class, 'deleteReportRequest']);

        // Schedule
        Route::get('/schedule',       [DepartmentHeadController::class, 'getSchedule']);
        Route::post('/schedule',      [DepartmentHeadController::class, 'createSchedule']);
        Route::put('/schedule/{id}',  [DepartmentHeadController::class, 'updateSchedule']);
        Route::get('/all-schedule',      [DepartmentHeadController::class, 'getAllSchedule']);
        Route::get('/all-exams',         [DepartmentHeadController::class, 'getAllExams']);
        Route::get('/programs-schedule', [DepartmentHeadController::class, 'getProgramsSchedule']);

        // Announcements
        Route::get('/announcements',          [DepartmentHeadController::class, 'getAnnouncements']);
        Route::post('/announcements',         [DepartmentHeadController::class, 'createAnnouncement']);
        Route::post('/announcements/{id}',    [DepartmentHeadController::class, 'updateAnnouncement']);
        Route::delete('/announcements/{id}',  [DepartmentHeadController::class, 'deleteAnnouncement']);

        // Send notification to students / students+teachers
        Route::post('/notifications/send', [DepartmentHeadController::class, 'sendNotification']);
    });

    #========= روابط الأدمن ==========
    Route::prefix('admin')->middleware('role:admin')->group(function () {
        Route::get('/dashboard', [AdminController::class, 'dashboard']);
        Route::get('/users', [AdminController::class, 'getUsers']);
        Route::post('/users', [AdminController::class, 'createUser']);
        Route::put('/users/{id}', [AdminController::class, 'updateUser']);
        Route::delete('/users/{id}', [AdminController::class, 'deleteUser']);
    });

    // ========== Parent Routes ==========
    Route::prefix('parent')->middleware('role:parent')->group(function () {
        Route::get('/dashboard', [ParentController::class, 'dashboard']);
        Route::get('/children', [ParentController::class, 'getChildren']);
        Route::post('/add-student', [ParentController::class, 'linkStudent']);
        Route::get('/announcements', [ParentController::class, 'getAnnouncements']);
        Route::get('/children/{id}/details', [ParentController::class, 'getChildDetails']);
        Route::get('/children/{id}/attendance', [ParentController::class, 'getChildAttendance']);
        Route::get('/children/{id}/grades', [ParentController::class, 'getChildGrades']);
        Route::get('/children/{id}/schedule', [ParentController::class, 'getChildSchedule']);
        Route::get('/children/{id}/assignments', [ParentController::class, 'getChildAssignments']);
        Route::post('/request-report', [ParentController::class, 'requestReport']);
        Route::get('/reports/history', [ParentController::class, 'getReportsHistory']);
    });

});

// -----------------------------------------------------------
// روابط ولي الأمر العامة (بدون توكن)
// -----------------------------------------------------------

Route::get('/parent/info/{user_id}', function ($user_id) {
    $user = DB::table('users')->where('user_id', $user_id)->first();
    if ($user) {
        return response()->json([
            'full_name' => $user->full_name,
            'phone'     => $user->phone ?? 'لا يوجد رقم',
            'role'      => $user->role,
        ]);
    }
    return response()->json(['message' => 'المستخدم غير موجود'], 404);
});

Route::get('/parent/children/{parent_id}', function ($parent_id) {
    $children = DB::table('parent_students')
        ->join('students', 'parent_students.student_id', '=', 'students.student_id')
        ->join('users', 'students.user_id', '=', 'users.user_id')
        ->where('parent_students.parent_id', $parent_id)
        ->select('students.student_id', 'users.full_name', 'students.level')
        ->get();
    return response()->json($children);
});

Route::post('/parent/link-student', function (Request $request) {
    $student = DB::table('students')->where('student_code', $request->student_code)->first();
    if (!$student) {
        return response()->json(['message' => 'كود الطالب غير موجود'], 404);
    }
    $parent = DB::table('parents')->where('user_id', $request->user_id)->first();
    if (!$parent) {
        return response()->json(['message' => 'سجل الأب غير موجود'], 404);
    }
    DB::table('parent_students')->updateOrInsert([
        'parent_id'  => $parent->parent_id,
        'student_id' => $student->student_id,
    ]);
    return response()->json(['message' => 'تم الربط بنجاح'], 200);
});

// ── Affairs API ────────────────────────────────────────────────────
use App\Http\Controllers\Api\AffairsController;

Route::prefix('affairs')->middleware(['auth:sanctum', 'role:affairs'])->group(function () {
    Route::get('/university-ids',                        [AffairsController::class, 'listUniversityIds']);
    Route::post('/university-ids',                       [AffairsController::class, 'addUniversityId']);
    Route::delete('/university-ids/{id}',                [AffairsController::class, 'deleteUniversityId']);
    Route::get('/pending-accounts',                      [AffairsController::class, 'pendingAccounts']);
    Route::post('/accounts/{userId}/approve',            [AffairsController::class, 'approveAccount']);
    Route::post('/accounts/{userId}/reject',             [AffairsController::class, 'rejectAccount']);
    Route::post('/students/{id}/reset-device',           [AffairsController::class, 'resetDevice']);
});

Route::get('/user/profile/{id}', function ($id) {
    return DB::table('users')
        ->where('user_id', $id)
        ->select('full_name', 'email', 'phone')
        ->first();
});

Route::get('/student/info/{id}', function ($id) {
    return DB::table('students')
        ->join('users', 'students.user_id', '=', 'users.user_id')
        ->where('students.student_id', $id)
        ->select('users.full_name', 'users.branch as department', 'students.level', 'students.student_code')
        ->first();
});

Route::get('/parent/notifications/{id}', function ($id) {
    return DB::table('notifications')
        ->where('user_id', $id)
        ->orderBy('created_at', 'desc')
        ->get();
});
