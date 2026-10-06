# التدفقات الرئيسية

المخططات بصيغة Mermaid (تُرسم تلقائياً في GitHub وموقع التوثيق). كل تدفق مبني على الكود الفعلي، مع ذكر الملف.

## 1) إنشاء الحساب والتفعيل

الحسابات الذاتية للطلاب وأولياء الأمور تمر بمراجعة الشؤون. (المصدر: `Api\AuthController::register`, `AffairsController::approveAccount`)

```mermaid
sequenceDiagram
    autonumber
    actor Aff as موظف الشؤون
    actor U as طالب / ولي أمر
    participant App as تطبيق Flutter
    participant API as Laravel API
    participant DB as قاعدة البيانات
    participant TG as Telegram

    Aff->>API: إضافة رقم جامعي مسبق (university_ids) مع الاسم والصورة
    U->>App: تعبئة نموذج التسجيل (الدور + الرقم الجامعي)
    App->>API: POST /api/register (throttle: otp-send)
    API->>DB: التحقق: الرقم موجود وغير مستخدم
    Note over API,DB: ولي الأمر: رقم الابن + تطابق الاسم
    API->>DB: إنشاء مستخدم status = inactive
    API-->>Aff: إشعار "طلب حساب بانتظار الاعتماد" (داخلي + FCM)
    API-->>App: pending_approval = true
    Aff->>API: POST /api/affairs/accounts/{id}/approve
    API->>DB: تفعيل الحساب
    API->>TG: إشعار المستخدم بالتفعيل
```

## 2) تسجيل الدخول (API)

(المصدر: `Api\AuthController::login`, `SingleSessionGuard`, `LoginThrottleGuard`)

```mermaid
flowchart TD
    A["POST /api/login<br/>username + password (+ device_id)"] --> T{"throttle:login<br/>5 / دقيقة / IP"}
    T -- "تجاوز" --> X429["429"]
    T --> F["بحث المستخدم بـ: username أو email أو phone<br/>أو university_id أو student_code"]
    F -- "غير موجود" --> X404["404"]
    F --> L{"الحساب مقفول؟"}
    L -- "نعم" --> X423a["423 + المدة المتبقية"]
    L -- "لا" --> P{"كلمة المرور صحيحة؟"}
    P -- "لا" --> R["تسجيل فشل<br/>(5 فشلات = قفل 15 دقيقة + إشعار)"] --> X401["401"]
    P -- "نعم" --> S{"status"}
    S -- "pending / inactive" --> X403a["403"]
    S -- "active" --> O{"جلسة موبايل أخرى نشطة؟"}
    O -- "نعم" --> X423b["423 + إشعار محاولة اختراق لصاحب الحساب"]
    O -- "لا" --> ST{"الدور = طالب؟"}
    ST -- "نعم" --> PL{"مرتبط بولي أمر؟"}
    PL -- "لا" --> X403b["403: parent_link_needed"]
    PL -- "نعم" --> DV{"الحساب مقفول على جهاز آخر؟"}
    DV -- "نعم" --> X403c["403: device_locked"]
    DV -- "لا" --> BIND["ربط الجهاز عند أول دخول<br/>+ تسجيل المقررات تلقائياً"]
    ST -- "لا" --> TOK
    BIND --> TOK["إصدار توكن Sanctum جديد<br/>وحذف كل التوكنات السابقة"]
    TOK --> OK["200: token + user(role, role_id, parent_id, is_advisor)"]
```

بدائل الدخول: **OTP عبر البريد** (`/login-otp/send` ثم `/login-otp/verify`)، وللويب صفحة دخول لكل دور مع **التحقق بالوجه** للطالب عند الدخول من أجهزة متعددة.

## 3) تسجيل الحضور (QR + جهاز + موقع + وجه)

(المصدر: `Api\TeacherController::generateQrSession` و`Api\StudentController::scanAttendanceQr`)

```mermaid
sequenceDiagram
    autonumber
    actor Tch as المعلّم
    actor Stu as الطالب
    participant TA as تطبيق المعلّم
    participant SA as تطبيق الطالب
    participant API as Laravel API
    participant DB as قاعدة البيانات

    Tch->>TA: فتح جلسة حضور لمقرر
    TA->>API: POST /api/teacher/attendance/generate-qr
    API->>DB: إنشاء lesson + attendance_session (QR صالح 30ث، الجلسة 10د)
    API-->>TA: qr_token
    loop كل ~30 ثانية
        TA->>API: POST .../session/{id}/refresh-qr
        API-->>TA: qr_token جديد
    end
    Stu->>SA: مسح الـ QR + التقاط وجه
    SA->>SA: كشف الوجه + استخراج بصمة 192 بُعداً (MobileFaceNet)
    SA->>API: POST /api/student/attendance/scan<br/>(qr_token, device_id, lat/lng, face_embedding, scanned_at)
    API->>DB: 1) الجلسة موجودة وضمن وقتها
    API->>DB: 2) سياسة المزامنة (same_day / anytime)
    API->>DB: 3) أهلية الطالب للمقرر
    API->>DB: 4) لم يُسجَّل حضوره مسبقاً
    API->>DB: 5) تطابق الجهاز المرتبط
    API->>DB: 6) المسافة (Haversine) ≤ نصف القطر
    API->>DB: 7) تشابه الوجه ≥ 70%
    alt أي فحص فشل
        API->>DB: تسجيل محاولة مرفوضة + سبب (reject_reason)
        API-->>SA: 4xx + reject_reason
    else كل الفحوص نجحت
        API->>DB: Attendance = present
        API-->>SA: 200 تم تسجيل الحضور
    end
    Tch->>TA: إنهاء الجلسة
    TA->>API: POST .../session/{id}/end
    API->>DB: تسجيل غياب لمن لم يحضر
```

