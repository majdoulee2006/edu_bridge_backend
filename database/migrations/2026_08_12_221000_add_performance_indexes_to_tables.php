<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $indexes = [
            'users'                  => ['role_id', 'status', 'university_id'],
            'students'               => ['user_id', 'department_id', 'program_id'],
            'teachers'               => ['user_id'],
            'enrollments'            => ['student_id', 'course_id'],
            'course_teachers'        => ['teacher_id', 'course_id'],
            'grade_events'           => ['teacher_id', 'course_id', 'type'],
            'grade_entries'          => ['grade_event_id', 'student_id'],
            'notifications'          => ['user_id', 'is_read', 'type'],
            'leave_requests'         => ['user_id', 'status'],
            'assignments'            => ['course_id'],
            'assignment_submissions' => ['assignment_id', 'student_id'],
            'parent_summons'         => ['student_id', 'parent_user_id', 'sender_user_id', 'status'],
        ];

        foreach ($indexes as $tableName => $columns) {
            if (!Schema::hasTable($tableName)) continue;

            foreach ($columns as $column) {
                if (Schema::hasColumn($tableName, $column)) {
                    try {
                        Schema::table($tableName, function (Blueprint $table) use ($column) {
                            $table->index($column);
                        });
                    } catch (\Throwable $e) {
                        // كتم الخطأ في حال وجود الفهرس مسبقاً
                    }
                }
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
