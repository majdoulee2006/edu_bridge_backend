# مرجع الـ API

> مُولَّد آلياً من `php artisan route:list` (329 نقطة نهاية تحت `/api`). **الأساس:** `<SERVER>/api` (مثال محلي: `http://127.0.0.1:8000/api`).

## الاستخدام العام

- **المصادقة:** Laravel Sanctum. بعد `POST /api/login` يرجع `token`، ويُرسَل في كل طلب: `Authorization: Bearer <token>` مع `Accept: application/json`.
- **جلسة واحدة:** تسجيل الدخول من جهاز جديد لا يُسمح به أثناء وجود جلسة نشطة (يرجع `423`). التوكن الذي أُبطل يتلقى `401` مع `error_code = LOGGED_IN_ELSEWHERE`.
- **الأدوار:** `student`, `teacher`, `head` (رئيس القسم), `admin`, `parent`, `affairs`. الرد على دور غير مسموح: `403`.
- **حدود المعدّل:** `throttle:api` عام (240 طلب/دقيقة لكل مستخدم). مسارات الدخول والـ OTP لها حدود أشد (⏱ في الجدول). التجاوز يرجع `429`.
- **صيغة الرد:** JSON، غالباً `{ "success": true|false, "message": "...", "data": ... }`. الأخطاء: `422` للتحقق، `403` للصلاحية، `404` غير موجود، `423` حساب مقفول أو جلسة مشغولة.
- **ترميز:** UTF-8، والنصوص العربية تُرجَع بدون escape.
- **ملاحظة:** أسماء الدوال (`Controller@method`) وصفية، وهي نقطة البداية لقراءة منطق كل نقطة في الكود.

## فهرس

- عام (بدون مصادقة): **12**
- مشترك بين الأدوار (مصادقة فقط): **27**
- الطالب (`/api/student/*`): **35**
- المعلّم (`/api/teacher/*`): **59**
- رئيس القسم (`/api/department-head/*`): **51**
- الإدارة (`/api/admin/*`): **42**
- ولي الأمر (`/api/parent/*`): **38**
- الشؤون (`/api/affairs/*`): **65**


## عام (بدون مصادقة)

| الطريقة | المسار | المعالج | الصلاحية |
|---|---|---|---|
| GET | `/api/file/{path}` | `Closure` | عام |
| POST | `/api/forgot-password` | `Api\AuthController@forgotPassword` | عام ⏱otp-send |
| POST | `/api/login` | `Api\AuthController@login` | عام ⏱login |
| POST | `/api/login-otp/send` | `Api\AuthController@sendLoginOtp` | عام ⏱login-otp |
| POST | `/api/login-otp/verify` | `Api\AuthController@verifyLoginOtp` | عام ⏱otp-verify |
| POST | `/api/register` | `Api\AuthController@register` | عام ⏱otp-send |
| POST | `/api/request-device-reset` | `Api\AuthController@requestDeviceReset` | عام ⏱login |
| POST | `/api/resend-otp` | `Api\AuthController@resendOtp` | عام ⏱otp-send |
| POST | `/api/reset-password` | `Api\AuthController@resetPassword` | عام ⏱otp-verify |
| GET | `/api/system/settings` | `Closure` | عام |
| POST | `/api/telegram/webhook` | `Api\TelegramWebhookController@handle` | عام |
| POST | `/api/verify-otp` | `Api\AuthController@verifyOtp` | عام ⏱otp-verify |

## مشترك بين الأدوار (مصادقة فقط)

| الطريقة | المسار | المعالج | الصلاحية |
|---|---|---|---|
| POST | `/api/ai/chat` | `Api\AiAssistantController@chat` | مصادقة (أي دور) |
| POST | `/api/broadcasting/auth` | `Closure` | مصادقة (أي دور) |
| GET | `/api/contacts` | `ChatController@getContacts` | مصادقة (أي دور) |
| POST | `/api/groups` | `ChatController@createGroup` | مصادقة (أي دور) |
| GET | `/api/groups/{groupId}/messages` | `ChatController@getGroupMessages` | مصادقة (أي دور) |
| POST | `/api/groups/{groupId}/messages` | `ChatController@sendGroupMessage` | مصادقة (أي دور) |
| POST | `/api/logout` | `Api\AuthController@logout` | مصادقة (أي دور) |
| POST | `/api/messages/forward` | `ChatController@forwardMessage` | مصادقة (أي دور) |
| GET | `/api/messages/unread-count` | `ChatController@getUnreadCount` | مصادقة (أي دور) |
| GET | `/api/messages/{id}/download` | `ChatController@downloadAttachment` | مصادقة (أي دور) |
| DELETE | `/api/messages/{messageId}` | `ChatController@deleteMessage` | مصادقة (أي دور) |
| PUT | `/api/messages/{messageId}/edit` | `ChatController@editMessage` | مصادقة (أي دور) |
| GET | `/api/messages/{otherUserId}` | `ChatController@getMessages` | مصادقة (أي دور) |
| PUT | `/api/messages/{otherUserId}/mark-read` | `ChatController@markAsRead` | مصادقة (أي دور) |
| GET | `/api/messages/{otherUserId}/search` | `ChatController@searchMessages` | مصادقة (أي دور) |
| GET | `/api/parent/info/{user_id}` | `Closure` | مصادقة (أي دور) |
| POST | `/api/parent/link-student` | `Closure` | مصادقة (أي دور) |
| POST | `/api/profile/avatar` | `Api\AuthController@updateAvatar` | مصادقة (أي دور) |
| POST | `/api/profile/confirm-change-email` | `Api\AuthController@confirmChangeEmail` | مصادقة (أي دور) |
| POST | `/api/profile/request-change-email` | `Api\AuthController@requestChangeEmail` | مصادقة (أي دور) |
| POST | `/api/profile/send-otp` | `Api\AuthController@sendProfileOtp` | مصادقة (أي دور) |
| POST | `/api/profile/update` | `Api\AuthController@updateProfile` | مصادقة (أي دور) |
| POST | `/api/profile/verify-otp` | `Api\AuthController@verifyProfileOtp` | مصادقة (أي دور) |
| POST | `/api/send-message` | `ChatController@sendMessage` | مصادقة (أي دور) |
| GET | `/api/transcript/export-pdf` | `Api\AffairsController@exportCourseWeightsStudentPdf` | مصادقة (أي دور) |
| POST | `/api/user/fcm-token` | `Closure` | مصادقة (أي دور) |
| GET | `/api/user/profile` | `Closure` | مصادقة (أي دور) |

