<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Cache;
use App\Models\User;
use App\Models\Notification;
use App\Models\Message;
use App\Models\AbsenceRequest;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\CalendarEvent;
use App\Models\Announcement;
use App\Services\TelegramService;

class AffairsWebController extends Controller
{
    use \App\Traits\HandlesMessagesTrait;
    use \App\Traits\NormalizesAccountCredentialsTrait;
    // ─────────────────────────── Auth ───────────────────────────
    public function showLoginForm()
    {
        if (Auth::check()) {
            return redirect()->route('affairs.dashboard');
        }
        return view('affairs.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email'    => 'required|email',
            'password' => 'required',
        ]);

        if (Auth::attempt($credentials)) {
            // تحقق أن المستخدم لديه دور موظف الشؤون
            if (Auth::user()->role_id !== 6) {
                \App\Models\UserActivity::log('محاولة دخول مرفوضة', 'حساب لا يملك صلاحية موظف الشؤون', Auth::user());
                Auth::logout();
                return back()->withErrors(['email' => 'هذا الحساب ليس حساب موظف شؤون.']);
            }

            // تحقق أن الحساب نشط
            if (Auth::user()->status !== 'active') {
                \App\Models\UserActivity::log('محاولة دخول مرفوضة', 'حساب موظف الشؤون موقوف', Auth::user());
                Auth::logout();
                return back()->withErrors(['email' => 'حسابك موقوف. يرجى التواصل مع الإدارة.']);
            }

            $request->session()->regenerate();

            // تحديث آخر تسجيل دخول
            Auth::user()->update(['last_login' => now()]);

            \App\Models\UserActivity::log('تسجيل دخول', 'تسجيل دخول ناجح إلى لوحة شؤون الطلاب');

            return redirect()->route('affairs.dashboard');
        }

