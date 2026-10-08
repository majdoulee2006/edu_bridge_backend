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
use App\Services\LeaveWorkflow;
use App\Support\LoginThrottleGuard;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

/**
 * Telegram bot: student services (schedule, grades, absences, lectures, leave, QR attendance)
 * (moved as-is from TelegramBotHandler; methods keep their names and behaviour)
 */
trait StudentHandlers
{
    // ==========================================
    // Student Services Handlers
    // ==========================================

    private function sendStudentMainMenu($chatId, $headerText = null)
    {
        $keyboard = [
            'keyboard' => [
                [['text' => '📅 جدولي'], ['text' => '💯 علاماتي']],
                [['text' => '🛑 غياباتي'], ['text' => '📚 محاضراتي']],
                [['text' => '✈️ طلب إجازة'], ['text' => '📋 إجازاتي']],
                [['text' => '📷 تسجيل حضور'], ['text' => '🚪 تسجيل خروج']]
            ],
            'resize_keyboard' => true,
            'one_time_keyboard' => false
        ];

        $servicesList = "📋 **الخدمات المتاحة لك عبر البوت:**\n"
            . "• 📅 **جدولي**: استعراض جدول المحاضرات الأسبوعي والقاعات\n"
            . "• 💯 **علاماتي**: استعراض كافة درجاتك وامتحاناتك المسجلة\n"
            . "• 🛑 **غياباتي**: متابعة نسبة الحضور والإنذارات وتقديم الأعذار\n"
            . "• 📚 **محاضراتي**: تصفح المواد والمحاضرات والملفات المرفقة\n"
            . "• ✈️ **طلب إجازة**: تقديم إذن غياب يومي أو ساعي (ولي الأمر ثم رئيس القسم ثم الشؤون)\n"
            . "• 📋 **إجازاتي**: متابعة حالة طلبات إجازتك ومرحلتها\n"
            . "• 📷 **تسجيل حضور**: مسح كود الـ QR والتحقق بالكاميرا\n"
            . "• 🚪 **تسجيل خروج**: فك ربط الحساب من هذا الجهاز\n\n"
            . "👇 اختر الخدمة المطلوبة من الأزرار أدناه:";

        $fullText = $headerText ? "{$headerText}\n\n{$servicesList}" : $servicesList;

        $this->sendMessage($chatId, $fullText, $keyboard);
    }

    private function handleSchedule(User $user, $chatId)
    {
        $student = $user->student;
        $enrolledCourseIds = $student ? $student->courses->modelKeys() : [];
        $academicYearStr = str_replace('السنة ال', 'سنة ', $user->academic_year ?? '');
        $branchName = \Illuminate\Support\Facades\DB::table('programs')->where('id', $student->program_id)->value('name') ?? $user->branch ?? '';
        $classGroup = $branchName . ' - ' . $academicYearStr;
        
        $today = now()->format('l'); // Today's name in English e.g. Sunday
        $arabicDays = [
            'Sunday' => 'الأحد', 'Monday' => 'الإثنين', 'Tuesday' => 'الثلاثاء',
            'Wednesday' => 'الأربعاء', 'Thursday' => 'الخميس', 'Friday' => 'الجمعة', 'Saturday' => 'السبت'
        ];
        $todayAr = $arabicDays[$today] ?? $today;

        $cacheKey = "telegram_schedule_student_{$student->student_id}_{$today}";
        $schedules = Cache::remember($cacheKey, 3600 * 24, function() use ($enrolledCourseIds, $classGroup, $today) {
            return Schedule::where(function($q) use ($enrolledCourseIds, $classGroup) {
                $q->whereIn('course_id', $enrolledCourseIds)
                  ->orWhere('class_group', $classGroup);
            })
            ->where('day', $today)
            ->orderBy('start_time', 'asc')
            ->with('course')
            ->get();
        });

        if ($schedules->isEmpty()) {
            $this->sendMessage($chatId, "📅 لا يوجد لديك محاضرات مبرمجة لليوم ({$todayAr}). عطلة سعيدة! 🏖️");
            return;
        }

        $message = "📅 **جدول محاضرات اليوم ({$todayAr}):**\n\n";
        foreach ($schedules as $s) {
            $courseName = $s->course->title ?? 'مادة غير محددة';
            $room = $s->room ?? 'قاعة غير محددة';
            $start = date('h:i A', strtotime($s->start_time));
            $end = date('h:i A', strtotime($s->end_time));
            
            $message .= "📘 **{$courseName}**\n";
            $message .= "🕒 {$start} - {$end}\n";
            $message .= "📍 القاعة: {$room}\n";
            $message .= "─────────────\n";
        }

        $this->sendMessage($chatId, $message);
    }

