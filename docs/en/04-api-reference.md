# API Reference

> Auto-generated from `php artisan route:list` (330 endpoints under `/api`). **Base:** `<SERVER>/api` (local example: `http://127.0.0.1:8000/api`).

## General usage

- **Authentication:** Laravel Sanctum. After `POST /api/login` a `token` is returned and must be sent on every request: `Authorization: Bearer <token>` with `Accept: application/json`.
- **Single session:** logging in from a new device is refused while another session is active (`423`). A token that was invalidated receives `401` with `error_code = LOGGED_IN_ELSEWHERE`.
- **Roles:** `student`, `teacher`, `head` (head of department), `admin`, `parent`, `affairs`. A disallowed role gets `403`.
- **Rate limits:** a global `throttle:api` (240 requests/minute per user). Login and OTP routes have stricter limits (shown in the table). Exceeding returns `429`.
- **Response shape:** JSON, usually `{ "success": true|false, "message": "...", "data": ... }`. Errors: `422` validation, `403` permission, `404` not found, `423` account locked or session occupied.
- **Encoding:** UTF-8; Arabic text is returned unescaped.
- **Note:** handler names (`Controller@method`) are descriptive and are the starting point for reading each endpoint's logic in code.

## Index

- Public (no authentication): **12**
- Shared (authentication only): **28**
- Student (`/api/student/*`): **35**
- Teacher (`/api/teacher/*`): **59**
- Head of department (`/api/department-head/*`): **51**
- Admin (`/api/admin/*`): **42**
- Parent (`/api/parent/*`): **38**
- Affairs (`/api/affairs/*`): **65**


## Public (no authentication)

| Method | Path | Handler | Access |
|---|---|---|---|
| GET | `/api/file/{path}` | `Closure` | Public |
| POST | `/api/forgot-password` | `Api\AuthController@forgotPassword` | Public (throttle:otp-send) |
| POST | `/api/login` | `Api\AuthController@login` | Public (throttle:login) |
| POST | `/api/login-otp/send` | `Api\AuthController@sendLoginOtp` | Public (throttle:login-otp) |
| POST | `/api/login-otp/verify` | `Api\AuthController@verifyLoginOtp` | Public (throttle:otp-verify) |
| POST | `/api/register` | `Api\AuthController@register` | Public (throttle:otp-send) |
| POST | `/api/request-device-reset` | `Api\AuthController@requestDeviceReset` | Public (throttle:login) |
| POST | `/api/resend-otp` | `Api\AuthController@resendOtp` | Public (throttle:otp-send) |
| POST | `/api/reset-password` | `Api\AuthController@resetPassword` | Public (throttle:otp-verify) |
| GET | `/api/system/settings` | `Closure` | Public |
| POST | `/api/telegram/webhook` | `Api\TelegramWebhookController@handle` | Public |
| POST | `/api/verify-otp` | `Api\AuthController@verifyOtp` | Public (throttle:otp-verify) |

## Shared (authentication only)

| Method | Path | Handler | Access |
|---|---|---|---|
| POST | `/api/ai/chat` | `Api\AiAssistantController@chat` | Auth (any role) |
| GET | `/api/attendance/{attendanceId}/face` | `Api\FaceImageController@show` | Auth (any role) |
| POST | `/api/broadcasting/auth` | `Closure` | Auth (any role) |
| GET | `/api/contacts` | `ChatController@getContacts` | Auth (any role) |
| POST | `/api/groups` | `ChatController@createGroup` | Auth (any role) |
| GET | `/api/groups/{groupId}/messages` | `ChatController@getGroupMessages` | Auth (any role) |
| POST | `/api/groups/{groupId}/messages` | `ChatController@sendGroupMessage` | Auth (any role) |
| POST | `/api/logout` | `Api\AuthController@logout` | Auth (any role) |
| POST | `/api/messages/forward` | `ChatController@forwardMessage` | Auth (any role) |
| GET | `/api/messages/unread-count` | `ChatController@getUnreadCount` | Auth (any role) |
| GET | `/api/messages/{id}/download` | `ChatController@downloadAttachment` | Auth (any role) |
| DELETE | `/api/messages/{messageId}` | `ChatController@deleteMessage` | Auth (any role) |
| PUT | `/api/messages/{messageId}/edit` | `ChatController@editMessage` | Auth (any role) |
| GET | `/api/messages/{otherUserId}` | `ChatController@getMessages` | Auth (any role) |
| PUT | `/api/messages/{otherUserId}/mark-read` | `ChatController@markAsRead` | Auth (any role) |
| GET | `/api/messages/{otherUserId}/search` | `ChatController@searchMessages` | Auth (any role) |
| GET | `/api/parent/info/{user_id}` | `Closure` | Auth (any role) |
| POST | `/api/parent/link-student` | `Closure` | Auth (any role) |
| POST | `/api/profile/avatar` | `Api\AuthController@updateAvatar` | Auth (any role) |
| POST | `/api/profile/confirm-change-email` | `Api\AuthController@confirmChangeEmail` | Auth (any role) |
| POST | `/api/profile/request-change-email` | `Api\AuthController@requestChangeEmail` | Auth (any role) |
| POST | `/api/profile/send-otp` | `Api\AuthController@sendProfileOtp` | Auth (any role) |
| POST | `/api/profile/update` | `Api\AuthController@updateProfile` | Auth (any role) |
| POST | `/api/profile/verify-otp` | `Api\AuthController@verifyProfileOtp` | Auth (any role) |
| POST | `/api/send-message` | `ChatController@sendMessage` | Auth (any role) |
| GET | `/api/transcript/export-pdf` | `Api\AffairsController@exportCourseWeightsStudentPdf` | Auth (any role) |
| POST | `/api/user/fcm-token` | `Closure` | Auth (any role) |
| GET | `/api/user/profile` | `Closure` | Auth (any role) |

