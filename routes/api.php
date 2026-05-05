<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\StudentController;
use App\Http\Controllers\Api\TeacherController;
use App\Http\Controllers\Api\AdminController;
use App\Http\Controllers\Api\ParentController;
use App\Http\Controllers\NotificationController;
use Illuminate\Support\Facades\DB;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

// ========== Public Routes ==========
Route::post('/login', [AuthController::class, 'login'])->name('login');
Route::post('/register', [AuthController::class, 'register']);
Route::post('/verify-otp', [AuthController::class, 'verifyOtp']);
Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);
Route::post('/reset-password', [AuthController::class, 'resetPassword']);

// ========== Protected Routes (Sanctum) ==========
Route::middleware('auth:sanctum')->group(function () {

    // Auth & Profile
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/profile', [AuthController::class, 'profile']);
    Route::put('/profile', [AuthController::class, 'updateProfile']);

    // ========== Student Routes ==========
    Route::prefix('student')->middleware('role:student')->group(function () {
        Route::get('/dashboard', [StudentController::class, 'getDashboardData']);
        Route::get('/profile', [StudentController::class, 'getProfileData']);
        Route::put('/profile', [StudentController::class, 'updateProfile']);
        Route::get('/courses', [StudentController::class, 'getMyCourses']);
        Route::get('/courses/{courseId}/materials', [StudentController::class, 'getCourseMaterials']);
        Route::get('/schedule', [StudentController::class, 'getMySchedule']);
        Route::get('/attendance', [StudentController::class, 'getMyAttendance']);
        Route::get('/grades', [StudentController::class, 'getMyGrades']);
        Route::get('/assignments', [StudentController::class, 'getMyAssignments']);
        Route::post('/assignments/{assignmentId}/submit', [StudentController::class, 'submitAssignment']);
        Route::get('/absence-requests', [StudentController::class, 'getMyAbsenceRequests']);
        Route::post('/absence-request', [StudentController::class, 'requestAbsence']);
        Route::get('/notifications', [StudentController::class, 'getNotifications']);
        Route::put('/notifications/{notificationId}/read', [StudentController::class, 'markNotificationAsRead']);
    });

    // ========== Teacher Routes ==========
    Route::prefix('teacher')->middleware('role:teacher')->group(function () {
        Route::get('/dashboard', [TeacherController::class, 'dashboard']);
        Route::get('/courses', [TeacherController::class, 'myCourses']);
        Route::get('/courses/{courseId}/students', [TeacherController::class, 'courseStudents']);
        Route::post('/attendance', [TeacherController::class, 'markAttendance']);
        Route::get('/attendance/{courseId}', [TeacherController::class, 'getAttendance']);
        Route::post('/grades', [TeacherController::class, 'enterGrades']);
        Route::get('/grades/{courseId}', [TeacherController::class, 'getGrades']);
        Route::post('/assignments', [TeacherController::class, 'createAssignment']);
        Route::put('/assignments/{assignmentId}', [TeacherController::class, 'updateAssignment']);
        Route::delete('/assignments/{assignmentId}', [TeacherController::class, 'deleteAssignment']);
        Route::post('/assignments/{submissionId}/grade', [TeacherController::class, 'gradeAssignment']);
        Route::post('/announcements', [TeacherController::class, 'createAnnouncement']);
        Route::get('/announcements', [TeacherController::class, 'getAnnouncements']);
        
        // HOD Feedback routes
        Route::get('/report-requests', [\App\Http\Controllers\Api\TeacherReportController::class, 'getMyPendingReportRequests']);
        Route::post('/teacher/submit-report', [\App\Http\Controllers\Api\TeacherReportController::class, 'submitReport']);
    });

    // ========== Parent Routes ==========
    Route::prefix('parent')->middleware('role:parent')->group(function () {
        Route::get('/dashboard', [ParentController::class, 'dashboard']);
        Route::get('/children', [ParentController::class, 'getChildren']);
        Route::get('/child/{childId}', [ParentController::class, 'getChildDetails']);
        Route::post('/link-student', [ParentController::class, 'linkStudent']);
        Route::get('/child/{childId}/attendance', [ParentController::class, 'getChildAttendance']);
        Route::get('/child/{childId}/grades', [ParentController::class, 'getChildGrades']);
        Route::get('/child/{childId}/schedule', [ParentController::class, 'getChildSchedule']);
        Route::get('/child/{childId}/assignments', [ParentController::class, 'getChildAssignments']);
        Route::get('/announcements', [ParentController::class, 'getAnnouncements']);
        Route::get('/notifications', [NotificationController::class, 'getNotifications']);
    });

    // ========== Admin Routes ==========
    Route::prefix('admin')->middleware('role:admin')->group(function () {
        Route::get('/dashboard', [AdminController::class, 'dashboard']);
        Route::get('/users', [AdminController::class, 'getUsers']);
        Route::post('/users', [AdminController::class, 'createUser']);
        Route::put('/users/{userId}', [AdminController::class, 'updateUser']);
        Route::delete('/users/{userId}', [AdminController::class, 'deleteUser']);
        Route::get('/courses', [AdminController::class, 'getCourses']);
        Route::post('/courses', [AdminController::class, 'createCourse']);
        Route::get('/departments', [AdminController::class, 'getDepartments']);
    });

    // ========== HOD (Head of Department) Routes ==========
    Route::prefix('hod')->middleware('role:head')->group(function () {
        Route::get('/leave-requests', [\App\Http\Controllers\Api\HODController::class, 'getLeaveRequests']);
        Route::post('/leave-requests/{id}/status', [\App\Http\Controllers\Api\HODController::class, 'updateLeaveStatus']);
        Route::get('/staff-and-students', [\App\Http\Controllers\Api\HODController::class, 'getStaffAndStudents']);
        Route::post('/report-requests', [\App\Http\Controllers\Api\HODController::class, 'storeReportRequest']);
        Route::get('/received-reports', [\App\Http\Controllers\Api\HODController::class, 'getReceivedReports']);
        Route::post('/accounts', [\App\Http\Controllers\Api\HODController::class, 'storeAccount']);
        Route::get('/accounts', [\App\Http\Controllers\Api\HODController::class, 'getAccounts']);
        Route::get('/courses', [\App\Http\Controllers\Api\HODController::class, 'getCourses']);

        // Academic Schedule
        Route::get('/schedules', [\App\Http\Controllers\Api\ScheduleController::class, 'index']);
        Route::post('/schedules', [\App\Http\Controllers\Api\ScheduleController::class, 'store']);
        Route::put('/schedules/{id}', [\App\Http\Controllers\Api\ScheduleController::class, 'update']);
        Route::delete('/schedules/{id}', [\App\Http\Controllers\Api\ScheduleController::class, 'destroy']);

        // Exam Schedule
        Route::get('/exams', [\App\Http\Controllers\Api\ExamScheduleController::class, 'index']);
        Route::post('/exams', [\App\Http\Controllers\Api\ExamScheduleController::class, 'store']);
        Route::put('/exams/{id}', [\App\Http\Controllers\Api\ExamScheduleController::class, 'update']);
        Route::delete('/exams/{id}', [\App\Http\Controllers\Api\ExamScheduleController::class, 'destroy']);
    });

});

// ========== General Routes ==========
Route::get('/user/profile', function (Request $request) {
    return $request->user()->load('student');
});
