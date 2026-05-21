<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DepartmentProgramSeeder extends Seeder
{
    public function run(): void
    {
        $data = [
            'نظم المعلومات' => ['معلوماتية', 'الكترون', 'اتصالات', 'ذكاء صنعي'],
            'طبي'           => ['صيدلة', 'مخابر'],
            'تجاري'         => ['محاسبة', 'مصارف', 'تجارة الكترونية', 'ادارة اعمال'],
            'هندسي'         => ['ديكور', 'مدني', 'اعلان'],
        ];

        foreach ($data as $deptName => $programs) {
            $deptId = DB::table('departments')->where('name', $deptName)->value('department_id');

            if (!$deptId) {
                $deptId = DB::table('departments')->insertGetId([
                    'name'       => $deptName,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            foreach ($programs as $programName) {
                $exists = DB::table('programs')
                    ->where('name', $programName)
                    ->where('department_id', $deptId)
                    ->exists();

                if (!$exists) {
                    DB::table('programs')->insert([
                        'name'          => $programName,
                        'department_id' => $deptId,
                        'created_at'    => now(),
                        'updated_at'    => now(),
                    ]);
                }
            }
        }

        $this->command->info('✅ تمت إضافة الأقسام والدورات بنجاح');
    }
}
