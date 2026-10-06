# Remediation Plan

> **Status:** ✅ **Executed** on branch `fix/security-hardening-phase2` (see section 6)
> **Prepared:** 2026-10-06
> **Reference:** the vulnerabilities and bugs in [`08-project-status.md`](08-project-status.md) + the extended inventory (section 1)

## 1) Extended inventory result

After phase one (S-01 to S-07) I inventoried every function that takes an `{id}` from the request:

| Metric | Value |
|---|---|
| Functions taking an id (API + Web) | 222 |
| No ownership marker (automatic scan) | 37 |
| Only one marker | 40 |
| Flagged and reviewed by hand | the most dangerous, and most teacher/student/head functions |

> The automatic scan is a **detector, not a proof**: everything flagged is reviewed by hand, and what was not flagged may still hide a defect. That is why the plan includes **automated authorization tests** that lock the result in.

**Defect patterns found (new):**

| Code | Defect | Impact |
|---|---|---|
| ~~N-01~~ | ~~`gradeAssignment` grades any submission~~ **Withdrawn:** my false alarm (the check exists); its test is kept as a regression guard | - |
| N-02 | `respondAbsenceRequest` answers any absence request (reading was limited, answering was not) | Tampering with excuses |
| N-03 | `getStudentAcademicStats` and `submitEvaluation` do not check the report request belongs to this teacher | Reading academic data and writing someone else's evaluation |
| N-04 | `markAttendance` checks the course but not that `lesson_id` and `student_id` belong to it | Attendance for a lesson or student outside the course |
| N-05 | `sendParentSummon` lets any teacher summon any student's parent | False summons |
| N-06 | `StudentController::getLeaveDetails` does not check the request is the student's own | Reading others' leave reasons |
| N-07 | `submitAssignment` does not check enrollment in the assignment's course | Submitting to assignments outside the student's courses |
| N-08 | Head of department (API): leave, notes, report deletion/sending, student services and course grades not limited to the department | A head controls another department's requests |
| N-09 | `HODController::updateLeaveStatus` set `approved` directly, skipping the parent's approval and the Affairs sequence | Leave without parental approval |
| N-10 | `HODWebController::deleteAccount` deletes **any** user (even Admin) and `updateAccount` edits any account | **Critical:** a head deletes the Admin or sets their password |
| N-11 | `HODWebController`: `deleteSchedule`, `deleteExam`, `deleteReport`, `updateHodNotes`, `updateCourseWeight`, `updateLeaveStatus` unscoped | Cross-department tampering |
| N-12 | `TeacherWebController`: `endSession`, `refreshSessionQr`, `getAbsentees`, `exportAttendance` without ownership (the web twin of S-05) | Same impact as S-05 |
| N-13 | `StudentWebController::downloadLesson` and `courseMaterials` with no enrollment check (and the latter auto-enrolls) | Downloading and self-enrolling in any course |
| N-14 | `ScheduleController` and `ExamScheduleController` (`update`/`destroy`) unscoped | Editing any department's timetable |
| N-15 | No role hierarchy: Affairs/Head can edit accounts of higher roles | Privilege escalation |
| N-16 | `ParentProfileController` not attached to any route (dead code) | Removed |

## 2) Principles

1. **Test before fix:** every defect gets a Feature test that fails before the fix and passes after.
2. **One place for authorization:** a central `App\Support\Access` helper instead of repeated checks.
3. **Deny by default:** nobody reaches a resource without proving ownership or role.
4. **One commit per group:** clear message, tests run before each commit.
5. **Do not break the app:** any change affecting Flutter behavior is made in the app in the same batch.
6. **Honest documentation:** `08-project-status.md` is updated with what was actually fixed and what remains.

## 3) Workstreams

### WS-A: Foundation
| # | Task | Acceptance |
|---|---|---|
| A1 | Test infrastructure: a dedicated MySQL test database, a **guard that refuses to run on a database not ending in `_test`**, data helpers | `php artisan test` runs and never touches the real database |
| A2 | `App\Support\Access`: parent/teacher/head ownership, course enrollment, role hierarchy | Used by all following fixes |
| A3 | CI on GitHub Actions (MySQL + `php artisan test`) | Passes on every push |

### WS-B: API authorization (N-01 to N-09, N-14 to N-16)
Limit teachers to their courses and students, students to themselves and their courses, heads to their department, and enforce a **state machine** for leave requests (no jumping stages).

### WS-C: Web authorization (N-10 to N-13)
The same rules through the same central helper. **N-10** (account delete/edit) first.

