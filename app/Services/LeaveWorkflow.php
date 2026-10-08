<?php

namespace App\Services;

use App\Models\LeaveRequest;
use App\Models\User;
use App\Models\UserActivity;
use App\Support\Access;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * مسار إجازة الطالب الواحد لكل القنوات (تطبيق Flutter / ويب / بوت تيليغرام):
 *
 *   الطالب  ──►  ولي الأمر (pending_parent)  ──►  رئيس القسم (pending_hod)  ──►  شؤون الطلاب (pending_affairs)  ──►  approved
 *                     │                                  │                                 │
 *                     └────────────── rejected ◄─────────┴─────────────────────────────────┘
 *
 * القواعد (كانت منسوخة ومتفاوتة بين الكنترولرات):
 *  - كل خطوة تُقبل فقط في مرحلتها (لا قفز بين المراحل ولا إعادة البتّ)
 *  - ولي الأمر لأبنائه، ورئيس القسم لطلاب قسمه (Access)، والشؤون عامة
 *  - إشعار المرحلة التالية يذهب لرئيس **قسم الطالب** وليس لكل رؤساء الأقسام
 *  - الجدول الوحيد هو leave_requests (جدول absence_requests القديم نُقل إليه ثم حُذف بمايغريشن)
 *
 * كل دالة قرار ترجع ['ok' => true, 'leave' => كائن الطلب بعد التحديث] أو ['ok' => false, 'error' => ...]
 * حيث error واحد من: not_found | forbidden | stage | invalid.
 */
class LeaveWorkflow
{
    public const STAGE_PARENT  = 'pending_parent';
    public const STAGE_HOD     = 'pending_hod';
    public const STAGE_AFFAIRS = 'pending_affairs';

    // ───────────────────────── الطالب ─────────────────────────

    /**
     * يسجّل الطلب ويرسله لولي الأمر (أو لرئيس القسم مباشرة إن لم يكن للطالب ولي أمر مربوط).
     * التحقق من التاريخ/الوقت/السبب مسؤولية المستدعي (لكل قناة رسائلها).
     */
    public static function submit(User $student, string $type, string $date, string $reason, ?string $attachment = null): LeaveRequest
    {
        $leave = LeaveRequest::create([
            'student_id' => $student->user_id,
            'type'       => $type === 'hourly' ? 'hourly' : 'full_day',
            'date'       => $date,
            'reason'     => $reason,
            'attachment' => $attachment,
            'status'     => self::STAGE_PARENT,
        ]);

        $name = $student->full_name ?? 'الطالب';
        $parents = self::parentUserIds($student);

        if ($parents->isNotEmpty()) {
            foreach ($parents as $parentId) {
                self::notify(
                    $parentId,
                    'طلب إجازة يحتاج موافقتك',
                    "قدّم {$name} طلب إجازة بتاريخ {$date}، يرجى مراجعة الطلب والرد عليه.",
                    $leave->id
                );
            }
        } else {
            // بلا ولي أمر مربوط: ينتقل مباشرة لرئيس قسم الطالب
            $leave->status = self::STAGE_HOD;
            $leave->save();
            self::notifyHeadsOf($student->user_id, 'طلب إجازة جديد بانتظار موافقتك', "قدّم الطالب {$name} طلب إجازة بتاريخ {$date}، يرجى مراجعته.", $leave->id);
        }

        UserActivity::log('تقديم طلب إجازة', "قام الطالب بتقديم طلب إجازة بتاريخ {$date}", $student);

        return $leave;
    }

    /**
     * ولي الأمر يقدّم الإجازة نيابة عن ابنه: موافقته مفهومة ضمناً، فيبدأ الطلب من مرحلة رئيس القسم.
     * (كان الويب يُدخلها في جدولين معاً فتظهر مرتين لرئيس القسم.)
     */
    public static function submitByParent(User $parent, User $student, string $type, string $date, string $reason, ?string $attachment = null, string $title = 'طلب إجازة من ولي الأمر', ?string $message = null): LeaveRequest
    {
        $leave = LeaveRequest::create([
            'student_id' => $student->user_id,
            'type'       => $type,
            'date'       => $date,
            'reason'     => $reason,
            'attachment' => $attachment,
            'status'     => self::STAGE_HOD,
        ]);

        $name = $student->full_name ?? 'الطالب';
        $message ??= "قدّم ولي أمر الطالب {$name} طلب إجازة بتاريخ {$date}، بانتظار موافقتك.";
        foreach (self::reviewerIdsOf($student->user_id) as $reviewerId) {
            self::notify($reviewerId, $title, $message, $leave->id);
        }

        UserActivity::log('تقديم طلب إجازة من ولي الأمر', "قدّم ولي الأمر طلب إجازة للطالب {$name} بتاريخ {$date}", $parent);

        return $leave;
    }

