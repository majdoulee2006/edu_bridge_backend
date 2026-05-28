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
    Route::put('/settings/password', [AffairsWebController::class, 'updatePassword'])->name('affairs.settings.password');
    Route::get('/announcements', [AffairsWebController::class, 'announcements'])->name('affairs.announcements');
});

// ===== مسارات الإدارة (Admin) =====
use App\Http\Controllers\Web\AdminWebController;

Route::get('/admin/login', [AdminWebController::class, 'showLoginForm'])->name('admin.login');
Route::post('/admin/login', [AdminWebController::class, 'login'])->name('admin.login.submit');
Route::post('/admin/logout', [AdminWebController::class, 'logout'])->name('admin.logout');

Route::prefix('admin')->middleware(['admin'])->group(function () {
    Route::get('/', fn() => redirect('/admin/dashboard'));
    Route::get('/dashboard', [AdminWebController::class, 'dashboard'])->name('admin.dashboard');
    Route::get('/profile', [AdminWebController::class, 'profile'])->name('admin.profile');
    Route::post('/profile', [AdminWebController::class, 'updateProfile'])->name('admin.profile.update');
    Route::post('/profile/password', [AdminWebController::class, 'updatePassword'])->name('admin.profile.password');
    Route::get('/settings', [AdminWebController::class, 'settings'])->name('admin.settings');
    Route::get('/messages', [AdminWebController::class, 'messages'])->name('admin.messages');
    Route::post('/messages', [AdminWebController::class, 'sendMessage'])->name('admin.messages.send');
    Route::get('/notifications', [AdminWebController::class, 'notifications'])->name('admin.notifications');
    Route::post('/notifications/{id}/read', [AdminWebController::class, 'markNotificationRead'])->name('admin.notifications.read');
    Route::post('/notifications/read-all', [AdminWebController::class, 'markAllNotificationsRead'])->name('admin.notifications.read_all');

    // ─── Accounts Management ───
    Route::get('/accounts', [AdminWebController::class, 'accounts'])->name('admin.accounts');
    Route::post('/accounts/approve/{id}', [AdminWebController::class, 'approveAccount'])->name('admin.accounts.approve');
    Route::post('/accounts/reject/{id}', [AdminWebController::class, 'rejectAccount'])->name('admin.accounts.reject');

    // Create accounts
    Route::get('/accounts/create/student', [AdminWebController::class, 'createStudent'])->name('admin.accounts.create.student');
    Route::post('/accounts/store/student', [AdminWebController::class, 'storeStudent'])->name('admin.accounts.store.student');

    Route::get('/accounts/create/parent', [AdminWebController::class, 'createParent'])->name('admin.accounts.create.parent');
    Route::post('/accounts/store/parent', [AdminWebController::class, 'storeParent'])->name('admin.accounts.store.parent');

    Route::get('/accounts/create/teacher', [AdminWebController::class, 'createTeacher'])->name('admin.accounts.create.teacher');
    Route::post('/accounts/store/teacher', [AdminWebController::class, 'storeTeacher'])->name('admin.accounts.store.teacher');

    Route::get('/accounts/create/hod', [AdminWebController::class, 'createHOD'])->name('admin.accounts.create.hod');
    Route::post('/accounts/store/hod', [AdminWebController::class, 'storeHOD'])->name('admin.accounts.store.hod');

    Route::get('/accounts/create/affairs', [AdminWebController::class, 'createAffairs'])->name('admin.accounts.create.affairs');
    Route::post('/accounts/store/affairs', [AdminWebController::class, 'storeAffairs'])->name('admin.accounts.store.affairs');

    // Delete accounts
    Route::get('/accounts/delete-list/{role_id}', [AdminWebController::class, 'deleteList'])->name('admin.accounts.delete-list');
    Route::post('/accounts/delete/{role_id}', [AdminWebController::class, 'deleteAccounts'])->name('admin.accounts.delete');

    // الدورات
    Route::get('/courses', [AdminWebController::class, 'courses'])->name('admin.courses');
    Route::get('/courses/create', [AdminWebController::class, 'createCourse'])->name('admin.courses.create');
    Route::post('/courses', [AdminWebController::class, 'storeCourse'])->name('admin.courses.store');
    Route::post('/courses/delete/{id}', [AdminWebController::class, 'deleteCourse'])->name('admin.courses.delete');

    // تخصيص رئيس قسم
    Route::get('/courses/assign-hod', [AdminWebController::class, 'assignHODForm'])->name('admin.courses.assign-hod');
    Route::post('/courses/assign-hod', [AdminWebController::class, 'assignHOD'])->name('admin.courses.assign-hod.store');

    // الفصول والمواد
    Route::get('/semesters-subjects', [AdminWebController::class, 'semestersSubjects'])->name('admin.semesters-subjects');
    Route::post('/semesters-subjects', [AdminWebController::class, 'storeSubject'])->name('admin.semesters-subjects.store');

    // التقارير
    Route::get('/reports', [AdminWebController::class, 'reports'])->name('admin.reports');
    Route::post('/reports/generate', [AdminWebController::class, 'generateReport'])->name('admin.reports.generate');
    Route::post('/reports/export', [AdminWebController::class, 'exportReport'])->name('admin.reports.export');

    // التقويم والأحداث
    Route::post('/calendar/events', [AdminWebController::class, 'storeCalendarEvent'])->name('admin.calendar.store');
});
