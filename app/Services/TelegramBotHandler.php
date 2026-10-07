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
                } elseif ($user->role === 'head' || $user->role_id == 5) {
                    $dept = $user->department ?? 'الأكاديمي';
                    $this->sendHodMainMenu($chatId, "مرحباً بك مجدداً الأستاذ/ة **{$user->full_name}** 🏛️\nرئيس قسم ({$dept})");
                } elseif ($user->role === 'affairs' || $user->role_id == 6) {
                    $this->sendAffairsMainMenu($chatId, "مرحباً بك مجدداً الأستاذ/ة **{$user->full_name}** 🏢\nإدارة شؤون الطلاب");
                } elseif ($user->role === 'admin' || $user->role_id == 1) {
                    $this->sendAdminMainMenu($chatId, "مرحباً بك مجدداً سعادة المدير العام **{$user->full_name}** 👑\nإدارة منظومة Edu Bridge");
                } else {
                    $this->sendMessage($chatId, "مرحباً مجدداً **{$user->full_name}** 👋\nحسابك مربوط بالفعل، وأي رمز تحقق (OTP) رح يوصلك هون تلقائياً.");
                }
            } else {
                $keyboard = [
                    'inline_keyboard' => [
                        [
                            ['text' => '🔐 تسجيل الدخول وربط الحساب', 'callback_data' => 'auth_login_start'],
                        ],
                        [
                            ['text' => '🎓 إنشاء حساب طالب جديد', 'callback_data' => 'auth_register_student'],
                            ['text' => '👨‍👩‍👧 إنشاء حساب ولي أمر جديد', 'callback_data' => 'auth_register_parent'],
                        ]
                    ]
                ];
                $this->sendMessage(
                    $chatId,
                    "👋 مرحباً بك في **البوت الرسمي لمنظومة Edu Bridge** 🎓\n\nبوابتك الأكاديمية والتعليمية الذكية لمتابعة المسار الدراسي والخدمات اللحظية.\n\nيرجى اختيار ما تود القيام به، أو إدخال (اسم المستخدم / الرقم الجامعي / الهاتف) لتسجيل الدخول مباشرة:",
                    null,
                    $keyboard
                );
                Cache::put($stateKey, 'awaiting_university_id', 3600);
            }
            return;
        }

        if ($text === '/logout') {
            $this->handleLogout($user, $chatId, $stateKey);
            return;
        }

        // معالجة حالات إنشاء حساب طالب جديد (Student Registration Flow)
        if ($state === 'reg_student_name') {
            $this->handleRegStudentName($chatId, $text);
            return;
        }

        if ($state === 'reg_student_uid') {
            $this->handleRegStudentUid($chatId, $text);
            return;
        }

        if ($state === 'reg_student_phone') {
            $this->handleRegStudentPhone($chatId, $text);
            return;
        }

        if ($state === 'reg_student_password') {
            $this->handleRegStudentPassword($chatId, $text);
            return;
        }

        // معالجة حالات إنشاء حساب ولي أمر جديد (Parent Registration Flow)
        if ($state === 'reg_parent_name') {
            $this->handleRegParentName($chatId, $text);
            return;
        }

        if ($state === 'reg_parent_child_uid') {
            $this->handleRegParentChildUid($chatId, $text);
            return;
        }

        if ($state === 'reg_parent_phone') {
            $this->handleRegParentPhone($chatId, $text);
            return;
        }

        if ($state === 'reg_parent_password') {
            $this->handleRegParentPassword($chatId, $text);
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

        // معالجة نشر إعلان من رئيس القسم
        if ($state === 'awaiting_hod_ann_title') {
            $this->handleHodAnnTitleInput($chatId, $text);
            return;
        }

        if ($state === 'awaiting_hod_ann_content') {
            $this->handleHodAnnContentInput($chatId, $text);
            return;
        }

        // معالجة تدوين ملاحظات وتوجيهات رئيس القسم على طلب طالب
        if ($state && str_starts_with($state, 'awaiting_hod_req_notes_')) {
            $this->handleHodReqNotesInput($user, $chatId, $text, $state);
            return;
        }

        // معالجة استعلام موظف الشؤون عن طالب
        if ($state === 'awaiting_affairs_student_search') {
            $this->handleAffairsStudentSearchInput($user, $chatId, $text);
            return;
        }

        // معالجة نشر إعلان من موظف الشؤون
        if ($state === 'awaiting_affairs_ann_title') {
            $this->handleAffairsAnnTitleInput($chatId, $text);
            return;
        }

        if ($state === 'awaiting_affairs_ann_content') {
            $this->handleAffairsAnnContentInput($chatId, $text);
            return;
        }

        // معالجة استعلام وإدارة المستخدمين من المدير العام
        if ($state === 'awaiting_admin_user_search') {
            $this->handleAdminUserSearchInput($user, $chatId, $text);
            return;
        }

        // معالجة نشر إعلان وبث شامل من الإدارة
        if ($state === 'awaiting_admin_ann_title') {
            $this->handleAdminAnnTitleInput($chatId, $text);
            return;
        }

        if ($state === 'awaiting_admin_ann_content') {
            $this->handleAdminAnnContentInput($chatId, $text);
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
        } elseif ($user && ($user->role === 'head' || $user->role_id == 5)) {
            if (str_contains($text, 'لوحة') || str_contains($text, 'إحصائيات') || str_contains($text, 'احصائيات') || str_contains($text, 'قسم')) {
                $this->handleHodOverview($user, $chatId);
            } elseif (str_contains($text, 'كادر') || str_contains($text, 'معلم') || str_contains($text, 'مدرب') || str_contains($text, 'أساتذة') || str_contains($text, 'اساتذة')) {
                $this->handleHodTeachers($user, $chatId);
            } elseif (str_contains($text, 'مقرر') || str_contains($text, 'شعب') || str_contains($text, 'مواد')) {
                $this->handleHodCourses($user, $chatId);
            } elseif (str_contains($text, 'إجاز') || str_contains($text, 'اجاز')) {
                $this->handleHodTeacherLeaves($user, $chatId);
            } elseif (str_contains($text, 'طلب') || str_contains($text, 'خدمات') || str_contains($text, 'طلاب')) {
                $this->handleHodStudentRequests($user, $chatId);
            } elseif (str_contains($text, 'موعد') || str_contains($text, 'مواعيد') || str_contains($text, 'أولياء') || str_contains($text, 'اولياء') || str_contains($text, 'لقاء')) {
                $this->handleHodAppointments($user, $chatId);
            } elseif (str_contains($text, 'إعلان') || str_contains($text, 'اعلان') || str_contains($text, 'نشر') || str_contains($text, 'تعميم')) {
                $this->handleHodBroadcastStart($user, $chatId);
            } elseif (str_contains($text, 'خروج')) {
                $this->handleLogout($user, $chatId, $stateKey);
            } else {
                $this->sendHodMainMenu($chatId);
            }
        } elseif ($user && ($user->role === 'affairs' || $user->role_id == 6)) {
            if (str_contains($text, 'لوحة') || str_contains($text, 'إحصائيات') || str_contains($text, 'احصائيات') || str_contains($text, 'شؤون')) {
                $this->handleAffairsOverview($user, $chatId);
            } elseif (str_contains($text, 'إجاز') || str_contains($text, 'اجاز') || str_contains($text, 'أعذار') || str_contains($text, 'اعذار')) {
                $this->handleAffairsStudentLeaves($user, $chatId);
            } elseif (str_contains($text, 'بصمة') || str_contains($text, 'صورة') || str_contains($text, 'وجه') || str_contains($text, 'صور')) {
                $this->handleAffairsPhotoRequests($user, $chatId);
            } elseif (str_contains($text, 'طلب') || str_contains($text, 'خدمات') || str_contains($text, 'قفل') || str_contains($text, 'جهاز')) {
                $this->handleAffairsStudentRequests($user, $chatId);
            } elseif (str_contains($text, 'استعلام') || str_contains($text, 'سجل') || str_contains($text, 'طالب') || str_contains($text, 'كشف')) {
                $this->handleAffairsStudentSearchStart($user, $chatId);
            } elseif (str_contains($text, 'إعلان') || str_contains($text, 'اعلان') || str_contains($text, 'نشر') || str_contains($text, 'تعميم')) {
                $this->handleAffairsBroadcastStart($user, $chatId);
            } elseif (str_contains($text, 'خروج')) {
                $this->handleLogout($user, $chatId, $stateKey);
            } else {
                $this->sendAffairsMainMenu($chatId);
            }
        } elseif ($user && ($user->role === 'admin' || $user->role_id == 1)) {
            if (str_contains($text, 'لوحة') || str_contains($text, 'إحصائيات') || str_contains($text, 'احصائيات') || str_contains($text, 'مؤشرات') || str_contains($text, 'نظام')) {
                $this->handleAdminOverview($user, $chatId);
            } elseif (str_contains($text, 'حسابات') || str_contains($text, 'تفعيل') || str_contains($text, 'جديدة') || str_contains($text, 'معلقين')) {
                $this->handleAdminPendingAccounts($user, $chatId);
            } elseif (str_contains($text, 'طلب') || str_contains($text, 'خدمات') || str_contains($text, 'قرارات') || str_contains($text, 'إدارية') || str_contains($text, 'ادارية')) {
                $this->handleAdminStudentRequests($user, $chatId);
            } elseif (str_contains($text, 'استعلام') || str_contains($text, 'إدارة مستخدم') || str_contains($text, 'ادارة مستخدم') || str_contains($text, 'بحث') || str_contains($text, 'مستخدم')) {
                $this->handleAdminUserSearchStart($user, $chatId);
            } elseif (str_contains($text, 'سجل') || str_contains($text, 'أمان') || str_contains($text, 'امان') || str_contains($text, 'نشاط') || str_contains($text, 'نشاطات') || str_contains($text, 'عمليات')) {
                $this->handleAdminLiveActivities($user, $chatId);
            } elseif (str_contains($text, 'إعلان') || str_contains($text, 'اعلان') || str_contains($text, 'بث') || str_contains($text, 'نشر') || str_contains($text, 'تعميم')) {
                $this->handleAdminBroadcastStart($user, $chatId);
            } elseif (str_contains($text, 'خروج')) {
                $this->handleLogout($user, $chatId, $stateKey);
            } else {
                $this->sendAdminMainMenu($chatId);
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

        // معالجة أزرار تسجيل الدخول وإنشاء الحساب (قبل التحقق من وجود الحساب)
        if ($data === 'auth_login_start') {
            Cache::put("telegram_state_{$chatId}", 'awaiting_university_id', 3600);
            $this->sendMessage($chatId, "🔐 **تسجيل الدخول وربط الحساب**\n\nيرجى إدخال **اسم المستخدم / البريد الإلكتروني / رقم الهاتف / الرقم الجامعي** الخاص بحسابك:");
            $this->answerCallbackQuery($queryId);
            return;
        } elseif ($data === 'auth_register_student') {
            $this->handleStartRegisterStudent($chatId);
            $this->answerCallbackQuery($queryId);
            return;
        } elseif ($data === 'auth_register_parent') {
            $this->handleStartRegisterParent($chatId);
            $this->answerCallbackQuery($queryId);
            return;
        } elseif (str_starts_with($data, 'reg_prog_')) {
            $progId = (int)str_replace('reg_prog_', '', $data);
            $this->handleRegStudentProgramCallback($chatId, $progId);
            $this->answerCallbackQuery($queryId);
            return;
        } elseif (str_starts_with($data, 'reg_gender_')) {
            $gender = str_replace('reg_gender_', '', $data) === 'male' ? 'ذكر' : 'أنثى';
            $this->handleRegStudentGenderCallback($chatId, $gender);
            $this->answerCallbackQuery($queryId);
            return;
        }

        $user = User::where('telegram_chat_id', $chatId)->first();
        if (!$user || (!in_array($user->role, ['student', 'parent', 'teacher', 'head', 'affairs', 'admin']) && !in_array($user->role_id, [1, 2, 3, 4, 5, 6]))) {
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
        // معالجات إنشاء الواجب وتصحيحه ورفع المحاضرات للمعلم
        elseif ($data === 'teacher_create_assignment_start') {
            $this->handleTeacherCreateAssignmentChooseCourse($user, $chatId);
            $this->answerCallbackQuery($queryId);
        } elseif (str_starts_with($data, 'teacher_create_assign_course_')) {
            $courseId = str_replace('teacher_create_assign_course_', '', $data);
            $this->handleTeacherCreateAssignmentStart($user, $chatId, (int)$courseId);
            $this->answerCallbackQuery($queryId);
        } elseif (str_starts_with($data, 'teacher_assign_due_preset_')) {
            $parts = explode('_', str_replace('teacher_assign_due_preset_', '', $data));
            $courseId = (int)($parts[0] ?? 0);
            $days = (int)($parts[1] ?? 7);
            $dueDate = now()->addDays($days)->format('Y-m-d');
            $this->handleTeacherAssignDueInput($chatId, $dueDate, "awaiting_teacher_assign_due_{$courseId}");
            $this->answerCallbackQuery($queryId);
        } elseif (str_starts_with($data, 'teacher_assign_points_preset_')) {
            $parts = explode('_', str_replace('teacher_assign_points_preset_', '', $data));
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
        // معالجات أزرار رئيس القسم (HOD Callbacks)
        elseif ($data === 'hod_action_overview') {
            $this->handleHodOverview($user, $chatId);
            $this->answerCallbackQuery($queryId);
        } elseif ($data === 'hod_action_teachers') {
            $this->handleHodTeachers($user, $chatId);
            $this->answerCallbackQuery($queryId);
        } elseif ($data === 'hod_action_courses') {
            $this->handleHodCourses($user, $chatId);
            $this->answerCallbackQuery($queryId);
        } elseif ($data === 'hod_action_leaves') {
            $this->handleHodTeacherLeaves($user, $chatId);
            $this->answerCallbackQuery($queryId);
        } elseif ($data === 'hod_action_requests') {
            $this->handleHodStudentRequests($user, $chatId);
            $this->answerCallbackQuery($queryId);
        } elseif ($data === 'hod_action_broadcast') {
            $this->handleHodBroadcastStart($user, $chatId);
            $this->answerCallbackQuery($queryId);
        } elseif (str_starts_with($data, 'hod_teacher_detail_')) {
            $teacherId = str_replace('hod_teacher_detail_', '', $data);
            $this->handleHodTeacherDetail($user, $chatId, (int)$teacherId);
            $this->answerCallbackQuery($queryId);
        } elseif (str_starts_with($data, 'hod_course_detail_')) {
            $courseId = str_replace('hod_course_detail_', '', $data);
            $this->handleHodCourseDetail($user, $chatId, (int)$courseId);
            $this->answerCallbackQuery($queryId);
        } elseif (str_starts_with($data, 'hod_approve_leave_')) {
            $leaveId = str_replace('hod_approve_leave_', '', $data);
            $this->handleHodApproveLeave($user, $chatId, (int)$leaveId);
            $this->answerCallbackQuery($queryId);
        } elseif (str_starts_with($data, 'hod_reject_leave_')) {
            $leaveId = str_replace('hod_reject_leave_', '', $data);
            $this->handleHodRejectLeave($user, $chatId, (int)$leaveId);
            $this->answerCallbackQuery($queryId);
        } elseif (str_starts_with($data, 'hod_approve_req_')) {
            $reqId = str_replace('hod_approve_req_', '', $data);
            $this->handleHodApproveStudentReq($user, $chatId, (int)$reqId);
            $this->answerCallbackQuery($queryId);
        } elseif (str_starts_with($data, 'hod_reject_req_')) {
            $reqId = str_replace('hod_reject_req_', '', $data);
            $this->handleHodRejectStudentReq($user, $chatId, (int)$reqId);
            $this->answerCallbackQuery($queryId);
        } elseif (str_starts_with($data, 'hod_notes_req_')) {
            $reqId = str_replace('hod_notes_req_', '', $data);
            $this->handleHodStartNotesStudentReq($user, $chatId, (int)$reqId);
            $this->answerCallbackQuery($queryId);
        } elseif (str_starts_with($data, 'hod_ann_target_')) {
            $target = str_replace('hod_ann_target_', '', $data);
            $this->handleHodAnnPublish($user, $chatId, $target);
            $this->answerCallbackQuery($queryId);
        }
        // معالجات أزرار موظف الشؤون (Affairs Callbacks)
        elseif ($data === 'affairs_action_overview') {
            $this->handleAffairsOverview($user, $chatId);
            $this->answerCallbackQuery($queryId);
        } elseif ($data === 'affairs_action_leaves') {
            $this->handleAffairsStudentLeaves($user, $chatId);
            $this->answerCallbackQuery($queryId);
        } elseif ($data === 'affairs_action_photos') {
            $this->handleAffairsPhotoRequests($user, $chatId);
            $this->answerCallbackQuery($queryId);
        } elseif ($data === 'affairs_action_requests') {
            $this->handleAffairsStudentRequests($user, $chatId);
            $this->answerCallbackQuery($queryId);
        } elseif ($data === 'affairs_action_search') {
            $this->handleAffairsStudentSearchStart($user, $chatId);
            $this->answerCallbackQuery($queryId);
        } elseif ($data === 'affairs_action_broadcast') {
            $this->handleAffairsBroadcastStart($user, $chatId);
            $this->answerCallbackQuery($queryId);
        } elseif (str_starts_with($data, 'affairs_approve_leave_')) {
            $payload = str_replace('affairs_approve_leave_', '', $data);
            $parts = explode('_', $payload, 2);
            $src = $parts[0] ?? 'leave_requests';
            $id = (int)($parts[1] ?? 0);
            $this->handleAffairsApproveLeave($user, $chatId, $src, $id);
            $this->answerCallbackQuery($queryId);
        } elseif (str_starts_with($data, 'affairs_reject_leave_')) {
            $payload = str_replace('affairs_reject_leave_', '', $data);
            $parts = explode('_', $payload, 2);
            $src = $parts[0] ?? 'leave_requests';
            $id = (int)($parts[1] ?? 0);
            $this->handleAffairsRejectLeave($user, $chatId, $src, $id);
            $this->answerCallbackQuery($queryId);
        } elseif (str_starts_with($data, 'affairs_approve_photo_')) {
            $reqId = (int)str_replace('affairs_approve_photo_', '', $data);
            $this->handleAffairsApprovePhoto($user, $chatId, $reqId);
            $this->answerCallbackQuery($queryId);
        } elseif (str_starts_with($data, 'affairs_reject_photo_')) {
            $reqId = (int)str_replace('affairs_reject_photo_', '', $data);
            $this->handleAffairsRejectPhoto($user, $chatId, $reqId);
            $this->answerCallbackQuery($queryId);
        } elseif (str_starts_with($data, 'affairs_approve_req_')) {
            $reqId = (int)str_replace('affairs_approve_req_', '', $data);
            $this->handleAffairsApproveStudentReq($user, $chatId, $reqId);
            $this->answerCallbackQuery($queryId);
        } elseif (str_starts_with($data, 'affairs_reject_req_')) {
            $reqId = (int)str_replace('affairs_reject_req_', '', $data);
            $this->handleAffairsRejectStudentReq($user, $chatId, $reqId);
            $this->answerCallbackQuery($queryId);
        } elseif (str_starts_with($data, 'affairs_ann_target_')) {
            $target = str_replace('affairs_ann_target_', '', $data);
            $this->handleAffairsAnnPublish($user, $chatId, $target);
            $this->answerCallbackQuery($queryId);
        }
        // معالجات أزرار الإدارة والمدير العام (Admin Callbacks)
        elseif ($data === 'admin_action_overview') {
            $this->handleAdminOverview($user, $chatId);
            $this->answerCallbackQuery($queryId);
        } elseif ($data === 'admin_action_pending_accounts') {
            $this->handleAdminPendingAccounts($user, $chatId);
            $this->answerCallbackQuery($queryId);
        } elseif ($data === 'admin_action_requests') {
            $this->handleAdminStudentRequests($user, $chatId);
            $this->answerCallbackQuery($queryId);
        } elseif ($data === 'admin_action_search') {
            $this->handleAdminUserSearchStart($user, $chatId);
            $this->answerCallbackQuery($queryId);
        } elseif ($data === 'admin_action_activities') {
            $this->handleAdminLiveActivities($user, $chatId);
            $this->answerCallbackQuery($queryId);
        } elseif ($data === 'admin_action_broadcast') {
            $this->handleAdminBroadcastStart($user, $chatId);
            $this->answerCallbackQuery($queryId);
        } elseif (str_starts_with($data, 'admin_approve_user_')) {
            $targetUserId = (int)str_replace('admin_approve_user_', '', $data);
            $this->handleAdminApproveUser($user, $chatId, $targetUserId);
            $this->answerCallbackQuery($queryId);
        } elseif (str_starts_with($data, 'admin_reject_user_')) {
            $targetUserId = (int)str_replace('admin_reject_user_', '', $data);
            $this->handleAdminRejectUser($user, $chatId, $targetUserId);
            $this->answerCallbackQuery($queryId);
        } elseif (str_starts_with($data, 'admin_approve_req_')) {
            $reqId = (int)str_replace('admin_approve_req_', '', $data);
            $this->handleAdminApproveStudentReq($user, $chatId, $reqId);
            $this->answerCallbackQuery($queryId);
        } elseif (str_starts_with($data, 'admin_reject_req_')) {
            $reqId = (int)str_replace('admin_reject_req_', '', $data);
            $this->handleAdminRejectStudentReq($user, $chatId, $reqId);
            $this->answerCallbackQuery($queryId);
        } elseif (str_starts_with($data, 'admin_toggle_status_')) {
            $targetUserId = (int)str_replace('admin_toggle_status_', '', $data);
            $this->handleAdminToggleStatus($user, $chatId, $targetUserId);
            $this->answerCallbackQuery($queryId);
        } elseif (str_starts_with($data, 'admin_unlink_user_')) {
            $targetUserId = (int)str_replace('admin_unlink_user_', '', $data);
            $this->handleAdminUnlinkUser($user, $chatId, $targetUserId);
            $this->answerCallbackQuery($queryId);
        } elseif (str_starts_with($data, 'admin_ann_target_')) {
            $target = str_replace('admin_ann_target_', '', $data);
            $this->handleAdminAnnPublish($user, $chatId, $target);
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

        // نفس قفل المحاولات المستخدم بتسجيل الدخول على الويب (5 محاولات فاشلة = قفل 15 دقيقة)،
        // وإلا يمكن تخمين كلمة السر عبر البوت بلا حد.
        if (LoginThrottleGuard::isLocked($user)) {
            $this->sendLoginLockedMessage($chatId, $user);
            return;
        }

        if (Hash::check($text, $user->password)) {
            LoginThrottleGuard::recordSuccess($user);

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
            } elseif ($user->role === 'head' || $user->role_id == 5) {
                $dept = $user->department ?? 'الأكاديمي';
                $this->sendHodMainMenu($chatId, "✅ **تم تسجيل الدخول وربط حسابك بنجاح!**\nمرحباً بك الأستاذ/ة **{$user->full_name}** 🏛️\nرئيس قسم ({$dept})");
            } elseif ($user->role === 'affairs' || $user->role_id == 6) {
                $this->sendAffairsMainMenu($chatId, "✅ **تم تسجيل الدخول وربط حسابك بنجاح!**\nمرحباً بك الأستاذ/ة **{$user->full_name}** 🏢\nإدارة شؤون الطلاب");
            } elseif ($user->role === 'admin' || $user->role_id == 1) {
                $this->sendAdminMainMenu($chatId, "✅ **تم تسجيل الدخول وربط حسابك بنجاح!**\nمرحباً بك سعادة المدير العام **{$user->full_name}** 👑\nإدارة منظومة Edu Bridge");
            } else {
                $this->sendMessage($chatId, "✅ **تم ربط حسابك بنجاح!**\nمرحباً بك **{$user->full_name}** 🎓\n\nمن الآن، أي رمز تحقق (OTP) — لتغيير كلمة السر أو البريد أو رقم الهاتف — رح يوصلك مباشرة هون على هالمحادثة.");
            }
        } else {
            LoginThrottleGuard::recordFailure($user);
            $user->refresh();

            if (LoginThrottleGuard::isLocked($user)) {
                $this->sendLoginLockedMessage($chatId, $user);
                return;
            }

            $this->sendMessage($chatId, "❌ كلمة المرور غير صحيحة. يرجى المحاولة مرة أخرى:");
        }
    }

    private function sendLoginLockedMessage($chatId, User $user): void
    {
        $minutes = LoginThrottleGuard::lockRemainingMinutes($user);
        Cache::forget("telegram_state_{$chatId}");
        Cache::forget("telegram_auth_{$chatId}_user_id");

        $this->sendMessage(
            $chatId,
            "🔒 تم قفل الحساب مؤقتاً بسبب محاولات دخول فاشلة متكررة. حاول مرة أخرى بعد {$minutes} دقيقة."
        );
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

    // ==========================================
    // Super Admin / General Manager Services Handlers (المدير العام / الإدارة)
    // ==========================================

    private function sendAdminMainMenu($chatId, $headerText = null)
    {
        $keyboard = [
            'keyboard' => [
                [['text' => '📊 لوحة المؤشرات الشاملة'], ['text' => '👥 تدقيق وتفعيل الحسابات']],
                [['text' => '📑 الطلبات والقرارات الإدارية'], ['text' => '🔍 إدارة واستعلام المستخدمين']],
                [['text' => '📋 سجل الأمان والنشاطات'], ['text' => '📢 بث وتعميم إداري شامل']],
                [['text' => '🚪 تسجيل خروج']]
            ],
            'resize_keyboard' => true,
            'persistent' => true
        ];

        $text = $headerText ?? "👑 **لوحة قيادة وإدارة منظومة Edu Bridge**\nيرجى اختيار الخدمة أو الإجراء المطلوب:";
        $this->sendMessage($chatId, $text, null, $keyboard);
    }

    private function handleAdminOverview(User $user, $chatId)
    {
        $totalStudents = User::where('role_id', 3)->count();
        $totalTeachers = User::where('role_id', 2)->count();
        $totalParents  = User::where('role_id', 4)->count();
        $totalHods     = User::where('role_id', 5)->count();
        $totalAffairs  = User::where('role_id', 6)->count();
        $totalAdmins   = User::where('role_id', 1)->count();
        $totalUsers    = User::count();

        $activeUsers   = User::where('status', 'active')->count();
        $inactiveUsers = User::where('status', 'inactive')->count();

        $totalDepts    = DB::table('departments')->count();
        $totalPrograms = DB::table('programs')->count();
        $totalCourses  = DB::table('courses')->count();

        $pendingAccounts = User::where('status', 'inactive')->where('role_id', '!=', 1)->count();
        $pendingReqs     = StudentRequest::where('status', 'pending_admin')->count();

        $activeSemester  = DB::table('semesters')->where('is_active', true)->first();
        $semesterName    = $activeSemester->semester_name ?? $activeSemester->name ?? 'الفصل الحالي نشط';

        $msg = "👑 **التقرير الشامل ولوحة مؤشرات الإدارة العليا**\n\n";
        $msg .= "👤 **المدير العام:** {$user->full_name}\n";
        $msg .= "🗓️ **الفصل الدراسي المعتمد:** `{$semesterName}`\n";
        $msg .= "─────────────\n";
        $msg .= "👥 **إحصائيات المستخدمين الكلية ({$totalUsers}):**\n";
        $msg .= "  🎓 الطلاب: `{$totalStudents}` | 👨‍🏫 المعلمون: `{$totalTeachers}`\n";
        $msg .= "  👨‍👩‍👧 أولياء الأمور: `{$totalParents}` | 🏛️ رؤساء الأقسام: `{$totalHods}`\n";
        $msg .= "  🏢 شؤون الطلاب: `{$totalAffairs}` | 👑 الإدارة: `{$totalAdmins}`\n";
        $msg .= "  🟢 الحسابات النشطة: `{$activeUsers}` | 🔴 غير المفعلة/الموقوفة: `{$inactiveUsers}`\n";
        $msg .= "─────────────\n";
        $msg .= "🏛️ **الهيكل الأكاديمي:**\n";
        $msg .= "  🏢 الأقسام: `{$totalDepts}` | 📚 التخصصات: `{$totalPrograms}` | 📖 المقررات: `{$totalCourses}`\n";
        $msg .= "─────────────\n";
        $msg .= "⚡ **إجراءات الإدارة وقرارات الموافقة المعلقة:**\n";
        $msg .= "  👥 حسابات جديدة بانتظار التفعيل: `{$pendingAccounts}`\n";
        $msg .= "  📑 طلبات خدمات وقرارات بانتظار توقيع الإدارة: `{$pendingReqs}`\n";

        $keyboard = [
            'inline_keyboard' => [
                [
                    ['text' => '👥 تفعيل الحسابات (' . $pendingAccounts . ')', 'callback_data' => 'admin_action_pending_accounts'],
                    ['text' => '📑 قرارات الإدارة (' . $pendingReqs . ')', 'callback_data' => 'admin_action_requests'],
                ],
                [
                    ['text' => '📋 سجل الأمان اللحظي', 'callback_data' => 'admin_action_activities'],
                    ['text' => '📢 بث تعميم إداري', 'callback_data' => 'admin_action_broadcast'],
                ]
            ]
        ];

        $this->sendMessage($chatId, $msg, null, $keyboard);
    }

    private function handleAdminPendingAccounts(User $user, $chatId)
    {
        $pendingUsers = User::where('status', 'inactive')
            ->where('role_id', '!=', 1)
            ->orderByDesc('created_at')
            ->take(8)
            ->get();

        if ($pendingUsers->isEmpty()) {
            $this->sendMessage($chatId, "🌟 **لا توجد أي حسابات معلقة بانتظار التفعيل حالياً.**\nكافة الحسابات مفعلة ونشطة.");
            return;
        }

        $this->sendMessage($chatId, "👥 **الحسابات الجديدة المعلقة بانتظار تدقيق واعتماد الإدارة ({$pendingUsers->count()}):**");

        foreach ($pendingUsers as $u) {
            $roleLabel = match((int)$u->role_id) {
                2 => '👨‍🏫 كادر المعلمين / المدربين',
                3 => '🎓 طالب مسجل',
                4 => '👨‍👩‍👧 ولي أمر',
                5 => '🏛️ رئيس قسم أكاديمي',
                6 => '🏢 موظف شؤون طلاب',
                default => 'مستخدم',
            };

            $date = date('Y-m-d h:i A', strtotime($u->created_at));
            $uid = $u->university_id ?? $u->username ?? '-';
            $phone = $u->phone ?? 'غير متوفر';

            $msg = "👤 **الاسم:** {$u->full_name}\n";
            $msg .= "📌 **الدور:** {$roleLabel}\n";
            $msg .= "🆔 **المعرف / الرقم:** `{$uid}`\n";
            $msg .= "📞 **الهاتف:** `{$phone}` | 📧 **الإيميل:** `{$u->email}`\n";
            $msg .= "🕒 **تاريخ التسجيل:** `{$date}`\n";

            $keyboard = [
                'inline_keyboard' => [
                    [
                        ['text' => '✅ تفعيل وقبول الحساب', 'callback_data' => "admin_approve_user_{$u->user_id}"],
                        ['text' => '❌ رفض وحذف', 'callback_data' => "admin_reject_user_{$u->user_id}"],
                    ]
                ]
            ];

            $this->sendMessage($chatId, $msg, null, $keyboard);
        }
    }

    private function handleAdminApproveUser(User $user, $chatId, int $targetUserId)
    {
        $targetUser = User::find($targetUserId);
        if (!$targetUser) {
            $this->sendMessage($chatId, "❌ الحساب غير موجود أو تمت معالجته.");
            return;
        }

        $targetUser->status = 'active';
        $targetUser->updated_at = now();
        $targetUser->save();

        // تفعيل تسجيل المواد التلقائي إن كان طالباً
        if ($targetUser->role_id == 3) {
            $student = Student::where('user_id', $targetUserId)->first();
            if ($student) {
                Student::autoEnrollCourses($student->student_id);
            }
        }

        \App\Models\UserActivity::log('تفعيل حساب', "قام المدير العام ({$user->full_name}) بتفعيل وقبول حساب: {$targetUser->full_name} ({$targetUser->username})", $user);

        // إشعار داخلي و FCM
        $notifTitle = 'تم تفعيل حسابك بنجاح ✓';
        $notifMsg   = "مرحباً {$targetUser->full_name}! تم اعتماد وتفعيل حسابك رسمياً من قِبل إدارة المعهد.";

        Notification::create([
            'user_id'    => $targetUserId,
            'sender_id'  => $user->user_id,
            'title'      => $notifTitle,
            'message'    => $notifMsg,
            'type'       => 'administrative',
            'category'   => 'administrative',
            'is_read'    => false,
        ]);

        FcmService::sendToUser($targetUserId, $notifTitle, $notifMsg, ['type' => 'administrative']);

        // إشعار تليجرام للمستخدم المفعّل إن كان حسابه مربوطاً
        if ($targetUser->telegram_chat_id) {
            $idText = $targetUser->university_id ?? $targetUser->username ?? '';
            $welcomeText = "🎓 <b>تفعيل الحساب الرسمي - Edu Bridge</b>\n\n"
                . "مرحباً <b>{$targetUser->full_name}</b>،\n\n"
                . "🎉 لقد تم <b>الموافقة وتفعيل حسابك بنجاح</b> من قِبل إدارة المعهد!\n"
                . ($idText ? "🆔 <b>المعرف الجامعي:</b> <code>{$idText}</code>\n\n" : "\n")
                . "📲 يمكنك الآن تسجيل الدخول مباشرة والوصول لكافة الخدمات والمحاضرات.";
            $this->sendMessage((int)$targetUser->telegram_chat_id, $welcomeText);
        }

        $this->sendMessage($chatId, "✅ **تم تفعيل وقبول حساب ({$targetUser->full_name}) بنجاح وإشعاره فورياً!**");
    }

    private function handleAdminRejectUser(User $user, $chatId, int $targetUserId)
    {
        $targetUser = User::find($targetUserId);
        if (!$targetUser) {
            $this->sendMessage($chatId, "❌ الحساب غير موجود أو تمت معالجته مسبقاً.");
            return;
        }

        $userName = $targetUser->full_name;

        // إشعار تليجرام بالرفض قبل الحذف
        if ($targetUser->telegram_chat_id) {
            $rejectText = "🎓 <b>طلب التسجيل - Edu Bridge</b>\n\n"
                . "مرحباً <b>{$userName}</b>،\n\n"
                . "⚠️ نأسف لإعلامك بأنه تم <b>رفض طلب إنشاء الحساب</b> من قِبل إدارة المعهد.\n"
                . "يرجى مراجعة إدارة شؤون الطلاب للمزيد من التفاصيل.";
            $this->sendMessage((int)$targetUser->telegram_chat_id, $rejectText);
        }

        if ($targetUser->university_id) {
            DB::table('university_ids')
                ->where('university_id', $targetUser->university_id)
                ->update(['is_used' => false]);
        }

        DB::table('students')->where('user_id', $targetUserId)->delete();
        DB::table('parents')->where('user_id', $targetUserId)->delete();
        DB::table('teachers')->where('user_id', $targetUserId)->delete();
        DB::table('heads')->where('user_id', $targetUserId)->delete();
        $targetUser->delete();

        \App\Models\UserActivity::log('رفض حساب', "قام المدير العام ({$user->full_name}) برفض وحذف حساب: {$userName}", $user);

        $this->sendMessage($chatId, "🛑 **تم رفض وحذف حساب ({$userName}) بنجاح.**");
    }

    private function handleAdminStudentRequests(User $user, $chatId)
    {
        $requests = StudentRequest::with(['student.user', 'student.program.department'])
            ->where('status', 'pending_admin')
            ->orderByDesc('created_at')
            ->take(8)
            ->get();

        if ($requests->isEmpty()) {
            $this->sendMessage($chatId, "🌟 **لا توجد أي طلبات خدمات طلابية معلقة بانتظار قرار الإدارة حالياً.**");
            return;
        }

        $this->sendMessage($chatId, "📑 **طلبات الخدمات المرفوعة لقرار الإدارة العليا ({$requests->count()}):**");

        foreach ($requests as $req) {
            $student = $req->student;
            $stUser = $student->user ?? null;
            $stName = $stUser->full_name ?? 'طالب';
            $code = $student && $student->student_code ? " (`{$student->student_code}`)" : "";
            $program = $student->program->name ?? 'عام';

            $typeLabel = match($req->type) {
                'document'   => '📜 طلب وثيقة / كشف علامات رسمي',
                'transcript' => '📜 كشف درجات وسجل أكاديمي',
                'freeze'     => '❄️ طلب تجميد / إيقاف تسجيل',
                'transfer'   => '🔄 طلب انتقال أو تعديل تخصص',
                default      => '📑 ' . ($req->type ?? 'طلب خدمة طلابية'),
            };

            $details = $req->formatted_details ?: ($req->details ?: 'لا توجد تفاصيل');
            $affDecision = $req->affairs_decision ? ($req->affairs_decision === 'approved' ? '✅ موافقة الشؤون' : '❌ تحفظ الشؤون') : 'لم تُبدَ';
            $hodDecision = $req->hod_decision ? ($req->hod_decision === 'approved' ? '✅ توصية رئيس القسم بالقبول' : '❌ عدم قبول رئيس القسم') : 'بانتظار الرأي';

            $msg = "🎓 **الطالب:** {$stName}{$code}\n";
            $msg .= "🏛️ **التخصص:** {$program}\n";
            $msg .= "📌 **نوع الطلب:** {$typeLabel}\n";
            $msg .= "📝 **التفاصيل والبيان:** {$details}\n";
            $msg .= "─────────────\n";
            $msg .= "🏢 **رأي الشؤون:** {$affDecision}" . ($req->affairs_notes ? " ({$req->affairs_notes})" : "") . "\n";
            $msg .= "🏛️ **رأي رئيس القسم:** {$hodDecision}" . ($req->hod_notes ? " ({$req->hod_notes})" : "") . "\n";

            $keyboard = [
                'inline_keyboard' => [
                    [
                        ['text' => '✅ اعتماد وموافقة نهائية', 'callback_data' => "admin_approve_req_{$req->id}"],
                        ['text' => '❌ رفض الطلب', 'callback_data' => "admin_reject_req_{$req->id}"],
                    ]
                ]
            ];

            $this->sendMessage($chatId, $msg, null, $keyboard);
        }
    }

    private function handleAdminApproveStudentReq(User $user, $chatId, int $reqId)
    {
        $studentReq = StudentRequest::with('student.user')->find($reqId);
        if (!$studentReq) {
            $this->sendMessage($chatId, "❌ الطلب غير موجود.");
            return;
        }

        $studentReq->admin_decision = 'approved';
        $studentReq->admin_notes = 'تمت الموافقة والاعتماد النهائي من قِبل المدير العام عبر بوت تليجرام.';
        $studentReq->status = 'completed';
        $studentReq->save();

        $stUser = $studentReq->student->user ?? null;
        $studentName = $stUser->full_name ?? 'الطالب';

        $isTranscript = $studentReq->type === 'document' ||
                        str_contains($studentReq->details ?? '', 'كشف علامات') ||
                        str_contains($studentReq->details ?? '', 'كشف درجات');

        if ($isTranscript) {
            // إشعار موظفي الشؤون لإصدار النسخة الرقمية
            $affairsUsers = User::where('role_id', 6)->where('status', 'active')->pluck('user_id');
            foreach ($affairsUsers as $affUserId) {
                Notification::create([
                    'user_id'    => $affUserId,
                    'sender_id'  => $user->user_id,
                    'title'      => "موافقة الإدارة على كشف علامات: {$studentName} 🎓",
                    'message'    => "وافقت إدارة المعهد على طلب كشف العلامات (#{$studentReq->id}) للطالب ({$studentName}). يرجى إصدار ومشاركة كشف الدرجات الرقمي المعتمد معه.",
                    'type'       => 'transcript_approved',
                    'category'   => 'administrative',
                    'related_id' => $studentReq->student_id,
                    'is_read'    => false,
                ]);
            }
        }

        if ($stUser) {
            $title = 'الموافقة على طلبك الإداري ✅';
            $message = "صدر القرار النهائي من قِبل إدارة المعهد باعتماد والموافقة على طلبك (#{$studentReq->id}).";

            Notification::create([
                'user_id'    => $stUser->user_id,
                'sender_id'  => $user->user_id,
                'title'      => $title,
                'message'    => $message,
                'type'       => 'student_service',
                'category'   => 'administrative',
                'related_id' => $studentReq->id,
                'is_read'    => false,
            ]);

            FcmService::sendToUser($stUser->user_id, $title, $message, ['type' => 'student_service']);

            if ($stUser->telegram_chat_id) {
                $this->sendMessage(
                    $stUser->telegram_chat_id,
                    "🎉 **إشعار من الإدارة العليا** 👑\n\nصدر القرار النهائي بـ **الموافقة والاعتماد الرسمي** على طلبك (#{$studentReq->id})."
                );
            }
        }

        \App\Models\UserActivity::log('قرار إداري', "اعتمد المدير العام بالموافقة النهائية طلب الطالب: {$studentName} (#{$studentReq->id})", $user);

        $this->sendMessage($chatId, "✅ **تم اعتماد وموافقة الإدارة على طلب الطالب ({$studentName}) بنجاح!**");
    }

    private function handleAdminRejectStudentReq(User $user, $chatId, int $reqId)
    {
        $studentReq = StudentRequest::with('student.user')->find($reqId);
        if (!$studentReq) {
            $this->sendMessage($chatId, "❌ الطلب غير موجود.");
            return;
        }

        $studentReq->admin_decision = 'rejected';
        $studentReq->admin_notes = 'تم الرفض النهائي من قِبل المدير العام عبر بوت تليجرام.';
        $studentReq->status = 'completed';
        $studentReq->save();

        $stUser = $studentReq->student->user ?? null;
        $studentName = $stUser->full_name ?? 'الطالب';

        if ($stUser) {
            $title = 'قرار الإدارة بشأن طلبك ❌';
            $message = "صدر القرار النهائي من قِبل إدارة المعهد بعدم الموافقة على طلبك (#{$studentReq->id}).";

            Notification::create([
                'user_id'    => $stUser->user_id,
                'sender_id'  => $user->user_id,
                'title'      => $title,
                'message'    => $message,
                'type'       => 'student_service',
                'category'   => 'administrative',
                'related_id' => $studentReq->id,
                'is_read'    => false,
            ]);

            FcmService::sendToUser($stUser->user_id, $title, $message, ['type' => 'student_service']);

            if ($stUser->telegram_chat_id) {
                $this->sendMessage(
                    $stUser->telegram_chat_id,
                    "⚠️ **إشعار من الإدارة العليا** 👑\n\nنعتذر، صدر قرار الإدارة بعدم الموافقة على طلبك (#{$studentReq->id})."
                );
            }
        }

        \App\Models\UserActivity::log('قرار إداري', "رفض المدير العام طلب الطالب: {$studentName} (#{$studentReq->id})", $user);

        $this->sendMessage($chatId, "🛑 **تم رفض الطلب وإشعار الطالب بنتيجة القرار.**");
    }

    private function handleAdminUserSearchStart(User $user, $chatId)
    {
        Cache::put("telegram_state_{$chatId}", 'awaiting_admin_user_search', 1800);
        $this->sendMessage(
            $chatId,
            "🔍 **استعلام وإدارة حسابات المستخدمين** 👥\n\nيرجى إرسال **اسم المستخدم / الرقم الجامعي / الاسم الكامل / البريد الإلكتروني / رقم الهاتف** للبحث والتحكم الفوري:"
        );
    }

    private function handleAdminUserSearchInput(User $user, $chatId, string $query)
    {
        $q = trim($query);
        if (empty($q)) {
            $this->sendMessage($chatId, "يرجى كتابة نص للبحث.");
            return;
        }

        $users = User::where(function($b) use ($q) {
                $b->where('full_name', 'LIKE', "%{$q}%")
                  ->orWhere('username', 'LIKE', "%{$q}%")
                  ->orWhere('email', 'LIKE', "%{$q}%")
                  ->orWhere('phone', 'LIKE', "%{$q}%")
                  ->orWhere('university_id', 'LIKE', "%{$q}%");
            })
            ->take(5)
            ->get();

        if ($users->isEmpty()) {
            $this->sendMessage($chatId, "❌ لم يتم العثور على أي مستخدم يطابق: `{$q}`\n\nيمكنك تجربة اسم أو رقم جامعي آخر.");
            return;
        }

        Cache::forget("telegram_state_{$chatId}");

        $this->sendMessage($chatId, "🎯 **نتائج البحث والتحكم في الحسابات ({$users->count()}):**");

        foreach ($users as $u) {
            $roleLabel = match((int)$u->role_id) {
                1 => '👑 مدير عام / إدارة',
                2 => '👨‍🏫 معلم / مدرب',
                3 => '🎓 طالب',
                4 => '👨‍👩‍👧 ولي أمر',
                5 => '🏛️ رئيس قسم',
                6 => '🏢 شؤون طلاب',
                default => 'مستخدم',
            };

            $statusLabel = $u->status === 'active' ? '🟢 نشط ومفعل' : '🔴 موقوف / غير مفعل';
            $tgLinked = $u->telegram_chat_id ? "✅ مربوط (`{$u->telegram_chat_id}`)" : "❌ غير مربوط";

            $card = "👤 **الاسم الكامل:** {$u->full_name}\n";
            $card .= "📌 **الدور والصلاحية:** {$roleLabel}\n";
            $card .= "⚡ **الحالة الحالية:** {$statusLabel}\n";
            $card .= "🆔 **المعرف الجامعي:** `{$u->university_id}` | اسم الدخول: `{$u->username}`\n";
            $card .= "📞 **الهاتف:** `{$u->phone}` | 📧 **الإيميل:** `{$u->email}`\n";
            $card .= "📲 **حساب التيليجرام:** {$tgLinked}\n";

            $toggleBtnText = $u->status === 'active' ? '🔴 إيقاف الحساب' : '🟢 تفعيل الحساب';

            $keyboard = [
                'inline_keyboard' => [
                    [
                        ['text' => $toggleBtnText, 'callback_data' => "admin_toggle_status_{$u->user_id}"],
                        ['text' => '🔓 فك ربط الجلسات والجهاز', 'callback_data' => "admin_unlink_user_{$u->user_id}"],
                    ]
                ]
            ];

            $this->sendMessage($chatId, $card, null, $keyboard);
        }
    }

    private function handleAdminToggleStatus(User $user, $chatId, int $targetUserId)
    {
        $targetUser = User::find($targetUserId);
        if (!$targetUser) {
            $this->sendMessage($chatId, "❌ الحساب غير موجود.");
            return;
        }

        if ($targetUser->role_id == 1 && $targetUser->user_id !== $user->user_id) {
            $this->sendMessage($chatId, "⚠️ لا يمكن تعديل حالة حساب مدير عام آخر.");
            return;
        }

        $newStatus = ($targetUser->status === 'active') ? 'inactive' : 'active';
        $targetUser->status = $newStatus;
        $targetUser->updated_at = now();
        $targetUser->save();

        $statusText = ($newStatus === 'active') ? 'تفعيل' : 'إيقاف';
        \App\Models\UserActivity::log('تغيير حالة حساب', "قام المدير العام بـ {$statusText} حساب: {$targetUser->full_name}", $user);

        $this->sendMessage($chatId, "✅ **تم {$statusText} حساب ({$targetUser->full_name}) بنجاح!**");
    }

    private function handleAdminUnlinkUser(User $user, $chatId, int $targetUserId)
    {
        $targetUser = User::find($targetUserId);
        if (!$targetUser) {
            $this->sendMessage($chatId, "❌ الحساب غير موجود.");
            return;
        }

        // فك قفل جهاز الطالب
        if ($targetUser->role_id == 3) {
            DB::table('students')->where('user_id', $targetUserId)->update([
                'device_id'        => null,
                'is_device_locked' => 0,
            ]);
        }

        // إنهاء جميع التوكنات والجلسات النشطة
        DB::table('personal_access_tokens')->where('tokenable_id', $targetUserId)->delete();

        \App\Models\UserActivity::log('فك ربط حساب', "قام المدير العام بفك ربط الجهاز والجلسات لحساب: {$targetUser->full_name}", $user);

        $this->sendMessage($chatId, "🔓 **تم فك ربط الجهاز وإنهاء كافة الجلسات النشطة لحساب ({$targetUser->full_name}) بنجاح!**");
    }

    private function handleAdminLiveActivities(User $user, $chatId)
    {
        $activities = \App\Models\UserActivity::orderByDesc('created_at')
            ->take(10)
            ->get();

        if ($activities->isEmpty()) {
            $this->sendMessage($chatId, "🌟 **لا توجد أي نشاطات أمنية مسجلة حالياً.**");
            return;
        }

        $msg = "📋 **سجل العمليات والنشاطات الأمنية اللحظية (آخر 10 أحداث):**\n\n";

        foreach ($activities as $idx => $act) {
            $num = $idx + 1;
            $time = date('m-d h:i A', strtotime($act->created_at));
            $uName = $act->user_name ?? 'مجهول / نظام';
            $role = $act->role_name ?? 'عام';
            $ip = $act->ip_address ? " (`{$act->ip_address}`)" : "";

            $msg .= "{$num}. ⏱️ `{$time}` | 👤 **{$uName}** [{$role}]\n";
            $msg .= "   ⚡ **العملية:** {$act->action}\n";
            $msg .= "   📝 **التفاصيل:** {$act->description}{$ip}\n";
            $msg .= "─────────────\n";
        }

        $this->sendMessage($chatId, $msg);
    }

    private function handleAdminBroadcastStart(User $user, $chatId)
    {
        Cache::put("telegram_state_{$chatId}", 'awaiting_admin_ann_title', 1800);
        $this->sendMessage($chatId, "📢 **بث وتعميم إداري شامل من المدير العام** 👑\n\nيرجى كتابة **عنوان التعميم أو الإعلان**:");
    }

    private function handleAdminAnnTitleInput($chatId, $text)
    {
        $title = trim($text);
        Cache::put("telegram_admin_ann_title_{$chatId}", $title, 1800);
        Cache::put("telegram_state_{$chatId}", 'awaiting_admin_ann_content', 1800);

        $this->sendMessage($chatId, "📝 **نص وتفاصيل التعميم الإداري**\n\nالعنوان: **{$title}**\n\nالآن يرجى كتابة نص وتفاصيل التعميم بالكامل:");
    }

    private function handleAdminAnnContentInput($chatId, $text)
    {
        $content = trim($text);
        Cache::put("telegram_admin_ann_content_{$chatId}", $content, 1800);

        $keyboard = [
            'inline_keyboard' => [
                [
                    ['text' => '🌐 الجميع بلا استثناء (كافة المستخدمين)', 'callback_data' => 'admin_ann_target_all'],
                ],
                [
                    ['text' => '🎓 الطلاب فقط', 'callback_data' => 'admin_ann_target_students'],
                    ['text' => '👨‍🏫 كادر المعلمين والمدربين', 'callback_data' => 'admin_ann_target_teachers'],
                ],
                [
                    ['text' => '🏛️ الكادر الإداري ورؤساء الأقسام والشؤون', 'callback_data' => 'admin_ann_target_staff'],
                    ['text' => '👨‍👩‍👧 أولياء الأمور', 'callback_data' => 'admin_ann_target_parents'],
                ]
            ]
        ];

        $title = Cache::get("telegram_admin_ann_title_{$chatId}", 'تعميم إداري رسمي');
        $this->sendMessage(
            $chatId,
            "🎯 **تحديد الفئة المستهدفة للتعميم**\n\n📌 **العنوان:** {$title}\n📝 **المحتوى:**\n\"{$content}\"\n\nاختر الجمهور المستهدف لبث ونشر التعميم فورياً:",
            null,
            $keyboard
        );
    }

    private function handleAdminAnnPublish(User $user, $chatId, string $target)
    {
        $title = Cache::get("telegram_admin_ann_title_{$chatId}", 'تعميم من المدير العام');
        $content = Cache::get("telegram_admin_ann_content_{$chatId}", '');

        $announcement = Announcement::create([
            'user_id'         => $user->user_id,
            'title'           => $title,
            'content'         => $content,
            'type'            => 'general',
            'target_audience' => $target,
            'department_id'   => null,
        ]);

        Cache::forget("telegram_state_{$chatId}");
        Cache::forget("telegram_admin_ann_title_{$chatId}");
        Cache::forget("telegram_admin_ann_content_{$chatId}");

        $roleIds = match($target) {
            'students' => [3],
            'teachers' => [2],
            'staff'    => [5, 6],
            'parents'  => [4],
            default    => [1, 2, 3, 4, 5, 6],
        };

        $recipients = User::whereIn('role_id', $roleIds)
            ->where('status', 'active')
            ->get();

        $notifTitle = "📢 تعميم رسمي من إدارة المعهد العليا";

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
                    "👑 **تعميم رسمي صادر عن إدارة المعهد**\n\n📌 **العنوان:** {$title}\n📝 **التفاصيل:**\n{$content}\n\n👤 **المرسل:** المدير العام {$user->full_name}"
                );
            }
        }

        \App\Models\UserActivity::log('بث تعميم إداري', "نشر المدير العام تعميماً رسمياً بعنوان: {$title} (المستهدفين: {$target})", $user);

        $targetLabel = match($target) {
            'students' => 'كافة الطلاب',
            'teachers' => 'كادر المعلمين والمدربين',
            'staff'    => 'الكادر الإداري ورؤساء الأقسام والشؤون',
            'parents'  => 'أولياء الأمور',
            default    => 'الجميع (كافة مستخدمي المعهد)',
        };

        $this->sendMessage(
            $chatId,
            "✅ **تم نشر وبث التعميم الإداري بنجاح!** 📢\n\n📌 **العنوان:** {$title}\n🎯 **الجمهور المستهدف:** {$targetLabel}\n👥 **عدد المستلمين:** `{$recipients->count()}` مستخدم\n\n📨 تم إرسال إشعارات التطبيق والرسائل الفورية عبر التيليجرام لكافة المستهدفين."
        );
    }

    // ==========================================
    // Student Registration Handlers
    // ==========================================

    private function handleStartRegisterStudent($chatId)
    {
        Cache::put("telegram_state_{$chatId}", 'reg_student_name', 1800);
        $this->sendMessage(
            $chatId,
            "🎓 **إنشاء حساب طالب جديد**\n\n📌 **الخطوة 1 من 6:**\nيرجى كتابة **اسمك الثلاثي أو الكامل**:"
        );
    }

    private function handleRegStudentName($chatId, string $text)
    {
        $name = trim($text);
        if (mb_strlen($name) < 3) {
            $this->sendMessage($chatId, "⚠️ يرجى كتابة اسم صحيح لا يقل عن 3 أحرف.");
            return;
        }

        Cache::put("reg_std_name_{$chatId}", $name, 1800);
        Cache::put("telegram_state_{$chatId}", 'reg_student_uid', 1800);

        $this->sendMessage(
            $chatId,
            "أهلاً بك **{$name}** 👋\n\n📌 **الخطوة 2 من 6:**\nيرجى إدخال **الرقم الجامعي** الخاص بك (مثال: `20241001`):"
        );
    }

    private function handleRegStudentUid($chatId, string $text)
    {
        $uid = preg_replace('/\s+/', '', trim($text));
        if (empty($uid)) {
            $this->sendMessage($chatId, "⚠️ يرجى إدخال رقم جامعي صحيح.");
            return;
        }

        // التحقق من تكرار الرقم الجامعي في جدول المستخدمين
        $existingUser = User::where('university_id', $uid)
            ->orWhere('username', 'std_' . $uid)
            ->orWhereHas('student', fn($sq) => $sq->where('student_code', $uid))
            ->first();

        if ($existingUser) {
            $this->sendMessage(
                $chatId,
                "❌ **عذراً! هذا الرقم الجامعي مستخدم مسبقاً في حساب آخر.**\nإذا كان الحساب يخصك، يمكنك الضغط على /start واختيار تسجيل الدخول."
            );
            return;
        }

        // الرقم الجامعي لازم يكون صادراً من شؤون الطلاب وغير مستخدم (نفس شرط التسجيل من التطبيق)،
        // وإلا يستطيع أي شخص اختراع رقم وإنشاء حساب طالب.
        $uidRecord = DB::table('university_ids')->where('university_id', $uid)->where('role', 'student')->first();
        if (!$uidRecord) {
            $this->sendMessage($chatId, "❌ هذا الرقم الجامعي غير موجود. يرجى التأكد منه أو التواصل مع إدارة شؤون الطلاب.");
            return;
        }
        if ($uidRecord->is_used) {
            $this->sendMessage($chatId, "❌ هذا الرقم الجامعي تم تفعيله مسبقاً. يرجى التواصل مع إدارة شؤون الطلاب.");
            return;
        }

        Cache::put("reg_std_uid_{$chatId}", $uid, 1800);
        Cache::put("telegram_state_{$chatId}", 'reg_student_program', 1800);

        // جلب قائمة البرامج والتخصصات المتاحة
        $programs = Program::orderBy('id')->get();
        $buttons = [];
        $row = [];

        foreach ($programs as $idx => $prog) {
            $row[] = ['text' => "🏛️ {$prog->name}", 'callback_data' => "reg_prog_{$prog->id}"];
            if (count($row) === 2 || $idx === $programs->count() - 1) {
                $buttons[] = $row;
                $row = [];
            }
        }

        if (empty($buttons)) {
            $buttons[] = [
                ['text' => '🏛️ معلوماتية وهندسة برمجيات', 'callback_data' => 'reg_prog_1']
            ];
        }

        $keyboard = ['inline_keyboard' => $buttons];

        $this->sendMessage(
            $chatId,
            "📌 **الخطوة 3 من 6:**\nاختر **التخصص / البرنامج الأكاديمي** الخاص بك من القائمة أدناه:",
            null,
            $keyboard
        );
    }

    private function handleRegStudentProgramCallback($chatId, int $progId)
    {
        $prog = Program::find($progId);
        $progName = $prog->name ?? 'عام';

        Cache::put("reg_std_prog_id_{$chatId}", $progId, 1800);
        Cache::put("reg_std_prog_name_{$chatId}", $progName, 1800);
        Cache::put("telegram_state_{$chatId}", 'reg_student_gender', 1800);

        $keyboard = [
            'inline_keyboard' => [
                [
                    ['text' => '👨 ذكر', 'callback_data' => 'reg_gender_male'],
                    ['text' => '👩 أنثى', 'callback_data' => 'reg_gender_female'],
                ]
            ]
        ];

        $this->sendMessage(
            $chatId,
            "تم اختيار تخصص: **{$progName}** 🏛️\n\n📌 **الخطوة 4 من 6:**\nيرجى تحديد **الجنس**:",
            null,
            $keyboard
        );
    }

    private function handleRegStudentGenderCallback($chatId, string $gender)
    {
        Cache::put("reg_std_gender_{$chatId}", $gender, 1800);
        Cache::put("telegram_state_{$chatId}", 'reg_student_phone', 1800);

        $this->sendMessage(
            $chatId,
            "📌 **الخطوة 5 من 6:**\nيرجى إدخال **رقم الهاتف** الخاص بك (مثال: `0987654321`):"
        );
    }

    private function handleRegStudentPhone($chatId, string $text)
    {
        $phone = preg_replace('/\s+/', '', trim($text));
        if (mb_strlen($phone) < 8) {
            $this->sendMessage($chatId, "⚠️ يرجى إدخال رقم هاتف صحيح.");
            return;
        }

        Cache::put("reg_std_phone_{$chatId}", $phone, 1800);
        Cache::put("telegram_state_{$chatId}", 'reg_student_password', 1800);

        $this->sendMessage(
            $chatId,
            "📌 **الخطوة 6 من 6 (الأخيرة):**\nيرجى إدخال **كلمة المرور** لحسابك (8 أحرف أو أرقام على الأقل):"
        );
    }

    private function handleRegStudentPassword($chatId, string $text)
    {
        $password = trim($text);
        if (mb_strlen($password) < 8) {
            $this->sendMessage($chatId, "⚠️ يجب ألا تقل كلمة المرور عن 8 خانات. يرجى إعادة الإدخال:");
            return;
        }

        $name     = Cache::get("reg_std_name_{$chatId}");
        $uid      = Cache::get("reg_std_uid_{$chatId}");
        $progId   = Cache::get("reg_std_prog_id_{$chatId}", 1);
        $progName = Cache::get("reg_std_prog_name_{$chatId}", 'معلوماتية');
        $gender   = Cache::get("reg_std_gender_{$chatId}", 'ذكر');
        $phone    = Cache::get("reg_std_phone_{$chatId}");

        if (!$name || !$uid) {
            $this->sendMessage($chatId, "⚠️ انتهت مهلة الجلسة، يرجى البدء من جديد عبر /start.");
            Cache::forget("telegram_state_{$chatId}");
            return;
        }

        $parts = explode(' ', $name, 2);
        $firstName = $parts[0] ?? $name;
        $lastName  = $parts[1] ?? '';

        $email = "std_{$uid}@edubridge.edu";
        $username = "std_{$uid}";

        // التأكد من عدم تكرار الإيميل
        if (User::where('email', $email)->exists()) {
            $email = "std_{$uid}_" . time() . "@edubridge.edu";
        }

        DB::beginTransaction();
        try {
            $user = User::create([
                'role_id'           => 3,
                'full_name'         => $name,
                'first_name'        => $firstName,
                'last_name'         => $lastName,
                'username'          => $username,
                'email'             => $email,
                'password'          => Hash::make($password),
                'phone'             => $phone,
                'telegram_chat_id'  => (string)$chatId,
                'university_id'     => $uid,
                'department'        => $progName,
                'branch'            => 'الفرع الرئيسي',
                'gender'            => $gender,
                'academic_year'     => 'السنة الأولى',
                'status'            => 'active',
            ]);

            Student::create([
                'user_id'          => $user->user_id,
                'student_code'     => $uid,
                'program_id'       => $progId,
                'level'            => 'السنة الأولى',
                'is_device_locked' => 0,
            ]);

            if (\Illuminate\Support\Facades\Schema::hasTable('university_ids')) {
                DB::table('university_ids')->where('university_id', $uid)->update([
                    'is_used'          => 1,
                    'telegram_chat_id' => (string)$chatId,
                ]);
            }

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Telegram Student Registration Error: ' . $e->getMessage());
            $this->sendMessage($chatId, "❌ حدث خطأ أثناء إنشاء الحساب. يرجى المحاولة لاحقاً عبر /start.");
            return;
        }

        // مسح الكاش المؤقت
        Cache::forget("telegram_state_{$chatId}");
        Cache::forget("reg_std_name_{$chatId}");
        Cache::forget("reg_std_uid_{$chatId}");
        Cache::forget("reg_std_prog_id_{$chatId}");
        Cache::forget("reg_std_prog_name_{$chatId}");
        Cache::forget("reg_std_gender_{$chatId}");
        Cache::forget("reg_std_phone_{$chatId}");

        Cache::forget('admin_profile_stats');
        Cache::forget('distinct_user_actions');

        \App\Models\UserActivity::log('إنشاء حساب جديد', "تم تسجيل حساب طالب جديد عبر بوت التلغرام: {$name} ({$uid})", $user);

        $welcomeMsg = "🎉 **تهانينا! تم إنشاء وتفعيل حسابك الأكاديمي بنجاح!** 🎓\n\n"
            . "👤 **الاسم:** {$name}\n"
            . "🆔 **الرقم الجامعي:** `{$uid}`\n"
            . "🏛️ **التخصص:** {$progName}\n"
            . "📞 **الهاتف:** `{$phone}`\n"
            . "📧 **اسم المستخدم:** `{$username}`\n"
            . "─────────────\n"
            . "⚡ تم ربط حسابك بالبوت تلقائياً ويمكنك الآن الاستفادة من كافة الخدمات أدناه:";

        $this->sendStudentMainMenu($chatId, $welcomeMsg);
    }

    // ==========================================
    // Parent Registration Handlers
    // ==========================================

    private function handleStartRegisterParent($chatId)
    {
        Cache::put("telegram_state_{$chatId}", 'reg_parent_name', 1800);
        $this->sendMessage(
            $chatId,
            "👨‍👩‍👧 **إنشاء حساب ولي أمر جديد**\n\n📌 **الخطوة 1 من 4:**\nيرجى كتابة **الاسم الكامل لولي الأمر**:"
        );
    }

    private function handleRegParentName($chatId, string $text)
    {
        $name = trim(preg_replace('/\s+/u', ' ', $text));
        if (mb_strlen($name) < 3 || !str_contains($name, ' ')) {
            $this->sendMessage($chatId, "⚠️ يرجى كتابة اسمك الكامل (الاسم + اسم العائلة).");
            return;
        }

        Cache::put("reg_par_name_{$chatId}", $name, 1800);
        Cache::put("telegram_state_{$chatId}", 'reg_parent_child_uid', 1800);

        $this->sendMessage(
            $chatId,
            "أهلاً بك **{$name}** 👨‍👩‍👧\n\n📌 **الخطوة 2 من 4:**\nيرجى إدخال **الرقم الجامعي أو كود الطالب (الابن/الابنة)** للربط معه:"
        );
    }

    private function parentLastNameMatchesChild(string $parentFullName, User $child): bool
    {
        $lastWord = function (string $s): string {
            $words = explode(' ', mb_strtolower(trim(preg_replace('/\s+/u', ' ', $s))));
            return (string) end($words);
        };

        $childLast  = $lastWord((string) ($child->last_name ?: $child->full_name));
        $parentLast = $lastWord($parentFullName);

        return $childLast !== '' && $parentLast !== '' && $childLast === $parentLast;
    }

    private function handleRegParentChildUid($chatId, string $text)
    {
        $childUid = preg_replace('/\s+/', '', trim($text));

        $studentUser = User::where(function($q) use ($childUid) {
                $q->where('university_id', $childUid)
                  ->orWhere('username', $childUid)
                  ->orWhere('username', 'std_' . $childUid)
                  ->orWhereHas('student', fn($sq) => $sq->where('student_code', $childUid));
            })
            // عمود users.role محذوف (الدور عبر role_id)، والاستعلام عنه كان يرمي خطأ SQL في كل مرة
            ->where('role_id', 3)
            ->first();

        if (!$studentUser) {
            $this->sendMessage(
                $chatId,
                "❌ **لم يتم العثور على طالب بهذا الرقم الجامعي: `{$childUid}`**\n\nيرجى التأكد من الرقم الجامعي للابن/الابنة وإعادة إدخاله:"
            );
            return;
        }

        // نفس شرط التسجيل من التطبيق: اسم عائلة ولي الأمر يطابق اسم عائلة الطالب،
        // وإلا يكفي معرفة الرقم الجامعي لأي طالب لتسجيل نفسك وليّ أمره والاطلاع على بياناته.
        $parentName = (string) Cache::get("reg_par_name_{$chatId}", '');
        if (!$this->parentLastNameMatchesChild($parentName, $studentUser)) {
            $this->sendMessage(
                $chatId,
                "❌ **اسم العائلة لا يطابق اسم عائلة الطالب.**
تأكد من كتابة اسمك الكامل (الاسم + اسم العائلة) ومن الرقم الجامعي، أو تواصل مع إدارة شؤون الطلاب."
            );
            return;
        }

        Cache::put("reg_par_child_user_id_{$chatId}", $studentUser->user_id, 1800);
        Cache::put("reg_par_child_name_{$chatId}", $studentUser->full_name, 1800);
        Cache::put("telegram_state_{$chatId}", 'reg_parent_phone', 1800);

        $this->sendMessage(
            $chatId,
            "✅ **تم التحقق والتعرف على الطالب:**\n🎓 **{$studentUser->full_name}** ({$studentUser->department})\n\n📌 **الخطوة 3 من 4:**\nيرجى إدخال **رقم هاتف ولي الأمر** (مثال: `0987654321`):"
        );
    }

    private function handleRegParentPhone($chatId, string $text)
    {
        $phone = preg_replace('/\s+/', '', trim($text));
        if (mb_strlen($phone) < 8) {
            $this->sendMessage($chatId, "⚠️ يرجى إدخال رقم هاتف صحيح.");
            return;
        }

        Cache::put("reg_par_phone_{$chatId}", $phone, 1800);
        Cache::put("telegram_state_{$chatId}", 'reg_parent_password', 1800);

        $this->sendMessage(
            $chatId,
            "📌 **الخطوة 4 من 4 (الأخيرة):**\nيرجى إدخال **كلمة المرور** لحساب ولي الأمر (8 أحرف أو أرقام على الأقل):"
        );
    }

    private function handleRegParentPassword($chatId, string $text)
    {
        $password = trim($text);
        if (mb_strlen($password) < 8) {
            $this->sendMessage($chatId, "⚠️ يجب ألا تقل كلمة المرور عن 8 خانات. يرجى إعادة الإدخال:");
            return;
        }

        $name        = Cache::get("reg_par_name_{$chatId}");
        $childUserId = Cache::get("reg_par_child_user_id_{$chatId}");
        $childName   = Cache::get("reg_par_child_name_{$chatId}", 'الطالب');
        $phone       = Cache::get("reg_par_phone_{$chatId}");

        if (!$name || !$childUserId) {
            $this->sendMessage($chatId, "⚠️ انتهت مهلة الجلسة، يرجى البدء من جديد عبر /start.");
            Cache::forget("telegram_state_{$chatId}");
            return;
        }

        $parts = explode(' ', $name, 2);
        $firstName = $parts[0] ?? $name;
        $lastName  = $parts[1] ?? '';

        $phoneClean = preg_replace('/[^0-9]/', '', $phone);
        $email = "parent_{$phoneClean}@edubridge.edu";
        $username = "par_" . $phoneClean;

        if (User::where('email', $email)->exists()) {
            $email = "parent_{$phoneClean}_" . time() . "@edubridge.edu";
        }

        DB::beginTransaction();
        try {
            $user = User::create([
                'role_id'           => 4,
                'full_name'         => $name,
                'first_name'        => $firstName,
                'last_name'         => $lastName,
                'username'          => $username,
                'email'             => $email,
                'password'          => Hash::make($password),
                'phone'             => $phone,
                'telegram_chat_id'  => (string)$chatId,
                'branch'            => 'الفرع الرئيسي',
                'status'            => 'active',
            ]);

            Parents::create([
                'user_id' => $user->user_id,
            ]);

            DB::table('parent_students')->insert([
                'parent_id'    => $user->user_id,
                'student_id'   => $childUserId,
                'relationship' => 'ولي أمر',
                'created_at'   => now(),
                'updated_at'   => now(),
            ]);

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Telegram Parent Registration Error: ' . $e->getMessage());
            $this->sendMessage($chatId, "❌ حدث خطأ أثناء إنشاء الحساب. يرجى المحاولة لاحقاً عبر /start.");
            return;
        }

        // مسح الكاش المؤقت
        Cache::forget("telegram_state_{$chatId}");
        Cache::forget("reg_par_name_{$chatId}");
        Cache::forget("reg_par_child_user_id_{$chatId}");
        Cache::forget("reg_par_child_name_{$chatId}");
        Cache::forget("reg_par_phone_{$chatId}");

        Cache::forget('admin_profile_stats');
        Cache::forget('distinct_user_actions');

        \App\Models\UserActivity::log('إنشاء حساب جديد', "تم تسجيل حساب ولي أمر جديد عبر بوت التلغرام: {$name} للابن: {$childName}", $user);

        $welcomeMsg = "🎉 **تهانينا! تم إنشاء وتفعيل حساب ولي الأمر بنجاح!** 👨‍👩‍👧‍👦\n\n"
            . "👤 **الاسم:** {$name}\n"
            . "🎓 **الطالب المرتبط:** {$childName}\n"
            . "📞 **رقم الهاتف:** `{$phone}`\n"
            . "📧 **اسم المستخدم:** `{$username}`\n"
            . "─────────────\n"
            . "⚡ تم ربط حسابك بالبوت تلقائياً ويمكنك الآن متابعة الحضور والدرجات وإرسال الإجازات للأبناء مباشرة أدناه:";

        $this->sendParentMainMenu($chatId, $welcomeMsg);
    }
}



