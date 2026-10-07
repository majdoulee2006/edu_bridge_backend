<?php

namespace App\Services\TelegramBot;

use App\Models\User;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\Parents;
use App\Models\Schedule;
use App\Models\Grade;
use App\Models\Attendance;
use App\Models\AttendanceSession;
use App\Models\Course;
use App\Models\Lesson;
use App\Models\Resource;
use App\Models\Assignment;
use App\Models\AssignmentSubmission;
use App\Models\Exam;
use App\Models\Announcement;
use App\Models\Notification;
use App\Models\AbsenceRequest;
use App\Models\StudentRequest;
use App\Models\Program;
use App\Services\FcmService;
use App\Support\LoginThrottleGuard;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

/**
 * Telegram bot: student affairs officer services
 * (moved as-is from TelegramBotHandler; methods keep their names and behaviour)
 */
trait AffairsHandlers
{
    // ==========================================
    // Student Affairs Services Handlers (موظف شؤون الطلاب)
    // ==========================================

    private function sendAffairsMainMenu($chatId, $headerText = null)
    {
        $keyboard = [
            'keyboard' => [
                [['text' => '📊 إحصائيات الشؤون'], ['text' => '✈️ إجازات وأعذار الطلاب']],
                [['text' => '📷 طلبات بصمة الوجه'], ['text' => '📑 الخدمات والطلبات الطلابية']],
                [['text' => '🔍 استعلام سريع عن طالب'], ['text' => '📢 نشر إعلان وتعميم عام']],
                [['text' => '🚪 تسجيل خروج']]
            ],
            'resize_keyboard' => true,
            'persistent' => true
        ];

        $text = $headerText ?? "🏢 **لوحة خدمات موظف شؤون الطلاب**\nيرجى اختيار الخدمة المطلوبة:";
        $this->sendMessage($chatId, $text, null, $keyboard);
    }

    private function handleAffairsOverview(User $user, $chatId)
    {
        $totalStudents = Student::count();
        $totalTeachers = Teacher::count();
        $totalStaff    = User::where('role_id', 6)->count();
        $totalParents  = Parents::count();

        $pendingLeaves = DB::table('leave_requests')->whereIn('status', ['pending_affairs', 'pending'])->count()
            + DB::table('absence_requests')->whereIn('status', ['pending_affairs', 'pending'])->count();

        $pendingPhotos = DB::table('photo_change_requests')->where('status', 'pending')->count();
        $pendingReqs   = StudentRequest::whereIn('status', ['pending_affairs', 'pending'])->count();

        $activeSemester = DB::table('semesters')->where('is_active', true)->first();
        $semesterName   = $activeSemester->semester_name ?? $activeSemester->name ?? 'الفصل الحالي نشط';

        $msg = "🏢 **لوحة معلومات وإحصائيات شؤون الطلاب**\n\n";
        $msg .= "👤 **الموظف المسؤول:** الأستاذ/ة {$user->full_name}\n";
        $msg .= "🗓️ **الفصل الدراسي:** `{$semesterName}`\n";
        $msg .= "─────────────\n";
        $msg .= "👥 **إحصائيات المجتمع الأكاديمي:**\n";
        $msg .= "  🎓 إجمالي الطلاب المسجلين: `{$totalStudents}` طالب\n";
        $msg .= "  👨‍🏫 كادر الأساتذة والمدربين: `{$totalTeachers}` مدرب\n";
        $msg .= "  👨‍👩‍👧 أولياء الأمور المرتبطين: `{$totalParents}` ولي أمر\n";
        $msg .= "  🏢 كادر شؤون الطلاب: `{$totalStaff}` موظف\n";
        $msg .= "─────────────\n";
        $msg .= "⚡ **المهام والطلبات المعلقة للتدقيق:**\n";
        $msg .= "  ✈️ طلبات إجازة وأعذار بانتظار الموافقة: `{$pendingLeaves}`\n";
        $msg .= "  📷 طلبات تحديث بصمة الوجه والصور: `{$pendingPhotos}`\n";
        $msg .= "  📑 طلبات خدمات وفك قفل أجهزة: `{$pendingReqs}`\n";

        $keyboard = [
            'inline_keyboard' => [
                [
                    ['text' => '✈️ مراجعة الإجازات (' . $pendingLeaves . ')', 'callback_data' => 'affairs_action_leaves'],
                    ['text' => '📷 طلبات الصور (' . $pendingPhotos . ')', 'callback_data' => 'affairs_action_photos'],
                ],
                [
                    ['text' => '📑 الخدمات والطلبات (' . $pendingReqs . ')', 'callback_data' => 'affairs_action_requests'],
                    ['text' => '📢 نشر تعميم رسمي', 'callback_data' => 'affairs_action_broadcast'],
                ]
            ]
        ];

        $this->sendMessage($chatId, $msg, null, $keyboard);
    }