## Student (`/api/student/*`)

| Method | Path | Handler | Access |
|---|---|---|---|
| GET|POST|HEAD | `/api/student/academic-card` | `Api\StudentController@getAcademicCard` | Auth + role: student |
| GET | `/api/student/academic-card/export-pdf` | `Api\StudentController@exportAcademicCardPdf` | Auth + role: student |
| GET | `/api/student/announcements` | `Api\AnnouncementController@getHomeAnnouncements` | Auth + role: student |
| GET | `/api/student/assignments` | `Api\StudentController@getMyAssignments` | Auth + role: student |
| POST | `/api/student/assignments/{id}/submit` | `Api\StudentController@submitAssignment` | Auth + role: student |
| GET | `/api/student/attendance` | `Api\StudentController@getMyAttendance` | Auth + role: student |
| POST | `/api/student/attendance/scan` | `Api\StudentController@scanAttendanceQr` | Auth + role: student |
| POST | `/api/student/attendance/{attendance_id}/excuse` | `Api\StudentController@submitAttendanceExcuse` | Auth + role: student |
| GET | `/api/student/courses` | `Api\StudentController@getMyCourses` | Auth + role: student |
| GET | `/api/student/courses/{courseId}/materials` | `Api\StudentController@getCourseMaterials` | Auth + role: student |
| GET | `/api/student/dashboard` | `Api\StudentController@getDashboardData` | Auth + role: student |
| GET | `/api/student/grade-event/{id}` | `Api\StudentController@getGradeEventForStudent` | Auth + role: student |
| GET | `/api/student/grades` | `Api\StudentController@getMyGrades` | Auth + role: student |
| GET | `/api/student/info/{id}` | `Closure` | Auth + role: student,parent |
| GET | `/api/student/leave-requests` | `Api\StudentController@getMyAbsenceRequests` | Auth + role: student |
| POST | `/api/student/leave-requests` | `Api\StudentController@requestAbsence` | Auth + role: student |
| GET | `/api/student/leave-requests/{id}` | `Api\StudentController@getLeaveDetails` | Auth + role: student |
| GET | `/api/student/lectures` | `Api\StudentController@getMyLectures` | Auth + role: student |
| GET | `/api/student/my-exams` | `Api\StudentController@getMyExams` | Auth + role: student |
| GET | `/api/student/my-exams/excel` | `Api\StudentController@exportExamsExcel` | Auth + role: student |
| GET | `/api/student/my-exams/pdf` | `Api\StudentController@exportExamsPdf` | Auth + role: student |
| GET | `/api/student/my-schedule` | `Api\StudentController@getMySchedule` | Auth + role: student |
| GET | `/api/student/my-schedule/pdf` | `Api\StudentController@exportSchedulePdf` | Auth + role: student |
| GET | `/api/student/notifications` | `Api\StudentController@getNotifications` | Auth + role: student |
| PUT | `/api/student/notifications/read-all` | `Api\StudentController@markAllNotificationsAsRead` | Auth + role: student |
| PUT | `/api/student/notifications/{id}/read` | `Api\StudentController@markNotificationAsRead` | Auth + role: student |
| POST | `/api/student/photo-change-request` | `Api\StudentController@requestPhotoChange` | Auth + role: student |
| GET | `/api/student/photo-change-request/status` | `Api\StudentController@myPhotoChangeStatus` | Auth + role: student |
| GET | `/api/student/profile` | `Api\StudentController@getProfileData` | Auth + role: student |
| POST | `/api/student/profile/initialize-face` | `Api\StudentController@initializeFaceFromPhoto` | Auth + role: student |
| POST | `/api/student/profile/update` | `Api\StudentController@updateProfile` | Auth + role: student |
| GET | `/api/student/program-courses` | `Api\StudentController@getProgramCourses` | Auth + role: student |
| GET | `/api/student/services/requests` | `Api\StudentController@getMyRequests` | Auth + role: student |
| POST | `/api/student/services/requests` | `Api\StudentController@submitRequest` | Auth + role: student |
| GET | `/api/student/warnings` | `Api\StudentController@getMyWarnings` | Auth + role: student |

## Teacher (`/api/teacher/*`)