    private function handleGrades(User $user, $chatId)
    {
        $student = $user->student;
        if (!$student) return;

        // جلب جميع العلامات بدون حصر (إلغاء take 5)
        $grades = Grade::with(['exam.course'])
            ->where('grades.student_id', $student->student_id)
            ->join('exams', 'grades.exam_id', '=', 'exams.exam_id')
            ->orderBy('exams.exam_date', 'desc')
            ->select('grades.*', 'exams.exam_name', 'exams.max_score', 'exams.exam_date', 'exams.course_id')
            ->get();

        if ($grades->isEmpty()) {
            $this->sendMessage($chatId, "💯 لا يوجد علامات مسجلة لك حتى الآن.");
            return;
        }

        $totalEarned = 0;
        $totalMax = 0;
        $message = "💯 **سجل جميع العلامات المسجلة ({$grades->count()}):**\n\n";

        foreach ($grades as $grade) {
            $courseName = $grade->exam->course->title ?? 'مادة غير معروفة';
            $type = $grade->exam_name ?? 'امتحان';
            $mark = (float)$grade->score;
            $max = (float)$grade->max_score;
            $date = $grade->exam_date ? date('Y-m-d', strtotime($grade->exam_date)) : 'غير محدد';

            $totalEarned += $mark;
            $totalMax += $max;

            $statusIcon = ($max > 0 && ($mark / $max) >= 0.5) ? '🟢' : '🔴';

            $message .= "📘 **{$courseName}**\n";
            $message .= "📝 الامتحان: {$type}\n";
            $message .= "{$statusIcon} الدرجة: **{$mark}** / {$max}\n";
            $message .= "📅 التاريخ: {$date}\n";
            $message .= "─────────────\n";
        }

        if ($totalMax > 0) {
            $overallPct = round(($totalEarned / $totalMax) * 100, 1);
            $message .= "\n📊 **المجموع الكلي:** `{$totalEarned} / {$totalMax}` (النسبة العامة: `{$overallPct}%`)\n";
        }

        $this->sendMessage($chatId, $message);
    }

