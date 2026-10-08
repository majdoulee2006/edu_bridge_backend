<?php

return [
    // تشغيل/إيقاف الإرسال الأسبوعي كاملًا
    'enabled' => env('DIGEST_ENABLED', true),

    // استخدام Gemini لتحسين صياغة النص. الحقائق والأرقام تُحسب دائمًا في PHP،
    // وأي نص يخالف الحقائق يُرفض ويُستبدل بالقالب الثابت.
    'use_ai' => env('DIGEST_USE_AI', true),

    // موعد الإرسال الأسبوعي (الخميس مساءً: آخر يوم دوام فعلي)
    'send_day'  => env('DIGEST_SEND_DAY', 'thursday'),
    'send_time' => env('DIGEST_SEND_TIME', '18:00'),

    // حدود تصنيف نبرة الملخص
    'concern_unexcused_absences' => 3,  // غيابات غير معذورة في الأسبوع
    'concern_missing_assignments' => 3, // واجبات فائتة في الأسبوع
    'grade_trend_delta' => 3.0,         // فرق (نقاط مئوية) يُعدّ ارتفاعًا/هبوطًا
    'upcoming_days' => 7,               // نافذة "واجبات قادمة"
];
