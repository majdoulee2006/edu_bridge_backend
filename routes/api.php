<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\StudentController;
use App\Models\StudentParent;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\StudentParentController;


// --- روابط عامة ---
Route::post('/login', [AuthController::class, 'login'])->name('login');
Route::post('/register', [AuthController::class, 'register']);
Route::post('/verify-otp', [AuthController::class, 'verifyOtp']);

// --- روابط محمية (تحتاج توكن) ---
Route::middleware('auth:sanctum')->group(function () {

    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/student/dashboard', [StudentController::class, 'getDashboardData']);

    // مسار البروفايل
    Route::get('/user/profile', function (Request $request) {
        return $request->user()->load('student');
    });

    // ✅ روابط الأهل (داخل الحماية ليعمل auth()->id)
    // طلب تقرير أداء (أكاديمي أو سلوكي) - التعديل لضمان وصول التوكن
    Route::post('/parent/request-report', [StudentParentController::class, 'requestReport']);

    // جلب الأداء
    Route::get('/parent/performance/{studentId}', [StudentParentController::class, 'getFullPerformance']);
    
    // جلب الواجبات
    Route::get('/parent/student/{studentId}/assignments', [StudentParentController::class, 'getAssignments']);
    
    // جلب الأذونات
    Route::get('/parent/student/{studentId}/permissions', [StudentParentController::class, 'getPermissions']);
    
    // الرد على إذن
    Route::post('/parent/permissions/{requestId}/respond', [StudentParentController::class, 'respondPermission']);

    // جلب الإشعارات الخاصة بالأب المسجل حالياً (بدون {id} يدوي)
    Route::get('/parent/notifications', [NotificationController::class, 'getNotifications']);
    
    // ربط طالب جديد
    Route::post('/parent/add-student', [StudentController::class, 'linkStudent']);
});


// -----------------------------------------------------------
// --- روابط الأهل (Parents) 
// -----------------------------------------------------------

// 1. جلب بيانات الأب
Route::get('/parent/info/{user_id}', function ($user_id) {
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

// 2. جلب الأبناء المرتبطين
Route::get('/parent/children/{user_id}', function ($user_id) {
    $children = DB::table('students')
        ->join('users', 'students.user_id', '=', 'users.user_id')
        ->where('students.parent_id', $user_id) 
        ->select('students.student_id', 'users.full_name', 'students.level')
        ->get();

    return response()->json($children);
});

// 3. كود الربط الحقيقي (Link Student)
Route::post('/parent/link-student', function (Request $request) {
    $studentCode = $request->student_code;
    $userId = $request->user_id; 

    $student = DB::table('students')->where('student_code', $studentCode)->first();
    if (!$student) {
        return response()->json(['message' => 'كود الطالب غير موجود'], 404);
    }

    $parent = DB::table('parents')->where('user_id', $userId)->first();
    if (!$parent) {
        return response()->json(['message' => 'سجل الأب غير موجود'], 404);
    }

    DB::table('parent_student')->updateOrInsert([
        'parent_id' => $parent->parent_id,
        'student_id' => $student->student_id
    ]);

    return response()->json(['message' => 'تم الربط بنجاح'], 200);
});

// جلب بيانات الملف الشخصي لأي مستخدم
Route::get('/user/profile/{id}', function ($id) {
    return DB::table('users')
        ->where('user_id', $id)
        ->select('full_name', 'email', 'phone')
        ->first();
});

// جلب معلومات طالب محدد
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

// جلب الإشعارات باستخدام الـ ID مباشرة لضمان الظهور في المتصفح
Route::get('/parent/notifications/{id}', function($id) {
    return DB::table('notifications')
        ->where('user_id', $id)
        ->orderBy('created_at', 'desc')
        ->get();
});
