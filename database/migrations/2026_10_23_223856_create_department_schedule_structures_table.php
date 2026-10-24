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
        Schema::create('department_schedule_structures', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('department_id')->nullable()->index();
            $table->string('academic_year', 50)->nullable();
            $table->string('title')->nullable();
            $table->json('rules'); // Array of day cards
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        if (Schema::hasTable('schedules')) {
            Schema::table('schedules', function (Blueprint $table) {
                if (!Schema::hasColumn('schedules', 'from_period')) {
                    $table->unsignedTinyInteger('from_period')->nullable()->after('day');
                }
                if (!Schema::hasColumn('schedules', 'to_period')) {
                    $table->unsignedTinyInteger('to_period')->nullable()->after('from_period');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('department_schedule_structures');

        if (Schema::hasTable('schedules')) {
            Schema::table('schedules', function (Blueprint $table) {
                if (Schema::hasColumn('schedules', 'to_period')) {
                    $table->dropColumn('to_period');
                }
                if (Schema::hasColumn('schedules', 'from_period')) {
                    $table->dropColumn('from_period');
                }
            });
        }
    }
};
