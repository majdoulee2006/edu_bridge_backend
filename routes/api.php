<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\StudentController;

// روابط عامة
// ✅ أضفنا ->name('login') هنا لحل مشكلة الخطأ 500 عند فقدان التوكن
Route::post('/login', [AuthController::class, 'login'])->name('login');
Route::post('/register', [AuthController::class, 'register']);

// روابط محمية (تحتاج توكن)
Route::middleware('auth:sanctum')->group(function () {

    Route::post('/logout', [AuthController::class, 'logout']);
  
    // أضيفي هذا السطر في ملف api.php تحت رابط الـ register


    // مسار البروفايل
    Route::get('/user/profile', function (Request $request) {
        // بعد إضافة العلاقة في المودل، سيعمل هذا السطر بنجاح
        return $request->user()->load('student');
    });
    #=========روابط واجهات الطالب ==========
     Route::get('/student/dashboard', [StudentController::class, 'getDashboardData']);
     Route::get('/student/profile', [\App\Http\Controllers\Api\StudentController::class, 'getProfileData']);
});
