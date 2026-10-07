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
 * Telegram bot: head of department services
 * (moved as-is from TelegramBotHandler; methods keep their names and behaviour)
 */
trait HodHandlers
{
    // ==========================================
    // Head of Department (HOD) Services Handlers
    // ==========================================

    private function sendHodMainMenu($chatId, $headerText = null)
    {
        $keyboard = [
            'keyboard' => [
                [['text' => '🏛️ لوحة القسم والإحصائيات'], ['text' => '👨‍🏫 كادر القسم التدريسي']],
                [['text' => '📚 مقررات وشعب القسم'], ['text' => '✈️ إجازات المعلمين']],
                [['text' => '🎓 الطلبات والخدمات الطلابية'], ['text' => '🤝 مواعيد أولياء الأمور']],
                [['text' => '📢 نشر إعلان للقسم'], ['text' => '🚪 تسجيل خروج']]
            ],
            'resize_keyboard' => true,
            'one_time_keyboard' => false
        ];

        $fullText = $headerText ?: "🏛️ **لوحة تحكم رئيس القسم**\nاختر الخدمة المطلوبة من القائمة أدناه:";

        $this->sendMessage($chatId, $fullText, $keyboard);
    }

    private function getHodDepartmentInfo(User $user)
    {
        $deptName = $user->department;
        $headRecord = DB::table('heads')->where('user_id', $user->user_id)->first();
        $deptId = $headRecord?->department_id;

        if (!$deptId && $deptName) {
            $deptId = DB::table('departments')
                ->where('name', 'LIKE', '%' . $deptName . '%')
                ->value('department_id');
        }

        if (!$deptName && $deptId) {
            $deptName = DB::table('departments')->where('department_id', $deptId)->value('name');
        }

        return [
            'id'   => $deptId,
            'name' => $deptName ?: 'القسم الأكاديمي',
        ];
    }

    private function handleHodOverview(User $user, $chatId)
    {
        $deptInfo = $this->getHodDepartmentInfo($user);
        $deptName = $deptInfo['name'];
        $deptId = $deptInfo['id'];

        // عدد معلمي القسم
        $teachersCount = User::where('role_id', 2)
            ->where(function($q) use ($deptName) {
                $q->where('department', 'LIKE', "%{$deptName}%");
            })->count();

        if ($teachersCount === 0) {
            $teachersCount = User::where('role_id', 2)->count();
        }

        // عدد طلاب القسم
        $studentsCount = User::where('role_id', 3)
            ->where(function($q) use ($deptName) {
                $q->where('department', 'LIKE', "%{$deptName}%");
            })->count();

        if ($studentsCount === 0) {
            $studentsCount = User::where('role_id', 3)->count();
        }

        // عدد المقررات
        $coursesQuery = DB::table('courses');
        if ($deptId) {
            $coursesQuery->whereExists(function($q) use ($deptId) {
                $q->select(DB::raw(1))
                  ->from('course_program')
                  ->join('programs', 'course_program.program_id', '=', 'programs.id')
                  ->whereColumn('courses.course_id', 'course_program.course_id')
                  ->where('programs.department_id', $deptId);
            });
        }
        $coursesCount = $coursesQuery->count();
        if ($coursesCount === 0) {
            $coursesCount = Course::count();
        }

        // طلبات الإجازات المعلقة للمعلمين
        $pendingLeavesCount = DB::table('leave_requests')
            ->whereIn('status', ['pending', 'pending_hod'])
            ->count();

        // الطلبات الطلابية المعلقة بانتظار رئيس القسم
        $pendingStudentReqsCount = DB::table('student_requests')
            ->where('status', 'pending_hod')
            ->count();

        // المواعيد القادمة
        $upcomingMeetingsCount = DB::table('parent_meeting_requests')
            ->whereIn('status', ['pending', 'approved'])
            ->count();

        $msg = "🏛️ **لوحة معلومات القسم الأكاديمي**\n\n"
            . "🏢 **القسم:** {$deptName}\n"
            . "👤 **رئيس القسم:** الأستاذ/ة {$user->full_name}\n"
            . "─────────────\n"
            . "👨‍🏫 **الهيئة التدريسية:** `{$teachersCount}` مدرّب\n"
            . "🎓 **إجمالي الطلاب:** `{$studentsCount}` طالب\n"
            . "📚 **المقررات المعتمدة:** `{$coursesCount}` مقرر\n"
            . "─────────────\n"
            . "⏳ **إجازات معلمين معلقة:** `{$pendingLeavesCount}` طلب\n"
            . "⏳ **طلبات طلابية معلقة:** `{$pendingStudentReqsCount}` طلب\n"
            . "🤝 **مواعيد أولياء الأمور:** `{$upcomingMeetingsCount}` موعد\n\n"
            . "👇 يمكنك استخدام الأزرار أدناه للإدارة السريعة:";

        $keyboard = [
            'inline_keyboard' => [
                [
                    ['text' => '👨‍🏫 كادر المعلمين', 'callback_data' => 'hod_action_teachers'],
                    ['text' => '📚 مقررات القسم', 'callback_data' => 'hod_action_courses'],
                ],
                [
                    ['text' => '✈️ إجازات المعلمين (' . $pendingLeavesCount . ')', 'callback_data' => 'hod_action_leaves'],
                    ['text' => '🎓 طلبات الطلاب (' . $pendingStudentReqsCount . ')', 'callback_data' => 'hod_action_requests'],
                ],
                [
                    ['text' => '📢 نشر إعلان للقسم الآن', 'callback_data' => 'hod_action_broadcast'],
                ]
            ]
        ];

        $this->sendMessage($chatId, $msg, null, $keyboard);
    }

