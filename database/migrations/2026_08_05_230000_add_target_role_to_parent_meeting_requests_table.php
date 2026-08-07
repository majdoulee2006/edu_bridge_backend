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
        Schema::table('parent_meeting_requests', function (Blueprint $table) {
            if (!Schema::hasColumn('parent_meeting_requests', 'target_role')) {
                $table->string('target_role')->nullable()->default('affairs')->after('student_id');
            }
            if (!Schema::hasColumn('parent_meeting_requests', 'department_id')) {
                $table->unsignedBigInteger('department_id')->nullable()->after('target_role');
            }
            if (!Schema::hasColumn('parent_meeting_requests', 'target_user_id')) {
                $table->unsignedBigInteger('target_user_id')->nullable()->after('department_id');
            }
        });

        Schema::table('parent_summons', function (Blueprint $table) {
            if (!Schema::hasColumn('parent_summons', 'subject')) {
                $table->string('subject')->nullable()->after('parent_user_id');
            }
            if (!Schema::hasColumn('parent_summons', 'reason')) {
                $table->text('reason')->nullable()->after('subject');
            }
            if (!Schema::hasColumn('parent_summons', 'date')) {
                $table->string('date')->nullable()->after('reason');
            }
            if (!Schema::hasColumn('parent_summons', 'time')) {
                $table->string('time')->nullable()->after('date');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('parent_meeting_requests', function (Blueprint $table) {
            $table->dropColumn(['target_role', 'department_id', 'target_user_id']);
        });

        Schema::table('parent_summons', function (Blueprint $table) {
            $table->dropColumn(['subject', 'reason', 'date', 'time']);
        });
    }
};
