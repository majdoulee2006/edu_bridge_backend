<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

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
        $user = $request->user();

        // 1. فحص توفر مفتاح Gemini API في .env
        $apiKey = env('GEMINI_API_KEY');

        if (!empty($apiKey)) {
            try {
                $reply = $this->callGeminiApi($apiKey, $message, $role, $user, $request->input('history', []));
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
     * استدعاء Google Gemini API
     */
    protected function callGeminiApi(string $apiKey, string $message, string $role, $user, array $history): ?string
    {
        $systemPrompt = "أنت 'EduBridge AI'، المساعد الذكي الرسمي لنظام إدارة المعاهد والجامعات 'EduBridge'. "
            . "أنت تتحدث مع مستخدم بصلاحية: {$role}. "
            . "كن مهذباً، دقيقاً، واستخدم اللغة العربية الفصحى الواضحة والداعمة. "
            . "نظام الغياب: الطالب يعتبر حاضراً لليوم عند حضور جلسة واحدة على الأقل. إنذار الغياب عند 15%، والحرمان من المقرر عند 20%. "
            . "درجة النجاح في المقررات 50/100. "
            . "قدّم إجابات منظمة ومفيدة ومختصرة باستخدام النقاط عند الحاجة.";

        // تحضير التاريخ
        $contents = [];
        $contents[] = [
            'role'  => 'user',
            'parts' => [['text' => "تعليمات النظام الأساسية:\n" . $systemPrompt]],
        ];
        $contents[] = [
            'role'  => 'model',
            'parts' => [['text' => 'مفهوم تماماً، أنا مساعد EduBridge AI وجاهز للإجابة بدقة وفق اللوائح المذكورة.']],
        ];

        foreach ($history as $h) {
            $hRole = ($h['role'] ?? '') === 'model' ? 'model' : 'user';
            $hText = $h['text'] ?? '';
            if (!empty($hText)) {
                $contents[] = [
                    'role'  => $hRole,
                    'parts' => [['text' => $hText]],
                ];
            }
        }

        $contents[] = [
            'role'  => 'user',
            'parts' => [['text' => $message]],
        ];

        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
        ])->timeout(20)->post("https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash:generateContent?key={$apiKey}", [
            'contents' => $contents,
            'generationConfig' => [
                'temperature'     => 0.7,
                'maxOutputTokens' => 800,
            ],
        ]);

        if ($response->successful()) {
            $data = $response->json();
            return $data['candidates'][0]['content']['parts'][0]['text'] ?? null;
        }

        return null;
    }

    /**
     * محرك الاستجابة الأكاديمي المحلي الذكي
     */
    protected function generateLocalAcademicResponse(string $message, string $role, $user): string
    {
        $q = mb_strtolower($message, 'UTF-8');

        // الحضور والغياب والإنذارات
        if (str_contains($q, 'غياب') || str_contains($q, 'حضور') || str_contains($q, 'انذار') || str_contains($q, 'إنذار') || str_contains($q, 'حرمان')) {
            return "📌 **نظام الحضور والغياب الأكاديمي (EduBridge):**\n\n"
                . "• يُحتسب اليوم حضوراً أكاديمياً بمجرد حضور جلسة واحدة على الأقل.\n"
                . "• **نسبة الإنذار:** يُصدر النظام إنذاراً أولياً للطالب عند بلوغ نسبة الغياب **15%**.\n"
                . "• **نسبة الحرمان:** يُحرم الطالب رسمياً من دخول الامتحان النهائي للمقرر عند تجاوز الغياب **20%**.\n"
                . "💡 يمكنك فحص رصيد حضورك الدقيق عبر تبويب 'الحضور' في التطبيق.";
        }

        // الامتحانات والبرنامج
        if (str_contains($q, 'امتحان') || str_contains($q, 'جدول') || str_contains($q, 'دوام') || str_contains($q, 'محاضر')) {
            return "📅 **الجداول والمواعيد الدراسية:**\n\n"
                . "• تم اعتماد ونشر جداول الامتحانات والدوام الأسبوعي للشعب.\n"
                . "• يمكنك عرض الجدول والاطلاع على أسماء القاعات والمراقبين والمدرسين، كما يمكنك تحميله مباشرة كصورة عالية الدقة PNG لحفظه في المعرض بهاتفك.";
        }

        // العلامات والمسار الأكاديمي
        if (str_contains($q, 'علام') || str_contains($q, 'درج') || str_contains($q, 'معدل') || str_contains($q, 'كشف') || str_contains($q, 'مسار')) {
            return "📊 **نظام التقييم والعلامات:**\n\n"
                . "• تتوزع العلامات على الأعمال الفصلية، المذاكرات الدورية، والامتحان النهائي.\n"
                . "• الحد الأدنى للنجاح في أي مقرر هو **50/100**.\n"
                . "• يمكنك استعراض المسار الأكاديمي الكامل لكل الفصول الدراسية واستخراج كشف العلامات بصيغة PDF مباشرة.";
        }

        // الخدمات والطلبات
        if (str_contains($q, 'خدم') || str_contains($q, 'طلب') || str_contains($q, 'جهاز') || str_contains($q, 'عذر') || str_contains($q, 'شهادة')) {
            return "📑 **بوابة الخدمات الإلكترونية:**\n\n"
                . "• **إعادة تعيين الجهاز:** إذا قمت بتغيير هاتفك، يمكنك تقديم طلب فوري لإعادة تعيين البصمة والجهاز لتسجيل الحضور عبر QR.\n"
                . "• **الأعذار الطبية:** يُرجى رفع الإشعار الطبي خلال 48 ساعة من تاريخ الغياب لدراسته من قبل الشؤون.\n"
                . "• **مصدقات التخرج وكشوف الدرجات:** تُطلب عبر قائمة 'الخدمات الطلابية' وتتم معالجتها إلكترونياً.";
        }

        // ترحيب
        if (str_contains($q, 'مرحبا') || str_contains($q, 'أهلا') || str_contains($q, 'السلام') || str_contains($q, 'صباح') || str_contains($q, 'مساء') || $q == 'hi' || $q == 'hello') {
            return "أهلاً وسهلاً بك في **EduBridge AI** 🌟!\n\n"
                . "أنا مساعدك الذكي الخاص بنظام المعهد، جاهز للإجابة عن استفساراتك الأكاديمية، اللوائح، الجداول، والخدمات. تفضل بسؤالي عن أي شيء!";
        }

        // نصائح دراسية
        if (str_contains($q, 'نصيح') || str_contains($q, 'تنظيم') || str_contains($q, 'دراسة') || str_contains($q, 'تفوق')) {
            return "💡 **إرشادات التفوق الدراسي في المعهد:**\n\n"
                . "1. داوم بانتظام وتجنب تخطي عتبة الـ 15% غياب لتفادي التشتت الأكاديمي.\n"
                . "2. راجع المحاضرات ونزل الملخصات من تبويب 'المحاضرات' أسبوعياً.\n"
                . "3. تواصل مع أستاذ المادة عبر الرسائل الفورية في حال واجهت أي صعوبة.\n"
                . "4. نظم أوقات دراستك قبل أسبوعين على الأقل من بدء فترة الامتحانات الرسمية.";
        }

        return "شكراً لتواصلك مع **EduBridge AI**! 🌟\n\n"
            . "أنا مبرمج لمساعدتك في كل ما يخص العمليات الأكاديمية، الحضور، الامتحانات، العلامات واللوائح. كيف يمكنني تقديم المساعدة بشكل أوضح؟";
    }
}
