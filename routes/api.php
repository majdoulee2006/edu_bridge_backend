<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\StudentController;
use App\Models\StudentParent;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\NotificationController;



// روابط عامة
// ✅ أضفنا ->name('login') هنا لحل مشكلة الخطأ 500 عند فقدان التوكن
Route::post('/login', [AuthController::class, 'login'])->name('login');
Route::post('/register', [AuthController::class, 'register']);
Route::post('/verify-otp', [AuthController::class, 'verifyOtp']);

// روابط محمية (تحتاج توكن)
Route::middleware('auth:sanctum')->group(function () {

    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/student/dashboard', [StudentController::class, 'getDashboardData']);
    // أضيفي هذا السطر في ملف api.php تحت رابط الـ register


    // مسار البروفايل
    Route::get('/user/profile', function (Request $request) {
        // بعد إضافة العلاقة في المودل، سيعمل هذا السطر بنجاح
        return $request->user()->load('student');
    });

});





// --- روابط الأهل (Parents) ---

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
    // جلب الطلاب الذين يملكون parent_id يساوي الـ ID الممرر
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
    $userId = $request->user_id; // هذا ID الأب من جدول users

    // البحث عن الطالب بالكود
    $student = DB::table('students')->where('student_code', $studentCode)->first();
    if (!$student) {
        return response()->json(['message' => 'كود الطالب غير موجود'], 404);
    }

    // البحث عن سجل الأب في جدول parents
    $parent = DB::table('parents')->where('user_id', $userId)->first();
    if (!$parent) {
        return response()->json(['message' => 'سجل الأب غير موجود'], 404);
    }

    // تنفيذ عملية الربط في جدول parent_student
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
        ->select('full_name', 'email', 'phone') // تأكدي أن الأسماء مطابقة لجدولك
        ->first();
});

Route::get('/student/info/{id}', function ($id) {
    return DB::table('students')
        ->join('users', 'students.user_id', '=', 'users.user_id') // ربط جدول الطلاب بالمستخدمين
        ->where('students.student_id', $id)
        ->select(
            'users.full_name', 
            'users.department', // القسم موجود هنا في جدول اليوزرز
            'students.level',    // السنة الدراسية موجودة هنا في جدول الطلاب
            'students.student_code'
        )
        ->first();
});

// داخل ملف api.php

Route::get('/parent/notifications/{id}', [NotificationController::class, 'getNotifications']);
//(Function) تبحث عن الطالب بالكود الذي أدخله الأب
Route::post('/parent/add-student', [StudentController::class, 'linkStudent']);