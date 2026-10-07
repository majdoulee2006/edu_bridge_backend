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
 * Telegram bot: teacher services (menu, courses, attendance sessions, excuses, leave)
 * (moved as-is from TelegramBotHandler; methods keep their names and behaviour)
 */
trait TeacherHandlers
{
    // ==========================================
    // Teacher Services Handlers
    // ==========================================

    private function sendTeacherMainMenu($chatId, $headerText = null)
    {
        $keyboard = [
            'keyboard' => [
                [['text' => '📅 جدولي التدريسي'], ['text' => '📚 موادي وطلابي']],
                [['text' => '📷 جلسة الحضور ورمز QR'], ['text' => '📝 الواجبات والتسليمات']],
                [['text' => '➕ إنشاء واجب جديد'], ['text' => '📤 رفع محاضرة / ملف']],
                [['text' => '💯 علامات الطلاب'], ['text' => '🛑 أعذار غياب الطلاب']],
                [['text' => '✈️ طلب إجازة'], ['text' => '🚪 تسجيل خروج']]
            ],
            'resize_keyboard' => true,
            'one_time_keyboard' => false
        ];

        $fullText = $headerText ?: "👨‍🏫 **لوحة تحكم المعلم**\nاختر الخدمة المطلوبة من القائمة أدناه:";

        $this->sendMessage($chatId, $fullText, $keyboard);
    }

    private function getTeacherCourses(User $user)
    {
        $teacher = $user->teacher;
        if (!$teacher) {
            return collect();
        }
        return $teacher->courses()->withCount('students')->get();
    }

    private function handleTeacherSchedule(User $user, $chatId)
    {
        $teacher = $user->teacher;
        if (!$teacher) {
            $this->sendMessage($chatId, "❌ تعذر العثور على ملف المعلم الخاص بحسابك.");
            return;
        }

        $courseIds = $teacher->courses()->pluck('courses.course_id');
        if ($courseIds->isEmpty()) {
            $this->sendMessage($chatId, "📚 لا توجد مواد مسندة لك حالياً.");
            return;
        }

        $today = now()->format('l');
        $arabicDays = [
            'Sunday' => 'الأحد', 'Monday' => 'الإثنين', 'Tuesday' => 'الثلاثاء',
            'Wednesday' => 'الأربعاء', 'Thursday' => 'الخميس', 'Friday' => 'الجمعة', 'Saturday' => 'السبت'
        ];
        $todayAr = $arabicDays[$today] ?? $today;

        $todaySchedules = Schedule::whereIn('course_id', $courseIds)
            ->where('day', $today)
            ->orderBy('start_time', 'asc')
            ->with('course')
            ->get();

        $message = "📅 **جدول محاضرات اليوم ({$todayAr}):**\n\n";

        if ($todaySchedules->isEmpty()) {
            $message .= "🌟 لا توجد لديك محاضرات مبرمجة لليوم ({$todayAr}). يوم تدريس مريح! ☕\n";
        } else {
            foreach ($todaySchedules as $s) {
                $cName = $s->course->title ?? 'مادة';
                $room = $s->room ?? 'غير محددة';
                $grp = $s->class_group ? " — الشعبة: {$s->class_group}" : '';
                $start = date('h:i A', strtotime($s->start_time));
                $end = date('h:i A', strtotime($s->end_time));

                $message .= "📘 **{$cName}**\n";
                $message .= "🕒 {$start} - {$end}\n";
                $message .= "📍 القاعة: {$room}{$grp}\n";
                $message .= "─────────────\n";
            }
        }

        $keyboard = [
            'inline_keyboard' => [
                [['text' => '🗓️ عرض الجدول الأسبوعي الكامل', 'callback_data' => 'teacher_full_weekly_schedule']]
            ]
        ];

        $this->sendMessage($chatId, $message, null, $keyboard);
    }

