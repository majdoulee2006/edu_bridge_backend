<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('university_ids', function (Blueprint $table) {
            if (!Schema::hasColumn('university_ids', 'first_name')) {
                $table->string('first_name')->nullable()->after('full_name');
            }
            if (!Schema::hasColumn('university_ids', 'last_name')) {
                $table->string('last_name')->nullable()->after('first_name');
            }
            if (!Schema::hasColumn('university_ids', 'date_of_birth')) {
                $table->date('date_of_birth')->nullable()->after('last_name');
            }
            if (!Schema::hasColumn('university_ids', 'phone')) {
                $table->string('phone', 20)->nullable()->after('date_of_birth');
            }
        });
    }

    public function down(): void
    {
        Schema::table('university_ids', function (Blueprint $table) {
            $table->dropColumn(['first_name', 'last_name', 'date_of_birth', 'phone']);
        });
    }
};
