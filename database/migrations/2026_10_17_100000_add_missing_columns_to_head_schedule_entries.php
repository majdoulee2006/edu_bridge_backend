<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The first create_head_schedule_entries migration made the table with only
     * id/timestamps, so the second one (which defines the real columns) was skipped.
     */
    public function up(): void
    {
        if (!Schema::hasTable('head_schedule_entries')) {
            return;
        }

        Schema::table('head_schedule_entries', function (Blueprint $table) {
            if (!Schema::hasColumn('head_schedule_entries', 'head_user_id')) {
                $table->unsignedBigInteger('head_user_id')->after('id')->index();
            }
            if (!Schema::hasColumn('head_schedule_entries', 'day')) {
                $table->string('day')->after('head_user_id');
            }
            if (!Schema::hasColumn('head_schedule_entries', 'class_name')) {
                $table->string('class_name')->after('day');
            }
            if (!Schema::hasColumn('head_schedule_entries', 'content')) {
                $table->string('content')->after('class_name');
            }
        });
    }

    public function down(): void
    {
        // Intentionally left empty: the columns belong to the table's intended schema.
    }
};
