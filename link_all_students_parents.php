<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

echo "=== Checking Students and Parents ===\n";

$students = DB::table('students')
    ->join('users', 'students.user_id', '=', 'users.user_id')
    ->select('students.student_id', 'students.student_code', 'users.user_id', 'users.full_name', 'users.phone', 'users.email', 'users.university_id')
    ->get();

echo "Found " . $students->count() . " student(s):\n";

foreach ($students as $st) {
    echo "- Student: {$st->full_name} (ID: {$st->student_id}, UserID: {$st->user_id}, Code: {$st->student_code})\n";
    
    $isLinked = DB::table('parent_students')
        ->where('student_id', $st->student_id)
        ->orWhere('student_id', $st->user_id)
        ->exists();

    if ($isLinked) {
        $parentRow = DB::table('parent_students')
            ->where('student_id', $st->student_id)
            ->orWhere('student_id', $st->user_id)
            ->first();
        echo "  [ALREADY LINKED] with Parent ID: {$parentRow->parent_id}\n";
    } else {
        echo "  [NOT LINKED] -> Finding or Creating Parent...\n";

        // Try to find an existing parent by family name or phone
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
            echo "  -> Linked to existing parent: {$existingParent->full_name} ({$existingParent->email})\n";
        } else {
            // Create a new parent account
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

            $parentId = DB::table('parents')->insertGetId([
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

            echo "  -> Created NEW parent: {$parentName} | Username: {$parentUsername} | Phone: {$parentPhone} | Pass: 12345678\n";
        }
    }
}

echo "=== Done linking all students to parents ===\n";