## الطالب (`/api/student/*`)

| الطريقة | المسار | المعالج | الصلاحية |
|---|---|---|---|
| GET|POST|HEAD | `/api/student/academic-card` | `Api\StudentController@getAcademicCard` | مصادقة + دور: student |
| GET | `/api/student/academic-card/export-pdf` | `Api\StudentController@exportAcademicCardPdf` | مصادقة + دور: student |
| GET | `/api/student/announcements` | `Api\AnnouncementController@getHomeAnnouncements` | مصادقة + دور: student |
| GET | `/api/student/assignments` | `Api\StudentController@getMyAssignments` | مصادقة + دور: student |
| POST | `/api/student/assignments/{id}/submit` | `Api\StudentController@submitAssignment` | مصادقة + دور: student |
| GET | `/api/student/attendance` | `Api\StudentController@getMyAttendance` | مصادقة + دور: student |
| POST | `/api/student/attendance/scan` | `Api\StudentController@scanAttendanceQr` | مصادقة + دور: student |
| POST | `/api/student/attendance/{attendance_id}/excuse` | `Api\StudentController@submitAttendanceExcuse` | مصادقة + دور: student |
| GET | `/api/student/courses` | `Api\StudentController@getMyCourses` | مصادقة + دور: student |
| GET | `/api/student/courses/{courseId}/materials` | `Api\StudentController@getCourseMaterials` | مصادقة + دور: student |
| GET | `/api/student/dashboard` | `Api\StudentController@getDashboardData` | مصادقة + دور: student |
| GET | `/api/student/grade-event/{id}` | `Api\StudentController@getGradeEventForStudent` | مصادقة + دور: student |
| GET | `/api/student/grades` | `Api\StudentController@getMyGrades` | مصادقة + دور: student |
| GET | `/api/student/info/{id}` | `Closure` | مصادقة + دور: student,parent |
| GET | `/api/student/leave-requests` | `Api\StudentController@getMyAbsenceRequests` | مصادقة + دور: student |
| POST | `/api/student/leave-requests` | `Api\StudentController@requestAbsence` | مصادقة + دور: student |
| GET | `/api/student/leave-requests/{id}` | `Api\StudentController@getLeaveDetails` | مصادقة + دور: student |
| GET | `/api/student/lectures` | `Api\StudentController@getMyLectures` | مصادقة + دور: student |
| GET | `/api/student/my-exams` | `Api\StudentController@getMyExams` | مصادقة + دور: student |
| GET | `/api/student/my-exams/excel` | `Api\StudentController@exportExamsExcel` | مصادقة + دور: student |
| GET | `/api/student/my-exams/pdf` | `Api\StudentController@exportExamsPdf` | مصادقة + دور: student |
| GET | `/api/student/my-schedule` | `Api\StudentController@getMySchedule` | مصادقة + دور: student |
| GET | `/api/student/my-schedule/pdf` | `Api\StudentController@exportSchedulePdf` | مصادقة + دور: student |
| GET | `/api/student/notifications` | `Api\StudentController@getNotifications` | مصادقة + دور: student |
| PUT | `/api/student/notifications/read-all` | `Api\StudentController@markAllNotificationsAsRead` | مصادقة + دور: student |
| PUT | `/api/student/notifications/{id}/read` | `Api\StudentController@markNotificationAsRead` | مصادقة + دور: student |
| POST | `/api/student/photo-change-request` | `Api\StudentController@requestPhotoChange` | مصادقة + دور: student |
| GET | `/api/student/photo-change-request/status` | `Api\StudentController@myPhotoChangeStatus` | مصادقة + دور: student |
| GET | `/api/student/profile` | `Api\StudentController@getProfileData` | مصادقة + دور: student |
| POST | `/api/student/profile/initialize-face` | `Api\StudentController@initializeFaceFromPhoto` | مصادقة + دور: student |
| POST | `/api/student/profile/update` | `Api\StudentController@updateProfile` | مصادقة + دور: student |
| GET | `/api/student/program-courses` | `Api\StudentController@getProgramCourses` | مصادقة + دور: student |
| GET | `/api/student/services/requests` | `Api\StudentController@getMyRequests` | مصادقة + دور: student |
| POST | `/api/student/services/requests` | `Api\StudentController@submitRequest` | مصادقة + دور: student |
| GET | `/api/student/warnings` | `Api\StudentController@getMyWarnings` | مصادقة + دور: student |

## المعلّم (`/api/teacher/*`)

