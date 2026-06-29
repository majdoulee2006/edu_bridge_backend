<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('students', 'reference_photo')) {
            Schema::table('students', function (Blueprint $table) {
                $table->string('reference_photo')->nullable()->after('face_embedding');
            });
        }
    }

    public function down(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->dropColumn('reference_photo');
        });
    }
};