    private function handleHodTeachers(User $user, $chatId)
    {
        $deptInfo = $this->getHodDepartmentInfo($user);
        $deptName = $deptInfo['name'];

        $teachers = Teacher::with(['user', 'courses'])
            ->whereHas('user', function($q) use ($deptName) {
                $q->where('department', 'LIKE', "%{$deptName}%");
            })
            ->get();

        if ($teachers->isEmpty()) {
            $teachers = Teacher::with(['user', 'courses'])->take(15)->get();
        }

        if ($teachers->isEmpty()) {
            $this->sendMessage($chatId, "👨‍🏫 لا يوجد معلمون مسجلون في القسم حالياً.");
            return;
        }

        $msg = "👨‍🏫 **كادر القسم التدريسي ({$teachers->count()} مدرّب):**\n\n";
        $keyboard = ['inline_keyboard' => []];

        foreach ($teachers as $idx => $t) {
            $tUser = $t->user;
            $name = $tUser->full_name ?? 'معلم';
            $coursesCount = $t->courses->count();
            $spec = $t->specialization ? " ({$t->specialization})" : '';
            $num = $idx + 1;

            $msg .= "{$num}. **{$name}**{$spec}\n";
            $msg .= "   📚 المقررات المسندة: `{$coursesCount}` مقرر | 📞 الهاتف: " . ($tUser->phone ?? '—') . "\n";
            $msg .= "─────────────\n";

            $keyboard['inline_keyboard'][] = [
                ['text' => "🔍 تفاصيل ومقررات: {$name}", 'callback_data' => "hod_teacher_detail_{$t->teacher_id}"]
            ];
        }

        $this->sendMessage($chatId, $msg, null, $keyboard);
    }