| الطريقة | المسار | المعالج | الصلاحية |
|---|---|---|---|
| GET | `/api/teacher/absence-requests` | `Api\TeacherController@getAbsenceRequests` | مصادقة + دور: teacher |
| PUT | `/api/teacher/absence-requests/{requestId}/respond` | `Api\TeacherController@respondAbsenceRequest` | مصادقة + دور: teacher |
| GET | `/api/teacher/announcements` | `Api\TeacherController@getAnnouncements` | مصادقة + دور: teacher |
| POST | `/api/teacher/announcements` | `Api\TeacherController@createAnnouncement` | مصادقة + دور: teacher |
| GET | `/api/teacher/assignments` | `Api\TeacherController@getAssignments` | مصادقة + دور: teacher |
| POST | `/api/teacher/assignments` | `Api\TeacherController@createAssignment` | مصادقة + دور: teacher |
| DELETE | `/api/teacher/assignments/{assignmentId}` | `Api\TeacherController@deleteAssignment` | مصادقة + دور: teacher |
| PUT | `/api/teacher/assignments/{assignmentId}` | `Api\TeacherController@updateAssignment` | مصادقة + دور: teacher |
| GET | `/api/teacher/assignments/{assignmentId}/submissions` | `Api\TeacherController@getAssignmentSubmissions` | مصادقة + دور: teacher |
| POST | `/api/teacher/assignments/{submissionId}/grade` | `Api\TeacherController@gradeAssignment` | مصادقة + دور: teacher |
| POST | `/api/teacher/attendance` | `Api\TeacherController@markAttendance` | مصادقة + دور: teacher |
| GET | `/api/teacher/attendance/advisor-export` | `Api\TeacherController@advisorExportAttendance` | مصادقة + دور: teacher |
| GET | `/api/teacher/attendance/export` | `Api\TeacherController@exportAttendance` | مصادقة + دور: teacher |
| GET | `/api/teacher/attendance/export-pdf` | `Api\TeacherController@exportFilteredPdf` | مصادقة + دور: teacher |
| POST | `/api/teacher/attendance/generate-qr` | `Api\TeacherController@generateQrSession` | مصادقة + دور: teacher |
| POST | `/api/teacher/attendance/session/{sessionId}/end` | `Api\TeacherController@endSession` | مصادقة + دور: teacher |
| GET | `/api/teacher/attendance/session/{sessionId}/list` | `Api\TeacherController@getSessionAttendance` | مصادقة + دور: teacher |
| POST | `/api/teacher/attendance/session/{sessionId}/refresh-qr` | `Api\TeacherController@refreshQrToken` | مصادقة + دور: teacher |
| GET | `/api/teacher/attendance/{courseId}` | `Api\TeacherController@getAttendance` | مصادقة + دور: teacher |
| GET | `/api/teacher/courses` | `Api\TeacherController@myCourses` | مصادقة + دور: teacher |
| GET | `/api/teacher/courses/{courseId}/students` | `Api\TeacherController@courseStudents` | مصادقة + دور: teacher |
| GET | `/api/teacher/dashboard` | `Api\TeacherController@dashboard` | مصادقة + دور: teacher |
| GET | `/api/teacher/educator-students` | `Api\TeacherController@getEducatorStudents` | مصادقة + دور: teacher |
| GET | `/api/teacher/exams` | `Api\TeacherController@getExams` | مصادقة + دور: teacher |
| POST | `/api/teacher/exams` | `Api\TeacherController@createExam` | مصادقة + دور: teacher |
| GET | `/api/teacher/grade-report-requests/pending` | `Api\TeacherController@getPendingGradeReportRequests` | مصادقة + دور: teacher |
| POST | `/api/teacher/grade-report-requests/{id}/complete` | `Api\TeacherController@completeGradeReport` | مصادقة + دور: teacher |
| POST | `/api/teacher/grades` | `Api\TeacherController@enterGrades` | مصادقة + دور: teacher |
| GET | `/api/teacher/grades/events` | `Api\TeacherController@getGradeEvents` | مصادقة + دور: teacher |
| POST | `/api/teacher/grades/events` | `Api\TeacherController@createGradeEvent` | مصادقة + دور: teacher |
| DELETE | `/api/teacher/grades/events/{id}` | `Api\TeacherController@deleteGradeEvent` | مصادقة + دور: teacher |
| GET | `/api/teacher/grades/events/{id}/entries` | `Api\TeacherController@getGradeEntries` | مصادقة + دور: teacher |
| POST | `/api/teacher/grades/events/{id}/entries` | `Api\TeacherController@saveGradeEntries` | مصادقة + دور: teacher |
| GET | `/api/teacher/grades/program-students` | `Api\TeacherController@getProgramStudents` | مصادقة + دور: teacher |
| GET | `/api/teacher/grades/programs` | `Api\TeacherController@getTeacherPrograms` | مصادقة + دور: teacher |
| GET | `/api/teacher/grades/{courseId}` | `Api\TeacherController@getGrades` | مصادقة + دور: teacher |
| GET | `/api/teacher/lessons` | `Api\TeacherController@getLessons` | مصادقة + دور: teacher |
| POST | `/api/teacher/lessons` | `Api\TeacherController@createLesson` | مصادقة + دور: teacher |
| DELETE | `/api/teacher/lessons/{lessonId}` | `Api\TeacherController@deleteLesson` | مصادقة + دور: teacher |
| POST | `/api/teacher/lessons/{lessonId}` | `Api\TeacherController@updateLesson` | مصادقة + دور: teacher |
| GET | `/api/teacher/messages` | `Api\TeacherController@getMessages` | مصادقة + دور: teacher |
| POST | `/api/teacher/messages` | `Api\TeacherController@sendMessage` | مصادقة + دور: teacher |
| GET | `/api/teacher/notifications` | `Api\TeacherController@getNotifications` | مصادقة + دور: teacher |
| PUT | `/api/teacher/notifications/read-all` | `Api\TeacherController@markAllNotificationsRead` | مصادقة + دور: teacher |
| PUT | `/api/teacher/notifications/{notificationId}/read` | `Api\TeacherController@markNotificationRead` | مصادقة + دور: teacher |
| GET | `/api/teacher/parent-summons` | `Api\ParentMeetingController@listSummons` | مصادقة + دور: teacher |
| GET | `/api/teacher/parent-summons-history` | `Api\TeacherController@getTeacherSummonsHistory` | مصادقة + دور: teacher |
| POST | `/api/teacher/parent-summons/request` | `Api\TeacherController@requestParentSummon` | مصادقة + دور: teacher |
| POST | `/api/teacher/parent-summons/send` | `Api\TeacherController@sendParentSummon` | مصادقة + دور: teacher |
| GET | `/api/teacher/profile` | `Api\TeacherController@getTeacherProfile` | مصادقة + دور: teacher |
| PUT | `/api/teacher/profile` | `Api\TeacherController@updateTeacherProfile` | مصادقة + دور: teacher |
| POST | `/api/teacher/profile/avatar` | `Api\TeacherController@updateAvatar` | مصادقة + دور: teacher |
| GET | `/api/teacher/programs` | `Api\TeacherController@myDepartmentPrograms` | مصادقة + دور: teacher |
| GET | `/api/teacher/report-requests` | `Api\TeacherController@getReportRequests` | مصادقة + دور: teacher |
| GET | `/api/teacher/report-requests/{id}/stats` | `Api\TeacherController@getStudentAcademicStats` | مصادقة + دور: teacher |
| POST | `/api/teacher/report-requests/{id}/submit` | `Api\TeacherController@submitEvaluation` | مصادقة + دور: teacher |
| GET | `/api/teacher/schedule` | `Api\TeacherController@getSchedule` | مصادقة + دور: teacher |
| POST | `/api/teacher/students/{studentId}/reset-face` | `Api\TeacherController@resetStudentFace` | مصادقة + دور: teacher |
| GET | `/api/teacher/submissions` | `Api\TeacherController@getSubmissions` | مصادقة + دور: teacher |

