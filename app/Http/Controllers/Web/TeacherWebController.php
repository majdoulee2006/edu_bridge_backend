<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use App\Models\Teacher;
use App\Models\User;

class TeacherWebController extends Controller
{
    // ────────────────────────────────────────────────────────────
    //  AUTH
    // ────────────────────────────────────────────────────────────

    public function showLoginForm()
    {
        if (Auth::check()) {
            $teacher = Teacher::where('user_id', Auth::user()->user_id)->first();
            if ($teacher) return redirect('/teacher/dashboard');
        }
        return view('teacher.login');
    }

    public function login(Request $request)
    {
        $request->validate([
            'login'    => 'required|string',
            'password' => 'required|min:6',
        ], [
            'login.required'    => 'البريد الإلكتروني أو رقم الهاتف مطلوب.',
            'password.required' => 'كلمة المرور مطلوبة.',
        ]);

        // يدعم: email أو phone أو username
        $input = $request->login;
        if (filter_var($input, FILTER_VALIDATE_EMAIL)) {
            $loginField = 'email';
        } elseif (preg_match('/^\+?[0-9]{7,15}$/', $input)) {
            $loginField = 'phone';
        } else {
            $loginField = 'username';
        }

        if (Auth::attempt([$loginField => $input, 'password' => $request->password])) {
            $teacher = Teacher::where('user_id', Auth::user()->getKey())->first();
            if (!$teacher) {
                Auth::logout();
                return back()->withErrors(['login' => 'هذا الحساب ليس حساب معلم.']);
            }
            $request->session()->regenerate();
            return redirect('/teacher/dashboard');
        }

        return back()->withInput()->withErrors(['login' => 'البريد الإلكتروني/رقم الهاتف أو كلمة المرور غير صحيحة.']);
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect('/teacher/login');
    }

    // ────────────────────────────────────────────────────────────
    //  HELPER: get current teacher record
    // ────────────────────────────────────────────────────────────
    private function getTeacher()
    {
        return Teacher::where('user_id', Auth::user()->user_id)->first();
    }

    // ────────────────────────────────────────────────────────────
    //  DASHBOARD
    // ────────────────────────────────────────────────────────────

    public function dashboard()
    {
        $teacher = $this->getTeacher();

        // المواد التي يدرسها المعلم
        $courses = DB::table('course_teachers')
            ->join('courses', 'course_teachers.course_id', '=', 'courses.course_id')
            ->where('course_teachers.teacher_id', $teacher->teacher_id)
            ->select('courses.*')
            ->get();

        $courseIds = $courses->pluck('course_id');

        // عدد الواجبات النشطة
        $recentAssignments = DB::table('assignments')
            ->join('courses', 'assignments.course_id', '=', 'courses.course_id')
            ->whereIn('assignments.course_id', $courseIds)
            ->select(
                'assignments.*',
                'courses.title as course_title',
                DB::raw('(SELECT COUNT(*) FROM assignment_submissions WHERE assignment_submissions.assignment_id = assignments.assignment_id) as submissions_count'),
                DB::raw('(SELECT COUNT(*) FROM assignment_submissions WHERE assignment_submissions.assignment_id = assignments.assignment_id AND grade IS NOT NULL) as graded_count')
            )
            ->orderByDesc('assignments.created_at')
            ->get();

        // حصص اليوم (للكارت فقط - العدد)
        $today = now()->locale('en')->dayName;
        $todayCount = DB::table('schedules')
            ->where('teacher_id', $teacher->teacher_id)
            ->where('day', $today)
            ->count();

        // الإعلانات من رئيس القسم (العامة + المتعلقة بمواد المعلم)
        $announcements = DB::table('announcements')
            ->where(function($q) use ($courseIds) {
                $q->where('type', 'general')
                  ->orWhereIn('course_id', $courseIds);
            })
            ->orderByDesc('created_at')
            ->limit(5)
            ->get();

        return view('teacher.dashboard', compact(
            'teacher', 'courses',
            'recentAssignments', 'todayCount',
            'announcements'
        ));
    }

    // ────────────────────────────────────────────────────────────
    //  SCHEDULE
    // ────────────────────────────────────────────────────────────

    public function schedule()
    {
        $teacher = $this->getTeacher();

        $schedules = DB::table('schedules')
            ->join('courses', 'schedules.course_id', '=', 'courses.course_id')
            ->where('schedules.teacher_id', $teacher->teacher_id)
            ->select('schedules.*', 'courses.title as course_title')
            ->orderByRaw("FIELD(schedules.day, 'Sunday','Monday','Tuesday','Wednesday','Thursday')")
            ->orderBy('schedules.start_time')
            ->get();

        // الامتحانات الخاصة بمواد المعلم
        $courseIds = DB::table('course_teachers')
            ->where('teacher_id', $teacher->teacher_id)
            ->pluck('course_id');

        $exams = DB::table('exams')
            ->join('courses', 'exams.course_id', '=', 'courses.course_id')
            ->whereIn('exams.course_id', $courseIds)
            ->select('exams.*', 'courses.title as course_title')
            ->orderBy('exams.exam_date')
            ->get();

        return view('teacher.schedule', compact('schedules', 'exams'));
    }

    // ────────────────────────────────────────────────────────────
    //  ATTENDANCE
    // ────────────────────────────────────────────────────────────

