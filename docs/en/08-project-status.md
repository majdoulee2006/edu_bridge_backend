# Current Project Status

> **Review date:** 2026-10-05
> **Scope:** the Laravel repository (`edu_bridge_backend`), with references to the Flutter app (`Edu_Pridge_flutter`)
> **Method:** manual code review (static review). **No dynamic penetration tests were run and the project was not load tested.**
> Every item is marked: ✅ confirmed by reading the code, or ⚠️ possible and needs verification.

---

## 1. Executive summary

| Dimension | Assessment |
|---|---|
| Functional completeness | **High.** 6 roles, a mobile app and full web dashboards, real-time chat, QR + face + location attendance, automatic absence warnings, a Telegram bot, an AI assistant |
| Security maturity | **Medium to good after the fixes.** The critical vulnerabilities S-01 to S-07 are closed. Medium items (S-08 onwards) and production settings remain |
| Code quality | **Medium to low.** Very large controllers, logic duplicated between web and API, no automated tests |
| Production readiness | **Nearly ready.** Needs production environment setup (section 6) and the medium items in section 3 |
| Documentation before this set | Almost none (default Laravel/Flutter READMEs) |

---

## 2. What was fixed in this session

| # | Fix | File |
|---|---|---|
| 1 | Removed `/create-student` (created a student account with password `123456`, with no protection) | `routes/web.php` |
| 2 | Removed `/dev/reset-schedules` (ran `truncate` on `schedules`, open to any logged-in user) | `routes/api.php` |
| 3 | Removed `/user/profile/{id}` and `/parent/notifications/{id}` (read any user's data/notifications with no ownership check) | `routes/api.php` |
| 4 | Removed the public `/parent/info/{user_id}` (exposed name and phone with no token) and a duplicate `/system/settings` | `routes/api.php` |
| 5 | `/ai/chat` now requires authentication (protects the Gemini key from public use) | `routes/api.php` |
| 6 | `/student/info/{id}`: added an ownership check (a student sees only themselves; a parent only their linked children) | `routes/api.php` |
| 7 | `/file/{path}`: stopped serving a random substitute PDF, and restricted access with `realpath` inside `storage/app/public` | `routes/api.php` |
| 8 | `/storage/{path}`: path traversal protection via `realpath` | `routes/web.php` |
| 9 | Untracked `ngrok.exe`, `*.sql` files and the SQLite file from git, and updated `.gitignore` | `.gitignore` |

### Second batch (high-severity fixes)

| # | Fix | File |
|---|---|---|
| 10 | **S-01/S-02:** a parent is limited to their linked children (`parentOwnsStudent`) in: performance, assignments, permissions, answering an absence permission, answering a leave request | `StudentParentController.php` |
| 11 | **S-05:** a teacher reaches only their own attendance sessions (`findOwnedSession`): end session, attendee list, QR refresh. Face reset is limited to students of their courses | `Api/TeacherController.php` |
| 12 | **S-03:** attempt limits against OTP guessing (10 per minute per IP, 10 per hour per email) on `verify-otp`, `reset-password` and `login-otp/verify` | `AppServiceProvider.php`, `routes/api.php` |
| 13 | **S-03 (web):** the password-reset code is invalidated after 5 wrong attempts, safe comparison with `hash_equals`, and a limit on sending/verifying the code | `Web/UnifiedAuthController.php`, `routes/web.php` |
| 14 | **S-03:** replaced `rand()` with `random_int()` for OTP generation in 7 web controllers | `Web/*Controller.php` |
| 15 | **S-04:** `request-device-reset` is now under `throttle:login`; limits added on `register`, `resend-otp` and `forgot-password` | `routes/api.php` |
| 16 | **S-07:** verify `X-Telegram-Bot-Api-Secret-Token` on the webhook (`TELEGRAM_WEBHOOK_SECRET`); the request is rejected in production if not configured | `TelegramWebhookController.php`, `config/services.php` |
| 17 | **S-06:** a file-type allow-list (`mimes:`) on chat attachment upload (9 places), with the extension taken from the file content, not its name | `ChatController.php` and others |

**Actual test:** 12 OTP guesses were sent; 10 were accepted for processing (wrong code, `400`) and the rest were rejected with `429`.

**Verification:** all migrations (about 120) were run on a new empty database and succeeded in full. The duplicate files are guarded by `hasColumn/hasTable` or add different columns, so they do not conflict. No migration was deleted on purpose.

---

## 3. Open security issues

### 🔴 Critical / high (all fixed)

#### S-01 ✅ A parent controls any student's permission requests (IDOR + broken authorization)
- **Status: ✅ Fixed**, see second batch (item 10).
- **Location:** `StudentParentController::respondPermission` -> `POST /api/parent/permissions/{requestId}/respond`
- **Problem:** it fetched the request by id only and did not check that the owner is a child of the logged-in parent. Any parent could accept or reject any student's permission by guessing `request_id` (sequential numbers).
- **Impact:** tampering with absence and permission records, and false notifications reaching students.
- **Fix:** verify the link through `parent_students` before any change.

#### S-02 ✅ Reading any student's data from a parent account
- **Status: ✅ Fixed**, see second batch (item 10).
- **Locations (all under `role:parent`):** `getFullPerformance`, `getAssignments`, `getPermissions` (`/api/parent/performance/{studentId}`, `/api/parent/student/{studentId}/assignments`, `/api/parent/student/{studentId}/permissions`)
- **Problem:** no check that `$studentId` is the parent's child. (By contrast `ParentController::getChildDetails` and its siblings check ownership correctly, so the right pattern already exists in the project.)
- **Impact:** leak of grades, assignments and absence permissions of any student.

#### S-03 ✅ Weak OTP verification (guessable)
- **Status: ✅ Fixed**: limits, safe comparison and a secure generator (items 12 to 14). **Remaining warning:** the code fixes the OTP to `123456` when `APP_ENV=local` (`AuthController`, lines 591 and 1029). If a server is deployed with that setting, any account can be taken over. Production must use `APP_ENV=production`, and the exception should preferably be removed from the code.
- **Location:** `AuthController::verifyOtp` and `verifyLoginOtp`
- **Problem:** a 6-digit code (one million possibilities) with no limit on wrong attempts; `verifyLoginOtp` issues a full login token on success, so guessing meant account takeover (knowing the email was enough); generation used `rand()` in the web controllers.

#### S-04 ✅ `requestDeviceReset` bypassed account lockout
- **Status: ✅ Fixed** (item 15).
- **Problem:** it checked the password with `Hash::check` without `LoginThrottleGuard` or `throttle:login`, allowing password guessing without lockout.

#### S-05 ✅ Any teacher controlled other teachers' attendance sessions and students' face data
- **Status: ✅ Fixed** (item 11).
- **Problem:** `endSession`, `getSessionAttendance`, `refreshQrToken` and `resetStudentFace` fetched the record by id without checking it belongs to the logged-in teacher. A teacher could end another's session, which marks everyone who has not attended as absent and triggers warnings and parent summons through `AttendanceObserver`.

#### S-06 ✅ Chat uploads with a client-chosen extension
- **Status: ✅ Fixed** (item 17). **Remaining on the server side:** block PHP execution inside `storage/` and `public/uploads/` (section 6).
- **Problem:** the extension came from `getClientOriginalExtension()` with no allow-list. `.php`, `.html` or `.svg` could be uploaded. If `storage` is served directly through a symlink on Apache/Nginx in production, remote code execution or stored XSS becomes possible.

#### S-07 ✅ Telegram webhook without source verification
- **Status: ✅ Fixed** (item 16). **Required from you:** register the webhook with a `secret_token` and put the same value in `.env`.
- **Problem:** no check of `X-Telegram-Bot-Api-Secret-Token`; anyone who knows the URL could send forged updates to the bot.

### 🟠 Medium (open)

| # | Item | Details |
|---|---|---|
| S-08 ✅ | Sanctum tokens never expire | `config/sanctum.php`: `'expiration' => null`. A token is valid forever unless logged out. (`SingleSessionGuard` has logic that drops a 24-hour idle token, but only on a new login attempt) |
| S-09 ✅ | CORS fully open | `config/cors.php`: `allowed_origins => ['*']` with `allowed_methods => ['*']`. Must be restricted to the project domains in production |
| S-10 ✅ | Face check passes implicitly when there is no reference | `StudentController::scanAttendanceQr`: with no reference photo, `face_score = 96` and `verified`; if vector extraction fails, `90`; at first enrollment or after `requires_face_reset`, any face is accepted and stored as the reference (100%) |
| S-11 ✅ | Face is compared on an embedding the client sends | The `face_embedding` is computed in the app and sent to the server. A modified app (or a direct HTTP request) can send the stored embedding itself. There is no server-side liveness check |
| S-12 ✅ | `scanned_at`, `device_id` and `latitude/longitude` come from the client | All are unsigned values the client sends. A student can forge location, device and time (within the session window). The offline sync policy `anytime` opens the door to late recording |
| S-13 ✅ | Face images in a public folder | Saved in `public/uploads/faces/` and reachable directly by URL. Sensitive biometric data. (The folder was added to `.gitignore`, but it is still public on the server) |
| S-14 ✅ | `POST /telegram/record-attendance` (web, public) | Identifies the student only by the `chat_id` sent in the request, with no login. Whoever knows a student's Telegram `chat_id` and holds a live QR token can record their attendance. The only protection is the optional face check (`face_image` and `face_embedding` are both `nullable`) |
| S-15 ✅ | QR token | `Str::random(32)` (good). But it can be shared with an absent student within the session (mitigated by the face, device and location checks) |
| S-16 ⚠️ | CSRF protection partly disabled | `bootstrap/app.php`: `affairs/accounts` and `affairs/accounts/*` are excluded from CSRF. The reason must be reviewed, since these are account-modifying routes |
| S-17 ✅ | `/broadcasting/auth` | Defined twice (one public, one inside `affairs`). In `routes/channels.php` the `presence-online` channel uses `$user->first_name`, which may be `null` |
| S-18 ⚠️ | `/web-notifications/*` (web) | Relies on `Auth::id()` only (good), but `delete` over `GET/POST` has no dedicated CSRF. Needs verification |
| S-19 ✅ | Link injection into AI assistant replies | `AiAssistantController::resolveServerBaseUrl` accepts `server_url` from the request body as is (any non-localhost domain) and places it in the login links inside the assistant replies. A logged-in user can make the assistant emit links pointing to a domain they choose (phishing). A domain allow-list is needed |

### 🟡 Low / improvements

- ✅ **Self-registration:** `register` accepts `role` from `student|parent` only (good). Rate limits were added after this review (item 15).
- ✅ **Password:** minimum 6 characters, with no complexity rules.
- ✅ **`SESSION_ENCRYPT=false`** and **`SESSION_DRIVER=file`** (does not scale to several servers).
- ✅ **`last_login` and `UserActivity`:** record good events, but there is no alert when a password/email is changed.
- ✅ **Hard-coded `sender_user_id = 1`** in `AbsenceWarningService` (assumes user 1 is the admin).

---

## 4. Logic errors and potential defects

| # | Item | Status |
|---|---|---|
| B-01 | The admin `create_parent` and HOD `accounts` pages call `/api/student/info/{id}` with a web session, while the route requires a Sanctum token and a student/parent role, so the call most likely fails (401) | ✅ |
| B-02 | Duplication and dead code: `Api\MessageController` is not attached to any route (chat works through `ChatController`); `/system/settings` and `/broadcasting/auth` were defined twice; Affairs routes sit inside the `auth:sanctum` group and repeat the same middleware | ✅ |
| B-03 | The `users.role` (enum) column, `role_id` and `roles` coexist. Values may not match (the original `enum` does not include `affairs`) | ✅ |
| B-04 | Link keys are inconsistent: `parent_students.parent_id` and `student_id` may point to `user_id` or to `parent_id/student_id` (some queries tolerate both with `whereIn`, others do not) | ✅ |
| B-05 | `Attendance::updateOrCreate` is keyed by (student, lesson, date). Scanning the QR twice on two days for the same lesson creates two rows | ⚠️ |
| B-06 | `$attendance->created_at = $scannedAt` overwrites the creation time with a client value, which pollutes the audit trail | ✅ |
| B-07 | `AbsenceWarningService`: counts "absence days" across all courses and semesters together, never resetting between semesters; each warning is guarded by `exists()` and never repeats for the same student for life | ✅ |
| B-08 | `endSession` records absence even for someone with an approved excuse (`excuse_status`). Needs verification | ⚠️ |
| B-09 | Disappearing chat messages are deleted by a scheduled task (`everyFiveMinutes`), which requires `schedule:run` to actually run on the server (cron) | ✅ |
| B-10 | `SendFcmNotificationJob` exists, but `FcmService::sendToUser` sends **synchronously** inside the request, so an FCM delay slows the API | ✅ |
| B-11 | `FcmService::getAccessToken` builds a JWT and requests an access token **on every send** without caching | ✅ |
| B-12 | `TeacherController.php` and some files have **Arabic comments with corrupted encoding (mojibake)**. The code works, but the comments are unreadable | ✅ |
| B-13 | Raw queries (`DB::raw/whereRaw/selectRaw`) in 21 files (136 places). No confirmed injection was found in the sampled code, but every place that includes user input must be reviewed | ⚠️ |
| B-14 | `{!! !!}` in Blade: only 6 places, and the two examined pass through `e()` (safe). The remaining search hits are legitimate uses | ✅ |
| B-15 | The absence of N+1 queries is unverified; the large controllers (`AffairsWebController` ~189KB) have loops and nested queries that may be problematic | ⚠️ |
| B-16 | `face_embedding` is stored as JSON with 192 numbers per student and updated with a moving average (0.85/0.15) on each success, so the reference may gradually drift | ⚠️ |
| B-17 | Flutter app: local server addresses are hard-coded (`127.0.0.1` and 3 LAN addresses) with handling for an old IP (`82.137.250.43`). There is no environment configuration (dev/staging/prod) | ✅ |
| B-18 | Flutter app: 2-second chat polling (in parallel with Pusher) and 30-second notification polling consume battery, data and server capacity, and are why `throttle:api` is set at 240 requests/minute | ✅ |

---

## 5. Code quality and maintenance (technical debt)

| Item | Detail |
|---|---|
| Very large controllers | `AffairsWebController` 189KB, `TeacherController` 144KB, `AffairsController` 130KB, `StudentController` 126KB, `TeacherWebController` 104KB. Business logic, view logic and queries in a single file |
| Web/API duplication | The same function is written twice (e.g. `scanAttendanceQr` in API, Telegram and Web) and the face code (`extractImageVector`) is duplicated between `StudentController` and a trait |
| Tests | `tests/` contains only `ExampleTest`. Flutter has the default `widget_test.dart`. **Effectively zero coverage** |
| Code documentation | Many comments, some documenting important decisions (good), but some mixed with a development tone |
| Migrations | About 120, some duplicated or conflicting in name (`.php.php`). They run from scratch, but squashing later is advised |
| Misplaced files | `render_*.cjs`, `start_bot.*` and `run_migrations.bat` at the root; the `_deprecated/` and `سيرفر/` folders |
| Dependencies | `barryvdh/laravel-dompdf` and `carlos-meneses/laravel-mpdf` together (two PDF libraries) plus `maatwebsite/excel`. Check whether both are needed |
| Naming | `app/Http/Middleware/Edu_Pridge_flutter.code-workspace`: a VSCode workspace file inside the Middleware folder (wrong place) |
| README | Default in both projects |

---

## 6. Production environment checklist

The current `.env` settings are for local development and must be changed:

- [ ] `APP_ENV=production` and `APP_DEBUG=false` (currently `local` and `true`, which exposes error details)
- [ ] `MAIL_MAILER` is currently `log`, so OTP emails are **not actually sent** (they are only written to the log). Configure real SMTP
- [ ] `SESSION_SECURE_COOKIE=true` and `SESSION_ENCRYPT=true`
- [ ] `SANCTUM_EXPIRATION` set, and `CORS` restricted to specific domains
- [ ] Run `php artisan schedule:run` through cron (for disappearing messages, the attendance summary and log cleanup)
- [ ] Run a queue worker `queue:work` (currently `QUEUE_CONNECTION=database`)
- [ ] Configure the Telegram webhook with a `secret_token`, or run `TelegramPoll` as a service
- [ ] File `storage/app/firebase-service-account.json` for FCM sending (not in the repository, which is correct)
- [ ] Keys: `GEMINI_API_KEY` (added to `.env.example`), `PUSHER_*` and `TELEGRAM_BOT_TOKEN`
- [ ] Block PHP execution inside `storage/` and `public/uploads/`, and move `uploads/faces` out of the public root
- [ ] Mandatory HTTPS (the code forces it via `URL::forceScheme` when `production`)
- [ ] Regular database backups (the `ExportDatabase` command exists)
- [ ] In the Flutter app: build flavors for the server address, and remove the hard-coded LAN addresses

---

## 7. What was not reviewed (limits of this study)

- Not every controller was read line by line. The following need an authorization review: `AdminController`, `AffairsController`, `DepartmentHeadController` (head scope: does a head see only their department?), `HODController`, `ParentMeetingController`, `Api\MessageController` and `NotificationController`.
- The grade and weight calculation logic (`StudentAcademicService`) was not reviewed for the correctness of the academic calculations.
- No performance test or dynamic penetration test was done.
- The Flutter code was not reviewed in depth (~73K lines of screens); the focus was on services and structure.
- The license of the `mobile_face_net.tflite` model must be confirmed (`assets/models/NOTICE.md`) before any commercial use.

---

## 8. Recommended fix order

1. ~~Week 1 (S-01 to S-07)~~ **Done**.
2. **Next:** S-08, S-09, S-13, S-10 to S-12 (tokens, CORS, face images, attendance hardening), and the section 6 items.
3. **Weeks 3 to 4:** write Feature tests for the authorization routes (for each role: can it reach another's data?) so these errors do not return.
4. **Later:** split controllers into Services, unify web/API logic, and squash the migrations.
