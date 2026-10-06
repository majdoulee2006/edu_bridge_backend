# Proposed Roadmap

In priority order. The numbers (S-xx / B-xx) refer to items in [`08-project-status.md`](08-project-status.md).

## Phase 1: Stabilize before any launch (1 to 2 weeks)

| Item | Description | Effort |
|---|---|---|
| Production setup | `APP_ENV=production`, real SMTP, cron, Supervisor for the queue, HTTPS, block PHP execution in storage | 1 day |
| S-08 | Expire Sanctum tokens (`SANCTUM_EXPIRATION`) | hours |
| S-09 | Restrict CORS to the project domains | hours |
| S-13 | Move face images out of the public root and serve them through a protected route | 1 day |
| S-14 | Authenticate `/telegram/record-attendance` and bind `chat_id` to a verified session | 1 day |
| S-19 | Domain allow-list for `server_url` in the AI assistant | hours |
| Remove the `123456` OTP exception from the code | Remove the `environment('local')` condition | 1 hour |
| Review the remaining controllers | Admin, Affairs and head of department (see section 7 of project status) | 2 to 3 days |

## Phase 2: Tests and quality (2 to 4 weeks)

1. **Authorization Feature tests:** for each role, make sure it cannot reach another's data (prevents S-01/S-02/S-05 from returning). This has the highest return.
2. Tests for the attendance logic (`scanAttendanceQr`), warnings (`AbsenceWarningService`) and grade calculation (`StudentAcademicService`).
3. Simple CI (GitHub Actions): `composer install`, `php artisan test`, `pint`, and `flutter analyze`.
4. Set up Flutter environments (`--dart-define`) instead of hard-coded server addresses (B-17).

## Phase 3: Reduce technical debt (1 to 3 months)

| Item | Description |
|---|---|
| Split controllers | Move logic into Services/Actions, starting with the largest: `AffairsWebController`, `TeacherController`, `AffairsController`, `StudentController` |
| Unify duplicated logic | Attendance recording (API/Web/Telegram), face vector extraction, absence-day counting |
| Unify link identifiers | Settle `parent_students` and `leave_requests.student_id` on `user_id` or the internal id (B-04) |
| Unify `role` and `role_id` | Rely on `role_id` only (B-03) |
| Squash migrations | Squash into one baseline schema (it already runs from scratch) |
| FormRequests | Move validation out of controllers (some exist in `Requests/Admin`) |
| Remove dead code | `Api\MessageController`, the `chats`, `user_activity`, `session` and `otps` tables, and the `_deprecated` folder |
| FCM | Send through the queue (`SendFcmNotificationJob`) and cache the access token (B-10/B-11) |
| Warnings | Count absences per semester (B-07) |

## Phase 4: Features and improvements (open-ended)

- **Stronger attendance:** on-device liveness detection with request signing, or detection of tampered devices (Play Integrity).
- **Replace polling with WebSocket only** after Pusher is stable (saves battery and server load).
- **Monitoring:** Sentry/central logging, and alerts for queue failures.
- **Interactive API documentation:** OpenAPI/Swagger (currently a table generated from the routes).
- **Multi-institution:** if the goal is to sell the product to more than one institute, data separation (multi-tenancy) and per-institution settings are required.
- **Performance:** review N+1 queries in the large controllers (B-15), and add indexes as measured.
- **Privacy:** a retention policy for biometric data (faces), explicit consent, and deletion at graduation.

## Readiness criteria for sale/handover

- [ ] Phase 1 complete
- [ ] Authorization tests (phase 2.1) pass
- [ ] Running `06-setup-and-deploy.md` on a clean machine ends with a working system
- [ ] The face-model license is settled
- [ ] The handover checklist [`10-handover-checklist.md`](10-handover-checklist.md) is satisfied
