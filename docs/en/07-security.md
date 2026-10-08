# Security Design

> This file describes the **existing controls**. For open and fixed vulnerabilities see [`08-project-status.md`](08-project-status.md).

## 1) Authentication

| Channel | Mechanism | Details |
|---|---|---|
| App (API) | Laravel Sanctum, Bearer token | One current token per user (`users.current_token_id`); issuing a new token deletes the previous one. Tokens expire after **30 days** (`SANCTUM_EXPIRATION`), and the app returns to login on 401 |
| Web | Laravel session + CSRF | One active session (`current_session_id`) with a **20-minute** idle window; afterwards a new login is allowed |
| Login alternative | Email OTP (`login-otp`) | 6-digit code via `random_int`. A fixed dev-only code applies only when `OTP_FIXED_CODE` is set explicitly in a local/testing environment |
| Student on multi-device web | Face verification | `/student/face-verification` |

Passwords are hashed with bcrypt (`Hash`). Minimum length is 8 characters when set by the user (6 for staff-created accounts for now).

## 2) Authorization

- **API:** `RoleMiddleware` with the roles allowed per route group (`role:student`, `role:teacher`, `role:head`, `role:admin`, `role:parent`, `role:affairs,admin`).
- **Web:** one middleware per role (`CheckStudentRole` ...).
- **Ownership (record level):** every controller must verify the resource belongs to the user. Applied explicitly in: child details for parents (`ParentController`), chat attachment download, teacher attendance sessions, and student/parent data (after the fixes). **This is the largest source of vulnerabilities in the project; every new endpoint that takes an id must check it.**
- **Chat:** the `canChat` matrix defines who can message whom, and the private channels `chat.{id}` are authorized by matching `user_id`.

## 2.1) Self-registration
Self-registration is limited to the `student` and `parent` roles. No other role can be chosen. Every new account needs **Affairs approval** and a pre-registered university ID.

## 3) Brute-force and abuse protection

| Control | Value | Where |
|---|---|---|
| Login limit | 5 / minute / IP (`throttle:login`) | `AppServiceProvider` |
| Account lockout | After 5 consecutive failures, 15 minutes, with a notice to the owner | `LoginThrottleGuard` |
| OTP sending | 3 / minute (login); 5 / minute + 10 / hour per email (register / forgot password) | `login-otp`, `otp-send` |
| OTP verification | 10 / minute / IP + 10 / hour per email; web: 5 attempts then the code is invalidated | `otp-verify` |
| General API | 240 requests / minute per user | `api` |
| Single session | Refuse login from a second device + notice to the owner | `SingleSessionGuard` |

## 4) Attendance integrity

The six layers (rotating QR, bound device, location, face, eligibility, no duplicates) are described in [`05-key-flows.md`](05-key-flows.md). **Their limits:** device, location, time and face embedding are all values sent by the client (a mobile app that can be modified), and there is no server-side liveness check. They are therefore **deterrent controls**, not an absolute guarantee. Details in items S-10 to S-12.

## 5) Sensitive data

| Data | Where stored | Note |
|---|---|---|
| Passwords | `users.password` (bcrypt) | |
| Reference face embedding | `students.face_embedding` (JSON, 192 numbers) + `students.reference_photo` | Biometric data; requires consent and a retention policy |
| Attendance photos | `storage/app/private/faces/` (private) | Served through `GET /api/attendance/{id}/face` to authorized users only; `faces:secure` moves old ones |
| Service keys | `.env` and `storage/app/firebase-service-account.json` | Outside git |
| Activity log | `user_activities` | Entries older than 90 days are purged |

## 6) Transport

- HTTPS is forced in `production` (`URL::forceScheme`).
- CORS is restricted: `CORS_ALLOWED_ORIGINS` + localhost for development (it was open `*`).
- CSRF protection is enabled for web **with no exceptions**. Sessions are encrypted by default.

## 7) Audit and monitoring

- `user_activities`: logins, failures, rejections, lockouts and admin operations, via `UserActivity::log`.
- `attendance`: rejected attendance is recorded with `reject_reason`.
- Laravel logs in `storage/logs`. There is no external monitoring or alerting.

## 8) Guidance for the next developer

