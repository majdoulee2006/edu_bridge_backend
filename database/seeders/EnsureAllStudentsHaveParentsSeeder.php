<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

class EnsureAllStudentsHaveParentsSeeder extends Seeder
{
    public function run(): void
    {
        $students = DB::table('students')
            ->join('users', 'students.user_id', '=', 'users.user_id')
            ->select('students.student_id', 'students.student_code', 'users.user_id', 'users.full_name', 'users.phone', 'users.email', 'users.university_id')
            ->get();

        foreach ($students as $st) {
            $isLinked = DB::table('parent_students')
                ->where('student_id', $st->student_id)
                ->orWhere('student_id', $st->user_id)
                ->exists();

            if (!$isLinked) {
                $nameParts = explode(' ', trim($st->full_name));
                $lastName = count($nameParts) > 1 ? end($nameParts) : $st->full_name;

                $existingParent = User::where('role_id', 4)
                    ->where(function($q) use ($lastName, $st) {
                        $q->where('full_name', 'LIKE', "%{$lastName}%")
                          ->orWhere('phone', $st->phone);
                    })
                    ->first();

                if ($existingParent) {
                    $parentId = DB::table('parents')->where('user_id', $existingParent->user_id)->value('parent_id');
                    if (!$parentId) {
                        $parentId = DB::table('parents')->insertGetId([
                            'user_id'    => $existingParent->user_id,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                    }

                    DB::table('parent_students')->insertOrIgnore([
                        'parent_id'    => $existingParent->user_id,
                        'student_id'   => $st->user_id,
                        'relationship' => 'father',
                        'created_at'   => now(),
                        'updated_at'   => now(),
                    ]);
                } else {
                    $parentName = 'أبو ' . $st->full_name;
                    $parentUsername = 'parent_' . ($st->student_code ?? $st->university_id ?? $st->user_id);
                    $parentPhone = '098' . rand(1000000, 9999999);
                    $parentEmail = 'parent.' . ($st->student_code ?? $st->university_id ?? $st->user_id) . '@edu-bridge.com';

                    $newParentUser = User::create([
                        'full_name'  => $parentName,
                        'username'   => $parentUsername,
                        'email'      => $parentEmail,
                        'phone'      => $parentPhone,
                        'password'   => Hash::make('12345678'),
                        'role_id'    => 4,
                        'status'     => 'active',
                    ]);

                    DB::table('parents')->insert([
                        'user_id'    => $newParentUser->user_id,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);

                    DB::table('parent_students')->insertOrIgnore([
                        'parent_id'    => $newParentUser->user_id,
                        'student_id'   => $st->user_id,
                        'relationship' => 'father',
                        'created_at'   => now(),
                        'updated_at'   => now(),
                    ]);
                }
            }
        }
    }
}