    private function handleTeacherWeeklySchedule(User $user, $chatId)
    {
        $teacher = $user->teacher;
        if (!$teacher) return;

        $courseIds = $teacher->courses()->pluck('courses.course_id');
        if ($courseIds->isEmpty()) {
            $this->sendMessage($chatId, "📚 لا توجد مواد مسندة لك حالياً.");
            return;
        }

        $dayOrder = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Saturday'];
        $arabicDays = [
            'Sunday' => 'الأحد', 'Monday' => 'الإثنين', 'Tuesday' => 'الثلاثاء',
            'Wednesday' => 'الأربعاء', 'Thursday' => 'الخميس', 'Friday' => 'الجمعة', 'Saturday' => 'السبت'
        ];

        $allSchedules = Schedule::whereIn('course_id', $courseIds)
            ->orderBy('start_time', 'asc')
            ->with('course')
            ->get()
            ->groupBy('day');

        if ($allSchedules->isEmpty()) {
            $this->sendMessage($chatId, "📅 لم يتم إدراج أي محاضرات في جدولك الأسبوعي بعد.");
            return;
        }

        $message = "🗓️ **الجدول التدريسي الأسبوعي الكامل:**\n\n";

        foreach ($dayOrder as $d) {
            if (!$allSchedules->has($d)) continue;

            $dAr = $arabicDays[$d] ?? $d;
            $message .= "📌 **يوم {$dAr}:**\n";
            foreach ($allSchedules[$d] as $item) {
                $cName = $item->course->title ?? 'مادة';
                $room = $item->room ?? 'قاعة';
                $grp = $item->class_group ? " ({$item->class_group})" : '';
                $start = date('h:i A', strtotime($item->start_time));
                $end = date('h:i A', strtotime($item->end_time));

                $message .= "  • 📘 **{$cName}** | 🕒 {$start} - {$end} | 📍 {$room}{$grp}\n";
            }
            $message .= "\n";
        }

        $this->sendMessage($chatId, $message);
    }

    private function handleTeacherCourses(User $user, $chatId)
    {
        $courses = $this->getTeacherCourses($user);

        if ($courses->isEmpty()) {
            $this->sendMessage($chatId, "📚 لا توجد مواد مسندة لك حالياً.");
            return;
        }

        $message = "📚 **المواد المسندة إليك ({$courses->count()}):**\n\n";
        $keyboard = ['inline_keyboard' => []];

        foreach ($courses as $c) {
            $code = $c->code ? "[{$c->code}] " : '';
            $stCount = $c->students_count ?? 0;
            $message .= "🔹 **{$code}{$c->title}** — عدد الطلاب: `{$stCount}` طالب\n";

            $keyboard['inline_keyboard'][] = [
                ['text' => "📖 تفاصيل مادة: {$c->title}", 'callback_data' => "teacher_course_detail_{$c->course_id}"]
            ];
        }

        $message .= "\n👇 اضغط على أي مادة لاستعراض تفاصيلها وقائمة طلابها:";

        $this->sendMessage($chatId, $message, null, $keyboard);
    }

    private function handleTeacherCourseDetail(User $user, $chatId, int $courseId)
    {
        $course = Course::withCount(['students', 'lessons'])->find($courseId);
        if (!$course) {
            $this->sendMessage($chatId, "❌ المادة غير موجودة.");
            return;
        }

        // حساب نسبة الحضور العامة للمادة
        $totalAttendance = Attendance::whereHas('lesson', function($q) use ($courseId) {
            $q->where('course_id', $courseId);
        })->count();

        $presentAttendance = Attendance::whereHas('lesson', function($q) use ($courseId) {
            $q->where('course_id', $courseId);
        })->where('status', 'present')->count();

        $rate = $totalAttendance > 0 ? round(($presentAttendance / $totalAttendance) * 100, 1) : 100;

        $msg = "📖 **بطاقة مادة: {$course->title}**\n\n";
        if ($course->code) $msg .= "🏷️ **رمز المادة:** `{$course->code}`\n";
        $msg .= "👥 **عدد الطلاب المسجلين:** `{$course->students_count}` طالب\n";
        $msg .= "🎥 **عدد المحاضرات المنجزة:** `{$course->lessons_count}` محاضرة\n";
        $msg .= "📊 **نسبة الحضور العامة للمادة:** `{$rate}%`\n\n";
        $msg .= "👇 اختر الإجراء المطلوب:";

        $keyboard = [
            'inline_keyboard' => [
                [
                    ['text' => '👥 قائمة الطلاب والدوام', 'callback_data' => "teacher_course_students_{$courseId}"],
                    ['text' => '📷 بدء جلسة حضور', 'callback_data' => "teacher_course_start_attendance_{$courseId}"],
                ],
                [
                    ['text' => '➕ إنشاء واجب جديد', 'callback_data' => "teacher_create_assign_course_{$courseId}"],
                    ['text' => '📤 رفع محاضرة / ملف', 'callback_data' => "teacher_upload_lesson_start_{$courseId}"],
                ],
                [
                    ['text' => '📝 واجبات المادة', 'callback_data' => "teacher_course_assignments_{$courseId}"],
                    ['text' => '💯 امتحانات المادة', 'callback_data' => "teacher_course_exams_{$courseId}"],
                ],
                [
                    ['text' => '🛑 أعذار غياب الطلاب', 'callback_data' => "teacher_course_excuses_{$courseId}"],
                ]
            ]
        ];

        $this->sendMessage($chatId, $msg, null, $keyboard);
    }