| Method | Path | Handler | Access |
|---|---|---|---|
| GET | `/api/teacher/announcements` | `Api\TeacherController@getAnnouncements` | Auth + role: teacher |
| POST | `/api/teacher/announcements` | `Api\TeacherController@createAnnouncement` | Auth + role: teacher |
| GET | `/api/teacher/assignments` | `Api\TeacherController@getAssignments` | Auth + role: teacher |
| POST | `/api/teacher/assignments` | `Api\TeacherController@createAssignment` | Auth + role: teacher |
| DELETE | `/api/teacher/assignments/{assignmentId}` | `Api\TeacherController@deleteAssignment` | Auth + role: teacher |
| PUT | `/api/teacher/assignments/{assignmentId}` | `Api\TeacherController@updateAssignment` | Auth + role: teacher |
| GET | `/api/teacher/assignments/{assignmentId}/submissions` | `Api\TeacherController@getAssignmentSubmissions` | Auth + role: teacher |
| POST | `/api/teacher/assignments/{submissionId}/grade` | `Api\TeacherController@gradeAssignment` | Auth + role: teacher |
| POST | `/api/teacher/attendance` | `Api\TeacherController@markAttendance` | Auth + role: teacher |
| GET | `/api/teacher/attendance/advisor-export` | `Api\TeacherController@advisorExportAttendance` | Auth + role: teacher |
| GET | `/api/teacher/attendance/export` | `Api\TeacherController@exportAttendance` | Auth + role: teacher |
| GET | `/api/teacher/attendance/export-pdf` | `Api\TeacherController@exportFilteredPdf` | Auth + role: teacher |
| POST | `/api/teacher/attendance/generate-qr` | `Api\TeacherController@generateQrSession` | Auth + role: teacher |
| POST | `/api/teacher/attendance/session/{sessionId}/end` | `Api\TeacherController@endSession` | Auth + role: teacher |
| GET | `/api/teacher/attendance/session/{sessionId}/list` | `Api\TeacherController@getSessionAttendance` | Auth + role: teacher |
| POST | `/api/teacher/attendance/session/{sessionId}/refresh-qr` | `Api\TeacherController@refreshQrToken` | Auth + role: teacher |
| GET | `/api/teacher/attendance/{courseId}` | `Api\TeacherController@getAttendance` | Auth + role: teacher |
| GET | `/api/teacher/courses` | `Api\TeacherController@myCourses` | Auth + role: teacher |
| GET | `/api/teacher/courses/{courseId}/students` | `Api\TeacherController@courseStudents` | Auth + role: teacher |
| GET | `/api/teacher/dashboard` | `Api\TeacherController@dashboard` | Auth + role: teacher |
| GET | `/api/teacher/educator-students` | `Api\TeacherController@getEducatorStudents` | Auth + role: teacher |
| GET | `/api/teacher/exams` | `Api\TeacherController@getExams` | Auth + role: teacher |
| POST | `/api/teacher/exams` | `Api\TeacherController@createExam` | Auth + role: teacher |
| GET | `/api/teacher/grade-report-requests/pending` | `Api\TeacherController@getPendingGradeReportRequests` | Auth + role: teacher |
| POST | `/api/teacher/grade-report-requests/{id}/complete` | `Api\TeacherController@completeGradeReport` | Auth + role: teacher |
| POST | `/api/teacher/grades` | `Api\TeacherController@enterGrades` | Auth + role: teacher |
| GET | `/api/teacher/grades/events` | `Api\TeacherController@getGradeEvents` | Auth + role: teacher |
| POST | `/api/teacher/grades/events` | `Api\TeacherController@createGradeEvent` | Auth + role: teacher |
| DELETE | `/api/teacher/grades/events/{id}` | `Api\TeacherController@deleteGradeEvent` | Auth + role: teacher |
| GET | `/api/teacher/grades/events/{id}/entries` | `Api\TeacherController@getGradeEntries` | Auth + role: teacher |
| POST | `/api/teacher/grades/events/{id}/entries` | `Api\TeacherController@saveGradeEntries` | Auth + role: teacher |
| GET | `/api/teacher/grades/program-students` | `Api\TeacherController@getProgramStudents` | Auth + role: teacher |
| GET | `/api/teacher/grades/programs` | `Api\TeacherController@getTeacherPrograms` | Auth + role: teacher |
| GET | `/api/teacher/grades/{courseId}` | `Api\TeacherController@getGrades` | Auth + role: teacher |
| GET | `/api/teacher/lessons` | `Api\TeacherController@getLessons` | Auth + role: teacher |
| POST | `/api/teacher/lessons` | `Api\TeacherController@createLesson` | Auth + role: teacher |
| DELETE | `/api/teacher/lessons/{lessonId}` | `Api\TeacherController@deleteLesson` | Auth + role: teacher |
| POST | `/api/teacher/lessons/{lessonId}` | `Api\TeacherController@updateLesson` | Auth + role: teacher |
| GET | `/api/teacher/messages` | `Api\TeacherController@getMessages` | Auth + role: teacher |
| POST | `/api/teacher/messages` | `Api\TeacherController@sendMessage` | Auth + role: teacher |
| GET | `/api/teacher/notifications` | `Api\TeacherController@getNotifications` | Auth + role: teacher |
| PUT | `/api/teacher/notifications/read-all` | `Api\TeacherController@markAllNotificationsRead` | Auth + role: teacher |
| PUT | `/api/teacher/notifications/{notificationId}/read` | `Api\TeacherController@markNotificationRead` | Auth + role: teacher |
| GET | `/api/teacher/parent-summons` | `Api\ParentMeetingController@listSummons` | Auth + role: teacher |
| GET | `/api/teacher/parent-summons-history` | `Api\TeacherController@getTeacherSummonsHistory` | Auth + role: teacher |
| POST | `/api/teacher/parent-summons/request` | `Api\TeacherController@sendParentSummon` | Auth + role: teacher |
| POST | `/api/teacher/parent-summons/send` | `Api\TeacherController@sendParentSummon` | Auth + role: teacher |
| GET | `/api/teacher/profile` | `Api\TeacherController@getTeacherProfile` | Auth + role: teacher |
| PUT | `/api/teacher/profile` | `Api\TeacherController@updateTeacherProfile` | Auth + role: teacher |
| POST | `/api/teacher/profile/avatar` | `Api\TeacherController@updateAvatar` | Auth + role: teacher |
| GET | `/api/teacher/programs` | `Api\TeacherController@myDepartmentPrograms` | Auth + role: teacher |
| GET | `/api/teacher/report-requests` | `Api\TeacherController@getReportRequests` | Auth + role: teacher |
| GET | `/api/teacher/report-requests/{id}/stats` | `Api\TeacherController@getStudentAcademicStats` | Auth + role: teacher |
| POST | `/api/teacher/report-requests/{id}/submit` | `Api\TeacherController@submitEvaluation` | Auth + role: teacher |
| GET | `/api/teacher/schedule` | `Api\TeacherController@getSchedule` | Auth + role: teacher |
| POST | `/api/teacher/students/{studentId}/reset-face` | `Api\TeacherController@resetStudentFace` | Auth + role: teacher |
| GET | `/api/teacher/submissions` | `Api\TeacherController@getSubmissions` | Auth + role: teacher |

