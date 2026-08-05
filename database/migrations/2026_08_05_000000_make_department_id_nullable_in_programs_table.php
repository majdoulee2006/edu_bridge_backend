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
        Schema::table('programs', function (Blueprint $table) {
            $table->unsignedBigInteger('department_id')->nullable()->change();
            
            if (!Schema::hasColumn('programs', 'description')) {
                $table->text('description')->nullable()->after('name');
            }
            if (!Schema::hasColumn('programs', 'year')) {
                $table->string('year')->nullable()->after('description');
            }
            if (!Schema::hasColumn('programs', 'semester')) {
                $table->string('semester')->nullable()->after('year');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('programs', function (Blueprint $table) {
            $table->unsignedBigInteger('department_id')->nullable(false)->change();
            $table->dropColumn(['description', 'year', 'semester']);
        });
    }
};
