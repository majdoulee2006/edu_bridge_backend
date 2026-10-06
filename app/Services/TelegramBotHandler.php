<?php

namespace App\Services;

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
use App\Services\FcmService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

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
            // 🔓 كل الأدوار هلق فيها تربط حسابها، عشان
            // تقدر تستقبل رموز التحقق (OTP) أو قوائم الخدمات عبر البوت.
            if ($user) {
                if ($user->role === 'student') {
                    $this->sendStudentMainMenu($chatId, "مرحباً مجدداً **{$user->full_name}** 🎓");
                } elseif ($user->role === 'parent') {
                    $this->sendParentMainMenu($chatId, "مرحباً مجدداً **{$user->full_name}** 👨‍👩‍👧‍👦");
                } elseif ($user->role === 'teacher') {
                    $this->sendTeacherMainMenu($chatId, "مرحباً مجدداً الأستاذ/ة **{$user->full_name}** 👨‍🏫");
                } else {
                    $this->sendMessage($chatId, "مرحباً مجدداً **{$user->full_name}** 👋\nحسابك مربوط بالفعل، وأي رمز تحقق (OTP) رح يوصلك هون تلقائياً.");
                }
            } else {
                $this->sendMessage($chatId, "مرحباً بك في البوت الرسمي لـ Edu Bridge 🎓\nللبدء، يرجى إدخال **اسم المستخدم / البريد الإلكتروني / رقم الهاتف / الرقم الجامعي** الخاص بحسابك:");
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

        // معالجة تقديم طلب إجازة للطالب
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

        // معالجة تقديم طلب إجازة للمعلم
        if ($state === 'awaiting_teacher_leave_date') {
            $this->handleTeacherLeaveDate($chatId, $text);
            return;
        }

        if ($state === 'awaiting_teacher_leave_hours') {
            $this->handleTeacherLeaveHours($chatId, $text);
            return;
        }

        if ($state === 'awaiting_teacher_leave_reason') {
            $this->handleTeacherLeaveReason($user, $chatId, $text);
            return;
        }

        // معالجة إنشاء ونشر واجب جديد من المعلم
        if ($state && str_starts_with($state, 'awaiting_teacher_assign_title_')) {
            $this->handleTeacherAssignTitleInput($chatId, $text, $state);
            return;
        }

        if ($state && str_starts_with($state, 'awaiting_teacher_assign_due_')) {
            $this->handleTeacherAssignDueInput($chatId, $text, $state);
            return;
        }

        if ($state && str_starts_with($state, 'awaiting_teacher_assign_points_')) {
            $this->handleTeacherAssignPointsInput($user, $chatId, $text, $state);
            return;
        }

        // معالجة تصحيح ورصد علامة الواجب للطالب
        if ($state && str_starts_with($state, 'awaiting_teacher_sub_grade_')) {
            $this->handleTeacherGradeSubmissionInput($user, $chatId, $text, $state);
            return;
        }

        // معالجة رفع محاضرة أو ملف جديد من المعلم
        if ($state && str_starts_with($state, 'awaiting_teacher_lesson_title_')) {
            $this->handleTeacherLessonTitleInput($chatId, $text, $state);
            return;
        }

        if ($state && str_starts_with($state, 'awaiting_teacher_lesson_file_')) {
            $this->handleTeacherLessonFileInput($user, $chatId, $message, $state);
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
        } elseif ($user && $user->role === 'parent') {
            if (str_contains($text, 'علامات')) {
                $this->handleParentGrades($user, $chatId);
            } elseif (str_contains($text, 'غياب')) {
                $this->handleParentAttendance($user, $chatId);
            } elseif (str_contains($text, 'أبنائي') || str_contains($text, 'ابنائي')) {
                $this->handleParentChildren($user, $chatId);
            } elseif (str_contains($text, 'خروج')) {
                $this->handleLogout($user, $chatId, $stateKey);
            } else {
                $this->sendParentMainMenu($chatId);
            }
        } elseif ($user && $user->role === 'teacher') {
            if (str_contains($text, 'جدول')) {
                $this->handleTeacherSchedule($user, $chatId);
            } elseif (str_contains($text, 'مواد') || str_contains($text, 'طلاب')) {
                $this->handleTeacherCourses($user, $chatId);
            } elseif (str_contains($text, 'حضور') || str_contains($text, 'جلسة') || str_contains($text, 'qr') || str_contains($text, 'QR')) {
                $this->handleTeacherAttendanceMenu($user, $chatId);
            } elseif (str_contains($text, 'إنشاء واجب') || str_contains($text, 'انشاء واجب')) {
                $this->handleTeacherCreateAssignmentChooseCourse($user, $chatId);
            } elseif (str_contains($text, 'رفع محاضرة') || str_contains($text, 'رفع ملف') || str_contains($text, 'محاضرة')) {
                $this->handleTeacherUploadLessonChooseCourse($user, $chatId);
            } elseif (str_contains($text, 'واجب') || str_contains($text, 'تسليم')) {
                $this->handleTeacherAssignments($user, $chatId);
            } elseif (str_contains($text, 'علامات') || str_contains($text, 'درجات') || str_contains($text, 'امتحان')) {
                $this->handleTeacherGrades($user, $chatId);
            } elseif (str_contains($text, 'أعذار') || str_contains($text, 'اعذار') || str_contains($text, 'عذر')) {
                $this->handleTeacherExcuses($user, $chatId);
            } elseif (str_contains($text, 'إجازة') || str_contains($text, 'اجازة')) {
                $this->handleTeacherLeaveMenu($chatId);
            } elseif (str_contains($text, 'خروج')) {
                $this->handleLogout($user, $chatId, $stateKey);
            } else {
                $this->sendTeacherMainMenu($chatId);
            }
        } elseif ($user) {
            $this->sendMessage($chatId, "مرحباً **{$user->full_name}** 👋\nحسابك مربوط، ورح توصلك رموز التحقق (OTP) هون تلقائياً وقت الحاجة.");
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
        if (!$user || !in_array($user->role, ['student', 'parent', 'teacher'])) {
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
        } elseif (str_starts_with($data, 'parent_student_grades_')) {
            $studentId = str_replace('parent_student_grades_', '', $data);
            $this->processParentStudentGrades($user, $chatId, $studentId);
            $this->answerCallbackQuery($queryId);
        } elseif (str_starts_with($data, 'parent_student_attendance_')) {
            $studentId = str_replace('parent_student_attendance_', '', $data);
            $this->processParentStudentAttendance($user, $chatId, $studentId);
            $this->answerCallbackQuery($queryId);
        }
        // معالجات أزرار المعلم (Teacher Callbacks)
        elseif ($data === 'teacher_full_weekly_schedule') {
            $this->handleTeacherWeeklySchedule($user, $chatId);
            $this->answerCallbackQuery($queryId);
        } elseif (str_starts_with($data, 'teacher_course_detail_')) {
            $courseId = str_replace('teacher_course_detail_', '', $data);
            $this->handleTeacherCourseDetail($user, $chatId, (int)$courseId);
            $this->answerCallbackQuery($queryId);
        } elseif (str_starts_with($data, 'teacher_course_students_')) {
            $courseId = str_replace('teacher_course_students_', '', $data);
            $this->handleTeacherCourseStudents($user, $chatId, (int)$courseId);
            $this->answerCallbackQuery($queryId);
        } elseif (str_starts_with($data, 'teacher_course_start_attendance_')) {
            $courseId = str_replace('teacher_course_start_attendance_', '', $data);
            $this->handleTeacherStartAttendance($user, $chatId, (int)$courseId);
            $this->answerCallbackQuery($queryId);
        } elseif (str_starts_with($data, 'teacher_session_stats_')) {
            $sessionId = str_replace('teacher_session_stats_', '', $data);
            $this->handleTeacherSessionStats($user, $chatId, (int)$sessionId);
            $this->answerCallbackQuery($queryId);
        } elseif (str_starts_with($data, 'teacher_end_session_')) {
            $sessionId = str_replace('teacher_end_session_', '', $data);
            $this->handleTeacherEndSession($user, $chatId, (int)$sessionId);
            $this->answerCallbackQuery($queryId);
        } elseif (str_starts_with($data, 'teacher_course_assignments_')) {
            $courseId = str_replace('teacher_course_assignments_', '', $data);
            $this->handleTeacherCourseAssignments($user, $chatId, (int)$courseId);
            $this->answerCallbackQuery($queryId);
        } elseif (str_starts_with($data, 'teacher_assignment_submissions_')) {
            $assignmentId = str_replace('teacher_assignment_submissions_', '', $data);
            $this->handleTeacherAssignmentSubmissions($user, $chatId, (int)$assignmentId);
            $this->answerCallbackQuery($queryId);
        } elseif (str_starts_with($data, 'teacher_course_exams_')) {
            $courseId = str_replace('teacher_course_exams_', '', $data);
            $this->handleTeacherCourseExams($user, $chatId, (int)$courseId);
            $this->answerCallbackQuery($queryId);
        } elseif (str_starts_with($data, 'teacher_exam_grades_')) {
            $examId = str_replace('teacher_exam_grades_', '', $data);
            $this->handleTeacherExamGrades($user, $chatId, (int)$examId);
            $this->answerCallbackQuery($queryId);
        } elseif (str_starts_with($data, 'teacher_course_excuses_')) {
            $courseId = str_replace('teacher_course_excuses_', '', $data);
            $this->handleTeacherCourseExcuses($user, $chatId, (int)$courseId);
            $this->answerCallbackQuery($queryId);
        } elseif (str_starts_with($data, 'teacher_excuse_detail_')) {
            $attendanceId = str_replace('teacher_excuse_detail_', '', $data);
            $this->handleTeacherExcuseDetail($user, $chatId, (int)$attendanceId);
            $this->answerCallbackQuery($queryId);
        } elseif (str_starts_with($data, 'teacher_leave_type_')) {
            $type = str_replace('teacher_leave_type_', '', $data);
            Cache::put("telegram_teacher_leave_type_{$chatId}", $type, 1800);
            Cache::put("telegram_state_{$chatId}", 'awaiting_teacher_leave_date', 1800);
            $this->sendMessage($chatId, "📅 **تاريخ الإجازة المطلوبة**\n\nيرجى إرسال تاريخ الإجازة (مثال: " . now()->addDay()->format('Y-m-d') . "):");
            $this->answerCallbackQuery($queryId);
        } elseif (str_starts_with($data, 'teacher_leave_hours_')) {
            $hours = str_replace('teacher_leave_hours_', '', $data);
            Cache::put("telegram_teacher_leave_hours_{$chatId}", $hours, 1800);
            Cache::put("telegram_state_{$chatId}", 'awaiting_teacher_leave_reason', 1800);
            $this->sendMessage($chatId, "✍️ **سبب طلب الإجازة**\n\nتم اختيار الفترة: ({$hours})\nيرجى كتابة وتوضيح سبب طلب الإجازة الساعية:");
            $this->answerCallbackQuery($queryId);
        }
        // معالجات إنشاء الواجب وتصحيحه ورفع المحاضرات
        elseif ($data === 'teacher_create_assignment_start') {
            $this->handleTeacherCreateAssignmentChooseCourse($user, $chatId);
            $this->answerCallbackQuery($queryId);
        } elseif (str_starts_with($data, 'teacher_create_assign_course_')) {
            $courseId = str_replace('teacher_create_assign_course_', '', $data);
            $this->handleTeacherCreateAssignmentStart($user, $chatId, (int)$courseId);
            $this->answerCallbackQuery($queryId);
        } elseif (str_starts_with($data, 'teacher_assign_due_preset_')) {
            $parts = explode('_', str_replace('teacher_assign_due_preset_', '', $data)); // [courseId, days]
            $courseId = (int)($parts[0] ?? 0);
            $days = (int)($parts[1] ?? 7);
            $dueDate = now()->addDays($days)->format('Y-m-d');
            $this->handleTeacherAssignDueInput($chatId, $dueDate, "awaiting_teacher_assign_due_{$courseId}");
            $this->answerCallbackQuery($queryId);
        } elseif (str_starts_with($data, 'teacher_assign_points_preset_')) {
            $parts = explode('_', str_replace('teacher_assign_points_preset_', '', $data)); // [courseId, points]
            $courseId = (int)($parts[0] ?? 0);
            $points = (string)($parts[1] ?? 20);
            $this->handleTeacherAssignPointsInput($user, $chatId, $points, "awaiting_teacher_assign_points_{$courseId}");
            $this->answerCallbackQuery($queryId);
        } elseif (str_starts_with($data, 'teacher_grade_sub_')) {
            $subId = str_replace('teacher_grade_sub_', '', $data);
            $this->handleTeacherStartGradeSubmission($user, $chatId, (int)$subId);
            $this->answerCallbackQuery($queryId);
        } elseif (str_starts_with($data, 'teacher_upload_lesson_start_')) {
            $courseId = str_replace('teacher_upload_lesson_start_', '', $data);
            $this->handleTeacherUploadLessonStart($user, $chatId, (int)$courseId);
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
        // 🔓 نفس منطق تحديد الهوية المستخدم بتسجيل الدخول الرئيسي بالتطبيق
        // (AuthController@login) بالظبط، بدل الاقتصار على الرقم الجامعي بس
        // — هيك ولي الأمر والمعلم ورئيس القسم والإدارة (يلي ما إلهم رقم
        // جامعي) فيهم يربطوا حسابهم كمان عبر اسم المستخدم أو الإيميل أو الهاتف.
        $input = trim($text);
        $digitsOnly = preg_replace('/[^0-9]/', '', $input);

        $user = User::where(function ($q) use ($input, $digitsOnly) {
                $q->where('username', $input)
                  ->orWhere('email', $input)
                  ->orWhere('phone', $input)
                  ->orWhere('university_id', $input)
                  ->orWhereHas('student', function ($sq) use ($input) {
                      $sq->where('student_code', $input);
                  });
                if (!empty($digitsOnly)) {
                    $q->orWhere('university_id', $digitsOnly)
                      ->orWhere('phone', '+' . $digitsOnly)
                      ->orWhereRaw("REPLACE(REPLACE(phone, '+', ''), ' ', '') = ?", [$digitsOnly]);
                }
            })
            ->first();

        if (!$user) {
            $this->sendMessage($chatId, "❌ لم يتم العثور على حساب بهذه البيانات. تأكد من إدخال اسم المستخدم أو الإيميل أو رقم الهاتف أو الرقم الجامعي بشكل صحيح.");
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

            if ($user->role === 'student') {
                $this->sendStudentMainMenu($chatId, "✅ **تم تسجيل الدخول وربط حسابك بنجاح!**\nمرحباً بك **{$user->full_name}** 🎓");
            } elseif ($user->role === 'parent') {
                $this->sendParentMainMenu($chatId, "✅ **تم تسجيل الدخول وربط حسابك بنجاح!**\nمرحباً بك **{$user->full_name}** 👨‍👩‍👧‍👦");
            } elseif ($user->role === 'teacher') {
                $this->sendTeacherMainMenu($chatId, "✅ **تم تسجيل الدخول وربط حسابك بنجاح!**\nمرحباً بك الأستاذ/ة **{$user->full_name}** 👨‍🏫");
            } else {
                $this->sendMessage($chatId, "✅ **تم ربط حسابك بنجاح!**\nمرحباً بك **{$user->full_name}** 🎓\n\nمن الآن، أي رمز تحقق (OTP) — لتغيير كلمة السر أو البريد أو رقم الهاتف — رح يوصلك مباشرة هون على هالمحادثة.");
            }
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

            // إشعار ولي الأمر للموافقة — يدعم الربط عبر student_id أو user_id
            $parentUserId = DB::table('parent_students')
                ->where(function($q) use ($student) {
                    $q->where('student_id', $student->user_id)
                      ->orWhere('student_id', $student->student_id);
                })
                ->join('parents', function($j) {
                    $j->on('parent_students.parent_id', '=', 'parents.user_id')
                      ->orOn('parent_students.parent_id', '=', 'parents.parent_id');
                })
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

    public function sendPhoto($chatId, $photoUrl, $caption = null, $inlineMarkup = null, $parseMode = 'Markdown')
    {
        $payload = [
            'chat_id' => $chatId,
            'photo' => $photoUrl,
            'caption' => $caption,
            'parse_mode' => $parseMode,
        ];

        if ($inlineMarkup) {
            $payload['reply_markup'] = json_encode($inlineMarkup);
        }

        try {
            $res = Http::post("{$this->apiUrl}/sendPhoto", $payload);
            if (!$res->successful()) {
                Log::error('Telegram sendPhoto error: ' . $res->body());
                $this->sendMessage($chatId, ($caption ? "{$caption}\n\n" : "") . "🔗 رابط الـ QR: {$photoUrl}", null, $inlineMarkup);
            }
        } catch (\Exception $e) {
            Log::error('Telegram sendPhoto exception: ' . $e->getMessage());
            $this->sendMessage($chatId, ($caption ? "{$caption}\n\n" : "") . "🔗 رابط الـ QR: {$photoUrl}", null, $inlineMarkup);
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

    // ==========================================
    // Parent Services Handlers
    // ==========================================

    private function sendParentMainMenu($chatId, $headerText = null)
    {
        $keyboard = [
            'keyboard' => [
                [['text' => '👨‍👦 أبنائي'], ['text' => '💯 علامات أبنائي']],
                [['text' => '🛑 غيابات أبنائي']],
                [['text' => '🚪 تسجيل خروج']]
            ],
            'resize_keyboard' => true,
            'one_time_keyboard' => false
        ];

        $servicesList = "📋 **الخدمات المتاحة لك كولي أمر عبر البوت:**\n"
            . "• 👨‍👦 **أبنائي**: استعراض قائمة أبنائك المسجلين بالمعهد\n"
            . "• 💯 **علامات أبنائي**: استعراض درجات أبنائك وامتحاناتهم\n"
            . "• 🛑 **غيابات أبنائي**: متابعة نسبة حضور أبنائك والإنذارات\n"
            . "• 🚪 **تسجيل خروج**: فك ربط الحساب من هذا الجهاز\n\n"
            . "👇 اختر الخدمة المطلوبة من الأزرار أدناه:";

        $fullText = $headerText ? "{$headerText}\n\n{$servicesList}" : $servicesList;

        $this->sendMessage($chatId, $fullText, $keyboard);
    }

    private function getParentChildren(User $user)
    {
        $children = collect();
        if ($user->parent) {
            $children = $user->parent->students()->with('user')->get();
        }

        if ($children->isEmpty()) {
            $parentRecord = DB::table('parents')->where('user_id', $user->user_id)->first();
            $parentIds = array_filter([$user->user_id, $parentRecord?->parent_id ?? null]);

            $studentUserIds = DB::table('parent_students')
                ->whereIn('parent_id', $parentIds)
                ->pluck('student_id');

            if ($studentUserIds->isNotEmpty()) {
                $children = Student::where(function($q) use ($studentUserIds) {
                    $q->whereIn('user_id', $studentUserIds)
                      ->orWhereIn('student_id', $studentUserIds);
                })->with('user')->get();
            }
        }

        return $children;
    }


    private function handleParentChildren(User $user, $chatId)
    {
        $children = $this->getParentChildren($user);
        
        if ($children->isEmpty()) {
            $this->sendMessage($chatId, "لا يوجد أبناء مسجلين بحسابك حالياً.");
            return;
        }

        $message = "👨‍👦 **قائمة أبنائك:**\n\n";
        foreach ($children as $child) {
            $childUser = $child->user;
            $message .= "🔹 **{$childUser->full_name}**\n";
            $message .= "التخصص: {$childUser->branch}\n";
            $message .= "السنة: {$childUser->academic_year}\n";
            $message .= "─────────────\n";
        }

        $this->sendMessage($chatId, $message);
    }

    private function handleParentGrades(User $user, $chatId)
    {
        $children = $this->getParentChildren($user);
        
        if ($children->isEmpty()) {
            $this->sendMessage($chatId, "لا يوجد أبناء مسجلين بحسابك حالياً.");
            return;
        }

        $keyboard = ['inline_keyboard' => []];
        foreach ($children as $child) {
            $keyboard['inline_keyboard'][] = [
                ['text' => "💯 علامات " . ($child->user->first_name ?? $child->user->full_name ?? 'الابن'), 'callback_data' => "parent_student_grades_{$child->student_id}"]
            ];
        }

        $this->sendMessage($chatId, "يرجى اختيار الابن لعرض علاماته:", null, $keyboard);
    }

    private function handleParentAttendance(User $user, $chatId)
    {
        $children = $this->getParentChildren($user);
        
        if ($children->isEmpty()) {
            $this->sendMessage($chatId, "لا يوجد أبناء مسجلين بحسابك حالياً.");
            return;
        }

        $keyboard = ['inline_keyboard' => []];
        foreach ($children as $child) {
            $keyboard['inline_keyboard'][] = [
                ['text' => "🛑 غيابات " . ($child->user->first_name ?? $child->user->full_name ?? 'الابن'), 'callback_data' => "parent_student_attendance_{$child->student_id}"]
            ];
        }

        $this->sendMessage($chatId, "يرجى اختيار الابن لعرض تقرير دوامه:", null, $keyboard);
    }

    private function processParentStudentGrades(User $parentUser, $chatId, $studentId)
    {
        $student = Student::with('user')->find($studentId);
        if (!$student || !$student->user) return;
        
        $this->sendMessage($chatId, "👨‍👦 **تقرير علامات: {$student->user->full_name}**");
        $this->handleGrades($student->user, $chatId);
    }

    private function processParentStudentAttendance(User $parentUser, $chatId, $studentId)
    {
        $student = Student::with('user')->find($studentId);
        if (!$student || !$student->user) return;
        
        $this->sendMessage($chatId, "👨‍👦 **تقرير دوام: {$student->user->full_name}**");
        $this->handleAttendance($student->user, $chatId);
    }

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
            $hodUserIds->where('department', 'LIKE', "%{$dept}%");
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

    // ==========================================
    // Teacher Assignment Creation & Grading
    // ==========================================

    private function handleTeacherCreateAssignmentChooseCourse(User $user, $chatId)
    {
        $courses = $this->getTeacherCourses($user);
        if ($courses->isEmpty()) {
            $this->sendMessage($chatId, "📚 لا توجد مواد مسندة لك لإنشاء واجبات.");
            return;
        }

        $keyboard = ['inline_keyboard' => []];
        foreach ($courses as $c) {
            $keyboard['inline_keyboard'][] = [
                ['text' => "📝 إنشاء واجب لمادة: {$c->title}", 'callback_data' => "teacher_create_assign_course_{$c->course_id}"]
            ];
        }

        $this->sendMessage($chatId, "📝 **إنشاء ونشر واجب جديد** 🎓\n\nيرجى اختيار المادة المراد إضافة الواجب لها:", null, $keyboard);
    }

    private function handleTeacherCreateAssignmentStart(User $user, $chatId, int $courseId)
    {
        $course = Course::find($courseId);
        if (!$course) return;

        Cache::put("telegram_teacher_assign_course_{$chatId}", $courseId, 1800);
        Cache::put("telegram_state_{$chatId}", "awaiting_teacher_assign_title_{$courseId}", 1800);

        $this->sendMessage($chatId, "📝 **إنشاء واجب جديد لمادة: {$course->title}**\n\nيرجى كتابة عنوان وتفاصيل الواجب (مثال: `حل تمارين الوحدة الثالثة صفحة 45`):");
    }

    private function handleTeacherAssignTitleInput($chatId, $text, $state)
    {
        $courseId = (int)str_replace('awaiting_teacher_assign_title_', '', $state);
        $title = trim($text);

        Cache::put("telegram_teacher_assign_title_{$chatId}", $title, 1800);
        Cache::put("telegram_state_{$chatId}", "awaiting_teacher_assign_due_{$courseId}", 1800);

        $keyboard = [
            'inline_keyboard' => [
                [
                    ['text' => '📅 بعد 3 أيام', 'callback_data' => "teacher_assign_due_preset_{$courseId}_3"],
                    ['text' => '📅 بعد أسبوع', 'callback_data' => "teacher_assign_due_preset_{$courseId}_7"],
                ],
                [
                    ['text' => '📅 بعد 10 أيام', 'callback_data' => "teacher_assign_due_preset_{$courseId}_10"],
                    ['text' => '📅 بعد أسبوعين', 'callback_data' => "teacher_assign_due_preset_{$courseId}_14"],
                ]
            ]
        ];

        $this->sendMessage(
            $chatId,
            "📅 **موعد تسليم الواجب (Due Date)**\n\nتم حفظ العنوان: **{$title}**\n\nاختر المدة من الأزرار أو اكتب التاريخ بصيغة YYYY-MM-DD (مثال: " . now()->addDays(7)->format('Y-m-d') . "):",
            null,
            $keyboard
        );
    }

    private function handleTeacherAssignDueInput($chatId, $text, $state)
    {
        $courseId = (int)str_replace('awaiting_teacher_assign_due_', '', $state);
        $time = strtotime(trim($text));

        if (!$time) {
            $this->sendMessage($chatId, "❌ التاريخ غير صحيح. يرجى إرسال تاريخ صالح بصيغة YYYY-MM-DD (مثال: " . now()->addDays(7)->format('Y-m-d') . "):");
            return;
        }

        $dueDate = date('Y-m-d 23:59:59', $time);
        Cache::put("telegram_teacher_assign_due_{$chatId}", $dueDate, 1800);
        Cache::put("telegram_state_{$chatId}", "awaiting_teacher_assign_points_{$courseId}", 1800);

        $keyboard = [
            'inline_keyboard' => [
                [
                    ['text' => '💯 10 درجات', 'callback_data' => "teacher_assign_points_preset_{$courseId}_10"],
                    ['text' => '💯 20 درجة', 'callback_data' => "teacher_assign_points_preset_{$courseId}_20"],
                ],
                [
                    ['text' => '💯 50 درجة', 'callback_data' => "teacher_assign_points_preset_{$courseId}_50"],
                    ['text' => '💯 100 درجة', 'callback_data' => "teacher_assign_points_preset_{$courseId}_100"],
                ]
            ]
        ];

        $displayDate = date('Y-m-d', $time);
        $this->sendMessage(
            $chatId,
            "💯 **الدرجة الكلية للواجب (Max Score)**\n\nموعد التسليم: `{$displayDate}`\n\nاختر الدرجة من الأزرار أو اكتب الرقم المطلوب:",
            null,
            $keyboard
        );
    }

    private function handleTeacherAssignPointsInput(User $user, $chatId, $text, $state)
    {
        $courseId = (int)str_replace('awaiting_teacher_assign_points_', '', $state);
        $points = (float)trim($text);

        if ($points <= 0) {
            $this->sendMessage($chatId, "❌ يرجى إدخال درجة موجبة أكبر من الصفر:");
            return;
        }

        $course = Course::with('students.user')->find($courseId);
        if (!$course) {
            $this->sendMessage($chatId, "❌ المادة غير موجودة.");
            Cache::forget("telegram_state_{$chatId}");
            return;
        }

        $title = Cache::get("telegram_teacher_assign_title_{$chatId}", 'واجب جديد');
        $dueDate = Cache::get("telegram_teacher_assign_due_{$chatId}", now()->addDays(7)->toDateTimeString());
        $teacher = $user->teacher;

        $assignment = Assignment::create([
            'course_id'   => $courseId,
            'teacher_id'  => $teacher?->teacher_id,
            'title'       => $title,
            'description' => "تم الإنشاء عبر بوت التيليغرام بواسطة الأستاذ {$user->full_name}",
            'due_date'    => $dueDate,
            'max_points'  => $points,
        ]);

        Cache::forget("telegram_state_{$chatId}");
        Cache::forget("telegram_teacher_assign_title_{$chatId}");
        Cache::forget("telegram_teacher_assign_due_{$chatId}");
        Cache::forget("telegram_teacher_assign_course_{$chatId}");

        // إشعار جميع طلاب المادة
        $students = $course->students;
        $dueStr = date('Y-m-d', strtotime($dueDate));
        $notifTitle = "واجب جديد: {$course->title}";
        $notifMsg = "قام الأستاذ ({$user->full_name}) بنشر واجب جديد ({$title})، موعد التسليم: {$dueStr}، الدرجة: {$points}.";

        foreach ($students as $student) {
            if (!$student->user) continue;

            Notification::create([
                'user_id'    => $student->user->user_id,
                'sender_id'  => $user->user_id,
                'title'      => $notifTitle,
                'message'    => $notifMsg,
                'type'       => 'new_assignment',
                'category'   => 'academic',
                'related_id' => $assignment->assignment_id,
                'is_read'    => false,
            ]);

            FcmService::sendToUser($student->user->user_id, $notifTitle, $notifMsg, [
                'type' => 'new_assignment',
                'assignment_id' => (string)$assignment->assignment_id,
                'course_id' => (string)$courseId,
            ]);

            if ($student->user->telegram_chat_id) {
                $this->sendMessage($student->user->telegram_chat_id, "📝 **واجب جديد مسند إليك!** 🎓\n\n📘 **المادة:** {$course->title}\n📌 **العنوان:** {$title}\n📅 **موعد التسليم:** `{$dueStr}`\n💯 **الدرجة:** `{$points}`");
            }
        }

        $this->sendMessage(
            $chatId,
            "✅ **تم إنشاء ونشر الواجب بنجاح!** 🎓\n\n📘 **المادة:** {$course->title}\n📌 **العنوان:** {$title}\n📅 **موعد التسليم:** `{$dueStr}`\n💯 **الدرجة:** `{$points}`\n👥 **عدد الطلاب المستلمين:** `{$students->count()}` طالب"
        );
    }

    private function handleTeacherStartGradeSubmission(User $user, $chatId, int $submissionId)
    {
        $submission = AssignmentSubmission::with(['student.user', 'assignment.course'])->find($submissionId);
        if (!$submission) {
            $this->sendMessage($chatId, "❌ التسليم غير موجود.");
            return;
        }

        $stName = $submission->student->user->full_name ?? 'طالب';
        $assignTitle = $submission->assignment->title ?? 'واجب';
        $maxPoints = $submission->assignment->max_points ?? 20;
        $currentGrade = $submission->grade !== null ? "{$submission->grade} / {$maxPoints}" : "غير مرصودة بعد";

        Cache::put("telegram_state_{$chatId}", "awaiting_teacher_sub_grade_{$submissionId}", 1800);

        $msg = "📝 **تصحيح ورصد علامة الواجب** 🎓\n\n"
            . "👤 **الطالب:** {$stName}\n"
            . "📘 **الواجب:** {$assignTitle}\n"
            . "💯 **الدرجة الكلية للواجب:** `{$maxPoints}`\n"
            . "📊 **الدرجة الحالية:** `{$currentGrade}`\n";

        if ($submission->solution_text) {
            $msg .= "\n✍️ **حل الطالب:**\n\"{$submission->solution_text}\"\n";
        }

        if ($submission->student_notes) {
            $msg .= "\n💬 **ملاحظة الطالب:**\n\"{$submission->student_notes}\"\n";
        }

        if ($submission->feedback) {
            $msg .= "\n💬 **ملاحظاتك السابقة:**\n\"{$submission->feedback}\"\n";
        }

        $msg .= "\n👇 **يرجى إرسال الدرجة المستحقة (من 0 إلى {$maxPoints})**\n"
            . "يمكنك كتابة ملاحظات للمعلم بعد فاصلة (مثال: `18, حل ممتاز ومنظم` أو فقط `19`):";

        $this->sendMessage($chatId, $msg);

        // إذا كان هناك ملف مرفق من الطالب أرسله للمعلم ليفحصه
        if (!empty($submission->file_path)) {
            $this->sendDocument($chatId, $submission->file_path, "📎 ملف حل الطالب: {$stName}");
        }
    }

    private function handleTeacherGradeSubmissionInput(User $user, $chatId, $text, $state)
    {
        $submissionId = (int)str_replace('awaiting_teacher_sub_grade_', '', $state);
        $submission = AssignmentSubmission::with(['student.user', 'assignment.course'])->find($submissionId);
        if (!$submission) {
            $this->sendMessage($chatId, "❌ التسليم غير موجود.");
            Cache::forget("telegram_state_{$chatId}");
            return;
        }

        $maxPoints = $submission->assignment->max_points ?? 20;

        // تحليل النص (الدرجة والملاحظات)
        $parts = explode(',', $text, 2);
        if (count($parts) < 2) {
            $parts = explode('،', $text, 2);
        }

        $scoreInput = trim($parts[0]);
        $feedback = isset($parts[1]) ? trim($parts[1]) : null;

        if (!is_numeric($scoreInput)) {
            $this->sendMessage($chatId, "❌ يرجى إدخال رقم صحيح أو عشري للدرجة (بين 0 و {$maxPoints}). مثال: `18` أو `18, ممتاز`:");
            return;
        }

        $score = (float)$scoreInput;
        if ($score < 0 || $score > $maxPoints) {
            $this->sendMessage($chatId, "❌ الدرجة المدخلة ({$score}) خارج النطاق المسموح (0 إلى {$maxPoints}). يرجى إعادة الإدخال:");
            return;
        }

        $submission->grade = $score;
        if ($feedback) {
            $submission->feedback = $feedback;
        }
        $submission->save();

        Cache::forget("telegram_state_{$chatId}");

        $stName = $submission->student->user->full_name ?? 'طالب';
        $assignTitle = $submission->assignment->title ?? 'واجب';
        $courseTitle = $submission->assignment->course->title ?? 'مادة';

        // إشعار الطالب فوراً
        $studentUser = $submission->student->user ?? null;
        if ($studentUser) {
            $notifTitle = "تم تصحيح واجب: {$assignTitle}";
            $notifMsg = "قام الأستاذ ({$user->full_name}) برصد علامتك: ({$score} / {$maxPoints}) في مادة ({$courseTitle})." . ($feedback ? "\nملاحظات: {$feedback}" : "");

            Notification::create([
                'user_id'    => $studentUser->user_id,
                'sender_id'  => $user->user_id,
                'title'      => $notifTitle,
                'message'    => $notifMsg,
                'type'       => 'assignment_grade',
                'category'   => 'academic',
                'related_id' => $submission->assignment_id,
                'is_read'    => false,
            ]);

            FcmService::sendToUser($studentUser->user_id, $notifTitle, $notifMsg, [
                'type' => 'assignment_grade',
                'assignment_id' => (string)$submission->assignment_id
            ]);

            if ($studentUser->telegram_chat_id) {
                $this->sendMessage($studentUser->telegram_chat_id, "🔔 **تم تصحيح واجبك!** 🎓\n\n📘 **المادة:** {$courseTitle}\n📝 **الواجب:** {$assignTitle}\n💯 **علامتك:** `{$score} / {$maxPoints}`" . ($feedback ? "\n💬 **ملاحظة الأستاذ:** {$feedback}" : ""));
            }
        }

        $feedbackStr = $feedback ? "\n💬 **الملاحظات:** {$feedback}" : "";
        $this->sendMessage(
            $chatId,
            "✅ **تم رصد العلامة بنجاح!** 🎓\n\n👤 **الطالب:** {$stName}\n📘 **الواجب:** {$assignTitle}\n💯 **الدرجة المرصودة:** `{$score} / {$maxPoints}`{$feedbackStr}\n\n📨 تم إرسال إشعار فوري للطالب بالنتيجة."
        );
    }

    // ==========================================
    // Teacher Lecture & Resource Upload Handlers
    // ==========================================

    private function handleTeacherUploadLessonChooseCourse(User $user, $chatId)
    {
        $courses = $this->getTeacherCourses($user);
        if ($courses->isEmpty()) {
            $this->sendMessage($chatId, "📚 لا توجد مواد مسندة لك لرفع ملفات.");
            return;
        }

        $keyboard = ['inline_keyboard' => []];
        foreach ($courses as $c) {
            $keyboard['inline_keyboard'][] = [
                ['text' => "📤 رفع لمحاضرة مادة: {$c->title}", 'callback_data' => "teacher_upload_lesson_start_{$c->course_id}"]
            ];
        }

        $this->sendMessage($chatId, "📤 **رفع محاضرة أو ملف تعليمي جديد** 🎓\n\nيرجى اختيار المادة المراد إضافة الملف أو المحاضرة لها:", null, $keyboard);
    }

    private function handleTeacherUploadLessonStart(User $user, $chatId, int $courseId)
    {
        $course = Course::find($courseId);
        if (!$course) return;

        Cache::put("telegram_teacher_lesson_course_{$chatId}", $courseId, 1800);
        Cache::put("telegram_state_{$chatId}", "awaiting_teacher_lesson_title_{$courseId}", 1800);

        $this->sendMessage($chatId, "📤 **رفع محاضرة / ملف تعليمي لمادة: {$course->title}** 📚\n\nيرجى كتابة عنوان المحاضرة أو الملف (مثال: `المحاضرة 5 - مقدمة في هياكل البيانات`):");
    }

    private function handleTeacherLessonTitleInput($chatId, $text, $state)
    {
        $courseId = (int)str_replace('awaiting_teacher_lesson_title_', '', $state);
        $title = trim($text);

        Cache::put("telegram_teacher_lesson_title_{$chatId}", $title, 1800);
        Cache::put("telegram_state_{$chatId}", "awaiting_teacher_lesson_file_{$courseId}", 1800);

        $this->sendMessage(
            $chatId,
            "📎 **إرفاق الملف أو الرابط**\n\nتم حفظ العنوان: **{$title}**\n\nالآن يرجى إرسال **المستند / الملف (PDF, Word, PPTX, صورة)** مباشرة في المحادثة، أو إرسال **رابط المحاضرة / الفيديو** كنص:"
        );
    }

    private function handleTeacherLessonFileInput(User $user, $chatId, array $message, $state)
    {
        $courseId = (int)str_replace('awaiting_teacher_lesson_file_', '', $state);
        $course = Course::with('students.user')->find($courseId);
        if (!$course) {
            $this->sendMessage($chatId, "❌ المادة غير موجودة.");
            Cache::forget("telegram_state_{$chatId}");
            return;
        }

        $title = Cache::get("telegram_teacher_lesson_title_{$chatId}", 'محاضرة جديدة');
        $teacher = $user->teacher;

        $filePath = null;
        $contentUrl = null;
        $type = 'document';
        $fileSize = null;

        if (isset($message['document'])) {
            $doc = $message['document'];
            $fileId = $doc['file_id'] ?? null;
            $origName = $doc['file_name'] ?? 'document.pdf';
            $fileSize = isset($doc['file_size']) ? round($doc['file_size'] / 1024, 1) . ' KB' : null;
            $ext = strtolower(pathinfo($origName, PATHINFO_EXTENSION));
            $type = in_array($ext, ['pdf', 'doc', 'docx', 'ppt', 'pptx', 'xls', 'xlsx', 'zip']) ? $ext : 'document';

            if ($fileId) {
                $filePath = $this->downloadTelegramDocument($fileId, $origName, 'lessons');
            }
        } elseif (isset($message['photo'])) {
            $photos = $message['photo'];
            $best = end($photos);
            $fileId = $best['file_id'] ?? null;
            $type = 'image';
            if ($fileId) {
                $filePath = $this->downloadTelegramPhoto($fileId);
            }
        } elseif (!empty($message['text'])) {
            $textUrl = trim($message['text']);
            $contentUrl = $textUrl;
            $type = (str_contains($textUrl, 'youtube') || str_contains($textUrl, 'youtu.be')) ? 'video' : 'link';
        }

        if (!$filePath && !$contentUrl) {
            $this->sendMessage($chatId, "❌ تعذر استلام الملف أو الرابط. يرجى إرسال ملف مستند أو صورة أو رابط إنترنت صالح:");
            return;
        }

        $lesson = Lesson::create([
            'course_id'     => $courseId,
            'teacher_id'    => $teacher?->teacher_id,
            'department_id' => $user->department_id ?? $course->department_id,
            'title'         => $title,
            'type'          => $type,
            'description'   => "تم الرفع عبر بوت التيليغرام بواسطة الأستاذ {$user->full_name}",
            'content_url'   => $contentUrl ?: ($filePath ? url($filePath) : null),
            'file_size'     => $fileSize,
        ]);

        Cache::forget("telegram_state_{$chatId}");
        Cache::forget("telegram_teacher_lesson_title_{$chatId}");
        Cache::forget("telegram_teacher_lesson_course_{$chatId}");

        // إشعار جميع طلاب المادة
        $students = $course->students;
        $notifTitle = "محاضرة / ملف جديد: {$course->title}";
        $notifMsg = "قام الأستاذ ({$user->full_name}) برفع ({$title}) لمادة ({$course->title}).";

        foreach ($students as $student) {
            if (!$student->user) continue;

            Notification::create([
                'user_id'    => $student->user->user_id,
                'sender_id'  => $user->user_id,
                'title'      => $notifTitle,
                'message'    => $notifMsg,
                'type'       => 'new_lesson',
                'category'   => 'academic',
                'related_id' => $lesson->lesson_id,
                'is_read'    => false,
            ]);

            FcmService::sendToUser($student->user->user_id, $notifTitle, $notifMsg, [
                'type' => 'new_lesson',
                'lesson_id' => (string)$lesson->lesson_id,
                'course_id' => (string)$courseId,
            ]);

            if ($student->user->telegram_chat_id) {
                $this->sendMessage($student->user->telegram_chat_id, "📢 **محاضرة / ملف جديد!** 🎓\n\n📘 **المادة:** {$course->title}\n📄 **العنوان:** {$title}\n👨‍🏫 **الأستاذ:** {$user->full_name}");
            }
        }

        $this->sendMessage(
            $chatId,
            "✅ **تم نشر المحاضرة / الملف بنجاح!** 🎓\n\n📘 **المادة:** {$course->title}\n📄 **العنوان:** {$title}\n📊 **النوع:** {$type}\n👥 **عدد الطلاب المستلمين للإشعار:** `{$students->count()}` طالب"
        );
    }

    private function downloadTelegramDocument(string $fileId, string $originalName = 'file', string $subDir = 'lessons'): ?string
    {
        try {
            $res = Http::get("{$this->apiUrl}/getFile", ['file_id' => $fileId]);
            if ($res->successful()) {
                $filePath = $res->json('result.file_path');
                if ($filePath) {
                    $token = config('services.telegram.bot_token', env('TELEGRAM_BOT_TOKEN'));
                    $fileUrl = "https://api.telegram.org/file/bot{$token}/{$filePath}";
                    $fileContents = Http::get($fileUrl)->body();

                    $ext = pathinfo($filePath, PATHINFO_EXTENSION);
                    if (empty($ext) && !empty($originalName)) {
                        $ext = pathinfo($originalName, PATHINFO_EXTENSION);
                    }
                    if (empty($ext)) {
                        $ext = 'pdf';
                    }

                    $cleanName = pathinfo($originalName, PATHINFO_FILENAME);
                    $cleanName = preg_replace('/[^a-zA-Z0-9_-]/', '_', $cleanName) ?: 'file';
                    $filename = $cleanName . '_' . time() . '.' . $ext;

                    $destDir = public_path('uploads/' . $subDir);
                    if (!is_dir($destDir)) {
                        mkdir($destDir, 0755, true);
                    }
                    file_put_contents($destDir . '/' . $filename, $fileContents);

                    return 'uploads/' . $subDir . '/' . $filename;
                }
            }
        } catch (\Exception $e) {
            Log::error('Telegram download document error: ' . $e->getMessage());
        }
        return null;
    }
}

