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


        User::updateOrCreate(
            ['email' => 'parent@test.com'],
            [
                'full_name' => ' أحمد ',
                'password' => Hash::make('123456'),
                'role' => 'parent',
                'status' => 'active',
            ]
        );
    }
}
