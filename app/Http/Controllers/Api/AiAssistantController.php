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

        // 🌟 تحديد الرابط الفعلي المباشر للسيرفر بشكل ديناميكي كامل وفقاً للشبكة الحالية (WiFi/LAN/Port)
        $baseHttp = $this->resolveServerBaseUrl($request);

        // بناء السياق الحي للمستخدم من قاعدة البيانات لجميع الأدوار
        $userLiveContext = $this->buildUserLiveContext($user, $role);

        // 1. فحص توفر مفتاح Gemini API في .env
        $apiKey = env('GEMINI_API_KEY');

        if (!empty($apiKey)) {
            try {
                $reply = $this->callGeminiApi($apiKey, $message, $role, $userLiveContext, $request->input('history', []), $baseHttp);
                if (!empty($reply)) {
                    // تنقية الاستجابة وضمان عدم تسريب أي رابط تسجيل دخول يخص دوراً آخر
                    $cleanReply = $this->sanitizeLoginLinksForRole($reply, $role, $baseHttp);
                    return response()->json([
                        'success' => true,
                        'reply'   => $cleanReply,
                        'source'  => 'gemini',
                    ]);
                }
            } catch (\Throwable $e) {
                Log::warning('Gemini API call failed, falling back to local academic engine: ' . $e->getMessage());
            }
        }

        // 2. المحرك الأكاديمي المحلي الذكي (Local Knowledge & Data Engine)
        $localReply = $this->generateLocalAcademicResponse($message, $role, $user, $baseHttp);
        $cleanLocalReply = $this->sanitizeLoginLinksForRole($localReply, $role, $baseHttp);

        return response()->json([
            'success' => true,
            'reply'   => $cleanLocalReply,
            'source'  => 'local_engine',
        ]);
    }

    /**
     * استدعاء Google Gemini API مع الدليل الشامل المزدوج (موبايل + ويب) لكافة أدوار المنظومة
     */
    protected function callGeminiApi(string $apiKey, string $message, string $role, string $userContext, array $history, string $baseHttp): ?string
    {
        $roleTitle = match($role) {
            'teacher'              => 'أستاذ / عضو هيئة تدريسية',
            'parent'               => 'ولي أمر طالب',
            'hod', 'boss', 'head'  => 'رئيس قسم أكاديمي',
            'affairs'              => 'موظف شؤون طلاب',
            'admin'                => 'مدير عام النظام',
            default                => 'طالب في المعهد',
        };

        $roleClean = strtolower(trim($role));
        $dedicated = match($roleClean) {
            'teacher'             => ['path' => '/teacher/login', 'title' => 'بوابة دخول الأساتذة والمدرسين على الويب'],
            'parent'              => ['path' => '/parent/login',  'title' => 'بوابة دخول أولياء الأمور على الويب'],
            'hod', 'boss', 'head' => ['path' => '/hod/login',     'title' => 'بوابة دخول رئاسة القسم والتنظيم الأكاديمي على الويب'],
            'affairs'             => ['path' => '/affairs/login', 'title' => 'بوابة دخول شؤون الطلاب والمعاملات على الويب'],
            'admin'               => ['path' => '/admin/login',   'title' => 'بوابة الإدارة المركزية الشاملة على الويب'],
            default               => ['path' => '/student/login', 'title' => 'بوابة دخول الطالب على الويب (تدعم التحقق بالوجه والـ OTP عبر تيليغرام)'],
        };
        $dedicatedLoginUrl = "{$baseHttp}{$dedicated['path']}";

        $systemPrompt = "أنت 'EduBridge AI'، المساعد الذكي الرسمي الحصري الشامل لمنظومة معهد وجامعة 'EduBridge' الأكاديمية المتكاملة.\n\n"
            . "👤 **المستخدم المتحدث معك حالياً:** دور وصلاحية المستخدم الحالي هي: [{$roleTitle}] (الكود: {$role}).\n\n"
            . "🎯 القواعد الإلزامية الصارمة في الشخصية والتفكير والرد والأمان:\n"
            . "1. ❤️ **الروح والأسلوب (سلاسة، محبة، ودفء بشري حقيقي):**\n"
            . "   - تحدث بأسلوب ذكي، دافئ، مرن، ودود جداً، ومحب. ابتعد تماماً عن الروبوتية والجفاف أو تكرار القوالب الجامدة.\n"
            . "   - في التحيات والمحادثات اللطيفة (مثل: 'كيفك'، 'مرحبا'، 'شو أخبارك'، 'كيفك اليوم'): رد بلباقة وسلاسة ومحبة وسرور واسأله عن حاله وعبر عن سعادتك بمساعدته.\n"
            . "2. 🛡️⛔ **القاعدة الصارمة للخصوصية والصلاحيات (حظر الإفصاح والتوجيه لما لا يملكه المستخدم):**\n"
            . "   - إذا سأل المستخدم عن أي بيانات أو شاشات أو عمليات أو إجراءات **ليست من صلاحيات دوره الحالي ({$roleTitle})**:\n"
            . "     * مثل: طالب يسأل عن: رصد حضور الطلاب، رصد أو تعديل درجات، بيانات أو علامات طالب آخر، حسابات الكادر، ترفيع الطلاب، صلاحيات الإدارة أو الشؤون أو الأساتذة...\n"
            . "     * أو ولي أمر يسأل عن بيانات طالب آخر غير أبنائه...\n"
            . "     ⛔ **يُمنع منعاً باتاً ومطلقاً أن تذكر له مكان وجودها أو تشرح له كيف يصل إليها في الموبايل أو الويب أو تخبره بأي تفاصيل عنها!**\n"
            . "     ⛔ **يجب عليك الرد عليه حصراً وحزماً ولطفاً بالتالي فقط دون أي شرح إضافي لمكان الميزة:**\n"
            . "     'عذراً، هذه البيانات والإجراءات ليست من صلاحياتك للاطلاع عليها أو إدارتها، وليس من صلاحياتي إخبارك بها أو بتفاصيلها. يرجى مراجعة إدارة المعهد أو المعنيين بذلك.'\n"
            . "3. 📱💻 **التوجيه المزدوج الذكي (موبايل + ويب) - حصراً للميزات المسموحة لدوره:**\n"
            . "   - عندما يسأل المستخدم عن أي ميزة تقع **ضمن صلاحيات دوره فقط**، اشرح له بسلاسة ووضوح أين يجدها:\n"
            . "     📱 **عبر الموبايل:** اشرح له اسم الشاشة والزر بدقة ومكانه.\n"
            . "     💻 **عبر الويب:** اذكر له اسم لوحة التحكم ورابطها وصفحتها المحددة.\n"
            . "4. 🎯⛔ **التركيز الحصري على السؤال الحالي فقط (ممنوع دمج الأسئلة أو العودة للقديم):**\n"
            . "   - السؤال الوحيد المطلوب منك الإجابة عليه الآن هو هذا السؤال الأخير فقط: '{$message}'.\n"
            . "   - **ممنوع منعاً باتاً وقاطعاً** أن تدمج إجابة سؤال قديم مع السؤال الجديد، أو تبدأ بالحديث عما سأله سابقاً، أو تعيد إجابة نقطة أجبت عليها في الرسائل الماضية.\n"
            . "   - تعامل مع السؤال الحالي بتركيز 100% وأعطه جوابه المباشر الشافي وحده دون أي استرجاع لما مضى.\n"
            . "5. 📊 **استخدام البيانات الحقيقية الحية المرفقة:**\n"
            . "   - إذا سأل عن موعد محاضرته، أرقام غيابه، مواده، قاعاته، استخرج له فوراً من بياناته المرفقة بالأسفل وأجبه بالتفصيل الدقيق.\n"
            . "6. ✍️ **إتمام الإجابة بالكامل وعدم بترها نهائياً (إلزامية قصوى):**\n"
            . "   - احرص على أن تكون إجابتك كاملة ومستوفية للشرح ومختومة بخاتمة طبيعية وواضحة.\n"
            . "   - يُمنع منعاً باتاً التوقف في منتصف الجملة أو ترك الرد ناقصاً أو مقطوعاً.\n"
            . "7. 🔐 **رابط تسجيل الدخول المخصص على الويب (قاعدة العزل الأمني الصارم للأدوار):**\n"
            . "   - ⛔ **تحذير أمني صارم جداً ومطلق:** المستخدم الحالي هو [{$roleTitle}]. يُمنع منعاً باتاً وقاطعاً تزويده بأي رابط تسجيل دخول يخص دوراً آخر (مثل شؤون الطلاب، المدرسين، الإدارة، أو أولياء الأمور). إعطاء رابط بوابة شؤون الطلاب (/affairs/login) أو المدرسين (/teacher/login) للطالب يعد خرقاً أمنياً جسيماً ومحظوراً تماماً!\n"
            . "   - عندما يسأل المستخدم عن **رابط تسجيل الدخول على الويب** أو **كيف أدخل على المنصة من المتصفح / موقع المعهد**:\n"
            . "     * الرابط المخصص والوحيد المسموح لك بتقديمه له هو رابط بوابته المعتمدة لدوره حصراً:\n"
            . "       🔗 **`{$dedicatedLoginUrl}`** ({$dedicated['title']})\n"
            . "     * كبديل عام محايد فقط، يمكنك ذكر البوابة الرئيسية الموحدة: **`{$baseHttp}/login`**.\n"
            . "     * ⛔ يُمنع تماماً كتابة أو اختلاق أي دومين وهمي (مثل edubridge.edu أو edubridge.com)، بل يجب دائماً تقديم الرابط الفعلي بالـ IP والبورت الموضح أعلاه: `{$dedicatedLoginUrl}`.\n"
            . "     * وضّح للمستخدم أنه يمكنه نسخ الرابط بالكامل ولصقه مباشرة في شريط عنوان المتصفح للدخول الفوري لبوابته.\n\n"
            . "═════════════════════════════════════════════════════════════\n"
            . $this->getDualPlatformEcosystemGuide($roleClean, $baseHttp) . "\n"
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
            'parts' => [['text' => 'مفهوم تماماً بكل سرور ومحبة. أنا رفيقك ومساعدك EduBridge AI، سأتحدث معك بسلاسة ولطف تام، وسأجيبك حصراً وبدقة على سؤالك الأخير فقط وبالمكان المحدد في الموبايل والويب دون أي دمج أو تكرار لأسئلة سابقة، وسأحرص على أن تكون إجابتي كاملة وتامة دون أي انقطاع.']],
        ];

        // تنقية وإضافة سجل المحادثة السابق فقط (آخر تبادلين فقط كحد أقصى)
        $expectedRole = 'user';
        $trimmedHistory = array_slice($history, -4);
        foreach ($trimmedHistory as $h) {
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

        // السؤال الحالي - التركيز المطلق عليه وحده
        $contents[] = [
            'role'  => 'user',
            'parts' => [['text' => "أجبني على سؤالي التالي الآن مباشرة وبشكل كامل دون تكرار أي إجابة سابقة:\n" . $message]],
        ];

        // الموديلات المعتمدة بالترتيب لضمان أقصى سرعة واستجابة كاملة بدون تعليق
        $models = [
            'gemini-3.5-flash-lite',
            'gemini-3.7-flash',
            'gemini-3.5-flash',
        ];

        foreach ($models as $modelName) {
            try {
                $response = Http::withHeaders([
                    'Content-Type' => 'application/json',
                ])->timeout(15)->post("https://generativelanguage.googleapis.com/v1beta/models/{$modelName}:generateContent?key={$apiKey}", [
                    'contents' => $contents,
                    'generationConfig' => [
                        'temperature'     => 0.7,
                        'maxOutputTokens' => 2500,
                    ],
                ]);

                if ($response->successful()) {
                    $data = $response->json();
                    $text = $data['candidates'][0]['content']['parts'][0]['text'] ?? null;
                    if (!empty($text)) {
                        return $text;
                    }
                } elseif ($response->status() === 429) {
                    Log::warning("Gemini model {$modelName} hit quota limit (429), switching to next model in cascade...");
                    continue;
                }
            } catch (\Throwable $e) {
                Log::warning("Gemini model {$modelName} request error: " . $e->getMessage());
                continue;
            }
        }

        return null;
    }

    /**
     * دليل منظومة EduBridge الشامل والمتكامل (موبايل + ويب لكافة المستخدمين والأدوار)
     */
    protected function getDualPlatformEcosystemGuide(string $userRole = 'student', string $baseHttp = 'http://127.0.0.1:8000'): string
    {
        $r = strtolower(trim($userRole));
        $studentLoginNote = ($r === 'student') ? " - الرابط المباشر الكامل لدخولك: `{$baseHttp}/student/login`" : "";
        $teacherLoginNote = ($r === 'teacher') ? " - الرابط المباشر الكامل لدخولك: `{$baseHttp}/teacher/login`" : "";
        $parentLoginNote  = ($r === 'parent')  ? " - الرابط المباشر الكامل لدخولك: `{$baseHttp}/parent/login`" : "";
        $hodLoginNote     = in_array($r, ['hod', 'boss', 'head']) ? " - الرابط المباشر الكامل لدخولك: `{$baseHttp}/hod/login`" : "";
        $affairsLoginNote = ($r === 'affairs') ? " - الرابط المباشر الكامل لدخولك: `{$baseHttp}/affairs/login`" : "";
        $adminLoginNote   = ($r === 'admin')   ? " - الرابط المباشر الكامل لدخولك: `{$baseHttp}/admin/login`" : "";

        return <<<GUIDE
دليل منظومة EduBridge المتكاملة الشامل (تطبيق الموبايل Flutter + منصات الويب Laravel لجميع المستخدمين):

═════════════════════════════════════════════════════════════════════
أولاً: الطالب (Student):
═════════════════════════════════════════════════════════════════════
📱 **في تطبيق الموبايل (Flutter App):**
• **تسجيل الحضور بالـ QR:** اضغط على الزر الدائري الأصفر العائم أسفل يسار الشاشة الرئيسية (فوق شريط التنقل) لفتح الكاميرا ومسح كود المحاضرة مع البصمة البيومترية.
• **المساعد الذكي EduBridge AI:** الزر الأصفر المربع ذو الحواف الدائرية ونجوم الذكاء أسفل يمين الشاشة الرئيسية.
• **الزر الدائري المركزي (Speed Dial):**
  - **الجدول:** جدول المحاضرات والامتحانات الأسبوعي مع إمكانية حفظه فورياً كصورة PNG عالية الدقة في المعرض.
  - **المحاضرات:** استعراض المواد وتحميل ملفات المحاضرات والملخصات.
  - **كشف العلامات / البطاقة الأكاديمية:** درجات المواد والمذاكرات والمسار التراكمي وتصدير PDF/Excel.
  - **الواجبات والتكاليف:** الاطلاع على التكاليف ورفع ملفات الحل ومتابعة درجات التقييم.
  - **الحضور والغياب:** سجل الجلسات المفصل ونسب الغياب وتقديم عذر طبي مع صورة التقرير الطبي.
• **بوابة الخدمات الطلابية (أيقونة القائمة العلوية في الهيدر):**
  - تقديم وتتبع طلب "إعادة تعيين الجهاز (Device Reset)" عند تغيير الهاتف، طلب مصدقات التخرج، وثائق الدوام، والاعتراضات.
• **طلبات الإذن:** تقديم طلب إذن مغادرة أو إجازة رسمية ومتابعة الموافقة.
• **شريط التنقل السفلي:** الرئيسية (Home)، الملف الشخصي (Profile)، الإشعارات (Notifications)، الرسائل (Messages للتواصل مع الأساتذة).

💻 **في منصة الويب (Student Web Portal{$studentLoginNote}):**
• تسجيل الدخول يدعم التحقق بالوجه (Face Verification) والـ OTP عبر تيليغرام.
• **لوحة التحكم (`/student/dashboard`):** ملخص المقررات، نسبة الدوام والإنذارات.
• **الجدول الدراسي والامتحانات (`/student/schedule`):** تصدير الجدول كصورة PNG عبر `/student/schedule/export-image`.
• **المواد والمحاضرات (`/student/courses`):** تنزيل ملفات الدروس والملخصات.
• **الواجبات والتكاليف (`/student/assignments`):** رفع التكاليف وحلول الواجبات إلكترونياً.
• **كشف العلامات والدرجات (`/student/grades`):** استعراض وتصدير كشف العلامات بصيغة PDF أو Excel.
• **الحضور والغياب والإنذارات (`/student/attendance` و `/student/warnings`):** رصد جلسات الحضور والإنذار (15%) أو الحرمان (20%).
• **الخدمات والطلبات الطلابية (`/student/student-services`):** تقديم وتتبع المعاملات وطلبات إعادة تعيين الجهاز.
• **طلبات الإجازة والإذن (`/student/leave-requests`):** تقديم ومتابعة طلبات الإجازة.

═════════════════════════════════════════════════════════════════════
ثانياً: المعلم / الأستاذ (Teacher):
═════════════════════════════════════════════════════════════════════
📱 **في تطبيق الموبايل (Flutter App):**
• **الزر الدائري المركزي (Speed Dial):**
  - **الحضور والغياب:** إنشاء جلسة حضور وعرض QR للطلاب ورصد الحضور يدوياً.
  - **الجدول الدراسي:** مواعيد المحاضرات والقاعات وتفاصيل الشعب.
  - **المحاضرات:** رفع ملخصات ومواد دراسية للطلاب.
  - **الواجبات:** نشر تكليفات وتحديد مواعيد التسليم وتصحيح واجبات الطلاب.
  - **التقييم والعلامات:** رصد درجات المذاكرات والامتحانات الشفهية والعملية.
  - **استدعاء ولي الأمر (Parent Summon):** طلب مقابلة ولي أمر طالب.
• **الهيدر وشريط التنقل:** نشر إعلانات للشعب، الرسائل مع الطلاب وأولياء الأمور، الإشعارات، تقارير طلبات أولياء الأمور.

💻 **في منصة الويب (Teacher Web Portal{$teacherLoginNote}):**
• **رصد الحضور وعرض الـ QR (`/teacher/attendance`):**
  - بدء جلسة QR ديناميكية متجددة على شاشة القاعة، وإغلاق الجلسة.
  - **تصدير تقارير الحضور المتقدمة:** تقرير PDF رسمي للطباعة بتواقيع المشرف ورئيس القسم، وتقرير Excel ملكي تفاعلي (Interactive Excel Workbook) بحزم الأيام وأعمدة الجلسات وشيت خاص بالمحرومين ومعادلات تلقائية.
• **الجدول التدريسي (`/teacher/schedule`):** تفاصيل أوقات وقاعات الشعب.
• **الواجبات والتكاليف (`/teacher/assignments`):** إنشاء تكليف وتصحيح تسليمات الطلاب ورصد الدرجات.
• **المحاضرات (`/teacher/lectures`):** رفع وإدارة ملفات المحاضرات.
• **الاختبارات والتقييمات (`/teacher/grade-events`):** إنشاء أحداث تقييم ورصد درجات أعمال الفصل والمذاكرات.
• **أدوات مرشد الدورة (`/teacher/advisor`):** رصد حضور الدورة ورفع تقارير شاملة لرئيس القسم.
• **الرسائل الرسمية والإعلانات والتقارير:** تواصل ومتابعة شاملة.

═════════════════════════════════════════════════════════════════════
ثالثاً: ولي الأمر (Parent):
═════════════════════════════════════════════════════════════════════
📱 **في تطبيق الموبايل (Flutter App):**
• **الهيدر:** قائمة منسدلة لاختيار الابن للتبديل الفوري بين الأبناء والاطلاع على نسبة الحضور والمعدل التراكمي لكل ابن.
• **الزر الدائري المركزي (Speed Dial):**
  - **البطاقة الأكاديمية وكشف العلامات:** تفاصيل علامات الابن وتصدير PDF/Excel.
  - **الواجبات:** متابعة التكاليف المسلمة والمتأخرة.
  - **الأداء:** متابعة تقارير المعلمين وسلوك الطالب.
  - **المواعيد والاستدعاءات:** الرد على استدعاءات المعهد أو طلب حجز موعد مقابلة.
  - **الأذونات والإجازات:** تقديم إذن غياب للابن.
  - **التقارير:** طلب تقرير أداء وسلوك مفصل من مرشد الدورة.
• **شريط التنقل:** المحادثة المباشرة مع مدرسي الابن والإدارة، الإشعارات، والملف الشخصي.

💻 **في منصة الويب (Parent Web Portal{$parentLoginNote}):**
• **لوحة التحكم (`/parent/dashboard`):** اختيار الابن ومتابعة مؤشراته الحيوية.
• **ربط الأبناء (`/parent/children`):** ربط حساب ابن جديد عبر كوده الأكاديمي.
• **الجدول الدراسي والواجبات والدرجات (`/parent/schedule` و `/parent/assignments` و `/parent/grades`):** تصدير كشف العلامات PDF/Excel.
• **الأذونات والاستدعاءات (`/parent/permissions` و `/parent/appointments`):** حجز موعد أو تقديم إذن غياب.
• **تقارير الأداء (`/parent/reports`):** طلب ومتابعة تقارير دورية.
• **المراسلات والإشعارات:** تواصل كتابي وتنبيهات فورية عند تسجيل غياب الابن أو صدور علامته.

═════════════════════════════════════════════════════════════════════
رابعاً: رئيس القسم (Head of Department - HOD / Boss):
═════════════════════════════════════════════════════════════════════
📱 **في تطبيق الموبايل (Flutter App):**
• **زر نشر إعلان سريع:** زر أصفر أسفل يسار الشاشة الرئيسية لنشر تعميم عاجل.
• **زر المساعد الذكي EduBridge AI:** أسفل يمين الشاشة.
• **الزر الدائري المركزي (Speed Dial):**
  - **التنظيم الأكاديمي:** جداول المحاضرات، الامتحانات، وتوزيع القاعات والمراقبين.
  - **إدارة الحسابات:** استعراض حسابات أساتذة وطلاب القسم وتعيين مرشدي الدورات.
  - **المواعيد واللقاءات والاستدعاءات:** تنسيق المواعيد مع الكادر وأولياء الأمور.
  - **طلبات الإجازات:** البت في إجازات المدرسين والطلاب.
  - **الإعلانات الرسمية والتقارير:** اعتماد تقارير الشعب ومتابعة نسب النجاح.

💻 **في منصة الويب (HOD Web Portal{$hodLoginNote}):**
• **لوحة التحكم (`/hod/dashboard`):** إحصائيات الدوام، الشعب، ونشاط القسم.
• **التنظيم الأكاديمي (`/hod/organization`):**
  - بناء وتعديل جداول المحاضرات والدوام الأسبوعي.
  - بناء وتعديل جداول الامتحانات وتوزيع القاعات والمراقبين.
  - تعديل وضبط أوزان المقررات (`course weights`).
• **إدارة الحسابات (`/hod/accounts`):** تسجيل أستاذ، طالب، ربط ولي أمر، وتعيين مرشد الدورة (`assign advisor`).
• **الإعلانات الرسمية (`/hod/announcements/create`):** نشر إعلانات القسم وتوجيهها حسب الفئة.
• **الخدمات الطلابية (`/hod/student-services`):** دراسة واعتماد الطلبات الأكاديمية.
• **الاستدعاءات والمواعيد (`/hod/appointments` و `/hod/summons`):** إصدار استدعاء رسمي أو إدارة المقابلات.
• **التقارير الأكاديمية (`/hod/reports`):** كتابة الملاحظات وتصدير تقارير الأداء وإرسالها لأولياء الأمور.
• **سياسات الحضور والغياب (`/hod/settings`):** ضبط سياسات ونسب الدوام.

═════════════════════════════════════════════════════════════════════
خامساً: مسؤول شؤون الطلاب (Affairs Officer):
═════════════════════════════════════════════════════════════════════
📱 **في تطبيق الموبايل (Flutter App):**
• **الزر الدائري المركزي (Speed Dial):**
  - **كشف العلامات الأكاديمي:** استخراج كشوف علامات الطلاب وتصديرها.
  - **إدارة الحسابات:** متابعة وتعديل بيانات الطلاب.
  - **التقويم الجامعي (Calendar):** استعراض وإدارة الأحداث والمواعيد الرسمية.
  - **الأنشطة والفعاليات والرحلات:** تسجيل وإدارة الفعاليات.
  - **المواعيد والاستدعاءات:** جدولة مراجعات الطلاب وأولياء الأمور للشؤون.
  - **الإجازات:** معالجة الإجازات والأعذار الطبية.
• **بوابة الخدمات الطلابية (الهيدر):** البت في طلبات إعادة تعيين الأجهزة (Reset Device) فورياً، مصدقات التخرج، والوثائق.

💻 **في منصة الويب (Affairs Web Portal{$affairsLoginNote}):**
• **الخدمات والطلبات الطلابية (`/affairs/student-services`):**
  - **زر إعادة تعيين الجهاز المباشر (`direct-reset-device`):** فك قفل جهاز الطالب بنقرة زر واحدة فوراً ليتمكن من مسح الـ QR من هاتفه الجديد!
  - معالجة طلبات المصدقات وكشوف العلامات والأعذار الطبية.
• **تثقيل المواد ونتائج الطلاب (`/affairs/course-weights`):**
  - رصد القرارات الأكاديمية للطلاب.
  - **تصدير النتائج الشاملة:** تصدير حسب المقرر، حسب الطالب، أو تصدير الدفعة كاملة (Cohort Export) بصيغة PDF رسمية ومشاركتها مع الطلاب.
• **الإدارة الأكاديمية والفصول (`/affairs/academic-management`):**
  - تفعيل الفصل الدراسي الجديد (`activate semester`).
  - ترفيع وترقية الطلاب للسنة التالية (`promote students`).
  - تعديل المستويات الدراسية.
• **إدارة الحسابات والأرقام الجامعية (`/affairs/accounts` و `/affairs/university-ids`):**
  - تسجيل وتفعيل الحسابات، توليد الأرقام الجامعية، مراجعة الحسابات المعلقة، والموافقة على طلبات تغيير الصور الشخصية (`photo-requests`).
• **التقويم والأحداث (`/affairs/calendar`):** تثبيت العطل والمواعيد الأكاديمية.
• **البطاقة الأكاديمية (`/affairs/academic-card`):** تصدير رسمي للطباعة PDF و Excel.

═════════════════════════════════════════════════════════════════════
سادساً: مدير النظام العام (System Admin):
═════════════════════════════════════════════════════════════════════
💻 **في منصة الويب الإدارية الشاملة (Admin Web Portal{$adminLoginNote}):**
• **لوحة القيادة الشاملة (`/admin/dashboard`):** إحصائيات متكاملة عن المعهد، نسب الطلاب والأساتذة والأقسام.
• **إدارة الحسابات المركزية (`/admin/accounts`):**
  - إنشاء وتفعيل وتعديل وتجميد حسابات (طالب، ولي أمر، أستاذ، رئيس قسم، شؤون).
  - ميزة الحذف الجماعي بالحسابات أو بالدور (`delete-all by role`).
  - ربط وفك ربط الأبناء بأولياء الأمور.
• **الأقسام والبرامج والمقررات (`/admin/courses` و `/admin/semesters-subjects`):**
  - إنشاء الأقسام وتعيين رؤساء الأقسام لها (`assign HOD`).
  - إضافة المقررات والفصول وتوزيعها.
• **سجلات الأمان والنشاطات (`/admin/activity-logs`):** تدقيق ومراقبة حركات الدخول والتعديلات على البيانات مع خيار التنظيف.
• **التقارير والإحصائيات (`/admin/reports`):** توليد وتصدير تقارير النظام الشاملة.
• **الإعلانات والإشعارات العامة المركزية (`/admin/announcements` و `/admin/notifications`).

═════════════════════════════════════════════════════════════════════
سابعاً: القواعد واللوائح الأكاديمية العامة للمعهد:
═════════════════════════════════════════════════════════════════════
• الحضور لليوم الأكاديمي يحتسب بحضور جلسة واحدة على الأقل في ذلك اليوم.
• إنذار الغياب الأولي يصدر رسمياً عند بلوغ نسبة الغياب 15%.
• الحرمان التلقائي من دخول الامتحان النهائي للمقرر يصدر عند تجاوز نسبة الغياب 20%.
• الحد الأدنى للنجاح في أي مقرر هو 50 من 100.
• تقديم الأعذار الطبية يجب أن يتم خلال 48 ساعة من تاريخ الغياب مع إرفاق التقرير الطبي المعتمد.
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
                    ->with(['course', 'course.teachers.user'])
                    ->get();

                if ($schedules->isEmpty()) {
                    $schedules = Schedule::whereHas('course.students', function($q) use ($student) {
                        $q->where('enrollments.student_id', $student->student_id);
                    })->with(['course', 'course.teachers.user'])->get();
                }

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
                $courses = DB::table('course_teachers')
                    ->join('courses', 'course_teachers.course_id', '=', 'courses.course_id')
                    ->where('course_teachers.teacher_id', $teacher->teacher_id)
                    ->pluck('courses.title')
                    ->toArray();
                if (!empty($courses)) {
                    $context .= "- المقررات المكلف بتدريسها الأستاذ: " . implode('، ', $courses) . "\n";
                }
                $schedules = Schedule::whereHas('course.teachers', function($q) use ($teacher) {
                    $q->where('course_teachers.teacher_id', $teacher->teacher_id);
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
     * محرك الاستجابة الأكاديمي المحلي الذكي (Live DB-Powered Academic Engine)
     */
    protected function generateLocalAcademicResponse(string $message, string $role, $user, string $baseHttp = 'http://127.0.0.1:8000'): string
    {
        $q = mb_strtolower($message, 'UTF-8');

        // 1. فحص محاولات تجاوز الصلاحيات (حظر الإفصاح عن البيانات الإدارية أو صلاحيات الكادر لمن لا يملكها)
        if ($role === 'student' || empty($role)) {
            if (str_contains($q, 'رصد حضور') || str_contains($q, 'رصد درجات') || str_contains($q, 'رصد علامات') || str_contains($q, 'تعديل علامات') || str_contains($q, 'تعديل درجات') || str_contains($q, 'حسابات') || str_contains($q, 'كادر') || str_contains($q, 'لوحة المعلم') || str_contains($q, 'لوحة الشؤون') || str_contains($q, 'لوحة المدير') || str_contains($q, 'ترفيع') || str_contains($q, 'بيانات الطلاب')) {
                return "عذراً، هذه البيانات والإجراءات ليست من صلاحياتك للاطلاع عليها أو إدارتها، وليس من صلاحياتي إخبارك بها أو بتفاصيلها. يرجى مراجعة إدارة المعهد أو المعنيين بذلك.";
            }
        }

        // 2. التحيات والمحادثات اللطيفة
        if ($q === 'كيفك' || $q === 'كيفك اليوم' || $q === 'مرحبا' || $q === 'أهلا' || $q === 'اهلين' || $q === 'صباح الخير' || $q === 'مساء الخير' || str_contains($q, 'شو أخبارك') || str_contains($q, 'شو اخبارك') || str_contains($q, 'كيف حالك') || str_contains($q, 'عساك بخير')) {
            return "يا أهلاً وسهلاً بك! أنا بأفضل حال والحمد لله، وكلي طاقة وسعادة لأني معك اليوم. تسلم على سؤالك اللطيف! ❤️\n\n"
                . "طمني عنك كيف حالك؟ أنا جاهز بكل سرور لأساعدك بأي استفسار عن جدولك، محاضراتك، غيابك، أو أي ميزة بالمنظومة! 😊✨";
        }

        // 2.5 استفسار عن رابط أو بوابة تسجيل الدخول على منصة الويب
        if (str_contains($q, 'تسجيل دخول') || str_contains($q, 'تسجيل الدخول') || str_contains($q, 'رابط الدخول') || str_contains($q, 'رابط الويب') || str_contains($q, 'رابط تسجيل') || str_contains($q, 'بوابة الويب') || str_contains($q, 'بوابة الدخول') || str_contains($q, 'كيف بفوت عالويب') || str_contains($q, 'كيف بسجل عالويب') || str_contains($q, 'فوت عالويب') || str_contains($q, 'ادخل عالويب') || str_contains($q, 'موقع المعهد') || str_contains($q, 'رابط المنصة')) {
            $baseHttp = rtrim($baseHttp, '/');

            $roleClean = strtolower($role);
            if ($roleClean === 'teacher') {
                return "🌐 **بوابة تسجيل الدخول الخاصة بالأستاذ / المدرس على الويب:**\n\n"
                    . "• يمكنك نسخ هذا الرابط ولصقه مباشرة في شريط عنوان المتصفح:\n"
                    . "🔗 **`{$baseHttp}/teacher/login`**\n\n"
                    . "• يتيح لك رصد حضور الطلاب عبر رمز الـ QR التفاعلي، رفع المحاضرات، ونشر وتصحيح الواجبات.\n"
                    . "• كما يتوفر الرابط العام الموحد كبديل: `{$baseHttp}/login`.";
            } elseif ($roleClean === 'parent') {
                return "🌐 **بوابة تسجيل الدخول الخاصة بولي الأمر على الويب:**\n\n"
                    . "• يمكنك نسخ هذا الرابط ولصقه مباشرة في شريط عنوان المتصفح:\n"
                    . "🔗 **`{$baseHttp}/parent/login`**\n\n"
                    . "• يتيح لك متابعة دوام الأبناء وعلاماتهم والواجبات وتقديم أذونات الغياب.\n"
                    . "• كما يتوفر الرابط العام الموحد كبديل: `{$baseHttp}/login`.";
            } elseif ($roleClean === 'hod' || $roleClean === 'boss' || $roleClean === 'head') {
                return "🌐 **بوابة تسجيل الدخول الخاصة برئيس القسم الأكاديمي على الويب:**\n\n"
                    . "• يمكنك نسخ هذا الرابط ولصقه مباشرة في شريط عنوان المتصفح:\n"
                    . "🔗 **`{$baseHttp}/hod/login`**\n\n"
                    . "• لإدارة التنظيم الأكاديمي، جداول المحاضرات والامتحانات، وتوزيع القاعات والمراقبين.\n"
                    . "• كما يتوفر الرابط العام الموحد كبديل: `{$baseHttp}/login`.";
            } elseif ($roleClean === 'affairs') {
                return "🌐 **بوابة تسجيل الدخول الخاصة بموظف شؤون الطلاب على الويب:**\n\n"
                    . "• يمكنك نسخ هذا الرابط ولصقه مباشرة في شريط عنوان المتصفح:\n"
                    . "🔗 **`{$baseHttp}/affairs/login`**\n\n"
                    . "• لمعالجة طلبات إعادة تعيين الأجهزة، إصدار مصدقات التخرج، تثقيل المواد، وترفيع وترقية الطلاب.\n"
                    . "• كما يتوفر الرابط العام الموحد كبديل: `{$baseHttp}/login`.";
            } elseif ($roleClean === 'admin') {
                return "🌐 **بوابة تسجيل الدخول الخاصة بالإدارة العامة ومدير النظام على الويب:**\n\n"
                    . "• يمكنك نسخ هذا الرابط ولصقه مباشرة في شريط عنوان المتصفح:\n"
                    . "🔗 **`{$baseHttp}/admin/login`**\n\n"
                    . "• لإدارة الحسابات المركزية، الأقسام، السجلات الأمنية، ومتابعة نشاط النظام الشامل.\n"
                    . "• كما يتوفر الرابط العام الموحد كبديل: `{$baseHttp}/login`.";
            } else {
                // الافتراضي للطالب (Student)
                return "🌐 **بوابة تسجيل الدخول الخاصة بالطالب على الويب:**\n\n"
                    . "• يمكنك نسخ هذا الرابط ولصقه مباشرة في شريط عنوان المتصفح:\n"
                    . "🔗 **`{$baseHttp}/student/login`**\n\n"
                    . "• تدعم البوابة الدخول بالرقم الجامعي وكلمة المرور، بالإضافة لخدمات التحقق بالوجه والـ OTP عبر تيليغرام.\n"
                    . "• كما يتوفر الرابط العام الموحد كبديل: `{$baseHttp}/login`.";
            }
        }

        // 3. الاستفسار عن عدد المحاضرات أو جدول المحاضرات الأسبوعي الفعلي للطالب من قاعدة البيانات
        if (str_contains($q, 'كم محاضرة') || str_contains($q, 'محاضراتي') || str_contains($q, 'محاضرات اليوم') || str_contains($q, 'جدول المحاضرات') || str_contains($q, 'عندي محاضر') || str_contains($q, 'شو عندي محاضر') || str_contains($q, 'ايمت عندي') || str_contains($q, 'إيمت عندي')) {
            if ($user) {
                $student = $user->student ?? Student::where('user_id', $user->user_id)->first();
                if ($student) {
                    $academicYearStr = str_replace('السنة ال', 'سنة ', $user->academic_year ?? $student->level ?? '');
                    $branchName = DB::table('programs')->where('id', $student->program_id)->value('name') ?? $user->branch ?? '';
                    $classGroup = $branchName . ' - ' . $academicYearStr;

                    $schedules = Schedule::where('class_group', $classGroup)
                        ->with(['course', 'course.teachers.user'])
                        ->get();

                    if ($schedules->isEmpty()) {
                        $schedules = Schedule::whereHas('course.students', function($q2) use ($student) {
                            $q2->where('enrollments.student_id', $student->student_id);
                        })->with(['course', 'course.teachers.user'])->get();
                    }

                    if ($schedules->isNotEmpty()) {
                        $count = $schedules->count();
                        $dayMap = [
                            'Sunday'    => 'الأحد',
                            'Monday'    => 'الاثنين',
                            'Tuesday'   => 'الثلاثاء',
                            'Wednesday' => 'الأربعاء',
                            'Thursday'  => 'الخميس',
                            'Friday'    => 'الجمعة',
                            'Saturday'  => 'السبت',
                        ];
                        $reply = "أهلاً بك يا غالي! 🌟 لديك في جدولك الأسبوعي **{$count} محاضرات مسجلة**:\n\n";
                        foreach ($schedules as $idx => $sch) {
                            $num = $idx + 1;
                            $d = $dayMap[$sch->day] ?? $sch->day;
                            $cTitle = $sch->course->title ?? 'مقرر';
                            $tName = $sch->course->teachers->first()->user->full_name ?? 'مدرس المقرر';
                            $room = $sch->room ?? $sch->location ?? 'القاعة المعتمدة';
                            $time = substr($sch->start_time, 0, 5) . ' إلى ' . substr($sch->end_time, 0, 5);
                            $reply .= "{$num}. يوم **{$d}**: محاضرة **'{$cTitle}'** من الساعة {$time} في ({$room}) - مع الأستاذ: {$tName}.\n";
                        }
                        $reply .= "\n📱 **عبر الموبايل:** افتح الزر الدائري المركزي (Speed Dial) ➡️ **'الجدول'** لحفظ جدولك كصورة PNG بجهازك.\n";
                        $reply .= "💻 **عبر الويب:** يمكنك استعراض وطباعة جدولك من لوحة الطالب عبر الرابط: `/student/schedule`.";
                        return $reply;
                    }

                    // في حال عدم وجود جدول زمني ولكن مسجل بمقررات
                    $courses = DB::table('enrollments')
                        ->join('courses', 'enrollments.course_id', '=', 'courses.course_id')
                        ->where('enrollments.student_id', $student->student_id)
                        ->pluck('courses.title')
                        ->toArray();
                    if (!empty($courses)) {
                        $cCount = count($courses);
                        $reply = "أهلاً بك! لديك **{$cCount} مقررات دراسية** مسجلة هذا الفصل:\n";
                        foreach ($courses as $i => $c) {
                            $reply .= ($i + 1) . ". مقرر: **{$c}**\n";
                        }
                        $reply .= "\n📱 **عبر الموبايل:** يمكنك متابعة المواعيد من الزر المركزي (Speed Dial) ➡️ **'الجدول'**.\n";
                        $reply .= "💻 **عبر الويب:** تجد تفاصيلها في صفحة المواد `/student/courses` والجدول `/student/schedule`.";
                        return $reply;
                    }
                }
            }
            return "📅 **جدول المحاضرات والدوام الأسبوعي:**\n\n"
                . "📱 **عبر الموبايل:** اضغط على الزر المركزي (Speed Dial) ثم اختر **'الجدول'** لمشاهدة كافة محاضراتك وتنزيل جدولك كصورة PNG عالية الدقة في المعرض.\n"
                . "💻 **عبر الويب:** سجّل دخولك إلى بوابتك وافتح صفحة **الجدول** عبر الرابط: `/student/schedule`.";
        }

        // 4. الحضور والغياب والإنذارات الحقيقية من قاعدة البيانات
        if (str_contains($q, 'غياب') || str_contains($q, 'حضور') || str_contains($q, 'انذار') || str_contains($q, 'إنذار') || str_contains($q, 'حرمان')) {
            if ($user && ($role === 'student' || ($user->role ?? '') === 'student')) {
                $student = $user->student ?? Student::where('user_id', $user->user_id)->first();
                if ($student) {
                    $attendances = Attendance::where('student_id', $student->student_id)->get();
                    $total = $attendances->count();
                    if ($total > 0) {
                        $present = $attendances->whereIn('status', ['present', 'late'])->count();
                        $absent = $attendances->where('status', 'absent')->count();
                        $rate = round(($absent / $total) * 100, 1);
                        $statusText = $rate >= 20 ? '⚠️ تجاوزت نسبة الحرمان (20%)! يرجى تقديم عذر فوراً للشؤون.' : ($rate >= 15 ? '⚠️ لديك إنذار أولي لتجاوز نسبة 15% غياب.' : '✅ وضعك الأكاديمي سليم وممتاز.');

                        return "📊 **سجل الحضور والغياب الفعلي الخاص بك:**\n\n"
                            . "• إجمالي الجلسات المنعقدة: **{$total} جلسات**\n"
                            . "• عدد جلسات الحضور: **{$present}**\n"
                            . "• عدد جلسات الغياب: **{$absent}** (نسبة الغياب: **{$rate}%**)\n"
                            . "• التقييم الأكاديمي: **{$statusText}**\n\n"
                            . "📱 **عبر الموبايل:** من الزر المركزي (Speed Dial) ➡️ **'الحضور والغياب'** لرؤية تفاصيل كل جلسة وتقديم عذر طبي.\n"
                            . "💻 **عبر الويب:** تابع سجل حضورك وإنذاراتك عبر الرابطين: `/student/attendance` و `/student/warnings`.";
                    }
                }
            }

            return "📌 **لوائح الحضور والغياب الأكاديمية (EduBridge):**\n\n"
                . "• يُحتسب اليوم حضوراً أكاديمياً بمجرد حضور جلسة واحدة على الأقل.\n"
                . "• **نسبة الإنذار:** يُصدر النظام إنذاراً أولياً للطالب عند بلوغ نسبة الغياب **15%**.\n"
                . "• **نسبة الحرمان:** يُحرم الطالب رسمياً من دخول الامتحان النهائي للمقرر عند تجاوز الغياب **20%**.\n\n"
                . "📱 **عبر الموبايل:** افتح الزر الدائري المركزي (Speed Dial) ثم اختر **'الحضور والغياب'** لفحص رصيد جلساتك وتقديم الأعذار.\n"
                . "💻 **عبر الويب:** يمكن للمعلم رصد الحضور وتصدير التقارير عبر لوحة المعلم `/teacher/attendance`، وتتابع الشؤون الحالات عبر لوحة `/affairs`.";
        }

        // 5. الخدمات والطلبات والأعذار وإعادة تعيين الجهاز
        if (str_contains($q, 'خدم') || str_contains($q, 'طلب') || str_contains($q, 'جهاز') || str_contains($q, 'عذر') || str_contains($q, 'شهادة') || str_contains($q, 'إجازة') || str_contains($q, 'اجازة') || str_contains($q, 'اذن') || str_contains($q, 'إذن')) {
            return "📑 **بوابة الخدمات والأعذار وإعادة تعيين الجهاز:**\n\n"
                . "• **إعادة تعيين الجهاز (Device Reset):** إذا غيرت هاتفك وتريد تسجيل الحضور من الهاتف الجديد، ارفع طلباً وسيقوم موظف الشؤون بفك القفل فوراً.\n"
                . "• **الأعذار الطبية:** تُرفع التقارير الطبية خلال 48 ساعة لدراستها واعتمادها.\n\n"
                . "📱 **عبر الموبايل:**\n"
                . "• **للطالب:** اضغط على أيقونة القائمة العلوية في الهيدر بالشاشة الرئيسية ثم اختر **'الخدمات الطلابية'** لطلب إعادة تعيين الجهاز أو مصدقة، أو اختر **'طلبات الإذن'**.\n"
                . "• **لولي الأمر:** من الزر المركزي (Speed Dial) اختر **'الأذونات والإجازات'** لتقديم إذن غياب لابنك.\n\n"
                . "💻 **عبر الويب:**\n"
                . "• **للطالب:** من لوحة الطالب `/student/student-services` أو `/student/leave-requests`.\n"
                . "• **لموظف الشؤون:** من لوحة `/affairs/student-services`، وتوجد ميزة **'إعادة تعيين الجهاز مباشرة (Direct Reset)'** بنقرة زر واحدة لإلغاء القفل فورياً!";
        }

        // 6. الامتحانات وبرنامج الامتحان
        if (str_contains($q, 'امتحان') || str_contains($q, 'برنامج الامتحان') || str_contains($q, 'جدول الامتحانات')) {
            return "📅 **جدول الامتحانات الرسمية:**\n\n"
                . "• تم اعتماد ونشر جداول الامتحانات الرسمية للشعب.\n\n"
                . "📱 **عبر الموبايل:** من الزر الدائري المركزي (Speed Dial) اضغط على **'الجدول'** ويمكنك تنزيله فورياً كصورة PNG عالية الدقة في الاستوديو.\n"
                . "💻 **عبر الويب:** يمكن لرئيس القسم إعداد وتعديل الجداول وتوزيع القاعات والمراقبين من لوحة `/hod/organization`، بينما يستعرضها الطالب من `/student/schedule` وولي الأمر من `/parent/schedule`.";
        }

        // العلامات والمسار الأكاديمي
        if (str_contains($q, 'علام') || str_contains($q, 'درج') || str_contains($q, 'معدل') || str_contains($q, 'كشف') || str_contains($q, 'مسار')) {
            return "📊 **نظام التقييم والعلامات:**\n\n"
                . "• تتوزع العلامات على الأعمال الفصلية، المذاكرات الدورية، والامتحان النهائي (الحد الأدنى للنجاح 50/100).\n\n"
                . "📱 **عبر الموبايل:** من الزر المركزي (Speed Dial) اضغط على **'العلامات'** لرؤية مسارك التراكمي وتصدير كشف العلامات PDF.\n"
                . "💻 **عبر الويب:** يقوم الأستاذ برصد العلامات في لوحته `/teacher`، وتقوم الشؤون باعتماد وطباعة الكشوف الرسمية ومحاضر الدفعات من `/affairs/course-weights`.";
        }

        // الواجبات والتكاليف
        if (str_contains($q, 'واجب') || str_contains($q, 'تليف') || str_contains($q, 'وظيفة') || str_contains($q, 'تسليم')) {
            return "📝 **الواجبات والتكاليف الدراسية:**\n\n"
                . "📱 **عبر الموبايل:**\n"
                . "• **للطالب:** افتح الزر المركزي (Speed Dial) ثم اختر **'الواجبات والتكاليف'** لتنزيل نص التكليف ورفع الحل ومتابعة الدرجة.\n"
                . "• **للأستاذ:** من الزر المركزي (Speed Dial) اختر **'الواجبات'** لإنشاء تكليف جديد وتصحيح حلول الطلاب.\n\n"
                . "💻 **عبر الويب:**\n"
                . "• **للطالب:** من القائمة الجانبية في لوحة الطالب `/student/assignments` لرفع الملفات والحلول.\n"
                . "• **للأستاذ:** من لوحة تحكم المعلم `/teacher/assignments` لإنشاء التكاليف وتنزيل تسليمات الطلاب ورصد درجاتها.";
        }

        // المحاضرات والمواد
        if (str_contains($q, 'محاضرة') || str_contains($q, 'ملخص') || str_contains($q, 'مقرر') || str_contains($q, 'مادة') || str_contains($q, 'ملف')) {
            return "📚 **المقررات والمحاضرات الدراسية:**\n\n"
                . "📱 **عبر الموبايل:**\n"
                . "• **للطالب:** من الزر المركزي (Speed Dial) اختر **'المحاضرات'** للاطلاع على المواد والملخصات وتحميلها.\n"
                . "• **للأستاذ:** من الزر المركزي (Speed Dial) اختر **'المحاضرات'** لرفع ملفات المحاضرات للطلاب.\n\n"
                . "💻 **عبر الويب:**\n"
                . "• **للطالب:** استعراض وتحميل المقررات من لوحة الطالب `/student/courses`.\n"
                . "• **للأستاذ:** إدارة ورفع ملفات الدروس من لوحة المعلم `/teacher/lectures`.";
        }

        // الحسابات والتسجيل
        if (str_contains($q, 'حساب') || str_contains($q, 'تسجيل') || str_contains($q, 'مستخدم') || str_contains($q, 'كلمة سر')) {
            return "👤 **إدارة الحسابات والمستخدمين:**\n\n"
                . "📱 **عبر الموبايل:** يمكن تعديل الملف الشخصي وتغيير كلمة السر من شاشة **'الملف الشخصي'** في شريط التنقل السفلي.\n\n"
                . "💻 **عبر الويب:**\n"
                . "• **لموظف الشؤون:** من لوحة `/affairs/accounts` لإدارة بيانات الطلاب والأرقام الجامعية.\n"
                . "• **لرئيس القسم:** من لوحة `/hod/accounts` لإدارة كادر القسم وتعيين مرشدي الدورات.\n"
                . "• **لمدير النظام:** من لوحة `/admin/accounts` لإنشاء وتعديل وحذف الحسابات وتوزيع الصلاحيات.";
        }

        // الاستدعاءات والمواعيد
        if (str_contains($q, 'استدعاء') || str_contains($q, 'موعد') || str_contains($q, 'مقابلة') || str_contains($q, 'لقاء')) {
            return "🤝 **المواعيد والاستدعاءات الرسمية:**\n\n"
                . "📱 **عبر الموبايل:**\n"
                . "• **لولي الأمر:** من الزر المركزي (Speed Dial) اختر **'المواعيد والاستدعاءات'** للرد على استدعاء أو طلب لقاء.\n"
                . "• **للأستاذ:** من الزر المركزي اختر **'استدعاء ولي الأمر'** لإرسال طلب استدعاء رسمي.\n\n"
                . "💻 **عبر الويب:** يمكن لرئيس القسم وموظف الشؤون وإدارة المعهد إدارة المقابلات والاستدعاءات عبر لوحات `/hod/appointments` و `/affairs/appointments` و `/admin/appointments`.";
        }

        return "شكراً لتواصلك مع **EduBridge AI**! 🌟\n\n"
            . "أنا دليلك ومساعدك الذكي الشامل لمنظومة معهد وجامعة **EduBridge**.\n"
            . "أستطيع إرشادك بدقة لكل ما تحتاجه في **تطبيق الموبايل** (اسم الشاشات والأزرار) وفي **منصة الويب** (الروابط واللوحات).\n\n"
            . "تفضل بطرح سؤالك حول أي خدمة أو ميزة لأي دور (طالب، أستاذ، ولي أمر، شؤون، رئيس قسم، إدارة)!";
    }

    /**
     * تحديد الرابط الفعلي المباشر للسيرفر بشكل ديناميكي كامل وفقاً للشبكة الحالية
     */
    /**
     * مضيف موثوق لروابط الدخول التي يولّدها المساعد.
     */
    protected function isTrustedServerHost(string $host, Request $request): bool
    {
        if (strcasecmp($host, $request->getHost()) === 0) {
            return true;
        }

        $allowed = array_filter(array_map('trim', explode(',', (string) config('app.ai_allowed_hosts'))));
        if (in_array(strtolower($host), array_map('strtolower', $allowed), true)) {
            return true;
        }

        // عنوان IP خاص/محجوز (شبكة محلية: 10.x، 192.168.x، 172.16-31.x)
        return filter_var($host, FILTER_VALIDATE_IP) !== false
            && filter_var($host, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) === false;
    }

    protected function resolveServerBaseUrl(?Request $request = null): string
    {
        $port = 8000;
        $scheme = 'http';

        // 1. إذا أرسل التطبيق server_url صريحاً وكان IP حقيقي (ليس localhost)
        if ($request && $request->filled('server_url')) {
            // server_url يرسله العميل، فلا نثق به إلا إذا كان مضيفاً معروفاً: نفس مضيف الطلب، أو عنوان شبكة محلية،
            // أو مضيفاً مدرجاً في AI_ALLOWED_HOSTS. وإلا استطاع مستخدم جعل المساعد يصدر روابط دخول لنطاق يختاره (تصيّد).
            $parts = parse_url(trim((string) $request->input('server_url')));
            $host  = $parts['host'] ?? null;
            if ($host && in_array($parts['scheme'] ?? '', ['http', 'https'], true)
                && !in_array($host, ['127.0.0.1', 'localhost'], true)
                && $this->isTrustedServerHost($host, $request)) {
                return $parts['scheme'] . '://' . $host . (isset($parts['port']) ? ':' . $parts['port'] : '');
            }
        }

        // 2. فحص الـ Host من الترويسة الحالية للطلب إن لم تكن localhost
        if ($request) {
            $scheme = $request->isSecure() ? 'https' : 'http';
            $httpHost = $request->getHttpHost(); // e.g. 10.102.114.209:8000
            if (!empty($httpHost) && !str_contains($httpHost, '127.0.0.1') && !str_contains($httpHost, 'localhost')) {
                return "{$scheme}://{$httpHost}";
            }
            $reqPort = $request->getPort();
            if (!empty($reqPort) && $reqPort > 0) {
                $port = $reqPort;
            }
        }

        // 3. في حال كان الاتصال عبر USB ADB Reverse (127.0.0.1):
        // نستخرج الـ IP الفعلي لكارت الشبكة النشط (WiFi / Ethernet) عبر جدول توجيه كيرنل النظام
        $lanIp = null;
        try {
            $sock = @stream_socket_client("udp://8.8.8.8:53", $errno, $errstr, 1);
            if ($sock) {
                $name = stream_socket_get_name($sock, false);
                fclose($sock);
                if ($name) {
                    $lanIp = explode(':', $name)[0];
                }
            }
        } catch (\Throwable $e) {}

        if (!empty($lanIp) && $lanIp !== '127.0.0.1') {
            $portSuffix = ($port === 80 && $scheme === 'http') || ($port === 443 && $scheme === 'https') ? '' : ":{$port}";
            return "{$scheme}://{$lanIp}{$portSuffix}";
        }

        // 4. فحص APP_URL من .env إن كان يحوي آي بي شبكة حقيقي
        $appUrl = env('APP_URL');
        if (!empty($appUrl) && !str_contains($appUrl, '127.0.0.1') && !str_contains($appUrl, 'localhost')) {
            return rtrim($appUrl, '/');
        }

        // 5. محاولة قراءة آي بي الجهاز المعتمد
        try {
            $hostIp = gethostbyname(gethostname());
            if (!empty($hostIp) && $hostIp !== '127.0.0.1') {
                $portSuffix = ($port === 80 && $scheme === 'http') || ($port === 443 && $scheme === 'https') ? '' : ":{$port}";
                return "{$scheme}://{$hostIp}{$portSuffix}";
            }
        } catch (\Throwable $e) {}

        $portSuffix = ($port === 80 && $scheme === 'http') || ($port === 443 && $scheme === 'https') ? '' : ":{$port}";
        return "{$scheme}://127.0.0.1{$portSuffix}";
    }

    /**
     * تنقية الروابط وضمان العزل الأمني الصارم لروابط تسجيل الدخول حسب دور المستخدم
     * بحيث يُمنع منعاً باتاً ظهور رابط أي دور آخر مهما كانت الظروف
     */
    protected function sanitizeLoginLinksForRole(string $text, string $role, string $baseHttp): string
    {
        $roleClean = strtolower(trim($role));

        $roleMap = [
            'student'             => ['path' => '/student/login', 'title' => 'بوابة دخول الطالب'],
            'teacher'             => ['path' => '/teacher/login', 'title' => 'بوابة دخول الأساتذة والمدرسين'],
            'parent'              => ['path' => '/parent/login',  'title' => 'بوابة دخول أولياء الأمور'],
            'hod'                 => ['path' => '/hod/login',     'title' => 'بوابة دخول رئاسة القسم'],
            'boss'                => ['path' => '/hod/login',     'title' => 'بوابة دخول رئاسة القسم'],
            'head'                => ['path' => '/hod/login',     'title' => 'بوابة دخول رئاسة القسم'],
            'affairs'             => ['path' => '/affairs/login', 'title' => 'بوابة دخول شؤون الطلاب'],
            'admin'               => ['path' => '/admin/login',   'title' => 'بوابة الإدارة المركزية'],
        ];

        $allowedInfo = $roleMap[$roleClean] ?? $roleMap['student'];
        $allowedPath = $allowedInfo['path'];
        $correctDedicatedUrl = "{$baseHttp}{$allowedPath}";

        // استبدال أي نطاقات وهمية (مثل edubridge.edu أو edubridge.com) بالرابط المعتمد
        $text = preg_replace(
            '#https?://(?:www\.)?edubridge\.(?:edu|com|org|local)(?::\d+)?(/student/login|/teacher/login|/parent/login|/hod/login|/affairs/login|/admin/login|/login)#i',
            "{$baseHttp}$1",
            $text
        );

        // قائمة المسارات الخاصة بالأدوار الأخرى المحظورة على هذا المستخدم
        $allRolePaths = [
            '/student/login',
            '/teacher/login',
            '/parent/login',
            '/hod/login',
            '/affairs/login',
            '/admin/login',
        ];

        foreach ($allRolePaths as $path) {
            if ($path !== $allowedPath) {
                // إذا وجد مسار محظور بالكامل برابط، يستبدل فوراً بالرابط المخصص المسموح
                $text = preg_replace(
                    '#https?://[^\s`"\'\)]+' . preg_quote($path, '#') . '#i',
                    $correctDedicatedUrl,
                    $text
                );
                // استبدال المسار الجزئي إذا ذُكر بمفرده
                $text = str_ireplace($path, $allowedPath, $text);
            }
        }

        // استبدال أي عنوان قديم أو localhost بـ baseHttp الجديد للرابط المسموح والرابط العام
        $text = preg_replace(
            '#https?://(?:192\.168\.\d+\.\d+|10\.\d+\.\d+\.\d+|172\.\d+\.\d+\.\d+|127\.0\.0\.1|localhost)(?::\d+)?(' . preg_quote($allowedPath, '#') . '|/login)#i',
            "{$baseHttp}$1",
            $text
        );

        return $text;
    }
}
