<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('lessons', function (Blueprint $table) {
            if (!Schema::hasColumn('lessons', 'teacher_id')) {
                $table->unsignedBigInteger('teacher_id')->nullable()->after('course_id');
            }
            if (!Schema::hasColumn('lessons', 'type')) {
                $table->string('type')->nullable()->after('content_url');
            }
            if (!Schema::hasColumn('lessons', 'file_size')) {
                $table->string('file_size')->nullable()->after('type');
            }
            if (!Schema::hasColumn('lessons', 'duration')) {
                $table->string('duration')->nullable()->after('file_size');
            }
        });
    }

    public function down(): void
    {
        Schema::table('lessons', function (Blueprint $table) {
            $table->dropColumn(['teacher_id', 'type', 'file_size', 'duration']);
        });
    }
};