    private function handleTeacherCourseStudents(User $user, $chatId, int $courseId)
    {
        $course = Course::with(['students.user'])->find($courseId);
        if (!$course) return;

        if ($course->students->isEmpty()) {
            $this->sendMessage($chatId, "👥 لا يوجد طلاب مسجلون في مادة ({$course->title}) حتى الآن.");
            return;
        }

        $msg = "👥 **قائمة طلاب مادة: {$course->title} ({$course->students->count()}):**\n\n";

        foreach ($course->students as $idx => $student) {
            $stUser = $student->user;
            $name = $stUser->full_name ?? 'طالب';
            $uid = $stUser->university_id ?? $student->student_code ?? '—';

            // حساب نسبة حضور هذا الطالب في هذه المادة
            $stTotal = Attendance::where('student_id', $student->student_id)
                ->whereHas('lesson', fn($q) => $q->where('course_id', $courseId))
                ->count();
            $stPresent = Attendance::where('student_id', $student->student_id)
                ->whereHas('lesson', fn($q) => $q->where('course_id', $courseId))
                ->where('status', 'present')
                ->count();
            $pct = $stTotal > 0 ? round(($stPresent / $stTotal) * 100) : 100;
            $icon = $pct >= 85 ? '🟢' : ($pct >= 70 ? '🟡' : '🔴');

            $num = $idx + 1;
            $msg .= "{$num}. **{$name}** (`{$uid}`) | {$icon} الدوام: `{$pct}%`\n";
        }

        $this->sendMessage($chatId, $msg);
    }

    private function handleTeacherAttendanceMenu(User $user, $chatId)
    {
        $courses = $this->getTeacherCourses($user);

        if ($courses->isEmpty()) {
            $this->sendMessage($chatId, "📚 لا توجد مواد مسندة لك لبدء جلسة حضور.");
            return;
        }

        $keyboard = ['inline_keyboard' => []];
        foreach ($courses as $c) {
            $keyboard['inline_keyboard'][] = [
                ['text' => "📷 بدء جلسة حضور: {$c->title}", 'callback_data' => "teacher_course_start_attendance_{$c->course_id}"]
            ];
        }

        $this->sendMessage($chatId, "📷 **بدء جلسة الحضور الذكي والـ QR** 🎓\n\nيرجى اختيار المادة لبدء جلسة الحضور وتوليد رمز الاستجابة السريعة (QR):", null, $keyboard);
    }

    private function handleTeacherStartAttendance(User $user, $chatId, int $courseId)
    {
        $teacher = $user->teacher;
        $course = Course::find($courseId);
        if (!$teacher || !$course) {
            $this->sendMessage($chatId, "❌ تعذر إيجاد المادة.");
            return;
        }

        // إنشاء درس/جلسة جديدة
        $lessonId = DB::table('lessons')->insertGetId([
            'course_id'   => $courseId,
            'teacher_id'  => $teacher->teacher_id,
            'title'       => 'جلسة حضور - ' . now()->format('Y-m-d H:i'),
            'type'        => 'session',
            'created_at'  => now(),
            'updated_at'  => now(),
        ]);

        $qrToken = \Illuminate\Support\Str::random(32);
        $sessionId = DB::table('attendance_sessions')->insertGetId([
            'lesson_id'          => $lessonId,
            'qr_token'           => $qrToken,
            'expires_at'         => now()->addMinutes(15),
            'session_expires_at' => now()->addMinutes(15),
            'is_active'          => 1,
            'created_at'         => now(),
            'updated_at'         => now(),
        ]);

        $qrImageUrl = "https://api.qrserver.com/v1/create-qr-code/?size=400x400&data=" . urlencode($qrToken);

        $caption = "📷 **جلسة الحضور نشطة الآن!** 🎓\n\n"
            . "📘 **المادة:** {$course->title}\n"
            . "⏰ **صلاحية الجلسة:** 15 دقيقة\n"
            . "🔑 **رمز الجلسة:** `{$qrToken}`\n\n"
            . "📌 اعرض هذا الرمز للطلاب في القاعة ليقوموا بمسحه من هواتفهم وتسجيل حضورهم فوراً.";

        $keyboard = [
            'inline_keyboard' => [
                [
                    ['text' => '🔄 تحديث إحصائيات الحضور المباشر', 'callback_data' => "teacher_session_stats_{$sessionId}"],
                ],
                [
                    ['text' => '🛑 إنهاء جلسة الحضور الآن', 'callback_data' => "teacher_end_session_{$sessionId}"],
                ]
            ]
        ];

        $this->sendPhoto($chatId, $qrImageUrl, $caption, $keyboard);
    }

