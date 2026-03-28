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