    private function handleAffairsStudentLeaves(User $user, $chatId)
    {
        // 1. leave_requests
        $q1 = DB::table('leave_requests')
            ->join('users', 'leave_requests.student_id', '=', 'users.user_id')
            ->leftJoin('students', 'students.user_id', '=', 'users.user_id')
            ->leftJoin('programs', 'students.program_id', '=', 'programs.id')
            ->select(
                'leave_requests.id',
                'leave_requests.student_id',
                'leave_requests.type',
                'leave_requests.date',
                'leave_requests.reason',
                'leave_requests.status',
                'leave_requests.created_at',
                'users.full_name as student_name',
                'students.level',
                'students.student_code',
                'programs.name as program_name',
                DB::raw("'leave_requests' as src_table")
            )
            ->whereIn('leave_requests.status', ['pending_affairs', 'pending']);

        // 2. absence_requests
        $q2 = DB::table('absence_requests')
            ->join('students', 'absence_requests.student_id', '=', 'students.student_id')
            ->join('users', 'students.user_id', '=', 'users.user_id')
            ->leftJoin('programs', 'students.program_id', '=', 'programs.id')
            ->select(
                'absence_requests.request_id as id',
                'students.user_id as student_id',
                DB::raw("'full_day' as type"),
                'absence_requests.date',
                'absence_requests.reason',
                'absence_requests.status',
                'absence_requests.created_at',
                'users.full_name as student_name',
                'students.level',
                'students.student_code',
                'programs.name as program_name',
                DB::raw("'absence_requests' as src_table")
            )
            ->whereIn('absence_requests.status', ['pending_affairs', 'pending']);

        $allLeaves = $q1->take(6)->get()->concat($q2->take(6)->get())->sortByDesc('created_at')->take(8);

        if ($allLeaves->isEmpty()) {
            $this->sendMessage($chatId, "🌟 **لا توجد أي طلبات إجازة أو أعذار معلقة بانتظار موافقة الشؤون حالياً.**");
            return;
        }

        $this->sendMessage($chatId, "✈️ **طلبات الإجازات والأعذار الطلابية المعلقة ({$allLeaves->count()}):**");

        foreach ($allLeaves as $l) {
            $src = $l->src_table;
            $typeLabel = match($l->type) {
                'hourly'   => '⏱️ إجازة ساعية',
                'full_day' => '📅 عذر غياب يوم كامل',
                default    => '✈️ طلب إجازة رسمي',
            };

            $code = $l->student_code ? " (`{$l->student_code}`)" : "";
            $prog = $l->program_name ?? 'عام';
            $level = $l->level ?? 'السنة الأولى';

            $msg = "🎓 **الطالب:** {$l->student_name}{$code}\n";
            $msg .= "🏛️ **التخصص والسنة:** {$prog} - {$level}\n";
            $msg .= "📌 **النوع:** {$typeLabel}\n";
            $msg .= "📅 **التاريخ المطلوب:** `{$l->date}`\n";
            $msg .= "📝 **السبب والبيان:** \"{$l->reason}\"\n";

            $keyboard = [
                'inline_keyboard' => [
                    [
                        ['text' => '✅ موافقة نهائية', 'callback_data' => "affairs_approve_leave_{$src}_{$l->id}"],
                        ['text' => '❌ رفض الطلب', 'callback_data' => "affairs_reject_leave_{$src}_{$l->id}"],
                    ]
                ]
            ];

            $this->sendMessage($chatId, $msg, null, $keyboard);
        }
    }

