<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'university_id')) {
                $table->string('university_id')->unique()->nullable()->after('phone');
            }
            if (!Schema::hasColumn('users', 'gender')) {
                $table->enum('gender', ['ذكر', 'أنثى'])->nullable()->after('university_id');
            }
            if (!Schema::hasColumn('users', 'birth_date')) {
                $table->date('birth_date')->nullable()->after('gender');
            }
            if (!Schema::hasColumn('users', 'academic_year')) {
                $table->string('academic_year')->nullable()->after('birth_date');
            }
            if (!Schema::hasColumn('users', 'department')) {
                $table->string('department')->nullable()->after('academic_year');
            }
            if (!Schema::hasColumn('users', 'branch')) {
                $table->string('branch')->nullable()->after('department');
            }
            if (!Schema::hasColumn('users', 'children_ids')) {
                $table->json('children_ids')->nullable()->after('branch');
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(array_filter([
                Schema::hasColumn('users', 'university_id') ? 'university_id' : null,
                Schema::hasColumn('users', 'gender')        ? 'gender'        : null,
                Schema::hasColumn('users', 'birth_date')    ? 'birth_date'    : null,
                Schema::hasColumn('users', 'academic_year') ? 'academic_year' : null,
                Schema::hasColumn('users', 'department')    ? 'department'    : null,
                Schema::hasColumn('users', 'branch')        ? 'branch'        : null,
                Schema::hasColumn('users', 'children_ids')  ? 'children_ids'  : null,
            ]));
        });
    }
};
