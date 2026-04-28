<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DepartmentSeeder extends Seeder
{
    public function run(): void
    {
        $departments = [
            ['name' => 'المرحلة الأولى', 'description' => 'السنة الدراسية الأولى'],
            ['name' => 'المرحلة الثانية', 'description' => 'السنة الدراسية الثانية'],
            ['name' => 'المرحلة الثالثة', 'description' => 'السنة الدراسية الثالثة'],
            ['name' => 'المرحلة الرابعة', 'description' => 'السنة الدراسية الرابعة'],
        ];

        foreach ($departments as $dept) {
            DB::table('departments')->insertOrIgnore($dept);
        }
    }
}