    private function handleAffairsApproveLeave(User $user, $chatId, string $src, int $id)
    {
        $studentUserId = null;
        $leaveDate = now()->format('Y-m-d');

        if ($src === 'leave_requests') {
            $rec = DB::table('leave_requests')->where('id', $id)->first();
            if (!$rec) {
                $this->sendMessage($chatId, "❌ طلب الإجازة غير موجود.");
                return;
            }
            DB::table('leave_requests')->where('id', $id)->update(['status' => 'approved', 'updated_at' => now()]);
            $studentUserId = $rec->student_id;
            $leaveDate = $rec->date ?? $leaveDate;
        } else {
            $rec = DB::table('absence_requests')->where('request_id', $id)->first();
            if (!$rec) {
                $this->sendMessage($chatId, "❌ عذر الغياب غير موجود.");
                return;
            }
            DB::table('absence_requests')->where('request_id', $id)->update(['status' => 'approved', 'updated_at' => now()]);
            $studentUserId = DB::table('students')->where('student_id', $rec->student_id)->value('user_id') ?? $rec->student_id;
            $leaveDate = $rec->date ?? $leaveDate;
        }

        if ($studentUserId) {
            $studentUser = User::find($studentUserId);
            $title = 'تمت الموافقة النهائية على طلب الإجازة ✓';
            $message = "تهانينا، تمت الموافقة النهائية على طلب إجازتك/عذرك لتاريخ {$leaveDate} من قِبل إدارة شؤون الطلاب.";

            Notification::create([
                'user_id'    => $studentUserId,
                'sender_id'  => $user->user_id,
                'title'      => $title,
                'message'    => $message,
                'type'       => 'leave_request',
                'category'   => 'administrative',
                'related_id' => $id,
                'is_read'    => false,
            ]);

            FcmService::sendToUser($studentUserId, $title, $message, ['type' => 'leave_request', 'related_id' => (string)$id]);

            if ($studentUser && $studentUser->telegram_chat_id) {
                $this->sendMessage(
                    $studentUser->telegram_chat_id,
                    "✅ **إشعار من شؤون الطلاب** 🏢\n\nتمت **الموافقة النهائية** على طلب الإجازة لتاريخ `{$leaveDate}` بنجاح."
                );
            }
        }

        $this->sendMessage($chatId, "✅ **تمت الموافقة على طلب الإجازة بنجاح وإشعار الطالب فورياً.**");
    }

    private function handleAffairsRejectLeave(User $user, $chatId, string $src, int $id)
    {
        $studentUserId = null;
        $leaveDate = now()->format('Y-m-d');

        if ($src === 'leave_requests') {
            $rec = DB::table('leave_requests')->where('id', $id)->first();
            if (!$rec) {
                $this->sendMessage($chatId, "❌ طلب الإجازة غير موجود.");
                return;
            }
            DB::table('leave_requests')->where('id', $id)->update(['status' => 'rejected', 'updated_at' => now()]);
            $studentUserId = $rec->student_id;
            $leaveDate = $rec->date ?? $leaveDate;
        } else {
            $rec = DB::table('absence_requests')->where('request_id', $id)->first();
            if (!$rec) {
                $this->sendMessage($chatId, "❌ عذر الغياب غير موجود.");
                return;
            }
            DB::table('absence_requests')->where('request_id', $id)->update(['status' => 'rejected', 'updated_at' => now()]);
            $studentUserId = DB::table('students')->where('student_id', $rec->student_id)->value('user_id') ?? $rec->student_id;
            $leaveDate = $rec->date ?? $leaveDate;
        }

        if ($studentUserId) {
            $studentUser = User::find($studentUserId);
            $title = 'تم رفض طلب الإجازة';
            $message = "نعتذر، تم رفض طلب إجازتك لتاريخ {$leaveDate} من قِبل إدارة شؤون الطلاب.";

            Notification::create([
                'user_id'    => $studentUserId,
                'sender_id'  => $user->user_id,
                'title'      => $title,
                'message'    => $message,
                'type'       => 'leave_request',
                'category'   => 'administrative',
                'related_id' => $id,
                'is_read'    => false,
            ]);

            FcmService::sendToUser($studentUserId, $title, $message, ['type' => 'leave_request', 'related_id' => (string)$id]);

            if ($studentUser && $studentUser->telegram_chat_id) {
                $this->sendMessage(
                    $studentUser->telegram_chat_id,
                    "⚠️ **إشعار من شؤون الطلاب** 🏢\n\nنعتذر، تم **رفض طلب الإجازة** لتاريخ `{$leaveDate}` من قِبل إدارة شؤون الطلاب."
                );
            }
        }

        $this->sendMessage($chatId, "🛑 **تم رفض طلب الإجازة وإشعار الطالب.**");
    }

