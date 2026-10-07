# التثبيت والتشغيل

> **ملاحظة صدق:** هذا الدليل مبني على قراءة ملفات الإعداد والسكربتات الموجودة. خطوات التشغيل المحلي مُجرَّبة جزئياً (شُغِّلت الـ migrations من الصفر بنجاح). أما **نشر الإنتاج** فهو توصيات، لأن المشروع لم يُنشر على خادم إنتاج مُعدّ في المستودع.

## 1) المتطلبات

| المكوّن | الإصدار |
|---|---|
| PHP | ^8.2 (مع `pdo_mysql`, `gd`, `mbstring`, `openssl`, `zip`, `fileinfo`) |
| Composer | 2.x |
| MySQL | 8.x (مُجرَّب 8.4) بترميز `utf8mb4` |
| Node.js | 18+ (لـ Vite و`puppeteer-core`) |
| Flutter | SDK بإصدار Dart ^3.10 |
| حسابات خارجية | Firebase (FCM)، Pusher، Telegram Bot، Google Gemini (اختياري)، SMTP |

## 2) تشغيل الـ Backend محلياً

```bash
# 1. الاعتماديات
composer install
npm install

# 2. الإعداد
cp .env.example .env          # ثم عدّلي القيم (القسم 3)
php artisan key:generate

# 3. قاعدة البيانات
#   أنشئي قاعدة فارغة (utf8mb4_unicode_ci) ثم:
php artisan migrate --force
php artisan db:seed           # بيانات تجريبية (القسم 4)

# 4. التشغيل
php artisan serve --host=0.0.0.0 --port=8000
npm run dev                   # أو: npm run build للإنتاج
php artisan queue:work        # عامل الطوابير (FCM / Telegram)
php artisan schedule:work     # المهام المجدولة محلياً (بدل Cron)
```

أو الاختصار الجاهز: `composer run dev` (يشغّل الخادم والطابور والسجلات وVite معاً).

> **ملف الإعداد `.env.example` ناقص:** أضفتُ إليه `TELEGRAM_WEBHOOK_SECRET` و`GEMINI_API_KEY`. بقية متغيرات Laravel القياسية (قاعدة البيانات، البريد، Pusher) يلزم نسخها من القسم 3.

## 3) متغيرات البيئة (`.env`)

| المتغير | الغرض | ملاحظة |
|---|---|---|
| `APP_ENV` | `local` للتطوير، **`production` للإنتاج** | ⚠️ في `local` يُستخدم OTP ثابت `123456` (انظر الأمان) |
| `APP_DEBUG` | `false` في الإنتاج | |
| `APP_URL` | رابط السيرفر | يُستخدم لبناء روابط الملفات |
| `DB_CONNECTION/HOST/PORT/DATABASE/USERNAME/PASSWORD` | MySQL | |
| `SESSION_DRIVER` | `file` حالياً | `database` أو `redis` للإنتاج |
| `QUEUE_CONNECTION` | `database` | يتطلب `queue:work` |
| `BROADCAST_CONNECTION` | `pusher` | |
| `PUSHER_APP_ID/KEY/SECRET/CLUSTER` | الدردشة اللحظية | مفتاح التطبيق والـ cluster (`eu`) مكتوبان في `chat_service.dart` ويجب مطابقتهما |
| `MAIL_MAILER/HOST/PORT/USERNAME/PASSWORD/FROM_ADDRESS` | رسائل OTP | ⚠️ القيمة الحالية `log` لا ترسل شيئاً |
| `TELEGRAM_BOT_TOKEN` | البوت | من BotFather |
| `TELEGRAM_WEBHOOK_SECRET` | سرّ التحقق من الـ webhook | مطلوب في الإنتاج |
| `TELEGRAM_FORWARD_NOTIFICATIONS` | نسخ كل إشعار بالتطبيق إلى تيليغرام المستخدم | `true` (الافتراضي)؛ `false` للإيقاف |
| `GEMINI_API_KEY` | المساعد الذكي | اختياري؛ بدونه يعمل المحرك المحلي |
| `SESSION_LIFETIME` | دقائق (افتراضي 20) | |

ملف خارج `.env`: **`storage/app/firebase-service-account.json`** (حساب خدمة Firebase لإرسال FCM). غير موجود في المستودع عمداً.

## 4) البيانات التجريبية

`php artisan db:seed` يشغّل `DatabaseSeeder`. توجد seeders متعددة تحت `database/seeders/` (أقسام وبرامج ومقررات ومعلّمون وطلاب وأولياء أمور وحضور وعلامات). أوامر توليد إضافية:

```bash
php artisan schedules:generate --fresh   # جداول دراسية وامتحانات
php artisan grades:generate --fresh      # علامات تجريبية
```

> ⚠️ تحقّقي من كلمات مرور حسابات الـ seeders وغيّريها أو احذفيها قبل أي نشر.

## 5) تشغيل بوت Telegram

خياران:
1. **Webhook (إنتاج):** يلزم رابط HTTPS عام، ويُسجَّل الـ webhook لدى Telegram مع `secret_token` مطابق لـ `TELEGRAM_WEBHOOK_SECRET`:
   `https://api.telegram.org/bot<TOKEN>/setWebhook?url=<APP_URL>/api/telegram/webhook&secret_token=<SECRET>`
2. **Polling (تطوير/بدون رابط عام):** `php artisan telegram:poll` (أو `start_bot.bat` على Windows).

## 6) تشغيل تطبيق Flutter

