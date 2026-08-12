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
        \Illuminate\Support\Facades\DB::statement("
            CREATE OR REPLACE VIEW affairs_dashboard_stats_view AS
            SELECT 
                (SELECT COUNT(*) FROM users WHERE role_id = 3) AS total_students,
                (SELECT COUNT(*) FROM users WHERE role_id = 2) AS total_teachers,
                (SELECT COUNT(*) FROM users WHERE role_id IN (2, 5, 6)) AS total_staff,
                (SELECT COUNT(*) FROM leave_requests WHERE status IN ('pending', 'pending_affairs')) AS pending_leaves,
                (SELECT COUNT(*) FROM users) AS total_users
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        \Illuminate\Support\Facades\DB::statement("DROP VIEW IF EXISTS affairs_dashboard_stats_view");
    }
};
