<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\StudentController;
use App\Http\Controllers\Api\TeacherController;
use App\Http\Controllers\Api\AdminController;
use App\Http\Controllers\Api\HODController;
use App\Http\Controllers\Api\ScheduleController;
use App\Http\Controllers\Api\ExamScheduleController;
use App\Http\Controllers\Api\TeacherReportController;
use Illuminate\Support\Facades\DB;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

// ========== Public Routes ==========
Route::post('/login', [AuthController::class, 'login'])->name('api.login');
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
        
        // تقارير المدرسين (HOD logic on Teacher side)
        Route::get('/report-requests', [TeacherReportController::class, 'getMyPendingReportRequests']);
        Route::post('/submit-report', [TeacherReportController::class, 'submitReport']);
    });

    // ========== Head Routes (خاص برؤساء الأقسام) - برانش Head ==========
    Route::prefix('hod')->middleware('role:head')->group(function () {
        Route::get('/dashboard', [HODController::class, 'getDashboardData']); // Placeholder or logic
        Route::get('/leave-requests', [HODController::class, 'getLeaveRequests']);
        Route::post('/leave-requests/{id}/status', [HODController::class, 'updateLeaveStatus']);
        Route::get('/staff-and-students', [HODController::class, 'getStaffAndStudents']);
        Route::post('/report-requests', [HODController::class, 'storeReportRequest']);
        Route::get('/received-reports', [HODController::class, 'getReceivedReports']);
        Route::post('/accounts', [HODController::class, 'storeAccount']);
        Route::get('/accounts', [HODController::class, 'getAccounts']);
        Route::get('/courses', [HODController::class, 'getCourses']);
        Route::get('/profile', [HODController::class, 'getProfile']);
        Route::get('/announcements', [HODController::class, 'getAnnouncements']);
        Route::post('/announcements', [HODController::class, 'storeAnnouncement']);

        // الجداول
        Route::get('/schedules', [ScheduleController::class, 'index']);
        Route::post('/schedules', [ScheduleController::class, 'store']);
        Route::put('/schedules/{id}', [ScheduleController::class, 'update']);
        Route::delete('/schedules/{id}', [ScheduleController::class, 'destroy']);

        Route::get('/exams', [ExamScheduleController::class, 'index']);
        Route::post('/exams', [ExamScheduleController::class, 'store']);
        Route::put('/exams/{id}', [ExamScheduleController::class, 'update']);
        Route::delete('/exams/{id}', [ExamScheduleController::class, 'destroy']);
    });

    // ========== Admin Routes ==========
    Route::prefix('admin')->middleware('role:admin')->group(function () {
        Route::get('/dashboard', [AdminController::class, 'dashboard']);
        Route::get('/users', [AdminController::class, 'getUsers']);
        Route::post('/users', [AdminController::class, 'createUser']);
        Route::get('/courses', [AdminController::class, 'getCourses']);
        Route::post('/courses', [AdminController::class, 'createCourse']);
    });

});