    private function handleTeacherSessionStats(User $user, $chatId, int $sessionId)
    {
        $session = DB::table('attendance_sessions')->where('id', $sessionId)->first();
        if (!$session) {
            $this->sendMessage($chatId, "❌ الجلسة غير موجودة.");
            return;
        }

        $lesson = DB::table('lessons')->where('lesson_id', $session->lesson_id)->first();
        $course = $lesson ? Course::with('students.user')->find($lesson->course_id) : null;
        $totalStudents = $course ? $course->students->count() : 0;

        $records = Attendance::where('lesson_id', $session->lesson_id)
            ->with('student.user')
            ->get();

        $present = $records->where('status', 'present');
        $presentCount = $present->count();
        $absentCount = max(0, $totalStudents - $presentCount);

        $statusStr = $session->is_active ? "🟢 نشطة حالياً" : "🔴 منتهية / مغلقة";

        $msg = "📊 **إحصائيات الحضور المباشرة للجلسة** ⏱️\n\n"
            . "📘 **المادة:** " . ($course->title ?? 'مادة') . "\n"
            . "الحالة: {$statusStr}\n"
            . "👥 **إجمالي طلاب المادة:** `{$totalStudents}`\n"
            . "✅ **الحاضرون:** `{$presentCount}` طالب\n"
            . "❌ **الغائبون:** `{$absentCount}` طالب\n\n";

        if ($present->isNotEmpty()) {
            $msg .= "📋 **قائمة الطلاب الحاضرين:**\n";
            foreach ($present as $idx => $att) {
                $stName = $att->student->user->full_name ?? 'طالب';
                $time = $att->created_at ? $att->created_at->format('h:i A') : '';
                $face = ($att->face_status === 'verified') ? '👤✅' : '📷';
                $num = $idx + 1;
                $msg .= "{$num}. {$stName} ({$time}) {$face}\n";
            }
        } else {
            $msg .= "⏳ بانتظار مسح الطلاب للرمز وتسجيل حضورهم...\n";
        }

        $keyboard = [
            'inline_keyboard' => [
                [
                    ['text' => '🔄 تحديث الإحصائيات مجدداً', 'callback_data' => "teacher_session_stats_{$sessionId}"],
                ]
            ]
        ];

        if ($session->is_active) {
            $keyboard['inline_keyboard'][] = [
                ['text' => '🛑 إنهاء الجلسة الآن', 'callback_data' => "teacher_end_session_{$sessionId}"]
            ];
        }

        $this->sendMessage($chatId, $msg, null, $keyboard);
    }

    private function handleTeacherEndSession(User $user, $chatId, int $sessionId)
    {
        DB::table('attendance_sessions')
            ->where('id', $sessionId)
            ->update(['is_active' => 0, 'updated_at' => now()]);

        $this->sendMessage($chatId, "🛑 **تم إنهاء جلسة الحضور بنجاح.**\nتم إغلاق استقبال التسجيلات ولن يتمكن أي طالب من مسح الرمز بعد الآن.");
    }

    private function handleTeacherAssignments(User $user, $chatId)
    {
        $courses = $this->getTeacherCourses($user);

        if ($courses->isEmpty()) {
            $this->sendMessage($chatId, "📚 لا توجد مواد مسندة لك.");
            return;
        }

        $keyboard = ['inline_keyboard' => []];
        $keyboard['inline_keyboard'][] = [
            ['text' => '➕ إنشاء ونشر واجب جديد الآن', 'callback_data' => 'teacher_create_assignment_start']
        ];
        foreach ($courses as $c) {
            $keyboard['inline_keyboard'][] = [
                ['text' => "📝 واجبات مادة: {$c->title}", 'callback_data' => "teacher_course_assignments_{$c->course_id}"]
            ];
        }

        $this->sendMessage($chatId, "📝 **متابعة الواجبات والتسليمات** 🎓\n\nيرجى اختيار المادة لعرض الواجبات المرفوعة وإحصائيات تسليم الطلاب أو إنشاء واجب جديد:", null, $keyboard);
    }

