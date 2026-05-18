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

use App\Http\Controllers\Web\HODWebController;
use Illuminate\Support\Facades\Auth;

// مسارات تسجيل الدخول لرئيس القسم
Route::get('/hod/login', [HODWebController::class, 'showLoginForm'])->name('hod.login');
Route::post('/hod/login', [HODWebController::class, 'login'])->name('hod.login.submit');
Route::post('/hod/logout', [HODWebController::class, 'logout'])->name('hod.logout');

// مسارات واجهات رئيس القسم (Frontend Only) محمية
Route::prefix('hod')->middleware([\App\Http\Middleware\CheckHodRole::class])->group(function () {
    Route::get('/', function() { return redirect('/hod/dashboard'); });
    Route::get('/dashboard', [HODWebController::class, 'dashboard']);
    Route::get('/profile', [HODWebController::class, 'profile']);
    Route::post('/profile', [HODWebController::class, 'updateProfile'])->name('hod.profile.update');
    Route::post('/profile/send-otp', [HODWebController::class, 'sendOTP'])->name('hod.profile.send_otp');
    Route::post('/profile/verify-otp', [HODWebController::class, 'verifyOTP'])->name('hod.profile.verify_otp');
    Route::get('/leaves', [HODWebController::class, 'leaves']);
    Route::post('/leaves/{id}/status', [HODWebController::class, 'updateLeaveStatus'])->name('hod.leaves.status');
    Route::get('/accounts', [HODWebController::class, 'accounts']);
    Route::post('/accounts/teacher', [HODWebController::class, 'storeTeacher'])->name('hod.accounts.store_teacher');
    Route::post('/accounts/student', [HODWebController::class, 'storeStudent'])->name('hod.accounts.store_student');
    Route::post('/accounts/delete/{id}', [HODWebController::class, 'deleteAccount'])->name('hod.accounts.delete');
    Route::get('/organization', [HODWebController::class, 'organization']);
    Route::post('/organization/schedule', [HODWebController::class, 'storeSchedule'])->name('hod.organization.store_schedule');
    Route::post('/organization/schedule/delete/{id}', [HODWebController::class, 'deleteSchedule'])->name('hod.organization.delete_schedule');
    Route::post('/organization/exam', [HODWebController::class, 'storeExam'])->name('hod.organization.store_exam');
    Route::post('/organization/exam/delete/{id}', [HODWebController::class, 'deleteExam'])->name('hod.organization.delete_exam');
    Route::get('/messages', [HODWebController::class, 'messages']);
    Route::post('/messages', [HODWebController::class, 'storeMessage'])->name('hod.messages.store');
    Route::post('/messages/delete/{id}', [HODWebController::class, 'deleteMessage'])->name('hod.messages.delete');
    Route::get('/reports', [HODWebController::class, 'reports']);
    Route::post('/reports', [HODWebController::class, 'storeReport'])->name('hod.reports.store');
    Route::post('/reports/delete/{id}', [HODWebController::class, 'deleteReport'])->name('hod.reports.delete');
    
    // واجهات الـ Mockup القديمة
    Route::get('/notifications', function () { return view('hod.notifications'); });
    Route::get('/settings', function () { return view('hod.settings'); });
});