```bash
cd Edu_Pridge_flutter
flutter pub get
flutter run                     # أندرويد/محاكي
flutter run -d chrome           # ويب
```

**عنوان السيرفر:** مكتوب حالياً في `lib/services/api_service.dart`. يحاول التطبيق تلقائياً: `127.0.0.1` (USB عبر `adb reverse tcp:8000 tcp:8000`) ثم عناوين LAN ثابتة ثم مسح الشبكة. وللويب `http://127.0.0.1:8000`. **ينبغي استبداله بإعداد بيئات (flavors/--dart-define)** قبل أي نشر.

> ملاحظة المنافذ: سكربت `سيرفر/سيرفر.bat` يشغّل الخادم على **8000** (يطابق ما يفترضه التطبيق غير الويب).

**Firebase:** `android/app/google-services.json` موجود. للويب، مفاتيح Firebase مكتوبة في `lib/main.dart`.

**نموذج الوجه:** `assets/models/mobile_face_net.tflite`. ⚠️ ترخيص هذا الملف غير موثَّق صراحة من مصدره (انظر `assets/models/NOTICE.md`)، ويلزم حسمه قبل أي استخدام تجاري.

## 7) توصيات النشر للإنتاج

لم يُعدّ المستودع لذلك، فهذه إرشادات:

1. **خادم:** Linux + Nginx + PHP-FPM + MySQL. جذر الويب `public/`.
2. **`.env`:** `APP_ENV=production`, `APP_DEBUG=false`, بريد SMTP حقيقي، `SESSION_DRIVER=database|redis`، `SESSION_SECURE_COOKIE=true`.
3. **HTTPS إجباري** (الكود يفرضه عند `production`).
4. **Cron:** `* * * * * cd /path && php artisan schedule:run >> /dev/null 2>&1`
5. **Supervisor** لعامل الطوابير: `php artisan queue:work --tries=3`.
6. **التخزين:** `php artisan storage:link`، ومنع تنفيذ PHP داخل `storage/` و`public/uploads/` في إعداد Nginx، ونقل `public/uploads/faces` خارج الجذر العام.
7. **التحسين:** `php artisan config:cache && route:cache && view:cache`، و`composer install --no-dev --optimize-autoloader`.
8. **النسخ الاحتياطي:** `php artisan db:export` أو `mysqldump` مجدول.
9. **Flutter:** بناء `flutter build apk --release` (أندرويد) مع عنوان الإنتاج، وتوقيع التطبيق.

## 8) مشاكل شائعة

| العَرَض | السبب والحل |
|---|---|
| الدردشة لا تتحدث لحظياً | تحقق من `BROADCAST_CONNECTION=pusher` ومفاتيح Pusher، ومن أن `/api/broadcasting/auth` يرجع 200. (يعمل polling احتياطي كل ثانيتين) |
| لا تصل رسائل OTP بالبريد | `MAIL_MAILER=log` (يكتب في `storage/logs` فقط) |
| لا تصل إشعارات FCM | غياب `storage/app/firebase-service-account.json`، أو لا يوجد `queue:work` |
| الرسائل المؤقتة لا تُحذف | لا يوجد Cron لـ `schedule:run` |
| التطبيق لا يجد السيرفر | `adb reverse tcp:8000 tcp:8000` أو ضبط العنوان في `api_service.dart` |
| خطأ 429 | تجاوز حدّ المعدّل (الدخول 5/دقيقة، OTP 10/ساعة لكل بريد) |
| خطأ 423 عند الدخول | الحساب مقفول (5 فشلات) أو مسجّل من جهاز آخر |

## 9) توزيع تطبيق الأندرويد وتحديثه

التطبيق غير منشور على Google Play؛ يُحمَّل **من سيرفر المعهد مباشرة** عبر صفحة عامة:

- صفحة التحميل: `/app` (مثلاً `http://82.137.250.43:8080/edu_bridge/public/app`) وفيها زر التحميل وخطوات التثبيت ورمز QR وأزرار المشاركة.
- التحميل المباشر: `/app/download` (نسخة 64-بت)، و`/app/download?abi=v7a` (نسخة الهواتف القديمة 32-بت).
- فحص الإصدار للتطبيق: `GET /api/app-version[?abi=arm64|v7a]` (عام، بدون تسجيل دخول).
- الملفات داخل `storage/app/app-release/`: `edubridge.apk` و`edubridge-v7a.apk` و`release.json`.

**نشر إصدار جديد:**

1. ارفعي الرقم بعد `+` في `pubspec.yaml` (مثلاً `1.0.1+2`).
2. من مجلد تطبيق Flutter: `flutter build apk --release --split-per-abi`.
3. انسخي `app-arm64-v8a-release.apk` إلى `edubridge.apk`، و`app-armeabi-v7a-release.apk` إلى `edubridge-v7a.apk`.
4. عدّلي `release.json` (`version_name` و`version_code` و`changelog`، ولا تغيّري `min_version_code` إلا لفرض التحديث).
5. ارفعي مجلد الباك إند إلى السيرفر.

**قاعدة التوقيع:** يتم التحديث فوق النسخة المثبتة (دون حذف البيانات) فقط إذا بقي `applicationId` نفسه ومفتاح التوقيع نفسه ورقم الإصدار أعلى. مفتاح الإصدار (`android/edubridge-release.jks` و`android/key.properties`) غير مرفوع إلى Git ويجب حفظ نسخة احتياطية منه. التفاصيل الكاملة في الملف `APP_RELEASE_GUIDE_FOR_AI.md` في جذر المشروع.
