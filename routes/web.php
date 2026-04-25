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
/* لعند هون */

Route::get('/', function () {
    return view('welcome');
});

/* هدول ما دخلني فين */
Route::get('/create-student', function () {
    try {
        // البحث عن المستخدم إذا كان موجوداً، أو إنشاؤه إذا لم يكن موجوداً
        $user = User::updateOrCreate(
            ['email' => 'student@test.com'], // شرط البحث
            [
                'full_name' => 'طالب تجريبي جديد',
                'password' => Hash::make('123456'),
                'role' => 'student',
            ]
        );

        return "تمت العملية بنجاح! الحساب جاهز الآن للاستخدام.";
    } catch (\Exception $e) {
        // في حال حدوث خطأ، سيعرض لكِ السبب الحقيقي هنا بدل رقم 500
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

    // المحاضرات
    Route::get('/lectures', function () {
        return view('teacher.lectures');
    })->name('lectures');

    // الحضور والغياب
    Route::get('/attendance', function () {
        return view('teacher.attendance');
    })->name('attendance');

    // --- قسم الملف الشخصي (Profile Section) ---
    // عرض البروفايل جلب البيانات من الداتابيز
    Route::get('/profile', [ProfileController::class, 'show'])->name('profile');

    // تحديث البيانات الشخصية (إيميل، هاتف)
    Route::post('/profile/update', [ProfileController::class, 'update'])->name('profile.update');

    // تحديث كلمة المرور
    Route::post('/profile/password', [ProfileController::class, 'updatePassword'])->name('profile.password');

    // مسار رفع الصورة الشخصية
    Route::post('/profile/photo', [ProfileController::class, 'updatePhoto'])->name('profile.photo');


    // الإعدادات
    Route::get('/settings', function () {
        return view('settein'); 
    })->name('settings');

    // --- قسم المراسلة (Messages Section) - تم الربط بالكنترولر ---
    Route::get('/messages', [MessageController::class, 'index'])->name('messages');
    Route::post('/messages/send', [MessageController::class, 'sendMessage'])->name('messages.send');

    // الإشعارات
    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications');
    Route::post('/notifications/{id}/read', [NotificationController::class, 'markAsRead'])->name('notifications.read');
    Route::post('/notifications/read-all', [NotificationController::class, 'markAllAsRead'])->name('notifications.readAll');
    
    // الجداول - تم الربط بالكنترولر الجديد (ScheduleController)
    Route::get('/schedule', [ScheduleController::class, 'index'])->name('schedule');

    // الواجبات
    Route::get('/assignments', function () {
        return view('teacher.assignments');
    })->name('assignments');

    // تسجيل الخروج
    Route::post('/logout', [TeacherAuthController::class, 'logout'])->name('logout');
});