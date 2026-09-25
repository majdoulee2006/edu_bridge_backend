<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('students', function (Blueprint $table) {
            if (!Schema::hasColumn('students', 'web_device_token')) {
                $table->string('web_device_token', 255)->nullable()->after('device_id')
                      ->comment('معرّف الجهاز الموثوق به للويب للتحقق من الأجهزة الجديدة ومطابقة الوجه');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('students', function (Blueprint $table) {
            if (Schema::hasColumn('students', 'web_device_token')) {
                $table->dropColumn('web_device_token');
            }
        });
    }
};
