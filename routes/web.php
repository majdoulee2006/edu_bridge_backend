<?php

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Route;

/* هدول تبع المعلم */
use App\Http\Controllers\Teacher\DashboardController;
use App\Http\Controllers\Teacher\TeacherAuthController;
use App\Http\Controllers\Teacher\ProfileController;
use App\Http\Controllers\Teacher\MessageController; 
use App\Http\Controllers\Teacher\NotificationController;
use App\Http\Controllers\Teacher\ScheduleController; 
use App\Http\Controllers\Teacher\LectureController; 
use App\Http\Controllers\Teacher\AttendanceController;
use App\Http\Controllers\Teacher\AssignmentController;
use App\Http\Controllers\Teacher\SettingsController;
/* لعند هون */

Route::get('/', function () {
    return view('welcome');
});

/* هدول ما دخلني فين */
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

        return "تمت العملية بنجاح! الحساب جاهز الآن للاستخدام.";
    } catch (\Exception $e) {
        return "حدث خطأ أثناء الإنشاء: " . $e->getMessage();
    }
});
/* لعند هون */

// 1. واجهة اختيار الأكتور (الصفحة الرئيسية)
Route::get('/', function () {
    return view('index'); 
})->name('index');

// 2. مسارات تسجيل الدخول (للمعلمين الضيوف قبل الدخول)
Route::get('/teacher/login', [TeacherAuthController::class, 'showLoginForm'])->name('teacher.login');
Route::post('/teacher/login', [TeacherAuthController::class, 'login'])->name('teacher.login.post');


// 3. مسارات المعلم المحمية (لا يمكن دخولها إلا بعد تسجيل الدخول)
Route::middleware(['auth'])->group(function () {

    // لوحة التحكم الأساسية
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/teacher/dashboard', [DashboardController::class, 'index'])->name('teacher.dashboard');

    // المحاضرات - تم الربط بالكنترولر الجديد
    Route::get('/lectures', [LectureController::class, 'index'])->name('lectures');
    Route::post('/lectures', [LectureController::class, 'store'])->name('lectures.store');

    // الحضور والغياب - ربط بالكنترولر الجديد
    Route::get('/attendance', [AttendanceController::class, 'index'])->name('attendance');

    // --- قسم الملف الشخصي (Profile Section) ---
    Route::get('/profile', [ProfileController::class, 'show'])->name('profile');
    Route::post('/profile/update', [ProfileController::class, 'update'])->name('profile.update');
    Route::post('/profile/password', [ProfileController::class, 'updatePassword'])->name('profile.password');
    Route::post('/profile/photo', [ProfileController::class, 'updatePhoto'])->name('profile.photo');

    // الإعدادات - ربط بالكنترولر الجديد
    Route::get('/settings', [SettingsController::class, 'index'])->name('settings');
    Route::post('/settings/save', [SettingsController::class, 'save'])->name('settings.save');

    // --- قسم المراسلة (Messages Section) ---
    Route::get('/messages', [MessageController::class, 'index'])->name('messages');
    Route::post('/messages/send', [MessageController::class, 'sendMessage'])->name('messages.send');

    // الإشعارات
    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications');
    Route::post('/notifications/{id}/read', [NotificationController::class, 'markAsRead'])->name('notifications.read');
    Route::post('/notifications/read-all', [NotificationController::class, 'markAllAsRead'])->name('notifications.readAll');
    
    // الجداول
    Route::get('/schedule', [ScheduleController::class, 'index'])->name('schedule');

    // الواجبات - ربط بالكنترولر الجديد
    Route::get('/assignments', [AssignmentController::class, 'index'])->name('assignments');
    Route::post('/assignments', [AssignmentController::class, 'store'])->name('assignments.store');

    // تسجيل الخروج
    Route::post('/logout', [TeacherAuthController::class, 'logout'])->name('logout');
});
