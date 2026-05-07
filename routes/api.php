<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\StudentController;
use App\Http\Controllers\Api\TeacherController;
use App\Http\Controllers\Api\AnnouncementController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\StudentParentController;

// روابط عامة
Route::post('/login', [AuthController::class, 'login'])->name('login');
Route::post('/register', [AuthController::class, 'register']);
Route::post('/verify-otp', [AuthController::class, 'verifyOtp']);

// روابط محمية (تحتاج توكن)
Route::middleware('auth:sanctum')->group(function () {

    Route::post('/logout', [AuthController::class, 'logout']);

    // مسار البروفايل
    Route::get('/user/profile', function (Request $request) {
        return $request->user()->load('student');
    });

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
    Route::post('/parent/request-report', [StudentParentController::class, 'requestReport']);
    Route::get('/parent/performance/{studentId}', [StudentParentController::class, 'getFullPerformance']);
    Route::get('/parent/student/{studentId}/assignments', [StudentParentController::class, 'getAssignments']);
    Route::get('/parent/student/{studentId}/permissions', [StudentParentController::class, 'getPermissions']);
    Route::post('/parent/permissions/{requestId}/respond', [StudentParentController::class, 'respondPermission']);
    Route::get('/parent/notifications', [NotificationController::class, 'getNotifications']);
    Route::post('/parent/add-student', [StudentController::class, 'linkStudent']);

    #========= روابط واجهات المعلم ==========
    Route::prefix('teacher')->middleware('role:teacher')->group(function () {

        // Dashboard
        Route::get('/dashboard', [TeacherController::class, 'dashboard']);

        // المواد والطلاب
        Route::get('/courses', [TeacherController::class, 'myCourses']);
        Route::get('/courses/{courseId}/students', [TeacherController::class, 'courseStudents']);

        // الجدول الدراسي
        Route::get('/schedule', [TeacherController::class, 'getSchedule']);

        // المحاضرات
        Route::get('/lessons', [TeacherController::class, 'getLessons']);
        Route::post('/lessons', [TeacherController::class, 'createLesson']);
        Route::delete('/lessons/{lessonId}', [TeacherController::class, 'deleteLesson']);

        // الحضور والغياب (الروابط الثابتة قبل الـ wildcards)
        Route::post('/attendance', [TeacherController::class, 'markAttendance']);
        Route::post('/attendance/generate-qr', [TeacherController::class, 'generateQrSession']);
        Route::get('/attendance/session/{sessionId}/list', [TeacherController::class, 'getSessionAttendance']);
        Route::post('/attendance/session/{sessionId}/end', [TeacherController::class, 'endSession']);
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
    });
<<<<<<< Updated upstream
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
=======
>>>>>>> Stashed changes
});