    private function handleHodTeacherDetail(User $user, $chatId, int $teacherId)
    {
        $teacher = Teacher::with(['user', 'courses.students'])->find($teacherId);
        if (!$teacher || !$teacher->user) {
            $this->sendMessage($chatId, "❌ ملف المعلم غير موجود.");
            return;
        }

        $tUser = $teacher->user;
        $name = $tUser->full_name;
        $spec = $teacher->specialization ?? 'عام';
        $email = $tUser->email ?? '—';
        $phone = $tUser->phone ?? '—';

        $msg = "👨‍🏫 **بطاقة المعلم التدريسية**\n\n"
            . "👤 **الاسم:** {$name}\n"
            . "🎯 **التخصص:** {$spec}\n"
            . "📧 **البريد:** `{$email}`\n"
            . "📞 **الهاتف:** `{$phone}`\n"
            . "🏢 **القسم:** " . ($tUser->department ?? 'القسم الأكاديمي') . "\n\n"
            . "📚 **المقررات المسندة للمدرب:**\n";

        if ($teacher->courses->isEmpty()) {
            $msg .= "🌟 لا توجد مقررات مسندة حالياً.\n";
        } else {
            foreach ($teacher->courses as $c) {
                $stCount = $c->students->count();
                $msg .= "  • 📘 **{$c->title}** (الطلاب: `{$stCount}` طالب)\n";
            }
        }

        $this->sendMessage($chatId, $msg);
    }

    private function handleHodCourses(User $user, $chatId)
    {
        $deptInfo = $this->getHodDepartmentInfo($user);
        $deptId = $deptInfo['id'];

        $query = Course::withCount('students')->with('teachers.user');
        if ($deptId) {
            $query->whereExists(function($q) use ($deptId) {
                $q->select(DB::raw(1))
                  ->from('course_program')
                  ->join('programs', 'course_program.program_id', '=', 'programs.id')
                  ->whereColumn('courses.course_id', 'course_program.course_id')
                  ->where('programs.department_id', $deptId);
            });
        }

        $courses = $query->orderBy('title')->get();
        if ($courses->isEmpty()) {
            $courses = Course::withCount('students')->with('teachers.user')->take(15)->get();
        }

        if ($courses->isEmpty()) {
            $this->sendMessage($chatId, "📚 لا توجد مقررات مسجلة في القسم.");
            return;
        }

        $msg = "📚 **مقررات وشعب القسم ({$courses->count()} مقرر):**\n\n";
        $keyboard = ['inline_keyboard' => []];

        foreach ($courses as $c) {
            $teacherNames = $c->teachers->pluck('user.full_name')->filter()->implode(', ') ?: 'غير محدد';
            $stCount = $c->students_count ?? 0;
            $code = $c->code ? "[{$c->code}] " : '';

            $msg .= "🔹 **{$code}{$c->title}**\n";
            $msg .= "   👨‍🏫 المدرس: {$teacherNames}\n";
            $msg .= "   👥 الطلاب: `{$stCount}` طالب | ⚖️ التثقيل: `{$c->weight}`\n";
            $msg .= "─────────────\n";

            $keyboard['inline_keyboard'][] = [
                ['text' => "📖 تفاصيل مقرر: {$c->title}", 'callback_data' => "hod_course_detail_{$c->course_id}"]
            ];
        }

        $this->sendMessage($chatId, $msg, null, $keyboard);
    }

    private function handleHodCourseDetail(User $user, $chatId, int $courseId)
    {
        $course = Course::with(['teachers.user', 'students.user'])->withCount(['lessons'])->find($courseId);
        if (!$course) {
            $this->sendMessage($chatId, "❌ المقرر غير موجود.");
            return;
        }

        $teachers = $course->teachers->pluck('user.full_name')->filter()->implode(', ') ?: 'غير مسند';
        $stCount = $course->students->count();

        // حساب نسبة الحضور العامة للمقرر
        $totalAtt = Attendance::whereHas('lesson', fn($q) => $q->where('course_id', $courseId))->count();
        $presentAtt = Attendance::whereHas('lesson', fn($q) => $q->where('course_id', $courseId))->where('status', 'present')->count();
        $rate = $totalAtt > 0 ? round(($presentAtt / $totalAtt) * 100, 1) : 100;

        $msg = "📖 **بطاقة المقرر الأكاديمي**\n\n"
            . "📘 **المقرر:** {$course->title}\n"
            . "🏷️ **الرمز:** `{$course->code}`\n"
            . "👨‍🏫 **المدرّس المسؤول:** {$teachers}\n"
            . "👥 **الطلاب المسجلون:** `{$stCount}` طالب\n"
            . "🎥 **المحاضرات المنجزة:** `{$course->lessons_count}` محاضرة\n"
            . "📊 **نسبة حضور الطلاب العامة:** `{$rate}%`\n\n";

        // الجداول الدراسية لهذا المقرر
        $schedules = Schedule::where('course_id', $courseId)->orderBy('start_time')->get();
        if ($schedules->isNotEmpty()) {
            $msg .= "📅 **مواعيد المحاضرات الأسبوعية:**\n";
            foreach ($schedules as $s) {
                $start = date('h:i A', strtotime($s->start_time));
                $end = date('h:i A', strtotime($s->end_time));
                $msg .= "  • {$s->day} | 🕒 {$start} - {$end} | 📍 {$s->room}\n";
            }
        }

        $this->sendMessage($chatId, $msg);
    }

