<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\StudentController;
use App\Http\Controllers\Api\TeacherController;
use App\Http\Controllers\Api\AdminController;
use App\Http\Controllers\Api\ParentController;
use App\Http\Controllers\NotificationController;
use App\Models\StudentParent;
use Illuminate\Support\Facades\DB;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

// ========== Routes العامة (لا تحتاج تسجيل دخول) ==========
Route::post('/login', [AuthController::class, 'login'])->name('login');
Route::post('/register', [AuthController::class, 'register']);
Route::post('/verify-otp', [AuthController::class, 'verifyOtp']);
Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);
Route::post('/reset-password', [AuthController::class, 'resetPassword']);

// ========== Routes المحمية (تتطلب توكن) ==========
Route::middleware('auth:sanctum')->group(function () {

    // ========== Auth Routes (مشتركة للجميع) ==========
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/profile', [AuthController::class, 'profile']);
    Route::put('/profile', [AuthController::class, 'updateProfile']);

    // ========== Student Routes (خاص بالطلاب) ==========
    Route::prefix('student')->middleware('role:student')->group(function () {
        // Dashboard and Profile
        Route::get('/dashboard', [StudentController::class, 'getDashboardData']);
        Route::get('/profile', [StudentController::class, 'getProfileData']);
        Route::put('/profile', [StudentController::class, 'updateProfile']);

        // Courses
        Route::get('/courses', [StudentController::class, 'getMyCourses']);
        Route::get('/courses/{courseId}/materials', [StudentController::class, 'getCourseMaterials']);

        // Schedule
        Route::get('/schedule', [StudentController::class, 'getMySchedule']);

        // Attendance
        Route::get('/attendance', [StudentController::class, 'getMyAttendance']);

        // Grades
        Route::get('/grades', [StudentController::class, 'getMyGrades']);

        // Assignments
        Route::get('/assignments', [StudentController::class, 'getMyAssignments']);
        Route::post('/assignments/{assignmentId}/submit', [StudentController::class, 'submitAssignment']);

        // Absence Requests
        Route::get('/absence-requests', [StudentController::class, 'getMyAbsenceRequests']);
        Route::post('/absence-request', [StudentController::class, 'requestAbsence']);

        // Notifications
        Route::get('/notifications', [StudentController::class, 'getNotifications']);
        Route::put('/notifications/{notificationId}/read', [StudentController::class, 'markNotificationAsRead']);
    });

    // ========== Teacher Routes (خاص بالمدرسين) ==========
    Route::prefix('teacher')->middleware('role:teacher')->group(function () {
        Route::get('/dashboard', [TeacherController::class, 'dashboard']);
        Route::get('/courses', [TeacherController::class, 'myCourses']);
        Route::get('/courses/{courseId}/students', [TeacherController::class, 'courseStudents']);
        Route::post('/attendance', [TeacherController::class, 'markAttendance']);
        Route::get('/attendance/{courseId}', [TeacherController::class, 'getAttendance']);
        Route::post('/grades', [TeacherController::class, 'enterGrades']);
        Route::get('/grades/{courseId}', [TeacherController::class, 'getGrades']);
        Route::post('/assignments', [TeacherController::class, 'createAssignment']);
        Route::put('/assignments/{assignmentId}', [TeacherController::class, 'updateAssignment']);
        Route::delete('/assignments/{assignmentId}', [TeacherController::class, 'deleteAssignment']);
        Route::post('/assignments/{submissionId}/grade', [TeacherController::class, 'gradeAssignment']);
        Route::post('/announcements', [TeacherController::class, 'createAnnouncement']);
        Route::get('/announcements', [TeacherController::class, 'getAnnouncements']);
    });

    // ========== Parent Routes (خاص بأولياء الأمور) ==========
    Route::prefix('parent')->middleware('role:parent')->group(function () {
        // NEW APIs (من الكود الجديد)
        Route::get('/dashboard', [ParentController::class, 'dashboard']);
        Route::get('/children', [ParentController::class, 'getChildren']);
        Route::get('/child/{childId}', [ParentController::class, 'getChildDetails']);
        Route::post('/link-student', [ParentController::class, 'linkStudent']);
        Route::get('/child/{childId}/attendance', [ParentController::class, 'getChildAttendance']);
        Route::get('/child/{childId}/grades', [ParentController::class, 'getChildGrades']);
        Route::get('/child/{childId}/schedule', [ParentController::class, 'getChildSchedule']);
        Route::get('/child/{childId}/assignments', [ParentController::class, 'getChildAssignments']);
        Route::get('/announcements', [ParentController::class, 'getAnnouncements']);

        // OLD APIs (للتوافق مع الكود القديم)
        Route::get('/info/{user_id}', function ($user_id) {
            $user = DB::table('users')->where('user_id', $user_id)->first();
            if ($user) {
                return response()->json([
                    'full_name' => $user->full_name,
                    'phone' => $user->phone ?? 'لا يوجد رقم',
                    'role' => $user->role
                ]);
            }
            return response()->json(['message' => 'المستخدم غير موجود'], 404);
        });

        Route::get('/children/{user_id}', function ($user_id) {
            $children = DB::table('students')
                ->join('users', 'students.user_id', '=', 'users.user_id')
                ->where('students.parent_id', $user_id)
                ->select('students.student_id', 'users.full_name', 'students.level')
                ->get();
            return response()->json($children);
        });

        Route::get('/notifications/{id}', [NotificationController::class, 'getNotifications']);
    });

    // ========== Admin Routes (خاص بالإدارة) ==========
    Route::prefix('admin')->middleware('role:admin')->group(function () {
        Route::get('/dashboard', [AdminController::class, 'dashboard']);
        Route::get('/users', [AdminController::class, 'getUsers']);
        Route::get('/users/{userId}', [AdminController::class, 'getUser']);
        Route::post('/users', [AdminController::class, 'createUser']);
        Route::put('/users/{userId}', [AdminController::class, 'updateUser']);
        Route::delete('/users/{userId}', [AdminController::class, 'deleteUser']);
        Route::get('/courses', [AdminController::class, 'getCourses']);
        Route::get('/courses/{courseId}', [AdminController::class, 'getCourse']);
        Route::post('/courses', [AdminController::class, 'createCourse']);
        Route::put('/courses/{courseId}', [AdminController::class, 'updateCourse']);
        Route::delete('/courses/{courseId}', [AdminController::class, 'deleteCourse']);
        Route::get('/semesters', [AdminController::class, 'getSemesters']);
        Route::post('/semesters', [AdminController::class, 'createSemester']);
        Route::put('/semesters/{semesterId}', [AdminController::class, 'updateSemester']);
        Route::delete('/semesters/{semesterId}', [AdminController::class, 'deleteSemester']);
        Route::get('/departments', [AdminController::class, 'getDepartments']);
        Route::post('/departments', [AdminController::class, 'createDepartment']);
        Route::put('/departments/{departmentId}', [AdminController::class, 'updateDepartment']);
        Route::delete('/departments/{departmentId}', [AdminController::class, 'deleteDepartment']);
        Route::get('/reports/attendance', [AdminController::class, 'attendanceReport']);
        Route::get('/reports/grades', [AdminController::class, 'gradesReport']);
        Route::get('/reports/students', [AdminController::class, 'studentsReport']);
    });

    // ========== Head Routes (خاص برؤساء الأقسام) ==========
    Route::prefix('head')->middleware('role:head')->group(function () {
        Route::get('/dashboard', function () {
            return response()->json(['message' => 'Head dashboard coming soon']);
        });
    });

    // ========== Routes عامة (لأي مستخدم مسجل دخول) ==========
    Route::get('/user/profile', function (Request $request) {
        return $request->user()->load('student');
    });

    Route::get('/user/profile/{id}', function ($id) {
        return DB::table('users')
            ->where('user_id', $id)
            ->select('full_name', 'email', 'phone')
            ->first();
    });

    Route::get('/student/info/{id}', function ($id) {
        return DB::table('students')
            ->join('users', 'students.user_id', '=', 'users.user_id')
            ->where('students.student_id', $id)
            ->select(
                'users.full_name',
                'users.department',
                'students.level',
                'students.student_code'
            )
            ->first();
    });

    // رابط ربط الطالب (من الكود القديم)
    Route::post('/parent/add-student', [StudentController::class, 'linkStudent']);
});