## رئيس القسم (`/api/department-head/*`)

| الطريقة | المسار | المعالج | الصلاحية |
|---|---|---|---|
| GET | `/api/department-head/all-exams` | `Api\DepartmentHeadController@getAllExams` | مصادقة + دور: head |
| GET | `/api/department-head/all-schedule` | `Api\DepartmentHeadController@getAllSchedule` | مصادقة + دور: head |
| GET | `/api/department-head/announcements` | `Api\DepartmentHeadController@getAnnouncements` | مصادقة + دور: head |
| POST | `/api/department-head/announcements` | `Api\DepartmentHeadController@createAnnouncement` | مصادقة + دور: head |
| DELETE | `/api/department-head/announcements/{id}` | `Api\DepartmentHeadController@deleteAnnouncement` | مصادقة + دور: head |
| POST | `/api/department-head/announcements/{id}` | `Api\DepartmentHeadController@updateAnnouncement` | مصادقة + دور: head |
| GET | `/api/department-head/appointments/meetings` | `Api\HODController@getMeetingRequests` | مصادقة + دور: head |
| POST | `/api/department-head/appointments/meetings/{id}/respond` | `Api\HODController@respondToMeetingRequest` | مصادقة + دور: head |
| PUT | `/api/department-head/appointments/meetings/{id}/respond` | `Api\HODController@respondToMeetingRequest` | مصادقة + دور: head |
| GET | `/api/department-head/appointments/metadata` | `Api\HODController@getAppointmentsMetadata` | مصادقة + دور: head |
| GET | `/api/department-head/appointments/summons` | `Api\HODController@getSummons` | مصادقة + دور: head |
| POST | `/api/department-head/appointments/summons` | `Api\HODController@storeSummon` | مصادقة + دور: head |
| POST | `/api/department-head/appointments/summons/{id}/forward` | `Api\HODController@forwardSummonToAffairs` | مصادقة + دور: head |
| GET | `/api/department-head/courses` | `Api\DepartmentHeadController@getCourses` | مصادقة + دور: head |
| GET | `/api/department-head/courses/{id}/students` | `Api\DepartmentHeadController@getStudentsByCourse` | مصادقة + دور: head |
| GET | `/api/department-head/courses/{id}/teachers` | `Api\DepartmentHeadController@getTeachersByCourse` | مصادقة + دور: head |
| GET | `/api/department-head/dashboard` | `Api\DepartmentHeadController@dashboard` | مصادقة + دور: head |
| GET | `/api/department-head/grade-report-requests` | `Api\DepartmentHeadController@getGradeReports` | مصادقة + دور: head |
| POST | `/api/department-head/grade-report-requests` | `Api\DepartmentHeadController@requestGradeReport` | مصادقة + دور: head |
| GET | `/api/department-head/grade-report-requests/{courseId}/entries` | `Api\DepartmentHeadController@getCourseGradeEntries` | مصادقة + دور: head |
| POST | `/api/department-head/grade-report-requests/{courseId}/remind-teacher` | `Api\DepartmentHeadController@remindTeacher` | مصادقة + دور: head |
| GET | `/api/department-head/leave-requests` | `Api\DepartmentHeadController@getLeaveRequests` | مصادقة + دور: head |
| PUT | `/api/department-head/leave-requests/{id}/respond` | `Api\DepartmentHeadController@respondLeaveRequest` | مصادقة + دور: head |
| GET | `/api/department-head/metadata` | `Api\DepartmentHeadController@getMetadata` | مصادقة + دور: head |
| GET | `/api/department-head/notifications` | `Api\DepartmentHeadController@getNotifications` | مصادقة + دور: head |
| PUT | `/api/department-head/notifications/read-all` | `Api\DepartmentHeadController@markAllNotificationsRead` | مصادقة + دور: head |
| POST | `/api/department-head/notifications/send` | `Api\DepartmentHeadController@sendNotification` | مصادقة + دور: head |
| PUT | `/api/department-head/notifications/{id}/read` | `Api\DepartmentHeadController@markNotificationRead` | مصادقة + دور: head |
| GET | `/api/department-head/parent-meetings` | `Api\ParentMeetingController@listMeetingRequests` | مصادقة + دور: head |
| PUT | `/api/department-head/parent-meetings/{id}/respond` | `Api\ParentMeetingController@respondToMeetingRequest` | مصادقة + دور: head |
| GET | `/api/department-head/parent-summons` | `Api\ParentMeetingController@listSummons` | مصادقة + دور: head |
| POST | `/api/department-head/parent-summons` | `Api\ParentMeetingController@sendSummon` | مصادقة + دور: head |
| GET | `/api/department-head/profile` | `Api\DepartmentHeadController@getProfile` | مصادقة + دور: head |
| GET | `/api/department-head/programs-schedule` | `Api\DepartmentHeadController@getProgramsSchedule` | مصادقة + دور: head |
| GET | `/api/department-head/report-requests` | `Api\DepartmentHeadController@getReportRequests` | مصادقة + دور: head |
| POST | `/api/department-head/report-requests` | `Api\DepartmentHeadController@createReportRequest` | مصادقة + دور: head |
| DELETE | `/api/department-head/report-requests/{id}` | `Api\DepartmentHeadController@deleteReportRequest` | مصادقة + دور: head |
| POST | `/api/department-head/report-requests/{id}/hod-notes` | `Api\DepartmentHeadController@updateHodNotes` | مصادقة + دور: head |
| POST | `/api/department-head/report-requests/{id}/send-to-parent` | `Api\DepartmentHeadController@sendReportToParent` | مصادقة + دور: head |
| GET | `/api/department-head/schedule` | `Api\DepartmentHeadController@getSchedule` | مصادقة + دور: head |
| POST | `/api/department-head/schedule` | `Api\DepartmentHeadController@createSchedule` | مصادقة + دور: head |
| PUT | `/api/department-head/schedule/{id}` | `Api\DepartmentHeadController@updateSchedule` | مصادقة + دور: head |
| GET | `/api/department-head/student-service-requests` | `Api\DepartmentHeadController@getStudentServiceRequests` | مصادقة + دور: head |
| PUT | `/api/department-head/student-service-requests/{id}/respond` | `Api\DepartmentHeadController@respondStudentServiceRequest` | مصادقة + دور: head |
| GET | `/api/department-head/teachers` | `Api\DepartmentHeadController@getTeachers` | مصادقة + دور: head |
| POST | `/api/department-head/users/parent` | `Api\DepartmentHeadController@createParent` | مصادقة + دور: head |
| GET | `/api/department-head/users/parents` | `Api\DepartmentHeadController@getParents` | مصادقة + دور: head |
| POST | `/api/department-head/users/student` | `Api\DepartmentHeadController@createStudent` | مصادقة + دور: head |
| GET | `/api/department-head/users/students` | `Api\DepartmentHeadController@getStudents` | مصادقة + دور: head |
| POST | `/api/department-head/users/trainer` | `Api\DepartmentHeadController@createTrainer` | مصادقة + دور: head |
| GET | `/api/department-head/users/trainers` | `Api\DepartmentHeadController@getTrainers` | مصادقة + دور: head |