    private function handleHodTeacherLeaves(User $user, $chatId)
    {
        $leaves = DB::table('leave_requests')
            ->leftJoin('users as u', 'leave_requests.student_id', '=', 'u.user_id')
            ->leftJoin('teachers', 'leave_requests.teacher_id', '=', 'teachers.teacher_id')
            ->leftJoin('users as tu', 'teachers.user_id', '=', 'tu.user_id')
            ->select(
                'leave_requests.*',
                DB::raw('COALESCE(tu.full_name, u.full_name, "مستخدم") as applicant_name'),
                DB::raw('COALESCE(tu.role_id, u.role_id, 2) as applicant_role')
            )
            ->whereIn('leave_requests.status', ['pending', 'pending_hod'])
            ->orderByDesc('leave_requests.created_at')
            ->take(10)
            ->get();

        if ($leaves->isEmpty()) {
            $this->sendMessage($chatId, "🌟 **لا توجد أي طلبات إجازة معلقة حالياً.**\nجميع طلبات الإجازات تمت معالجتها والبت فيها.");
            return;
        }

        $msg = "✈️ **طلبات الإجازات المعلقة ({$leaves->count()}):**\n\n";
        $keyboard = ['inline_keyboard' => []];

        foreach ($leaves as $idx => $l) {
            $typeStr = $l->type === 'hourly' ? "إجازة ساعية" : "إجازة يوم كامل";
            $date = date('Y-m-d', strtotime($l->date));
            $num = $idx + 1;

            $msg .= "{$num}. 👤 **{$l->applicant_name}**\n";
            $msg .= "   📌 النوع: {$typeStr} | 📅 التاريخ: `{$date}`\n";
            $msg .= "   📝 السبب: \"{$l->reason}\"\n";
            $msg .= "─────────────\n";

            $keyboard['inline_keyboard'][] = [
                ['text' => "✅ موافقة: {$l->applicant_name}", 'callback_data' => "hod_approve_leave_{$l->id}"],
                ['text' => "❌ رفض: {$l->applicant_name}", 'callback_data' => "hod_reject_leave_{$l->id}"],
            ];
        }

        $msg .= "\n👇 اضغط على الإجراء المناسب لكل طلب للبت فيه فوراً:";

        $this->sendMessage($chatId, $msg, null, $keyboard);
    }

    private function handleHodApproveLeave(User $user, $chatId, int $leaveId)
    {
        $leave = DB::table('leave_requests')->where('id', $leaveId)->first();
        if (!$leave) {
            $this->sendMessage($chatId, "❌ الطلب غير موجود.");
            return;
        }

        DB::table('leave_requests')->where('id', $leaveId)->update([
            'status'     => 'approved',
            'updated_at' => now(),
        ]);

        // إشعار صاحب الطلب
        $applicantUserId = $leave->student_id;
        if (!$applicantUserId && $leave->teacher_id) {
            $applicantUserId = DB::table('teachers')->where('teacher_id', $leave->teacher_id)->value('user_id');
        }

        if ($applicantUserId) {
            $appUser = User::find($applicantUserId);
            $notifTitle = "تمت الموافقة على طلب الإجازة";
            $notifMsg = "وافق رئيس القسم ({$user->full_name}) على طلب إجازتك بتاريخ ({$leave->date}).";

            Notification::create([
                'user_id'    => $applicantUserId,
                'sender_id'  => $user->user_id,
                'title'      => $notifTitle,
                'message'    => $notifMsg,
                'type'       => 'leave_approved',
                'category'   => 'administrative',
                'related_id' => $leaveId,
                'is_read'    => false,
            ]);

            FcmService::sendToUser($applicantUserId, $notifTitle, $notifMsg, ['type' => 'leave_approved']);

            if ($appUser && $appUser->telegram_chat_id) {
                $this->sendMessage($appUser->telegram_chat_id, "✅ **تمت الموافقة على طلب إجازتك!** ✈️\n\nوافق رئيس القسم ({$user->full_name}) على طلب إجازتك بتاريخ `{$leave->date}`.");
            }
        }

        $this->sendMessage($chatId, "✅ **تمت الموافقة على طلب الإجازة بنجاح!**\nتم تحديث حالة الطلب وإشعار صاحب العلاقة فوراً.");
    }