## Head of department (`/api/department-head/*`)

| Method | Path | Handler | Access |
|---|---|---|---|
| GET | `/api/department-head/all-exams` | `Api\DepartmentHeadController@getAllExams` | Auth + role: head |
| GET | `/api/department-head/all-schedule` | `Api\DepartmentHeadController@getAllSchedule` | Auth + role: head |
| GET | `/api/department-head/announcements` | `Api\DepartmentHeadController@getAnnouncements` | Auth + role: head |
| POST | `/api/department-head/announcements` | `Api\DepartmentHeadController@createAnnouncement` | Auth + role: head |
| DELETE | `/api/department-head/announcements/{id}` | `Api\DepartmentHeadController@deleteAnnouncement` | Auth + role: head |
| POST | `/api/department-head/announcements/{id}` | `Api\DepartmentHeadController@updateAnnouncement` | Auth + role: head |
| GET | `/api/department-head/appointments/meetings` | `Api\HODController@getMeetingRequests` | Auth + role: head |
| POST | `/api/department-head/appointments/meetings/{id}/respond` | `Api\HODController@respondToMeetingRequest` | Auth + role: head |
| PUT | `/api/department-head/appointments/meetings/{id}/respond` | `Api\HODController@respondToMeetingRequest` | Auth + role: head |
| GET | `/api/department-head/appointments/metadata` | `Api\HODController@getAppointmentsMetadata` | Auth + role: head |
| GET | `/api/department-head/appointments/summons` | `Api\HODController@getSummons` | Auth + role: head |
| POST | `/api/department-head/appointments/summons` | `Api\HODController@storeSummon` | Auth + role: head |
| POST | `/api/department-head/appointments/summons/{id}/forward` | `Api\HODController@forwardSummonToAffairs` | Auth + role: head |
| GET | `/api/department-head/courses` | `Api\DepartmentHeadController@getCourses` | Auth + role: head |
| GET | `/api/department-head/courses/{id}/students` | `Api\DepartmentHeadController@getStudentsByCourse` | Auth + role: head |
| GET | `/api/department-head/courses/{id}/teachers` | `Api\DepartmentHeadController@getTeachersByCourse` | Auth + role: head |
| GET | `/api/department-head/dashboard` | `Api\DepartmentHeadController@dashboard` | Auth + role: head |
| GET | `/api/department-head/grade-report-requests` | `Api\DepartmentHeadController@getGradeReports` | Auth + role: head |
| POST | `/api/department-head/grade-report-requests` | `Api\DepartmentHeadController@requestGradeReport` | Auth + role: head |
| GET | `/api/department-head/grade-report-requests/{courseId}/entries` | `Api\DepartmentHeadController@getCourseGradeEntries` | Auth + role: head |
| POST | `/api/department-head/grade-report-requests/{courseId}/remind-teacher` | `Api\DepartmentHeadController@remindTeacher` | Auth + role: head |
| GET | `/api/department-head/leave-requests` | `Api\DepartmentHeadController@getLeaveRequests` | Auth + role: head |
| PUT | `/api/department-head/leave-requests/{id}/respond` | `Api\DepartmentHeadController@respondLeaveRequest` | Auth + role: head |
| GET | `/api/department-head/metadata` | `Api\DepartmentHeadController@getMetadata` | Auth + role: head |
| GET | `/api/department-head/notifications` | `Api\DepartmentHeadController@getNotifications` | Auth + role: head |
| PUT | `/api/department-head/notifications/read-all` | `Api\DepartmentHeadController@markAllNotificationsRead` | Auth + role: head |
| POST | `/api/department-head/notifications/send` | `Api\DepartmentHeadController@sendNotification` | Auth + role: head |
| PUT | `/api/department-head/notifications/{id}/read` | `Api\DepartmentHeadController@markNotificationRead` | Auth + role: head |
| GET | `/api/department-head/parent-meetings` | `Api\ParentMeetingController@listMeetingRequests` | Auth + role: head |
| PUT | `/api/department-head/parent-meetings/{id}/respond` | `Api\ParentMeetingController@respondToMeetingRequest` | Auth + role: head |
| GET | `/api/department-head/parent-summons` | `Api\ParentMeetingController@listSummons` | Auth + role: head |
| POST | `/api/department-head/parent-summons` | `Api\ParentMeetingController@sendSummon` | Auth + role: head |
| GET | `/api/department-head/profile` | `Api\DepartmentHeadController@getProfile` | Auth + role: head |
| GET | `/api/department-head/programs-schedule` | `Api\DepartmentHeadController@getProgramsSchedule` | Auth + role: head |
| GET | `/api/department-head/report-requests` | `Api\DepartmentHeadController@getReportRequests` | Auth + role: head |
| POST | `/api/department-head/report-requests` | `Api\DepartmentHeadController@createReportRequest` | Auth + role: head |
| DELETE | `/api/department-head/report-requests/{id}` | `Api\DepartmentHeadController@deleteReportRequest` | Auth + role: head |
| POST | `/api/department-head/report-requests/{id}/hod-notes` | `Api\DepartmentHeadController@updateHodNotes` | Auth + role: head |
| POST | `/api/department-head/report-requests/{id}/send-to-parent` | `Api\DepartmentHeadController@sendReportToParent` | Auth + role: head |
| GET | `/api/department-head/schedule` | `Api\DepartmentHeadController@getSchedule` | Auth + role: head |
| POST | `/api/department-head/schedule` | `Api\DepartmentHeadController@createSchedule` | Auth + role: head |
| PUT | `/api/department-head/schedule/{id}` | `Api\DepartmentHeadController@updateSchedule` | Auth + role: head |
| GET | `/api/department-head/student-service-requests` | `Api\DepartmentHeadController@getStudentServiceRequests` | Auth + role: head |
| PUT | `/api/department-head/student-service-requests/{id}/respond` | `Api\DepartmentHeadController@respondStudentServiceRequest` | Auth + role: head |
| GET | `/api/department-head/teachers` | `Api\DepartmentHeadController@getTeachers` | Auth + role: head |
| POST | `/api/department-head/users/parent` | `Api\DepartmentHeadController@createParent` | Auth + role: head |
| GET | `/api/department-head/users/parents` | `Api\DepartmentHeadController@getParents` | Auth + role: head |
| POST | `/api/department-head/users/student` | `Api\DepartmentHeadController@createStudent` | Auth + role: head |
| GET | `/api/department-head/users/students` | `Api\DepartmentHeadController@getStudents` | Auth + role: head |
| POST | `/api/department-head/users/trainer` | `Api\DepartmentHeadController@createTrainer` | Auth + role: head |
| GET | `/api/department-head/users/trainers` | `Api\DepartmentHeadController@getTrainers` | Auth + role: head |