    private function handleTeacherCourseAssignments(User $user, $chatId, int $courseId)
    {
        $course = Course::withCount('students')->find($courseId);
        if (!$course) return;

        $assignments = Assignment::where('course_id', $courseId)
            ->withCount('submissions')
            ->latest()
            ->get();

        $keyboard = [
            'inline_keyboard' => [
                [
                    ['text' => "➕ إنشاء واجب جديد لمادة {$course->title}", 'callback_data' => "teacher_create_assign_course_{$courseId}"]
                ]
            ]
        ];

        if ($assignments->isEmpty()) {
            $this->sendMessage($chatId, "📝 لا توجد واجبات مرفوعة لمادة ({$course->title}) حتى الآن.\nيمكنك إنشاء أول واجب من الزر أدناه:", null, $keyboard);
            return;
        }

        $msg = "📝 **واجبات مادة: {$course->title} ({$assignments->count()}):**\n\n";

        foreach ($assignments as $a) {
            $due = $a->due_date ? date('Y-m-d', strtotime($a->due_date)) : 'غير محدد';
            $subs = $a->submissions_count ?? 0;
            $total = $course->students_count ?? 0;
            $pts = $a->max_points ?? $a->max_score ?? 20;
            $msg .= "🔹 **{$a->title}**\n";
            $msg .= "📅 موعد التسليم: `{$due}` | 💯 الدرجة: `{$pts}`\n";
            $msg .= "📥 عدد التسليمات: `{$subs} / {$total}` طالب\n";
            $msg .= "─────────────\n";

            $keyboard['inline_keyboard'][] = [
                ['text' => "👥 تسليمات وتصحيح: {$a->title}", 'callback_data' => "teacher_assignment_submissions_{$a->assignment_id}"]
            ];
        }

        $this->sendMessage($chatId, $msg, null, $keyboard);
    }

    private function handleTeacherAssignmentSubmissions(User $user, $chatId, int $assignmentId)
    {
        $assignment = Assignment::with(['course'])->find($assignmentId);
        if (!$assignment) return;

        $submissions = AssignmentSubmission::where('assignment_id', $assignmentId)
            ->with('student.user')
            ->latest('submitted_at')
            ->get();

        if ($submissions->isEmpty()) {
            $this->sendMessage($chatId, "📥 لم يقم أي طالب بتسليم واجب ({$assignment->title}) حتى الآن.");
            return;
        }

        $maxPts = $assignment->max_points ?? $assignment->max_score ?? 20;
        $msg = "📥 **تسليمات واجب: {$assignment->title} ({$submissions->count()}):**\n"
            . "💯 الدرجة الكلية: `{$maxPts}`\n\n";

        $keyboard = ['inline_keyboard' => []];

        foreach ($submissions as $idx => $sub) {
            $stName = $sub->student->user->full_name ?? 'طالب';
            $time = $sub->submitted_at ? date('Y-m-d h:i A', strtotime($sub->submitted_at)) : '—';
            $grade = $sub->grade !== null ? " | 💯 الدرجة: **{$sub->grade} / {$maxPts}**" : ' | ⏳ (بانتظار التصحيح)';
            $hasFile = !empty($sub->file_path) ? ' 📎' : '';
            $num = $idx + 1;
            $msg .= "{$num}. **{$stName}**{$hasFile}\n   🕒 تم التسليم: {$time}{$grade}\n";

            $btnText = $sub->grade !== null ? "✏️ تعديل علامة: {$stName} ({$sub->grade})" : "📝 تصحيح ورصد علامة: {$stName}";
            $keyboard['inline_keyboard'][] = [
                ['text' => $btnText, 'callback_data' => "teacher_grade_sub_{$sub->submission_id}"]
            ];
        }

        $msg .= "\n👇 اضغط على اسم أي طالب لتصحيح حله ورصد علامته:";

        $this->sendMessage($chatId, $msg, null, $keyboard);
    }

    private function handleTeacherGrades(User $user, $chatId)
    {
        $courses = $this->getTeacherCourses($user);

        if ($courses->isEmpty()) {
            $this->sendMessage($chatId, "📚 لا توجد مواد مسندة لك.");
            return;
        }

        $keyboard = ['inline_keyboard' => []];
        foreach ($courses as $c) {
            $keyboard['inline_keyboard'][] = [
                ['text' => "💯 امتحانات مادة: {$c->title}", 'callback_data' => "teacher_course_exams_{$c->course_id}"]
            ];
        }

        $this->sendMessage($chatId, "💯 **كشف درجات الطلاب والامتحانات** 🎓\n\nيرجى اختيار المادة:", null, $keyboard);
    }