    private function handleAttendance(User $user, $chatId)
    {
        $student = $user->student;
        if (!$student) return;

        // جلب كل سجلات الحضور والغياب للطالب
        $allRecords = Attendance::where('student_id', $student->student_id)
            ->with(['lesson.course'])
            ->get();

        $totalSessions = $allRecords->count();
        $presentCount  = $allRecords->where('status', 'present')->count();
        $absentCount   = $allRecords->where('status', 'absent')->count();

        // حساب نسبة الحضور المئوية
        $attendanceRate = $totalSessions > 0 ? round(($presentCount / $totalSessions) * 100, 1) : 100;

        // حساب أيام الغياب الفعلية (أيام فريدة بدون تكرار الجلسات بنفس اليوم)
        $absenceDaysCount = $allRecords->where('status', 'absent')
            ->pluck('attendance_date')
            ->map(fn($d) => $d ? \Carbon\Carbon::parse($d)->toDateString() : null)
            ->filter()
            ->unique()
            ->count();

        // تحديد المؤشر اللوني والرسالة التقييمية
        if ($attendanceRate >= 85) {
            $badge = "🟢 وضعك ممتاز ومثالي!";
        } elseif ($attendanceRate >= 70) {
            $badge = "🟡 تنبيه: انتبه لنسبة حضورك!";
        } else {
            $badge = "🔴 خطر: نسبة حضورك منخفضة جداً ومهدد بالحرمان!";
        }

        $message = "📊 **بطاقة الحضور والغياب الأكاديمية** 🎓\n\n";
        $message .= "📈 **نسبة الدوام العامة:** `{$attendanceRate}%`\n";
        $message .= "الحالة: {$badge}\n\n";

        $message .= "━━━━━━━━━━━━━━━━━━\n";
        $message .= "✅ **الحضور:** {$presentCount} حصة\n";
        $message .= "❌ **الغياب:** {$absentCount} حصة\n";
        $message .= "📅 **إجمالي أيام الغياب:** `{$absenceDaysCount}` يوم\n";
        $message .= "━━━━━━━━━━━━━━━━━━\n\n";

        // تفصيل الغيابات حسب المواد
        $absencesByCourse = [];
        foreach ($allRecords->where('status', 'absent') as $abs) {
            $courseName = $abs->lesson->course->title ?? 'مادة عامة';
            if (!isset($absencesByCourse[$courseName])) {
                $absencesByCourse[$courseName] = 0;
            }
            $absencesByCourse[$courseName]++;
        }

        if (!empty($absencesByCourse)) {
            $message .= "📚 **تفصيل الغيابات حسب المواد:**\n";
            foreach ($absencesByCourse as $course => $cnt) {
                $message .= "🔹 **{$course}**: {$cnt} غياب\n";
            }
        } else {
            $message .= "🌟 لا توجد أي غيابات مسجلة حتى الآن، استمر في تفوقك!\n";
        }

        // تحذير إذا قارب الطالب عتبة الإنذارات (7 أو 10 أو 15)
        if ($absenceDaysCount >= 15) {
            $message .= "\n🚨 **تحذير نهائي:** لقد بلغت حد الـ 15 يوم غياب! تم رفع ملفك لإدارة شؤون الطلاب ورئيس القسم لاتخاذ القرار.";
        } elseif ($absenceDaysCount >= 10) {
            $message .= "\n⚠️ **إنذار ثانٍ:** لديك 10 أيام غياب أو أكثر! تم استدعاء ولي أمرك تلقائياً.";
        } elseif ($absenceDaysCount >= 7) {
            $message .= "\n⚠️ **إنذار أول:** لقد بلغت 7 أيام غياب! يرجى مراجعة إدارة شؤون الطلاب وتجنب المزيد من الغياب.";
        }

        // أزرار تقديم عذر للغيابات غير المبررة
        $unexcused = $allRecords->where('status', 'absent')
            ->whereIn('excuse_status', ['none', null])
            ->sortByDesc('attendance_date')
            ->take(4);

        $keyboard = null;
        if ($unexcused->isNotEmpty()) {
            $keyboard = ['inline_keyboard' => []];
            foreach ($unexcused as $unRecord) {
                $cName = $unRecord->lesson->course->title ?? 'مادة';
                $dStr = $unRecord->attendance_date ? \Carbon\Carbon::parse($unRecord->attendance_date)->format('m/d') : '';
                $keyboard['inline_keyboard'][] = [
                    ['text' => "📝 تقديم عذر: {$cName} ({$dStr})", 'callback_data' => "excuse_start_{$unRecord->attendance_id}"]
                ];
            }
        }

        $this->sendMessage($chatId, $message, null, $keyboard);
    }

    private function handleCoursesMenu(User $user, $chatId)
    {
        $student = $user->student;
        if (!$student) return;

        $courses = $student->courses;
        if ($courses->isEmpty()) {
            $this->sendMessage($chatId, "📚 لست مسجلاً في أي مادة حالياً.");
            return;
        }

        $keyboard = ['inline_keyboard' => []];
        foreach ($courses as $course) {
            $keyboard['inline_keyboard'][] = [
                ['text' => $course->title, 'callback_data' => "course_lectures_{$course->course_id}"]
            ];
        }

        $this->sendMessage($chatId, "📚 **يرجى اختيار المادة لعرض آخر المحاضرات المرفوعة:**", null, $keyboard);
    }

    private function handleCourseLectures(User $user, $chatId, $courseId)
    {
        $course = Course::with(['lessons', 'resources'])->find($courseId);
        
        if (!$course) {
            $this->sendMessage($chatId, "عذراً، المادة غير موجودة.");
            return;
        }

        if ($course->lessons->isEmpty() && $course->resources->isEmpty()) {
            $this->sendMessage($chatId, "لا يوجد دروس أو ملفات مرفوعة لهذه المادة حتى الآن.");
            return;
        }

        $keyboard = ['inline_keyboard' => []];
        foreach ($course->lessons as $lesson) {
            $keyboard['inline_keyboard'][] = [
                ['text' => "🎥 " . $lesson->title, 'callback_data' => "lesson_details_{$lesson->lesson_id}"]
            ];
        }
        foreach ($course->resources as $resource) {
            $keyboard['inline_keyboard'][] = [
                ['text' => "📁 " . $resource->resource_name, 'callback_data' => "resource_details_{$resource->resource_id}"]
            ];
        }

        $this->sendMessage($chatId, "📚 **اختر المحاضرة أو الملف من مادة ({$course->title}):**", null, $keyboard);
    }

