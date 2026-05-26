<?php

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Web\HODWebController;
use Illuminate\Support\Facades\Auth;

// Default Redirect
Route::get('/', function () {
    return redirect('/hod/login');
});

// ===== مسارات المعلم (Teacher) =====
use App\Http\Controllers\Web\TeacherWebController;

// تسجيل الدخول والخروج
Route::get('/teacher/login', [TeacherWebController::class, 'showLoginForm'])->name('teacher.login');
Route::post('/teacher/login', [TeacherWebController::class, 'login'])->name('teacher.login.post');
Route::post('/teacher/logout', [TeacherWebController::class, 'logout'])->name('teacher.logout');

// الصفحات المحمية بـ Middleware
Route::prefix('teacher')->middleware([\App\Http\Middleware\CheckTeacherRole::class])->group(function () {
    Route::get('/', fn() => redirect('/teacher/dashboard'));
    Route::get('/dashboard', [TeacherWebController::class, 'dashboard'])->name('teacher.dashboard');

    // الجداول
    Route::get('/schedule', [TeacherWebController::class, 'schedule'])->name('teacher.schedule');

    // الحضور
    Route::get('/attendance', [TeacherWebController::class, 'attendance'])->name('teacher.attendance');
    Route::post('/attendance', [TeacherWebController::class, 'storeAttendanceSession'])->name('teacher.attendance.store');
    Route::get('/attendance/export/{id}', [TeacherWebController::class, 'exportAttendance'])->name('teacher.attendance.export');
    Route::get('/attendance/absentees/{id}', [TeacherWebController::class, 'getAbsentees'])->name('teacher.attendance.absentees');

    // الواجبات
    Route::get('/assignments', [TeacherWebController::class, 'assignments'])->name('teacher.assignments');
    Route::post('/assignments', [TeacherWebController::class, 'storeAssignment'])->name('teacher.assignments.store');
    Route::post('/assignments/delete/{id}', [TeacherWebController::class, 'deleteAssignment'])->name('teacher.assignments.delete');
    Route::get('/assignments/{id}/submissions', [TeacherWebController::class, 'assignmentSubmissions'])->name('teacher.assignments.submissions');
    Route::post('/assignments/submissions/{id}/grade', [TeacherWebController::class, 'gradeSubmission'])->name('teacher.submissions.grade');

    // المحاضرات
    Route::get('/lectures', [TeacherWebController::class, 'lectures'])->name('teacher.lectures');
    Route::post('/lectures', [TeacherWebController::class, 'storeLecture'])->name('teacher.lectures.store');
    Route::post('/lectures/delete/{id}', [TeacherWebController::class, 'deleteLecture'])->name('teacher.lectures.delete');

    // الرسائل
    Route::get('/messages', [TeacherWebController::class, 'messages'])->name('teacher.messages');
    Route::post('/messages', [TeacherWebController::class, 'sendMessage'])->name('teacher.messages.send');
    Route::get('/messages/conversation/{userId}', [TeacherWebController::class, 'getConversation'])->name('teacher.messages.conversation');

    // الإشعارات
    Route::get('/notifications', [TeacherWebController::class, 'notifications'])->name('teacher.notifications');

    // الملف الشخصي
    Route::get('/profile', [TeacherWebController::class, 'profile'])->name('teacher.profile');
    Route::post('/profile', [TeacherWebController::class, 'updateProfile'])->name('teacher.profile.update');
    Route::post('/profile/send-otp', [TeacherWebController::class, 'sendOTP'])->name('teacher.profile.send_otp');
    Route::post('/profile/verify-otp', [TeacherWebController::class, 'verifyOTP'])->name('teacher.profile.verify_otp');
    Route::post('/profile/password', [TeacherWebController::class, 'updatePassword'])->name('teacher.profile.password');
});

// ===== Utility Routes =====
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


// مسارات تسجيل الدخول لرئيس القسم
Route::get('/hod/login', [HODWebController::class, 'showLoginForm'])->name('hod.login');
Route::post('/hod/login', [HODWebController::class, 'login'])->name('hod.login.submit');
Route::post('/hod/logout', [HODWebController::class, 'logout'])->name('hod.logout');

