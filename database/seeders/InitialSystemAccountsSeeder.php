<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

class InitialSystemAccountsSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Roles
        $roles = [
            ['role_id' => 1, 'name' => 'admin'],
            ['role_id' => 2, 'name' => 'teacher'],
            ['role_id' => 3, 'name' => 'student'],
            ['role_id' => 4, 'name' => 'parent'],
            ['role_id' => 5, 'name' => 'head'],
            ['role_id' => 6, 'name' => 'affairs'],
        ];

        foreach ($roles as $r) {
            DB::table('roles')->updateOrInsert(
                ['role_id' => $r['role_id']],
                ['name' => $r['name'], 'updated_at' => now(), 'created_at' => now()]
            );
        }

        // 2. Departments
        $departments = [
            [
                'department_id' => 1,
                'name' => 'هندسي',
                'description' => 'قسم الهندسة والبرامج التقنية والتطبيقية',
            ],
            [
                'department_id' => 2,
                'name' => 'نظم المعلومات الحاسوبية',
                'description' => 'قسم علوم الحاسوب، تكنولوجيا المعلومات، الذكاء الاصطناعي وهندسة البرمجيات',
            ],
            [
                'department_id' => 3,
                'name' => 'طبي',
                'description' => 'قسم العلوم الطبية والصيدلانية والمخبرية',
            ],
            [
                'department_id' => 4,
                'name' => 'تجاري',
                'description' => 'قسم العلوم المالية والمصرفية وإدارة الأعمال',
            ],
        ];

        foreach ($departments as $dept) {
            DB::table('departments')->updateOrInsert(
                ['department_id' => $dept['department_id']],
                [
                    'name' => $dept['name'],
                    'description' => $dept['description'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }

        // 3. Programs for Department 2 (Computer & Information Systems)
        $programs = [
            ['id' => 1, 'department_id' => 2, 'name' => 'معلوماتية'],
            ['id' => 2, 'department_id' => 2, 'name' => 'اتصالات'],
            ['id' => 3, 'department_id' => 2, 'name' => 'الكترون'],
            ['id' => 4, 'department_id' => 2, 'name' => 'ذكاء اصطناعي'],
            ['id' => 5, 'department_id' => 3, 'name' => 'مخبري'],
            ['id' => 6, 'department_id' => 3, 'name' => 'صيدلة'],
            ['id' => 7, 'department_id' => 1, 'name' => 'هندسة مدني'],
            ['id' => 8, 'department_id' => 1, 'name' => 'هندسة عمارة'],
            ['id' => 9, 'department_id' => 1, 'name' => 'هندسة ديكور وتصميم داخلي'],
        ];

        foreach ($programs as $prog) {
            DB::table('programs')->updateOrInsert(
                ['id' => $prog['id']],
                [
                    'department_id' => $prog['department_id'],
                    'name' => $prog['name'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }

        // 4. Admin Account (مدير النظام)
        $adminUser = User::updateOrCreate(
            ['username' => 'admin'],
            [
                'full_name'  => 'مدير النظام الرئيسي',
                'first_name' => 'مدير',
                'last_name'  => 'النظام',
                'email'      => 'admin@edu-bridge.com',
                'password'   => Hash::make('12345678'),
                'role_id'    => 1,
                'status'     => 'active',
                'phone'      => '0911111111',
            ]
        );

        DB::table('admins')->updateOrInsert(
            ['user_id' => $adminUser->user_id],
            ['created_at' => now(), 'updated_at' => now()]
        );

        // 5. Student Affairs Account (موظف شؤون الطلاب)
        User::updateOrCreate(
            ['username' => 'affairs'],
            [
                'full_name'  => 'موظف شؤون الطلاب',
                'first_name' => 'موظف',
                'last_name'  => 'الشؤون',
                'email'      => 'affairs@edu-bridge.com',
                'password'   => Hash::make('12345678'),
                'role_id'    => 6,
                'status'     => 'active',
                'phone'      => '0922222222',
            ]
        );

        // 6. Computer & Information Systems Department Head Account (رئيس قسم الكومبيوتر ونظم المعلومات)
        // هذا الحساب رئيس للقسم بالكامل (Department) بما يشمل كل شعب وبرامج: المعلوماتية، الذكاء الاصطناعي، الاتصالات، والإلكترون
        $headDeptId = 2; // قسم نظم المعلومات الحاسوبية
        $headUser = User::updateOrCreate(
            ['username' => 'head_cis'],
            [
                'full_name'  => 'رئيس قسم الكومبيوتر ونظم المعلومات',
                'first_name' => 'رئيس قسم',
                'last_name'  => 'الكومبيوتر ونظم المعلومات',
                'email'      => 'head.cis@edu-bridge.com',
                'password'   => Hash::make('12345678'),
                'role_id'    => 5,
                'status'     => 'active',
                'department' => 'نظم المعلومات الحاسوبية',
                'phone'      => '0933333333',
            ]
        );

        // حذف أي مستخدم قديم إذا كان باسم head_it
        User::where('username', 'head_it')->where('user_id', '!=', $headUser->user_id)->delete();

        DB::table('heads')->updateOrInsert(
            ['user_id' => $headUser->user_id],
            [
                'department_id' => $headDeptId,
                'created_at'    => now(),
                'updated_at'    => now(),
            ]
        );

        // 7. Ensure at least one active semester exists
        \App\Models\Semester::currentOrCreate();

        $this->command->info('✅ تم إنشاء وتحديث الحسابات الأساسية الـ 3 فقط بنجاح:');
        $this->command->info('👑 1. الإدارة (Admin): username = admin | password = 12345678');
        $this->command->info('🏢 2. شؤون الطلاب (Affairs): username = affairs | password = 12345678');
        $this->command->info('🎓 3. رئيس قسم الكومبيوتر ونظم المعلومات بالكامل (HOD): username = head_cis | password = 12345678');
    }
}
