<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // 1. طالب تجريبي (يدخل برقمه الجامعي)
        User::create([
            'full_name' => 'Student Name',
            'university_id' => '2026100',
            'username' => '2026100',
            'email' => 'student@test.com',
            'password' => Hash::make('12345678'),
            'role' => 'student',
        ]);

        // 2. ولي أمر تجريبي (يدخل باسم المستخدم أو الإيميل)
        User::create([
            'full_name' => 'Parent Name',
            'university_id' => null,
            'username' => '098638799',
            'phone'=>'0986387993',
            'email' => 'parent@test.com',
            'password' => Hash::make('12345678'),
            'role' => 'parent',
        ]);

        User::create([
            'full_name' => 'Teacher Name',
            'username' => '0986387992',
            'phone'=>'0986387992',
            'email' => 'teacher@test.com',
           'password' => Hash::make('12345678'),
           'role' => 'teacher',
]);
    }
}
