<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\StudentController;
use App\Http\Controllers\Api\HODController;
use App\Http\Controllers\Api\ScheduleController;
use App\Http\Controllers\Api\ExamScheduleController;
use App\Http\Controllers\Api\TeacherReportController;
use Illuminate\Support\Facades\DB;


// --- روابط عامة (بدون توكن) ---
Route::post('/login', [AuthController::class, 'login'])->name('login');
Route::post('/register', [AuthController::class, 'register']);
Route::post('/verify-otp', [AuthController::class, 'verifyOtp']);

// --- روابط محمية (تحتاج توكن auth:sanctum) ---
Route::middleware('auth:sanctum')->group(function () {

    Route::post('/logout', [AuthController::class, 'logout']);
    
    // الملف الشخصي الموحد (لأي مستخدم مسجل)
    Route::get('/user/profile', function (Request $request) {
        return response()->json($request->user());
    });

    // 🎓 روابط الطالب (Student)
    Route::get('/student/dashboard', [StudentController::class, 'getDashboardData']);

    // 🏢 روابط رئيس القسم (HOD) - برانش Head
    Route::prefix('hod')->group(function () {
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

    // 👨‍🏫 روابط المدرب (Teacher)
    Route::get('/teacher/report-requests', [TeacherReportController::class, 'getMyPendingReportRequests']);
    Route::post('/teacher/submit-report', [TeacherReportController::class, 'submitReport']);

});