## Admin (`/api/admin/*`)

| Method | Path | Handler | Access |
|---|---|---|---|
| POST | `/api/admin/accounts/{id}/approve` | `Api\AdminController@approveAccount` | Auth + role: admin |
| POST | `/api/admin/accounts/{id}/reject` | `Api\AdminController@rejectAccount` | Auth + role: admin |
| POST | `/api/admin/announcements` | `Api\AdminController@createAnnouncement` | Auth + role: admin |
| DELETE | `/api/admin/announcements/{id}` | `Api\AdminController@deleteAnnouncement` | Auth + role: admin |
| POST | `/api/admin/announcements/{id}` | `Api\AdminController@updateAnnouncement` | Auth + role: admin |
| GET | `/api/admin/assign-hod` | `Api\AdminController@getAssignHodData` | Auth + role: admin |
| POST | `/api/admin/assign-hod` | `Api\AdminController@assignHodExisting` | Auth + role: admin |
| POST | `/api/admin/assign-hod/new` | `Api\AdminController@assignHodNew` | Auth + role: admin |
| POST | `/api/admin/broadcast` | `Api\AdminController@sendBroadcast` | Auth + role: admin |
| GET | `/api/admin/courses` | `Api\AdminController@getCourses` | Auth + role: admin |
| POST | `/api/admin/courses` | `Api\AdminController@createCourse` | Auth + role: admin |
| DELETE | `/api/admin/courses/{id}` | `Api\AdminController@deleteCourse` | Auth + role: admin |
| GET | `/api/admin/courses/{id}` | `Api\AdminController@getCourse` | Auth + role: admin |
| PUT | `/api/admin/courses/{id}` | `Api\AdminController@updateCourse` | Auth + role: admin |
| GET | `/api/admin/dashboard` | `Api\AdminController@dashboard` | Auth + role: admin |
| GET | `/api/admin/departments` | `Api\AdminController@getDepartments` | Auth + role: admin |
| POST | `/api/admin/departments` | `Api\AdminController@createDepartment` | Auth + role: admin |
| POST | `/api/admin/departments/assign-programs` | `Api\AdminController@assignProgramsToDepartment` | Auth + role: admin |
| DELETE | `/api/admin/departments/{id}` | `Api\AdminController@deleteDepartment` | Auth + role: admin |
| PUT | `/api/admin/departments/{id}` | `Api\AdminController@updateDepartment` | Auth + role: admin |
| GET | `/api/admin/parent-meetings` | `Api\ParentMeetingController@listMeetingRequests` | Auth + role: admin |
| PUT | `/api/admin/parent-meetings/{id}/respond` | `Api\ParentMeetingController@respondToMeetingRequest` | Auth + role: admin |
| GET | `/api/admin/parent-summons` | `Api\ParentMeetingController@listSummons` | Auth + role: admin |
| POST | `/api/admin/parent-summons` | `Api\ParentMeetingController@sendSummon` | Auth + role: admin |
| GET | `/api/admin/pending-accounts` | `Api\AdminController@getPendingAccounts` | Auth + role: admin |
| GET | `/api/admin/reports/attendance` | `Api\AdminController@attendanceReport` | Auth + role: admin |
| GET | `/api/admin/reports/export/{id}` | `Api\AdminController@exportReportById` | Auth + role: admin |
| GET | `/api/admin/reports/grades` | `Api\AdminController@gradesReport` | Auth + role: admin |
| GET | `/api/admin/reports/log` | `Api\AdminController@getReportsLog` | Auth + role: admin |
| GET | `/api/admin/reports/students` | `Api\AdminController@studentsReport` | Auth + role: admin |
| GET | `/api/admin/reports/view/{id}` | `Api\AdminController@viewReportById` | Auth + role: admin |
| GET | `/api/admin/semesters` | `Api\AdminController@getSemesters` | Auth + role: admin |
| POST | `/api/admin/semesters` | `Api\AdminController@createSemester` | Auth + role: admin |
| GET | `/api/admin/semesters-subjects` | `Api\AdminController@getSemestersSubjects` | Auth + role: admin |
| DELETE | `/api/admin/semesters/{id}` | `Api\AdminController@deleteSemester` | Auth + role: admin |
| PUT | `/api/admin/semesters/{id}` | `Api\AdminController@updateSemester` | Auth + role: admin |
| GET | `/api/admin/student-services` | `Api\AdminController@getStudentServices` | Auth + role: admin |
| POST | `/api/admin/student-services/{id}/process` | `Api\AdminController@processStudentService` | Auth + role: admin |
| GET | `/api/admin/users` | `Api\AdminController@getUsers` | Auth + role: admin |
| POST | `/api/admin/users` | `Api\AdminController@createUser` | Auth + role: admin |
| DELETE | `/api/admin/users/{id}` | `Api\AdminController@deleteUser` | Auth + role: admin |
| PUT | `/api/admin/users/{id}` | `Api\AdminController@updateUser` | Auth + role: admin |

