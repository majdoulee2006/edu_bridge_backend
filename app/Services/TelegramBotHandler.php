<?php

namespace App\Services;

use App\Models\User;
use App\Models\Student;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;
use App\Models\Schedule;
use App\Models\Grade;
use App\Models\Attendance;
use App\Models\Course;
use Illuminate\Support\Facades\DB;
use App\Models\Notification;
use App\Services\FcmService;

class TelegramBotHandler
{
    private string $token;
    private string $apiUrl;

    public function __construct()
    {
        $this->token  = config('services.telegram.bot_token') ?? '';
        $this->apiUrl = "https://api.telegram.org/bot{$this->token}";
    }

    public function handleUpdate(array $update)
    {
        if (isset($update['message'])) {
            $this->handleMessage($update['message']);
        } elseif (isset($update['callback_query'])) {
            $this->handleCallbackQuery($update['callback_query']);
        }
    }

    private function handleMessage(array $message)
    {
        $chatId = $message['chat']['id'] ?? null;
        $text = $message['text'] ?? '';

        if (!$chatId) return;

        // التحقق من حالة المستخدم
        $user = User::where('telegram_chat_id', $chatId)->first();
        
        $stateKey = "telegram_state_{$chatId}";
        $state = Cache::get($stateKey);

        if ($text === '/start') {
            if ($user && $user->role === 'student') {
                $this->sendStudentMainMenu($chatId, "مرحباً مجدداً **{$user->full_name}** 🎓");
            } else {
                $this->sendMessage($chatId, "مرحباً بك في البوت الرسمي لـ Edu Bridge 🎓\nللبدء، يرجى إدخال **الرقم الجامعي** الخاص بك:");
                Cache::put($stateKey, 'awaiting_university_id', 3600);
            }
            return;
        }

        if ($text === '/logout') {
            $this->handleLogout($user, $chatId, $stateKey);
            return;
        }

        // معالجة حالة التسجيل (Authentication Flow)
        if ($state === 'awaiting_university_id') {
            $this->handleUniversityIdInput($chatId, $text);
            return;
        }

        if ($state === 'awaiting_password') {
            $this->handlePasswordInput($chatId, $text);
            return;
        }

        // معالجة تقديم عذر غياب
        if ($state && str_starts_with($state, 'awaiting_excuse_text_')) {
            $this->handleExcuseText($chatId, $text, $state);
            return;
        }

        if ($state && str_starts_with($state, 'awaiting_excuse_photo_')) {
            $this->handleExcusePhoto($chatId, $message, $state);
            return;
        }

        // معالجة تقديم طلب إجازة
        if ($state === 'awaiting_leave_date') {
            $this->handleLeaveDate($chatId, $text);
            return;
        }

        if ($state === 'awaiting_leave_hours') {
            $this->handleLeaveHours($chatId, $text);
            return;
        }

        if ($state === 'awaiting_leave_reason') {
            $this->handleLeaveReason($user, $chatId, $text);
            return;
        }

        // إذا كان المستخدم مسجلاً كطالب، معالجة الردود النصية للقائمة الرئيسية
        if ($user && $user->role === 'student') {
            if (str_contains($text, 'جدول')) {
                $this->handleSchedule($user, $chatId);
            } elseif (str_contains($text, 'علامات')) {
                $this->handleGrades($user, $chatId);
            } elseif (str_contains($text, 'غياب')) {
                $this->handleAttendance($user, $chatId);
            } elseif (str_contains($text, 'محاضر')) {
                $this->handleCoursesMenu($user, $chatId);
            } elseif (str_contains($text, 'إجازة') || str_contains($text, 'اجازة')) {
                $this->handleLeaveMenu($chatId);
            } elseif (str_contains($text, 'حضور')) {
                $this->handleQrAttendanceMenu($chatId);
            } elseif (str_contains($text, 'خروج')) {
                $this->handleLogout($user, $chatId, $stateKey);
            } else {
                $this->sendStudentMainMenu($chatId);
            }
        } else {
            $this->sendMessage($chatId, "عذراً، لم أتمكن من التعرف على حسابك. يرجى الضغط على /start للبدء من جديد.");
        }
    }