أسباب الرفض (`reject_reason`): `expired_qr`, `sync_timeout`, `lesson_not_found`, `device_mismatch`, `location_too_far`, `face_mismatch`، بالإضافة لأسباب الأهلية وتكرار التسجيل.

## 4) الإنذارات التلقائية للغياب

(المصدر: `AttendanceObserver`, `Services\AbsenceWarningService`)

```mermaid
flowchart LR
    A["سجل حضور بحالة absent<br/>(إنشاء أو تعديل)"] --> O["AttendanceObserver"]
    O --> TG["إشعار Telegram للطالب"]
    O --> W["AbsenceWarningService::checkAndWarn"]
    W --> C["عدّ أيام الغياب الفريدة<br/>عبر كل المقررات"]
    C -->|"≥ 7"| W1["إنذار أول<br/>(إشعار للطالب)"]
    C -->|"≥ 10"| W2["إنذار ثانٍ + استدعاء ولي أمر تلقائي<br/>(parent_summons)"]
    C -->|"≥ 15"| W3["إنذار نهائي + إحالة للإدارة ورئيس القسم"]
```

كل مستوى يُصدر **مرة واحدة** لكل طالب (`student_warnings`). انظر ملاحظة B-07 في حالة المشروع.

## 5) طلب الإجازة

(المصدر: `StudentController::requestAbsence` ← `StudentParentController::respondLeaveRequest` ← `Api\DepartmentHeadController::respondLeaveRequest` ← `AffairsController::updateLeaveStatus`)

```mermaid
stateDiagram-v2
    [*] --> pending_parent: الطالب يقدّم الطلب
    pending_parent --> pending_hod: ولي الأمر يوافق
    pending_parent --> rejected: ولي الأمر يرفض
    pending_hod --> pending_affairs: رئيس القسم يوافق
    pending_hod --> rejected: رئيس القسم يرفض
    pending_affairs --> approved: الشؤون تعتمد
    pending_affairs --> rejected: الشؤون ترفض
    approved --> [*]
    rejected --> [*]
```

> أسماء الحالات الدقيقة تختلف قليلاً بين `leave_requests` و`absence_requests`. راجع الجدولين في قاموس البيانات.

## 6) الدردشة

```mermaid
sequenceDiagram
    autonumber
    participant A as المرسل
    participant API as ChatController
    participant DB as messages
    participant PU as Pusher
    participant B as المستقبل

    A->>API: POST /api/send-message (receiver_id, message/attachment)
    API->>API: canChat(دور المرسل، دور المستقبل)
    alt غير مسموح
        API-->>A: 403
    else مسموح
        API->>DB: حفظ الرسالة (+ المرفق، + وقت انتهاء إن كانت مؤقتة)
        API->>PU: بث MessageSent على chat.{receiver_id} و chat.{sender_id}
        API->>B: FCM (إن لم تكن الإشعارات مكتومة)
        PU-->>B: وصول لحظي
    end
    Note over B: بالتوازي يعمل polling كل ثانيتين كاحتياط
```

مصفوفة من يراسل من:

| المرسل | يستطيع مراسلة |
|---|---|
| الطالب | رئيس القسم، المعلّم، الإدارة |
| المعلّم | المعلّمين، الطلاب، رئيس القسم |
| ولي الأمر | الإدارة، رئيس القسم |
| رئيس القسم | ولي الأمر، المعلّم، الطالب، الإدارة |
| الإدارة | رئيس القسم، الشؤون، المعلّم، الطالب |
| الشؤون | الإدارة |

## 7) استعادة كلمة المرور

- **API:** `forgot-password` يرسل OTP، ثم `reset-password` (بالبريد + الرمز + كلمة جديدة).
- **ويب:** عبر Telegram: `send-otp` ← `verify-otp` (5 محاولات كحد أقصى) ← `reset`، والرمز محفوظ في جلسة المتصفح وصالح 15 دقيقة.

## 8) ماسح تيليغرام

(المصدر: `TelegramBotHandler::handleQrAttendanceMenu`, `TelegramWebhookController`)

```mermaid
sequenceDiagram
    autonumber
    actor Stu as الطالب
    participant Bot as بوت تيليغرام
    participant Web as صفحة الماسح
    participant API as Laravel

    Stu->>Bot: طلب حضور بالـ QR
    Bot->>Bot: رابط موقّع مؤقت (15 دقيقة) مربوط بـ chat_id
    Bot-->>Stu: زر فتح الماسح
    Stu->>Web: فتح الرابط الموقّع
    Web->>API: GET /telegram/scanner (signed:relative)
    API-->>Web: صفحة + scanner_token مشفّر
    Stu->>Web: مسح QR + التقاط وجه
    Web->>API: POST /telegram/record-attendance (scanner_token, qr_token, وجه)
    API->>API: فك التشفير: الهوية من الرمز وليس من العميل
    API-->>Web: تم تسجيل الحضور أو رفض
```

الحالات: `verified` عند مطابقة وجه 70% فأكثر، `first_time` عند أول بصمة، `suspicious` عند غياب بيانات وجه أو تطابق ضعيف (مع تنبيه المعلّم). ويمكن اشتراط الوجه بـ `ATTENDANCE_REQUIRE_FACE=true`.