    private function handleHodRejectLeave(User $user, $chatId, int $leaveId)
    {
        $leave = DB::table('leave_requests')->where('id', $leaveId)->first();
        if (!$leave) {
            $this->sendMessage($chatId, "❌ الطلب غير موجود.");
            return;
        }

        DB::table('leave_requests')->where('id', $leaveId)->update([
            'status'     => 'rejected',
            'updated_at' => now(),
        ]);

        // إشعار صاحب الطلب
        $applicantUserId = $leave->student_id;
        if (!$applicantUserId && $leave->teacher_id) {
            $applicantUserId = DB::table('teachers')->where('teacher_id', $leave->teacher_id)->value('user_id');
        }

        if ($applicantUserId) {
            $appUser = User::find($applicantUserId);
            $notifTitle = "تم رفض طلب الإجازة";
            $notifMsg = "تم رفض طلب إجازتك بتاريخ ({$leave->date}) من قبل رئيس القسم.";

            Notification::create([
                'user_id'    => $applicantUserId,
                'sender_id'  => $user->user_id,
                'title'      => $notifTitle,
                'message'    => $notifMsg,
                'type'       => 'leave_rejected',
                'category'   => 'administrative',
                'related_id' => $leaveId,
                'is_read'    => false,
            ]);

            FcmService::sendToUser($applicantUserId, $notifTitle, $notifMsg, ['type' => 'leave_rejected']);

            if ($appUser && $appUser->telegram_chat_id) {
                $this->sendMessage($appUser->telegram_chat_id, "❌ **تم رفض طلب الإجازة**\n\nنعتذر، لم تتم الموافقة على طلب إجازتك بتاريخ `{$leave->date}` من قبل رئيس القسم.");
            }
        }

        $this->sendMessage($chatId, "🛑 **تم رفض طلب الإجازة.**\nتم تحديث حالة الطلب وإشعار صاحب العلاقة.");
    }

    private function handleHodStudentRequests(User $user, $chatId)
    {
        $deptInfo = $this->getHodDepartmentInfo($user);
        $deptName = $deptInfo['name'];
        $deptId = $deptInfo['id'];

        $query = StudentRequest::with(['student.user', 'student.program.department'])
            ->whereIn('status', ['pending_hod', 'pending_admin']);

        if ($deptName || $deptId) {
            $query->whereHas('student', function ($sq) use ($deptName, $deptId) {
                $sq->where(function ($w) use ($deptName, $deptId) {
                    if ($deptName) {
                        $w->whereHas('user', function ($uq) use ($deptName) {
                            $uq->where('department', 'LIKE', "%{$deptName}%");
                        });
                    }
                });
            });
        }

        $requests = $query->orderByDesc('created_at')->take(10)->get();

        if ($requests->isEmpty()) {
            $this->sendMessage($chatId, "🌟 **لا توجد طلبات طلابية معلقة لقسمك حالياً.**");
            return;
        }

        $msg = "🎓 **الطلبات والخدمات الطلابية المعلقة ({$requests->count()}):**\n\n";
        $keyboard = ['inline_keyboard' => []];

        foreach ($requests as $idx => $r) {
            $stName = $r->student->user->full_name ?? 'طالب';
            $uid = $r->student->user->university_id ?? $r->student->student_code ?? '—';
            $type = $r->type ?? 'طلب عام';
            $details = $r->formatted_details ?: ($r->details ?? 'بدون تفاصيل إضافية');
            $num = $idx + 1;

            $msg .= "{$num}. 👤 **{$stName}** (`{$uid}`)\n";
            $msg .= "   📌 نوع الطلب: **{$type}**\n";
            $msg .= "   📝 التفاصيل: \"{$details}\"\n";
            $msg .= "─────────────\n";

            $keyboard['inline_keyboard'][] = [
                ['text' => "✅ موافقة وتوصية: {$stName}", 'callback_data' => "hod_approve_req_{$r->id}"],
                ['text' => "❌ عدم الموافقة: {$stName}", 'callback_data' => "hod_reject_req_{$r->id}"],
            ];
            $keyboard['inline_keyboard'][] = [
                ['text' => "📝 إضافة ملاحظات وتوجيهات: {$stName}", 'callback_data' => "hod_notes_req_{$r->id}"]
            ];
        }

        $this->sendMessage($chatId, $msg, null, $keyboard);
    }

