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
 * Telegram bot: student and parent self-registration
 * (moved as-is from TelegramBotHandler; methods keep their names and behaviour)
 */
trait RegistrationHandlers
{
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
