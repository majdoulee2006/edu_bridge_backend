# Key Flows

Diagrams use Mermaid (rendered automatically on GitHub and the documentation site). Every flow is based on the real code; the source file is named.

## 1) Account creation and activation

Self-registered student and parent accounts go through Affairs review. (Source: `Api\AuthController::register`, `AffairsController::approveAccount`)

```mermaid
sequenceDiagram
    autonumber
    actor Aff as Affairs officer
    actor U as Student / Parent
    participant App as Flutter app
    participant API as Laravel API
    participant DB as Database
    participant TG as Telegram

    Aff->>API: Add a pre-registered university ID (university_ids) with name and photo
    U->>App: Fill the registration form (role + university ID)
    App->>API: POST /api/register (throttle: otp-send)
    API->>DB: Check: ID exists and is unused
    Note over API,DB: Parent: child ID + last name match
    API->>DB: Create user with status = inactive
    API-->>Aff: "Account awaiting approval" notice (in-app + FCM)
    API-->>App: pending_approval = true
    Aff->>API: POST /api/affairs/accounts/{id}/approve
    API->>DB: Activate the account
    API->>TG: Notify the user of activation
```

## 2) Login (API)

(Source: `Api\AuthController::login`, `SingleSessionGuard`, `LoginThrottleGuard`)

```mermaid
flowchart TD
    A["POST /api/login<br/>username + password (+ device_id)"] --> T{"throttle:login<br/>5 / minute / IP"}
    T -- "exceeded" --> X429["429"]
    T --> F["Find user by: username, email, phone,<br/>university_id or student_code"]
    F -- "not found" --> X404["404"]
    F --> L{"Account locked?"}
    L -- "yes" --> X423a["423 + remaining time"]
    L -- "no" --> P{"Password correct?"}
    P -- "no" --> R["Record failure<br/>(5 failures = 15-minute lock + notice)"] --> X401["401"]
    P -- "yes" --> S{"status"}
    S -- "pending / inactive" --> X403a["403"]
    S -- "active" --> O{"Another mobile session active?"}
    O -- "yes" --> X423b["423 + intrusion notice to the owner"]
    O -- "no" --> ST{"Role = student?"}
    ST -- "yes" --> PL{"Linked to a parent?"}
    PL -- "no" --> X403b["403: parent_link_needed"]
    PL -- "yes" --> DV{"Account locked to another device?"}
    DV -- "yes" --> X403c["403: device_locked"]
    DV -- "no" --> BIND["Bind device on first login<br/>+ auto-enroll courses"]
    ST -- "no" --> TOK
    BIND --> TOK["Issue a new Sanctum token<br/>and delete all previous tokens"]
    TOK --> OK["200: token + user(role, role_id, parent_id, is_advisor)"]
```

Alternatives: **email OTP login** (`/login-otp/send` then `/login-otp/verify`); the web has one login page per role, plus **face verification** for students signing in from multiple devices.

## 3) Attendance (QR + device + location + face)

(Source: `Api\TeacherController::generateQrSession` and `Api\StudentController::scanAttendanceQr`)

```mermaid
sequenceDiagram
    autonumber
    actor Tch as Teacher
    actor Stu as Student
    participant TA as Teacher app
    participant SA as Student app
    participant API as Laravel API
    participant DB as Database

    Tch->>TA: Open an attendance session for a course
    TA->>API: POST /api/teacher/attendance/generate-qr
    API->>DB: Create lesson + attendance_session (QR valid 30s, session 10m)
    API-->>TA: qr_token
    loop every ~30 seconds
        TA->>API: POST .../session/{id}/refresh-qr
        API-->>TA: new qr_token
    end
    Stu->>SA: Scan the QR + capture a face
    SA->>SA: Detect face + extract 192-dim embedding (MobileFaceNet)
    SA->>API: POST /api/student/attendance/scan<br/>(qr_token, device_id, lat/lng, face_embedding, scanned_at)
    API->>DB: 1) Session exists and within its time
    API->>DB: 2) Sync policy (same_day / anytime)
    API->>DB: 3) Student eligible for the course
    API->>DB: 4) Not already marked
    API->>DB: 5) Device matches the bound device
    API->>DB: 6) Distance (Haversine) <= radius
    API->>DB: 7) Face similarity >= 70%
    alt any check fails
        API->>DB: Log rejected attempt + reason (reject_reason)
        API-->>SA: 4xx + reject_reason
    else all checks pass
        API->>DB: Attendance = present
        API-->>SA: 200 attendance recorded
    end
    Tch->>TA: End the session
    TA->>API: POST .../session/{id}/end
    API->>DB: Mark absent everyone who did not attend
```

