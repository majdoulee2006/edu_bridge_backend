<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\StudentController;
use App\Models\StudentParent;
use Illuminate\Support\Facades\DB;


// روابط عامة
// ✅ أضفنا ->name('login') هنا لحل مشكلة الخطأ 500 عند فقدان التوكن
Route::post('/login', [AuthController::class, 'login'])->name('login');
Route::post('/register', [AuthController::class, 'register']);

// روابط محمية (تحتاج توكن)
Route::middleware('auth:sanctum')->group(function () {

    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/student/dashboard', [StudentController::class, 'getDashboardData']);

    // مسار البروفايل
    Route::get('/user/profile', function (Request $request) {
        // بعد إضافة العلاقة في المودل، سيعمل هذا السطر بنجاح
        return $request->user()->load('student');
    });

});


// رابط جديد ومستقل تماماً للملف الشخصي
Route::get('/parent-full-profile/{id}', function ($id) {
    $parent = \App\Models\StudentParent::with('user')->where('parent_id', $id)->first();

    if ($parent && $parent->user) {
        return response()->json([
            'full_name'  => $parent->user->full_name,
            'email'      => $parent->user->email,
            'phone'      => $parent->phone_number ?? '050 123 4567', // قيمة افتراضية لو فارغ
            'address'    => $parent->address ?? 'غير محدد',
            'gender'     => $parent->user->gender ?? 'ذكر',
            'birth_date' => $parent->user->birth_date ?? '2002-05-15',
        ]);
    }
    return response()->json(['message' => 'لم يتم العثور على البيانات'], 404);
});
//طلب بيانات الابن المختار
Route::get('/student-details/{student_id}', function ($student_id) {
    $student = \App\Models\Student::with('user')->find($student_id);
    return response()->json([
        'full_name' => $student->user->full_name,
        'level' => $student->level,
        'department' => 'هندسة حاسوب',
    ]);
});

Route::get('/children/{id}', [ParentController::class, 'getChildren']);









//Parents:
Route::get('/test-parent-children/{id}', function ($id) {
    // جلب الأبناء فقط مع بيانات المستخدم الخاصة بهم (بدون تعقيد الأب حالياً)
    return \App\Models\Student::where('parent_id', $id)
           ->with('user') 
           ->get();
});

// ابقي على كود الربط (الزائد الصفراء) كما هو لأنه كان يعمل
Route::post('/parent/link-student', function (Request $request) {
    $studentCode = $request->student_code;
    $student = \App\Models\Student::where('student_code', $studentCode)->first();
    if ($student) {
        $student->parent_id = 10;
        $student->save();
        return response()->json(['message' => 'success']);
    }
    return response()->json(['message' => 'not found'], 404);
});

// رابط خاص لجلب اسم الأب فقط لتجنب تعقيد الأبناء

Route::get('/parent-name/{id}', function ($id) {
    // نجلب الاسم مباشرة بربط جدول الأهل مع جدول المستخدمين
    $parent = DB::table('parents')
        ->join('users', 'parents.user_id', '=', 'users.user_id') // تأكدي أن اسم الحقل في الجدولين هو user_id
        ->where('parents.parent_id', $id)
        ->select('users.full_name')
        ->first();

    if ($parent) {
        return response()->json(['full_name' => $parent->full_name]);
    }

    return response()->json(['full_name' => 'اسم غير موجود'], 404);
});