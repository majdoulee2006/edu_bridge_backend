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
        $indexes = [
            'messages' => ['sender_id', 'receiver_id', 'created_at'],
            'announcements' => ['department_id', 'course_id', 'target_audience', 'created_at'],
            'schedules' => ['course_id', 'day'],
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
                        // Ignore if already indexed
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