    private function handleHodApproveStudentReq(User $user, $chatId, int $reqId)
    {
        $studentReq = StudentRequest::with('student.user')->find($reqId);
        if (!$studentReq) {
            $this->sendMessage($chatId, "❌ الطلب غير موجود.");
            return;
        }

        $studentReq->hod_decision = 'approved';
        $studentReq->status = 'pending_admin';
        $studentReq->save();

        $stUser = $studentReq->student->user ?? null;
        if ($stUser) {
            $notifTitle = "تحديث من رئيس القسم على طلبك";
            $notifMsg = "أبدى رئيس القسم ({$user->full_name}) الموافقة والتوصية الإيجابية على طلبك (#{$reqId})، وتم تحويله للإدارة للقرار النهائي.";

            Notification::create([
                'user_id'    => $stUser->user_id,
                'sender_id'  => $user->user_id,
                'title'      => $notifTitle,
                'message'    => $notifMsg,
                'type'       => 'student_service',
                'category'   => 'administrative',
                'related_id' => $reqId,
                'is_read'    => false,
            ]);

            FcmService::sendToUser($stUser->user_id, $notifTitle, $notifMsg, ['type' => 'student_service']);

            if ($stUser->telegram_chat_id) {
                $this->sendMessage($stUser->telegram_chat_id, "🎓 **تحديث على طلبك الطلابي (#{$reqId})**\n\nأبدى رئيس القسم ({$user->full_name}) **الموافقة والتوصية الإيجابية**، وتم تحويل الطلب لإدارة المعهد لاتخاذ القرار النهائي.");
            }
        }

        $this->sendMessage($chatId, "✅ **تم اعتماد التوصية الإيجابية وتحويل الطلب للإدارة بنجاح!**");
    }

    private function handleHodRejectStudentReq(User $user, $chatId, int $reqId)
    {
        $studentReq = StudentRequest::with('student.user')->find($reqId);
        if (!$studentReq) {
            $this->sendMessage($chatId, "❌ الطلب غير موجود.");
            return;
        }

        $studentReq->hod_decision = 'rejected';
        $studentReq->status = 'pending_admin';
        $studentReq->save();

        $stUser = $studentReq->student->user ?? null;
        if ($stUser) {
            $notifTitle = "تحديث من رئيس القسم على طلبك";
            $notifMsg = "أبدى رئيس القسم التوصية بعدم القبول على طلبك (#{$reqId}) وتم تحويله للإدارة.";

            Notification::create([
                'user_id'    => $stUser->user_id,
                'sender_id'  => $user->user_id,
                'title'      => $notifTitle,
                'message'    => $notifMsg,
                'type'       => 'student_service',
                'category'   => 'administrative',
                'related_id' => $reqId,
                'is_read'    => false,
            ]);

            FcmService::sendToUser($stUser->user_id, $notifTitle, $notifMsg, ['type' => 'student_service']);
        }

        $this->sendMessage($chatId, "🛑 **تم تدوين التوصية بالرفض وتحويل الطلب للإدارة.**");
    }