    private function handleTeacherCourseExams(User $user, $chatId, int $courseId)
    {
        $course = Course::find($courseId);
        if (!$course) return;

        $exams = \App\Models\Exam::where('course_id', $courseId)
            ->withCount('grades')
            ->latest('exam_date')
            ->get();

        if ($exams->isEmpty()) {
            $this->sendMessage($chatId, "💯 لا توجد امتحانات مسجلة لمادة ({$course->title}) حتى الآن.");
            return;
        }

        $msg = "💯 **امتحانات مادة: {$course->title}:**\n\n";
        $keyboard = ['inline_keyboard' => []];

        foreach ($exams as $ex) {
            $date = $ex->exam_date ? date('Y-m-d', strtotime($ex->exam_date)) : 'غير محدد';
            $grCount = $ex->grades_count ?? 0;
            $msg .= "🔹 **{$ex->exam_name}**\n";
            $msg .= "📅 التاريخ: `{$date}` | 💯 الدرجة العظمى: `{$ex->max_score}`\n";
            $msg .= "👥 عدد الطلاب المرصودة علاماتهم: `{$grCount}`\n";
            $msg .= "─────────────\n";

            $keyboard['inline_keyboard'][] = [
                ['text' => "📊 كشف علامات: {$ex->exam_name}", 'callback_data' => "teacher_exam_grades_{$ex->exam_id}"]
            ];
        }

        $this->sendMessage($chatId, $msg, null, $keyboard);
    }

    private function handleTeacherExamGrades(User $user, $chatId, int $examId)
    {
        $exam = \App\Models\Exam::with('course')->find($examId);
        if (!$exam) return;

        $grades = Grade::where('exam_id', $examId)
            ->with('student.user')
            ->get();

        if ($grades->isEmpty()) {
            $this->sendMessage($chatId, "📊 لا توجد علامات مرصودة لامتحان ({$exam->exam_name}) بعد.");
            return;
        }

        $scores = $grades->pluck('score')->map(fn($s) => (float)$s);
        $maxScore = (float)$exam->max_score;
        $highest = $scores->max();
        $lowest = $scores->min();
        $avg = round($scores->avg(), 1);
        $passCount = $scores->filter(fn($s) => $maxScore > 0 && ($s / $maxScore) >= 0.5)->count();
        $passRate = $grades->count() > 0 ? round(($passCount / $grades->count()) * 100, 1) : 0;

        $msg = "📊 **إحصائيات امتحان: {$exam->exam_name}** ({$exam->course->title})\n\n"
            . "👥 **عدد الطلاب:** `{$grades->count()}`\n"
            . "🔝 **أعلى علامة:** `{$highest} / {$maxScore}`\n"
            . "🔻 **أدنى علامة:** `{$lowest} / {$maxScore}`\n"
            . "📈 **المتوسط الحسابي:** `{$avg}`\n"
            . "🟢 **نسبة النجاح:** `{$passRate}%` ({$passCount} ناجح)\n\n"
            . "━━━━━━━━━━━━━━━━━━\n"
            . "📋 **كشف علامات الطلاب:**\n";

        foreach ($grades as $idx => $g) {
            $stName = $g->student->user->full_name ?? 'طالب';
            $sc = (float)$g->score;
            $icon = ($maxScore > 0 && ($sc / $maxScore) >= 0.5) ? '🟢' : '🔴';
            $num = $idx + 1;
            $msg .= "{$num}. {$stName}: {$icon} **{$sc}** / {$maxScore}\n";
        }

        $this->sendMessage($chatId, $msg);
    }

    private function handleTeacherExcuses(User $user, $chatId)
    {
        $courses = $this->getTeacherCourses($user);

        if ($courses->isEmpty()) {
            $this->sendMessage($chatId, "📚 لا توجد مواد مسندة لك.");
            return;
        }

        $keyboard = ['inline_keyboard' => []];
        foreach ($courses as $c) {
            $keyboard['inline_keyboard'][] = [
                ['text' => "🛑 أعذار مادة: {$c->title}", 'callback_data' => "teacher_course_excuses_{$c->course_id}"]
            ];
        }

        $this->sendMessage($chatId, "🛑 **مراجعة أعذار غياب الطلاب** 🎓\n\nيرجى اختيار المادة لعرض الأعذار المقدمة من الطلاب:", null, $keyboard);
    }

