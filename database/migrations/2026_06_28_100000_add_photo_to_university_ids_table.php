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
        if (!Schema::hasColumn('university_ids', 'photo')) {
            Schema::table('university_ids', function (Blueprint $table) {
                $table->string('photo')->nullable()->after('full_name');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('university_ids', function (Blueprint $table) {
            $table->dropColumn('photo');
        });
    }
};
