<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('users', 'telegram_id')) {
            Schema::table('users', function (Blueprint $table) {
                $table->string('telegram_id')->nullable()->after('phone');
            });
        }

        if (Schema::hasTable('parents') && !Schema::hasColumn('parents', 'telegram_id')) {
            Schema::table('parents', function (Blueprint $table) {
                $table->string('telegram_id')->nullable();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('users', 'telegram_id')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('telegram_id');
            });
        }
        if (Schema::hasTable('parents') && Schema::hasColumn('parents', 'telegram_id')) {
            Schema::table('parents', function (Blueprint $table) {
                $table->dropColumn('telegram_id');
            });
        }
    }
};
