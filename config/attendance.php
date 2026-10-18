<?php

return [
    /*
    | اشتراط بيانات الوجه لتسجيل الحضور. عند true يُرفض أي مسح لا يحمل بصمة/صورة وجه.
    | الافتراضي false حفاظاً على توافق الأجهزة/الإصدارات القديمة؛ يُنصح بتفعيله في الإنتاج.
    */
    'require_face' => (bool) env('ATTENDANCE_REQUIRE_FACE', false),

    /*
    | أقصى تقدّم مسموح لوقت المسح الذي يرسله التطبيق عن وقت السيرفر (ثوانٍ).
    | يمنع تزوير وقت مستقبلي؛ وقت الماضي مقيَّد أصلاً بنافذة الجلسة.
    */
    'max_future_skew_seconds' => (int) env('ATTENDANCE_MAX_FUTURE_SKEW', 120),

    // كم ثانية يبقى رمز QR المستبدَل صالحًا بعد استبداله (الرمز يتجدد كل 30 ثانية، وخطوة الوجه عند الطالب تأخذ وقتًا).
    // 0 = لا مهلة (الرمز الحالي فقط). كلما زادت اتسعت نافذة مشاركة صورة الـ QR مع غائب.
    'qr_grace_seconds' => (int) env('ATTENDANCE_QR_GRACE_SECONDS', 90),

    /*
    | مقارنة ساعة هاتف الطالب بساعة السيرفر. فرق أكبر من tolerance_seconds يُعدّ انحرافًا/تلاعبًا:
    | يُصحَّح وقت المسح به، ويُنبَّه الطالب، وإذا تكرر escalate_after مرات خلال alert_window_days يومًا
    | دون أن يؤكد الطالب التنبيه يُشعَر رئيس قسمه.
    */
    'clock' => [
        'tolerance_seconds' => (int) env('ATTENDANCE_CLOCK_TOLERANCE', 120),
        'escalate_after'    => (int) env('ATTENDANCE_CLOCK_ESCALATE_AFTER', 2),
        'alert_window_days' => (int) env('ATTENDANCE_CLOCK_ALERT_DAYS', 14),
    ],

    // كم يومًا بعد الجلسة يستطيع المعلم تسجيل "استثناء" حضور لطالب (بسبب مكتوب)
    'exception_days' => (int) env('ATTENDANCE_EXCEPTION_DAYS', 7),

    /*
    | إعدادات الحضور الأوفلاين الشامل (المرحلة 3)
    */
    'offline' => [
        'enabled'                => (bool) env('ATTENDANCE_OFFLINE_ENABLED', true),
        'max_batch'              => (int) env('ATTENDANCE_OFFLINE_MAX_BATCH', 20),
        'clock_tolerance_seconds'=> (int) env('ATTENDANCE_OFFLINE_CLOCK_TOLERANCE', 60),
        'default_window_hours'   => (int) env('ATTENDANCE_OFFLINE_DEFAULT_WINDOW_HOURS', 48),
    ],
];
