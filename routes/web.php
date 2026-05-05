<?php

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Route;

/* --- HOD & Web Controllers --- */
use App\Http\Controllers\WebHead\WebAuthController;
use App\Http\Controllers\WebHead\DashboardController;

/* --- Teacher Controllers --- */
use App\Http\Controllers\Teacher\DashboardController as TeacherDashboardController;
use App\Http\Controllers\Teacher\TeacherAuthController;
use App\Http\Controllers\Teacher\ProfileController;
use App\Http\Controllers\Teacher\MessageController; 
use App\Http\Controllers\Teacher\NotificationController;
use App\Http\Controllers\Teacher\ScheduleController; 
use App\Http\Controllers\Teacher\LectureController; 
use App\Http\Controllers\Teacher\AttendanceController;
use App\Http\Controllers\Teacher\AssignmentController;
use App\Http\Controllers\Teacher\SettingsController;

// Default Redirect
Route::get('/', function () {
    return redirect('/login');
});

// --- Auth Routes ---
Route::get('/login', [WebAuthController::class, 'showLogin'])->name('login');
Route::post('/login', [WebAuthController::class, 'login']);

// --- HOD Dashboard Routes ---
Route::middleware(['auth'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index']);
    Route::post('/dashboard/send-otp', [DashboardController::class, 'sendOtp']);
    Route::post('/dashboard/update-profile', [DashboardController::class, 'updateProfile']);
});

// --- Teacher Dashboard Routes ---
Route::middleware(['auth'])->group(function () {
    Route::get('/teacher/dashboard', [TeacherDashboardController::class, 'index'])->name('teacher.dashboard');
    Route::get('/teacher/login', [TeacherAuthController::class, 'showLoginForm'])->name('teacher.login');
    Route::post('/teacher/login', [TeacherAuthController::class, 'login'])->name('teacher.login.post');

    // Lectures
    Route::get('/lectures', [LectureController::class, 'index'])->name('lectures');
    Route::post('/lectures', [LectureController::class, 'store'])->name('lectures.store');

    // Attendance
    Route::get('/attendance', [AttendanceController::class, 'index'])->name('attendance');

    // Profile
    Route::get('/profile', [ProfileController::class, 'show'])->name('profile');
    Route::post('/profile/update', [ProfileController::class, 'update'])->name('profile.update');
    Route::post('/profile/password', [ProfileController::class, 'updatePassword'])->name('profile.password');
    Route::post('/profile/photo', [ProfileController::class, 'updatePhoto'])->name('profile.photo');

    // Settings
    Route::get('/settings', [SettingsController::class, 'index'])->name('settings');
    Route::post('/settings/save', [SettingsController::class, 'save'])->name('settings.save');

    // Messages
    Route::get('/messages', [MessageController::class, 'index'])->name('messages');
    Route::post('/messages/send', [MessageController::class, 'sendMessage'])->name('messages.send');

    // Notifications
    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications');
    Route::post('/notifications/{id}/read', [NotificationController::class, 'markAsRead'])->name('notifications.read');
    Route::post('/notifications/read-all', [NotificationController::class, 'markAllAsRead'])->name('notifications.readAll');
    
    // Schedule
    Route::get('/schedule', [ScheduleController::class, 'index'])->name('schedule');

    // Assignments
    Route::get('/assignments', [AssignmentController::class, 'index'])->name('assignments');
    Route::post('/assignments', [AssignmentController::class, 'store'])->name('assignments.store');

    // Logout
    Route::post('/logout', [TeacherAuthController::class, 'logout'])->name('logout');
});

// Utility Routes
Route::get('/create-student', function () {
    try {
        $user = User::updateOrCreate(
            ['email' => 'student@test.com'],
            [
                'full_name' => 'طالب تجريبي جديد',
                'password' => Hash::make('123456'),
                'role' => 'student',
            ]
        );
        return "تمت العملية بنجاح!";
    } catch (\Exception $e) {
        return "Error: " . $e->getMessage();
    }
});
