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
