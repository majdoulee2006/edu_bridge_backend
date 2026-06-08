<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('students', function (Blueprint $table) {
            if (! Schema::hasColumn('students', 'device_id')) {
                $table->string('device_id', 255)->nullable()->after('student_code');
            }
            if (! Schema::hasColumn('students', 'is_device_locked')) {
                $table->tinyInteger('is_device_locked')->default(0)->after('device_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->dropColumn(['device_id', 'is_device_locked']);
        });
    }
};