    private function handleLessonDetails(User $user, $chatId, $lessonId)
    {
        $lesson = \App\Models\Lesson::find($lessonId);
        if (!$lesson) {
            $this->sendMessage($chatId, "عذراً، تفاصيل هذه المحاضرة غير متوفرة.");
            return;
        }

        $message = "🎥 **{$lesson->title}**\n\n";
        
        if (!empty($lesson->content_url)) {
            $message .= "🔗 [رابط مشاهدة الفيديو]({$lesson->content_url})\n";
        }
        
        $this->sendMessage($chatId, $message);

        // إرسال الملف المرفق مع المحاضرة إذا كان موجوداً
        if (!empty($lesson->file_path)) {
            $fileName = $lesson->file_name ?? $lesson->title;
            $extension = pathinfo($lesson->file_path, PATHINFO_EXTENSION);
            if ($extension && !str_ends_with($fileName, ".$extension")) {
                $fileName .= ".$extension";
            }
            $this->sendDocument($chatId, $lesson->file_path, "📁 " . $fileName, $fileName);
        }
    }

    private function handleResourceDetails(User $user, $chatId, $resourceId)
    {
        $resource = \App\Models\Resource::find($resourceId);
        if (!$resource) {
            $this->sendMessage($chatId, "عذراً، تفاصيل هذا الملف غير متوفرة.");
            return;
        }

        $fileName = $resource->resource_name;
        $extension = pathinfo($resource->file_path, PATHINFO_EXTENSION);
        if ($extension && !str_ends_with($fileName, ".$extension")) {
            $fileName .= ".$extension";
        }

        $this->sendDocument($chatId, $resource->file_path, "📁 {$resource->resource_name}", $fileName);
    }

    // ==========================================
    // Excuse & Leave & QR Attendance Handlers
    // ==========================================

    private function handleExcuseText($chatId, $text, $state)
    {
        $attendanceId = str_replace('awaiting_excuse_text_', '', $state);
        Cache::put("telegram_excuse_reason_{$chatId}_{$attendanceId}", trim($text), 1800);
        Cache::put("telegram_state_{$chatId}", "awaiting_excuse_photo_{$attendanceId}", 1800);

        $msg = "📸 **مرفق العذر الطبي أو الرسمي**\n\n";
        $msg .= "تم حفظ نص العذر: \"{$text}\".\n\n";
        $msg .= "الآن يمكنك إرسال صورة الوثيقة أو التقرير الطبي من الكاميرا أو الاستديو.\n";
        $msg .= "أو أرسل أمر `/skip_photo` للمتابعة بدون إرفاق صورة.";

        $this->sendMessage($chatId, $msg);
    }

    private function handleExcusePhoto($chatId, array $message, $state)
    {
        $attendanceId = str_replace('awaiting_excuse_photo_', '', $state);
        $reason = Cache::get("telegram_excuse_reason_{$chatId}_{$attendanceId}", 'عذر غياب مقدم عبر بوت التيليغرام');

        $filePath = null;
        if (isset($message['photo']) && is_array($message['photo'])) {
            $photos = $message['photo'];
            $bestPhoto = end($photos); // أعلى دقة
            $fileId = $bestPhoto['file_id'] ?? null;
            if ($fileId) {
                $filePath = $this->downloadTelegramPhoto($fileId);
            }
        }

        $attendance = Attendance::find($attendanceId);
        if ($attendance) {
            $attendance->excuse_text = $reason;
            $attendance->excuse_status = 'pending';
            if ($filePath) {
                $attendance->excuse_attachment = $filePath;
            }
            $attendance->save();

            // إشعار إدارة شؤون الطلاب
            $studentName = $attendance->student->user->full_name ?? 'طالب';
            // موظفو الشؤون يراجعون الأعذار؛ وإن لم يوجد أحد فأول أدمن (لا نفترض أن المستخدم رقم 1 موجود)
            $recipients = DB::table('users')->where('role_id', 6)->pluck('user_id');
            if ($recipients->isEmpty() && ($fallbackAdmin = \App\Support\Access::systemSenderId())) {
                $recipients = collect([$fallbackAdmin]);
            }
            foreach ($recipients as $recipientId) {
                Notification::create([
                    'user_id'    => $recipientId,
                    'sender_id'  => $attendance->student->user->user_id ?? null,
                    'title'      => 'عذر غياب جديد بحاجة للمراجعة',
                    'message'    => "قام الطالب ({$studentName}) بتقديم عذر لغيابه في مادة ({$attendance->lesson->course->title})، يرجى مراجعته.",
                    'type'       => 'excuse_request',
                    'category'   => 'administrative',
                    'related_id' => $attendance->attendance_id,
                    'is_read'    => false,
                ]);
            }

        }

        Cache::forget("telegram_state_{$chatId}");
        Cache::forget("telegram_excuse_reason_{$chatId}_{$attendanceId}");

        $this->sendMessage($chatId, "✅ **تم إرسال عذر الغياب بنجاح!**\nطلبك قيد المراجعة والتدقيق من قبل إدارة المعهد.");
    }