    private function handleTeacherCourseExcuses(User $user, $chatId, int $courseId)
    {
        $course = Course::find($courseId);
        if (!$course) return;

        $excuses = Attendance::whereHas('lesson', fn($q) => $q->where('course_id', $courseId))
            ->whereNotNull('excuse_text')
            ->where('excuse_text', '!=', '')
            ->with(['student.user', 'lesson'])
            ->latest('attendance_date')
            ->take(15)
            ->get();

        if ($excuses->isEmpty()) {
            $this->sendMessage($chatId, "🌟 لا توجد أي أعذار غياب مقدمة لمادة ({$course->title}).");
            return;
        }

        $msg = "🛑 **أعذار غياب طلاب مادة: {$course->title} ({$excuses->count()}):**\n\n";
        $keyboard = ['inline_keyboard' => []];

        foreach ($excuses as $att) {
            $stName = $att->student->user->full_name ?? 'طالب';
            $date = $att->attendance_date ? date('Y-m-d', strtotime($att->attendance_date)) : '—';
            $stStatus = match($att->excuse_status) {
                'approved' => '✅ مقبول',
                'rejected' => '❌ مرفوض',
                default    => '⏳ قيد المراجعة',
            };

            $msg .= "🔹 **{$stName}** (تاريخ: `{$date}`) — {$stStatus}\n";
            $msg .= "📝 السبب: \"{$att->excuse_text}\"\n";
            $msg .= "─────────────\n";

            $keyboard['inline_keyboard'][] = [
                ['text' => "🔍 تفاصيل عذر: {$stName} ({$date})", 'callback_data' => "teacher_excuse_detail_{$att->attendance_id}"]
            ];
        }

        $this->sendMessage($chatId, $msg, null, $keyboard);
    }

    private function handleTeacherExcuseDetail(User $user, $chatId, int $attendanceId)
    {
        $att = Attendance::with(['student.user', 'lesson.course'])->find($attendanceId);
        if (!$att) {
            $this->sendMessage($chatId, "❌ العذر غير موجود.");
            return;
        }

        $stName = $att->student->user->full_name ?? 'طالب';
        $cName = $att->lesson->course->title ?? 'مادة';
        $date = $att->attendance_date ? date('Y-m-d', strtotime($att->attendance_date)) : '—';
        $stStatus = match($att->excuse_status) {
            'approved' => '✅ مقبول من الإدارة',
            'rejected' => '❌ مرفوض من الإدارة',
            default    => '⏳ قيد المراجعة والتدقيق لدى شؤون الطلاب',
        };

        $msg = "📝 **تفاصيل عذر الغياب الأكاديمي**\n\n"
            . "👤 **الطالب:** {$stName}\n"
            . "📘 **المادة:** {$cName}\n"
            . "📅 **تاريخ الغياب:** `{$date}`\n"
            . "الحالة: {$stStatus}\n\n"
            . "✍️ **نص العذر المقدم:**\n\"{$att->excuse_text}\"\n";

        $this->sendMessage($chatId, $msg);

        if (!empty($att->excuse_attachment)) {
            $this->sendDocument($chatId, $att->excuse_attachment, "📸 وثيقة العذر المرفقة للطالب {$stName}");
        }
    }

    private function handleTeacherLeaveMenu($chatId)
    {
        $keyboard = [
            'inline_keyboard' => [
                [
                    ['text' => '📅 إجازة يوم كامل', 'callback_data' => 'teacher_leave_type_full_day'],
                    ['text' => '⏱️ إجازة ساعية', 'callback_data' => 'teacher_leave_type_hourly'],
                ]
            ]
        ];

        $this->sendMessage($chatId, "✈️ **تقديم طلب إجازة للمعلم** 👨‍🏫\n\nيرجى تحديد نوع الإجازة المطلوبة لإرسالها لرئيس القسم والإدارة:", null, $keyboard);
    }

