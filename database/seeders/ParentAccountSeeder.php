<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class ParentAccountSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // تأكدي من مسح السطر القديم $this->call(ParentAccountSeeder::class);
        // لأنه كان بينادي الكلاس داخل نفسه وبيعمل Error تعليق للمشروع.

        User::updateOrCreate(
            ['email' => 'parent@test.com'], // بيبحث إذا الإيميل موجود أولاً عشان ما يطلع خطأ Duplicate
            [
                'full_name' => 'أبو أحمد (تجريبي)',
                'password' => Hash::make('123456'),
                'role' => 'parent',
                'status' => 'active',
            ]
        );
    }
}
