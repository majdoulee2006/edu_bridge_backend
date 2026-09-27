<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\PersonalAccessToken;
use App\Models\Student;
use App\Models\Schedule;
use App\Models\Attendance;
use App\Models\Course;
use App\Models\Teacher;

class AiAssistantController extends Controller
{
    /**
     * معالجة استفسار المحادثة الذكية
     */
    public function chat(Request $request)
    {
        $request->validate([
            'message' => 'required|string|max:1000',
            'role'    => 'nullable|string',
            'history' => 'nullable|array',
        ]);

        $message = trim($request->input('message'));
        $role = $request->input('role', 'student');

        // استخراج المستخدم من التوكن (Sanctum) إن وُجد
        $user = $request->user();
        if (!$user) {
            $bearer = $request->bearerToken();
            if (!empty($bearer)) {
                $tokenRecord = PersonalAccessToken::findToken($bearer);
                if ($tokenRecord && $tokenRecord->tokenable) {
                    $user = $tokenRecord->tokenable;
                }
            }
        }

        if ($user && !empty($user->role)) {
            $role = $user->role;
        }

        // بناء السياق الحي للمستخدم من قاعدة البيانات لجميع الأدوار
        $userLiveContext = $this->buildUserLiveContext($user, $role);

        // 1. فحص توفر مفتاح Gemini API في .env
        $apiKey = env('GEMINI_API_KEY');

        if (!empty($apiKey)) {
            try {
                $reply = $this->callGeminiApi($apiKey, $message, $role, $userLiveContext, $request->input('history', []));
                if (!empty($reply)) {
                    return response()->json([
                        'success' => true,
                        'reply'   => $reply,
                        'source'  => 'gemini',
                    ]);
                }
            } catch (\Throwable $e) {
                Log::warning('Gemini API call failed, falling back to local academic engine: ' . $e->getMessage());
            }
        }

        // 2. المحرك الأكاديمي المحلي الذكي (Local Knowledge & Data Engine)
        $localReply = $this->generateLocalAcademicResponse($message, $role, $user);

        return response()->json([
            'success' => true,
            'reply'   => $localReply,
            'source'  => 'local_engine',
        ]);
    }

