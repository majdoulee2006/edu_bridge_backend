<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$studentData = \Illuminate\Support\Facades\DB::table('students')
    ->join('users', 'students.user_id', '=', 'users.user_id')
    ->leftJoin('programs', 'students.program_id', '=', 'programs.id')
    ->where('students.student_id', 31)
    ->select('users.academic_year', 'programs.name as branch_name')
    ->first();

$advisorTeacher = null;
if ($studentData && $studentData->branch_name && $studentData->academic_year) {
    $academicYear = trim($studentData->academic_year);
    if (in_array($academicYear, ['أولى', 'السنة الأولى', '1'])) $academicYear = 'السنة الأولى';

    $advisorTeacher = \Illuminate\Support\Facades\DB::table('teachers')
        ->join('users', 'teachers.user_id', '=', 'users.user_id')
        ->where('advisor_branch', $studentData->branch_name)
        ->where('advisor_year', $academicYear)
        ->select('users.full_name', 'users.email')
        ->first();
}

echo json_encode([
    'student' => $studentData,
    'advisor' => $advisorTeacher
], JSON_UNESCAPED_UNICODE);