    private function handleLeaveMenu($chatId)
    {
        $keyboard = [
            'inline_keyboard' => [
                [
                    ['text' => '📅 إجازة يوم كامل', 'callback_data' => 'leave_type_full_day'],
                    ['text' => '⏱️ إجازة ساعية', 'callback_data' => 'leave_type_hourly'],
                ]
            ]
        ];

        $this->sendMessage($chatId, "✈️ **تقديم طلب إذن غياب** 🎓\n\nيرجى تحديد نوع الإجازة المطلوبة:", null, $keyboard);
    }

    private function handleLeaveDate($chatId, $text)
    {
        $time = strtotime(trim($text));
        if (!$time) {
            $this->sendMessage($chatId, "❌ التاريخ غير صحيح. يرجى إرسال تاريخ صالح بصيغة YYYY-MM-DD (مثال: " . now()->addDay()->format('Y-m-d') . "):");
            return;
        }

        $date = date('Y-m-d', $time);
        if ($date < now()->toDateString()) {
            $this->sendMessage($chatId, "❌ لا يمكن تقديم إجازة بتاريخ سابق. يرجى إرسال تاريخ اليوم أو تاريخ لاحق بصيغة YYYY-MM-DD.");
            return;
        }

        Cache::put("telegram_leave_date_{$chatId}", $date, 1800);
        $type = Cache::get("telegram_leave_type_{$chatId}", 'full_day');

        if ($type === 'hourly') {
            Cache::put("telegram_state_{$chatId}", 'awaiting_leave_hours', 1800);
            $keyboard = [
                'inline_keyboard' => [
                    [
                        ['text' => '⏰ 08:00 ص - 10:00 ص', 'callback_data' => 'leave_hours_08:00 - 10:00'],
                        ['text' => '⏰ 10:00 ص - 12:00 م', 'callback_data' => 'leave_hours_10:00 - 12:00'],
                    ],
                    [
                        ['text' => '⏰ 12:00 م - 02:00 م', 'callback_data' => 'leave_hours_12:00 - 02:00'],
                        ['text' => '⏰ 08:00 ص - 12:00 م', 'callback_data' => 'leave_hours_08:00 - 12:00'],
                    ],
                    [
                        ['text' => '⏰ 10:00 ص - 02:00 م', 'callback_data' => 'leave_hours_10:00 - 02:00'],
                    ]
                ]
            ];
            $this->sendMessage(
                $chatId,
                "⏱️ **وقت الإجازة الساعية**\n\n📌 *ملاحظة:* أوقات الدوام الرسمي من **08:00 صباحاً** حتى **02:00 ظهراً**.\n\nيمكنك اختيار إحدى الفترات أدناه أو كتابة الوقت المطلوب (مثال: من 09:00 إلى 11:00):",
                null,
                $keyboard
            );
        } else {
            Cache::put("telegram_state_{$chatId}", 'awaiting_leave_reason', 1800);
            $this->sendMessage($chatId, "✍️ **سبب طلب الإجازة**\n\nيرجى كتابة سبب طلب الإجازة بالتفصيل:");
        }
    }