    private function handleHodStartNotesStudentReq(User $user, $chatId, int $reqId)
    {
        $studentReq = StudentRequest::with('student.user')->find($reqId);
        if (!$studentReq) {
            $this->sendMessage($chatId, "❌ الطلب غير موجود.");
            return;
        }

        $stName = $studentReq->student->user->full_name ?? 'طالب';
        Cache::put("telegram_state_{$chatId}", "awaiting_hod_req_notes_{$reqId}", 1800);

        $this->sendMessage($chatId, "✍️ **تدوين توجيهات وملاحظات رئيس القسم**\n\nيرجى كتابة ملاحظاتك وتوجيهاتك لطلب الطالب ({$stName}):");
    }

    private function handleHodReqNotesInput(User $user, $chatId, string $text, string $state)
    {
        $reqId = (int)str_replace('awaiting_hod_req_notes_', '', $state);
        $studentReq = StudentRequest::with('student.user')->find($reqId);
        if (!$studentReq) {
            $this->sendMessage($chatId, "❌ الطلب غير موجود.");
            Cache::forget("telegram_state_{$chatId}");
            return;
        }

        $notes = trim($text);
        $studentReq->hod_notes = $notes;
        $studentReq->save();

        Cache::forget("telegram_state_{$chatId}");

        $this->sendMessage($chatId, "✅ **تم تدوين وحفظ ملاحظاتك على الطلب بنجاح!**\n\"{$notes}\"");
    }

    private function handleHodAppointments(User $user, $chatId)
    {
        $deptInfo = $this->getHodDepartmentInfo($user);
        $deptName = $deptInfo['name'];

        $meetings = DB::table('parent_meeting_requests')
            ->join('users as parent_users', 'parent_meeting_requests.parent_id', '=', 'parent_users.user_id')
            ->join('students', 'parent_meeting_requests.student_id', '=', 'students.student_id')
            ->join('users as student_users', 'students.user_id', '=', 'student_users.user_id')
            ->where(function($q) use ($deptName) {
                $q->where('student_users.department', 'LIKE', "%{$deptName}%");
            })
            ->select(
                'parent_meeting_requests.*',
                'parent_users.full_name as parent_name',
                'parent_users.phone as parent_phone',
                'student_users.full_name as student_name'
            )
            ->orderByDesc('parent_meeting_requests.meeting_date')
            ->take(10)
            ->get();

        if ($meetings->isEmpty()) {
            $this->sendMessage($chatId, "🌟 **لا توجد أي مواعيد محجوزة مع أولياء الأمور حالياً.**");
            return;
        }

        $msg = "🤝 **مواعيد ولقاءات أولياء الأمور ({$meetings->count()}):**\n\n";

        foreach ($meetings as $idx => $m) {
            $date = date('Y-m-d h:i A', strtotime($m->meeting_date));
            $stStatus = match($m->status) {
                'approved' => '✅ مؤكد',
                'rejected' => '❌ ملغى',
                default    => '⏳ قيد التدقيق',
            };
            $num = $idx + 1;

            $msg .= "{$num}. 👨‍👦 **ولي أمر:** {$m->parent_name} (طالب: {$m->student_name})\n";
            $msg .= "   📅 الموعد: `{$date}` | الحالة: {$stStatus}\n";
            $msg .= "   📝 موضوع اللقاء: \"{$m->subject}\"\n";
            $msg .= "   📞 هاتف ولي الأمر: `{$m->parent_phone}`\n";
            $msg .= "─────────────\n";
        }

        $this->sendMessage($chatId, $msg);
    }

    private function handleHodBroadcastStart(User $user, $chatId)
    {
        Cache::put("telegram_state_{$chatId}", 'awaiting_hod_ann_title', 1800);
        $this->sendMessage($chatId, "📢 **نشر إعلان أكاديمي للقسم** 🏛️\n\nيرجى كتابة **عنوان الإعلان**:");
    }