## الإدارة (`/api/admin/*`)

| الطريقة | المسار | المعالج | الصلاحية |
|---|---|---|---|
| POST | `/api/admin/accounts/{id}/approve` | `Api\AdminController@approveAccount` | مصادقة + دور: admin |
| POST | `/api/admin/accounts/{id}/reject` | `Api\AdminController@rejectAccount` | مصادقة + دور: admin |
| POST | `/api/admin/announcements` | `Api\AdminController@createAnnouncement` | مصادقة + دور: admin |
| DELETE | `/api/admin/announcements/{id}` | `Api\AdminController@deleteAnnouncement` | مصادقة + دور: admin |
| POST | `/api/admin/announcements/{id}` | `Api\AdminController@updateAnnouncement` | مصادقة + دور: admin |
| GET | `/api/admin/assign-hod` | `Api\AdminController@getAssignHodData` | مصادقة + دور: admin |
| POST | `/api/admin/assign-hod` | `Api\AdminController@assignHodExisting` | مصادقة + دور: admin |
| POST | `/api/admin/assign-hod/new` | `Api\AdminController@assignHodNew` | مصادقة + دور: admin |
| POST | `/api/admin/broadcast` | `Api\AdminController@sendBroadcast` | مصادقة + دور: admin |
| GET | `/api/admin/courses` | `Api\AdminController@getCourses` | مصادقة + دور: admin |
| POST | `/api/admin/courses` | `Api\AdminController@createCourse` | مصادقة + دور: admin |
| DELETE | `/api/admin/courses/{id}` | `Api\AdminController@deleteCourse` | مصادقة + دور: admin |
| GET | `/api/admin/courses/{id}` | `Api\AdminController@getCourse` | مصادقة + دور: admin |
| PUT | `/api/admin/courses/{id}` | `Api\AdminController@updateCourse` | مصادقة + دور: admin |
| GET | `/api/admin/dashboard` | `Api\AdminController@dashboard` | مصادقة + دور: admin |
| GET | `/api/admin/departments` | `Api\AdminController@getDepartments` | مصادقة + دور: admin |
| POST | `/api/admin/departments` | `Api\AdminController@createDepartment` | مصادقة + دور: admin |
| POST | `/api/admin/departments/assign-programs` | `Api\AdminController@assignProgramsToDepartment` | مصادقة + دور: admin |
| DELETE | `/api/admin/departments/{id}` | `Api\AdminController@deleteDepartment` | مصادقة + دور: admin |
| PUT | `/api/admin/departments/{id}` | `Api\AdminController@updateDepartment` | مصادقة + دور: admin |
| GET | `/api/admin/parent-meetings` | `Api\ParentMeetingController@listMeetingRequests` | مصادقة + دور: admin |
| PUT | `/api/admin/parent-meetings/{id}/respond` | `Api\ParentMeetingController@respondToMeetingRequest` | مصادقة + دور: admin |
| GET | `/api/admin/parent-summons` | `Api\ParentMeetingController@listSummons` | مصادقة + دور: admin |
| POST | `/api/admin/parent-summons` | `Api\ParentMeetingController@sendSummon` | مصادقة + دور: admin |
| GET | `/api/admin/pending-accounts` | `Api\AdminController@getPendingAccounts` | مصادقة + دور: admin |
| GET | `/api/admin/reports/attendance` | `Api\AdminController@attendanceReport` | مصادقة + دور: admin |
| GET | `/api/admin/reports/export/{id}` | `Api\AdminController@exportReportById` | مصادقة + دور: admin |
| GET | `/api/admin/reports/grades` | `Api\AdminController@gradesReport` | مصادقة + دور: admin |
| GET | `/api/admin/reports/log` | `Api\AdminController@getReportsLog` | مصادقة + دور: admin |
| GET | `/api/admin/reports/students` | `Api\AdminController@studentsReport` | مصادقة + دور: admin |
| GET | `/api/admin/reports/view/{id}` | `Api\AdminController@viewReportById` | مصادقة + دور: admin |
| GET | `/api/admin/semesters` | `Api\AdminController@getSemesters` | مصادقة + دور: admin |
| POST | `/api/admin/semesters` | `Api\AdminController@createSemester` | مصادقة + دور: admin |
| GET | `/api/admin/semesters-subjects` | `Api\AdminController@getSemestersSubjects` | مصادقة + دور: admin |
| DELETE | `/api/admin/semesters/{id}` | `Api\AdminController@deleteSemester` | مصادقة + دور: admin |
| PUT | `/api/admin/semesters/{id}` | `Api\AdminController@updateSemester` | مصادقة + دور: admin |
| GET | `/api/admin/student-services` | `Api\AdminController@getStudentServices` | مصادقة + دور: admin |
| POST | `/api/admin/student-services/{id}/process` | `Api\AdminController@processStudentService` | مصادقة + دور: admin |
| GET | `/api/admin/users` | `Api\AdminController@getUsers` | مصادقة + دور: admin |
| POST | `/api/admin/users` | `Api\AdminController@createUser` | مصادقة + دور: admin |
| DELETE | `/api/admin/users/{id}` | `Api\AdminController@deleteUser` | مصادقة + دور: admin |
| PUT | `/api/admin/users/{id}` | `Api\AdminController@updateUser` | مصادقة + دور: admin |