### WS-D: Authentication, sessions and settings
| # | Task |
|---|---|
| D1 | Sanctum token expiry (default 30 days, configurable) + automatic logout in Flutter on `401` |
| D2 | Restrict CORS to domains configured in `.env` |
| D3 | **Remove the fixed OTP `123456`** (now an explicit dev-only `OTP_FIXED_CODE` option) |
| D4 | Review and remove the CSRF exception for `affairs/accounts*` |
| D5 | `presence-online` payload |
| D6 | Password policy (8 characters minimum when set) |
| D7 | `SESSION_ENCRYPT` and safe defaults |

### WS-E: Attendance and biometric integrity (S-10 to S-14, B-05, B-06, B-08)
| # | Task |
|---|---|
| E1 | **No implicit face acceptance:** a missing reference/embedding is recorded `suspicious` and flagged to the teacher (instead of `verified` with 90/96) |
| E2 | Bound `scanned_at`: never in the future, within the session window |
| E3 | Move face images to private storage, served through a permission-checked route |
| E4 | **Telegram:** attendance from the scanner requires a **signed, expiring link** issued by the bot, instead of trusting `chat_id` alone |
| E5 | `endSession` respects approved excuses |
| E6 | **S-11:** no complete server-side fix (an architectural decision). Documented as an accepted risk with mitigations: rate limiting, device binding, reject log. A liveness check stays on the roadmap |

### WS-F: Remaining items
| # | Task |
|---|---|
| F1 | S-19: allow-list for the `server_url` domain in the AI assistant |
| F2 | B-01: fix the `/api/student/info` call from web pages (a proper web route with session authorization) |
| F3 | B-10/B-11: send FCM through the queue + cache the access token |
| F4 | The hard-coded `sender_user_id = 1` in warnings becomes the first real admin |
| F5 | B-13: review raw queries that mix user input, and convert them to bindings |

### WS-G: Verification and handover
Run all tests, regenerate the documentation (`docs/tools`), update `08-project-status.md`, the PDF and the site, review the final diff, and prepare a change summary.

## 4) Decisions I made (adjustable assumptions)

| Decision | Choice | Reason |
|---|---|---|
| Token lifetime | 30 days | Balance between security and not bothering students |
| Face when no reference exists | Accept with a `suspicious` flag (not reject) | We do not break new students before their first enrollment |
| Minimum password | 8 characters | Standard |
| Absence counting per semester (B-07) | **Deferred** | An academic decision (do warnings reset each semester?) for the institute |
| S-15 (QR sharing) and S-11 | Documented accepted risk | No complete fix without an architectural change |

## 5) Risks and rollback

- Every group is in its own commit, so rollback is `git revert <commit>`.
- Changes are tried on a separate test database, and no destructive migration is run.
- Fixes that may change user behavior (token expiry, the signed Telegram link) are flagged in the status file and should be tried on a device before deployment.

## 6) Execution log

| Workstream | Status | Notes |
|---|---|---|
| WS-A Foundation | ✅ | Separate test database (`edu_bridge_test`) with a **guard** against running on a database not ending in `_test`; `App\Support\Access`; test data helper. (Not done yet: CI on GitHub Actions) |
| WS-B API authorization | ✅ | N-02 to N-09, N-14 to N-16. Head/teacher/Affairs tests were proven to **fail on the old code** |
| WS-C Web authorization | ✅ | N-10 to N-13 + role hierarchy + chat groups |
| WS-D Authentication and settings | ✅ | D1 (30-day token + Flutter), D2 (CORS), D3 (fixed OTP removed), D4 (CSRF), D5, D6 (8 characters on self-service), D7 |
| WS-E Attendance | ✅ | E1, E2, E3, E4, E5; **E6 is a documented accepted risk** |
| WS-F Remaining items | ✅ | F1 to F5 (F3: cache only; queue sending deferred) |
| WS-G Verification and handover | ✅ | **105+ tests pass**; documentation regenerated; status files, PDF and site updated |

### Not done, and why
- **CI (A3):** needs GitHub repository settings, which belong to you or the repository owner.
- **B-07** (per-semester absence counting): an academic decision.
- **S-11** (liveness): an architectural decision, outside what a server fix can do.
- **8-character limit for staff-created accounts:** needs a review of the creation screens in the app.
- **Splitting the controllers:** a separate project (phase 3 of the roadmap).

### Latent functional bugs found by the tests
See the L-01 to L-07 table in [`08-project-status.md`](08-project-status.md): whole features were crashing with a 500 (teacher parent-summons, the Telegram scanner, the head's lists...).