    // ───────────────────────── ولي الأمر ─────────────────────────

    public static function parentRespond(User $parent, int $leaveId, string $decision): array
    {
        $leave = self::find($leaveId);
        if (!$leave) {
            return self::fail('not_found');
        }
        if (!Access::parentOwnsStudent($parent, $leave->student_id, 'user')) {
            return self::fail('forbidden');
        }
        if ($leave->status !== self::STAGE_PARENT) {
            return self::fail('stage');
        }

        $name = self::userName($leave->student_id);

        if ($decision === 'approved') {
            self::setStatus($leaveId, self::STAGE_HOD);
            self::notifyHeadsOf($leave->student_id, 'طلب إجازة بانتظار موافقتك', "وافق ولي أمر الطالب {$name} على طلب إجازة بتاريخ {$leave->date}، يرجى مراجعته.", $leaveId);
        } else {
            self::setStatus($leaveId, 'rejected');
            $typeText = $leave->type === 'hourly' ? 'الساعية' : 'اليومية';
            self::notify($leave->student_id, 'تم رفض طلب الإجازة', "تم رفض طلب إجازتك {$typeText} بتاريخ {$leave->date} من قِبل ولي الأمر", $leaveId);
        }

        return self::ok($leaveId);
    }

    // ───────────────────────── رئيس القسم ─────────────────────────

    public static function hodRespond(User $head, int $leaveId, string $decision): array
    {
        $leave = self::find($leaveId);
        if (!$leave) {
            return self::fail('not_found');
        }
        if (!$leave->student_id || !Access::headManagesUser($head, $leave->student_id)) {
            return self::fail('forbidden');
        }
        if ($leave->status !== self::STAGE_HOD) {
            return self::fail('stage');
        }

        if ($decision === 'approved') {
            // رئيس القسم يمرّر الطلب لشؤون الطلاب للاعتماد النهائي، ولا يعتمده بنفسه
            self::setStatus($leaveId, self::STAGE_AFFAIRS);
            $name = self::userName($leave->student_id);
            foreach (DB::table('users')->where('role_id', 6)->pluck('user_id') as $affairsId) {
                self::notify($affairsId, 'طلب إذن جديد بانتظار الاعتماد النهائي', "وافق ولي الأمر ورئيس القسم على طلب إذن الطالب {$name} بتاريخ {$leave->date}، يرجى الاعتماد النهائي.", $leaveId);
            }
        } else {
            self::setStatus($leaveId, 'rejected');
            self::notify($leave->student_id, 'تم رفض طلب الإذن/الإجازة', "تم رفض طلب إذنك بتاريخ {$leave->date} من قِبل رئيس القسم.", $leaveId);
        }

        return self::ok($leaveId);
    }

    // ───────────────────────── شؤون الطلاب ─────────────────────────

    public static function affairsRespond(User $affairs, int $leaveId, string $decision): array
    {
        $leave = self::find($leaveId);
        if (!$leave) {
            return self::fail('not_found');
        }
        if ($leave->status !== self::STAGE_AFFAIRS) {
            return self::fail('stage');
        }

        $approved = $decision === 'approved';
        self::setStatus($leaveId, $approved ? 'approved' : 'rejected');

        $title = $approved ? 'القرار النهائي: تمت الموافقة على الإجازة' : 'القرار النهائي: تم رفض الإجازة';
        $name  = self::userName($leave->student_id);

        self::notify(
            $leave->student_id,
            $title,
            $approved
                ? "وافقت إدارة شؤون الطلاب نهائياً على طلب إجازتك بتاريخ {$leave->date}."
                : "نعتذر، تم رفض طلب إجازتك بتاريخ {$leave->date} من قِبل إدارة شؤون الطلاب.",
            $leaveId
        );

        $parentMsg = $approved
            ? "وافقت شؤون الطلاب نهائياً على طلب الإجازة المقدم بتاريخ {$leave->date}."
            : "تم رفض طلب الإجازة المقدم بتاريخ {$leave->date} من قِبل شؤون الطلاب.";
        $student = User::find($leave->student_id);
        if ($student) {
            foreach (self::parentUserIds($student) as $parentId) {
                self::notify($parentId, $title, $parentMsg, $leaveId);
            }
        }

        $hodMsg = $approved
            ? "اعتمدت شؤون الطلاب إجازة الطالب {$name} بتاريخ {$leave->date}"
            : "رفضت شؤون الطلاب إجازة الطالب {$name} بتاريخ {$leave->date}";
        foreach (self::headUserIdsOf($leave->student_id) as $headId) {
            self::notify($headId, $title, $hodMsg, $leaveId);
        }

        // مربي الدورة (Advisor): المعلم المسؤول عن قسم وسنة الطالب (كما في تطبيق الموبايل)
        $info = DB::table('users')
            ->join('students', 'users.user_id', '=', 'students.user_id')
            ->where('users.user_id', $leave->student_id)
            ->select('users.department', 'users.full_name', 'students.level')
            ->first();
        if ($info && $info->department && $info->level) {
            $advisorUserId = DB::table('teachers')
                ->where('advisor_branch', $info->department)
                ->where('advisor_year', $info->level)
                ->value('user_id');
            if ($advisorUserId) {
                self::notify(
                    $advisorUserId,
                    'تحديث حالة تبرير غياب',
                    ($approved ? 'قامت شؤون الطلاب بقبول' : 'قامت شؤون الطلاب برفض') . " تبرير غياب للطالب {$info->full_name} (عن تاريخ {$leave->date})",
                    $leaveId
                );
            }
        }

        return self::ok($leaveId);
    }