    private function handleCallbackQuery(array $callbackQuery)
    {
        $chatId = $callbackQuery['message']['chat']['id'] ?? null;
        $data = $callbackQuery['data'] ?? '';
        $queryId = $callbackQuery['id'] ?? '';

        if (!$chatId) return;

        $user = User::where('telegram_chat_id', $chatId)->first();
        if (!$user || $user->role !== 'student') {
            $this->answerCallbackQuery($queryId, "يرجى تسجيل الدخول أولاً.");
            return;
        }

        // تحليل بيانات الزر المضغوط
        if (str_starts_with($data, 'course_lectures_')) {
            $courseId = str_replace('course_lectures_', '', $data);
            $this->handleCourseLectures($user, $chatId, $courseId);
            $this->answerCallbackQuery($queryId); // إخفاء علامة التحميل
        } elseif (str_starts_with($data, 'lesson_details_')) {
            $lessonId = str_replace('lesson_details_', '', $data);
            $this->handleLessonDetails($user, $chatId, $lessonId);
            $this->answerCallbackQuery($queryId);
        } elseif (str_starts_with($data, 'resource_details_')) {
            $resourceId = str_replace('resource_details_', '', $data);
            $this->handleResourceDetails($user, $chatId, $resourceId);
            $this->answerCallbackQuery($queryId);
        } elseif (str_starts_with($data, 'excuse_start_')) {
            $attendanceId = str_replace('excuse_start_', '', $data);
            Cache::put("telegram_state_{$chatId}", "awaiting_excuse_text_{$attendanceId}", 1800);
            $this->sendMessage($chatId, "📝 **تقديم عذر غياب**\n\nيرجى كتابة وتوضيح سبب الغياب بالتفصيل وإرساله هنا:");
            $this->answerCallbackQuery($queryId);
        } elseif (str_starts_with($data, 'leave_type_')) {
            $type = str_replace('leave_type_', '', $data);
            Cache::put("telegram_leave_type_{$chatId}", $type, 1800);
            Cache::put("telegram_state_{$chatId}", 'awaiting_leave_date', 1800);
            $this->sendMessage($chatId, "📅 **تاريخ الإجازة المطلوبة**\n\nيرجى إرسال تاريخ الإجازة (مثال: " . now()->addDay()->format('Y-m-d') . "):");
            $this->answerCallbackQuery($queryId);
        } elseif (str_starts_with($data, 'leave_hours_')) {
            $hours = str_replace('leave_hours_', '', $data);
            Cache::put("telegram_leave_hours_{$chatId}", $hours, 1800);
            Cache::put("telegram_state_{$chatId}", 'awaiting_leave_reason', 1800);
            $this->sendMessage($chatId, "✍️ **سبب طلب الإجازة**\n\nتم اختيار الفترة: ({$hours})\nيرجى كتابة وتوضيح سبب طلب الإجازة الساعية:");
            $this->answerCallbackQuery($queryId);
        }
    }

    // ==========================================
    // Auth Handlers
    // ==========================================

    private function handleLogout($user, $chatId, $stateKey)
    {
        if ($user) {
            $user->telegram_chat_id = null;
            $user->save();
        }
        Cache::forget($stateKey);
        Cache::forget("telegram_auth_{$chatId}_user_id");
        
        $this->sendMessage($chatId, "تم تسجيل الخروج بنجاح 👋\nللبدء من جديد وتسجيل الدخول بحساب آخر، اضغط على /start", ['remove_keyboard' => true]);
    }

    private function handleUniversityIdInput($chatId, $text)
    {
        $universityId = trim($text);
        $user = User::where('university_id', $universityId)->first();

        if (!$user) {
            $this->sendMessage($chatId, "❌ الرقم الجامعي غير صحيح. يرجى المحاولة مرة أخرى أو التأكد من إدخال الرقم باللغة الإنجليزية.");
            return;
        }

        if ($user->role !== 'student') {
            $this->sendMessage($chatId, "عذراً، هذا البوت مخصص للطلاب حالياً.");
            Cache::forget("telegram_state_{$chatId}");
            return;
        }

        Cache::put("telegram_auth_{$chatId}_user_id", $user->user_id, 3600);
        Cache::put("telegram_state_{$chatId}", 'awaiting_password', 3600);
        
        $this->sendMessage($chatId, "ممتاز! تم العثور على الحساب باسم ({$user->full_name}).\nالآن، يرجى إدخال **كلمة المرور** الخاصة بحسابك:");
    }