    private function handleAffairsPhotoRequests(User $user, $chatId)
    {
        $requests = DB::table('photo_change_requests')
            ->join('users', 'photo_change_requests.user_id', '=', 'users.user_id')
            ->leftJoin('students', 'students.user_id', '=', 'users.user_id')
            ->where('photo_change_requests.status', 'pending')
            ->select(
                'photo_change_requests.id',
                'photo_change_requests.user_id',
                'photo_change_requests.old_photo',
                'photo_change_requests.new_photo',
                'photo_change_requests.created_at',
                'users.full_name',
                'users.department',
                'students.student_code'
            )
            ->orderByDesc('photo_change_requests.created_at')
            ->take(8)
            ->get();

        if ($requests->isEmpty()) {
            $this->sendMessage($chatId, "🌟 **لا توجد أي طلبات تغيير صورة وبصمة وجه معلقة حالياً.**");
            return;
        }

        $this->sendMessage($chatId, "📷 **طلبات تحديث بصمة الوجه والصور الشخصية ({$requests->count()}):**");

        foreach ($requests as $r) {
            $code = $r->student_code ? " (`{$r->student_code}`)" : "";
            $dept = $r->department ?? 'عام';
            $date = date('Y-m-d', strtotime($r->created_at));

            $msg = "👤 **الطالب:** {$r->full_name}{$code}\n";
            $msg .= "🏛️ **القسم:** {$dept}\n";
            $msg .= "📅 **تاريخ الطلب:** `{$date}`\n";
            $msg .= "📷 تم رفع صورة جديدة لبصمة الوجه والتحقق من الهوية.\n";

            $keyboard = [
                'inline_keyboard' => [
                    [
                        ['text' => '✅ اعتماد الصورة والبصمة', 'callback_data' => "affairs_approve_photo_{$r->id}"],
                        ['text' => '❌ رفض الطلب', 'callback_data' => "affairs_reject_photo_{$r->id}"],
                    ]
                ]
            ];

            $this->sendMessage($chatId, $msg, null, $keyboard);
        }
    }

    private function handleAffairsApprovePhoto(User $user, $chatId, int $reqId)
    {
        $req = DB::table('photo_change_requests')->where('id', $reqId)->where('status', 'pending')->first();
        if (!$req) {
            $this->sendMessage($chatId, "❌ الطلب غير موجود أو تمت معالجته مسبقاً.");
            return;
        }

        if ($req->old_photo) {
            \Illuminate\Support\Facades\Storage::disk('public')->delete($req->old_photo);
        }

        DB::table('users')->where('user_id', $req->user_id)->update(['avatar' => $req->new_photo]);
        DB::table('students')->where('user_id', $req->user_id)->update(['reference_photo' => $req->new_photo, 'face_embedding' => null]);
        DB::table('photo_change_requests')->where('id', $reqId)->update(['status' => 'approved', 'updated_at' => now()]);

        $stUser = User::find($req->user_id);
        $title = 'تمت الموافقة على تغيير صورة الوجه 📷';
        $message = 'تمت الموافقة من قِبل إدارة شؤون الطلاب على طلب تحديث صورة بصمة الوجه الخاصة بك واعتمادها رسمياً.';

        Notification::create([
            'user_id'    => $req->user_id,
            'sender_id'  => $user->user_id,
            'title'      => $title,
            'message'    => $message,
            'type'       => 'academic',
            'category'   => 'academic',
            'is_read'    => false,
        ]);

        FcmService::sendToUser($req->user_id, $title, $message, ['type' => 'academic']);

        if ($stUser && $stUser->telegram_chat_id) {
            $this->sendMessage(
                $stUser->telegram_chat_id,
                "📷 **إشعار من شؤون الطلاب** 🏢\n\nتهانينا، تمت **الموافقة على تحديث صورة بصمة الوجه** الخاصة بك واعتمادها بنجاح في النظام!"
            );
        }

        $this->sendMessage($chatId, "✅ **تمت الموافقة على طلب تحديث الصورة وبصمة الوجه بنجاح!**");
    }

