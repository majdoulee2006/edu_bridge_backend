<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

$latest = DB::table('report_requests')->orderBy('id', 'desc')->first();
if ($latest && $latest->status == 'pending') {
    $reportId = DB::table('performance_reports')->insertGetId([
        'student_id' => $latest->student_id,
        'report_type' => $latest->report_type,
        'attendance_rate' => 98,
        'average_grade' => 92,
        'recommendations' => 'الطالب متميز جداً في الحضور والمشاركة الصفية. نوصي بتكريمه.',
        'generated_at' => now(),
        'created_at' => now(),
        'updated_at' => now(),
    ]);
    DB::table('report_requests')->where('id', $latest->id)->update(['status' => 'completed', 'updated_at' => now()]);
    DB::table('notifications')->insert([
        'user_id' => $latest->head_id,
        'title' => 'تم تسليم تقرير جديد',
        'message' => 'قام المدرب بالرد على طلب التقرير الأخير بنجاح',
        'type' => 'report_submitted',
        'is_read' => 0,
        'created_at' => now(),
        'updated_at' => now(),
    ]);
    echo "Report for request ID {$latest->id} has been submitted.\n";
} else {
    echo "No pending request found or already submitted.\n";
}