    private function handlePasswordInput($chatId, $text)
    {
        $userId = Cache::get("telegram_auth_{$chatId}_user_id");
        if (!$userId) {
            $this->sendMessage($chatId, "انتهت مهلة التسجيل. يرجى البدء من جديد عبر /start.");
            Cache::forget("telegram_state_{$chatId}");
            return;
        }

        $user = User::find($userId);
        if (!$user) return;

        if (Hash::check($text, $user->password)) {
            // Success! Link telegram_chat_id
            $user->telegram_chat_id = $chatId;
            $user->save();

            Cache::forget("telegram_state_{$chatId}");
            Cache::forget("telegram_auth_{$chatId}_user_id");

            // Delete the password message for security if possible (optional)
            // Telegram API supports deleteMessage but we need message_id

            $this->sendStudentMainMenu($chatId, "✅ **تم تسجيل الدخول وربط حسابك بنجاح!**\nمرحباً بك **{$user->full_name}** 🎓");
        } else {
            $this->sendMessage($chatId, "❌ كلمة المرور غير صحيحة. يرجى المحاولة مرة أخرى:");
        }
    }

    // ==========================================
    // Student Services Handlers
    // ==========================================

    private function sendStudentMainMenu($chatId, $headerText = null)
    {
        $keyboard = [
            'keyboard' => [
                [['text' => '📅 جدولي'], ['text' => '💯 علاماتي']],
                [['text' => '🛑 غياباتي'], ['text' => '📚 محاضراتي']],
                [['text' => '✈️ طلب إجازة'], ['text' => '📷 تسجيل حضور']],
                [['text' => '🚪 تسجيل خروج']]
            ],
            'resize_keyboard' => true,
            'one_time_keyboard' => false
        ];

        $servicesList = "📋 **الخدمات المتاحة لك عبر البوت:**\n"
            . "• 📅 **جدولي**: استعراض جدول المحاضرات الأسبوعي والقاعات\n"
            . "• 💯 **علاماتي**: استعراض كافة درجاتك وامتحاناتك المسجلة\n"
            . "• 🛑 **غياباتي**: متابعة نسبة الحضور والإنذارات وتقديم الأعذار\n"
            . "• 📚 **محاضراتي**: تصفح المواد والمحاضرات والملفات المرفقة\n"
            . "• ✈️ **طلب إجازة**: تقديم إذن غياب يومي أو ساعي لولي الأمر\n"
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
        $msg .= "أو أرسل أمر /skip_photo للمتابعة بدون إرفاق صورة.";

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
            Notification::create([
                'user_id'    => 1, // الإدارة
                'sender_id'  => $attendance->student->user->user_id ?? 1,
                'title'      => 'عذر غياب جديد بحاجة للمراجعة',
                'message'    => "قام الطالب ({$studentName}) بتقديم عذر لغيابه في مادة ({$attendance->lesson->course->title})، يرجى مراجعته.",
                'type'       => 'excuse_request',
                'category'   => 'administrative',
                'related_id' => $attendance->attendance_id,
                'is_read'    => false,
            ]);
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

        $type = Cache::get("telegram_leave_type_{$chatId}", 'full_day');
        $date = Cache::get("telegram_leave_date_{$chatId}", now()->toDateString());
        $hours = Cache::get("telegram_leave_hours_{$chatId}", '');
        $reason = trim($text);

        $reasonText = $type === 'hourly'
            ? "[إذن ساعي: {$hours}] - {$reason}"
            : "[إذن يومي] - {$reason}";

        try {
            $requestId = DB::table('absence_requests')->insertGetId([
                'student_id' => $student->student_id,
                'reason'     => $reasonText,
                'date'       => $date,
                'status'     => 'pending_parent',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // إشعار ولي الأمر للموافقة
            $parentUserId = DB::table('parent_students')
                ->where('student_id', $student->student_id)
                ->join('parents', 'parent_students.parent_id', '=', 'parents.parent_id')
                ->value('parents.user_id');

            if ($parentUserId) {
                $studentName = $user->full_name ?? 'ابنكم';
                $pTitle = 'طلب إذن جديد من الابن';
                $pMsg = "قام ابنكم {$studentName} بتقديم طلب إذن غياب بتاريخ {$date} عبر التيليغرام، يرجى مراجعته والموافقة عليه.";

                Notification::create([
                    'user_id'    => $parentUserId,
                    'sender_id'  => $user->user_id,
                    'title'      => $pTitle,
                    'message'    => $pMsg,
                    'type'       => 'leave_request',
                    'category'   => 'administrative',
                    'related_id' => $requestId,
                    'is_read'    => false,
                ]);

                FcmService::sendToUser($parentUserId, $pTitle, $pMsg, [
                    'type'       => 'leave_request',
                    'related_id' => (string)$requestId,
                ]);
            }

            Cache::forget("telegram_state_{$chatId}");
            Cache::forget("telegram_leave_type_{$chatId}");
            Cache::forget("telegram_leave_date_{$chatId}");
            Cache::forget("telegram_leave_hours_{$chatId}");

            $this->sendMessage($chatId, "✅ **تم تقديم طلب الإجازة بنجاح!**\n\n📌 **النوع:** " . ($type === 'hourly' ? "إجازة ساعية ({$hours})" : "إجازة يوم كامل") . "\n📅 **التاريخ:** {$date}\n📝 **السبب:** {$reason}\n\n📨 تم إرسال الطلب إلى ولي أمرك للموافقة عليه أولاً.");
        } catch (\Exception $e) {
            Log::error("Failed to submit leave request from Telegram: " . $e->getMessage());
            $this->sendMessage($chatId, "❌ حدث خطأ أثناء حفظ طلب الإجازة. يرجى المحاولة مرة أخرى لاحقاً.");
        }
    }

    private function handleQrAttendanceMenu($chatId)
    {
        $domain = env('APP_URL', 'https://edubridge-attend.loca.lt');
        if (!str_starts_with($domain, 'https://')) {
            $domain = 'https://edubridge-attend.loca.lt';
        }
        $scannerUrl = rtrim($domain, '/') . '/telegram/scanner?chat_id=' . $chatId;

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

    // ==========================================
    // Telegram API Helpers
    // ==========================================

    public function sendMessage($chatId, $text, $replyMarkup = null, $inlineMarkup = null, $parseMode = 'Markdown')
    {
        $payload = [
            'chat_id' => $chatId,
            'text' => $text,
            'parse_mode' => $parseMode,
        ];

        if ($replyMarkup) {
            $payload['reply_markup'] = json_encode($replyMarkup);
        } elseif ($inlineMarkup) {
            $payload['reply_markup'] = json_encode($inlineMarkup);
        }

        try {
            $res = Http::post("{$this->apiUrl}/sendMessage", $payload);
            if (!$res->successful()) {
                Log::error('Telegram API error response: ' . $res->body());
            }
        } catch (\Exception $e) {
            Log::error('Telegram sendMessage error: ' . $e->getMessage());
        }
    }

    private function answerCallbackQuery($callbackQueryId, $text = null)
    {
        $payload = ['callback_query_id' => $callbackQueryId];
        if ($text) {
            $payload['text'] = $text;
            $payload['show_alert'] = true;
        }

        try {
            Http::post("{$this->apiUrl}/answerCallbackQuery", $payload);
        } catch (\Exception $e) {
            Log::error('Telegram answerCallbackQuery error: ' . $e->getMessage());
        }
    }

    private function sendDocument($chatId, $filePath, $caption = null, $originalName = null)
    {
        // بافتراض أن الملفات محفوظة في storage/app/public/
        $fullPath = storage_path('app/public/' . ltrim($filePath, '/'));
        
        if (!file_exists($fullPath)) {
            Log::error("Telegram sendDocument error: File not found at {$fullPath}");
            $this->sendMessage($chatId, "عذراً، لم يتم العثور على الملف المطلوب في السيرفر.");
            return;
        }

        $filename = $originalName ?? basename($fullPath);

        try {
            Http::attach(
                'document', file_get_contents($fullPath), $filename
            )->post("{$this->apiUrl}/sendDocument", [
                'chat_id' => $chatId,
                'caption' => $caption,
                'parse_mode' => 'Markdown'
            ]);
        } catch (\Exception $e) {
            Log::error('Telegram sendDocument error: ' . $e->getMessage());
        }
    }
}
