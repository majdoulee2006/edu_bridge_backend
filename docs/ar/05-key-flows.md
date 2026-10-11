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

## 3) تسجيل الحضور (المتصل + الأوفلاين الكامل)

(المصدر: `Api\TeacherController`, `Api\StudentController`, `Support\QrTokens`, `Support\TotpQrService`, `Services\AttendanceSessionFinalizer`, `Services\TeacherReceiptService`)

يدعم النظام نمطين متكاملين لتسجيل الحضور:
1. **المسار المتصل (Online):** تدوير الرموز دورياً والتحقق الفوري.
2. **المسار غير المتصل الشامل (Full Offline Attendance - الفكرة 8):**
   - **المعلم أوفلاين:** توليد رموز TOTP محلياً كل 30 ثانية عبر `TotpQrService` (خوارزمية `HMAC-SHA256` مع تسامح زمني `±1`)، وجلب الحصص المجهزة لليوم عبر `GET /api/teacher/attendance/today-sessions`.
   - **الخط الزمني للرموز:** حفظ السجل الزمني الكامل في `attendance_session_tokens` لمنع رفض مسوح الطلاب القديمة (حل مشكلة P1).
   - **منع التكرار (Idempotency):** اعتماد `client_scan_id` فريد لكل مسح لمنع تكرار السجلات أو التعارضات عند إعادة المحاولة.
   - **مزامنة الدفعات:** نقطة `POST /api/student/attendance/sync` لمعالجة حتى 20 مسحاً دفعة واحدة عند استعادة الاتصال.
   - **تأجيل الغياب (Grace Window):** عند إنهاء الجلسة الأوفلاين (`offline_mode != none`)، يتم وسمها بحالة `pending_sync` وتحديد مهلة `sync_deadline_at` دون إنشاء غياب فوري (حماية الطلاب من الإنذارات الخاطئة P3). ويقوم الأمر المجدول `attendance:finalize-sessions` بتسجيل الغياب تلقائياً فقط بعد انقضاء المهلة.
   - **الوضع الصارم الاختياري (Strict Mode - Ed25519):** بعد المسح يعرض الطالب رمز إثبات (`edu-bridge://proof?scan_id=...`)، ويقوم المعلم بمسحه وتوقيع إيصال محلياً بمفتاح جهازه Ed25519، ورفعه عبر `POST /api/teacher/attendance/sync` لترقية مستوى الثقة إلى `countersigned = true` و`time_confidence = high`.

```mermaid
sequenceDiagram
    autonumber
    actor Tch as المعلّم
    actor Stu as الطالب
    participant TA as تطبيق المعلّم
    participant SA as تطبيق الطالب
    participant API as Laravel API
    participant DB as قاعدة البيانات

    rect rgb(240, 248, 255)
    Note over Tch,API: مرحلة الجلسة (متصل أو أوفلاين)
    Tch->>TA: بدء الجلسة أو تفعيل حصة مجهزة
    TA->>TA: توليد رموز TOTP v2 محلياً كل 30ث (أوفلاين)
    Stu->>SA: مسح QR + التقاط الوجه محلياً
    SA->>SA: توليد client_scan_id وحفظ المسح في طابور الأوفلاين
    opt الوضع الصارم (Strict Mode)
        SA->>TA: عرض رمز الإثبات (Proof QR) ومسحه بكاميرا المعلم
        TA->>TA: توقيع إيصال Ed25519 بمفتاح جهاز المعلم
    end
    end

    rect rgb(255, 250, 240)
    Note over Stu,API: مرحلة المزامنة عند عودة الإنترنت
    SA->>API: POST /api/student/attendance/sync (دفعة مسوح)
    API->>DB: مطابقة TOTP أو خط الرموز الزمني + التحقق من الأهلية
    API->>DB: تسجيل حضور مع وسم source=offline و client_scan_id
    API-->>SA: 200 نتيجة المزامنة لكل مسح
    opt إيصالات المعلم
        TA->>API: POST /api/teacher/attendance/sync (إيصالات موثقة)
        API->>DB: التحقق من توقيع Ed25519 وترقية countersigned=true
    end
    end

    rect rgb(255, 245, 245)
    Note over Tch,DB: مرحلة الإنهاء ومهلة المزامنة
    Tch->>TA: إنهاء الجلسة
    TA->>API: POST .../session/{id}/end
    API->>DB: تأجيل الغياب وتعيين sync_deadline_at (pending_sync)
    Note over API,DB: المجدول attendance:finalize-sessions يسجل الغياب فقط بعد انقضاء المهلة
    end
```

أسباب الرفض (`reject_reason`): `expired_qr`, `sync_timeout`, `lesson_not_found`, `device_mismatch`, `location_too_far`, `face_mismatch`, `bad_offline_token`, `bad_receipt`، بالإضافة لأسباب الأهلية وتكرار التسجيل.

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

> كل طلبات الإجازة في `leave_requests` وتمر عبر مسار واحد (`LeaveWorkflow`) للويب وتطبيق Flutter والبوت. جدول `absence_requests` القديم نُسخ إليه ثم حُذف.

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
