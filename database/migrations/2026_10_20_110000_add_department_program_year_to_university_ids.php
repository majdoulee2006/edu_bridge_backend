<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('university_ids', function (Blueprint $table) {
            if (!Schema::hasColumn('university_ids', 'department_id')) {
                $table->unsignedBigInteger('department_id')->nullable()->after('role');
            }
            if (!Schema::hasColumn('university_ids', 'program_id')) {
                $table->unsignedBigInteger('program_id')->nullable()->after('department_id');
            }
            if (!Schema::hasColumn('university_ids', 'academic_year')) {
                $table->string('academic_year', 50)->nullable()->after('program_id');
            }
        });

        // مزامنة البيانات القديمة للأرقام الجامعية المسجلة مسبقاً من جدول الطلاب والمستخدمين
        $usedRecords = DB::table('university_ids')
            ->join('students', 'university_ids.university_id', '=', 'students.student_code')
            ->join('users', 'students.user_id', '=', 'users.user_id')
            ->leftJoin('programs', 'students.program_id', '=', 'programs.id')
            ->leftJoin('departments', function($j) {
                $j->on('programs.department_id', '=', 'departments.department_id')
                  ->orOn('users.department', '=', 'departments.name');
            })
            ->select(
                'university_ids.id as uid_id',
                'departments.department_id as dept_id',
                'programs.id as prog_id',
                DB::raw('COALESCE(students.level, users.academic_year) as year_val')
            )
            ->get();

        foreach ($usedRecords as $rec) {
            DB::table('university_ids')
                ->where('id', $rec->uid_id)
                ->update([
                    'department_id' => $rec->dept_id,
                    'program_id'    => $rec->prog_id,
                    'academic_year' => $rec->year_val,
                ]);
        }
    }

    public function down(): void
    {
        Schema::table('university_ids', function (Blueprint $table) {
            if (Schema::hasColumn('university_ids', 'academic_year')) {
                $table->dropColumn('academic_year');
            }
            if (Schema::hasColumn('university_ids', 'program_id')) {
                $table->dropColumn('program_id');
            }
            if (Schema::hasColumn('university_ids', 'department_id')) {
                $table->dropColumn('department_id');
            }
        });
    }
};