    private function handleAffairsRejectPhoto(User $user, $chatId, int $reqId)
    {
        $req = DB::table('photo_change_requests')->where('id', $reqId)->where('status', 'pending')->first();
        if (!$req) {
            $this->sendMessage($chatId, "❌ الطلب غير موجود أو تمت معالجته مسبقاً.");
            return;
        }

        if ($req->new_photo) {
            \Illuminate\Support\Facades\Storage::disk('public')->delete($req->new_photo);
        }

        DB::table('photo_change_requests')->where('id', $reqId)->update(['status' => 'rejected', 'updated_at' => now()]);

        $stUser = User::find($req->user_id);
        $title = 'تم رفض طلب تغيير الصورة';
        $message = 'تم رفض طلب تحديث صورة بصمة الوجه الخاصة بك من قِبل شؤون الطلاب.';

        Notification::create([
            'user_id'    => $req->user_id,
            'sender_id'  => $user->user_id,
            'title'      => $title,
            'message'    => $message,
            'type'       => 'alert',
            'category'   => 'academic',
            'is_read'    => false,
        ]);

        FcmService::sendToUser($req->user_id, $title, $message, ['type' => 'photo_request', 'status' => 'rejected']);

        if ($stUser && $stUser->telegram_chat_id) {
            $this->sendMessage(
                $stUser->telegram_chat_id,
                "⚠️ **إشعار من شؤون الطلاب** 🏢\n\nنعتذر، تم **رفض طلب تحديث صورة بصمة الوجه** من قِبل إدارة شؤون الطلاب."
            );
        }

        $this->sendMessage($chatId, "🛑 **تم رفض طلب تحديث الصورة وإشعار الطالب.**");
    }

    private function handleAffairsStudentRequests(User $user, $chatId)
    {
        $requests = StudentRequest::with(['student.user', 'student.program'])
            ->whereIn('status', ['pending_affairs', 'pending'])
            ->orderByDesc('created_at')
            ->take(8)
            ->get();

        if ($requests->isEmpty()) {
            $this->sendMessage($chatId, "🌟 **لا توجد أي طلبات خدمات طلابية معلقة بانتظار الشؤون حالياً.**");
            return;
        }

        $this->sendMessage($chatId, "📑 **الخدمات والطلبات الطلابية المعلقة ({$requests->count()}):**");

        foreach ($requests as $req) {
            $student = $req->student;
            $stUser = $student->user ?? null;
            $stName = $stUser->full_name ?? 'طالب';
            $code = $student && $student->student_code ? " (`{$student->student_code}`)" : "";
            $program = $student->program->name ?? 'عام';

            $typeLabel = match($req->type) {
                'device_reset' => '📱 فك قفل وتصفير جهاز',
                'face_photo'   => '📷 تحديث صورة بصمة الوجه',
                'transcript'   => '📜 طلب كشف درجات وسجل أكاديمي',
                'enrollment'   => '📄 طلب وثيقة دوام وتسجيل',
                default        => '📑 ' . ($req->type ?? 'طلب خدمة طلابية'),
            };

            $details = $req->formatted_details ?: ($req->details ?: 'لا توجد تفاصيل إضافية');

            $msg = "🎓 **الطالب:** {$stName}{$code}\n";
            $msg .= "🏛️ **التخصص:** {$program}\n";
            $msg .= "📌 **نوع الخدمة:** {$typeLabel}\n";
            $msg .= "📝 **التفاصيل:** {$details}\n";
            $msg .= "🕒 **تاريخ التقديم:** `" . $req->created_at->format('Y-m-d') . "`\n";

            $keyboard = [
                'inline_keyboard' => [
                    [
                        ['text' => '✅ موافقة واعتماد', 'callback_data' => "affairs_approve_req_{$req->id}"],
                        ['text' => '❌ رفض الطلب', 'callback_data' => "affairs_reject_req_{$req->id}"],
                    ]
                ]
            ];

            $this->sendMessage($chatId, $msg, null, $keyboard);
        }
    }

