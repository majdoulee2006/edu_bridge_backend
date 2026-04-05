<?php

use App\Http\Controllers\Api\AuthController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\StudentController;

// 🟢 روابط عامة (أي حدا بيقدر يوصلها بدون توكن)
Route::post('/login', [AuthController::class, 'login']);
Route::post('/register', [AuthController::class, 'register']); // 👈 جبناه لهون (برا الغروب)

// 🔴 روابط محمية (لازم يكون معك توكن لتفتح)
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/student/dashboard', [StudentController::class, 'getDashboardData']);
});