    private function handleTeacherLeaveDate($chatId, $text)
    {
        $time = strtotime(trim($text));
        if (!$time) {
            $this->sendMessage($chatId, "❌ التاريخ غير صحيح. يرجى إرسال تاريخ صالح بصيغة YYYY-MM-DD (مثال: " . now()->addDay()->format('Y-m-d') . "):");
            return;
        }

        $date = date('Y-m-d', $time);
        Cache::put("telegram_teacher_leave_date_{$chatId}", $date, 1800);
        $type = Cache::get("telegram_teacher_leave_type_{$chatId}", 'full_day');

        if ($type === 'hourly') {
            Cache::put("telegram_state_{$chatId}", 'awaiting_teacher_leave_hours', 1800);
            $keyboard = [
                'inline_keyboard' => [
                    [
                        ['text' => '⏰ 08:00 ص - 10:00 ص', 'callback_data' => 'teacher_leave_hours_08:00 - 10:00'],
                        ['text' => '⏰ 10:00 ص - 12:00 م', 'callback_data' => 'teacher_leave_hours_10:00 - 12:00'],
                    ],
                    [
                        ['text' => '⏰ 12:00 م - 02:00 م', 'callback_data' => 'teacher_leave_hours_12:00 - 02:00'],
                        ['text' => '⏰ 08:00 ص - 12:00 م', 'callback_data' => 'teacher_leave_hours_08:00 - 12:00'],
                    ]
                ]
            ];
            $this->sendMessage(
                $chatId,
                "⏱️ **وقت الإجازة الساعية**\n\nيرجى اختيار الفترة الزمنية أو كتابتها (مثال: من 09:00 إلى 11:00):",
                null,
                $keyboard
            );
        } else {
            Cache::put("telegram_state_{$chatId}", 'awaiting_teacher_leave_reason', 1800);
            $this->sendMessage($chatId, "✍️ **سبب طلب الإجازة**\n\nيرجى كتابة وتوضيح سبب طلب الإجازة بالتفصيل:");
        }
    }

    private function handleTeacherLeaveHours($chatId, $text)
    {
        $input = trim($text);
        Cache::put("telegram_teacher_leave_hours_{$chatId}", $input, 1800);
        Cache::put("telegram_state_{$chatId}", 'awaiting_teacher_leave_reason', 1800);
        $this->sendMessage($chatId, "✍️ **سبب طلب الإجازة**\n\nيرجى كتابة سبب طلب الإجازة الساعية:");
    }

    private function handleTeacherLeaveReason(User $user, $chatId, $text)
    {
        $type = Cache::get("telegram_teacher_leave_type_{$chatId}", 'full_day');
        $date = Cache::get("telegram_teacher_leave_date_{$chatId}", now()->toDateString());
        $hours = Cache::get("telegram_teacher_leave_hours_{$chatId}", '');
        $reason = trim($text);

        $reasonText = $type === 'hourly'
            ? "[إجازة ساعية للمعلم: {$hours}] - {$reason}"
            : "[إجازة يوم كامل للمعلم] - {$reason}";

        // إشعار رئيس القسم والإدارة وشؤون الطلاب
        $dept = $user->department;
        $hodUserIds = DB::table('users')->where('role_id', 5);
        if ($dept) {
            // رئيس نفس القسم فقط (مطابقة دقيقة): LIKE كان يُبلغ رؤساء أقسام أسماؤها تحتوي اسم قسم المعلم
            $hodUserIds->where('department', $dept);
        }
        $recipients = $hodUserIds->pluck('user_id');

        if ($recipients->isEmpty()) {
            $recipients = DB::table('users')->whereIn('role_id', [1, 6])->pluck('user_id');
        }

        $title = 'طلب إجازة جديد من المعلم';
        $msg = "قام الأستاذ ({$user->full_name}) بتقديم طلب إجازة بتاريخ ({$date}) عبر التيليغرام.\nالسبب: {$reasonText}";

        foreach ($recipients as $recipientId) {
            Notification::create([
                'user_id'    => $recipientId,
                'sender_id'  => $user->user_id,
                'title'      => $title,
                'message'    => $msg,
                'type'       => 'teacher_leave',
                'category'   => 'administrative',
                'related_id' => $user->user_id,
                'is_read'    => false,
            ]);

            FcmService::sendToUser($recipientId, $title, $msg, [
                'type' => 'teacher_leave',
                'teacher_user_id' => (string)$user->user_id
            ]);
        }

        Cache::forget("telegram_state_{$chatId}");
        Cache::forget("telegram_teacher_leave_type_{$chatId}");
        Cache::forget("telegram_teacher_leave_date_{$chatId}");
        Cache::forget("telegram_teacher_leave_hours_{$chatId}");

        $typeLabel = $type === 'hourly' ? "إجازة ساعية ({$hours})" : "إجازة يوم كامل";
        $this->sendMessage($chatId, "✅ **تم تقديم طلب الإجازة بنجاح!**\n\n📌 **النوع:** {$typeLabel}\n📅 **التاريخ:** {$date}\n📝 **السبب:** {$reason}\n\n📨 تم إرسال إشعار فوري لرئيس قسمك والإدارة لمراجعة الطلب.");
    }

}
