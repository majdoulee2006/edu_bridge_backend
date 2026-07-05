<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class SendDailyAttendanceSummary extends Command
{
    protected $signature   = 'attendance:daily-summary';
    protected $description = 'يرسل ملخص حضور اليوم لكل مربي دورة في نهاية اليوم';

    public function handle()
    {
        $today = Carbon::today()->toDateString();

        /*
         * منطق الملخص:
         *  - نجلب كل المربين الذين عندهم طلاب في فرعهم وسنتهم
         *  - لكل مربي: نجمع الطلاب التابعين له ونحسب
         *    • حاضر في اليوم = حضر ولو جلسة واحدة
         *    • غائب في اليوم = غاب في جميع الجلسات (أو ما فيه جلسة أصلاً ولم يُسجَّل)
         */

        // 1. جلب كل المربين النشيطين
        $advisors = DB::table('teachers')
            ->whereNotNull('advisor_branch')
            ->whereNotNull('advisor_year')
            ->join('users', 'teachers.user_id', '=', 'users.user_id')
            ->select('teachers.teacher_id', 'teachers.advisor_branch', 'teachers.advisor_year', 'users.user_id as advisor_user_id', 'users.full_name as advisor_name')
            ->get();

        if ($advisors->isEmpty()) {
            $this->info('لا يوجد مربون معيّنون.');
            return;
        }

        foreach ($advisors as $advisor) {
            // 2. جلب الطلاب التابعين لهذا المربي (نفس الفرع + السنة)
            $students = DB::table('students')
                ->join('users as su', 'students.user_id', '=', 'su.user_id')
                ->leftJoin('programs', 'students.program_id', '=', 'programs.id')
                ->where('programs.name', $advisor->advisor_branch)
                ->where('su.academic_year', $advisor->advisor_year)
                ->select('students.student_id', 'su.full_name as student_name')
                ->get();

            if ($students->isEmpty()) {
                continue;
            }

            $studentIds = $students->pluck('student_id')->toArray();

            // 3. جلب سجلات الحضور لهؤلاء الطلاب اليوم
            $attendanceToday = DB::table('attendance')
                ->whereIn('student_id', $studentIds)
                ->whereDate('attendance_date', $today)
                ->get()
                ->groupBy('student_id');

            // 4. حساب من حضر ومن غاب (منطق: حضر ولو جلسة = حاضر)
            $presentStudents = [];
            $absentStudents  = [];

            foreach ($students as $student) {
                $records = $attendanceToday->get($student->student_id, collect());
                $hasPresent = $records->where('status', 'present')->count() > 0;
                if ($hasPresent) {
                    $presentStudents[] = $student->student_name;
                } else {
                    $absentStudents[] = $student->student_name;
                }
            }

            $totalCount   = count($studentIds);
            $presentCount = count($presentStudents);
            $absentCount  = count($absentStudents);

            // لو ما فيه أي جلسة اليوم → ما نرسل إشعار فارغ
            if ($attendanceToday->isEmpty()) {
                continue;
            }

            // 5. بناء نص الإشعار
            $absentList = !empty($absentStudents)
                ? ' — الغائبون: ' . implode(', ', array_slice($absentStudents, 0, 5)) . (count($absentStudents) > 5 ? ' وآخرون' : '')
                : '';

            $message = "ملخص حضور يوم {$today}: "
                . "إجمالي الطلاب {$totalCount} | "
                . "حاضرون {$presentCount} | "
                . "غائبون {$absentCount}"
                . $absentList;

            // 6. إرسال إشعار واحد للمربي
            DB::table('notifications')->insert([
                'user_id'    => $advisor->advisor_user_id,
                'title'      => 'ملخص حضور اليوم',
                'message'    => $message,
                'type'       => 'attendance_summary',
                'is_read'    => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            \App\Services\FcmService::sendToUser(
                $advisor->advisor_user_id,
                'ملخص حضور اليوم',
                $message,
                ['type' => 'attendance_summary']
            );

            $this->info("✅ أُرسل الملخص للمربي: {$advisor->advisor_name}");
        }

        $this->info('✅ تم إرسال ملخصات الحضور اليومية.');
    }
}