## Parent (`/api/parent/*`)

| Method | Path | Handler | Access |
|---|---|---|---|
| POST | `/api/parent/add-student` | `Api\ParentController@linkStudent` | Auth + role: parent |
| DELETE | `/api/parent/affairs/notifications/{id}` | `NotificationController@deleteNotification` | Auth + role: parent |
| GET | `/api/parent/announcements` | `Api\ParentController@getAnnouncements` | Auth + role: parent |
| DELETE | `/api/parent/children/{id}` | `Api\ParentController@unlinkStudent` | Auth + role: parent |
| GET | `/api/parent/children/{id}/academic-card` | `Api\ParentController@getChildAcademicCard` | Auth + role: parent |
| GET | `/api/parent/children/{id}/academic-card/export-excel` | `Api\ParentController@exportChildAcademicCardExcel` | Auth + role: parent |
| GET | `/api/parent/children/{id}/academic-card/export-pdf` | `Api\ParentController@exportChildAcademicCardPdf` | Auth + role: parent |
| GET | `/api/parent/children/{id}/assignments` | `Api\ParentController@getChildAssignments` | Auth + role: parent |
| GET | `/api/parent/children/{id}/attendance` | `Api\ParentController@getChildAttendance` | Auth + role: parent |
| GET | `/api/parent/children/{id}/details` | `Api\ParentController@getChildDetails` | Auth + role: parent |
| GET | `/api/parent/children/{id}/grades` | `Api\ParentController@getChildGrades` | Auth + role: parent |
| GET | `/api/parent/children/{id}/schedule` | `Api\ParentController@getChildSchedule` | Auth + role: parent |
| POST | `/api/parent/children/{id}/unlink` | `Api\ParentController@unlinkStudent` | Auth + role: parent |
| GET | `/api/parent/children/{parent_id?}` | `Api\ParentController@getChildren` | Auth + role: parent |
| GET | `/api/parent/dashboard` | `Api\ParentController@dashboard` | Auth + role: parent |
| DELETE | `/api/parent/hod/notifications/{id}` | `NotificationController@deleteNotification` | Auth + role: parent |
| GET | `/api/parent/leave-requests` | `StudentParentController@getLeaveRequests` | Auth + role: parent |
| POST | `/api/parent/leave-requests/submit` | `StudentParentController@submitParentLeaveRequest` | Auth + role: parent |
| POST | `/api/parent/leave-requests/{id}/respond` | `StudentParentController@respondLeaveRequest` | Auth + role: parent |
| GET | `/api/parent/meeting-requests` | `Api\ParentController@getMyMeetingRequests` | Auth + role: parent |
| GET | `/api/parent/notifications` | `NotificationController@getNotifications` | Auth + role: parent |
| DELETE | `/api/parent/notifications/chat/{senderId}` | `NotificationController@deleteChatNotifications` | Auth + role: parent |
| PUT | `/api/parent/notifications/read-all` | `NotificationController@markAllAsRead` | Auth + role: parent |
| PUT | `/api/parent/notifications/read-by-type` | `NotificationController@markByTypeAndRelatedId` | Auth + role: parent |
| POST | `/api/parent/notifications/toggle-mute` | `NotificationController@toggleMute` | Auth + role: parent |
| DELETE | `/api/parent/notifications/{id}` | `NotificationController@deleteNotification` | Auth + role: parent |
| PUT | `/api/parent/notifications/{id}/read` | `NotificationController@markAsRead` | Auth + role: parent |
| GET | `/api/parent/performance/{studentId}` | `StudentParentController@getFullPerformance` | Auth + role: parent |
| GET | `/api/parent/reports/history` | `Api\ParentController@getReportsHistory` | Auth + role: parent |
| POST | `/api/parent/request-meeting` | `Api\ParentController@requestMeeting` | Auth + role: parent |
| POST | `/api/parent/request-report` | `Api\ParentController@requestReport` | Auth + role: parent |
| DELETE | `/api/parent/student/notifications/{id}` | `NotificationController@deleteNotification` | Auth + role: parent |
| GET | `/api/parent/student/{studentId}/assignments` | `StudentParentController@getAssignments` | Auth + role: parent |
| GET | `/api/parent/summons` | `Api\ParentController@getMySummons` | Auth + role: parent |
| POST | `/api/parent/summons/{id}/respond` | `Api\ParentMeetingController@respondToSummon` | Auth + role: parent |
| DELETE | `/api/parent/teacher/notifications/{id}` | `NotificationController@deleteNotification` | Auth + role: parent |