    private function handleAffairsApproveStudentReq(User $user, $chatId, int $reqId)
    {
        $studentReq = StudentRequest::with('student.user')->find($reqId);
        if (!$studentReq) {
            $this->sendMessage($chatId, "❌ الطلب غير موجود.");
            return;
        }

        $student = $studentReq->student;
        $stUser = $student->user ?? null;

        if ($studentReq->type === 'device_reset') {
            $studentReq->affairs_decision = 'approved';
            $studentReq->affairs_notes = 'تم فك قفل الجهاز وتصفيره بنجاح بواسطة موظف الشؤون عبر بوت تليجرام.';
            $studentReq->status = 'approved';
            $studentReq->save();

            if ($student) {
                $student->update([
                    'device_id'        => null,
                    'is_device_locked' => 0,
                ]);
                DB::table('personal_access_tokens')->where('tokenable_id', $student->user_id)->delete();
            }

            if ($stUser) {
                $title = 'تم فك قفل الجهاز بنجاح 📱';
                $message = 'وافقت شؤون الطلاب على طلب فك قفل الجهاز الخاص بك. تم تصفير القفل، يمكنك الآن تسجيل الدخول مباشرة من جهازك الجديد.';

                Notification::create([
                    'user_id'    => $stUser->user_id,
                    'sender_id'  => $user->user_id,
                    'title'      => $title,
                    'message'    => $message,
                    'type'       => 'academic',
                    'category'   => 'administrative',
                    'related_id' => $reqId,
                    'is_read'    => false,
                ]);

                FcmService::sendToUser($stUser->user_id, $title, $message, ['type' => 'academic']);

                if ($stUser->telegram_chat_id) {
                    $this->sendMessage(
                        $stUser->telegram_chat_id,
                        "📱 **إشعار من شؤون الطلاب** 🏢\n\nتمت **الموافقة على فك قفل جهازك وتصفيره**. يمكنك الآن تسجيل الدخول من جهازك الجديد فوراً!"
                    );
                }
            }

            $this->sendMessage($chatId, "✅ **تمت الموافقة وتصفير قفل الجهاز للطالب بنجاح!** 📱");
            return;
        }

        // باقي الطلبات تنتقل لرئيس القسم
        $studentReq->affairs_decision = 'approved';
        $studentReq->affairs_notes = 'موافقة مبدئية معتمدة من شؤون الطلاب عبر بوت تليجرام.';
        $studentReq->status = 'pending_hod';
        $studentReq->save();

        if ($stUser) {
            $title = 'موافقة مبدئية على طلبك 📑';
            $message = "قامت شؤون الطلاب بالموافقة المبدئية على طلبك (#{$reqId})، وتم تحويل الطلب إلى رئيس القسم للمتابعة.";

            Notification::create([
                'user_id'    => $stUser->user_id,
                'sender_id'  => $user->user_id,
                'title'      => $title,
                'message'    => $message,
                'type'       => 'student_service',
                'category'   => 'administrative',
                'related_id' => $reqId,
                'is_read'    => false,
            ]);

            FcmService::sendToUser($stUser->user_id, $title, $message, ['type' => 'student_service']);

            if ($stUser->telegram_chat_id) {
                $this->sendMessage(
                    $stUser->telegram_chat_id,
                    "📑 **إشعار من شؤون الطلاب** 🏢\n\nتمت **الموافقة المبدئية** على طلبك (#{$reqId}) وتحويله لرئيس القسم للاعتماد الأكاديمي."
                );
            }
        }

        $this->sendMessage($chatId, "✅ **تم اعتماد رأي الشؤون بالموافقة وتحويل الطلب لرئيس القسم بنجاح.**");
    }

    private function handleAffairsRejectStudentReq(User $user, $chatId, int $reqId)
    {
        $studentReq = StudentRequest::with('student.user')->find($reqId);
        if (!$studentReq) {
            $this->sendMessage($chatId, "❌ الطلب غير موجود.");
            return;
        }

        $studentReq->affairs_decision = 'rejected';
        $studentReq->affairs_notes = 'تم رفض الطلب من قِبل موظف شؤون الطلاب عبر بوت تليجرام.';
        $studentReq->status = 'rejected';
        $studentReq->save();

        $stUser = $studentReq->student->user ?? null;
        if ($stUser) {
            $title = 'تحديث حول طلب الخدمة الطلابية';
            $message = "نعتذر، تم رفض طلبك (#{$reqId}) من قِبل إدارة شؤون الطلاب.";

            Notification::create([
                'user_id'    => $stUser->user_id,
                'sender_id'  => $user->user_id,
                'title'      => $title,
                'message'    => $message,
                'type'       => 'student_service',
                'category'   => 'administrative',
                'related_id' => $reqId,
                'is_read'    => false,
            ]);

            FcmService::sendToUser($stUser->user_id, $title, $message, ['type' => 'student_service']);

            if ($stUser->telegram_chat_id) {
                $this->sendMessage(
                    $stUser->telegram_chat_id,
                    "⚠️ **إشعار من شؤون الطلاب** 🏢\n\nنعتذر، تم **رفض طلبك** (#{$reqId}) من قِبل إدارة شؤون الطلاب."
                );
            }
        }

        $this->sendMessage($chatId, "🛑 **تم رفض الطلب وإشعار الطالب.**");
    }