    private function handleLeaveHours($chatId, $text)
    {
        $input = trim($text);
        
        // التحقق من أرقام الساعات إذا كانت خارج 8 صباحاً إلى 2 ظهراً
        if (preg_match_all('/\b([0-9]{1,2})(?::[0-9]{2})?\b/', $input, $matches)) {
            foreach ($matches[1] as $numStr) {
                $h = (int)$numStr;
                // إذا أدخل الطالب ساعة مثل 3 أو 4 أو 5 أو 6 أو 7 (قبل 8 ص) أو 15 فما فوق (بعد 2 ظهراً)
                if (($h >= 3 && $h <= 7) || ($h >= 15 && $h <= 23)) {
                    $keyboard = [
                        'inline_keyboard' => [
                            [
                                ['text' => '⏰ 08:00 ص - 10:00 ص', 'callback_data' => 'leave_hours_08:00 - 10:00'],
                                ['text' => '⏰ 10:00 ص - 12:00 م', 'callback_data' => 'leave_hours_10:00 - 12:00'],
                            ],
                            [
                                ['text' => '⏰ 12:00 م - 02:00 م', 'callback_data' => 'leave_hours_12:00 - 02:00'],
                                ['text' => '⏰ 08:00 ص - 12:00 م', 'callback_data' => 'leave_hours_08:00 - 12:00'],
                            ],
                        ]
                    ];
                    $this->sendMessage(
                        $chatId,
                        "❌ **الوقت المدخل خارج أوقات الدوام الرسمي!**\n\nيجب أن تكون الإجازة الساعية بين **08:00 صباحاً** و **02:00 ظهراً**.\nيرجى اختيار فترة من الأزرار أو إعادة كتابة وقت صحيح:",
                        null,
                        $keyboard
                    );
                    return;
                }
            }
        }

        Cache::put("telegram_leave_hours_{$chatId}", $input, 1800);
        Cache::put("telegram_state_{$chatId}", 'awaiting_leave_reason', 1800);
        $this->sendMessage($chatId, "✍️ **سبب طلب الإجازة**\n\nيرجى كتابة سبب طلب الإجازة الساعية:");
    }

    private function handleLeaveReason($user, $chatId, $text)
    {
        if (!$user) {
            $user = User::where('telegram_chat_id', $chatId)->first();
        }

        $student = $user?->student;
        if (!$student) {
            $this->sendMessage($chatId, "❌ تعذر تحديد حساب الطالب. يرجى تسجيل الدخول مجدداً عبر /start.");
            return;
        }

        $reason = trim($text);
        if (mb_strlen($reason) < 3 || mb_strlen($reason) > 500) {
            $this->sendMessage($chatId, "❌ سبب الإجازة يجب أن يكون بين 3 و500 حرف. يرجى إعادة كتابته:");
            return;
        }

        $type = Cache::get("telegram_leave_type_{$chatId}", 'full_day');
        $date = Cache::get("telegram_leave_date_{$chatId}", now()->toDateString());
        $hours = Cache::get("telegram_leave_hours_{$chatId}", '');

        // نفس صيغة نص الإذن في الويب: النوع والفترة داخل السبب
        $reasonText = $type === 'hourly'
            ? "[إذن ساعي - الفترة: {$hours}] - {$reason}"
            : "[إذن يومي] - {$reason}";

        try {
            // منع التكرار المتزامن (كما في الويب: نفس التاريخ خلال 30 ثانية)
            $duplicate = DB::table('leave_requests')
                ->where('student_id', $user->user_id)
                ->where('date', $date)
                ->where('created_at', '>=', now()->subSeconds(30))
                ->exists();

            // المسار الموحّد (التطبيق/الويب/البوت): ولي الأمر ثم رئيس القسم ثم شؤون الطلاب
            $leave = $duplicate ? null : LeaveWorkflow::submit($user, $type, $date, $reasonText);
        } catch (\Exception $e) {
            Log::error("Failed to submit leave request from Telegram: " . $e->getMessage());
            $this->sendMessage($chatId, "❌ حدث خطأ أثناء حفظ طلب الإجازة. يرجى المحاولة مرة أخرى لاحقاً.");
            return;
        }

        Cache::forget("telegram_state_{$chatId}");
        Cache::forget("telegram_leave_type_{$chatId}");
        Cache::forget("telegram_leave_date_{$chatId}");
        Cache::forget("telegram_leave_hours_{$chatId}");

        $typeLabel = $type === 'hourly' ? "إجازة ساعية ({$hours})" : "إجازة يوم كامل";
        $next = ($leave && $leave->status === LeaveWorkflow::STAGE_HOD)
            ? "لا يوجد ولي أمر مربوط بحسابك، فتم تحويل الطلب مباشرة إلى رئيس القسم."
            : "تم إرسال الطلب إلى **ولي أمرك** للموافقة أولاً، ثم رئيس القسم، ثم شؤون الطلاب للاعتماد النهائي.";

        $this->sendMessage(
            $chatId,
            "✅ **تم تقديم طلب الإجازة بنجاح!**\n\n📌 **النوع:** {$typeLabel}\n📅 **التاريخ:** `{$date}`\n📝 **السبب:** {$reason}\n\n{$next}\nيمكنك متابعة المرحلة من زر **إجازاتي**."
        );
    }

