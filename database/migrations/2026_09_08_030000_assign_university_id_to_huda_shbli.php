<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // 1. تحديث حساب الطالب التجريبي عمر الخالد لتفريغ الرقم 2026100
        DB::table('users')
            ->where('email', 'student@test.com')
            ->orWhere(function($q) {
                $q->where('university_id', '2026100')
                  ->where('full_name', 'like', '%عمر%');
            })
            ->update([
                'university_id' => '2026099',
                'username'      => '2026099',
            ]);

        DB::table('students')
            ->where('student_code', '2026100')
            ->whereIn('user_id', function($q) {
                $q->select('user_id')->from('users')->where('full_name', 'like', '%عمر%');
            })
            ->update([
                'student_code' => '2026099',
            ]);

        // 2. تثبيت الرقم 2026100 واسم المستخدم للطالبة هدى شبلي
        $huda = DB::table('users')
            ->where('email', 'hudashbli8@gmail.com')
            ->orWhere('username', 'hudashbli8')
            ->orWhere('full_name', 'like', '%هدى%شبلي%')
            ->first();

        if ($huda) {
            DB::table('users')->where('user_id', $huda->user_id)->update([
                'university_id' => '2026100',
                'username'      => '2026100',
            ]);

            DB::table('students')->where('user_id', $huda->user_id)->update([
                'student_code' => '2026100',
            ]);
        }
    }

    public function down(): void
    {
    }
};
