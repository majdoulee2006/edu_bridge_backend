<?php

namespace App\Services\Ai;

/**
 * دليل المنظومة (تطبيق Flutter + الويب) مقسّم حسب الدور، فيُرسل للنموذج
 * قسم دور المستخدم فقط (لا تُسرَّب شاشات/روابط الأدوار الأخرى).
 */
class AiGuide
{
    public function forRole(string $role, string $baseHttp): string
    {
        $role = AiRole::normalize($role);
        $base = rtrim($baseHttp, '/');
        $login = $base . LoginLinkSanitizer::LOGIN_PATH;

        return self::GUIDES[$role] . "\n\n- رابط تسجيل الدخول على الويب (بوابة موحدة لكل الأدوار): {$login}\n\n" . self::RULES;
    }

    private const RULES = <<<'TXT'
اللوائح الأكاديمية العامة:
• نظام الإنذارات (الوحيد المعتمد) يقوم على عدد أيام الغياب غير المعذورة إجمالاً عبر كل المواد: إنذار أول عند 7 أيام، إنذار ثانٍ مع استدعاء ولي الأمر تلقائياً عند 10، وإنذار نهائي وإحالة للإدارة ورئيس القسم عند 15.
• لا تذكر نسباً مئوية للحرمان ولا تخترع قرار حرمان؛ القرار النهائي بيد الإدارة.
• الغياب بعذر معتمد لا يُحتسب. العذر الطبي يُقدَّم خلال 48 ساعة مع التقرير.
• الحد الأدنى للنجاح في أي مقرر 50 من 100.
TXT;

    private const GUIDES = [
        'student' => <<<'TXT'
الطالب - تطبيق الموبايل:
• تسجيل الحضور: الزر الدائري الأصفر العائم (QR مع التحقق البيومتري/الوجه).
• المساعد الذكي: الزر الأصفر المربع بنجوم الذكاء أسفل يمين الشاشة الرئيسية.
• الزر الدائري المركزي (Speed Dial): الجدول (يُحفظ كصورة PNG)، المحاضرات، كشف العلامات/البطاقة الأكاديمية (PDF/Excel)، الواجبات والتكاليف، الحضور والغياب (وتقديم عذر طبي).
• أيقونة القائمة في الهيدر: الخدمات الطلابية (إعادة تعيين الجهاز، مصدقات، اعتراضات)، وطلبات الإذن/الإجازة.
• الشريط السفلي: الرئيسية، الملف الشخصي، الإشعارات، الرسائل مع الأساتذة.
الطالب - الويب: /student/dashboard، /student/schedule، /student/courses، /student/assignments، /student/grades، /student/attendance و/student/warnings، /student/student-services، /student/leave-requests.
TXT,
        'teacher' => <<<'TXT'
الأستاذ - تطبيق الموبايل:
• المساعد الذكي: الزر الأصفر المربع أسفل يمين الشاشة الرئيسية.
• الزر المركزي (Speed Dial): الحضور والغياب (إنشاء جلسة وعرض QR أو رصد يدوي)، الجدول، المحاضرات (رفع ملفات)، الواجبات (نشر وتصحيح)، التقييم والعلامات، استدعاء ولي الأمر.
• الهيدر والشريط السفلي: الإعلانات للشعب، الرسائل، الإشعارات، تقارير طلبات أولياء الأمور.
الأستاذ - الويب: /teacher/attendance (جلسة QR وتقارير PDF/Excel)، /teacher/schedule، /teacher/assignments، /teacher/lectures، /teacher/grade-events، /teacher/advisor (مرشد الدورة).
TXT,
        'parent' => <<<'TXT'
ولي الأمر - تطبيق الموبايل:
• المساعد الذكي: الزر الأصفر المربع أسفل يمين الشاشة الرئيسية.
• الهيدر: قائمة منسدلة للتبديل بين الأبناء ونسبة الحضور والمعدل.
• الزر المركزي (Speed Dial): البطاقة الأكاديمية وكشف العلامات، الواجبات، الأداء، المواعيد والاستدعاءات، الأذونات والإجازات، التقارير.
• الشريط السفلي: المحادثة مع مدرسي الابن والإدارة، الإشعارات، الملف الشخصي.
ولي الأمر - الويب: /parent/dashboard، /parent/children (ربط ابن بكوده)، /parent/schedule، /parent/assignments، /parent/grades، /parent/permissions، /parent/appointments، /parent/reports.
لا تذكر بيانات أي طالب غير أبناء المستخدم.
TXT,
        'hod' => <<<'TXT'
رئيس القسم - تطبيق الموبايل:
• زر نشر إعلان سريع (أصفر أسفل يسار الشاشة الرئيسية) والمساعد الذكي (أسفل اليمين).
• الزر المركزي: التنظيم الأكاديمي (جداول المحاضرات والامتحانات والقاعات والمراقبين)، إدارة الحسابات وتعيين مرشدي الدورات، المواعيد والاستدعاءات، طلبات الإجازات، الإعلانات والتقارير.
رئيس القسم - الويب: /hod/dashboard، /hod/organization، /hod/accounts، /hod/announcements/create، /hod/student-services، /hod/appointments و/hod/summons، /hod/reports، /hod/settings (سياسة الحضور بدون إنترنت).
TXT,
        'affairs' => <<<'TXT'
موظف الشؤون - تطبيق الموبايل:
• المساعد الذكي: الزر الأصفر المربع أسفل يمين الشاشة الرئيسية.
• الزر المركزي: كشف العلامات، إدارة الحسابات، التقويم الجامعي، الأنشطة والرحلات، المواعيد والاستدعاءات، الإجازات والأعذار.
• بوابة الخدمات الطلابية في الهيدر: إعادة تعيين الأجهزة، مصدقات التخرج، الوثائق.
موظف الشؤون - الويب: /affairs/student-services (إعادة تعيين الجهاز المباشر)، /affairs/course-weights (تثقيل وتصدير النتائج)، /affairs/academic-management (تفعيل الفصل وترفيع الطلاب)، /affairs/accounts و/affairs/university-ids، /affairs/calendar، /affairs/academic-card.
TXT,
        'admin' => <<<'TXT'
مدير النظام - الويب (لا توجد شاشات موبايل لهذا الدور): /admin/dashboard، /admin/accounts، /admin/courses و/admin/semesters-subjects، /admin/activity-logs، /admin/reports، /admin/announcements و/admin/notifications.
TXT,
    ];
}
