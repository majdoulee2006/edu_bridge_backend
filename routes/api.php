<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\StudentController;
use App\Models\StudentParent;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Api\AnnouncementController;


// روابط عامة
// ✅ أضفنا ->name('login') هنا لحل مشكلة الخطأ 500 عند فقدان التوكن
Route::post('/login', [AuthController::class, 'login'])->name('login');
Route::post('/register', [AuthController::class, 'register']);
Route::post('/verify-otp', [AuthController::class, 'verifyOtp']);

// روابط محمية (تحتاج توكن)
Route::middleware('auth:sanctum')->group(function () {

    Route::post('/logout', [AuthController::class, 'logout']);
  
    


    // مسار البروفايل
    Route::get('/user/profile', function (Request $request) {
        // بعد إضافة العلاقة في المودل، سيعمل هذا السطر بنجاح
        return $request->user()->load('student');
    });
    #=========روابط واجهات الطالب ==========
     Route::get('/student/dashboard', [StudentController::class, 'getDashboardData']);
     Route::get('/student/profile', [\App\Http\Controllers\Api\StudentController::class, 'getProfileData']);
     Route::post('/student/profile/update', [StudentController::class, 'updateProfile']);
     Route::get('/student/notifications', [StudentController::class, 'getNotifications']);
     Route::get('/student/announcements', [AnnouncementController::class, 'getHomeAnnouncements']);
     Route::get('/student/my-schedule', [StudentController::class, 'getMySchedule']); // جدول الحصص
     Route::get('/student/my-exams', [StudentController::class, 'getMyExams']); // جدول الامتحانات
     Route::get('/student/my-exams/pdf', [StudentController::class, 'exportExamsPdf']); // تصدير الـ PDF
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
});