    /** حالة طلبات إجازة الطالب ومرحلة كل طلب (نفس معنى الحالات في التطبيق والويب). */
    private function handleMyLeaves(User $user, $chatId)
    {
        $labels = [
            'pending_parent'  => '⏳ بانتظار موافقة ولي الأمر',
            'pending_hod'     => '⏳ بانتظار رئيس القسم',
            'pending_affairs' => '⏳ بانتظار شؤون الطلاب (الاعتماد النهائي)',
            'pending'         => '⏳ قيد المراجعة',
            'approved'        => '✅ موافق عليها نهائياً',
            'rejected'        => '❌ مرفوضة',
        ];

        $rows = DB::table('leave_requests')
            ->where('student_id', $user->user_id)
            ->select('type', 'date', 'reason', 'status', 'created_at')
            ->get();

        $rows = $rows->sortByDesc('created_at')->take(6)->values();

        if ($rows->isEmpty()) {
            $this->sendMessage($chatId, "📋 لم تقدّم أي طلب إجازة بعد.\nاستخدم زر **طلب إجازة** لتقديم طلب جديد.");
            return;
        }

        $msg = "📋 **طلبات إجازتك الأخيرة:**\n\n";
        foreach ($rows as $i => $r) {
            $typeLabel = $r->type === 'hourly' ? 'ساعية' : 'يوم كامل';
            $msg .= ($i + 1) . ". 📅 `{$r->date}` | {$typeLabel}\n";
            $msg .= "   " . ($labels[$r->status] ?? $r->status) . "\n";
            $msg .= "   📝 " . mb_substr((string) $r->reason, 0, 80) . "\n";
            $msg .= "─────────────\n";
        }

        $this->sendMessage($chatId, $msg);
    }

    private function handleQrAttendanceMenu($chatId)
    {
        $domain = env('APP_URL', 'https://edubridge-attend.loca.lt');
        if (!str_starts_with($domain, 'https://')) {
            $domain = 'https://edubridge-attend.loca.lt';
        }
        // رابط موقّع ومؤقت (15 دقيقة) مربوط بهذا الـ chat_id، حتى لا يستطيع أحد فتح الماسح باسم طالب آخر
        $scannerPath = \Illuminate\Support\Facades\URL::temporarySignedRoute(
            'telegram.scanner', now()->addMinutes(15), ['chat_id' => $chatId], false
        );
        $scannerUrl = rtrim($domain, '/') . $scannerPath;

        $keyboard = [
            'inline_keyboard' => [
                [
                    ['text' => '📷 فتح ماسح رمز الحضور (QR & Face)', 'web_app' => ['url' => $scannerUrl]]
                ]
            ]
        ];

        $message = "📷 **تسجيل الحضور الذكي عبر الكاميرا** 🎓\n\nاضغط على الزر أدناه لفتح الكاميرا لمسح رمز المحاضرة والتقاط صورة التحقق وتسجيل حضورك فوراً:";

        $this->sendMessage($chatId, $message, null, $keyboard);
    }

    private function downloadTelegramPhoto(string $fileId): ?string
    {
        try {
            $res = Http::get("{$this->apiUrl}/getFile", ['file_id' => $fileId]);
            if ($res->successful()) {
                $filePath = $res->json('result.file_path');
                if ($filePath) {
                    $token = config('services.telegram.bot_token', env('TELEGRAM_BOT_TOKEN'));
                    $fileUrl = "https://api.telegram.org/file/bot{$token}/{$filePath}";
                    $fileContents = Http::get($fileUrl)->body();

                    $filename = 'excuse_' . time() . '_' . uniqid() . '.jpg';
                    $destDir = public_path('uploads/excuses');
                    if (!is_dir($destDir)) {
                        mkdir($destDir, 0755, true);
                    }
                    file_put_contents($destDir . '/' . $filename, $fileContents);

                    return 'uploads/excuses/' . $filename;
                }
            }
        } catch (\Exception $e) {
            Log::error('Telegram download photo error: ' . $e->getMessage());
        }
        return null;
    }
}
