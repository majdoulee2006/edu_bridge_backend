<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\StudentController;
use App\Http\Controllers\Api\ParentController;
use App\Http\Controllers\NotificationController;
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

    // 👨‍👩‍👧‍👦 روابط الأهل (Parent Portal) - برانش Parents
    Route::prefix('parent')->group(function () {
        Route::get('/dashboard', [ParentController::class, 'dashboard']);
        Route::get('/children', [ParentController::class, 'getChildren']);
        Route::get('/student/{id}', [ParentController::class, 'getChildDetails']);
        Route::get('/student/{id}/attendance', [ParentController::class, 'getChildAttendance']);
        Route::get('/student/{id}/grades', [ParentController::class, 'getChildGrades']);
        Route::get('/student/{id}/schedule', [ParentController::class, 'getChildSchedule']);
        Route::get('/student/{id}/assignments', [ParentController::class, 'getChildAssignments']);
        Route::get('/announcements', [ParentController::class, 'getAnnouncements']);
        Route::post('/link-student', [ParentController::class, 'linkStudent']);
        
        // التواصل والرسائل
        Route::get('/messages', [ParentController::class, 'getMessages']);
        Route::post('/messages', [ParentController::class, 'sendMessage']);
        
        // الإشعارات
        Route::get('/notifications', [NotificationController::class, 'getNotifications']);
    });

});
