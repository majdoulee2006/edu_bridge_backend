<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class HODSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // إضافة الأقسام إذا لم تكن موجودة
        $departments = [
            'قسم علوم الحاسوب',
            'قسم الرياضيات',
            'قسم الفيزياء',
            'قسم اللغات',
            'قسم إدارة الأعمال'
        ];

        foreach ($departments as $deptName) {
            DB::table('departments')->insertOrIgnore([
                'name' => $deptName,
                'created_at' => now(),
                'updated_at' => now()
            ]);
        }

        $deptIds = DB::table('departments')->pluck('department_id')->toArray();

        // 5 رؤساء أقسام
        for ($i = 1; $i <= 5; $i++) {
            $userId = DB::table('users')->insertGetId([
                'full_name' => 'رئيس قسم ' . $i,
                'username' => 'hod' . $i,
                'email' => 'hod' . $i . '@example.com',
                'password' => Hash::make('123456'), // الباسورد المطلوب
                'phone' => '050000000' . $i,
                'birth_date' => '1980-01-0' . $i,
                'role_id' => 5, // 5 = head
                'status' => 'active',
                'created_at' => now(),
                'updated_at' => now()
            ]);

            DB::table('heads')->insert([
                'user_id' => $userId,
                'department_id' => $deptIds[$i - 1] ?? 1,
                'created_at' => now(),
                'updated_at' => now()
            ]);
        }
    }
}