Reject reasons (`reject_reason`): `expired_qr`, `sync_timeout`, `lesson_not_found`, `device_mismatch`, `location_too_far`, `face_mismatch`, plus eligibility and duplicate reasons.

## 4) Automatic absence warnings

(Source: `AttendanceObserver`, `Services\AbsenceWarningService`)

```mermaid
flowchart LR
    A["Attendance row with status absent<br/>(created or updated)"] --> O["AttendanceObserver"]
    O --> TG["Telegram notice to the student"]
    O --> W["AbsenceWarningService::checkAndWarn"]
    W --> C["Count unique absence days<br/>across all courses"]
    C -->|">= 7"| W1["First warning<br/>(notice to the student)"]
    C -->|">= 10"| W2["Second warning + automatic parent summons<br/>(parent_summons)"]
    C -->|">= 15"| W3["Final warning + referral to administration and head"]
```

Each level is issued **once** per student (`student_warnings`). See note B-07 in the project status.

## 5) Leave request

(Source: `StudentController::requestAbsence` -> `StudentParentController::respondLeaveRequest` -> `Api\DepartmentHeadController::respondLeaveRequest` -> `AffairsController::updateLeaveStatus`)

```mermaid
stateDiagram-v2
    [*] --> pending_parent: Student submits the request
    pending_parent --> pending_hod: Parent approves
    pending_parent --> rejected: Parent rejects
    pending_hod --> pending_affairs: Head approves
    pending_hod --> rejected: Head rejects
    pending_affairs --> approved: Affairs approves
    pending_affairs --> rejected: Affairs rejects
    approved --> [*]
    rejected --> [*]
```

> All leave requests live in `leave_requests` and run through one workflow (`LeaveWorkflow`) for the web, the Flutter app and the bot. The old `absence_requests` table was copied into it and dropped.

## 6) Chat

```mermaid
sequenceDiagram
    autonumber
    participant A as Sender
    participant API as ChatController
    participant DB as messages
    participant PU as Pusher
    participant B as Receiver

    A->>API: POST /api/send-message (receiver_id, message/attachment)
    API->>API: canChat(sender role, receiver role)
    alt not allowed
        API-->>A: 403
    else allowed
        API->>DB: Save the message (+ attachment, + expiry if disappearing)
        API->>PU: Broadcast MessageSent on chat.{receiver_id} and chat.{sender_id}
        API->>B: FCM (unless notifications are muted)
        PU-->>B: Real-time delivery
    end
    Note over B: A 2-second polling fallback runs in parallel
```

Who can message whom:

| Sender | Can message |
|---|---|
| Student | Head of department, teacher, admin |
| Teacher | Teachers, students, head of department |
| Parent | Admin, head of department |
| Head of department | Parent, teacher, student, admin |
| Admin | Head of department, Affairs, teacher, student |
| Affairs | Admin |

## 7) Password recovery

- **API:** `forgot-password` sends an OTP, then `reset-password` (email + code + new password).
- **Web:** through Telegram: `send-otp` -> `verify-otp` (5 attempts maximum) -> `reset`; the code is kept in the browser session and is valid for 15 minutes.

## 8) Telegram scanner

(Source: `TelegramBotHandler::handleQrAttendanceMenu`, `TelegramWebhookController`)

```mermaid
sequenceDiagram
    autonumber
    actor Stu as Student
    participant Bot as Telegram bot
    participant Web as Scanner page
    participant API as Laravel

    Stu->>Bot: Request QR attendance
    Bot->>Bot: Signed expiring link (15 min) bound to the chat_id
    Bot-->>Stu: Open-scanner button
    Stu->>Web: Open the signed link
    Web->>API: GET /telegram/scanner (signed:relative)
    API-->>Web: Page + encrypted scanner_token
    Stu->>Web: Scan QR + capture a face
    Web->>API: POST /telegram/record-attendance (scanner_token, qr_token, face)
    API->>API: Decrypt: identity comes from the token, not the client
    API-->>Web: Attendance recorded or rejected
```

Statuses: `verified` for a face match of 70% or more, `first_time` for the first embedding, `suspicious` when face data is missing or the match is weak (the teacher is alerted). Face can be made mandatory with `ATTENDANCE_REQUIRE_FACE=true`.