    /**
     * استدعاء Google Gemini API مع الدليل الشامل المزدوج (موبايل + ويب)
     */
    protected function callGeminiApi(string $apiKey, string $message, string $role, string $userContext, array $history): ?string
    {
        $systemPrompt = "أنت 'EduBridge AI'، المساعد الذكي الرسمي الحصري الشامل لمنظومة معهد وجامعة 'EduBridge' الأكاديمية المتكاملة.\n\n"
            . "🎯 القواعد الأساسية في التفكير والإجابة:\n"
            . "1. ركّز إجابتك حصراً ومباشرة على السؤال الأخير المطروح من المستخدم الآن. لا تكرر إجابات الأسئلة السابقة ولا تدمج الأسئلة معاً.\n"
            . "2. المنظومة تتألف من منصتين رئيسيتين: (تطبيق الموبايل Flutter App) و (منظومة لوحات تحكم الويب Laravel Web Platform).\n"
            . "   - عندما يسألك المستخدم عن أي وظيفة أو استفسار أو إجراء، اشرح له دائماً كيف يصل إليها في المنصتين:\n"
            . "     📱 **عبر تطبيق الموبايل:** اشرح له اسم الشاشة والزر الدقيق ومكانه (مثلاً: الزر الدائري المركزي Speed Dial، زر مسح الـ QR أسفل يسار الشاشة، زر الـ AI أسفل يمين الشاشة، القائمة العلوية...).\n"
            . "     💻 **عبر منصة الويب:** اذكر له اسم لوحة التحكم ورابطها وصفحتها في القائمة الجانبية (مثلاً: لوحة تحكم المعلم /teacher، لوحة الشؤون /affairs، لوحة رئيس القسم /hod، لوحة المدير /admin...).\n"
            . "3. استخدم معلومات وسجلات المستخدم الحقيقية المرفقة أدناه (المواد، جدول المحاضرات بالأيام والساعات والقاعات، وأرقام الحضور والغياب) للإجابة بشكل دقيق وملموس وحاسم:\n"
            . "   - إذا سأل: 'إيمت عندي مادة كذا؟'، استخرج المادة من جدوله المرفق واذكر له اليوم والتوقيت والقاعة واسم الأستاذ.\n"
            . "   - إذا سأل: 'كم نسبة غيابي؟'، استخرج أرقام الحضور والغياب الفعلية المرفقة ووضّح له موقفه من إنذار الـ 15% أو الحرمان 20%.\n"
            . "4. أسلوبك: فخم، دافئ، منظم بالنقاط والرموز التعبيرية المناسبة، وباللغة العربية الفصحى الواضحة والداعمة.\n\n"
            . "═════════════════════════════════════════════════════════════\n"
            . $this->getDualPlatformEcosystemGuide() . "\n"
            . "═════════════════════════════════════════════════════════════\n"
            . $userContext;

        // تحضير المحتوى مع ضمان التبديل النظامي user -> model
        $contents = [];
        $contents[] = [
            'role'  => 'user',
            'parts' => [['text' => "تعليمات النظام الأساسية والدليل الشامل للمنظومة:\n" . $systemPrompt]],
        ];
        $contents[] = [
            'role'  => 'model',
            'parts' => [['text' => 'مفهوم تماماً. أنا مساعد EduBridge AI الشامل، مطّلع على تفاصيل تطبيق الموبايل ولوحات تحكم الويب لجميع المستخدمين، وسأجيب على السؤال الحالي المطروح فقط بدقة موضحاً الطريقة في الموبايل والويب ومستنداً لبيانات المستخدم الفعلية المرفقة.']],
        ];

        // تنقية وإضافة سجل المحادثة السابق فقط
        $expectedRole = 'user';
        foreach ($history as $h) {
            $hRole = ($h['role'] ?? '') === 'model' ? 'model' : 'user';
            $hText = trim($h['text'] ?? '');
            if (!empty($hText) && $hRole === $expectedRole) {
                $contents[] = [
                    'role'  => $hRole,
                    'parts' => [['text' => $hText]],
                ];
                $expectedRole = $hRole === 'user' ? 'model' : 'user';
            }
        }

        // السؤال الحالي
        $contents[] = [
            'role'  => 'user',
            'parts' => [['text' => $message]],
        ];

        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
        ])->timeout(20)->post("https://generativelanguage.googleapis.com/v1beta/models/gemini-3.8-flash:generateContent?key={$apiKey}", [
            'contents' => $contents,
            'generationConfig' => [
                'temperature'     => 0.6,
                'maxOutputTokens' => 900,
            ],
        ]);

        if ($response->successful()) {
            $data = $response->json();
            return $data['candidates'][0]['content']['parts'][0]['text'] ?? null;
        }

        return null;
    }

    /**
     * دليل منظومة EduBridge الشامل والمتكامل (موبايل + ويب لكافة المستخدمين)
     */
    protected function getDualPlatformEcosystemGuide(): string
    {
        return <<<GUIDE
دليل منظومة EduBridge المتكاملة (تطبيق الموبايل + منصات الويب):

اولاً: بنية وأزرار تطبيق الموبايل (Flutter Mobile App):
1. **الشاشة الرئيسية (Home):**
   - **زر مسح كود الـ QR:** الزر الدائري الأصفر العائم في أسفل يسار الشاشة الرئيسية (فوق شريط التنقل). مخصص للطالب لفتح الكاميرا ومسح كود QR المحاضرة لتسجيل الحضور اللحظي بالبصمة البيومترية.
   - **زر المساعد الذكي EduBridge AI:** الزر الأصفر المربع ذو الحواف الدائرية مع نجوم الذكاء الاصطناعي في أسفل يمين الشاشة الرئيسية (فوق شريط التنقل) متاح لكافة المستخدمين.
   - **زر إضافة إعلان سريع:** لرئيس القسم فقط، يظهر باللون الأصفر أسفل يسار شاشته الرئيسية.
   - **الزر المركزي الدائري الفخم (Speed Dial):** يتوسط الشريط السفلي ويفتح قائمة الخدمات:
     * **المحاضرات:** استعراض المقررات والملخصات والملفات المرفوعة.
     * **كشف العلامات:** نتائج المذاكرات والامتحانات والمسار التراكمي وتصدير PDF.
     * **الجدول:** جدول المحاضرات والامتحانات مع زر تنزيل الجدول مباشرة كصورة PNG عالية الدقة في استوديو الهاتف.
     * **الواجبات والتكاليف:** الاطلاع على التكاليف وتسليم الواجبات ومتابعة درجاتها.
     * **الحضور والغياب:** سجل تفصيلي بالجلسات وإمكانية تقديم عذر طبي مع صورة التقرير الطبي.
   - **شريط التنقل السفلي (Bottom Bar):**
     * الرئيسية (Home)
     * الملف الشخصي (Profile)
     * الإشعارات (Notifications): تنبيهات المواد والعلامات والغياب الفورية.
     * الرسائل (Messages): الدردشة المباشرة مع الأساتذة أو أولياء الأمور والمجموعات.
2. **بوابة الخدمات الطلابية في الموبايل:**
   - تفتح من أيقونة القائمة العلوية في الهيدر.
   - تتيح: طلب إعادة تعيين الجهاز (Device Reset) في حال تغيير الهاتف لتسجيل الـ QR، طلب مصدقات التخرج، كشف علامات، وتقديم عذر رسمي.
3. **واجهات ولي الأمر في الموبايل:**
   - شاشة الأبناء في الهيدر لاختيار الابن، متابعة نسبة حضوره ومعدله التراكمي لحظياً، الإشعارات، التواصل المباشر مع أساتذة ابنه.

ثانياً: منظومة لوحات تحكم الويب المتكاملة (Laravel Web Portals):
1. **لوحة تحكم الأستاذ (Teacher Web Portal) - الرابط: `/teacher/attendance` و `/teacher/login`:**
   - **رصد الحضور والغياب:** رصد يومي وجلسة بجلسة للطلاب، وعرض كود الـ QR الديناميكي على شاشة القاعة.
   - **تصدير تقارير الحضور والغياب المتقدمة:**
     * **تقرير PDF رسمي للطباعة:** تنسيق معتمد مع فواصل صفحات ذكية، ترويسة الجدول وتواقيع المشرف ورئيس القسم.
     * **تقرير Excel ملكي تفاعلي (Interactive Excel Workbook):** شيت رئيسي بحزم الأيام وأعمدة الجلسات ودوام اليوم ومعادلات SUM و AVERAGE، وشيت مفصول للطلاب المتجاوزين لنسبة الغياب مع أوتوفلتر تفاعلي.
   - **رصد درجات الطلاب:** إدخال وتعديل درجات الأعمال الفصلية والمذاكرات الدورية.
   - **نشر المحاضرات والواجبات:** رفع الملفات والروابط للطلاب.
2. **لوحة تحكم شؤون الطلاب (Affairs Web Portal) - الرابط: `/affairs/login`:**
   - **إدارة الشعب والطلاب:** تسجيل وتوزيع الطلاب على الشعب والمقررات.
   - **معالجة الخدمات الطلابية:** قبول أو رفض طلبات إعادة تعيين الأجهزة (Direct Reset Device) بنقرة زر، معالجة الأعذار الطبية، وإصدار المصدقات.
   - **إصدار كشوف العلامات ومحاضر الدفعات المعتمدة:** تصدير كشوف العلامات الرسمية ومحاضر الدفعة كاملة بصيغة طباعة PDF.
   - **أوزان المقررات (`course-weights`):** توزيع النسب المئوية لأعمال الفصل والمذاكرات والامتحان.
3. **لوحة تحكم رئيس القسم (Head of Department - HOD Web) - الرابط: `/hod/login`:**
   - **إعداد جداول المحاضرات والامتحانات:** توزيع القاعات، الأوقات، والمراقبين والأساتذة.
   - **نشر الإعلانات الرسمية للقسم:** وتحديد الفئة المستهدفة (طلاب أو أساتذة أو الجميع).
   - **متابعة تقارير الشعب:** الاطلاع على نسب الحضور ومحاضر الدفعة واعتماد النتائج.
4. **لوحة تحكم المدير العام (Admin Web Portal) - الرابط: `/admin/login`:**
   - **إدارة المستخدمين والصلاحيات:** إنشاء وإدارة حسابات الأساتذة، رؤساء الأقسام، وموظفي الشؤون.
   - **تقارير وإحصائيات النظام الشاملة:** الرسوم البيانية لنسب النجاح والرسوب، مراقبة النشاطات وسجلات النظام.
5. **لوحات الويب للطلاب وأولياء الأمور (`/student/login` و `/parent/login`):**
   - تتيح للطالب وولي الأمر فتح حسابهم من أي متصفح لمتابعة كشف الدرجات، الجداول، والخدمات.

ثالثاً: لوائح المعهد الأكاديمية العامة:
- الحضور لليوم الأكاديمي يحتسب بحضور جلسة واحدة على الأقل في ذلك اليوم.
- إنذار الغياب الأولي يصدر رسمياً عند بلوغ نسبة الغياب 15%.
- الحرمان من دخول الامتحان النهائي للمقرر يصدر تلقائياً عند تجاوز نسبة الغياب 20%.
- الحد الأدنى للنجاح في أي مقرر هو 50 من 100.
GUIDE;
    }

    /**
     * بناء سياق بيانات المستخدم الحية من قاعدة البيانات لجميع الأدوار
     */
    protected function buildUserLiveContext($user, string $role): string
    {
        if (!$user) {
            return "سياق المستخدم: مستخدم عام (لم يتم التعرف على جلسته بعد).";
        }

        $context = "بيانات وسجلات المستخدم الحقيقية من قاعدة بيانات EduBridge:\n";
        $context .= "- اسم المستخدم: " . ($user->full_name ?? $user->name ?? 'غير محدد') . "\n";
        $context .= "- الصلاحية / الدور: " . $role . "\n";

        if ($role === 'student' || ($user->role ?? '') === 'student') {
            $student = $user->student ?? Student::where('user_id', $user->user_id)->first();
            if ($student) {
                $context .= "- الرقم الجامعي/الكود الأكاديمي: " . ($student->student_code ?? $user->university_id ?? 'غير متوفر') . "\n";
                $context .= "- التخصص / الاختصاص: " . ($user->branch ?? 'معلوماتية') . "\n";
                $context .= "- السنة الدراسية / المستوى: " . ($student->level ?? $user->academic_year ?? 'السنة الثانية') . "\n";

                // المقررات المسجل بها
                $courses = DB::table('enrollments')
                    ->join('courses', 'enrollments.course_id', '=', 'courses.course_id')
                    ->where('enrollments.student_id', $student->student_id)
                    ->pluck('courses.title')
                    ->toArray();
                if (!empty($courses)) {
                    $context .= "- المقررات المسجل بها هذا الفصل: " . implode('، ', $courses) . "\n";
                }

                // الجداول والمحاضرات الأسبوعية الفعلية
                $academicYearStr = str_replace('السنة ال', 'سنة ', $user->academic_year ?? $student->level ?? '');
                $branchName = DB::table('programs')->where('id', $student->program_id)->value('name') ?? $user->branch ?? '';
                $classGroup = $branchName . ' - ' . $academicYearStr;

                $schedules = Schedule::where('class_group', $classGroup)
                    ->orWhereHas('course.students', function($q) use ($student) {
                        $q->where('enrollments.student_id', $student->student_id);
                    })
                    ->with(['course', 'course.teachers.user'])
                    ->get();

                if ($schedules->isNotEmpty()) {
                    $context .= "- جدول المحاضرات الأسبوعي الفعلي للطالب:\n";
                    $dayMap = [
                        'Sunday'    => 'الأحد',
                        'Monday'    => 'الاثنين',
                        'Tuesday'   => 'الثلاثاء',
                        'Wednesday' => 'الأربعاء',
                        'Thursday'  => 'الخميس',
                        'Friday'    => 'الجمعة',
                        'Saturday'  => 'السبت',
                    ];
                    foreach ($schedules as $sch) {
                        $d = $dayMap[$sch->day] ?? $sch->day;
                        $cTitle = $sch->course->title ?? 'مقرر';
                        $tName = $sch->course->teachers->first()->user->full_name ?? 'مدرس المقرر';
                        $room = $sch->room ?? $sch->location ?? 'القاعة المعتمدة';
                        $time = substr($sch->start_time, 0, 5) . ' إلى ' . substr($sch->end_time, 0, 5);
                        $context .= "  * يوم {$d}: محاضرة '{$cTitle}' من الساعة {$time} في ({$room}) - مع الأستاذ: {$tName}\n";
                    }
                }

                // إحصائيات الحضور والغياب
                $attendances = Attendance::where('student_id', $student->student_id)->get();
                $totalSessions = $attendances->count();
                if ($totalSessions > 0) {
                    $present = $attendances->whereIn('status', ['present', 'late'])->count();
                    $absent = $attendances->where('status', 'absent')->count();
                    $attendanceRate = round(($present / $totalSessions) * 100, 1);
                    $absenceRate = round(($absent / $totalSessions) * 100, 1);
                    $context .= "- إحصائيات وسجل الحضور والغياب الفعلي:\n";
                    $context .= "  * إجمالي الجلسات المنعقدة: {$totalSessions}\n";
                    $context .= "  * عدد جلسات الحضور: {$present}\n";
                    $context .= "  * عدد جلسات الغياب: {$absent}\n";
                    $context .= "  * نسبة الحضور: {$attendanceRate}%\n";
                    $context .= "  * نسبة الغياب: {$absenceRate}%\n";
                }
            }
        } elseif ($role === 'teacher' || ($user->role ?? '') === 'teacher') {
            $teacher = $user->teacher ?? Teacher::where('user_id', $user->user_id)->first();
            if ($teacher) {
                $courses = Course::where('teacher_id', $teacher->teacher_id)->pluck('title')->toArray();
                if (!empty($courses)) {
                    $context .= "- المقررات المكلف بتدريسها الأستاذ: " . implode('، ', $courses) . "\n";
                }
                $schedules = Schedule::whereHas('course', function($q) use ($teacher) {
                    $q->where('teacher_id', $teacher->teacher_id);
                })->with('course')->get();
                if ($schedules->isNotEmpty()) {
                    $context .= "- جدول تدريس الأستاذ:\n";
                    foreach ($schedules as $sch) {
                        $context .= "  * يوم {$sch->day}: مادة '{$sch->course->title}' للشعبة {$sch->class_group} من {$sch->start_time} إلى {$sch->end_time}\n";
                    }
                }
            }
        } elseif ($role === 'parent' || ($user->role ?? '') === 'parent') {
            $children = DB::table('parent_students')
                ->join('students', 'parent_students.student_id', '=', 'students.student_id')
                ->join('users', 'students.user_id', '=', 'users.user_id')
                ->where('parent_students.parent_id', function($q) use ($user) {
                    $q->select('parent_id')->from('parents')->where('user_id', $user->user_id)->limit(1);
                })
                ->select('users.full_name', 'students.student_code', 'students.level', 'users.branch')
                ->get();
            if ($children->isNotEmpty()) {
                $context .= "- قائمة الأبناء المرتبطين بحساب ولي الأمر:\n";
                foreach ($children as $ch) {
                    $context .= "  * الطالب: {$ch->full_name} (كود: {$ch->student_code}) - تخصص {$ch->branch} - {$ch->level}\n";
                }
            }
        } elseif ($role === 'boss' || $role === 'hod' || ($user->role ?? '') === 'hod') {
            $context .= "- رئيس قسم معتمد في النظام، يمتلك صلاحيات إدارة الجداول، توزيع القاعات، نشر الإعلانات، ومتابعة الشعب.\n";
        } elseif ($role === 'affairs' || ($user->role ?? '') === 'affairs') {
            $pendingServices = DB::table('student_requests')->where('status', 'pending')->count();
            $context .= "- موظف شؤون الطلاب، مسؤول عن معالجة طلبات إعادة تعيين الأجهزة، الأعذار، وتصدير كشوف العلامات. (الطلبات المعلقة حالياً: {$pendingServices}).\n";
        }

        return $context;
    }

    /**
     * محرك الاستجابة الأكاديمي المحلي الذكي (Fallback)
     */
    protected function generateLocalAcademicResponse(string $message, string $role, $user): string
    {
        $q = mb_strtolower($message, 'UTF-8');

        // الحضور والغياب والإنذارات
        if (str_contains($q, 'غياب') || str_contains($q, 'حضور') || str_contains($q, 'انذار') || str_contains($q, 'إنذار') || str_contains($q, 'حرمان')) {
            return "📌 **لوائح الحضور والغياب الأكاديمية (EduBridge):**\n\n"
                . "• يُحتسب اليوم حضوراً أكاديمياً بمجرد حضور جلسة واحدة على الأقل.\n"
                . "• **نسبة الإنذار:** يُصدر النظام إنذاراً أولياً للطالب عند بلوغ نسبة الغياب **15%**.\n"
                . "• **نسبة الحرمان:** يُحرم الطالب رسمياً من دخول الامتحان النهائي للمقرر عند تجاوز الغياب **20%**.\n\n"
                . "📱 **عبر الموبايل:** افتح الزر الدائري المركزي (Speed Dial) ثم اختر **'الحضور والغياب'** لفحص رصيد جلساتك وتقديم الأعذار.\n"
                . "💻 **عبر الويب:** يمكن للمعلم رصد الحضور وتصدير التقارير عبر لوحة المعلم `/teacher/attendance`، وتتابع الشؤون الحالات عبر لوحة `/affairs`.";
        }

        // الامتحانات والبرنامج
        if (str_contains($q, 'امتحان') || str_contains($q, 'جدول') || str_contains($q, 'دوام') || str_contains($q, 'محاضر')) {
            return "📅 **الجداول والمواعيد الدراسية:**\n\n"
                . "• تم اعتماد ونشر جداول الامتحانات والدوام الأسبوعي للشعب.\n\n"
                . "📱 **عبر الموبايل:** من الزر الدائري المركزي (Speed Dial) اضغط على **'الجدول'** ويمكنك تنزيله فورياً كصورة PNG عالية الدقة.\n"
                . "💻 **عبر الويب:** يمكن لرئيس القسم إعداد وتعديل الجداول وتوزيع القاعات والمراقبين من لوحة `/hod`، بينما يمكن للطلاب وأولياء الأمور استعراضها من حساباتهم.";
        }

        // العلامات والمسار الأكاديمي
        if (str_contains($q, 'علام') || str_contains($q, 'درج') || str_contains($q, 'معدل') || str_contains($q, 'كشف') || str_contains($q, 'مسار')) {
            return "📊 **نظام التقييم والعلامات:**\n\n"
                . "• تتوزع العلامات على الأعمال الفصلية، المذاكرات الدورية، والامتحان النهائي (الحد الأدنى للنجاح 50/100).\n\n"
                . "📱 **عبر الموبايل:** من الزر المركزي (Speed Dial) اضغط على **'العلامات'** لرؤية مسارك التراكمي وتصدير كشف العلامات PDF.\n"
                . "💻 **عبر الويب:** يقوم الأستاذ برصد العلامات في لوحته `/teacher`، وتقوم الشؤون باعتماد وطباعة الكشوف الرسمية ومحاضر الدفعات من `/affairs/course-weights`.";
        }

        // الخدمات والطلبات
        if (str_contains($q, 'خدم') || str_contains($q, 'طلب') || str_contains($q, 'جهاز') || str_contains($q, 'عذر') || str_contains($q, 'شهادة')) {
            return "📑 **بوابة الخدمات الإلكترونية:**\n\n"
                . "• **إعادة تعيين الجهاز (Device Reset):** إذا غيرت هاتفك وتريد مسح الـ QR من جهاز جديد.\n"
                . "• **الأعذار الطبية:** يُرجى رفع الإشعار الطبي خلال 48 ساعة لدراسته من قبل الشؤون.\n\n"
                . "📱 **عبر الموبايل:** اضغط على أيقونة القائمة الجانبية في أعلى الهيدر بالشاشة الرئيسية ثم اختر **'الخدمات الطلابية'** وقدم طلبك وتتبعه.\n"
                . "💻 **عبر الويب:** يقوم موظف الشؤون بمراجعة الطلبات والضغط على 'إعادة تعيين الجهاز مباشرة' بنقرة زر واحدة عبر لوحة `/affairs/student-services`.";
        }

        return "شكراً لتواصلك مع **EduBridge AI**! 🌟\n\n"
            . "أنا جاهز لإرشادك في أي خدمة أكاديمية أو إدارية سواء كنت تستخدم **تطبيق الموبايل** أو **لوحات تحكم الويب**. كيف يمكنني مساعدتك؟";
    }
}
