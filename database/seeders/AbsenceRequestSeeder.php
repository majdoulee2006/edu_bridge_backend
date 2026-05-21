<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class AbsenceRequestSeeder extends Seeder
{
    public function run()
    {
        // جلب student_id للطالب التجريبي
        $student = DB::table('students')->where('student_code', '2026100')->first();
        if (!$student) {
            $this->command->error('لم يتم العثور على الطالب 2026100');
            return;
        }
        $studentId = $student->student_id;

        // حذف القديم لتجنب التكرار
        DB::table('absence_requests')->where('student_id', $studentId)->delete();

        DB::table('absence_requests')->insert([
            [
                'student_id'  => $studentId,
                'date'        => Carbon::now()->subDays(1)->toDateString(),
                'reason'      => 'يعاني الطالب من وعكة صحية مفاجئة ويحتاج للراحة.',
                'document'    => null,
                'status'      => 'pending',
                'reviewed_by' => null,
                'created_at'  => now(),
                'updated_at'  => now(),
            ],
            [
                'student_id'  => $studentId,
                'date'        => Carbon::now()->subDays(5)->toDateString(),
                'reason'      => 'خروج مبكر لظرف عائلي طارئ.',
                'document'    => null,
                'status'      => 'approved',
                'reviewed_by' => 1,
                'created_at'  => now()->subDays(5),
                'updated_at'  => now()->subDays(5),
            ],
            [
                'student_id'  => $studentId,
                'date'        => Carbon::now()->subDays(10)->toDateString(),
                'reason'      => 'غياب بسبب ظرف قاهر خارج عن الإرادة.',
                'document'    => null,
                'status'      => 'rejected',
                'reviewed_by' => 1,
                'created_at'  => now()->subDays(10),
                'updated_at'  => now()->subDays(10),
            ],
        ]);

        $this->command->info("✅ تمت إضافة 3 طلبات غياب للطالب رقم $studentId");
    }
}