    private function handleAffairsStudentSearchStart(User $user, $chatId)
    {
        Cache::put("telegram_state_{$chatId}", 'awaiting_affairs_student_search', 1800);
        $this->sendMessage(
            $chatId,
            "🔍 **استعلام وسجل أكاديمي لطالب** 🎓\n\nيرجى إرسال **اسم الطالب / الرقم الجامعي / كود الطالب / رقم الهاتف** للبحث الفوري:"
        );
    }

    private function handleAffairsStudentSearchInput(User $user, $chatId, string $query)
    {
        $q = trim($query);
        if (empty($q)) {
            $this->sendMessage($chatId, "يرجى كتابة نص للبحث.");
            return;
        }

        $students = Student::with(['user', 'program.department'])
            ->where(function($builder) use ($q) {
                $builder->where('student_code', 'LIKE', "%{$q}%")
                    ->orWhereHas('user', function($uq) use ($q) {
                        $uq->where('full_name', 'LIKE', "%{$q}%")
                           ->orWhere('university_id', 'LIKE', "%{$q}%")
                           ->orWhere('phone', 'LIKE', "%{$q}%")
                           ->orWhere('email', 'LIKE', "%{$q}%");
                    });
            })
            ->take(5)
            ->get();

        if ($students->isEmpty()) {
            $this->sendMessage($chatId, "❌ لم يتم العثور على أي طالب يطابق البحث: `{$q}`\n\nيمكنك البحث من جديد بكتابة اسم أو رقم جامعي آخر.");
            return;
        }

        Cache::forget("telegram_state_{$chatId}");

        $this->sendMessage($chatId, "🎯 **نتائج البحث عن الطالب ({$students->count()}):**");

        foreach ($students as $s) {
            $u = $s->user;
            if (!$u) continue;

            $prog = $s->program->name ?? $u->department ?? 'عام';
            $level = $s->level ?? $u->academic_year ?? 'السنة الأولى';
            $code = $s->student_code ?? $u->university_id ?? '-';
            $phone = $u->phone ?? 'غير متوفر';
            $email = $u->email ?? 'غير متوفر';

            // حالة قفل الجهاز
            $lockStatus = $s->is_device_locked ? "🔒 مقيد على جهاز (`{$s->device_id}`)" : "🔓 متاح وغير مقيد";

            // عدد المقررات المسجلة
            $coursesCount = DB::table('enrollments')->where('student_id', $s->student_id)->where('status', 'active')->count();

            // نسبة الحضور
            $totalSessions = DB::table('attendances')->where('student_id', $s->student_id)->count();
            $presentSessions = DB::table('attendances')->where('student_id', $s->student_id)->where('status', 'present')->count();
            $attendanceRate = $totalSessions > 0 ? round(($presentSessions / $totalSessions) * 100, 1) . '%' : 'لا توجد جلسات';

            // بيانات ولي الأمر
            $parentInfo = 'غير مرتبط';
            $parentUser = DB::table('parent_students')
                ->join('users', 'parent_students.parent_id', '=', 'users.user_id')
                ->where('parent_students.student_id', $u->user_id)
                ->select('users.full_name', 'users.phone')
                ->first();

            if ($parentUser) {
                $parentInfo = "{$parentUser->full_name} (`{$parentUser->phone}`)";
            }

            $card = "🎓 **بيانات الطالب الأكاديمية:**\n";
            $card .= "👤 **الاسم الكامل:** {$u->full_name}\n";
            $card .= "🆔 **الرقم الجامعي / الكود:** `{$code}`\n";
            $card .= "🏛️ **التخصص والبرنامج:** {$prog}\n";
            $card .= "📚 **السنة الدراسية:** {$level}\n";
            $card .= "📞 **رقم الهاتف:** `{$phone}`\n";
            $card .= "📧 **البريد الإلكتروني:** `{$email}`\n";
            $card .= "─────────────\n";
            $card .= "📱 **حالة الجهاز:** {$lockStatus}\n";
            $card .= "📊 **نسبة الحضور الإجمالية:** {$attendanceRate}\n";
            $card .= "📖 **المقررات المسجلة:** `{$coursesCount}` مادة\n";
            $card .= "👨‍👩‍👧 **ولي الأمر:** {$parentInfo}\n";

            $this->sendMessage($chatId, $card);
        }
    }

    private function handleAffairsBroadcastStart(User $user, $chatId)
    {
        Cache::put("telegram_state_{$chatId}", 'awaiting_affairs_ann_title', 1800);
        $this->sendMessage($chatId, "📢 **نشر إعلان وتعميم رسمي عام** 🏢\n\nيرجى كتابة **عنوان الإعلان**:");
    }

