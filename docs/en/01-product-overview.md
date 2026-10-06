# Product Overview

## What is Edu-Bridge?

**Edu-Bridge** is an integrated academic management system for an educational institute. It connects **six roles** across several channels:

- **Mobile/web app (Flutter)** for all roles.
- **Web dashboards (Laravel Blade)**, one per role, each with its own login page.
- **Telegram bot** for students.

Goal: automate the daily academic cycle (timetables, attendance, assignments, grades, administrative requests) and connect the student with their parent, teachers and administration in one place.

## Roles

| Role | `role_id` | Main capabilities |
|---|---|---|
| Admin | 1 | Manage accounts, departments, programs and courses; assign heads of department; reports; announcements; bulk notifications; activity log; theme customization |
| Teacher | 2 | Lectures and materials, QR attendance sessions, assignments and grading, grades, quizzes, performance reports, parent summons. **Advisors** have extra tools (cohort attendance export, daily summary) |
| Student | 3 | Timetable and exams, attendance (QR + face + location), assignments and lectures, grades and academic card, leave and service requests, chat, AI assistant |
| Parent | 4 | Follow children (attendance, grades, assignments), approve leave permissions, request reports and meetings, answer summons |
| Head of department | 5 | Timetables and exams, approve leave and service requests, request behavioral/grade reports from teachers, create accounts, announcements, summons |
| Affairs officer | 6 | Pre-registered university IDs, account approval, semesters and promotion, academic card and transcripts, course weights and cohort reports, device unlock, student-parent linking, activities and calendar |

## Main features

### Smart attendance
The teacher opens a session and a **QR code that rotates every 30 seconds** is generated (the session lasts 10 minutes). The student scans it in the app and the server checks:
1. QR validity and scan time
2. Student eligibility for the course (year / specialization / enrollment)
3. No duplicate record
4. **Device binding** (one device per student)
5. **Geolocation** within the classroom radius
6. **Face match** (MobileFaceNet model on-device, 70% threshold)

There is also **offline attendance**: the app stores the scan time and syncs later according to the department policy (`anytime` or `same_day`).

### Automatic absence warnings
| Absence days | Action |
|---|---|
| 7 | First warning to the student |
| 10 | Second warning + **automatic parent summons** |
| 15 | Final warning and referral to administration and the head of department |

### Communication
- **Real-time chat** (Pusher) with attachments, voice notes, groups, edit, delete, forward and disappearing messages, governed by a **permission matrix** that defines who can message whom.
- **Notifications** in-app + push (FCM) + Telegram.
- **Announcements and activities** with a target audience and images.

### Administrative request cycle
Leave: `student -> parent -> head of department -> Affairs`. Student services (appeal, documents, make-up exam, device unlock): `student -> head of department -> Affairs/Admin`.

### Reports and export
Academic card (PDF/Excel), study and exam timetables (PDF/Excel/image), attendance reports (PDF/Excel), cohort reports, performance reports and advisor reports.

### AI assistant
In-app chat powered by **Google Gemini** with live context from the user's data, and a local fallback engine if no key is configured or the call fails.

### Telegram bot for students
Timetable, grades, attendance, lectures, submit excuses and leave, QR scanner, and instant notifications for grades and absences. Also used to send OTP codes and recover passwords.

## Security and sessions (summary)
- One active session per channel (web / mobile).
- Account lockout for 15 minutes after 5 failed logins, and per-IP rate limiting.
- A student is bound to one device; unlocking is requested through Affairs.
- An activity (audit) log for important actions.
- Details in [`07-security.md`](07-security.md) and [`08-project-status.md`](08-project-status.md).

## Project size

| Component | Approximate size |
|---|---|
| Backend (PHP) | ~36K lines, 48 controllers |
| Web UI | 142 Blade pages |
| Flutter app | ~73K lines, ~170 files |
| Database | 65 tables + 3 views (about 120 migrations) |
| API | 330 endpoints |
| Web routes | ~370 routes |
