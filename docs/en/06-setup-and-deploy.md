# Setup and Deployment

> **Honesty note:** this guide is based on reading the configuration files and scripts that exist. Local-run steps are partially verified (the migrations were run from scratch successfully). **Production deployment** is a set of recommendations, because the repository contains no configured production deployment.

## 1) Requirements

| Component | Version |
|---|---|
| PHP | ^8.2 (with `pdo_mysql`, `gd`, `mbstring`, `openssl`, `zip`, `fileinfo`) |
| Composer | 2.x |
| MySQL | 8.x (tested 8.4) with `utf8mb4` |
| Node.js | 18+ (for Vite and `puppeteer-core`) |
| Flutter | SDK with Dart ^3.10 |
| External accounts | Firebase (FCM), Pusher, Telegram Bot, Google Gemini (optional), SMTP |

## 2) Running the backend locally

```bash
# 1. Dependencies
composer install
npm install

# 2. Configuration
cp .env.example .env          # then edit the values (section 3)
php artisan key:generate

# 3. Database
#   create an empty database (utf8mb4_unicode_ci), then:
php artisan migrate --force
php artisan db:seed           # sample data (section 4)

# 4. Run
php artisan serve --host=0.0.0.0 --port=8000
npm run dev                   # or: npm run build for production
php artisan queue:work        # queue worker (FCM / Telegram)
php artisan schedule:work     # scheduled tasks locally (instead of cron)
```

Or the ready shortcut: `composer run dev` (runs the server, queue, logs and Vite together).

> **`.env.example` is incomplete:** `TELEGRAM_WEBHOOK_SECRET` and `GEMINI_API_KEY` were added to it. The remaining standard Laravel variables (database, mail, Pusher) must be copied from section 3.

## 3) Environment variables (`.env`)

| Variable | Purpose | Note |
|---|---|---|
| `APP_ENV` | `local` for development, **`production` for production** | WARNING: in `local` a fixed OTP `123456` is used (see security) |
| `APP_DEBUG` | `false` in production | |
| `APP_URL` | Server URL | Used to build file links |
| `DB_CONNECTION/HOST/PORT/DATABASE/USERNAME/PASSWORD` | MySQL | |
| `SESSION_DRIVER` | `file` currently | `database` or `redis` for production |
| `QUEUE_CONNECTION` | `database` | Requires `queue:work` |
| `BROADCAST_CONNECTION` | `pusher` | |
| `PUSHER_APP_ID/KEY/SECRET/CLUSTER` | Real-time chat | The app key and cluster (`eu`) are written in `chat_service.dart` and must match |
| `MAIL_MAILER/HOST/PORT/USERNAME/PASSWORD/FROM_ADDRESS` | OTP emails | WARNING: the current value `log` sends nothing |
| `TELEGRAM_BOT_TOKEN` | The bot | From BotFather |
| `TELEGRAM_WEBHOOK_SECRET` | Secret to verify the webhook | Required in production |
| `GEMINI_API_KEY` | AI assistant | Optional; without it the local engine is used |
| `SESSION_LIFETIME` | Minutes (default 20) | |

A file outside `.env`: **`storage/app/firebase-service-account.json`** (Firebase service account for sending FCM). It is intentionally not in the repository.

## 4) Sample data

`php artisan db:seed` runs `DatabaseSeeder`. There are many seeders under `database/seeders/` (departments, programs, courses, teachers, students, parents, attendance, grades). Extra generation commands:

```bash
php artisan schedules:generate --fresh   # timetables and exams
php artisan grades:generate --fresh      # sample grades
```

> WARNING: check the passwords of the seeded accounts and change or remove them before any deployment.

## 5) Running the Telegram bot

Two options:
1. **Webhook (production):** needs a public HTTPS URL, and the webhook is registered with Telegram using a `secret_token` equal to `TELEGRAM_WEBHOOK_SECRET`:
   `https://api.telegram.org/bot<TOKEN>/setWebhook?url=<APP_URL>/api/telegram/webhook&secret_token=<SECRET>`
2. **Polling (development / no public URL):** `php artisan telegram:poll` (or `start_bot.bat` on Windows).

## 6) Running the Flutter app

