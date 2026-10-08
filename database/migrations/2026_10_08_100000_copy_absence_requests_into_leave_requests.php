<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * توحيد إجازات الطلاب على جدول واحد (leave_requests):
 * الويب كان يحفظ الطلبات في absence_requests بينما تطبيق Flutter والبوت يستعملان leave_requests.
 * نسخ آمن للسجلات القديمة (لا يحذف شيئاً ويمكن تشغيله أكثر من مرة):
 *  - students.student_id  ->  users.user_id (leave_requests.student_id)
 *  - النوع: ساعي إن كان السبب يحمل «إذن ساعي»، وإلا يوم كامل
 *  - الحالة تُحفظ كما هي إن كانت من حالات المسار، وإلا تُعتبر معتمدة (recorded) أو بانتظار الشؤون
 *  - تُتجاوز الصفوف الموجودة أصلاً (نموذج ولي الأمر القديم كان يُدخل الطلب في الجدولين معاً)
 * ملاحظة: إشعارات قديمة تشير (related_id) إلى رقم الطلب القديم لن تفتح الطلب الجديد.
 */
return new class extends Migration
{
    private const STATUSES = ['pending', 'pending_hod', 'pending_affairs', 'pending_parent', 'approved', 'rejected'];

    public function up(): void
    {
        if (!Schema::hasTable('absence_requests') || !Schema::hasTable('leave_requests')) {
            return;
        }

        $rows = DB::table('absence_requests')
            ->join('students', 'absence_requests.student_id', '=', 'students.student_id')
            ->select('absence_requests.*', 'students.user_id as student_user_id')
            ->orderBy('absence_requests.request_id')
            ->get();

        foreach ($rows as $r) {
            if (!$r->student_user_id) {
                continue;
            }

            $already = DB::table('leave_requests')
                ->where('student_id', $r->student_user_id)
                ->where('date', $r->date)
                ->where('reason', $r->reason)
                ->exists();
            if ($already) {
                continue;
            }

            $status = in_array($r->status, self::STATUSES, true)
                ? $r->status
                : ($r->status === 'recorded' ? 'approved' : 'pending_affairs');

            DB::table('leave_requests')->insert([
                'student_id' => $r->student_user_id,
                'type'       => str_contains((string) $r->reason, 'إذن ساعي') ? 'hourly' : 'full_day',
                'date'       => $r->date,
                'reason'     => $r->reason,
                'attachment' => $r->document ?? null,
                'status'     => $status,
                'created_at' => $r->created_at ?? now(),
                'updated_at' => $r->updated_at ?? now(),
            ]);
        }
    }

    public function down(): void
    {
        // نسخ بيانات فقط: لا شيء يُعاد (السجلات الجديدة قد تكون عُدّلت)
    }
};