    private function handleAffairsAnnTitleInput($chatId, $text)
    {
        $title = trim($text);
        Cache::put("telegram_affairs_ann_title_{$chatId}", $title, 1800);
        Cache::put("telegram_state_{$chatId}", 'awaiting_affairs_ann_content', 1800);

        $this->sendMessage($chatId, "📝 **نص وتفاصيل الإعلان**\n\nالعنوان: **{$title}**\n\nالآن يرجى كتابة نص وتفاصيل الإعلان بالكامل:");
    }

    private function handleAffairsAnnContentInput($chatId, $text)
    {
        $content = trim($text);
        Cache::put("telegram_affairs_ann_content_{$chatId}", $content, 1800);

        $keyboard = [
            'inline_keyboard' => [
                [
                    ['text' => '👥 الجميع (طلاب ومعلمون وأولياء أمور)', 'callback_data' => 'affairs_ann_target_all'],
                ],
                [
                    ['text' => '🎓 الطلاب فقط', 'callback_data' => 'affairs_ann_target_students'],
                    ['text' => '👨‍🏫 المعلمون فقط', 'callback_data' => 'affairs_ann_target_teachers'],
                ],
                [
                    ['text' => '👨‍👩‍👧 أولياء الأمور فقط', 'callback_data' => 'affairs_ann_target_parents'],
                ]
            ]
        ];

        $title = Cache::get("telegram_affairs_ann_title_{$chatId}", 'إعلان رسمي');
        $this->sendMessage(
            $chatId,
            "🎯 **تحديد الجمهور المستهدف**\n\n📌 **العنوان:** {$title}\n📝 **المحتوى:**\n\"{$content}\"\n\nاختر الفئة المستهدفة لنشر الإعلان وتعميمه فورياً:",
            null,
            $keyboard
        );
    }

    private function handleAffairsAnnPublish(User $user, $chatId, string $target)
    {
        $title = Cache::get("telegram_affairs_ann_title_{$chatId}", 'إعلان من شؤون الطلاب');
        $content = Cache::get("telegram_affairs_ann_content_{$chatId}", '');

        $announcement = Announcement::create([
            'user_id'         => $user->user_id,
            'title'           => $title,
            'content'         => $content,
            'type'            => 'general',
            'target_audience' => $target,
            'department_id'   => null,
        ]);

        Cache::forget("telegram_state_{$chatId}");
        Cache::forget("telegram_affairs_ann_title_{$chatId}");
        Cache::forget("telegram_affairs_ann_content_{$chatId}");

        $roleIds = match($target) {
            'students' => [3],
            'teachers' => [2],
            'parents'  => [4],
            default    => [2, 3, 4],
        };

        $recipients = User::whereIn('role_id', $roleIds)
            ->where('status', 'active')
            ->get();

        $notifTitle = "📢 إعلان رسمي من إدارة شؤون الطلاب";

        foreach ($recipients as $rUser) {
            Notification::create([
                'user_id'    => $rUser->user_id,
                'sender_id'  => $user->user_id,
                'title'      => $notifTitle,
                'message'    => $title,
                'type'       => 'announcement',
                'category'   => 'administrative',
                'related_id' => $announcement->announcement_id ?? $announcement->id,
                'is_read'    => false,
            ]);

            FcmService::sendToUser($rUser->user_id, $notifTitle, $title, ['type' => 'announcement']);

            if ($rUser->telegram_chat_id) {
                $this->sendMessage(
                    $rUser->telegram_chat_id,
                    "📢 **إعلان رسمي من إدارة شؤون الطلاب** 🏢\n\n📌 **العنوان:** {$title}\n📝 **التفاصيل:**\n{$content}\n\n👤 **المرسل:** {$user->full_name}"
                );
            }
        }

        $targetLabel = match($target) {
            'students' => 'جميع الطلاب',
            'teachers' => 'كادر المعلمين والمدربين',
            'parents'  => 'أولياء الأمور',
            default    => 'الجميع (طلاب ومعلمون وأولياء أمور)',
        };

        $this->sendMessage(
            $chatId,
            "✅ **تم نشر وتعميم الإعلان بنجاح!** 📢\n\n📌 **العنوان:** {$title}\n🎯 **الجمهور المستهدف:** {$targetLabel}\n👥 **عدد المستلمين:** `{$recipients->count()}` مستخدم\n\n📨 تم إرسال إشعارات التطبيق والرسائل الفورية للجميع."
        );
    }
}