## ولي الأمر (`/api/parent/*`)

| الطريقة | المسار | المعالج | الصلاحية |
|---|---|---|---|
| POST | `/api/parent/add-student` | `Api\ParentController@linkStudent` | مصادقة + دور: parent |
| DELETE | `/api/parent/affairs/notifications/{id}` | `NotificationController@deleteNotification` | مصادقة + دور: parent |
| GET | `/api/parent/announcements` | `Api\ParentController@getAnnouncements` | مصادقة + دور: parent |
| DELETE | `/api/parent/children/{id}` | `Api\ParentController@unlinkStudent` | مصادقة + دور: parent |
| GET | `/api/parent/children/{id}/academic-card` | `Api\ParentController@getChildAcademicCard` | مصادقة + دور: parent |
| GET | `/api/parent/children/{id}/academic-card/export-excel` | `Api\ParentController@exportChildAcademicCardExcel` | مصادقة + دور: parent |
| GET | `/api/parent/children/{id}/academic-card/export-pdf` | `Api\ParentController@exportChildAcademicCardPdf` | مصادقة + دور: parent |
| GET | `/api/parent/children/{id}/assignments` | `Api\ParentController@getChildAssignments` | مصادقة + دور: parent |
| GET | `/api/parent/children/{id}/attendance` | `Api\ParentController@getChildAttendance` | مصادقة + دور: parent |
| GET | `/api/parent/children/{id}/details` | `Api\ParentController@getChildDetails` | مصادقة + دور: parent |
| GET | `/api/parent/children/{id}/grades` | `Api\ParentController@getChildGrades` | مصادقة + دور: parent |
| GET | `/api/parent/children/{id}/schedule` | `Api\ParentController@getChildSchedule` | مصادقة + دور: parent |
| POST | `/api/parent/children/{id}/unlink` | `Api\ParentController@unlinkStudent` | مصادقة + دور: parent |
| GET | `/api/parent/children/{parent_id?}` | `Api\ParentController@getChildren` | مصادقة + دور: parent |
| GET | `/api/parent/dashboard` | `Api\ParentController@dashboard` | مصادقة + دور: parent |
| DELETE | `/api/parent/hod/notifications/{id}` | `NotificationController@deleteNotification` | مصادقة + دور: parent |
| GET | `/api/parent/leave-requests` | `StudentParentController@getLeaveRequests` | مصادقة + دور: parent |
| POST | `/api/parent/leave-requests/submit` | `StudentParentController@submitParentLeaveRequest` | مصادقة + دور: parent |
| POST | `/api/parent/leave-requests/{id}/respond` | `StudentParentController@respondLeaveRequest` | مصادقة + دور: parent |
| GET | `/api/parent/meeting-requests` | `Api\ParentController@getMyMeetingRequests` | مصادقة + دور: parent |
| GET | `/api/parent/notifications` | `NotificationController@getNotifications` | مصادقة + دور: parent |
| DELETE | `/api/parent/notifications/chat/{senderId}` | `NotificationController@deleteChatNotifications` | مصادقة + دور: parent |
| PUT | `/api/parent/notifications/read-all` | `NotificationController@markAllAsRead` | مصادقة + دور: parent |
| PUT | `/api/parent/notifications/read-by-type` | `NotificationController@markByTypeAndRelatedId` | مصادقة + دور: parent |
| POST | `/api/parent/notifications/toggle-mute` | `NotificationController@toggleMute` | مصادقة + دور: parent |
| DELETE | `/api/parent/notifications/{id}` | `NotificationController@deleteNotification` | مصادقة + دور: parent |
| PUT | `/api/parent/notifications/{id}/read` | `NotificationController@markAsRead` | مصادقة + دور: parent |
| GET | `/api/parent/performance/{studentId}` | `StudentParentController@getFullPerformance` | مصادقة + دور: parent |
| POST | `/api/parent/permissions/{requestId}/respond` | `StudentParentController@respondPermission` | مصادقة + دور: parent |
| GET | `/api/parent/reports/history` | `Api\ParentController@getReportsHistory` | مصادقة + دور: parent |
| POST | `/api/parent/request-meeting` | `Api\ParentController@requestMeeting` | مصادقة + دور: parent |
| POST | `/api/parent/request-report` | `Api\ParentController@requestReport` | مصادقة + دور: parent |
| DELETE | `/api/parent/student/notifications/{id}` | `NotificationController@deleteNotification` | مصادقة + دور: parent |
| GET | `/api/parent/student/{studentId}/assignments` | `StudentParentController@getAssignments` | مصادقة + دور: parent |
| GET | `/api/parent/student/{studentId}/permissions` | `StudentParentController@getPermissions` | مصادقة + دور: parent |
| GET | `/api/parent/summons` | `Api\ParentController@getMySummons` | مصادقة + دور: parent |
| POST | `/api/parent/summons/{id}/respond` | `Api\ParentMeetingController@respondToSummon` | مصادقة + دور: parent |
| DELETE | `/api/parent/teacher/notifications/{id}` | `NotificationController@deleteNotification` | مصادقة + دور: parent |

