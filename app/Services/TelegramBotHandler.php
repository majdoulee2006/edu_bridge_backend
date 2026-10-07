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
use App\Services\TelegramBot\AuthHandlers;
use App\Services\TelegramBot\StudentHandlers;
use App\Services\TelegramBot\ParentHandlers;
use App\Services\TelegramBot\TeacherHandlers;
use App\Services\TelegramBot\TeacherAssignmentHandlers;
use App\Services\TelegramBot\TeacherLectureHandlers;
use App\Services\TelegramBot\HodHandlers;
use App\Services\TelegramBot\AffairsHandlers;
use App\Services\TelegramBot\AdminHandlers;
use App\Services\TelegramBot\RegistrationHandlers;
use App\Services\TelegramBot\CallbackAuthorization;

class TelegramBotHandler
{
    use AuthHandlers, StudentHandlers, ParentHandlers, TeacherHandlers, TeacherAssignmentHandlers, TeacherLectureHandlers, HodHandlers, AffairsHandlers, AdminHandlers, RegistrationHandlers, CallbackAuthorization;

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

        // حارس مركزي: أزرار كل دور (admin_ / affairs_ / hod_ / teacher_ / parent_) لا تُنفَّذ إلا من حساب بهذا الدور.
        // معالجات الإجراءات نفسها (تعديل حالة حساب، اعتماد طلبات...) لا تفحص الدور، فبدون هذا الحارس
        // يكفي أن يصل callback مزوَّر (مثلاً إن تسرّب سر الـ webhook) لينفَّذ إجراء إداري من أي حساب مربوط.
        $requiredRole = $this->requiredRoleForCallback($data);
        if ($requiredRole !== null && !$this->userHasBotRole($user, $requiredRole)) {
            Log::warning('Telegram callback refused: role mismatch', ['user_id' => $user->user_id, 'required' => $requiredRole]);
            $this->answerCallbackQuery($queryId, "غير مصرّح لك بهذا الإجراء.");
            return;
        }

        // ملكية السجل داخل الزر: ولي الأمر لأبنائه، المعلم لمقرراته/جلساته/تسليماته، الطالب لعذره ومواده، رئيس القسم لطلبات قسمه.
        if (!$this->userMayUseCallback($user, $data)) {
            Log::warning('Telegram callback refused: record not owned by caller', ['user_id' => $user->user_id, 'data' => $data]);
            $this->answerCallbackQuery($queryId, "غير مصرّح لك بالوصول إلى هذا السجل.");
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

}



