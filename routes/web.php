<?php
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});




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