## الشؤون (`/api/affairs/*`)

| الطريقة | المسار | المعالج | الصلاحية |
|---|---|---|---|
| GET | `/api/affairs/academic-card` | `Api\AffairsController@getStudentAcademicCardForAffairs` | مصادقة + دور: affairs,admin |
| GET | `/api/affairs/academic-card/export-pdf` | `Api\AffairsController@exportStudentAcademicCardPdf` | مصادقة + دور: affairs,admin |
| GET | `/api/affairs/accounts` | `Api\AffairsController@listAccounts` | مصادقة + دور: affairs,admin |
| POST | `/api/affairs/accounts/create` | `Api\AffairsController@createAccount` | مصادقة + دور: affairs,admin |
| DELETE | `/api/affairs/accounts/{id}` | `Api\AffairsController@deleteAccount` | مصادقة + دور: affairs,admin |
| POST | `/api/affairs/accounts/{id}/toggle` | `Api\AffairsController@toggleAccountStatus` | مصادقة + دور: affairs,admin |
| POST | `/api/affairs/accounts/{id}/update` | `Api\AffairsController@updateAccount` | مصادقة + دور: affairs,admin |
| POST | `/api/affairs/accounts/{userId}/approve` | `Api\AffairsController@approveAccount` | مصادقة + دور: affairs,admin |
| POST | `/api/affairs/accounts/{userId}/reject` | `Api\AffairsController@rejectAccount` | مصادقة + دور: affairs,admin |
| GET | `/api/affairs/announcements` | `Api\AffairsController@listAnnouncements` | مصادقة + دور: affairs,admin |
| POST | `/api/affairs/announcements` | `Api\AffairsController@createAnnouncement` | مصادقة + دور: affairs,admin |
| DELETE | `/api/affairs/announcements/{id}` | `Api\AffairsController@deleteAnnouncement` | مصادقة + دور: affairs,admin |
| POST | `/api/affairs/announcements/{id}` | `Api\AffairsController@updateAnnouncement` | مصادقة + دور: affairs,admin |
| GET | `/api/affairs/appointments/meetings` | `Api\AffairsController@getMeetingRequests` | مصادقة + دور: affairs,admin |
| POST | `/api/affairs/appointments/meetings/{id}/respond` | `Api\AffairsController@respondToMeetingRequest` | مصادقة + دور: affairs,admin |
| PUT | `/api/affairs/appointments/meetings/{id}/respond` | `Api\AffairsController@respondToMeetingRequest` | مصادقة + دور: affairs,admin |
| GET | `/api/affairs/appointments/metadata` | `Api\AffairsController@getAppointmentsMetadata` | مصادقة + دور: affairs,admin |
| GET | `/api/affairs/appointments/summons` | `Api\AffairsController@getSummons` | مصادقة + دور: affairs,admin |
| POST | `/api/affairs/appointments/summons` | `Api\AffairsController@storeSummon` | مصادقة + دور: affairs,admin |
| POST | `/api/affairs/appointments/summons/{id}/issue` | `Api\AffairsController@issueParentSummon` | مصادقة + دور: affairs,admin |
| POST | `/api/affairs/broadcasting/auth` | `Closure` | مصادقة + دور: affairs,admin |
| GET | `/api/affairs/calendar` | `Api\AffairsController@listCalendarEvents` | مصادقة + دور: affairs,admin |
| POST | `/api/affairs/calendar/events` | `Api\AffairsController@storeCalendarEvent` | مصادقة + دور: affairs,admin |
| POST | `/api/affairs/calendar/events/delete/{id}` | `Api\AffairsController@deleteCalendarEvent` | مصادقة + دور: affairs,admin |
| POST | `/api/affairs/calendar/events/update/{id}` | `Api\AffairsController@updateCalendarEvent` | مصادقة + دور: affairs,admin |
| GET | `/api/affairs/course-weights/data` | `Api\AffairsController@getCourseWeightsData` | مصادقة + دور: affairs,admin |
| GET | `/api/affairs/course-weights/export-cohort-pdf` | `Api\AffairsController@exportCourseWeightsCohortPdf` | مصادقة + دور: affairs,admin |
| GET | `/api/affairs/course-weights/export-student-pdf` | `Api\AffairsController@exportCourseWeightsStudentPdf` | مصادقة + دور: affairs,admin |
| POST | `/api/affairs/course-weights/student-decision` | `Api\AffairsController@updateStudentAcademicDecision` | مصادقة + دور: affairs,admin |
| GET | `/api/affairs/dashboard` | `Api\AffairsController@getDashboardStats` | مصادقة + دور: affairs,admin |
| GET | `/api/affairs/leaves` | `Api\AffairsController@listLeaves` | مصادقة + دور: affairs,admin |
| POST | `/api/affairs/leaves/{id}/status` | `Api\AffairsController@updateLeaveStatus` | مصادقة + دور: affairs,admin |
| GET | `/api/affairs/messages` | `Api\AffairsController@listMessages` | مصادقة + دور: affairs,admin |
| POST | `/api/affairs/messages` | `Api\AffairsController@sendMessage` | مصادقة + دور: affairs,admin |
| GET | `/api/affairs/messages/conversation/{userId}` | `Api\AffairsController@getConversation` | مصادقة + دور: affairs,admin |
| GET | `/api/affairs/metadata` | `Api\AffairsController@getMetadata` | مصادقة + دور: affairs,admin |
| GET | `/api/affairs/notifications` | `Api\AffairsController@listNotifications` | مصادقة + دور: affairs,admin |
| POST | `/api/affairs/notifications/read-all` | `Api\AffairsController@markAllNotificationsRead` | مصادقة + دور: affairs,admin |
| POST | `/api/affairs/notifications/{id}/read` | `Api\AffairsController@markNotificationRead` | مصادقة + دور: affairs,admin |
| POST | `/api/affairs/parents-students/create-parent` | `Api\AffairsController@createParentAndLink` | مصادقة + دور: affairs,admin |
| POST | `/api/affairs/parents-students/link` | `Api\AffairsController@linkStudentToParent` | مصادقة + دور: affairs,admin |
| GET | `/api/affairs/parents-students/parents` | `Api\AffairsController@listParentsWithStudents` | مصادقة + دور: affairs,admin |
| POST | `/api/affairs/parents-students/unlink` | `Api\AffairsController@unlinkStudentFromParent` | مصادقة + دور: affairs,admin |
| GET | `/api/affairs/parents-students/unlinked` | `Api\AffairsController@listUnlinkedStudents` | مصادقة + دور: affairs,admin |
| GET | `/api/affairs/pending-accounts` | `Api\AffairsController@pendingAccounts` | مصادقة + دور: affairs,admin |
| GET | `/api/affairs/photo-change-requests` | `Api\AffairsController@listPhotoChangeRequests` | مصادقة + دور: affairs,admin |
| POST | `/api/affairs/photo-change-requests/{id}/approve` | `Api\AffairsController@approvePhotoChange` | مصادقة + دور: affairs,admin |
| POST | `/api/affairs/photo-change-requests/{id}/reject` | `Api\AffairsController@rejectPhotoChange` | مصادقة + دور: affairs,admin |
| GET | `/api/affairs/profile` | `Api\AffairsController@getProfile` | مصادقة + دور: affairs,admin |
| POST | `/api/affairs/profile/password` | `Api\AffairsController@updatePassword` | مصادقة + دور: affairs,admin |
| POST | `/api/affairs/profile/update` | `Api\AffairsController@updateProfile` | مصادقة + دور: affairs,admin |
| GET | `/api/affairs/semesters` | `Api\AffairsController@listSemesters` | مصادقة + دور: affairs,admin |
| POST | `/api/affairs/semesters/{id}/activate` | `Api\AffairsController@activateSemester` | مصادقة + دور: affairs,admin |
| GET | `/api/affairs/student-service-requests` | `Api\AffairsController@listStudentRequests` | مصادقة + دور: affairs,admin |
| POST | `/api/affairs/student-service-requests/{id}/process` | `Api\AffairsController@processStudentRequest` | مصادقة + دور: affairs,admin |
| GET | `/api/affairs/student-services` | `Api\AffairsController@getStudentServices` | مصادقة + دور: affairs,admin |
| POST | `/api/affairs/student-services/{id}/process` | `Api\AffairsController@processStudentService` | مصادقة + دور: affairs,admin |
| GET | `/api/affairs/students-academic` | `Api\AffairsController@getFilteredStudentsForAcademicCard` | مصادقة + دور: affairs,admin |
| POST | `/api/affairs/students/promote` | `Api\AffairsController@promoteStudentsYear` | مصادقة + دور: affairs,admin |
| POST | `/api/affairs/students/{id}/reset-device` | `Api\AffairsController@resetDevice` | مصادقة + دور: affairs,admin |
| GET | `/api/affairs/university-ids` | `Api\AffairsController@listUniversityIds` | مصادقة + دور: affairs,admin |
| POST | `/api/affairs/university-ids` | `Api\AffairsController@addUniversityId` | مصادقة + دور: affairs,admin |
| GET | `/api/affairs/university-ids/next-id` | `Api\AffairsController@nextUniversityId` | مصادقة + دور: affairs,admin |
| DELETE | `/api/affairs/university-ids/{id}` | `Api\AffairsController@deleteUniversityId` | مصادقة + دور: affairs,admin |
| POST | `/api/affairs/university-ids/{id}/update` | `Api\AffairsController@updateUniversityId` | مصادقة + دور: affairs,admin |