    // ───────────────────────── أدوات ─────────────────────────

    /** أولياء أمر الطالب (users.user_id): تدعم الربط القديم بـ students.student_id و parents.parent_id. */
    public static function parentUserIds(User $student): Collection
    {
        $studentRow = DB::table('students')->where('user_id', $student->user_id)->first();
        $keys = array_filter([$student->user_id, $studentRow->student_id ?? null]);

        return DB::table('parent_students')
            ->join('parents', function ($j) {
                $j->on('parent_students.parent_id', '=', 'parents.user_id')
                  ->orOn('parent_students.parent_id', '=', 'parents.parent_id');
            })
            ->whereIn('parent_students.student_id', $keys)
            ->pluck('parents.user_id')
            ->filter()
            ->unique()
            ->values();
    }

    /** رؤساء قسم الطالب فقط (users.department أو جدول heads)، وإلا المديرون كي لا يبقى الطلب بلا مسؤول. */
    public static function headUserIdsOf(int $studentUserId): array
    {
        $dept = DB::table('users')->where('user_id', $studentUserId)->value('department');
        if (!$dept) {
            return [];
        }

        return DB::table('users')->where('role_id', 5)->where('department', $dept)->pluck('user_id')
            ->merge(
                DB::table('heads')
                    ->join('departments', 'heads.department_id', '=', 'departments.department_id')
                    ->where('departments.name', $dept)
                    ->pluck('heads.user_id')
            )
            ->unique()->values()->all();
    }

    /** من يُنبَّه لطلب بانتظار رئيس القسم: رئيس قسم الطالب، وإلا المديرون كي لا يضيع الطلب. */
    public static function reviewerIdsOf(int $studentUserId): array
    {
        $heads = self::headUserIdsOf($studentUserId);

        return $heads ?: DB::table('users')->where('role_id', 1)->pluck('user_id')->all();
    }

    private static function notifyHeadsOf(int $studentUserId, string $title, string $message, int $leaveId): void
    {
        if (!self::headUserIdsOf($studentUserId)) {
            $message .= ' (لا يوجد رئيس قسم مرتبط بقسم الطالب)';
        }

        foreach (self::reviewerIdsOf($studentUserId) as $reviewerId) {
            self::notify($reviewerId, $title, $message, $leaveId);
        }
    }

    private static function notify(int $userId, string $title, string $message, int $leaveId): void
    {
        DB::table('notifications')->insert([
            'user_id'    => $userId,
            'title'      => $title,
            'message'    => $message,
            'type'       => 'leave_request',
            'category'   => 'administrative',
            'related_id' => $leaveId,
            'is_read'    => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        FcmService::sendToUser($userId, $title, $message, ['type' => 'leave_request', 'related_id' => (string) $leaveId]);
    }

    private static function find(int $leaveId): ?object
    {
        return DB::table('leave_requests')->where('id', $leaveId)->first();
    }

    private static function setStatus(int $leaveId, string $status): void
    {
        DB::table('leave_requests')->where('id', $leaveId)->update(['status' => $status, 'updated_at' => now()]);
    }

    private static function userName(?int $userId): string
    {
        return (string) (DB::table('users')->where('user_id', $userId)->value('full_name') ?: 'الطالب');
    }

    private static function ok(int $leaveId): array
    {
        return ['ok' => true, 'leave' => self::find($leaveId)];
    }

    private static function fail(string $error): array
    {
        return ['ok' => false, 'error' => $error];
    }
}
