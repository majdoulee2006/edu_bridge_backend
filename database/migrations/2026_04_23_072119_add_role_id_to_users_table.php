<?php
// database/migrations/xxxx_add_role_id_to_users_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // أولاً: أضف عمود role_id
        if (!Schema::hasColumn('users', 'role_id')) {
            Schema::table('users', function (Blueprint $table) {
                $table->foreignId('role_id')->nullable()->after('user_id')->constrained('roles', 'role_id');
            });
        }

        // ثانياً: حول البيانات القديمة من عمود role إلى role_id
        // هذا يعتمد على الأدوار الموجودة لديك حالياً
        if (Schema::hasColumn('users', 'role')) {
            $users = DB::table('users')->get();
            foreach ($users as $user) {
                if (isset($user->role)) {
                    $role = DB::table('roles')->where('name', $user->role)->first();
                    if ($role) {
                        DB::table('users')->where('user_id', $user->user_id)->update(['role_id' => $role->role_id]);
                    }
                }
            }
        }

        // ثالثاً: اجعل role_id لا يقبل القيم الفارغة بعد تعبئته
        try {
            Schema::table('users', function (Blueprint $table) {
                $table->foreignId('role_id')->nullable(false)->change();
            });
        } catch (\Exception $e) {
            // some SQLite versions/drivers might complain about changing column nullability or foreign keys
        }

        // رابعاً: احذف عمود role القديم
        if (Schema::hasColumn('users', 'role')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('role');
            });
        }

        // خامساً: احذف عمود children_ids
        if (Schema::hasColumn('users', 'children_ids')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('children_ids');
            });
        }
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('role')->default('student');
            $table->json('children_ids')->nullable();
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['role_id']);
            $table->dropColumn('role_id');
        });
    }
};