```bash
cd Edu_Pridge_flutter
flutter pub get
flutter run                     # Android / emulator
flutter run -d chrome           # web
```

**Server address:** currently written in `lib/services/api_service.dart`. The app automatically tries: `127.0.0.1` (USB via `adb reverse tcp:8000 tcp:8000`), then fixed LAN addresses, then a subnet scan. For web it uses `http://127.0.0.1:8000`. **It should be replaced by environment configuration (flavors / `--dart-define`)** before any deployment.

> Port note: the script `سيرفر/سيرفر.bat` runs the server on **8000**, which matches what the app (non-web) assumes.

**Firebase:** `android/app/google-services.json` exists. For web, the Firebase keys are written in `lib/main.dart`.

**Face model:** `assets/models/mobile_face_net.tflite`. WARNING: this file's license is not explicitly documented by its source (see `assets/models/NOTICE.md`), and must be settled before any commercial use.

## 7) Production deployment recommendations

The repository is not set up for it, so these are guidelines:

1. **Server:** Linux + Nginx + PHP-FPM + MySQL. Web root `public/`.
2. **`.env`:** `APP_ENV=production`, `APP_DEBUG=false`, real SMTP, `SESSION_DRIVER=database|redis`, `SESSION_SECURE_COOKIE=true`.
3. **HTTPS is mandatory** (the code forces it in `production`).
4. **Cron:** `* * * * * cd /path && php artisan schedule:run >> /dev/null 2>&1`
5. **Supervisor** for the queue worker: `php artisan queue:work --tries=3`.
6. **Storage:** `php artisan storage:link`; block PHP execution inside `storage/` and `public/uploads/` in the Nginx config; move `public/uploads/faces` outside the public root.
7. **Optimization:** `php artisan config:cache && route:cache && view:cache`, and `composer install --no-dev --optimize-autoloader`.
8. **Backup:** `php artisan db:export` or a scheduled `mysqldump`.
9. **Flutter:** build `flutter build apk --release` (Android) with the production address, and sign the app.

## 8) Common problems

| Symptom | Cause and fix |
|---|---|
| Chat is not real time | Check `BROADCAST_CONNECTION=pusher`, the Pusher keys, and that `/api/broadcasting/auth` returns 200. (A 2-second polling fallback runs) |
| OTP emails do not arrive | `MAIL_MAILER=log` (writes to `storage/logs` only) |
| FCM notifications do not arrive | Missing `storage/app/firebase-service-account.json`, or no `queue:work` |
| Disappearing messages are not deleted | No cron for `schedule:run` |
| The app cannot find the server | `adb reverse tcp:8000 tcp:8000`, or set the address in `api_service.dart` |
| 429 error | Rate limit exceeded (login 5/min, OTP 10/hour per email) |
| 423 on login | The account is locked (5 failures) or signed in on another device |

## 9) Distributing and updating the Android app

The app is not on Google Play; it is downloaded **directly from the institute server** through a public page:

- Download page: `/app` (e.g. `http://82.137.250.43:8080/edu_bridge/public/app`) with the download button, install steps, a QR code and share buttons.
- Direct download: `/app/download` (64-bit build) and `/app/download?abi=v7a` (32-bit build for old phones).
- Version check used by the app: `GET /api/app-version[?abi=arm64|v7a]` (public, no login).
- Files live in `storage/app/app-release/`: `edubridge.apk`, `edubridge-v7a.apk` and `release.json`.

**Publishing a new version:**

1. Raise the number after `+` in `pubspec.yaml` (e.g. `1.0.1+2`).
2. From the Flutter app folder: `flutter build apk --release --split-per-abi`.
3. Copy `app-arm64-v8a-release.apk` to `edubridge.apk` and `app-armeabi-v7a-release.apk` to `edubridge-v7a.apk`.
4. Edit `release.json` (`version_name`, `version_code`, `changelog`; change `min_version_code` only to force an update).
5. Upload the backend folder to the server.

**Signing rule:** an update installs over the existing app (keeping its data) only if the `applicationId` and the signing key are the same and the version number is higher. The release key (`android/edubridge-release.jks` and `android/key.properties`) is not in Git and must be backed up. Full details are in `APP_RELEASE_GUIDE_FOR_AI.md` at the project root.