## Affairs (`/api/affairs/*`)

| Method | Path | Handler | Access |
|---|---|---|---|
| GET | `/api/affairs/academic-card` | `Api\AffairsController@getStudentAcademicCardForAffairs` | Auth + role: affairs,admin |
| GET | `/api/affairs/academic-card/export-pdf` | `Api\AffairsController@exportStudentAcademicCardPdf` | Auth + role: affairs,admin |
| GET | `/api/affairs/accounts` | `Api\AffairsController@listAccounts` | Auth + role: affairs,admin |
| POST | `/api/affairs/accounts/create` | `Api\AffairsController@createAccount` | Auth + role: affairs,admin |
| DELETE | `/api/affairs/accounts/{id}` | `Api\AffairsController@deleteAccount` | Auth + role: affairs,admin |
| POST | `/api/affairs/accounts/{id}/toggle` | `Api\AffairsController@toggleAccountStatus` | Auth + role: affairs,admin |
| POST | `/api/affairs/accounts/{id}/update` | `Api\AffairsController@updateAccount` | Auth + role: affairs,admin |
| POST | `/api/affairs/accounts/{userId}/approve` | `Api\AffairsController@approveAccount` | Auth + role: affairs,admin |
| POST | `/api/affairs/accounts/{userId}/reject` | `Api\AffairsController@rejectAccount` | Auth + role: affairs,admin |
| GET | `/api/affairs/announcements` | `Api\AffairsController@listAnnouncements` | Auth + role: affairs,admin |
| POST | `/api/affairs/announcements` | `Api\AffairsController@createAnnouncement` | Auth + role: affairs,admin |
| DELETE | `/api/affairs/announcements/{id}` | `Api\AffairsController@deleteAnnouncement` | Auth + role: affairs,admin |
| POST | `/api/affairs/announcements/{id}` | `Api\AffairsController@updateAnnouncement` | Auth + role: affairs,admin |
| GET | `/api/affairs/appointments/meetings` | `Api\AffairsController@getMeetingRequests` | Auth + role: affairs,admin |
| POST | `/api/affairs/appointments/meetings/{id}/respond` | `Api\AffairsController@respondToMeetingRequest` | Auth + role: affairs,admin |
| PUT | `/api/affairs/appointments/meetings/{id}/respond` | `Api\AffairsController@respondToMeetingRequest` | Auth + role: affairs,admin |
| GET | `/api/affairs/appointments/metadata` | `Api\AffairsController@getAppointmentsMetadata` | Auth + role: affairs,admin |
| GET | `/api/affairs/appointments/summons` | `Api\AffairsController@getSummons` | Auth + role: affairs,admin |
| POST | `/api/affairs/appointments/summons` | `Api\AffairsController@storeSummon` | Auth + role: affairs,admin |
| POST | `/api/affairs/appointments/summons/{id}/issue` | `Api\AffairsController@issueParentSummon` | Auth + role: affairs,admin |
| POST | `/api/affairs/broadcasting/auth` | `Closure` | Auth + role: affairs,admin |
| GET | `/api/affairs/calendar` | `Api\AffairsController@listCalendarEvents` | Auth + role: affairs,admin |
| POST | `/api/affairs/calendar/events` | `Api\AffairsController@storeCalendarEvent` | Auth + role: affairs,admin |
| POST | `/api/affairs/calendar/events/delete/{id}` | `Api\AffairsController@deleteCalendarEvent` | Auth + role: affairs,admin |
| POST | `/api/affairs/calendar/events/update/{id}` | `Api\AffairsController@updateCalendarEvent` | Auth + role: affairs,admin |
| GET | `/api/affairs/course-weights/data` | `Api\AffairsController@getCourseWeightsData` | Auth + role: affairs,admin |
| GET | `/api/affairs/course-weights/export-cohort-pdf` | `Api\AffairsController@exportCourseWeightsCohortPdf` | Auth + role: affairs,admin |
| GET | `/api/affairs/course-weights/export-student-pdf` | `Api\AffairsController@exportCourseWeightsStudentPdf` | Auth + role: affairs,admin |
| POST | `/api/affairs/course-weights/student-decision` | `Api\AffairsController@updateStudentAcademicDecision` | Auth + role: affairs,admin |
| GET | `/api/affairs/dashboard` | `Api\AffairsController@getDashboardStats` | Auth + role: affairs,admin |
| GET | `/api/affairs/leaves` | `Api\AffairsController@listLeaves` | Auth + role: affairs,admin |
| POST | `/api/affairs/leaves/{id}/status` | `Api\AffairsController@updateLeaveStatus` | Auth + role: affairs,admin |
| GET | `/api/affairs/messages` | `Api\AffairsController@listMessages` | Auth + role: affairs,admin |
| POST | `/api/affairs/messages` | `Api\AffairsController@sendMessage` | Auth + role: affairs,admin |
| GET | `/api/affairs/messages/conversation/{userId}` | `Api\AffairsController@getConversation` | Auth + role: affairs,admin |
| GET | `/api/affairs/metadata` | `Api\AffairsController@getMetadata` | Auth + role: affairs,admin |
| GET | `/api/affairs/notifications` | `Api\AffairsController@listNotifications` | Auth + role: affairs,admin |
| POST | `/api/affairs/notifications/read-all` | `Api\AffairsController@markAllNotificationsRead` | Auth + role: affairs,admin |
| POST | `/api/affairs/notifications/{id}/read` | `Api\AffairsController@markNotificationRead` | Auth + role: affairs,admin |
| POST | `/api/affairs/parents-students/create-parent` | `Api\AffairsController@createParentAndLink` | Auth + role: affairs,admin |
| POST | `/api/affairs/parents-students/link` | `Api\AffairsController@linkStudentToParent` | Auth + role: affairs,admin |
| GET | `/api/affairs/parents-students/parents` | `Api\AffairsController@listParentsWithStudents` | Auth + role: affairs,admin |
| POST | `/api/affairs/parents-students/unlink` | `Api\AffairsController@unlinkStudentFromParent` | Auth + role: affairs,admin |
| GET | `/api/affairs/parents-students/unlinked` | `Api\AffairsController@listUnlinkedStudents` | Auth + role: affairs,admin |
| GET | `/api/affairs/pending-accounts` | `Api\AffairsController@pendingAccounts` | Auth + role: affairs,admin |
| GET | `/api/affairs/photo-change-requests` | `Api\AffairsController@listPhotoChangeRequests` | Auth + role: affairs,admin |
| POST | `/api/affairs/photo-change-requests/{id}/approve` | `Api\AffairsController@approvePhotoChange` | Auth + role: affairs,admin |
| POST | `/api/affairs/photo-change-requests/{id}/reject` | `Api\AffairsController@rejectPhotoChange` | Auth + role: affairs,admin |
| GET | `/api/affairs/profile` | `Api\AffairsController@getProfile` | Auth + role: affairs,admin |
| POST | `/api/affairs/profile/password` | `Api\AffairsController@updatePassword` | Auth + role: affairs,admin |
| POST | `/api/affairs/profile/update` | `Api\AffairsController@updateProfile` | Auth + role: affairs,admin |
| GET | `/api/affairs/semesters` | `Api\AffairsController@listSemesters` | Auth + role: affairs,admin |
| POST | `/api/affairs/semesters/{id}/activate` | `Api\AffairsController@activateSemester` | Auth + role: affairs,admin |
| GET | `/api/affairs/student-service-requests` | `Api\AffairsController@listStudentRequests` | Auth + role: affairs,admin |
| POST | `/api/affairs/student-service-requests/{id}/process` | `Api\AffairsController@processStudentRequest` | Auth + role: affairs,admin |
| GET | `/api/affairs/student-services` | `Api\AffairsController@getStudentServices` | Auth + role: affairs,admin |
| POST | `/api/affairs/student-services/{id}/process` | `Api\AffairsController@processStudentService` | Auth + role: affairs,admin |
| GET | `/api/affairs/students-academic` | `Api\AffairsController@getFilteredStudentsForAcademicCard` | Auth + role: affairs,admin |
| POST | `/api/affairs/students/promote` | `Api\AffairsController@promoteStudentsYear` | Auth + role: affairs,admin |
| POST | `/api/affairs/students/{id}/reset-device` | `Api\AffairsController@resetDevice` | Auth + role: affairs,admin |
| GET | `/api/affairs/university-ids` | `Api\AffairsController@listUniversityIds` | Auth + role: affairs,admin |
| POST | `/api/affairs/university-ids` | `Api\AffairsController@addUniversityId` | Auth + role: affairs,admin |
| GET | `/api/affairs/university-ids/next-id` | `Api\AffairsController@nextUniversityId` | Auth + role: affairs,admin |
| DELETE | `/api/affairs/university-ids/{id}` | `Api\AffairsController@deleteUniversityId` | Auth + role: affairs,admin |
| POST | `/api/affairs/university-ids/{id}/update` | `Api\AffairsController@updateUniversityId` | Auth + role: affairs,admin |