// مسارات واجهات رئيس القسم (Frontend Only) محمية
Route::prefix('hod')->middleware([\App\Http\Middleware\CheckHodRole::class])->group(function () {
    Route::get('/', function() { return redirect('/hod/dashboard'); });
    Route::get('/dashboard', [HODWebController::class, 'dashboard'])->name('hod.dashboard');
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
    // إعلانات رئيس القسم
    Route::get('/announcements/create', [HODWebController::class, 'showCreateAnnouncementForm'])
        ->name('hod.announcements.create');
    Route::post('/announcements', [HODWebController::class, 'storeAnnouncement'])
        ->name('hod.announcements.store');
});

// ===== مسارات الشؤون (Affairs) =====
use App\Http\Controllers\Web\AffairsWebController;

Route::get('/affairs/login', [AffairsWebController::class, 'showLoginForm'])->name('affairs.login');
Route::post('/affairs/login', [AffairsWebController::class, 'login'])->name('affairs.login.submit');
Route::post('/affairs/logout', [AffairsWebController::class, 'logout'])->name('affairs.logout');

Route::prefix('affairs')->middleware(['affairs'])->group(function () {
    Route::get('/', fn() => redirect('/affairs/dashboard'));
    Route::get('/dashboard', [AffairsWebController::class, 'dashboard'])->name('affairs.dashboard');
    Route::get('/calendar', [AffairsWebController::class, 'calendar'])->name('affairs.calendar');
    Route::post('/calendar/events', [AffairsWebController::class, 'storeCalendarEvent'])->name('affairs.calendar.store');
    Route::post('/calendar/events/update/{id}', [AffairsWebController::class, 'updateCalendarEvent'])->name('affairs.calendar.update');
    Route::post('/calendar/events/delete/{id}', [AffairsWebController::class, 'deleteCalendarEvent'])->name('affairs.calendar.delete');
    Route::get('/activities', [AffairsWebController::class, 'activities'])->name('affairs.activities');

    // الحسابات
    Route::get('/accounts', [AffairsWebController::class, 'accounts'])->name('affairs.accounts');
    Route::post('/accounts', [AffairsWebController::class, 'storeAccount'])->name('affairs.accounts.store');
    Route::post('/accounts/{id}/toggle', [AffairsWebController::class, 'toggleAccountStatus'])->name('affairs.accounts.toggle');
    Route::post('/accounts/{id}/delete', [AffairsWebController::class, 'deleteAccount'])->name('affairs.accounts.delete');

    // طلبات الإجازة
    Route::get('/leaves', [AffairsWebController::class, 'leaves'])->name('affairs.leaves');
    Route::post('/leaves/{id}/status', [AffairsWebController::class, 'updateLeaveStatus'])->name('affairs.leaves.status');

    // الرسائل
    Route::get('/messages', [AffairsWebController::class, 'messages'])->name('affairs.messages');
    Route::post('/messages', [AffairsWebController::class, 'sendMessage'])->name('affairs.messages.send');
    Route::get('/messages/conversation/{userId}', [AffairsWebController::class, 'getConversation'])->name('affairs.messages.conversation');

    // الإشعارات
    Route::get('/notifications', [AffairsWebController::class, 'notifications'])->name('affairs.notifications');
    Route::post('/notifications/{id}/read', [AffairsWebController::class, 'markNotificationRead'])->name('affairs.notifications.read');
    Route::post('/notifications/read-all', [AffairsWebController::class, 'markAllNotificationsRead'])->name('affairs.notifications.read_all');

    // الملف الشخصي
    Route::get('/profile', [AffairsWebController::class, 'profile'])->name('affairs.profile');
    Route::post('/profile', [AffairsWebController::class, 'updateProfile'])->name('affairs.profile.update');
    Route::post('/profile/password', [AffairsWebController::class, 'updatePassword'])->name('affairs.profile.password');

    Route::get('/settings', [AffairsWebController::class, 'settings'])->name('affairs.settings');
    Route::get('/announcements', [AffairsWebController::class, 'announcements'])->name('affairs.announcements');
});
