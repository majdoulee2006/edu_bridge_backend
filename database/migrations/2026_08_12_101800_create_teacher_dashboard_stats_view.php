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
        DB::statement("
            CREATE OR REPLACE VIEW teacher_dashboard_stats_view AS
            SELECT 
                t.teacher_id,
                (
                    SELECT COUNT(DISTINCT ct.course_id) 
                    FROM course_teachers ct 
                    WHERE ct.teacher_id = t.teacher_id
                ) AS courses_count,
                (
                    SELECT COUNT(DISTINCT a.assignment_id) 
                    FROM assignments a 
                    JOIN course_teachers ct ON a.course_id = ct.course_id 
                    WHERE ct.teacher_id = t.teacher_id
                ) AS active_assignments_count
            FROM teachers t
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("DROP VIEW IF EXISTS teacher_dashboard_stats_view");
    }
};
