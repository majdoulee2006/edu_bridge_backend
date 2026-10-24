<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // تهيئة الحسابات الأساسية الثلاثة فقط (إدارة، شؤون، رئيس قسم المعلوماتية)
        $this->call(InitialSystemAccountsSeeder::class);
    }
}
