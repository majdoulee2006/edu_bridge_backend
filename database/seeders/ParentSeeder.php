<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class ParentSeeder extends Seeder
{
    public function run()
    {
        // البحث عن المستخدم الذي دوره "ولي أمر"
        $parentUser = User::where('role_id', 4)->first();

        if ($parentUser) {
            // حذف أي سجل قديم لتجنب التكرار
            DB::table('parents')->where('user_id', $parentUser->user_id)->delete();

            // إدخال البيانات الموجودة في المايجريشن فقط (user_id)
            DB::table('parents')->insert([
                'user_id'    => $parentUser->user_id,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}
