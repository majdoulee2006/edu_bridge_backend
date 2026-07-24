<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class AbsenceRequestSeeder extends Seeder
{
    public function run()
    {
        // حذف القديم لتجنب التكرار
        DB::table('absence_requests')->truncate();

        // جلب جميع الطلاب
        $students = DB::table('students')->get();

        if ($students->isEmpty()) {
            $this->command->error('لا يوجد طلاب في قاعدة البيانات لإنشاء طلبات إجازة لهم.');
            return;
        }

        // جلب معرف مستخدم إداري أو رئيس قسم أو موظف شؤون للمراجعة
        $reviewer = DB::table('users')->whereIn('role_id', [1, 5, 6])->first();
        $reviewerId = $reviewer ? $reviewer->user_id : null;

        $reasons = [
            'يعاني الطالب من وعكة صحية مفاجئة ويحتاج للراحة التامة لمدة يومين.',
            'ظرف عائلي طارئ يستدعي السفر خارج المدينة مع العائلة.',
            'مراجعة طبيب الأسنان لإجراء عملية جراحية مستعجلة.',
            'حضور حفل زفاف أحد الإخوة في مدينة أخرى.',
            'تجديد الأوراق الثبوتية الشخصية وجواز السفر في الدائرة الحكومية.',
            'المشاركة في مسابقة رياضية رسمية ممثلاً للنادي.',
            'تأخر المواصلات العامة بسبب الأحوال الجوية السائدة صباح اليوم.'
        ];

        $statuses = ['pending', 'approved', 'rejected'];

        $count = 0;
        foreach ($students as $student) {
            // ننشئ 2 إلى 3 طلبات لكل طالب للتنويع
            $numRequests = rand(2, 3);
            for ($i = 0; $i < $numRequests; $i++) {
                $status = $statuses[array_rand($statuses)];
                $date = Carbon::now()->subDays(rand(1, 20))->toDateString();
                
                DB::table('absence_requests')->insert([
                    'student_id'  => $student->student_id,
                    'date'        => $date,
                    'reason'      => $reasons[array_rand($reasons)],
                    'document'    => null,
                    'status'      => $status,
                    'reviewed_by' => $status !== 'pending' ? $reviewerId : null,
                    'created_at'  => Carbon::parse($date)->subHours(rand(1, 12)),
                    'updated_at'  => $status !== 'pending' ? Carbon::parse($date)->addHours(rand(1, 4)) : now(),
                ]);
                $count++;
            }
        }

        $this->command->info("✅ تمت إضافة $count طلب إجازة بنجاح لجميع الطلاب!");
    }
}