    public function attendance()
    {
        $teacher = $this->getTeacher();

        $courses = DB::table('course_teachers')
            ->join('courses', 'course_teachers.course_id', '=', 'courses.course_id')
            ->where('course_teachers.teacher_id', $teacher->teacher_id)
            ->select('courses.course_id', 'courses.title', 'courses.level')
            ->get();

        // جلسات الحضور الأخيرة (عبر lessons)
        $recentSessions = DB::table('attendance_sessions')
            ->join('lessons', 'attendance_sessions.lesson_id', '=', 'lessons.lesson_id')
            ->join('courses', 'lessons.course_id', '=', 'courses.course_id')
            ->where('lessons.teacher_id', $teacher->teacher_id)
            ->select('attendance_sessions.*', 'courses.title as course_title', 'lessons.title as lesson_title')
            ->orderByDesc('attendance_sessions.created_at')
            ->limit(10)
            ->get();

        return view('teacher.attendance', compact('courses', 'recentSessions'));
    }

    public function storeAttendanceSession(Request $request)
    {
        $request->validate([
            'course_id' => 'required|exists:courses,course_id',
            'room'      => 'nullable|string|max:100',
        ]);

        $teacher = $this->getTeacher();

        // نجلب أو ننشئ lesson مؤقت لهذه الجلسة
        $lesson = DB::table('lessons')
            ->where('course_id', $request->course_id)
            ->where('teacher_id', $teacher->teacher_id)
            ->first();

        if (!$lesson) {
            $lessonId = DB::table('lessons')->insertGetId([
                'course_id'   => $request->course_id,
                'teacher_id'  => $teacher->teacher_id,
                'title'       => 'جلسة حضور - ' . now()->format('Y-m-d'),
                'created_at'  => now(),
                'updated_at'  => now(),
            ]);
        } else {
            $lessonId = $lesson->lesson_id;
        }

        DB::table('attendance_sessions')->insert([
            'lesson_id'  => $lessonId,
            'qr_token'   => bin2hex(random_bytes(16)),
            'expires_at' => now()->addMinutes(10), // صالحة لمدة 10 دقائق
            'is_active'  => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return redirect()->back()->with('success', 'تم بدء جلسة الحضور بنجاح لمدة 10 دقائق!');
    }

    public function exportAttendance($sessionId)
    {
        $session = DB::table('attendance_sessions')
            ->join('lessons', 'attendance_sessions.lesson_id', '=', 'lessons.lesson_id')
            ->join('courses', 'lessons.course_id', '=', 'courses.course_id')
            ->where('attendance_sessions.id', $sessionId)
            ->select('attendance_sessions.*', 'courses.course_id', 'courses.title as course_title')
            ->first();

        if (!$session) abort(404);

        $students = DB::table('enrollments')
            ->join('students', 'enrollments.student_id', '=', 'students.student_id')
            ->join('users', 'students.user_id', '=', 'users.user_id')
            ->where('enrollments.course_id', $session->course_id)
            ->select('students.student_id', 'users.full_name', 'students.level')
            ->get();

        $attendances = DB::table('attendance')
            ->where('lesson_id', $session->lesson_id)
            ->pluck('status', 'student_id');

        $csvData = "
        <html xmlns:o=\"urn:schemas-microsoft-com:office:office\" xmlns:x=\"urn:schemas-microsoft-com:office:excel\" xmlns=\"http://www.w3.org/TR/REC-html40\">
        <head>
        <meta http-equiv=\"Content-Type\" content=\"text/html; charset=UTF-8\">
        <style>
            table { direction: rtl; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; border-collapse: collapse; }
            th { background-color: #f2f2f2; font-weight: bold; border: 1px solid #ddd; padding: 8px; }
            td { border: 1px solid #ddd; padding: 8px; text-align: right; }
        </style>
        </head>
        <body>
            <table>
                <tr>
                    <th>اسم الطالب</th>
                    <th>القسم/السنة</th>
                    <th>حالة الحضور</th>
                    <th>التاريخ</th>
                </tr>";

        $date = \Carbon\Carbon::parse($session->created_at)->format('Y-m-d');

        foreach ($students as $student) {
            $statusRaw = $attendances->get($student->student_id);
            $statusText = ($statusRaw === 'present') ? 'حاضر' : 'غائب';
            $color = ($statusRaw === 'present') ? '#166534' : '#b91c1c';
            
            $csvData .= "
                <tr>
                    <td>{$student->full_name}</td>
                    <td>{$student->level}</td>
                    <td style=\"color: {$color}; font-weight: bold;\">{$statusText}</td>
                    <td>{$date}</td>
                </tr>";
        }

        $csvData .= "
            </table>
        </body>
        </html>";

        $fileName = "attendance_{$session->course_id}_{$date}.xls";
        
        return response("\xEF\xBB\xBF" . $csvData)
            ->header('Content-Type', 'application/vnd.ms-excel; charset=utf-8')
            ->header('Content-Disposition', "attachment; filename=\"$fileName\"");
    }

    public function getAbsentees($sessionId)
    {
        $session = DB::table('attendance_sessions')
            ->join('lessons', 'attendance_sessions.lesson_id', '=', 'lessons.lesson_id')
            ->where('attendance_sessions.id', $sessionId)
            ->select('attendance_sessions.*', 'lessons.course_id')
            ->first();

        if (!$session) return response()->json([]);

        $students = DB::table('enrollments')
            ->join('students', 'enrollments.student_id', '=', 'students.student_id')
            ->join('users', 'students.user_id', '=', 'users.user_id')
            ->where('enrollments.course_id', $session->course_id)
            ->select('students.student_id', 'users.full_name', 'students.level')
            ->get();

        $attendances = DB::table('attendance')
            ->where('lesson_id', $session->lesson_id)
            ->where('status', 'present')
            ->pluck('student_id')->toArray();

        $absentees = [];
        foreach ($students as $student) {
            if (!in_array($student->student_id, $attendances)) {
                $absentees[] = $student;
            }
        }

        return response()->json($absentees);
    }

    // ────────────────────────────────────────────────────────────
    //  ASSIGNMENTS
    // ────────────────────────────────────────────────────────────

    public function assignments()
    {
        $teacher = $this->getTeacher();

        $courseIds = DB::table('course_teachers')
            ->where('teacher_id', $teacher->teacher_id)
            ->pluck('course_id');

        $assignments = DB::table('assignments')
            ->join('courses', 'assignments.course_id', '=', 'courses.course_id')
            ->whereIn('assignments.course_id', $courseIds)
            ->select(
                'assignments.*',
                'courses.title as course_title',
                DB::raw('(SELECT COUNT(*) FROM assignment_submissions WHERE assignment_submissions.assignment_id = assignments.assignment_id) as submissions_count'),
                DB::raw('(SELECT COUNT(*) FROM assignment_submissions WHERE assignment_submissions.assignment_id = assignments.assignment_id AND assignment_submissions.grade IS NOT NULL) as graded_count')
            )
            ->orderByDesc('assignments.created_at')
            ->get();

        $courses = DB::table('course_teachers')
            ->join('courses', 'course_teachers.course_id', '=', 'courses.course_id')
            ->where('course_teachers.teacher_id', $teacher->teacher_id)
            ->select('courses.course_id', 'courses.title')
            ->get();

        return view('teacher.assignments', compact('assignments', 'courses'));
    }

    public function storeAssignment(Request $request)
    {
        $request->validate([
            'course_id'   => 'required|exists:courses,course_id',
            'title'       => 'required|string|max:255',
            'description' => 'required|string',
            'due_date'    => 'required|date',
            'max_points'  => 'required|integer|min:1',
            'attachment'  => 'nullable|file|max:51200|mimes:jpg,jpeg,png,gif,webp,mp4,mov,avi,mkv,webm,pdf,doc,docx,ppt,pptx,xls,xlsx,txt,zip',
        ], [
            'attachment.max'   => 'حجم الملف يجب ألا يتجاوز 50 ميجابايت.',
            'attachment.mimes' => 'نوع الملف غير مدعوم.',
        ]);

        $filePath = null;
        $fileName = null;
        $fileType = null;

        if ($request->hasFile('attachment') && $request->file('attachment')->isValid()) {
            $file     = $request->file('attachment');
            $fileName = $file->getClientOriginalName();
            $mime     = $file->getMimeType();

            if (str_starts_with($mime, 'image/')) {
                $fileType = 'image';
                $folder   = 'assignments/images';
            } elseif (str_starts_with($mime, 'video/')) {
                $fileType = 'video';
                $folder   = 'assignments/videos';
            } else {
                $fileType = 'document';
                $folder   = 'assignments/documents';
            }

            $filePath = $file->store($folder, 'public');
        }

        $teacher = $this->getTeacher();

        $assignmentId = DB::table('assignments')->insertGetId([
            'course_id'       => $request->course_id,
            'teacher_id'      => $teacher->teacher_id,
            'title'           => $request->title,
            'description'     => $request->description,
            'due_date'        => $request->due_date,
            'max_points'      => $request->max_points,
            'file_path'       => $filePath,
            'attachment_path' => $filePath,
            'file_name'       => $fileName,
            'file_type'       => $fileType,
            'created_at'      => now(),
            'updated_at'      => now(),
        ]);

        // ── إشعار الطلاب المسجلين في المادة ─────────────────────────
        $course       = DB::table('courses')->where('course_id', $request->course_id)->first();
        $teacherUser  = DB::table('users')->where('user_id', $teacher->user_id)->first();
        $courseName   = $course->title ?? $course->name ?? 'المادة';
        $fcmTitle     = 'واجب جديد — ' . $courseName;
        $fcmBody      = 'رفع المعلم ' . ($teacherUser->full_name ?? '') . ' واجباً جديداً: ' . $request->title;

        $studentUserIds = DB::table('enrollments')
            ->join('students', 'enrollments.student_id', '=', 'students.student_id')
            ->where('enrollments.course_id', $request->course_id)
            ->where('enrollments.status', 'active')
            ->pluck('students.user_id');

        $now = now();
        $notifRows = $studentUserIds->map(fn($uid) => [
            'user_id'    => $uid,
            'sender_id'  => $teacher->user_id,
            'title'      => $fcmTitle,
            'message'    => $fcmBody,
            'type'       => 'assignment',
            'category'   => 'academic',
            'related_id' => $assignmentId,
            'is_read'    => 0,
            'created_at' => $now,
            'updated_at' => $now,
        ])->all();

        if (!empty($notifRows)) {
            DB::table('notifications')->insert($notifRows);
            foreach ($studentUserIds as $uid) {
                \App\Services\FcmService::sendToUser($uid, $fcmTitle, $fcmBody, [
                    'type' => 'assignment', 'related_id' => (string) $assignmentId,
                ]);
            }
        }

        return redirect()->back()->with('success', 'تم إضافة الواجب بنجاح!');
    }

    public function deleteAssignment($id)
    {
        // حذف الملف المرفق إن وُجد
        $assignment = DB::table('assignments')->where('assignment_id', $id)->first();
        if ($assignment && $assignment->file_path) {
            \Illuminate\Support\Facades\Storage::disk('public')->delete($assignment->file_path);
        }
        DB::table('assignments')->where('assignment_id', $id)->delete();
        return redirect()->back()->with('success', 'تم حذف الواجب.');
    }

    // عرض تفاصيل التسليمات لواجب معين
    public function assignmentSubmissions($assignmentId)
    {
        $teacher  = $this->getTeacher();
        $assignment = DB::table('assignments')
            ->join('courses', 'assignments.course_id', '=', 'courses.course_id')
            ->where('assignments.assignment_id', $assignmentId)
            ->select('assignments.*', 'courses.title as course_title')
            ->first();

        $submissions = DB::table('assignment_submissions')
            ->join('students', 'assignment_submissions.student_id', '=', 'students.student_id')
            ->join('users', 'students.user_id', '=', 'users.user_id')
            ->where('assignment_submissions.assignment_id', $assignmentId)
            ->select('assignment_submissions.*', 'users.full_name as student_name')
            ->orderByDesc('assignment_submissions.submitted_at')
            ->get();

        return view('teacher.submissions', compact('assignment', 'submissions'));
    }

    public function gradeSubmission(Request $request, $submissionId)
    {
        $request->validate([
            'grade'    => 'required|numeric|min:0',
            'feedback' => 'nullable|string',
        ]);

        DB::table('assignment_submissions')
            ->where('submission_id', $submissionId)
            ->update([
                'grade'      => $request->grade,
                'feedback'   => $request->feedback,
                'updated_at' => now(),
            ]);

        // ── إشعار الطالب بتصحيح واجبه ───────────────────────────────
        $submission = DB::table('assignment_submissions')
            ->join('students', 'assignment_submissions.student_id', '=', 'students.student_id')
            ->join('assignments', 'assignment_submissions.assignment_id', '=', 'assignments.assignment_id')
            ->where('assignment_submissions.submission_id', $submissionId)
            ->select('students.user_id', 'assignments.title as assignment_title', 'assignments.max_points')
            ->first();

        if ($submission) {
            $notifTitle = 'تم تصحيح واجبك';
            $notifMsg   = 'صحّح المعلم واجب "' . $submission->assignment_title . '" — علامتك: ' . $request->grade . '/' . ($submission->max_points ?? 100);
            DB::table('notifications')->insert([
                'user_id'    => $submission->user_id,
                'title'      => $notifTitle,
                'message'    => $notifMsg,
                'type'       => 'assignment',
                'category'   => 'academic',
                'is_read'    => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            \App\Services\FcmService::sendToUser($submission->user_id, $notifTitle, $notifMsg, ['type' => 'assignment']);
        }

        return redirect()->back()->with('success', 'تم حفظ التصحيح بنجاح!');
    }

    // ────────────────────────────────────────────────────────────
    //  LECTURES
    // ────────────────────────────────────────────────────────────

    public function lectures()
    {
        $teacher = $this->getTeacher();

        $courseIds = DB::table('course_teachers')
            ->where('teacher_id', $teacher->teacher_id)
            ->pluck('course_id');

        $lectures = DB::table('lessons')
            ->join('courses', 'lessons.course_id', '=', 'courses.course_id')
            ->whereIn('lessons.course_id', $courseIds)
            ->select('lessons.*', 'courses.title as course_title')
            ->orderByDesc('lessons.created_at')
            ->get();

        $courses = DB::table('course_teachers')
            ->join('courses', 'course_teachers.course_id', '=', 'courses.course_id')
            ->where('course_teachers.teacher_id', $teacher->teacher_id)
            ->select('courses.course_id', 'courses.title')
            ->get();

        return view('teacher.lectures', compact('lectures', 'courses'));
    }

    public function storeLecture(Request $request)
    {
        $request->validate([
            'course_id'   => 'required|exists:courses,course_id',
            'title'       => 'required|string|max:255',
            'description' => 'nullable|string',
            'attachment'  => 'nullable|file|max:51200|mimes:jpg,jpeg,png,gif,webp,mp4,mov,avi,mkv,webm,pdf,doc,docx,ppt,pptx,xls,xlsx,txt,zip',
        ], [
            'attachment.max'   => 'حجم الملف يجب ألا يتجاوز 50 ميجابايت.',
            'attachment.mimes' => 'نوع الملف غير مدعوم.',
        ]);

        $teacher = $this->getTeacher();

        $filePath = null;
        $fileName = null;
        $fileType = null;

        if ($request->hasFile('attachment') && $request->file('attachment')->isValid()) {
            $file     = $request->file('attachment');
            $fileName = $file->getClientOriginalName();
            $mime     = $file->getMimeType();

            if (str_starts_with($mime, 'image/')) {
                $fileType = 'image';
                $folder   = 'lectures/images';
            } elseif (str_starts_with($mime, 'video/')) {
                $fileType = 'video';
                $folder   = 'lectures/videos';
            } else {
                $fileType = 'document';
                $folder   = 'lectures/documents';
            }

            $filePath = $file->store($folder, 'public');
        }

        DB::table('lessons')->insert([
            'course_id'   => $request->course_id,
            'teacher_id'  => $teacher->teacher_id,
            'title'       => $request->title,
            'description' => $request->description,
            'file_path'   => $filePath,
            'file_name'   => $fileName,
            'file_type'   => $fileType,
            'created_at'  => now(),
            'updated_at'  => now(),
        ]);

        return redirect()->back()->with('success', 'تمت إضافة المحاضرة بنجاح!');
    }

    public function updateLecture(Request $request, $id)
    {
        $request->validate([
            'course_id'   => 'required|exists:courses,course_id',
            'title'       => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);

        DB::table('lessons')->where('lesson_id', $id)->update([
            'course_id'   => $request->course_id,
            'title'       => $request->title,
            'description' => $request->description,
            'updated_at'  => now(),
        ]);

        return redirect()->back()->with('success', 'تم تحديث المحاضرة بنجاح!');
    }

    public function deleteLecture($id)
    {
        // حذف الملف المرفق إن وُجد
        $lesson = DB::table('lessons')->where('lesson_id', $id)->first();
        if ($lesson && $lesson->file_path) {
            \Illuminate\Support\Facades\Storage::disk('public')->delete($lesson->file_path);
        }
        DB::table('lessons')->where('lesson_id', $id)->delete();
        return redirect()->back()->with('success', 'تم حذف المحاضرة.');
    }

    // ────────────────────────────────────────────────────────────
    //  ANNOUNCEMENTS
    // ────────────────────────────────────────────────────────────

    public function createAnnouncement()
    {
        $courses = DB::table('course_teachers')
            ->join('courses', 'course_teachers.course_id', '=', 'courses.course_id')
            ->where('course_teachers.teacher_id', $this->getTeacher()->teacher_id)
            ->select('courses.course_id', 'courses.title')
            ->get();
        return view('teacher.announcements_create', compact('courses'));
    }

    public function storeAnnouncement(Request $request)
    {
        $request->validate([
            'title'   => 'required|string|max:255',
            'content' => 'required|string',
            'image'   => 'nullable|image|max:5120',
        ]);

        $imagePath = null;
        if ($request->hasFile('image')) {
            $imagePath = $request->file('image')->store('announcements', 'public');
        }

        \App\Models\Announcement::create([
            'user_id' => Auth::id(),
            'title'   => $request->title,
            'content' => $request->content,
            'image'   => $imagePath,
            'type'    => 'general',
        ]);

        return redirect()->route('teacher.dashboard')->with('success', 'تم نشر الإعلان بنجاح!');
    }

    public function editAnnouncement($id)
    {
        $announcement = \App\Models\Announcement::where('announcement_id', $id)
            ->where('user_id', Auth::id())->firstOrFail();
        return view('teacher.announcements_edit', compact('announcement'));
    }

    public function updateAnnouncement(Request $request, $id)
    {
        $announcement = \App\Models\Announcement::where('announcement_id', $id)
            ->where('user_id', Auth::id())->firstOrFail();

        $request->validate(['title' => 'required|string|max:255', 'content' => 'required|string']);

        $updates = ['title' => $request->title, 'content' => $request->content, 'updated_at' => now()];

        if ($request->hasFile('image')) {
            if ($announcement->image) \Illuminate\Support\Facades\Storage::disk('public')->delete($announcement->image);
            $updates['image'] = $request->file('image')->store('announcements', 'public');
        }

        $announcement->update($updates);
        return redirect()->route('teacher.dashboard')->with('success', 'تم تحديث الإعلان!');
    }

    public function deleteAnnouncement($id)
    {
        $announcement = \App\Models\Announcement::where('announcement_id', $id)
            ->where('user_id', Auth::id())->firstOrFail();
        if ($announcement->image) \Illuminate\Support\Facades\Storage::disk('public')->delete($announcement->image);
        $announcement->delete();
        return redirect()->route('teacher.dashboard')->with('success', 'تم حذف الإعلان.');
    }

    // ────────────────────────────────────────────────────────────
    //  MESSAGES
    // ────────────────────────────────────────────────────────────

    public function messages()
    {
        $currentUserId = Auth::id();

        $conversations = \App\Models\Message::with(['sender', 'receiver'])
            ->where('sender_id', $currentUserId)
            ->orWhere('receiver_id', $currentUserId)
            ->latest()
            ->get()
            ->map(function ($msg) use ($currentUserId) {
                return ($msg->sender_id == $currentUserId) ? $msg->receiver_id : $msg->sender_id;
            })
            ->unique()
            ->values();

        $contacts = User::whereIn('user_id', $conversations)->get();

        // قائمة كل المستخدمين للرسالة الجديدة
        $allUsers = User::where('user_id', '!=', $currentUserId)->get();

        return view('teacher.messages', compact('contacts', 'allUsers'));
    }

    public function getConversation($userId)
    {
        $currentUserId = Auth::id();
        $messages = \App\Models\Message::with(['sender', 'receiver'])
            ->where(function ($q) use ($currentUserId, $userId) {
                $q->where('sender_id', $currentUserId)->where('receiver_id', $userId);
            })
            ->orWhere(function ($q) use ($currentUserId, $userId) {
                $q->where('sender_id', $userId)->where('receiver_id', $currentUserId);
            })
            ->orderBy('created_at', 'asc')
            ->get();

        // تحديد الرسائل كمقروءة
        \App\Models\Message::where('sender_id', $userId)
            ->where('receiver_id', $currentUserId)
            ->where('is_read', false)
            ->update(['is_read' => true]);

        return response()->json($messages);
    }

    public function sendMessage(Request $request)
    {
        $request->validate([
            'receiver_id' => 'required|exists:users,user_id',
            'message'     => 'required|string|max:2000',
        ]);

        $message = \App\Models\Message::create([
            'sender_id'   => Auth::user()->user_id,
            'receiver_id' => $request->receiver_id,
            'message'     => $request->message,
            'is_read'     => false,
        ]);

        // إضافة إشعار للمستلم
        DB::table('notifications')->insert([
            'user_id' => $request->receiver_id,
            'title'   => 'رسالة جديدة',
            'message' => 'لقد تلقيت رسالة جديدة من ' . Auth::user()->full_name,
            'type'    => 'message',
            'is_read' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        if ($request->expectsJson()) {
            return response()->json(['success' => true, 'message' => clone $message]);
        }

        return redirect()->back()->with('success', 'تم إرسال الرسالة بنجاح!');
    }

    // ────────────────────────────────────────────────────────────
    //  NOTIFICATIONS
    // ────────────────────────────────────────────────────────────

    public function notifications()
    {
        $notifications = DB::table('notifications')
            ->where('user_id', Auth::user()->user_id)
            ->orderByDesc('created_at')
            ->get();

        return view('teacher.notifications', compact('notifications'));
    }

    // ────────────────────────────────────────────────────────────
    //  PROFILE
    // ────────────────────────────────────────────────────────────

    public function profile()
    {
        $teacher = $this->getTeacher();
        $user    = Auth::user();

        $courses = DB::table('course_teachers')
            ->join('courses', 'course_teachers.course_id', '=', 'courses.course_id')
            ->where('course_teachers.teacher_id', $teacher->teacher_id)
            ->select('courses.course_id', 'courses.title')
            ->get();

        $courseIds = $courses->pluck('course_id');
        $totalStudents = DB::table('enrollments')
            ->whereIn('course_id', $courseIds)
            ->distinct('student_id')
            ->count('student_id');

        return view('teacher.profile', compact('teacher', 'user', 'courses', 'totalStudents'));
    }

    public function updateProfile(Request $request)
    {
        $request->validate([
            'full_name' => 'required|string|max:255',
            'phone'     => 'nullable|string|max:20',
        ]);

        DB::table('users')
            ->where('user_id', Auth::user()->user_id)
            ->update([
                'full_name'  => $request->full_name,
                'phone'      => $request->phone,
                'updated_at' => now(),
            ]);

        return redirect()->back()->with('success', 'تم تحديث الملف الشخصي بنجاح!');
    }

    public function sendOTP(Request $request)
    {
        $request->validate([
            'full_name' => 'nullable|string|max:255',
            'phone'     => 'nullable|string|max:20',
            'current_password' => 'nullable|string',
            'new_password'     => 'nullable|string|min:6',
        ]);

        // If changing password, verify current password first
        if ($request->has('current_password') && $request->current_password) {
            $user = Auth::user();
            if (!Hash::check($request->current_password, $user->password)) {
                return response()->json([
                    'success' => false,
                    'message' => 'كلمة المرور الحالية غير صحيحة.'
                ]);
            }
        }

        $otp = rand(1000, 9999);
        
        session([
            'teacher_profile_otp' => $otp,
            'teacher_pending_profile_data' => $request->only(['full_name', 'phone', 'new_password'])
        ]);

        return response()->json([
            'success' => true,
            'otp' => $otp,
            'message' => 'تم إرسال رمز التحقق بنجاح!'
        ]);
    }

    public function verifyOTP(Request $request)
    {
        $request->validate([
            'otp' => 'required|numeric'
        ]);

        if (session('teacher_profile_otp') == $request->otp) {
            $user = Auth::user();
            $data = session('teacher_pending_profile_data');

            $updates = ['updated_at' => now()];

            if (isset($data['full_name']) && $data['full_name']) {
                $updates['full_name'] = $data['full_name'];
            }

            if (isset($data['phone'])) {
                $updates['phone'] = $data['phone'];
            }

            if (isset($data['new_password']) && $data['new_password']) {
                $updates['password'] = Hash::make($data['new_password']);
            }

            DB::table('users')
                ->where('user_id', $user->user_id)
                ->update($updates);

            session()->forget(['teacher_profile_otp', 'teacher_pending_profile_data']);

            return response()->json([
                'success' => true,
                'message' => 'تم تحديث البيانات بنجاح!'
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'رمز التحقق غير صحيح، يرجى المحاولة مرة أخرى.'
        ]);
    }

    public function updatePassword(Request $request)
    {
        $request->validate([
            'current_password' => 'required',
            'new_password'     => 'required|min:6|confirmed',
        ]);

        $user = Auth::user();

        if (!Hash::check($request->current_password, $user->password)) {
            return back()->withErrors(['current_password' => 'كلمة المرور الحالية غير صحيحة.']);
        }

        DB::table('users')
            ->where('user_id', $user->user_id)
            ->update([
                'password'   => Hash::make($request->new_password),
                'updated_at' => now(),
            ]);

        return redirect()->back()->with('success', 'تم تغيير كلمة المرور بنجاح!');
    }
    // ────────────────────────────────────────────────────────────
    //  ADVISOR TOOLS (أدوات المربي)
    // ────────────────────────────────────────────────────────────

    public function advisorTools()
    {
        $teacher = $this->getTeacher();
        
        $advisorCourses = DB::table('course_teachers')
            ->join('courses', 'course_teachers.course_id', '=', 'courses.course_id')
            ->where('course_teachers.teacher_id', $teacher->teacher_id)
            ->where('course_teachers.role', 'advisor')
            ->select('courses.course_id', 'courses.title')
            ->get();
            
        $students = [];
        if ($advisorCourses->count() > 0) {
            $courseIds = $advisorCourses->pluck('course_id');
            $students = DB::table('enrollments')
                ->join('students', 'enrollments.student_id', '=', 'students.student_id')
                ->join('users', 'students.user_id', '=', 'users.user_id')
                ->whereIn('enrollments.course_id', $courseIds)
                ->where('enrollments.status', 'active')
                ->select('students.student_id', 'users.full_name', 'users.university_id')
                ->distinct()
                ->get();
        }

        return view('teacher.advisor', compact('advisorCourses', 'students'));
    }

    public function storeAdvisorReport(Request $request)
    {
        $request->validate([
            'student_id' => 'required|exists:students,student_id',
            'behavioral_notes' => 'required|string|max:2000'
        ]);

        $teacher = $this->getTeacher();

        // 1. Create a simulated report request to tie it to
        $requestId = DB::table('report_requests')->insertGetId([
            'student_id' => $request->student_id,
            'teacher_id' => $teacher->teacher_id,
            'report_type' => 'behavioral',
            'status' => 'completed',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // 2. Insert performance report
        DB::table('performance_reports')->insert([
            'report_request_id' => $requestId,
            'student_id'        => $request->student_id,
            'report_type'       => 'behavioral',
            'recommendations'   => $request->behavioral_notes,
            'generated_at'      => now(),
            'created_at'        => now(),
            'updated_at'        => now(),
        ]);

        // 3. Notify parents
        $studentName = DB::table('students')
            ->join('users', 'students.user_id', '=', 'users.user_id')
            ->where('students.student_id', $request->student_id)
            ->value('users.full_name') ?? 'الطالب';

        $parentRows = DB::table('parent_students')
            ->join('parents', 'parent_students.parent_id', '=', 'parents.parent_id')
            ->where('parent_students.student_id', $request->student_id)
            ->pluck('parents.user_id');

        $notifTitle = 'تقرير سلوكي جديد';
        $notifBody  = 'تم إضافة تقرير سلوكي جديد عن ابنك/ابنتك ' . $studentName . ' من قبل مربي الدورة.';

        foreach ($parentRows as $parentUserId) {
            DB::table('notifications')->insert([
                'user_id'    => $parentUserId,
                'sender_id'  => auth()->id(),
                'title'      => $notifTitle,
                'message'    => $notifBody,
                'type'       => 'report',
                'related_id' => $requestId,
                'category'   => 'academic',
                'is_read'    => false,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            \App\Services\FcmService::sendToUser($parentUserId, $notifTitle, $notifBody, [
                'type'       => 'behavioral_report',
                'student_id' => (string) $request->student_id,
            ]);
        }

        return redirect()->back()->with('success', 'تم رفع التقرير السلوكي وإشعار ولي الأمر بنجاح!');
    }

    public function storeAdvisorAttendance(Request $request)
    {
        $teacher = $this->getTeacher();
        
        $request->validate([
            'course_id' => 'required|exists:courses,course_id',
            'attendance' => 'required|array',
            'date' => 'required|date'
        ]);

        $courseId = $request->input('course_id');
        $date = $request->input('date');
        $attendances = $request->input('attendance');

        $isAdvisor = DB::table('course_teachers')
            ->where('teacher_id', $teacher->teacher_id)
            ->where('course_id', $courseId)
            ->where('role', 'advisor')
            ->exists();

        if (!$isAdvisor) {
            return back()->with('error', 'ليس لديك صلاحية مربي لهذه الدورة.');
        }

        $lesson = DB::table('lessons')
            ->where('course_id', $courseId)
            ->where('title', 'الحضور اليومي للقاعة')
            ->first();

        if (!$lesson) {
            $lessonId = DB::table('lessons')->insertGetId([
                'course_id' => $courseId,
                'title' => 'الحضور اليومي للقاعة',
                'description' => 'سجل خاص بتفقد المربي',
                'content' => 'يومي',
                'teacher_id' => $teacher->teacher_id,
                'department_id' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } else {
            $lessonId = $lesson->lesson_id;
        }

        $sessionId = DB::table('attendance_sessions')->insertGetId([
            'lesson_id' => $lessonId,
            'qr_token' => 'DAILY_' . $courseId . '_' . uniqid(),
            'expires_at' => now()->addHours(24),
            'is_active' => false,
            'created_at' => $date . ' 00:00:00',
            'updated_at' => now(),
        ]);

        $attendanceRecords = [];
        foreach ($attendances as $studentId => $status) {
            $attendanceRecords[] = [
                'session_id' => $sessionId,
                'student_id' => $studentId,
                'status' => $status,
                'recorded_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        if (!empty($attendanceRecords)) {
            DB::table('attendance')->insert($attendanceRecords);
        }

        return back()->with('success', 'تم حفظ الحضور اليومي للقاعة بنجاح.');
    }

    // ===== التقارير =====

    public function reports()
    {
        $teacher = DB::table('teachers')
            ->where('user_id', auth()->id())
            ->first();

        if (!$teacher) {
            return view('teacher.reports', ['requests' => collect()]);
        }

        $requests = DB::table('report_requests')
            ->where('report_requests.teacher_id', $teacher->teacher_id)
            ->join('students', 'report_requests.student_id', '=', 'students.student_id')
            ->join('users as su', 'students.user_id', '=', 'su.user_id')
            ->leftJoin('performance_reports', 'performance_reports.report_request_id', '=', 'report_requests.id')
            ->select(
                'report_requests.*',
                'su.full_name as student_name',
                'students.student_code',
                'performance_reports.attendance_rate',
                'performance_reports.average_grade',
                'performance_reports.recommendations as submitted_notes',
                'performance_reports.generated_at'
            )
            ->orderByDesc('report_requests.created_at')
            ->get();

        return view('teacher.reports', compact('requests'));
    }

    public function submitReport(Request $request, $id)
    {
        $request->validate([
            'behavioral_notes' => 'nullable|string|max:2000',
        ]);

        $reportRequest = DB::table('report_requests')->where('id', $id)->firstOrFail();
        $studentId     = $reportRequest->student_id;
        $isBehavioral  = $reportRequest->report_type === 'behavioral';

        // ===== حساب البيانات الأكاديمية الحقيقية =====
        $attendanceRate = null;
        $avgGrade       = null;
        $recommendations = $request->behavioral_notes ?? '';

        if (!$isBehavioral) {
            // نسبة الحضور الفعلية
            $totalSessions   = DB::table('attendance')->where('student_id', $studentId)->count();
            $presentSessions = DB::table('attendance')->where('student_id', $studentId)->where('status', 'present')->count();
            $attendanceRate  = $totalSessions > 0 ? round(($presentSessions / $totalSessions) * 100, 1) : 0;

            // المعدل من الامتحانات
            $avgGrade = DB::table('grades')->where('student_id', $studentId)->avg('score');

            // إذا ما في درجات امتحان، نأخذ من الواجبات
            if ($avgGrade === null) {
                $avgGrade = DB::table('assignment_submissions')
                    ->where('student_id', $studentId)
                    ->whereNotNull('grade')
                    ->avg('grade');
            }

            $avgGrade = $avgGrade !== null ? round($avgGrade, 1) : null;

            // توليد التوصية تلقائياً
            $attendancePart = '';
            if ($attendanceRate >= 90)      $attendancePart = 'نسبة الحضور ممتازة (' . $attendanceRate . '%)';
            elseif ($attendanceRate >= 75)  $attendancePart = 'نسبة الحضور جيدة (' . $attendanceRate . '%)';
            elseif ($attendanceRate > 0)    $attendancePart = 'نسبة الحضور تحتاج تحسيناً (' . $attendanceRate . '%)';

            $gradePart = '';
            if ($avgGrade !== null) {
                if ($avgGrade >= 85)      $gradePart = 'مستوى أكاديمي ممتاز (المعدل: ' . $avgGrade . ')';
                elseif ($avgGrade >= 70)  $gradePart = 'مستوى أكاديمي جيد (المعدل: ' . $avgGrade . ')';
                elseif ($avgGrade >= 60)  $gradePart = 'مستوى أكاديمي مقبول (المعدل: ' . $avgGrade . ')';
                else                      $gradePart = 'يحتاج دعماً أكاديمياً (المعدل: ' . $avgGrade . ')';
            }

            $parts = array_filter([$attendancePart, $gradePart]);
            $recommendations = $parts ? implode(' — ', $parts) : 'لا توجد بيانات كافية لتوليد توصية.';
        }

        // حفظ التقرير
        DB::table('performance_reports')->insert([
            'report_request_id' => $id,
            'student_id'        => $studentId,
            'report_type'       => $reportRequest->report_type,
            'attendance_rate'   => $attendanceRate,
            'average_grade'     => $avgGrade,
            'recommendations'   => $recommendations,
            'generated_at'      => now(),
            'created_at'        => now(),
            'updated_at'        => now(),
        ]);

        DB::table('report_requests')->where('id', $id)->update([
            'status'     => 'completed',
            'updated_at' => now(),
        ]);

        // ===== إشعار الأهل (FCM + داخلي) =====
        $studentName = DB::table('students')
            ->join('users', 'students.user_id', '=', 'users.user_id')
            ->where('students.student_id', $studentId)
            ->value('users.full_name') ?? 'الطالب';

        $notifTitle = $isBehavioral ? 'تقرير سلوكي جديد' : 'تقرير أكاديمي جديد';
        $notifBody  = 'تم إرسال تقرير ' . ($isBehavioral ? 'سلوكي' : 'أكاديمي') . ' عن ابنك/ابنتك ' . $studentName;

        $parentRows = DB::table('parent_students')
            ->join('parents', 'parent_students.parent_id', '=', 'parents.parent_id')
            ->where('parent_students.student_id', $studentId)
            ->pluck('parents.user_id');

        foreach ($parentRows as $parentUserId) {
            // إشعار داخلي
            DB::table('notifications')->insert([
                'user_id'    => $parentUserId,
                'sender_id'  => auth()->id(),
                'title'      => $notifTitle,
                'message'    => $notifBody,
                'type'       => 'report',
                'related_id' => $id,
                'category'   => 'academic',
                'is_read'    => false,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // FCM
            \App\Services\FcmService::sendToUser($parentUserId, $notifTitle, $notifBody, [
                'type'       => $reportRequest->report_type . '_report',
                'student_id' => (string) $studentId,
            ]);
        }
        }

        // إشعار رئيس القسم
        $headId = \Illuminate\Support\Facades\DB::table('users')->where('role', 'head')->value('user_id');
        if ($headId) {
            \Illuminate\Support\Facades\DB::table('notifications')->insert([
                'user_id'    => $headId,
                'sender_id'  => auth()->id(),
                'title'      => $notifTitle,
                'message'    => 'تم رفع تقرير عن الطالب ' . $studentName . ' بواسطة ' . auth()->user()->full_name,
                'type'       => 'report',
                'related_id' => $id,
                'category'   => 'academic',
                'is_read'    => false,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $msg = $isBehavioral
            ? 'تم إرسال التقرير السلوكي وإشعار ولي الأمر ورئيس القسم بنجاح!'
            : 'تم توليد التقرير الأكاديمي وإشعار ولي الأمر ورئيس القسم بنجاح!';

        return redirect()->back()->with('success', $msg);
    }
}
