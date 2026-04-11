<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\StudentController;
use App\Models\StudentParent;
use Illuminate\Support\Facades\DB;


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
});





