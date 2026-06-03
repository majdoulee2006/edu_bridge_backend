<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class InformationSystemsDeptSeeder extends Seeder
{
    public function run(): void
    {
        // 1. أضف قسم "نظم معلومات" إذا ما كان موجود
        $existing = DB::table('departments')->where('name', 'نظم معلومات')->first();

        if ($existing) {
            $deptId = $existing->department_id;
        } else {
            $deptId = DB::table('departments')->insertGetId([
                'name'        => 'نظم معلومات',
                'description' => 'قسم نظم المعلومات',
                'created_at'  => now(),
                'updated_at'  => now(),
            ]);
        }

        // 2. اربط جميع رؤساء الأقسام الموجودين بهذا القسم
        DB::table('heads')->update([
            'department_id' => $deptId,
            'updated_at'    => now(),
        ]);

        $this->command->info("✅ تم إضافة قسم 'نظم معلومات' (ID: {$deptId}) وربط رؤساء الأقسام به.");
    }
}
