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

        $loginField = filter_var($request->login, FILTER_VALIDATE_EMAIL) ? 'email' : 'phone';

        if (Auth::attempt([$loginField => $request->login, 'password' => $request->password])) {
            $teacher = Teacher::where('user_id', Auth::user()->user_id)->first();
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

        DB::table('assignments')->insert([
            'course_id'   => $request->course_id,
            'title'       => $request->title,
            'description' => $request->description,
            'due_date'    => $request->due_date,
            'max_points'  => $request->max_points,
            'file_path'   => $filePath,
            'file_name'   => $fileName,
            'file_type'   => $fileType,
            'created_at'  => now(),
            'updated_at'  => now(),
        ]);

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
    //  MESSAGES
    // ────────────────────────────────────────────────────────────

    public function messages()
    {
        $messages = DB::table('messages')
            ->join('users', 'messages.sender_id', '=', 'users.user_id')
            ->where('messages.receiver_id', Auth::user()->user_id)
            ->select('messages.*', 'users.full_name as sender_name')
            ->orderByDesc('messages.created_at')
            ->get();

        $sent = DB::table('messages')
            ->join('users', 'messages.receiver_id', '=', 'users.user_id')
            ->where('messages.sender_id', Auth::user()->user_id)
            ->select('messages.*', 'users.full_name as receiver_name')
            ->orderByDesc('messages.created_at')
            ->get();

        // قائمة المستخدمين لإرسال رسائل
        $users = DB::table('users')
            ->where('user_id', '!=', Auth::user()->user_id)
            ->select('user_id', 'full_name', 'email')
            ->orderBy('full_name')
            ->get();

        return view('teacher.messages', compact('messages', 'sent', 'users'));
    }

    public function sendMessage(Request $request)
    {
        $request->validate([
            'receiver_id' => 'required|exists:users,user_id',
            'content'     => 'required|string',
        ]);

        DB::table('messages')->insert([
            'sender_id'   => Auth::user()->user_id,
            'receiver_id' => $request->receiver_id,
            'content'     => $request->content,
            'created_at'  => now(),
            'updated_at'  => now(),
        ]);

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
            ->select('courses.title')
            ->get();

        return view('teacher.profile', compact('teacher', 'user', 'courses'));
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
}
