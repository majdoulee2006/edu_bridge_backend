<?php

use App\Http\Controllers\Api\AuthController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\StudentController;

// رابط تسجيل الدخول
Route::post('/login', [AuthController::class, 'login']);

// الروابط المحمية (بتحتاج توكن)
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/student/dashboard', [StudentController::class, 'getDashboardData']);

});