1. Any new endpoint that takes `{id}` must verify resource ownership before any read or write.
2. Do not use `rand()` for codes; use `random_int()`.
3. Any file upload: a `mimes:` allow-list, and the extension taken from the content, not the file name.
4. Never put keys in code or the repository.
5. Add a Feature test for every new route verifying that another role gets `403`.

## 9) Telegram bot and password reset hardening (2026-10-07)

The bot and the password reset were reviewed after the AR/EN and bot suite merge. Fixed, each with feature tests that fail on the old code:

| Area | Problem | Now |
|---|---|---|
| Password reset (`sendResetOtp`) | For an account with no linked Telegram, the id typed in the request was saved on the account and the OTP was sent to it (account takeover). The OTP was also returned in the JSON when delivery failed. | The OTP goes only to the chat already linked to the account (linked from the bot with the password) and is never returned. Unknown / inactive / not-linked / failed delivery return the same 422 (no account enumeration). |
| Bot sign-up | Any university id not already used was accepted; a parent only needed a student's university id. | The id must exist in `university_ids` and be unused; the parent's family name must match the student's (same rule as the app). Passwords: 8 characters. |
| Bot sign-in | No brute-force protection. | `LoginThrottleGuard` (5 failures = 15 min lock, shared with the web login). |
| Bot buttons | Role-gated menus only; the action handlers never checked the caller. | Central role guard by button prefix (`admin_`, `affairs_`, `hod_`, `teacher_`, `parent_`) **and** ownership checks (`CallbackAuthorization`): parent to own children, student to own absences and enrolled courses, teacher to own courses/sessions/submissions, head to his department's requests. |
| Admin lectures | `content_url` (free text written by the teacher) could point to any server file, and a hard-coded CV was served for missing files. | Files must resolve (realpath) under `storage/app/public`, `public/storage` or `public/uploads`; no fallback file. |
| Semesters / promotion | `semester_name` column did not exist (SQL error); the two promotion routes behaved differently. | One shared promotion routine (level, academic year, auto-enrolment, one transaction per student, activity log). |
| Head of department (bot and web) | Lists, details and decisions fell back to "show everything" or never checked the department; leaves were approved without Student Affairs; notifications and announcements reached the whole university; LIKE matching. | Everything goes through `Access` (exact department, workflow stage `pending_hod`, fail closed without a department). Leave approval moves to `pending_affairs`. Audience = the head's department (`Access::headAudienceUserIds`). |
| Affairs / Admin (bot) | Decisions had no stage check; admin "reject account" deleted any user (even an active one). Affairs leave buttons never worked (id parsed as 0). | Affairs decides in `pending_affairs`, admin in `pending_admin`, account reject/approve only on pending accounts; button parsing fixed. |
| Bot routing and crashes | Two head buttons (courses, announcement) went to the dashboard; appointments, affairs student search and lecture-file details crashed on wrong columns / tables / model key. | Fixed; a test presses every button of the six keyboards and a crawler presses every inline button on realistic data. |
| Student leave scenario (all channels) | The web stored requests in the legacy `absence_requests` table, the Flutter app and the bot in different ways; the web parent form inserted every request twice; parent/affairs steps had no stage check; next-stage notices went to every head. | One service, `LeaveWorkflow`, for the web, the Flutter API and the bot, on `leave_requests`: student, parent (`pending_parent`), head of the student's department (`pending_hod`), student affairs (`pending_affairs`), approved. Each step only in its stage; notices to the student's department head only; final decision to student, parents, head and advisor. The legacy `absence_requests` rows are copied to `leave_requests` by the migration `2026_10_08_100000`, and the table is then dropped by `2026_10_08_110000` (it refuses to drop while a row is uncopied); the unused legacy endpoints `parent/permissions` and `teacher/absence-requests` were removed. Run `php artisan migrate` on the server. The bot gained "my leaves" and the parent's "children's leaves" approval. |
| Notifications | Every app notification is copied to Telegram. | Kept, but `TELEGRAM_FORWARD_NOTIFICATIONS=false` turns it off. |

**Still open (not fixable by code):**

1. A Telegram bot token committed in June 2026 and removed in September is still in the Git history of a **public** repository. Revoke it with BotFather (`/revoke`) and keep the new token only in `.env`.
2. The institute server is served over plain HTTP.
3. `TelegramBotHandler` was split into role traits under `app/Services/TelegramBot/`; the bot still has little test coverage outside sign-up, sign-in, role and ownership checks.