    private function handleHodAnnTitleInput($chatId, $text)
    {
        $title = trim($text);
        Cache::put("telegram_hod_ann_title_{$chatId}", $title, 1800);
        Cache::put("telegram_state_{$chatId}", 'awaiting_hod_ann_content', 1800);

        $this->sendMessage($chatId, "📝 **نص وتفاصيل الإعلان**\n\nالعنوان: **{$title}**\n\nالآن يرجى كتابة نص وتفاصيل الإعلان بالكامل:");
    }

    private function handleHodAnnContentInput($chatId, $text)
    {
        $content = trim($text);
        Cache::put("telegram_hod_ann_content_{$chatId}", $content, 1800);

        $keyboard = [
            'inline_keyboard' => [
                [
                    ['text' => '👥 الجميع (طلاب ومعلمون)', 'callback_data' => 'hod_ann_target_all'],
                ],
                [
                    ['text' => '🎓 طلاب القسم فقط', 'callback_data' => 'hod_ann_target_students'],
                    ['text' => '👨‍🏫 معلمو القسم فقط', 'callback_data' => 'hod_ann_target_teachers'],
                ]
            ]
        ];

        $title = Cache::get("telegram_hod_ann_title_{$chatId}", 'إعلان');
        $this->sendMessage(
            $chatId,
            "🎯 **تحديد الجمهور المستهدف**\n\n📌 **العنوان:** {$title}\n📝 **المحتوى:**\n\"{$content}\"\n\nاختر الفئة المستهدفة لنشر الإعلان وإرساله فوراً:",
            null,
            $keyboard
        );
    }

    private function handleHodAnnPublish(User $user, $chatId, string $target)
    {
        $title = Cache::get("telegram_hod_ann_title_{$chatId}", 'إعلان من رئيس القسم');
        $content = Cache::get("telegram_hod_ann_content_{$chatId}", '');
        $deptInfo = $this->getHodDepartmentInfo($user);
        $deptId = $deptInfo['id'];
        $deptName = $deptInfo['name'];

        $announcement = Announcement::create([
            'user_id'         => $user->user_id,
            'title'           => $title,
            'content'         => $content,
            'type'            => 'general',
            'target_audience' => $target,
            'department_id'   => $deptId,
        ]);

        Cache::forget("telegram_state_{$chatId}");
        Cache::forget("telegram_hod_ann_title_{$chatId}");
        Cache::forget("telegram_hod_ann_content_{$chatId}");

        $roleIds = match($target) {
            'students' => [3],
            'teachers' => [2],
            default    => [2, 3],
        };

        $recipientsQuery = User::whereIn('role_id', $roleIds)
            ->where('status', 'active');

        if ($deptName) {
            $recipientsQuery->where('department', 'LIKE', "%{$deptName}%");
        }

        $recipients = $recipientsQuery->get();
        if ($recipients->isEmpty()) {
            $recipients = User::whereIn('role_id', $roleIds)->where('status', 'active')->get();
        }

        $notifTitle = "📢 إعلان من رئيس القسم ({$deptName})";
        $now = now();

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
                $this->sendMessage($rUser->telegram_chat_id, "📢 **إعلان أكاديمي رسمي من رئيس القسم** 🏛️\n\n📌 **العنوان:** {$title}\n📝 **التفاصيل:**\n{$content}\n\n👤 **المرسل:** الأستاذ/ة {$user->full_name}");
            }
        }

        $targetLabel = match($target) {
            'students' => 'طلاب القسم',
            'teachers' => 'كادر المعلمين',
            default    => 'الجميع (طلاب ومعلمون)',
        };

        $this->sendMessage(
            $chatId,
            "✅ **تم نشر وتعميم الإعلان بنجاح!** 📢\n\n📌 **العنوان:** {$title}\n🎯 **الجمهور المستهدف:** {$targetLabel}\n👥 **عدد المستلمين:** `{$recipients->count()}` مستخدم\n\n📨 تم إرسال إشعارات التطبيق والرسائل الفورية للجميع."
        );
    }
}