        return back()->withErrors(['email' => 'البريد الإلكتروني أو كلمة المرور غير صحيحة.']);
    }

    public function logout(Request $request)
    {
        if (Auth::check()) {
            if ($request->has('is_inactivity_logout')) {
                \App\Models\UserActivity::log('خروج تلقائي (خمول)', 'تم تسجيل الخروج تلقائياً بعد 20 دقيقة من الخمول');
            } else {
                \App\Models\UserActivity::log('تسجيل خروج', 'قام موظف الشؤون بتسجيل الخروج يدوياً');
            }
        }
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('affairs.login');
    }

    // ─────────────────────────── Dashboard ───────────────────────────
    public function dashboard()
    {
        // جلب الإحصائيات من الـ MySQL View مع تخزين مؤقت (Cache) لمدة 60 ثانية لحماية قاعدة البيانات
        $stats = \Illuminate\Support\Facades\Cache::remember('affairs_dashboard_stats', 60, function () {
            return DB::table('affairs_dashboard_stats_view')->first();
        });

        $totalStudents = $stats->total_students ?? 0;
        $totalTeachers = $stats->total_teachers ?? 0;
        $totalStaff    = $stats->total_staff ?? 0;
        $pendingLeaves = $stats->pending_leaves ?? 0;
        $totalUsers    = $stats->total_users ?? 0;

        // آخر 5 طلبات إجازة
        $recentLeaves = DB::table('leave_requests')
            ->join('users', 'leave_requests.student_id', '=', 'users.user_id')
            ->select('leave_requests.*', 'users.full_name as student_name')
            ->orderBy('leave_requests.created_at', 'desc')
            ->take(5)
            ->get();

        // إعلانات الكاروسيل — جميع إعلانات المعهد والأقسام لموظف الشؤون
        $carouselAnnouncements = Announcement::with('user')
            ->latest()
            ->take(5)
            ->get();

        // منشورات الإدارة — جميع إعلانات المعهد والأقسام لموظف الشؤون
        $posts = Announcement::with('user')
            ->latest()
            ->take(6)
            ->get();

        // إشعارات المستخدم الحالي
        $recentNotifications = Notification::where('user_id', Auth::id())
            ->latest()
            ->take(5)
            ->get();

        $activeSemester = DB::table('semesters')->where('is_active', true)->first();
        $semestersList  = DB::table('semesters')->orderBy('semester_id')->get();

        return view('affairs.dashboard', compact(
            'totalStudents',
            'totalTeachers',
            'totalStaff',
            'pendingLeaves',
            'totalUsers',
            'recentLeaves',
            'carouselAnnouncements',
            'posts',
            'recentNotifications',
            'activeSemester',
            'semestersList'
        ));
    }

    // ─────────────────────────── تثقيلات المواد (Course Weights) ───────────────────────────
    public function courseWeights()
    {
        // 1. جلب الأقسام
        $departments = DB::table('departments')->select('department_id', 'name')->get();

        // 2. جلب الدورات (Programs)
        $programs = DB::table('programs')->select('id', 'name', 'department_id')->get();

        // 3. جلب المواد (Courses) مع ربطها بالدورات
        $courses = DB::table('courses')
            ->join('course_program', 'courses.course_id', '=', 'course_program.course_id')
            ->select('courses.course_id', 'courses.title', 'courses.weight', 'courses.year', 'course_program.program_id')
            ->get();

        // 4. جلب الطلاب وعلاماتهم بناءً على التسجيل (Enrollments)
        // نستخدم COALESCE لضمان أن القيمة 0 في حال عدم وجود علامات
        // ملاحظة: مادة زي c++ أو شبكات ممكن تكون مشتركة بين أكثر من دورة (اتصالات والكترون مثلاً)،
        // فلازم نربط grade_events بـ program_id الطالب كمان (وليس فقط course_id) حتى ما تختلط
        // علامات شعبة بشعبة تانية لنفس المادة، ونمرّر برنامج الطالب الحقيقي عشان تصفية القائمة بالواجهة.
        $courseStudents = DB::table('enrollments')
            ->join('students', 'enrollments.student_id', '=', 'students.student_id')
            ->join('users', 'students.user_id', '=', 'users.user_id')
            ->join('courses', 'enrollments.course_id', '=', 'courses.course_id')
            ->leftJoin('grade_events', function($join) {
                $join->on('courses.course_id', '=', 'grade_events.course_id')
                     ->on('students.program_id', '=', 'grade_events.program_id')
                     ->where('grade_events.type', '=', 'exam');
            })
            ->leftJoin('grade_entries', function($join) {
                $join->on('grade_events.id', '=', 'grade_entries.grade_event_id')
                     ->on('enrollments.student_id', '=', 'grade_entries.student_id');
            })
            ->where('enrollments.status', 'active')
            ->select(
                'enrollments.course_id',
                'enrollments.student_id',
                'students.program_id as student_program_id',
                'students.student_code',
                'users.full_name as student_name',
                'courses.weight',
                DB::raw('COALESCE(SUM(grade_entries.score), 0) as exam_score'),
                DB::raw('COALESCE(MAX(grade_events.max_score), 100) as exam_max_score')
            )
            ->groupBy('enrollments.course_id', 'enrollments.student_id', 'students.program_id', 'students.student_code', 'users.full_name', 'courses.weight')
            ->get();

        // تجهيز مصفوفات متداخلة لتسهيل عرضها في الـ JavaScript
        $data = [
            'departments' => $departments,
            'programs'    => $programs,
            'courses'     => $courses,
            'students'    => $courseStudents,
        ];

        return view('affairs.course_weights', compact('data'));
    }

    /**
     * تصدير نتائج طلاب مادة معينة ضمن دورة معينة (Excel أو PDF).
     */
    public function exportCourseWeightsCourse(Request $request)
    {
        $request->validate([
            'course_id'  => 'required|integer|exists:courses,course_id',
            'program_id' => 'required|integer|exists:programs,id',
            'status'     => 'nullable|in:pass,fail,all',
            'format'     => 'required|in:excel,pdf',
        ]);

        $courseId  = (int) $request->course_id;
        $programId = (int) $request->program_id;
        $status    = $request->input('status', 'all');

        $course  = DB::table('courses')->where('course_id', $courseId)->first();
        $program = DB::table('programs')->where('id', $programId)->first();

        $rows = DB::table('enrollments')
            ->join('students', 'enrollments.student_id', '=', 'students.student_id')
            ->join('users', 'students.user_id', '=', 'users.user_id')
            ->leftJoin('grade_events', function ($join) use ($courseId) {
                $join->on('students.program_id', '=', 'grade_events.program_id')
                     ->where('grade_events.course_id', '=', $courseId)
                     ->where('grade_events.type', '=', 'exam');
            })
            ->leftJoin('grade_entries', function ($join) {
                $join->on('grade_events.id', '=', 'grade_entries.grade_event_id')
                     ->on('enrollments.student_id', '=', 'grade_entries.student_id');
            })
            ->where('enrollments.status', 'active')
            ->where('enrollments.course_id', $courseId)
            ->where('students.program_id', $programId)
            ->select(
                'users.full_name as student_name',
                DB::raw('COALESCE(SUM(grade_entries.score), 0) as exam_score'),
                DB::raw('COALESCE(MAX(grade_events.max_score), 100) as exam_max_score')
            )
            ->groupBy('enrollments.student_id', 'users.full_name')
            ->get()
            ->map(function ($r) use ($course) {
                $percentage = $r->exam_max_score > 0 ? ($r->exam_score / $r->exam_max_score) * 100 : 0;
                return [
                    'name'       => $r->student_name,
                    'exam'       => number_format($r->exam_score, 2) . ' / ' . number_format($r->exam_max_score, 0),
                    'weight'     => $course->weight ?? 1,
                    'percentage' => round($percentage, 2),
                    'status'     => $percentage >= 50 ? 'ناجح' : 'راسب',
                ];
            });

        if ($status === 'pass') {
            $rows = $rows->where('status', 'ناجح')->values();
        } elseif ($status === 'fail') {
            $rows = $rows->where('status', 'راسب')->values();
        }

        $yearLabel = ($course->year ?? 1) == 1 ? 'سنة_أولى' : 'سنة_ثانية';
        $title     = 'نتائج مادة ' . ($course->title ?? '') . ' - ' . ($program->name ?? '');
        $columns   = ['اسم الطالب', 'علامة الامتحان', 'التثقيل', 'المعدل', 'الحالة'];
        $fileBase  = $this->sanitizeFileName('كشف_نتائج_' . ($course->title ?? 'مادة') . '_' . ($program->name ?? '') . '_' . $yearLabel);

        return $request->format === 'excel'
            ? $this->downloadExcelTable($title, $columns, $rows, $fileBase)
            : $this->downloadPdfTable($title, $columns, $rows, $fileBase);
    }

    /**
     * تصدير نتائج كل مواد طالب معين ضمن دورة وسنة معينة (Excel أو PDF)، مع المعدل العام.
     */
    public function exportCourseWeightsStudent(Request $request)
    {
        $request->validate([
            'student_id' => 'required|integer|exists:students,student_id',
            'program_id' => 'required|integer|exists:programs,id',
            'year'       => 'required|integer|in:1,2',
            'format'     => 'required|in:excel,pdf',
        ]);

        $studentId = (int) $request->student_id;
        $programId = (int) $request->program_id;
        $year      = (int) $request->year;

        $student = DB::table('students')
            ->join('users', 'students.user_id', '=', 'users.user_id')
            ->where('students.student_id', $studentId)
            ->select('users.full_name', 'students.student_code')
            ->first();
        $program = DB::table('programs')->where('id', $programId)->first();

        $rawRows = DB::table('enrollments')
            ->join('courses', 'enrollments.course_id', '=', 'courses.course_id')
            ->leftJoin('grade_events', function ($join) use ($programId) {
                $join->on('courses.course_id', '=', 'grade_events.course_id')
                     ->where('grade_events.program_id', '=', $programId)
                     ->where('grade_events.type', '=', 'exam');
            })
            ->leftJoin('grade_entries', function ($join) use ($studentId) {
                $join->on('grade_events.id', '=', 'grade_entries.grade_event_id')
                     ->where('grade_entries.student_id', '=', $studentId);
            })
            ->where('enrollments.status', 'active')
            ->where('enrollments.student_id', $studentId)
            ->where('courses.year', $year)
            ->select(
                'courses.title',
                'courses.weight',
                DB::raw('COALESCE(SUM(grade_entries.score), 0) as exam_score'),
                DB::raw('COALESCE(MAX(grade_events.max_score), 100) as exam_max_score')
            )
            ->groupBy('enrollments.course_id', 'courses.title', 'courses.weight')
            ->get();

        $sumWeighted = 0;
        $sumWeight = 0;

        $rows = $rawRows->map(function ($r) use (&$sumWeighted, &$sumWeight) {
            $percentage = $r->exam_max_score > 0 ? ($r->exam_score / $r->exam_max_score) * 100 : 0;
            $sumWeighted += ($percentage / 100) * $r->weight;
            $sumWeight += $r->weight;

            return [
                'name'       => $r->title,
                'exam'       => number_format($r->exam_score, 2) . ' / ' . number_format($r->exam_max_score, 0),
                'weight'     => $r->weight,
                'percentage' => round($percentage, 2),
                'status'     => $percentage >= 50 ? 'ناجح' : 'راسب',
            ];
        });

        $overallAverage = $sumWeight > 0 ? round(($sumWeighted / $sumWeight) * 100, 2) : 0;

        $yearLabel = $year == 1 ? 'سنة_أولى' : 'سنة_ثانية';
        $title     = 'نتائج الطالب ' . ($student->full_name ?? '') . ' - ' . ($program->name ?? '');
        $columns   = ['اسم المادة', 'علامة الامتحان', 'التثقيل', 'المعدل', 'الحالة'];
        $footer    = 'المعدل العام: ' . $overallAverage . '%';
        $fileBase  = $this->sanitizeFileName('كشف_علامات_' . ($student->full_name ?? 'طالب') . '_' . ($program->name ?? '') . '_' . $yearLabel);

        return $request->format === 'excel'
            ? $this->downloadExcelTable($title, $columns, $rows, $fileBase, $footer)
            : $this->downloadPdfTable($title, $columns, $rows, $fileBase, $footer);
    }

    /**
     * اسم ملف نظيف ورسمي: يستبدل الفراغات بـ "_" ويحذف الرموز غير المسموحة بأسماء الملفات.
     */
    private function sanitizeFileName(string $name): string
    {
        $name = trim($name);
        $name = preg_replace('/\s+/u', '_', $name);
        $name = preg_replace('/[\/\\\\:\*\?"<>\|]/u', '', $name);
        return $name;
    }

    /**
     * يحوّل ملف شعار (لو موجود فعلاً بـ public/images/logos) إلى data URI مضمّن بالـ HTML،
     * حتى يظهر بالـ PDF/Excel المُصدَّر بدون الاعتماد على تحميل عن بعد. يرجع سلسلة فارغة إن لم يوجد الملف.
     */
    private function logoImgTag(string $fileName, string $alt, string $style = 'max-height:60px;'): string
    {
        $path = public_path('images/logos/' . $fileName);
        if (!file_exists($path)) {
            return '';
        }

        $mime = match (strtolower(pathinfo($path, PATHINFO_EXTENSION))) {
            'jpg', 'jpeg' => 'image/jpeg',
            'svg' => 'image/svg+xml',
            default => 'image/png',
        };

        $base64 = base64_encode(file_get_contents($path));

        return '<img src="data:' . $mime . ';base64,' . $base64 . '" alt="' . htmlspecialchars($alt) . '" style="' . $style . '">';
    }

    /**
     * HTML موحّد (بنمط جدول Excel/طباعة RTL) يُستخدم لكل من تصدير Excel وPDF، بشكل رسمي أكاديمي
     * (ترويسة بثلاثة شعارات + عنوان كشف علامات + بيانات إصدار + مكان توقيع وختم).
     */
    private function buildResultsHtml(string $title, array $columns, $rows, ?string $footer = null): string
    {
        $headerCells = '';
        foreach ($columns as $col) {
            $headerCells .= '<th>' . htmlspecialchars($col) . '</th>';
        }

        $bodyRows = '';
        foreach ($rows as $row) {
            $rowClass = ($row['status'] ?? '') === 'راسب' ? 'fail' : 'pass';
            $bodyRows .= '<tr>'
                . '<td>' . htmlspecialchars($row['name']) . '</td>'
                . '<td>' . htmlspecialchars($row['exam']) . '</td>'
                . '<td>' . htmlspecialchars((string) $row['weight']) . '</td>'
                . '<td class="' . $rowClass . '">' . htmlspecialchars($row['percentage'] . '%') . '</td>'
                . '<td class="' . $rowClass . '">' . htmlspecialchars($row['status']) . '</td>'
                . '</tr>';
        }

        $footerHtml = $footer ? '<tr><td colspan="5" class="footer-row"><b>' . htmlspecialchars($footer) . '</b></td></tr>' : '';

        $activeSemester = DB::table('semesters')->where('is_active', 1)->value('name') ?? '-';

        $logoStyle     = 'max-height:52px; max-width:100px;';
        $edubridgeLogo = $this->logoImgTag('edubridge.png', 'EduBridge', $logoStyle);
        $unrwaLogo     = $this->logoImgTag('unrwa.png', 'UNRWA', $logoStyle);
        $dtcLogo       = $this->logoImgTag('dtc.png', 'Damascus Training Centre', $logoStyle);

        return '<html dir="rtl" xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns="http://www.w3.org/TR/REC-html40">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
    <!--[if gte mso 9]>
    <xml>
     <x:ExcelWorkbook>
      <x:ExcelWorksheets>
       <x:ExcelWorksheet>
        <x:Name>كشف العلامات</x:Name>
        <x:WorksheetOptions>
         <x:DisplayRightToLeft/>
        </x:WorksheetOptions>
       </x:ExcelWorksheet>
      </x:ExcelWorksheets>
     </x:ExcelWorkbook>
    </xml>
    <![endif]-->
    <style>
        /* DejaVu Sans أولاً لأن محرك PDF (Dompdf) يتعرف عليه كخط مضمّن يدعم الأحرف العربية؛
           برامج Excel/Word بتتجاهله وبتستخدم Segoe UI/Tahoma تلقائياً بما إنه غير مثبت عندها */
        body { font-family: "DejaVu Sans", "Segoe UI", Tahoma, Arial, sans-serif; direction: rtl; text-align: right; }
        .doc-frame { border: 3px double #0f172a; padding: 14px; }
        table { border-collapse: collapse; width: 100%; }
        th, td { border: 1px solid #94a3b8; padding: 10px; text-align: center; vertical-align: middle; font-size: 10pt; }
        th { background-color: #0f172a; color: #ffffff; font-weight: bold; font-size: 11pt; }
        .plain, .plain td, .plain th { border: none; padding: 4px; }
        .letterhead-name { font-size: 12pt; font-weight: bold; color: #0f172a; }
        .letterhead-sub { font-size: 9pt; color: #64748b; }
        .doc-title { font-size: 17pt; font-weight: bold; color: #ffffff; background-color: #0f172a; text-align: center; padding: 12px; }
        .doc-subtitle { font-size: 11pt; font-weight: normal; color: #e2e8f0; }
        .meta-row { text-align: center; font-size: 9pt; color: #475569; background-color: #f1f5f9; padding: 8px; }
        .pass { color: #15803d; font-weight: bold; background-color: #dcfce7; }
        .fail { color: #b91c1c; font-weight: bold; background-color: #fee2e2; }
        .footer-row { background-color: #fef9c3; text-align: center; font-size: 12pt; padding: 12px; }
    </style>
</head>
<body>
<div class="doc-frame">
    <table class="plain">
        <tr>
            <td class="plain" style="width: 33%; text-align: center; vertical-align: middle;">' . $unrwaLogo . '</td>
            <td class="plain" style="width: 34%; text-align: center; vertical-align: middle;">' . $edubridgeLogo . '</td>
            <td class="plain" style="width: 33%; text-align: center; vertical-align: middle;">' . $dtcLogo . '</td>
        </tr>
    </table>

    <div style="text-align: center; padding: 8px 0 4px;">
        <div class="letterhead-name">معهد دمشق المتوسط</div>
        <div class="letterhead-sub" style="margin-top: 2px;">نظام إدارة العملية التعليمية</div>
    </div>

    <div style="border-bottom: 2px solid #0f172a; margin-bottom: 10px;"></div>

    <table>
        <tr><td colspan="5" class="doc-title">كشف علامات رسمي<br><span class="doc-subtitle">' . htmlspecialchars($title) . '</span></td></tr>
        <tr><td colspan="5" class="meta-row">الفصل الدراسي: ' . htmlspecialchars($activeSemester) . ' &nbsp;|&nbsp; تاريخ الإصدار: ' . now()->format('Y-m-d H:i') . '</td></tr>
        <thead><tr>' . $headerCells . '</tr></thead>
        <tbody>' . $bodyRows . $footerHtml . '</tbody>
    </table>
</div>
</body>
</html>';
    }

    /**
     * HTML مخصّص لإكسل فقط: جدول مسطّح واحد بدون جداول متداخلة وبدون صور مضمّنة (data URI)،
     * لأن آلية استيراد إكسل لملفات HTML لا تدعم الصور المضمّنة، وتتشوّش أعمدة الجدول عند وجود
     * أكثر من <table> منفصل بنفس الصفحة. الشعارات هنا نص فقط بدل الصور.
     */
    private function buildExcelHtml(string $title, array $columns, $rows, ?string $footer = null): string
    {
        $colCount = count($columns);

        $headerCells = '';
        foreach ($columns as $col) {
            $headerCells .= '<th>' . htmlspecialchars($col) . '</th>';
        }

        $bodyRows = '';
        foreach ($rows as $row) {
            $rowClass = ($row['status'] ?? '') === 'راسب' ? 'fail' : 'pass';
            $bodyRows .= '<tr>'
                . '<td>' . htmlspecialchars($row['name']) . '</td>'
                . '<td>' . htmlspecialchars($row['exam']) . '</td>'
                . '<td>' . htmlspecialchars((string) $row['weight']) . '</td>'
                . '<td class="' . $rowClass . '">' . htmlspecialchars($row['percentage'] . '%') . '</td>'
                . '<td class="' . $rowClass . '">' . htmlspecialchars($row['status']) . '</td>'
                . '</tr>';
        }

        $footerHtml = $footer ? '<tr><td colspan="' . $colCount . '" class="footer-row"><b>' . htmlspecialchars($footer) . '</b></td></tr>' : '';

        $activeSemester = DB::table('semesters')->where('is_active', 1)->value('name') ?? '-';

        return '<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns="http://www.w3.org/TR/REC-html40">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
    <!--[if gte mso 9]>
    <xml>
     <x:ExcelWorkbook>
      <x:ExcelWorksheets>
       <x:ExcelWorksheet>
        <x:Name>كشف العلامات</x:Name>
        <x:WorksheetOptions>
         <x:DisplayRightToLeft/>
        </x:WorksheetOptions>
       </x:ExcelWorksheet>
      </x:ExcelWorksheets>
     </x:ExcelWorkbook>
    </xml>
    <![endif]-->
    <style>
        body { font-family: "Segoe UI", Tahoma, Arial, sans-serif; direction: rtl; text-align: right; }
        table { border-collapse: collapse; width: 100%; }
        th, td { border: 1px solid #94a3b8; padding: 10px; text-align: center; vertical-align: middle; font-size: 10pt; }
        th { background-color: #0f172a; color: #ffffff; font-weight: bold; font-size: 11pt; }
        .institution-row { font-size: 11pt; font-weight: bold; color: #0f172a; background-color: #f1f5f9; text-align: center; padding: 8px; }
        .header-title { font-size: 16pt; font-weight: bold; color: #ffffff; background-color: #0f172a; text-align: center; padding: 14px; }
        .subtitle-row { font-size: 11pt; color: #334155; text-align: center; padding: 8px; }
        .meta-row { font-size: 9pt; color: #475569; background-color: #f1f5f9; text-align: center; padding: 8px; }
        .pass { color: #15803d; font-weight: bold; background-color: #dcfce7; }
        .fail { color: #b91c1c; font-weight: bold; background-color: #fee2e2; }
        .footer-row { background-color: #fef9c3; text-align: center; font-size: 12pt; padding: 12px; }
    </style>
</head>
<body>
    <table>
        <tr><td colspan="' . $colCount . '" class="institution-row">معهد دمشق المتوسط</td></tr>
        <tr><td colspan="' . $colCount . '" class="header-title">كشف علامات رسمي</td></tr>
        <tr><td colspan="' . $colCount . '" class="subtitle-row">' . htmlspecialchars($title) . '</td></tr>
        <tr><td colspan="' . $colCount . '" class="meta-row">الفصل الدراسي: ' . htmlspecialchars($activeSemester) . '  |  تاريخ الإصدار: ' . now()->format('Y-m-d H:i') . '</td></tr>
        <thead><tr>' . $headerCells . '</tr></thead>
        <tbody>' . $bodyRows . $footerHtml . '</tbody>
    </table>
</body>
</html>';
    }

    private function downloadExcelTable(string $title, array $columns, $rows, string $fileBase, ?string $footer = null)
    {
        $html = $this->buildExcelHtml($title, $columns, $rows, $footer);
        $fileName = $fileBase . '_' . now()->format('Y-m-d') . '.xls';

        return response($html, 200, [
            'Content-Type' => 'application/vnd.ms-excel; charset=utf-8',
            'Content-Disposition' => 'attachment; filename="' . $fileName . '"',
        ]);
    }

    private function downloadPdfTable(string $title, array $columns, $rows, string $fileBase, ?string $footer = null)
    {
        $html = $this->buildResultsHtml($title, $columns, $rows, $footer);
        $fileName = $fileBase . '_' . now()->format('Y-m-d') . '.pdf';
        $pdfContent = null;

        if (class_exists('\Mpdf\Mpdf')) {
            try {
                $mpdf = new \Mpdf\Mpdf([
                    'mode' => 'utf-8',
                    'format' => 'A4',
                    'orientation' => 'P',
                    'autoScriptToLang' => true,
                    'autoLangToFont' => true,
                    'useSubsets' => false,
                ]);
                $mpdf->SetDirectionality('rtl');
                $mpdf->WriteHTML($html);
                $pdfContent = $mpdf->Output('', 'S');
            } catch (\Throwable $e) {
                Log::warning('mPDF error: ' . $e->getMessage());
            }
        }

        if (!$pdfContent && class_exists('\Barryvdh\DomPDF\Facade\Pdf')) {
            try {
                $pdfContent = \Barryvdh\DomPDF\Facade\Pdf::setOptions(['defaultFont' => 'DejaVu Sans', 'isRemoteEnabled' => false])
                    ->loadHTML($html)->setPaper('a4', 'portrait')->output();
            } catch (\Throwable $e) {
                Log::warning('DomPDF Facade error: ' . $e->getMessage());
            }
        }

        if (!$pdfContent && class_exists('\Dompdf\Dompdf')) {
            try {
                $options = new \Dompdf\Options();
                $options->set('defaultFont', 'DejaVu Sans');
                $dompdf = new \Dompdf\Dompdf($options);
                $dompdf->loadHtml($html);
                $dompdf->setPaper('A4', 'portrait');
                $dompdf->render();
                $pdfContent = $dompdf->output();
            } catch (\Throwable $e) {
                Log::warning('Dompdf direct error: ' . $e->getMessage());
            }
        }

        if (!$pdfContent) {
            return response($html, 200, ['Content-Type' => 'text/html; charset=utf-8']);
        }

        return response($pdfContent, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="' . $fileName . '"',
            'Content-Length' => strlen($pdfContent),
            'Cache-Control' => 'no-cache, no-store, must-revalidate',
            'Pragma' => 'no-cache',
            'Expires' => '0',
        ]);
    }

    public function storeSemesterWeb(Request $request)
    {
        $this->normalizeAccountCredentials($request);


        $request->validate([
            'name'       => 'required|string|max:255',
            'start_date' => 'nullable|date',
            'end_date'   => 'nullable|date',
            'is_active'  => 'nullable|boolean',
        ]);

        if ($request->boolean('is_active')) {
            DB::table('semesters')->update(['is_active' => false]);
        }

        DB::table('semesters')->insert([
            'name'       => $request->name,
            'start_date' => $request->start_date,
            'end_date'   => $request->end_date,
            'is_active'  => $request->boolean('is_active'),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return back()->with('success', 'تم إنشاء وتعيين الفصل الدراسي بنجاح.');
    }

    public function activateSemesterWeb(Request $request)
    {
        $semesterId = $request->input('semester_id');
        $startDate  = $request->input('start_date');
        $endDate    = $request->input('end_date');

        if ($semesterId === 'none') {
            DB::table('semesters')->update(['is_active' => false]);
            return back()->with('success', 'تم إيقاف تفعيل جميع الفصول الدراسية حالياً (لا يوجد فصل مفعل).');
        }

        $target = DB::table('semesters')->where('semester_id', $semesterId)->first();
        if (!$target) {
            return back()->with('error', 'الفصل الدراسي غير موجود.');
        }

        DB::table('semesters')->update(['is_active' => false]);

        $updates = ['is_active' => true, 'updated_at' => now()];
        if ($startDate) $updates['start_date'] = $startDate;
        if ($endDate)   $updates['end_date']   = $endDate;

        DB::table('semesters')->where('semester_id', $semesterId)->update($updates);

        return back()->with('success', 'تم تفعيل ' . $target->name . ' وتحديث تواريخه بنجاح!');
    }

    public function promoteStudentsWeb(Request $request)
    {
        $query = Student::query();

        if ($request->filled('student_ids')) {
            $studentIds = is_array($request->student_ids) ? $request->student_ids : explode(',', $request->student_ids);
            $query->whereIn('student_id', $studentIds);
        } elseif ($request->filled('student_id')) {
            $query->where('student_id', $request->student_id);
        } else {
            $query->where(function($q) {
                $q->whereIn('level', ['السنة الأولى', 'أولى', '1'])->orWhereNull('level')->orWhere('level', '');
            });
        }

        $students = $query->get();
        $targetLevel = $request->input('target_level', 'السنة الثانية');
        $count = 0;

        foreach ($students as $st) {
            $st->update(['level' => $targetLevel, 'updated_at' => now()]);
            DB::table('users')->where('user_id', $st->user_id)->update(['academic_year' => $targetLevel]);
            Student::autoEnrollCourses($st->student_id);

            // إرسال إشعار للطالب بالترفيع
            Notification::create([
                'user_id'   => $st->user_id,
                'title'     => 'مبروك! تم الترفيع الأكاديمي 🎓',
                'message'   => "قام موظف الشؤون بترفيعك بنجاح إلى ({$targetLevel}) وتسجيل جميع المواد المقررة لك.",
                'type'      => 'academic',
                'category'  => 'academic',
                'is_read'   => 0,
            ]);
            \App\Services\FcmService::sendToUser($st->user_id, 'مبروك! تم الترفيع الأكاديمي 🎓', "قام موظف الشؤون بترفيعك بنجاح إلى ({$targetLevel}) وتسجيل جميع المواد المقررة لك.", ['type' => 'academic']);

            $count++;
        }

        return back()->with('success', "تم ترفيع {$count} طالباً بنجاح إلى {$targetLevel} وتسجيل موادهم تلقائياً.");
    }

    public function academicManagement(Request $request)
    {
        $activeSemester = DB::table('semesters')->where('is_active', true)->first();
        $semestersList  = DB::table('semesters')->orderBy('semester_id', 'desc')->get();

        // جلب قائمة الطلاب مع بيناتهم وسنتهم الأكاديمية
        $studentsQuery = Student::with(['user', 'program']);

        if ($request->filled('search')) {
            $search = $request->search;
            $studentsQuery->whereHas('user', function ($q) use ($search) {
                $q->where('full_name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            })->orWhere('student_code', 'like', "%{$search}%");
        }

        if ($request->filled('level_filter')) {
            if ($request->level_filter === 'first_year') {
                $studentsQuery->where(function($q) {
                    $q->whereIn('level', ['السنة الأولى', 'أولى', '1'])->orWhereNull('level')->orWhere('level', '');
                });
            } elseif ($request->level_filter === 'second_year') {
                $studentsQuery->whereIn('level', ['السنة الثانية', 'ثانية', '2']);
            }
        }

        $students = $studentsQuery->paginate(20)->appends($request->all());

        $firstYearCount = Student::where(function($q) {
            $q->whereIn('level', ['السنة الأولى', 'أولى', '1'])->orWhereNull('level')->orWhere('level', '');
        })->count();

        $secondYearCount = Student::whereIn('level', ['السنة الثانية', 'ثانية', '2'])->count();
        $totalStudents = Student::count();
        $courses = DB::table('courses')
            ->leftJoin('course_program', 'courses.course_id', '=', 'course_program.course_id')
            ->leftJoin('programs', 'course_program.program_id', '=', 'programs.id')
            ->select('courses.*', 'programs.name as program_name')
            ->orderBy('courses.year')
            ->orderBy('courses.title')
            ->get();

        return view('affairs.academic_management', compact(
            'activeSemester',
            'semestersList',
            'students',
            'firstYearCount',
            'secondYearCount',
            'totalStudents',
            'courses'
        ));
    }

    public function updateStudentLevel(Request $request, $id)
    {
        $request->validate([
            'level' => 'required|string|in:السنة الأولى,السنة الثانية',
        ]);

        $student = Student::findOrFail($id);
        $oldLevel = $student->level ?? 'غير محدد';
        $newLevel = $request->level;

        $student->update(['level' => $newLevel, 'updated_at' => now()]);
        DB::table('users')->where('user_id', $student->user_id)->update(['academic_year' => $newLevel, 'updated_at' => now()]);

        // تلقين المواد المناسبة للـ level الجديد
        Student::autoEnrollCourses($student->student_id);

        // إرسال إشعار للطالب بتعديل السنة الدراسية
        Notification::create([
            'user_id'  => $student->user_id,
            'title'    => 'تعديل السنة الدراسية ℹ️',
            'message'  => "تم تعديل سنتك الدراسية إلى ({$newLevel}) وتحديث موادك الدراسية المسجلة.",
            'type'     => 'academic',
            'category' => 'academic',
            'is_read'  => 0,
        ]);
        \App\Services\FcmService::sendToUser($student->user_id, 'تعديل السنة الدراسية ℹ️', "تم تعديل سنتك الدراسية إلى ({$newLevel}) وتحديث موادك الدراسية المسجلة.", ['type' => 'academic']);

        \App\Models\UserActivity::log('تغيير السنة الدراسية لطالب', "تم تغيير سنة الطالب {$student->user->full_name} من {$oldLevel} إلى {$newLevel}");

        return back()->with('success', "تم تعديل السنة الدراسية للطالب ({$student->user->full_name}) إلى [{$newLevel}] وتحديث تسجيل المواد بنجاح.");
    }




    // ─────────────────────────── Calendar ───────────────────────────
    public function calendar()
    {
        $departments = DB::table('departments')->orderBy('name')->get();
        $events = CalendarEvent::with('department')
            ->orderBy('event_date', 'asc')
            ->get();

        return view('affairs.calendar', compact('events', 'departments'));
    }

    public function storeCalendarEvent(Request $request)
    {
        $this->normalizeAccountCredentials($request);


        $request->validate([
            'event_date'    => 'required|date',
            'title'         => 'required|string|max:255',
            'event_time'    => 'nullable',
            'location'      => 'nullable|string|max:255',
            'department_id' => 'nullable|exists:departments,department_id',
        ]);

        // حماية من التكرار عند النقر المتعدد أو البطء في الاتصال
        $existing = CalendarEvent::where('user_id', Auth::id())
            ->where('event_date', $request->event_date)
            ->where('title', $request->title)
            ->where('created_at', '>=', now()->subSeconds(10))
            ->first();

        if ($existing) {
            return back()->with('success', 'تم إضافة الحدث بنجاح.');
        }

        CalendarEvent::create([
            'user_id'       => Auth::id(),
            'department_id' => $request->filled('department_id') ? $request->department_id : null,
            'event_date'    => $request->event_date,
            'title'         => $request->title,
            'event_time'    => $request->filled('event_time') ? $request->event_time : null,
            'location'      => $request->location,
        ]);

        return back()->with('success', 'تم إضافة الحدث بنجاح إلى قاعدة البيانات.');
    }

    public function updateCalendarEvent(Request $request, $id)
    {
        $request->validate([
            'event_date'    => 'required|date',
            'title'         => 'required|string|max:255',
            'event_time'    => 'nullable',
            'location'      => 'nullable|string|max:255',
            'department_id' => 'nullable|exists:departments,department_id',
        ]);

        $event = CalendarEvent::where('id', $id)->where('user_id', Auth::id())->firstOrFail();
        $event->update([
            'department_id' => $request->filled('department_id') ? $request->department_id : null,
            'event_date'    => $request->event_date,
            'title'         => $request->title,
            'event_time'    => $request->filled('event_time') ? $request->event_time : null,
            'location'      => $request->location,
        ]);

        return back()->with('success', 'تم تحديث الحدث بنجاح.');
    }

    public function deleteCalendarEvent($id)
    {
        $event = CalendarEvent::where('id', $id)->where('user_id', Auth::id())->firstOrFail();
        $event->delete();

        return back()->with('success', 'تم حذف الحدث بنجاح.');
    }

    // ─────────────────────────── Activities ───────────────────────────
    public function activities(Request $request)
    {
        $departments = DB::table('departments')->orderBy('name')->get();

        $deptId     = $request->input('department_id');
        $dateMode   = $request->input('date_mode', 'all');
        $singleDate = $request->input('single_date');
        $startDate  = $request->input('start_date');
        $endDate    = $request->input('end_date');
        $weekDate   = $request->input('week_date');

        $query = CalendarEvent::with('department');

        // Department Filter
        if (!empty($deptId)) {
            $dept = DB::table('departments')->where('department_id', $deptId)->first();
            $query->where(function($q) use ($deptId, $dept) {
                $q->where('department_id', $deptId);
                if ($dept) {
                    $q->orWhere('location', 'like', "%{$dept->name}%")
                      ->orWhere('title', 'like', "%{$dept->name}%");
                }
            });
        }

        // Date Filters
        if ($dateMode === 'single_date' && !empty($singleDate)) {
            $query->whereDate('event_date', $singleDate);
        } elseif ($dateMode === 'date_range' && !empty($startDate) && !empty($endDate)) {
            $query->whereBetween('event_date', [$startDate, $endDate]);
        } elseif ($dateMode === 'week' && !empty($weekDate)) {
            try {
                $carbonDate = \Carbon\Carbon::parse($weekDate);
                $startOfWeek = $carbonDate->copy()->startOfWeek(\Carbon\Carbon::SUNDAY)->format('Y-m-d');
                $endOfWeek   = $carbonDate->copy()->endOfWeek(\Carbon\Carbon::SATURDAY)->format('Y-m-d');
                $query->whereBetween('event_date', [$startOfWeek, $endOfWeek]);
            } catch (\Exception $e) {}
        }

        $events = $query->orderBy('event_date', 'asc')->get();

        return view('affairs.activities', compact(
            'events',
            'departments',
            'deptId',
            'dateMode',
            'singleDate',
            'startDate',
            'endDate',
            'weekDate'
        ));
    }

    // ─────────────────────────── Student Services ───────────────────────────
    public function studentServices()
    {
        // الشؤون يرون كافة الطلبات 
        // أو الطلبات التي في حالتهم ('pending_affairs') والطلبات التي قرروا فيها من قبل
        $requests = \App\Models\StudentRequest::with(['student.user', 'student.program.department'])
                    ->orderBy('created_at', 'desc')
                    ->get();
        return view('affairs.student-services', compact('requests'));
    }

    public function processStudentService(Request $request, $id)
    {
        $studentReq = \App\Models\StudentRequest::findOrFail($id);

        if (!in_array($studentReq->status, ['pending_affairs', 'pending'])) {
            return back()->with('error', 'لقد قمت بإبداء رأيك وسحب صلاحية التعديل على هذا الطلب مسبقاً (مسموح برد واحد فقط).');
        }

        $request->validate([
            'decision' => 'required|in:approved,rejected',
            'notes' => 'required|string|max:1000'
        ]);
        
        // تحديث قرار الشؤون
        $studentReq->affairs_decision = $request->decision;
        $studentReq->affairs_notes = $request->notes;
        
        // إذا كان نوع الطلب فك قفل الجهاز وتمت الموافقة عليه من الشؤون
        if ($studentReq->type === 'device_reset') {
            $studentReq->status = $request->decision === 'approved' ? 'approved' : 'rejected';
            $studentReq->save();

            $student = $studentReq->student;
            if ($student && $request->decision === 'approved') {
                // تصفير قفل الجهاز
                $student->update([
                    'device_id'        => null,
                    'is_device_locked' => 0,
                ]);

                // تسجيل الخروج التلقائي من جميع الأجهزة عبر حذف التوكنات Active
                DB::table('personal_access_tokens')
                    ->where('tokenable_id', $student->user_id)
                    ->delete();

                // إرسال إشعار للطالب
                \App\Models\Notification::create([
                    'user_id' => $student->user_id,
                    'title'   => 'تم فك قفل الجهاز',
                    'message' => 'وافقت شؤون الطلاب على طلب فك قفل الجهاز الخاص بك. تم تسجيل الخروج من الأجهزة القديمة وتصفير القفل، يمكنك الآن تسجيل الدخول من جهازك الجديد.',
                    'type'    => 'academic',
                ]);
            }

            return back()->with('success', $request->decision === 'approved' 
                ? 'تمت الموافقة على طلب فك قفل الجهاز وتصفير الجهاز وتسجيل الخروج من الحساب بنجاح.' 
                : 'تم رفض طلب فك قفل الجهاز.');
        }

        // الطلبات الأخرى تنتقل لرئيس القسم
        $studentReq->status = 'pending_hod';
        $studentReq->save();

        $studentObj = $studentReq->student;
        if ($studentObj) {
            $studentUser = DB::table('users')->where('user_id', $studentObj->user_id)->first();
            $studentName = $studentUser?->full_name ?? 'الطالب';

            $decisionText = $request->decision === 'approved' ? 'الموافقة المبدئية' : 'إبداء الرأي والتحفظات';
            $affairsMsg = "قامت الشؤون الطلابية بإبداء ($decisionText) وملاحظاتها على طلبك (#{$studentReq->id})، وتم تحويل الطلب إلى رئيس القسم للمتابعة.";

            // إشعار للطالب بمراجعة الشؤون
            DB::table('notifications')->insert([
                'user_id'    => $studentObj->user_id,
                'title'      => 'تحديث من الشؤون الطلابية على طلبك',
                'message'    => $affairsMsg,
                'type'       => 'student_service',
                'category'   => 'administrative',
                'related_id' => $studentReq->id,
                'is_read'    => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // إشعار لرؤساء الأقسام
            $hodUserIds = DB::table('users')->where('role_id', 5)->pluck('user_id');
            foreach ($hodUserIds as $hodUserId) {
                DB::table('notifications')->insert([
                    'user_id'    => $hodUserId,
                    'title'      => 'طلب خدمة محول من الشؤون',
                    'message'    => "تمت مراجعة طلب الطالب $studentName من قبل الشؤون وهو بانتظار موافقتك وملاحظاتك كرئيس قسم.",
                    'type'       => 'student_service',
                    'category'   => 'administrative',
                    'related_id' => $studentReq->id,
                    'is_read'    => 0,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        return back()->with('success', 'تم حفظ رأي الشؤون بنجاح وتحويل الطلب إلى رئيس القسم.');
    }

    // ─────────────────────────── Accounts (معلم + رئيس قسم فقط) ────
    public function accounts()
    {
        $users = User::whereIn('role_id', [2, 3, 4, 5, 6])->with('student')->latest()->get();
        $departments = DB::table('departments')->orderBy('name')->get();
        
        $coursesList = DB::table('courses')
            ->join('course_program', 'courses.course_id', '=', 'course_program.course_id')
            ->join('programs', 'course_program.program_id', '=', 'programs.id')
            ->select('courses.course_id', 'courses.title', 'programs.department_id')
            ->distinct()
            ->get();
            
        $deptCourses = [];
        foreach ($coursesList as $c) {
            $deptCourses[$c->department_id][] = ['id' => $c->course_id, 'title' => $c->title];
        }
        
        $branchesList = DB::table('programs')->select('id', 'name', 'department_id')->get();
        $deptBranches = [];
        foreach ($branchesList as $b) {
            $deptBranches[$b->department_id][] = ['id' => $b->id, 'name' => $b->name];
        }
        
        $courses = DB::table('courses')->orderBy('title')->get();
        return view('affairs.accounts', compact('users', 'departments', 'courses', 'deptCourses', 'deptBranches'));
    }

    public function resetStudentDevice(Request $request, int $studentId)
    {
        $student = Student::find($studentId);

        if (!$student) {
            return back()->with('error', 'الطالب غير موجود.');
        }

        $student->update([
            'device_id'        => null,
            'is_device_locked' => 0,
        ]);

        return back()->with('success', 'تم إعادة تسجيل الجهاز بنجاح. يمكن للطالب الآن تسجيل الدخول من جهاز جديد.');
    }

    public function updateAccount(Request $request, $id)
    {
        $this->normalizeAccountCredentials($request);


        $user = DB::table('users')->where('user_id', $id)->first();
        if (!$user) {
            return redirect()->back()->with('error', 'المستخدم غير موجود.');
        }

        $request->validate([
            'full_name' => 'required|string|max:255',
            'phone'     => 'nullable|string|max:20',
            'email'     => [
                'required',
                'email',
                'max:255',
                'unique:users,email,' . $id . ',user_id',
                
            ],
            'password'  => 'nullable|string|min:6|confirmed',
        ], [
            'email.unique'       => 'البريد الإلكتروني مستخدم بالفعل.',
            'password.confirmed' => 'تأكيد كلمة المرور غير متطابق.',
        ]);

        $updates = [
            'full_name'  => $request->full_name,
            'email'      => $request->email,
            'phone'      => $request->phone,
            'updated_at' => now(),
        ];

        if ($request->filled('password')) {
            $updates['password'] = bcrypt($request->password);
        }

        DB::table('users')->where('user_id', $id)->update($updates);

        return redirect()->back()->with('success', 'تم تحديث بيانات الحساب بنجاح!');
    }

    public function storeAccount(Request $request)
    {
        $this->normalizeAccountCredentials($request);


        $request->validate([
            'full_name' => 'required|string|max:255',
            'email'     => 'required|email|unique:users,email',
            'role_id'   => 'required|integer|in:2,5', // معلم أو رئيس قسم فقط
            'password'  => 'required|min:6',
            'phone'     => 'nullable|string|max:20',
        ], [
            'email.unique' => 'البريد الإلكتروني مستخدم بالفعل.',
            'role_id.in'   => 'يمكن إنشاء حسابات للمعلمين ورؤساء الأقسام فقط.',
        ]);

        if ($request->role_id == 5) {
            $request->validate([
                'department_id' => 'required|exists:departments,department_id'
            ]);
        } elseif ($request->role_id == 2) {
            $request->validate([
                'department_id'  => 'required|exists:departments,department_id',
                'specialization' => 'required|string|max:255',
                'courses'        => 'nullable|array'
            ]);
        }

        $baseUsername = explode('@', $request->email)[0];
        $username = $baseUsername;
        $counter = 1;
        while (User::where('username', $username)->exists()) {
            $username = $baseUsername . $counter++;
        }

        $dept = DB::table('departments')->where('department_id', $request->department_id)->first();

        DB::transaction(function () use ($request, $username, $dept) {
            $user = User::create([
                'full_name'  => $request->full_name,
                'email'      => $request->email,
                'phone'      => $request->phone,
                'role_id'    => $request->role_id,
                'department' => $dept ? $dept->name : null,
                'password'   => Hash::make($request->password),
                'status'     => 'active',
                'username'   => $username,
            ]);

            if ((int) $request->role_id === 2) {
                $teacherId = DB::table('teachers')->insertGetId([
                    'user_id'        => $user->user_id,
                    'specialization' => $request->specialization ?? 'عام',
                    'created_at'     => now(),
                    'updated_at'     => now(),
                ]);

                if ($request->filled('courses')) {
                    foreach ($request->courses as $courseId) {
                        DB::table('course_teachers')->insertOrIgnore([
                            'teacher_id' => $teacherId,
                            'course_id'  => $courseId,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                    }
                }
            } elseif ((int) $request->role_id === 5) {
                DB::table('heads')->insert([
                    'user_id'       => $user->user_id,
                    'department_id' => $request->department_id,
                    'created_at'    => now(),
                    'updated_at'    => now(),
                ]);
            }
        });

        return back()->with('success', 'تم إنشاء الحساب بنجاح.');
    }

    // ─────────────────────────── الأرقام الجامعية ────────────────────
    public function universityIds()
    {
        $ids = DB::table('university_ids')->orderByDesc('created_at')->get();
        return view('affairs.university_ids', compact('ids'));
    }

    public function storeUniversityId(Request $request)
    {
        $this->normalizeAccountCredentials($request);


        $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name'  => 'required|string|max:255',
            'date_of_birth' => 'nullable|date',
            'phone'      => 'nullable|string|max:20',
            'telegram_chat_id' => 'nullable|string|max:50',
            'photo'      => 'nullable|image|mimes:jpeg,png,jpg,gif|max:5120',
        ]);

        $year = date('Y');
        $lastId = DB::table('university_ids')
            ->where('university_id', 'like', $year . '%')
            ->orderBy('university_id', 'desc')
            ->value('university_id');

        if ($lastId) {
            $increment = intval(substr($lastId, 4)) + 1;
            $newId = $year . str_pad($increment, 2, '0', STR_PAD_LEFT);
        } else {
            $newId = $year . '01';
        }

        $fullName = trim($request->first_name . ' ' . $request->last_name);
        $telegramChatId = $request->telegram_chat_id ? trim($request->telegram_chat_id) : null;

        $photoPath = null;
        if ($request->hasFile('photo')) {
            $photoPath = $request->file('photo')->store('student_photos', 'public');
        }

        DB::table('university_ids')->insert([
            'university_id'    => $newId,
            'full_name'        => $fullName,
            'first_name'       => $request->first_name,
            'last_name'        => $request->last_name,
            'date_of_birth'    => $request->date_of_birth,
            'phone'            => $request->phone,
            'photo'            => $photoPath,
            'role'             => 'student',
            'is_used'          => false,
            'telegram_chat_id' => $telegramChatId,
            'created_by'       => Auth::id(),
            'created_at'       => now(),
            'updated_at'       => now(),
        ]);

        // ── إرسال رسالة تليجرام للطالب بمعلومات الرقم الجامعي ──
        if ($telegramChatId) {
            try {
                $telegram = new TelegramService();
                $defaultPassword = $newId; // كلمة المرور الافتراضية = الرقم الجامعي
                $telegram->sendCredentials(
                    (int) $telegramChatId,
                    $newId,
                    $defaultPassword,
                    $fullName,
                    '',
                    $request->date_of_birth ?? ''
                );
            } catch (\Exception $e) {
                Log::error('Telegram sendCredentials error: ' . $e->getMessage());
            }
        }

        return back()->with('success', 'تم إضافة الرقم الجامعي بنجاح وتوليد الرقم: ' . $newId);
    }

    public function updateUniversityId(Request $request, $id)
    {
        $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name'  => 'required|string|max:255',
            'date_of_birth' => 'nullable|date',
            'phone'      => 'nullable|string|max:20',
            'telegram_chat_id' => 'nullable|string|max:50',
            'photo'      => 'nullable|image|mimes:jpeg,png,jpg,gif|max:5120',
        ]);

        $uid = DB::table('university_ids')->where('id', $id)->first();
        if (!$uid) {
            return back()->with('error', 'الرقم الجامعي غير موجود.');
        }

        $fullName = trim($request->first_name . ' ' . $request->last_name);

        $updates = [
            'full_name'        => $fullName,
            'first_name'       => $request->first_name,
            'last_name'        => $request->last_name,
            'date_of_birth'    => $request->date_of_birth,
            'phone'            => $request->phone,
            'telegram_chat_id' => $request->telegram_chat_id ? trim($request->telegram_chat_id) : null,
            'updated_at'       => now(),
        ];

        if ($request->hasFile('photo')) {
            // حذف الصورة القديمة إذا موجودة
            if ($uid->photo) {
                Storage::disk('public')->delete($uid->photo);
            }
            $updates['photo'] = $request->file('photo')->store('student_photos', 'public');
        }

        DB::table('university_ids')->where('id', $id)->update($updates);

        return back()->with('success', 'تم تحديث البيانات بنجاح.');
    }

    public function deleteUniversityId($id)
    {
        $uid = DB::table('university_ids')->where('id', $id)->first();
        if ($uid && $uid->is_used) {
            return back()->with('error', 'لا يمكن حذف رقم مستخدم.');
        }
        DB::table('university_ids')->where('id', $id)->delete();
        return back()->with('success', 'تم الحذف.');
    }

    // ─────────────────────────── طلبات الحسابات المعلّقة ─────────────
    public function pendingAccounts()
    {
        $pending = User::whereIn('role_id', [3, 4])
            ->where('status', 'inactive')
            ->orderByDesc('created_at')
            ->get();
        return view('affairs.pending_accounts', compact('pending'));
    }

    public function approveAccount($id)
    {
        $user = User::findOrFail($id);

        // إذا كان طالباً ولم يكن يملك رقماً جامعياً، نولد له رقماً جامعياً غير مكرر تلقائياً
        if ($user->role_id == 3 && empty($user->university_id)) {
            $existingCode = DB::table('students')->where('user_id', $user->user_id)->value('student_code');
            if (!empty($existingCode) && !str_starts_with($existingCode, 'PENDING_') && is_numeric($existingCode)) {
                $user->university_id = $existingCode;
            } else {
                $maxUid = (int) (DB::table('university_ids')->whereRaw("university_id REGEXP '^[0-9]+$'")->max(DB::raw('CAST(university_id AS UNSIGNED)')) ?? 2026100);
                $maxStu = (int) (DB::table('students')->whereRaw("student_code REGEXP '^[0-9]+$'")->max(DB::raw('CAST(student_code AS UNSIGNED)')) ?? 2026100);
                $maxUsr = (int) (DB::table('users')->whereRaw("university_id REGEXP '^[0-9]+$'")->max(DB::raw('CAST(university_id AS UNSIGNED)')) ?? 2026100);
                $nextId = max($maxUid, $maxStu, $maxUsr, 2026100) + 1;
                $generatedUniversityId = (string) $nextId;

                $user->university_id = $generatedUniversityId;

                DB::table('university_ids')->insertOrIgnore([
                    'university_id' => $generatedUniversityId,
                    'full_name'     => $user->full_name,
                    'first_name'    => $user->first_name,
                    'last_name'     => $user->last_name,
                    'role'          => 'student',
                    'is_used'       => true,
                    'created_at'    => now(),
                    'updated_at'    => now(),
                ]);

                DB::table('students')->where('user_id', $user->user_id)->update([
                    'student_code' => $generatedUniversityId
                ]);
            }
        }

        $user->status = 'active';
        $user->save();

        // ---- تسجيل الطالب تلقائياً بكافة مواد وقسم برنامجه عند الموافقة ----
        if ($user->role_id == 3) {
            DB::table('users')->where('user_id', $user->user_id)->update(['academic_year' => 'السنة الأولى']);
            $student = \App\Models\Student::where('user_id', $user->user_id)->first();
            if ($student) {
                $student->update(['level' => 'السنة الأولى']);
                $existingEnrollments = \DB::table('enrollments')->where('student_id', $student->student_id)->count();
                if ($existingEnrollments === 0) {
                    $branch = $user->branch ?? $user->department;
                    $program = null;
                    if ($branch) {
                        $program = \DB::table('programs')
                            ->where('name', 'LIKE', '%' . $branch . '%')
                            ->first();
                    }
                    if (!$program && $user->department) {
                        $program = \DB::table('programs')
                            ->where('name', 'LIKE', '%' . $user->department . '%')
                            ->first();
                    }

                    $courseIds = collect();
                    if ($program) {
                        $student->update(['program_id' => $program->id]);
                        $courseIds = \DB::table('course_program')
                            ->where('program_id', $program->id)
                            ->pluck('course_id');
                    }

                    if ($courseIds->isEmpty()) {
                        $courseIds = \DB::table('courses')->pluck('course_id');
                    }

                    foreach ($courseIds as $courseId) {
                        \DB::table('enrollments')->insertOrIgnore([
                            'student_id'      => $student->student_id,
                            'course_id'       => $courseId,
                            'status'          => 'active',
                            'enrollment_date' => now(),
                            'created_at'      => now(),
                            'updated_at'      => now(),
                        ]);
                    }
                }
                \App\Models\Student::autoAssignAdvisor($student->student_id);
            }
        }

        // ---- إضافة ربط الأبناء بولي الأمر عند الموافقة ----
        if ($user->role_id == 4 && !empty($user->children_ids)) {
            $childrenIdsList = is_array($user->children_ids) ? $user->children_ids : json_decode($user->children_ids, true);
            if (is_array($childrenIdsList)) {
                $parent = DB::table('parents')->where('user_id', $user->user_id)->first();
                if ($parent) {
                    foreach ($childrenIdsList as $universityId) {
                        $childStudent = DB::table('students')
                            ->where('student_code', $universityId)
                            ->first();
                        if ($childStudent) {
                            $childUser = User::where('user_id', $childStudent->user_id)->first();
                            DB::table('parent_students')->insertOrIgnore([
                                'parent_id'    => $user->user_id,
                                'student_id'   => $childUser ? $childUser->user_id : $childStudent->user_id,
                                'relationship' => 'father',
                                'created_at'   => now(),
                                'updated_at'   => now(),
                            ]);
                        }
                    }
                }
            }
        }
        // ---------------------------------------------------

        $notifTitle = 'تم تفعيل حسابك ✓';
        $notifMsg   = 'مرحباً ' . $user->full_name . '! تم تفعيل حسابك. يمكنك الآن تسجيل الدخول والوصول لموادك ومحاضراتك.';
        DB::table('notifications')->insert([
            'user_id'    => $user->user_id,
            'sender_id'  => Auth::user()->user_id,
            'title'      => $notifTitle,
            'message'    => $notifMsg,
            'type'       => 'administrative',
            'category'   => 'administrative',
            'is_read'    => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        \App\Services\FcmService::sendToUser($user->user_id, $notifTitle, $notifMsg, ['type' => 'administrative']);

        // ── إرسال إشعار تليجرام للموافقة والتفعيل عبر البوت ──
        $telegramChatId = $user->telegram_chat_id ?? '7821980919';
        try {
            $telegram = new TelegramService();
            $idText = $user->university_id ?? $user->username ?? '';
            $text = "🎓 <b>تفعيل الحساب - Edu Bridge</b>\n\n"
                  . "مرحباً <b>{$user->full_name}</b>،\n\n"
                  . "🎉 لقد تم <b>الموافقة وتفعيل حسابك بنجاح</b> من قِبل إدارة شؤون الطلاب!\n"
                  . "📚 تم تسجيل ونزول كافة موادك ومحاضراتك الأكاديمية بنجاح.\n\n"
                  . ($idText ? "🆔 <b>الرقم الجامعي / اسم المستخدم:</b> <code>{$idText}</code>\n\n" : "\n")
                  . "📲 يمكنك الآن فتح التطبيق وتسجيل الدخول مباشرة للوصول إلى موادك ومحاضراتك.";
            $telegram->sendMessage((int) $telegramChatId, $text);
        } catch (\Exception $e) {
            Log::error('Telegram approveAccount notification error: ' . $e->getMessage());
        }

        return back()->with('success', 'تم موافقة وتفعيل الحساب وإرسال رسالة التليجرام بنجاح.');
    }

    public function rejectAccount($id)
    {
        $user = User::findOrFail($id);

        // إرسال إشعار تليجرام للرفض قبل الحذف
        if ($user->telegram_chat_id) {
            try {
                $telegram = new TelegramService();
                $text = "🎓 <b>طلب التسجيل - Edu Bridge</b>\n\n"
                      . "مرحباً <b>{$user->full_name}</b>،\n\n"
                      . "⚠️ نأسف لإعلامك بأنه تم <b>رفض طلب إنشاء وتفعيل حسابك</b> من قِبل إدارة شؤون الطلاب.\n\n"
                      . "يرجى مراجعة شؤون الطلاب لمزيد من التفاصيل.";
                $telegram->sendMessage((int) $user->telegram_chat_id, $text);
            } catch (\Exception $e) {
                Log::error('Telegram rejectAccount notification error: ' . $e->getMessage());
            }
        }

        if ($user->university_id) {
            DB::table('university_ids')
                ->where('university_id', $user->university_id)
                ->update(['is_used' => false]);
        }
        DB::table('students')->where('user_id', $id)->delete();
        DB::table('parents')->where('user_id', $id)->delete();
        $user->delete();
        return back()->with('success', 'تم رفض الطلب وحذفه.');
    }

    public function toggleAccountStatus(Request $request, $id)
    {
        $user = User::findOrFail($id);
        $user->status = ($user->status === 'active') ? 'inactive' : 'active';
        $user->save();
        return back()->with('success', 'تم تحديث حالة الحساب.');
    }

    public function deleteAccount($id)
    {
        User::findOrFail($id)->delete();
        return back()->with('success', 'تم حذف الحساب بنجاح.');
    }

    // ─────────────────────────── Leaves ───────────────────────────
    // ─────────────────────────── Leaves ───────────────────────────
    // ─────────────────────────── Leaves ───────────────────────────
    public function leaves(Request $request)
    {
        $departments = DB::table('departments')->orderBy('name')->get();

        $deptId     = $request->input('department_id');
        $dateMode   = $request->input('date_mode', 'all');
        $singleDate = $request->input('single_date');
        $startDate  = $request->input('start_date');
        $endDate    = $request->input('end_date');
        $weekDate   = $request->input('week_date');

        // Query 1: leave_requests
        $q1 = DB::table('leave_requests')
            ->join('users', 'leave_requests.student_id', '=', 'users.user_id')
            ->leftJoin('students', 'students.user_id', '=', 'users.user_id')
            ->leftJoin('programs', 'students.program_id', '=', 'programs.id')
            ->select(
                'leave_requests.id',
                'leave_requests.student_id',
                'leave_requests.type',
                'leave_requests.date',
                'leave_requests.reason',
                'leave_requests.status',
                'leave_requests.created_at',
                'leave_requests.updated_at',
                'users.full_name as student_name',
                'students.level',
                'students.student_code',
                'programs.name as program_name',
                DB::raw("'leave_requests' as source_table")
            )
            ->whereIn('leave_requests.status', ['pending_affairs', 'approved', 'rejected']);

        // Query 2: absence_requests
        $q2 = DB::table('absence_requests')
            ->join('students', 'absence_requests.student_id', '=', 'students.student_id')
            ->join('users', 'students.user_id', '=', 'users.user_id')
            ->leftJoin('programs', 'students.program_id', '=', 'programs.id')
            ->select(
                'absence_requests.request_id as id',
                'students.user_id as student_id',
                DB::raw("'full_day' as type"),
                'absence_requests.date',
                'absence_requests.reason',
                'absence_requests.status',
                'absence_requests.created_at',
                'absence_requests.updated_at',
                'users.full_name as student_name',
                'students.level',
                'students.student_code',
                'programs.name as program_name',
                DB::raw("'absence_requests' as source_table")
            )
            ->whereIn('absence_requests.status', ['pending_affairs', 'approved', 'rejected']);

        // Department Filter
        if (!empty($deptId)) {
            $q1->where('programs.department_id', $deptId);
            $q2->where('programs.department_id', $deptId);
        }

        // Date Filters
        if ($dateMode === 'single_date' && !empty($singleDate)) {
            $q1->whereDate('leave_requests.date', $singleDate);
            $q2->whereDate('absence_requests.date', $singleDate);
        } elseif ($dateMode === 'date_range' && !empty($startDate) && !empty($endDate)) {
            $q1->whereBetween('leave_requests.date', [$startDate, $endDate]);
            $q2->whereBetween('absence_requests.date', [$startDate, $endDate]);
        } elseif ($dateMode === 'week' && !empty($weekDate)) {
            try {
                $carbonDate = \Carbon\Carbon::parse($weekDate);
                $startOfWeek = $carbonDate->copy()->startOfWeek(\Carbon\Carbon::SUNDAY)->format('Y-m-d');
                $endOfWeek   = $carbonDate->copy()->endOfWeek(\Carbon\Carbon::SATURDAY)->format('Y-m-d');

                $q1->whereBetween('leave_requests.date', [$startOfWeek, $endOfWeek]);
                $q2->whereBetween('absence_requests.date', [$startOfWeek, $endOfWeek]);
            } catch (\Exception $e) {
                // In case of invalid date input
            }
        }

        $res1 = $q1->orderBy('leave_requests.created_at', 'desc')->take(10)->get();
        $res2 = $q2->orderBy('absence_requests.created_at', 'desc')->take(10)->get();

        $allMerged = $res1->concat($res2)->sortByDesc('created_at');

        // Limit strictly to 10 latest records for server efficiency
        $leaves = $allMerged->take(10);

        $pendingCount  = $leaves->whereIn('status', ['pending', 'pending_affairs'])->count();
        $approvedCount = $leaves->where('status', 'approved')->count();
        $rejectedCount = $leaves->where('status', 'rejected')->count();

        return view('affairs.leaves', compact(
            'leaves',
            'pendingCount',
            'approvedCount',
            'rejectedCount',
            'departments',
            'deptId',
            'dateMode',
            'singleDate',
            'startDate',
            'endDate',
            'weekDate'
        ));
    }

    public function updateLeaveStatus(Request $request, $id)
    {
        $request->validate(['status' => 'required|in:approved,rejected,recorded']);
        $status = $request->status;
        $sourceTable = $request->input('source_table', 'absence_requests');

        if ($sourceTable === 'leave_requests') {
            $leaveRequest = DB::table('leave_requests')->where('id', $id)->first();
            if ($leaveRequest) {
                DB::table('leave_requests')->where('id', $id)->update(['status' => $status, 'updated_at' => now()]);
            }
        } else {
            $leaveRequest = DB::table('absence_requests')->where('request_id', $id)->first();
            if ($leaveRequest) {
                DB::table('absence_requests')->where('request_id', $id)->update(['status' => $status, 'updated_at' => now()]);
            }
        }

        if (!$leaveRequest) {
            return back()->with('error', 'الطلب غير موجود.');
        }

        // تحديد الطالب المستهدف لإشعاره بالقرار النهائي
        $studentUserId = null;
        if (isset($leaveRequest->student_id)) {
            // إذا كان المعرف يخزن student_id الخاص بجدول الطلاب
            $stUser = DB::table('students')->where('student_id', $leaveRequest->student_id)->value('user_id');
            $studentUserId = $stUser ?? $leaveRequest->student_id;
        }

        // الخطوة الأهم: إرسال الإشعار النهائي للطالب فقط عند موافقة أو رفض شؤون الطلاب
        if ($studentUserId) {
            $title   = $status === 'approved' ? 'تمت الموافقة النهائية على طلب الإذن ✓' : 'تم رفض طلب الإذن';
            $message = $status === 'approved'
                ? 'تهانينا، تمت الموافقة على طلب إذنك بتاريخ ' . $leaveRequest->date . ' نهائياً من قِبل ولي الأمر ورئيس القسم وشؤون الطلاب!'
                : 'نعتذر، تم رفض طلب إذنك بتاريخ ' . $leaveRequest->date . ' من قِبل إدارة شؤون الطلاب.';

            DB::table('notifications')->insert([
                'user_id'    => $studentUserId,
                'title'      => $title,
                'message'    => $message,
                'type'       => 'leave_request',
                'related_id' => $id,
                'is_read'    => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            \App\Services\FcmService::sendToUser(
                $studentUserId,
                $title,
                $message,
                ['type' => 'leave_request', 'related_id' => (string) $id]
            );
        }

        return back()->with('success', 'تم تحديث حالة طلب الإجازة وإشعار الطالب بالنتيجة النهائية.');
    }

    // ─────────────────────────── Messages ───────────────────────────
    public function messages()
    {
        $currentUserId = Auth::id();

        // Affairs can chat with any other user
        $allUsers = User::where('user_id', '!=', $currentUserId)->get();

        return view('affairs.messages', compact('allUsers'));
    }



    public function getConversation($userId)
    {
        $currentUserId = Auth::id();
        $messages = Message::with(['sender', 'receiver'])
            ->where('deleted_for_everyone', false)
            ->where(function ($q) use ($currentUserId, $userId) {
                $q->where(function ($sub) use ($currentUserId, $userId) {
                    $sub->where('sender_id', $currentUserId)->where('receiver_id', $userId)->where('deleted_for_sender', false);
                })->orWhere(function ($sub) use ($currentUserId, $userId) {
                    $sub->where('sender_id', $userId)->where('receiver_id', $currentUserId)->where('deleted_for_receiver', false);
                });
            })
            ->orderBy('created_at', 'asc')
            ->get();

        Message::where('sender_id', $userId)
            ->where('receiver_id', $currentUserId)
            ->where('is_read', false)
            ->update(['is_read' => true]);

        return response()->json($messages);
    }

    public function searchMessages(Request $request, $userId)
    {
        $currentUserId = Auth::id();
        $query = $request->query('q');

        $messages = Message::with(['sender', 'receiver'])
            ->where('deleted_for_everyone', false)
            ->where(function ($q) use ($currentUserId, $userId) {
                $q->where(function($q2) use ($currentUserId, $userId) {
                    $q2->where('sender_id', $currentUserId)->where('receiver_id', $userId)->where('deleted_for_sender', false);
                })
                ->orWhere(function($q2) use ($currentUserId, $userId) {
                    $q2->where('sender_id', $userId)->where('receiver_id', $currentUserId)->where('deleted_for_receiver', false);
                });
            })
            ->where('message', 'LIKE', '%' . $query . '%')
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json(['status' => 'success', 'data' => $messages]);
    }

    public function sendMessage(Request $request)
    {
        $request->validate([
            'receiver_id' => 'required|exists:users,user_id',
            'message'     => 'required|string|max:2000',
            'attachment'  => 'nullable|file|max:51200',
        ]);

        $attachmentPath = null;
        if ($request->hasFile('attachment')) {
            $file = $request->file('attachment');
            $folder = 'chat_attachments';
            
            if ($request->message === '[Voice Note]' || strpos($file->getMimeType(), 'audio') !== false) {
                $folder = 'chat_voice_notes';
            }
            
            $attachmentPath = $file->store($folder, 'public');
            $attachmentPath = asset('storage/' . $attachmentPath);
        }

        $message = Message::create([
            'sender_id'   => Auth::user()->user_id,
            'receiver_id' => $request->receiver_id,
            'message'     => $request->message,
            'attachment'  => $attachmentPath,
            'is_read'     => false,
        ]);

        try {
            broadcast(new \App\Events\MessageSent($message))->toOthers();
        } catch (\Exception $e) {
            Log::error('MessageSent Broadcast Error: ' . $e->getMessage());
        }

        DB::table('notifications')->insert([
            'user_id' => $request->receiver_id,
            'title'   => 'رسالة جديدة',
            'message' => 'لقد تلقيت رسالة جديدة من ' . Auth::user()->full_name,
            'type'    => 'message',
            'is_read' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        \App\Services\FcmService::sendToUser(
            $request->receiver_id,
            'رسالة جديدة',
            'لقد تلقيت رسالة جديدة من ' . Auth::user()->full_name,
            ['type' => 'message']
        );

        if ($request->expectsJson()) {
            return response()->json(['status' => 'success', 'success' => true, 'data' => $message, 'message' => $message]);
        }

        return redirect()->back()->with('success', 'تم إرسال الرسالة بنجاح!');
    }

    public function updateMessage(Request $request, $id)
    {
        $message = Message::findOrFail($id);
        
        if ($message->sender_id !== Auth::id()) {
            return response()->json(['status' => 'error', 'message' => 'غير مصرح'], 403);
        }

        if ($message->attachment || $message->message === '[Voice Note]') {
            return response()->json(['status' => 'error', 'message' => 'لا يمكن تعديل المرفقات'], 400);
        }

        $request->validate(['message' => 'required|string|max:2000']);
        $message->update(['message' => $request->message]);

        return response()->json(['status' => 'success', 'message' => $message]);
    }

    public function deleteMessage(Request $request, $id)
    {
        $currentUserId = Auth::id();
        $type = $request->input('type', 'me');

        $message = Message::findOrFail($id);

        if ((int)$message->sender_id !== (int)$currentUserId && (int)$message->receiver_id !== (int)$currentUserId) {
            return response()->json(['status' => 'error', 'message' => 'غير مصرح'], 403);
        }

        if ($type === 'everyone') {
            if ((int)$message->sender_id !== (int)$currentUserId) {
                return response()->json(['status' => 'error', 'message' => 'يمكن لمراسل الرسالة فقط حذفها لدى الجميع'], 403);
            }
            $message->deleted_for_everyone = true;
            $message->save();

            if ($message->attachment) {
                $path = str_replace(asset('storage/'), '', $message->attachment);
                \Illuminate\Support\Facades\Storage::disk('public')->delete($path);
            }
        } else {
            if ((int)$message->sender_id === (int)$currentUserId) {
                $message->deleted_for_sender = true;
            }
            if ((int)$message->receiver_id === (int)$currentUserId) {
                $message->deleted_for_receiver = true;
            }
            $message->save();

            if (($message->deleted_for_sender && $message->deleted_for_receiver) || $message->deleted_for_everyone) {
                if ($message->attachment) {
                    $path = str_replace(asset('storage/'), '', $message->attachment);
                    \Illuminate\Support\Facades\Storage::disk('public')->delete($path);
                }
            }
        }

        return response()->json(['status' => 'success', 'message' => 'تم حذف الرسالة بنجاح']);
    }

    // ─────────────────────────── Notifications ───────────────────────────
    public function notifications(Request $request)
    {
        $query = Notification::with('sender')
            ->where('user_id', Auth::id())
            ->latest();

        if ($request->has('filter')) {
            if ($request->filter == 'unread') {
                $query->where('is_read', false);
            } elseif ($request->filter == 'read') {
                $query->where('is_read', true);
            }
        }

        $notifications = $query->paginate(15);
        $unreadCount = Notification::where('user_id', Auth::id())->where('is_read', false)->count();

        return view('affairs.notifications', compact('notifications', 'unreadCount'));
    }

    public function markNotificationRead(Request $request, $id)
    {
        Notification::where('id', $id)
            ->where('user_id', Auth::id())
            ->update(['is_read' => true]);

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json(['status' => 'success']);
        }

        return back()->with('success', 'تم تحديد الإشعار كمقروء.');
    }

    public function markAllNotificationsRead()
    {
        Notification::where('user_id', Auth::id())
            ->update(['is_read' => true]);

        return back()->with('success', 'تم تحديد جميع الإشعارات كمقروءة.');
    }

    // ─────────────────────────── Profile ───────────────────────────
    public function profile()
    {
        $user = Auth::user();

        // إحصائيات بسيطة
        // Assuming there isn't a reviewed_by column in leave_requests, we'll just show total recorded requests
        $reviewedLeaves = DB::table('leave_requests')->whereIn('status', ['approved', 'rejected'])->count();
        $sentMessages   = Message::where('sender_id', $user->user_id)->count();

        return view('affairs.profile', compact('user', 'reviewedLeaves', 'sentMessages'));
    }

    public function updateProfile(Request $request)
    {
        $user = Auth::user();

        $request->validate([
            'full_name' => 'required|string|max:255',
            'phone'     => 'nullable|string|max:20',
        ]);

        $user->update([
            'full_name' => $request->full_name,
            'phone'     => $request->phone,
        ]);

        return back()->with('success', 'تم تحديث الملف الشخصي بنجاح.');
    }

    public function updatePassword(Request $request)
    {
        $request->validate([
            'current_password' => 'required',
            'password'         => 'required|min:8|confirmed',
        ]);

        $user = Auth::user();
        if (!Hash::check($request->current_password, $user->password)) {
            return back()->withErrors(['current_password' => 'كلمة المرور الحالية غير صحيحة.']);
        }

        $user->update(['password' => Hash::make($request->password)]);
        return back()->with('success', 'تم تغيير كلمة المرور بنجاح.');
    }

    public function sendOTP(Request $request)
    {
        $request->validate([
            'full_name'        => 'nullable|string|max:255',
            'phone'            => 'nullable|string|max:20',
            'current_password' => 'nullable|string',
            'new_password'     => 'nullable|string|min:6',
            'telegram_chat_id' => 'nullable|string',
        ]);

        $user = Auth::user();

        if ($request->filled('current_password')) {
            if (!Hash::check($request->current_password, $user->password)) {
                return response()->json([
                    'success' => false,
                    'message' => 'كلمة المرور الحالية غير صحيحة.'
                ]);
            }
        }

        $otp = (string) rand(100000, 999999);

        $telegramService = new \App\Services\TelegramService();
        $telegramResult  = $telegramService->sendProfileOtpToUser($user, $otp, $request->input('telegram_chat_id'));

        if (!$telegramResult['success']) {
            return response()->json([
                'success' => false,
                'message' => $telegramResult['message']
            ]);
        }

        session([
            'affairs_profile_otp'          => $otp,
            'affairs_pending_profile_data' => $request->only(['full_name', 'phone', 'new_password'])
        ]);

        return response()->json([
            'success' => true,
            'message' => 'تم إرسال رمز التحقق (OTP) إلى حسابك في بوت تيليغرام بنجاح!'
        ]);
    }

    public function verifyOTP(Request $request)
    {
        $request->validate([
            'otp' => 'required|numeric'
        ]);

        if (session('affairs_profile_otp') == $request->otp) {
            $user = Auth::user();
            $data = session('affairs_pending_profile_data');

            $updates = ['updated_at' => now()];

            if (!empty($data['full_name'])) {
                $updates['full_name'] = $data['full_name'];
            }
            if (!empty($data['phone'])) {
                $updates['phone'] = $data['phone'];
            }
            if (!empty($data['new_password'])) {
                $updates['password'] = Hash::make($data['new_password']);
            }

            DB::table('users')
                ->where('user_id', $user->user_id)
                ->update($updates);

            session()->forget(['affairs_profile_otp', 'affairs_pending_profile_data']);

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

    // ─────────────────────────── Settings ───────────────────────────
    public function settings()
    {
        return view('affairs.settings');
    }

    // ─────────────────────────── Announcements ───────────────────────────
    public function announcements()
    {
        return view('affairs.announcements');
    }

    // ===== التقارير =====

    public function reports()
    {
        // التقارير المنجزة (الصادرة)
        $reports = DB::table('performance_reports')
            ->join('students', 'performance_reports.student_id', '=', 'students.student_id')
            ->join('users as su', 'students.user_id', '=', 'su.user_id')
            ->leftJoin('report_requests', 'performance_reports.report_request_id', '=', 'report_requests.id')
            ->leftJoin('teachers', 'report_requests.teacher_id', '=', 'teachers.teacher_id')
            ->leftJoin('users as tu', 'teachers.user_id', '=', 'tu.user_id')
            ->select(
                'performance_reports.*',
                'su.full_name as student_name',
                'tu.full_name as teacher_name'
            )
            ->orderByDesc('performance_reports.created_at')
            ->get();

        // للنموذج: قائمة الطلاب والمدربين
        $students = DB::table('students')
            ->join('users', 'students.user_id', '=', 'users.user_id')
            ->select('students.student_id', 'users.full_name')
            ->orderBy('users.full_name')
            ->get();

        $teachers = DB::table('teachers')
            ->join('users', 'teachers.user_id', '=', 'users.user_id')
            ->select('teachers.teacher_id', 'users.full_name')
            ->orderBy('users.full_name')
            ->get();

        return view('affairs.reports', compact('reports', 'students', 'teachers'));
    }

    public function storeReport(Request $request)
    {
        $this->normalizeAccountCredentials($request);


        $request->validate([
            'student_id'  => 'required|exists:students,student_id',
            'teacher_id'  => 'required|exists:teachers,teacher_id',
            'report_type' => 'required|in:academic,behavioral',
            'notes'       => 'nullable|string|max:1000',
        ]);

        $requestId = DB::table('report_requests')->insertGetId([
            'head_id'     => auth()->id(),
            'teacher_id'  => $request->teacher_id,
            'student_id'  => $request->student_id,
            'report_type' => $request->report_type,
            'notes'       => $request->notes,
            'status'      => 'pending',
            'created_at'  => now(),
            'updated_at'  => now(),
        ]);

        // إشعار المدرب (داخلي + FCM)
        $teacherUserId = DB::table('teachers')->where('teacher_id', $request->teacher_id)->value('user_id');
        $studentName   = DB::table('students')
            ->join('users', 'students.user_id', '=', 'users.user_id')
            ->where('students.student_id', $request->student_id)
            ->value('users.full_name') ?? 'طالب';

        $typLabel = $request->report_type === 'behavioral' ? 'سلوكي' : 'أكاديمي';
        $title    = 'طلب تقرير جديد';
        $message  = 'طُلب منك تقرير ' . $typLabel . ' عن الطالب ' . $studentName;

        DB::table('notifications')->insert([
            'user_id'    => $teacherUserId,
            'sender_id'  => auth()->id(),
            'title'      => $title,
            'message'    => $message,
            'type'       => 'report_request',
            'related_id' => $requestId,
            'category'   => 'academic',
            'is_read'    => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        \App\Services\FcmService::sendToUser($teacherUserId, $title, $message, [
            'type'       => 'report_request',
            'request_id' => (string) $requestId,
        ]);

        return redirect()->back()->with('success', 'تم إرسال طلب التقرير للمدرب وتم إشعاره بنجاح!');
    }

    // ─────────────────────────── طلبات تغيير الصورة ────────────────────
    public function photoRequests()
    {
        $requests = DB::table('photo_change_requests')
            ->join('users', 'photo_change_requests.user_id', '=', 'users.user_id')
            ->where('photo_change_requests.status', 'pending')
            ->select(
                'photo_change_requests.id',
                'photo_change_requests.user_id',
                'photo_change_requests.old_photo',
                'photo_change_requests.new_photo',
                'photo_change_requests.status',
                'photo_change_requests.created_at',
                'users.full_name',
                'users.email',
                'users.department'
            )
            ->orderByDesc('photo_change_requests.created_at')
            ->get();

        return view('affairs.photo_requests', compact('requests'));
    }

    public function approvePhotoRequest($id)
    {
        $req = DB::table('photo_change_requests')->where('id', $id)->where('status', 'pending')->first();
        if (!$req) {
            return back()->with('error', 'الطلب غير موجود.');
        }

        if ($req->old_photo) {
            Storage::disk('public')->delete($req->old_photo);
        }

        DB::table('users')->where('user_id', $req->user_id)->update(['avatar' => $req->new_photo]);
        DB::table('students')->where('user_id', $req->user_id)->update(['reference_photo' => $req->new_photo]);
        DB::table('photo_change_requests')->where('id', $id)->update(['status' => 'approved', 'updated_at' => now()]);

        // إرسال إشعار للطالب
        DB::table('notifications')->insert([
            'user_id'    => $req->user_id,
            'sender_id'  => Auth::id(),
            'title'      => 'تمت الموافقة على تغيير صورة الوجه',
            'message'    => 'تمت الموافقة من قبل شؤون الطلاب على طلب تحديث صورة بصمة الوجه الخاصة بك.',
            'type'       => 'academic',
            'category'   => 'academic',
            'is_read'    => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        \App\Services\FcmService::sendToUser($req->user_id, 'تمت الموافقة على تغيير صورة الوجه', 'تمت الموافقة من قبل شؤون الطلاب على طلب تحديث صورة بصمة الوجه الخاصة بك.', ['type' => 'academic']);

        return back()->with('success', 'تمت الموافقة على تغيير الصورة وتحديث البصمة بنجاح.');
    }

    public function rejectPhotoRequest($id)
    {
        $req = DB::table('photo_change_requests')->where('id', $id)->where('status', 'pending')->first();
        if (!$req) {
            return back()->with('error', 'الطلب غير موجود.');
        }

        if ($req->new_photo) {
            Storage::disk('public')->delete($req->new_photo);
        }

        DB::table('photo_change_requests')->where('id', $id)->update(['status' => 'rejected', 'updated_at' => now()]);

        // إرسال إشعار للطالب بالرفض
        DB::table('notifications')->insert([
            'user_id'    => $req->user_id,
            'sender_id'  => Auth::id(),
            'title'      => 'تم رفض طلب تغيير الصورة',
            'message'    => 'تم رفض طلب تحديث صورة بصمة الوجه الخاصة بك من قبل شؤون الطلاب.',
            'type'       => 'alert',
            'category'   => 'academic',
            'is_read'    => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        \App\Services\FcmService::sendToUser(
            $req->user_id,
            'تم رفض طلب تغيير الصورة',
            'تم رفض طلب تحديث صورة بصمة الوجه الخاصة بك من قبل شؤون الطلاب.',
            ['type' => 'photo_request', 'status' => 'rejected']
        );

        return back()->with('success', 'تم رفض طلب تغيير الصورة وإشعاره بنجاح.');
    }

    // ─────────────────────────── Academic Card Methods ───────────────────────────
    public function getFilteredStudents(Request $request)
    {
        $apiController = app(\App\Http\Controllers\Api\AffairsController::class);
        return $apiController->getFilteredStudentsForAcademicCard($request);
    }

    public function getAcademicCardData(Request $request)
    {
        $apiController = app(\App\Http\Controllers\Api\AffairsController::class);
        return $apiController->getStudentAcademicCardForAffairs($request);
    }

    public function exportAcademicCardPdf(Request $request)
    {
        $apiController = app(\App\Http\Controllers\Api\AffairsController::class);
        return $apiController->exportStudentAcademicCardPdf($request);
    }

    public function exportAcademicCardExcel(Request $request)
    {
        $apiController = app(\App\Http\Controllers\Api\AffairsController::class);
        return $apiController->exportStudentAcademicCardExcel($request);
    }
}


