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

    // ─────────────────────────── تثقيلات المواد والكنترول الأكاديمي (Course Weights & Academic Control) ───────────────────────────
    public function getCourseWeightsPayload()
    {
        // 1. الأقسام
        $departments = DB::table('departments')->select('department_id', 'name')->get();

        // 2. التخصصات / البرامج
        $programs = DB::table('programs')->select('id', 'name', 'department_id')->get();

        // 3. الفصول الدراسية
        $semesters = DB::table('semesters')->select('semester_id', 'name')->get();

        // 4. المواد المرتبطة بالبرامج مع التثقيلات
        $courses = DB::table('courses')
            ->leftJoin('course_program', 'courses.course_id', '=', 'course_program.course_id')
            ->select(
                'courses.course_id',
                'courses.title',
                'courses.weight',
                'courses.year',
                'courses.semester_id',
                'course_program.program_id'
            )
            ->get();

        // 5. رؤساء الأقسام والمرشدين الأكاديميين
        $hods = DB::table('departments')
            ->leftJoin('heads', 'departments.department_id', '=', 'heads.department_id')
            ->leftJoin('users', 'heads.user_id', '=', 'users.user_id')
            ->pluck('users.full_name', 'departments.department_id');

        $advisors = DB::table('teachers')
            ->join('users', 'teachers.user_id', '=', 'users.user_id')
            ->whereNotNull('teachers.advisor_branch')
            ->select('teachers.advisor_branch', 'teachers.advisor_year', 'users.full_name')
            ->get()
            ->keyBy(fn($t) => trim((string)$t->advisor_branch) . '_' . trim((string)$t->advisor_year));

        // 6. جلب الطلاب مع بياناتهم المؤسساتية
        $rawStudents = DB::table('students')
            ->join('users', 'students.user_id', '=', 'users.user_id')
            ->leftJoin('programs', 'students.program_id', '=', 'programs.id')
            ->leftJoin('departments', 'programs.department_id', '=', 'departments.department_id')
            ->select(
                'students.student_id',
                'students.user_id',
                'students.student_code',
                'users.full_name',
                'students.program_id',
                'programs.name as program_name',
                'departments.department_id',
                'departments.name as department_name',
                'students.level',
                'users.academic_year',
                'users.status as user_status',
                'users.created_at as joined_at'
            )
            ->get();

        // 7. علامات الطلاب في المواد المسجلة
        $studentGrades = DB::table('enrollments')
            ->join('courses', 'enrollments.course_id', '=', 'courses.course_id')
            ->leftJoin('grade_events', function($join) {
                $join->on('courses.course_id', '=', 'grade_events.course_id');
            })
            ->leftJoin('grade_entries', function($join) {
                $join->on('grade_events.id', '=', 'grade_entries.grade_event_id')
                     ->on('enrollments.student_id', '=', 'grade_entries.student_id');
            })
            ->where('enrollments.status', 'active')
            ->select(
                'enrollments.student_id',
                'courses.course_id',
                'courses.title',
                'courses.weight',
                'courses.year',
                'courses.semester_id',
                DB::raw('COALESCE(SUM(grade_entries.score), 0) as total_score'),
                DB::raw('COALESCE(SUM(CASE WHEN grade_events.type = "quiz" THEN grade_entries.score ELSE 0 END), 0) as quiz_score'),
                DB::raw('COALESCE(SUM(CASE WHEN grade_events.type = "oral" THEN grade_entries.score ELSE 0 END), 0) as oral_score'),
                DB::raw('COALESCE(SUM(CASE WHEN grade_events.type = "exam" THEN grade_entries.score ELSE 0 END), 0) as exam_score'),
                DB::raw('100 as event_max_score')
            )
            ->groupBy('enrollments.student_id', 'courses.course_id', 'courses.title', 'courses.weight', 'courses.year', 'courses.semester_id')
            ->get()
            ->groupBy('student_id');

        // تجميع بيانات الطلاب وربطها بالمواد والقرارات
        $studentsList = $rawStudents->map(function ($s) use ($hods, $advisors, $studentGrades) {
            $isYear1 = str_contains(trim((string)$s->level), 'الأولى');
            $stdCourses = $studentGrades->get($s->student_id, collect())
                ->filter(function($c) use ($isYear1) {
                    if ($isYear1 && (int)($c->year ?? 1) > 1) {
                        return false;
                    }
                    return true;
                })
                ->map(function ($c) use ($isYear1) {
                    $cYear = (int) ($c->year ?? 1);
                    $semId = (int) ($c->semester_id ?? 1);

                    // لطالب السنة الأولى: الفصل الثاني فقط مغلق (year 1, sem 2)
                    // لطالب السنة الثانية: الفصل الأخير فقط مغلق (year 2, sem 2)
                    $isClosed = $isYear1 ? ($cYear === 1 && $semId === 2) : ($cYear === 2 && $semId === 2);
                    $isPastCompleted = !$isYear1 && ($cYear === 1);
                    $isCurrentActive = $isYear1 ? ($cYear === 1 && $semId === 1) : ($cYear === 2 && $semId === 1);

                    $maxScore = 100;
                    $rawPct = (float) $c->total_score;
                    $pct = min(100, max(0, round($rawPct, 1)));
                    $w = (float) ($c->weight ?? 1);
                    if ($w <= 0) $w = 1;
                    $weightedPts = round($pct * $w, 2);

                    $status = 'ناجح';
                    if ($isClosed) {
                        $status = 'مغلق المقرر لحين انتهاء الفصل الساري';
                    } elseif ($isPastCompleted) {
                        $status = 'مجتاز (السنة السابقة)';
                    } else {
                        $status = $pct >= 50 ? 'ناجح' : 'راسب';
                    }

                    return [
                        'course_id'         => $c->course_id,
                        'title'             => $c->title,
                        'year'              => $cYear,
                        'semester_id'       => $semId,
                        'is_closed'         => $isClosed,
                        'is_past_completed' => $isPastCompleted,
                        'is_current_active' => $isCurrentActive,
                        'weight'            => $w,
                        'quiz_score'        => $isClosed ? null : ($isPastCompleted ? null : round((float) ($c->quiz_score ?? 0), 1)),
                        'oral_score'        => $isClosed ? null : ($isPastCompleted ? null : round((float) ($c->oral_score ?? 0), 1)),
                        'exam_score'        => $isClosed ? null : ($isPastCompleted ? null : round((float) ($c->exam_score ?? 0), 1)),
                        'score'             => $isClosed ? null : ($isPastCompleted ? null : round((float) $c->total_score, 1)),
                        'max_score'         => (float) $maxScore,
                        'percentage'        => $isClosed ? null : ($isPastCompleted ? null : $pct),
                        'weighted_points'   => $isCurrentActive ? $weightedPts : 0,
                        'status'            => $status,
                    ];
                })->values();

            // احتساب المعدل التراكمي والحالة الأكاديمية اعتماداً على الفصل الساري حالياً بالمعهد
            $activeCourses = $stdCourses->where('is_current_active', true);
            $semGroups = $activeCourses->groupBy(function($c) {
                return $c['year'] . '_' . $c['semester_id'];
            });
            $semGpas = [];
            foreach ($semGroups as $coursesInSem) {
                $semW = $coursesInSem->sum('weight');
                $semPts = $coursesInSem->sum('weighted_points');
                if ($semW > 0) {
                    $semGpas[] = round($semPts / $semW, 2);
                }
            }
            // المعدل التراكمي النهائي: مجموع معدلات الفصول المنتهية المجتازة على عدد الفصول المجتازة
            // طالب السنة الأولى لديه فصل مجتاز واحد (المقام 1)
            // طالب السنة الثانية لديه 3 فصول مجتازة (فصلان من السنة الأولى + الفصل الساري إذا كان ناجحاً، المقام 3)
            $activePassedGpas = array_values(array_filter($semGpas, fn($g) => $g >= 50));
            $passedCount = $isYear1 ? count($activePassedGpas) : (2 + count($activePassedGpas));
            $sumPassed = array_sum($activePassedGpas);
            $weightedGpa = $passedCount > 0 ? round($sumPassed / $passedCount, 2) : 0;
            $sumWeight = $activeCourses->sum('weight');
            $failedCourses = $activeCourses->where('status', 'راسب')->pluck('title')->toArray();
            $failedCount = count($failedCourses);

            $levelStr = trim((string)$s->level);
            $isGrad = in_array($levelStr, ['خريج', 'graduate']) || $s->user_status === 'graduated';
            $isSupp = in_array($levelStr, ['دورة تكميلية', 'تكميلي']) || ($levelStr == 'السنة الثانية' && $failedCount > 0);

            $standing = 'passed';
            if ($isGrad) {
                $standing = 'graduated';
            } elseif ($isSupp) {
                $standing = 'supplementary';
            } elseif ($failedCount > 0) {
                $standing = 'failed';
            }

            $advKey = trim((string)$s->program_name) . '_' . trim((string)$s->level);
            $advisor = $advisors->get($advKey);

            return [
                'student_id'      => $s->student_id,
                'user_id'         => $s->user_id,
                'student_code'    => $s->student_code ?? '',
                'full_name'       => $s->full_name,
                'program_id'      => $s->program_id,
                'program_name'    => $s->program_name ?? 'عام',
                'department_id'   => $s->department_id,
                'department_name' => $s->department_name ?? 'عام',
                'level'           => $s->level ?? 'السنة الأولى',
                'joined_at'       => $s->joined_at ? \Carbon\Carbon::parse($s->joined_at)->format('Y-m-d') : '2026-09-01',
                'hod_name'        => $hods->get($s->department_id) ?? 'د. أحمد ديب (رئيس القسم)',
                'advisor_name'    => $advisor ? $advisor->full_name : 'أ. أحمد نصلى (المرشد الأكاديمي)',
                'status'          => $s->user_status ?? 'active',
                'courses'         => $stdCourses,
                'summary'         => [
                    'total_weight'   => $sumWeight,
                    'weighted_gpa'   => $weightedGpa,
                    'failed_count'   => $failedCount,
                    'failed_courses' => $failedCourses,
                    'standing'       => $standing,
                ],
            ];
        });

        // 8. تجهيز طلاب المواد للمنظور الأول (Course View)
        $courseStudents = DB::table('enrollments')
            ->join('students', 'enrollments.student_id', '=', 'students.student_id')
            ->join('users', 'students.user_id', '=', 'users.user_id')
            ->join('courses', 'enrollments.course_id', '=', 'courses.course_id')
            ->leftJoin('grade_events', function($join) {
                $join->on('courses.course_id', '=', 'grade_events.course_id');
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
                'courses.year',
                'courses.semester_id',
                DB::raw('COALESCE(SUM(grade_entries.score), 0) as exam_score'),
                DB::raw('COALESCE(SUM(CASE WHEN grade_events.type = "quiz" THEN grade_entries.score ELSE 0 END), 0) as quiz_score'),
                DB::raw('COALESCE(SUM(CASE WHEN grade_events.type = "oral" THEN grade_entries.score ELSE 0 END), 0) as oral_score'),
                DB::raw('COALESCE(SUM(CASE WHEN grade_events.type = "exam" THEN grade_entries.score ELSE 0 END), 0) as final_exam_score'),
                DB::raw('100 as exam_max_score')
            )
            ->groupBy('enrollments.course_id', 'enrollments.student_id', 'students.program_id', 'students.student_code', 'users.full_name', 'courses.weight', 'courses.year', 'courses.semester_id')
            ->get();

        return [
            'departments'  => $departments,
            'programs'     => $programs,
            'semesters'    => $semesters,
            'courses'      => $courses,
            'students'     => $courseStudents,
            'studentsList' => $studentsList,
        ];
    }

    public function courseWeights()
    {
        $data = $this->getCourseWeightsPayload();

        if (request()->wantsJson() || request()->ajax() || request()->has('api') || request()->is('api/*')) {
            return response()->json([
                'success' => true,
                'data'    => $data,
            ]);
        }

        return view('affairs.course_weights', compact('data'));
    }

    /**
     * اتخاذ القرار الأكاديمي للطالب (ترفيع، تخرج، دورة تكميلية، إعادة سنة)
     */
    public function studentAcademicDecision(Request $request)
    {
        $request->validate([
            'student_id' => 'required|exists:students,student_id',
            'decision'   => 'required|in:promote_semester_2,promote_year_2,promote_semester_4,graduate,supplementary,repeat_year',
            'notes'      => 'nullable|string|max:1000',
        ]);

        $student = Student::with('user')->findOrFail($request->student_id);
        $user = $student->user;
        $decision = $request->decision;
        $notes = $request->input('notes', '');

        $title = '';
        $message = '';
        $newLevel = '';
        $newStanding = '';

        if ($decision === 'promote_semester_2') {
            $newLevel = 'السنة الأولى - الفصل الثاني';
            $newStanding = 'passed';
            $student->update(['updated_at' => now()]);
            $title = 'مبروك! تم الترفيع إلى الفصل الثاني 📚';
            $message = 'قررت شؤون الطلاب ترفيعك بنجاح إلى الفصل الدراسي الثاني بعد استيفاء مقررات الفصل الأول.' . ($notes ? " ملاحظة: {$notes}" : '');
        } elseif ($decision === 'promote_year_2') {
            $newLevel = 'السنة الثانية';
            $newStanding = 'passed';
            $student->update(['level' => $newLevel, 'updated_at' => now()]);
            if ($user) {
                $user->update(['academic_year' => $newLevel]);
            }
            Student::autoEnrollCourses($student->student_id);
            $title = 'مبروك! تم الترفيع للسنة الثانية 🎓';
            $message = 'قررت شؤون الطلاب ترفيعك بنجاح إلى السنة الثانية وتسجيل مواد الفصل الجديد.' . ($notes ? " ملاحظة: {$notes}" : '');
        } elseif ($decision === 'promote_semester_4') {
            $newLevel = 'السنة الثانية - الفصل الرابع';
            $newStanding = 'passed';
            $student->update(['updated_at' => now()]);
            $title = 'مبروك! تم الترفيع إلى الفصل الرابع 📚✨';
            $message = 'قررت شؤون الطلاب ترفيعك بنجاح إلى الفصل الدراسي الرابع (فصل التخرج النهائي) بعد استيفاء مقررات الفصل الثالث.' . ($notes ? " ملاحظة: {$notes}" : '');
        } elseif ($decision === 'graduate') {
            $newLevel = 'خريج';
            $newStanding = 'graduated';
            $student->update(['level' => $newLevel, 'updated_at' => now()]);
            if ($user) {
                $user->update(['academic_year' => $newLevel, 'status' => 'graduated']);
            }
            $title = 'مبارك التخرج! 🎓✨';
            $message = 'اعتمدت شؤون الطلاب تخرجك الرسمي بنجاح من المعهد. نتمنى لك دوام التوفيق والنجاح.' . ($notes ? " ملاحظة: {$notes}" : '');
        } elseif ($decision === 'supplementary') {
            $newLevel = 'دورة تكميلية';
            $newStanding = 'supplementary';
            $student->update(['level' => $newLevel, 'updated_at' => now()]);
            $title = 'إشعار الدورة التكميلية 📝';
            $message = 'تم اعتماد إحالتك للدورة التكميلية في المواد غير المجتازة. يرجى مراجعة شؤون الطلاب.' . ($notes ? " ملاحظة: {$notes}" : '');
        } elseif ($decision === 'repeat_year') {
            $newLevel = 'راسب - إعادة سنة';
            $newStanding = 'failed';
            $student->update(['level' => $newLevel, 'updated_at' => now()]);
            $title = 'تنبيه أكاديمي - إعادة السنة ⚠️';
            $message = 'تم تثبيت حالة إعادة السنة الدراسية بناءً على النتائج والمعدل العام.' . ($notes ? " ملاحظة: {$notes}" : '');
        }

        // إشعار داخلي و FCM
        if ($user) {
            DB::table('notifications')->insert([
                'user_id'    => $user->user_id,
                'title'      => $title,
                'message'    => $message,
                'type'       => 'academic',
                'category'   => 'academic',
                'is_read'    => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            try {
                \App\Services\FcmService::sendToUser($user->user_id, $title, $message, ['type' => 'academic']);
            } catch (\Exception $e) {
                Log::warning("FCM failed: " . $e->getMessage());
            }
        }

        return response()->json([
            'success'      => true,
            'message'      => 'تم حفظ القرار الأكاديمي بنجاح وإشعار الطالب.',
            'new_level'    => $newLevel,
            'new_standing' => $newStanding,
        ]);
    }

    /**
     * تصدير نتائج طلاب مادة معينة (Excel أو PDF).
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
            ->leftJoin('grade_events', 'enrollments.course_id', '=', 'grade_events.course_id')
            ->leftJoin('grade_entries', function ($join) {
                $join->on('grade_events.id', '=', 'grade_entries.grade_event_id')
                     ->on('enrollments.student_id', '=', 'grade_entries.student_id');
            })
            ->where('enrollments.status', 'active')
            ->where('enrollments.course_id', $courseId)
            ->where('students.program_id', $programId)
            ->select(
                'users.full_name as student_name',
                'students.student_code',
                DB::raw('COALESCE(SUM(grade_entries.score), 0) as exam_score'),
                DB::raw('100 as exam_max_score')
            )
            ->groupBy('enrollments.student_id', 'users.full_name', 'students.student_code')
            ->get()
            ->map(function ($r) use ($course) {
                $percentage = $r->exam_max_score > 0 ? ($r->exam_score / $r->exam_max_score) * 100 : 0;
                return [
                    'code'       => $r->student_code ?? '-',
                    'name'       => $r->student_name,
                    'exam'       => number_format($r->exam_score, 1) . ' / ' . number_format($r->exam_max_score, 0),
                    'weight'     => $course->weight ?? 1,
                    'percentage' => round($percentage, 1),
                    'status'     => $percentage >= 50 ? 'ناجح' : 'راسب',
                ];
            });

        if ($status === 'pass') {
            $rows = $rows->where('status', 'ناجح')->values();
        } elseif ($status === 'fail') {
            $rows = $rows->where('status', 'راسب')->values();
        }

        $yearLabel = ($course->year ?? 1) == 1 ? 'السنة الأولى' : 'السنة الثانية';
        $title     = 'كشف ملتحقين ونتائج مادة: ' . ($course->title ?? '') . ' - ' . ($program->name ?? '');
        $columns   = ['الرقم الجامعي', 'اسم الطالب', 'علامة الامتحان', 'التثقيل', 'المعدل', 'الحالة'];
        $fileBase  = $this->sanitizeFileName('كشف_مادة_' . ($course->title ?? 'مادة') . '_' . ($program->name ?? ''));

        $meta = [
            'المادة' => ($course->title ?? '') . ' (وزن: ' . ($course->weight ?? 1) . ')',
            'التخصص / الفرع' => $program->name ?? '-',
            'السنة الدراسية' => $yearLabel,
            'إجمالي الطلاب' => $rows->count(),
            'نسبة النجاح' => $rows->count() > 0 ? round(($rows->where('status', 'ناجح')->count() / $rows->count()) * 100, 1) . '%' : '0%',
        ];

        return $request->format === 'excel'
            ? $this->downloadExcelTable($title, $columns, $rows, $fileBase, null, $meta)
            : $this->downloadPdfTable($title, $columns, $rows, $fileBase, null, $meta);
    }

/**
     * تصدير كشف علامات رسمي معتمد لطالب واحد بمطابقة تامة لنموذج معهد دمشق المتوسط (UNRWA / DTC)
     * مع الطابع المؤسساتي لمنظومة Edu-Bridge والاعتمادات الرسمية.
     */
    public function exportCourseWeightsStudent(Request $request)
    {
        $request->validate([
            'student_id' => 'required|integer|exists:students,student_id',
            'format'     => 'required|in:excel,pdf',
        ]);

        $studentId = (int) $request->student_id;
        $student = DB::table('students')
            ->join('users', 'students.user_id', '=', 'users.user_id')
            ->leftJoin('programs', 'students.program_id', '=', 'programs.id')
            ->leftJoin('departments', 'programs.department_id', '=', 'departments.department_id')
            ->where('students.student_id', $studentId)
            ->select(
                'students.student_id',
                'students.student_code',
                'students.birth_date',
                'users.full_name',
                'users.gender',
                'programs.name as program_name',
                'departments.name as department_name',
                'departments.department_id',
                'students.level',
                'users.academic_year',
                'users.created_at as joined_at'
            )
            ->first();

        if (!$student) {
            return back()->with('error', 'الطالب غير موجود');
        }

        $isYear1 = str_contains(trim((string)$student->level), 'الأولى');

        // جلب المقررات المسجلة مع تفاصيل المذاكرة والشفهي والامتحان
        $rawRows = DB::table('enrollments')
            ->join('courses', 'enrollments.course_id', '=', 'courses.course_id')
            ->leftJoin('grade_events', function ($join) {
                $join->on('courses.course_id', '=', 'grade_events.course_id');
            })
            ->leftJoin('grade_entries', function ($join) use ($studentId) {
                $join->on('grade_events.id', '=', 'grade_entries.grade_event_id')
                     ->on('enrollments.student_id', '=', 'grade_entries.student_id');
            })
            ->where('enrollments.status', 'active')
            ->where('enrollments.student_id', $studentId)
            ->select(
                'courses.course_id',
                'courses.title',
                'courses.weight',
                'courses.year',
                'courses.semester_id',
                DB::raw('COALESCE(SUM(grade_entries.score), 0) as total_score'),
                DB::raw('COALESCE(SUM(CASE WHEN grade_events.type = "quiz" THEN grade_entries.score ELSE 0 END), 0) as quiz_score'),
                DB::raw('COALESCE(SUM(CASE WHEN grade_events.type = "oral" THEN grade_entries.score ELSE 0 END), 0) as oral_score'),
                DB::raw('COALESCE(SUM(CASE WHEN grade_events.type = "exam" THEN grade_entries.score ELSE 0 END), 0) as exam_score')
            )
            ->groupBy('enrollments.course_id', 'courses.course_id', 'courses.title', 'courses.weight', 'courses.year', 'courses.semester_id')
            ->orderBy('courses.year')
            ->orderBy('courses.semester_id')
            ->orderBy('courses.course_id')
            ->get();

        $coursesList = [];
        $totalHours = 0;
        $totalPoints = 0;
        $failedCount = 0;
        $activePoints = 0;
        $activeHours = 0;

        foreach ($rawRows as $c) {
            $cYear = (int)($c->year ?? 1);
            $cSem = (int)($c->semester_id ?? 1);

            // طالب السنة الأولى لا تظهر له مقررات السنة الثانية إطلاقاً
            if ($isYear1 && $cYear > 1) {
                continue;
            }

            $isClosed = $isYear1 ? ($cYear === 1 && $cSem === 2) : ($cYear === 2 && $cSem === 2);
            $isPastCompleted = !$isYear1 && ($cYear === 1);
            $isCurrentActive = $isYear1 ? ($cYear === 1 && $cSem === 1) : ($cYear === 2 && $cSem === 1);

            $w = (float)($c->weight ?? 1);
            if ($w <= 0) $w = 1;
            $totalHours += $w;

            $semLabel = '';
            if ($cYear === 1 && $cSem === 1) $semLabel = 'الأول';
            elseif ($cYear === 1 && $cSem === 2) $semLabel = 'الثاني';
            elseif ($cYear === 2 && $cSem === 1) $semLabel = 'الثالث';
            elseif ($cYear === 2 && $cSem === 2) $semLabel = 'الرابع';

            if ($isCurrentActive) {
                $rawScore = (float)$c->total_score;
                $pct = min(100, max(0, round($rawScore, 1)));
                $pts = round($pct * $w, 2);
                $totalPoints += $pts;
                $activePoints += $pts;
                $activeHours += $w;

                $isPass = $pct >= 50;
                if (!$isPass) $failedCount++;

                $statusLabel = 'ناجح (مقبول)';
                if ($pct >= 85) $statusLabel = 'ناجح (ممتاز)';
                elseif ($pct >= 75) $statusLabel = 'ناجح (جيد جداً)';
                elseif ($pct >= 65) $statusLabel = 'ناجح (جيد)';
                elseif ($pct >= 50) $statusLabel = 'ناجح (مقبول)';
                else $statusLabel = 'راسب';

                $coursesList[] = [
                    'title'        => $c->title,
                    'sem_label'    => $semLabel,
                    'weight'       => $w,
                    'quiz_score'   => number_format((float)$c->quiz_score, 1),
                    'exam_score'   => number_format((float)$c->exam_score, 1),
                    'oral_score'   => number_format((float)$c->oral_score, 1),
                    'score'        => number_format($pct, 1),
                    'points'       => number_format($pts, 1),
                    'is_pass'      => $isPass,
                    'is_closed'    => false,
                    'is_past'      => false,
                    'status_label' => $statusLabel,
                ];
            } elseif ($isPastCompleted) {
                $coursesList[] = [
                    'title'        => $c->title,
                    'sem_label'    => $semLabel,
                    'weight'       => $w,
                    'quiz_score'   => '-',
                    'exam_score'   => '-',
                    'oral_score'   => '-',
                    'score'        => '-',
                    'points'       => '-',
                    'is_pass'      => true,
                    'is_closed'    => false,
                    'is_past'      => true,
                    'status_label' => 'مجتاز (العام الماضي)',
                ];
            } else {
                $coursesList[] = [
                    'title'        => $c->title,
                    'sem_label'    => $semLabel,
                    'weight'       => $w,
                    'quiz_score'   => '-',
                    'exam_score'   => '-',
                    'oral_score'   => '-',
                    'score'        => '-',
                    'points'       => '-',
                    'is_pass'      => null,
                    'is_closed'    => true,
                    'is_past'      => false,
                    'status_label' => 'مغلق المقرر',
                ];
            }
        }

        // معدل الفصل الساري
        $currentSemGpa = $activeHours > 0 ? round($activePoints / $activeHours, 2) : 0;

        // المعدل التراكمي النهائي وفق القاعدة الأكاديمية المعتمدة (مجموع الفصول المجتازة ÷ عدد الفصول المجتازة)
        $passedSemCount = $isYear1 ? ($currentSemGpa >= 50 ? 1 : 0) : (2 + ($currentSemGpa >= 50 ? 1 : 0));
        $cumGpa = $passedSemCount > 0 ? round($currentSemGpa / $passedSemCount, 2) : 0;

        $appreciation = 'مقبول';
        if ($cumGpa >= 85) $appreciation = 'ممتاز';
        elseif ($cumGpa >= 75) $appreciation = 'جيد جداً';
        elseif ($cumGpa >= 65) $appreciation = 'جيد';
        elseif ($cumGpa >= 50) $appreciation = 'مقبول';
        else $appreciation = 'راسب';

        $cumulativeStats = [
            'cum_gpa'      => number_format($cumGpa, 2),
            'appreciation' => $appreciation,
            'birth_date'   => $student->birth_date ? \Carbon\Carbon::parse($student->birth_date)->format('Y/m/d') : '2004/01/01',
            'birth_place'  => 'حمص',
            'nationality'  => 'الفلسطينية',
            'joined_at'    => $student->joined_at ? \Carbon\Carbon::parse($student->joined_at)->format('Y/m/d') : '2024/10/01',
            'plan_code'    => '72502 - ' . ($student->joined_at ? \Carbon\Carbon::parse($student->joined_at)->format('Y') : '2024'),
            'issue_date'   => now()->format('Y/m/d'),
        ];

        // نصوص القرار والتوجيهات بناءً على الفصول المنتهية
        $isSem2Active = collect($coursesList)->where('sem_label', 'الفصل الثاني')->where('is_closed', false)->count() > 0;
        $isSem4Active = collect($coursesList)->where('sem_label', 'الفصل الرابع')->where('is_closed', false)->count() > 0;

        $decisionTitle = '';
        $directivesText = '';
        if ($isYear1) {
            if ($failedCount === 0 && $currentSemGpa >= 50) {
                if ($isSem2Active) {
                    $decisionTitle = 'مرفّع بنجاح إلى السنة الدراسية الثانية';
                    $directivesText = 'استوفى الطالب متطلبات السنة الأولى كاملة بنجاح، ويحق له الانتقال الرسمي إلى السنة الدراسية الثانية وتسجيل مقرراتها.';
                } else {
                    $decisionTitle = 'مرفّع بنجاح إلى الفصل الدراسي الثاني';
                    $directivesText = 'استوفى الطالب شروط النجاح للفصل الدراسي الأول، ويحق له متابعة المسار الأكاديمي والتسجيل في الفصل الدراسي الثاني.';
                }
            } else {
                $decisionTitle = 'راسب في ' . $failedCount . ' مواد - قيد الاستدراك والتكميلية';
                $directivesText = 'يتوجب على الطالب استدراك المقررات غير المجتازة ومراجعة الكنترول الأكاديمي.';
            }
        } else {
            if ($failedCount === 0 && $currentSemGpa >= 50) {
                if ($isSem4Active) {
                    $decisionTitle = 'مستوفٍ لشروط التخرج (خريج رسمي معتمد 🎓)';
                    $directivesText = 'أتم الطالب كافة المتطلبات الأكاديمية والتدريبية المعتمدة في الخطة الدراسية (الفصول الأربعة كاملة). بريء الذمة نظامياً لاعتماد تخرجه الرسمي.';
                } else {
                    $decisionTitle = 'مرفّع بنجاح إلى الفصل الدراسي الرابع';
                    $directivesText = 'استوفى الطالب شروط النجاح للفصل الدراسي الثالث، ويحق له متابعة المسار الأكاديمي في الفصل الدراسي الرابع (الفصل النهائي والتخرج).';
                }
            } else {
                $decisionTitle = 'دورة تكميلية في ' . $failedCount . ' مواد';
                $directivesText = 'يحال الطالب للدورة التكميلية في المقررات غير المجتازة تمهيداً لاستكمال المسار الأكاديمي بعد استيفاء شروط النجاح.';
            }
        }

        // الأحرف الأولى
        $nameParts = preg_split('/\s+/', trim((string)$student->full_name));
        $studentInitials = (count($nameParts) >= 2) 
            ? (mb_substr($nameParts[0], 0, 1) . '.' . mb_substr($nameParts[count($nameParts) - 1], 0, 1))
            : (mb_substr($nameParts[0] ?? 'ط', 0, 1));

        $issueDateFormatted = now()->format('d-m-Y (h:i') . ' ' . (now()->format('A') === 'AM' ? 'ص' : 'م') . ')';
        $joinedAtFormatted = $student->joined_at ? \Carbon\Carbon::parse($student->joined_at)->format('d-m-Y') : '10-09-2024';
        $planCode = 'DTC-PLAN-' . ($isYear1 ? '2024/1' : '2023/2');
        $academicYearLabel = '2024 - 2025';

        $dtcLogoPath = public_path('images/logos/dtc.png');
        $dtcLogoBase64 = file_exists($dtcLogoPath) ? ('data:image/png;base64,' . base64_encode(file_get_contents($dtcLogoPath))) : '';

        $watermarkPath = public_path('images/logos/edubridge_clean.png');
        $watermarkBase64 = file_exists($watermarkPath) ? ('data:image/png;base64,' . base64_encode(file_get_contents($watermarkPath))) : '';

        $verificationHash = substr(md5($student->student_id . ($student->student_code ?? '') . 'edubridge_salt'), 0, 24) . 'dtc';

        if ($request->format === 'pdf') {
            return view('affairs.transcript_template', compact(
                'student',
                'isYear1',
                'coursesList',
                'totalHours',
                'totalPoints',
                'currentSemGpa',
                'passedSemCount',
                'cumulativeStats',
                'decisionTitle',
                'directivesText',
                'studentInitials',
                'issueDateFormatted',
                'joinedAtFormatted',
                'planCode',
                'academicYearLabel',
                'dtcLogoBase64',
                'watermarkBase64',
                'verificationHash'
            ));
        }

        // Excel format
        $columns = ['اسم المقرر الدراسي', 'الفصل', 'الساعات (W)', 'المذاكرة (25)', 'الامتحان (50)', 'الشفهي (25)', 'المجموع (100)', 'النقاط', 'الحالة والتقدير'];
        $flatRows = collect();
        foreach ($coursesList as $c) {
            $flatRows->push([
                'title'      => $c['title'],
                'semester'   => $c['sem_label'],
                'weight'     => $c['weight'],
                'quiz'       => $c['quiz_score'],
                'exam'       => $c['exam_score'],
                'oral'       => $c['oral_score'],
                'total'      => $c['score'],
                'points'     => $c['points'],
                'status'     => $c['status_label'],
            ]);
        }
        $footer = 'المعدل التراكمي العام: ' . $cumulativeStats['cum_gpa'] . '% (' . $cumulativeStats['appreciation'] . ') - عدد الفصول المجتازة: ' . $passedSemCount;
        $fileBase = $this->sanitizeFileName('كشف_علامات_' . ($student->full_name ?? 'طالب') . '_' . ($student->student_code ?? ''));

        return $this->downloadExcelTable('كشف علامات الطالب: ' . $student->full_name, $columns, $flatRows, $fileBase, $footer);
    }

    /**
     * توليد كود HTML بمطابقة تامة 100% لكشف علامات معهد دمشق المتوسط (DTC / UNRWA)
     * مع الطابع المؤسساتي لمنظومة Edu-Bridge.
     */
    private function buildOfficialDtcTranscriptHtml($student, array $semestersData, array $cumulativeStats): string
    {
        $unrwaLogo     = $this->logoImgTag('unrwa.png', 'UNRWA', 'height:48px; max-width:85px;');
        $dtcLogo       = $this->logoImgTag('dtc.png', 'Damascus Training Centre', 'height:45px; max-width:85px;');
        $edubridgeLogo = $this->logoImgTag('edubridge.png', 'EduBridge', 'height:40px; max-width:80px;');

        // جداول الفصول الدراسية
        $semestersHtml = '';
        foreach ($semestersData as $sem) {
            $coursesRows = '';
            foreach ($sem['courses'] as $c) {
                $coursesRows .= '<tr>'
                    . '<td style="border:1px solid #000; text-align:center; padding:3.5px 5px; font-family:monospace; font-size:8.5pt;">' . htmlspecialchars($c['code']) . '</td>'
                    . '<td style="border:1px solid #000; text-align:right; padding:3.5px 8px; font-weight:bold; font-size:8.5pt;">' . htmlspecialchars($c['title']) . '</td>'
                    . '<td style="border:1px solid #000; text-align:center; padding:3.5px 5px; font-size:8.5pt;">' . htmlspecialchars((string)$c['weight']) . '</td>'
                    . '<td style="border:1px solid #000; text-align:center; padding:3.5px 5px; font-weight:bold; font-size:9pt;">' . htmlspecialchars((string)$c['score']) . '</td>'
                    . '<td style="border:1px solid #000; text-align:center; padding:3.5px 5px; font-size:8pt; color:#b91c1c;">' . htmlspecialchars($c['remarks']) . '</td>'
                    . '</tr>';
            }

            $semestersHtml .= '
            <table class="sem-title-table" style="width:100%; margin-top:10px; margin-bottom:3px;">
                <tr>
                    <td style="border:none; text-align:right; width:35%;">
                        <b style="color:#004085; font-size:10.5pt;">' . htmlspecialchars($sem['semester_name']) . '</b>
                    </td>
                    <td style="border:none; text-align:center; width:65%;">
                        <b style="font-size:10.5pt;">العام الدراسي &nbsp; ' . htmlspecialchars($sem['academic_year']) . '</b>
                    </td>
                </tr>
            </table>

            <table style="border:1.5px solid #000; width:100%; border-collapse:collapse; margin-bottom:8px;">
                <thead>
                    <tr style="background-color:#f8fafc;">
                        <th style="border:1px solid #000; width:18%; padding:4.5px; font-size:8.5pt; font-weight:bold; text-align:center;">رقم المادة</th>
                        <th style="border:1px solid #000; width:45%; padding:4.5px 8px; font-size:8.5pt; font-weight:bold; text-align:right;">اسم المادة الرئيسية</th>
                        <th style="border:1px solid #000; width:14%; padding:4.5px; font-size:8.5pt; font-weight:bold; text-align:center;">الساعات المعتمدة</th>
                        <th style="border:1px solid #000; width:11%; padding:4.5px; font-size:8.5pt; font-weight:bold; text-align:center;">العلامة %</th>
                        <th style="border:1px solid #000; width:12%; padding:4.5px; font-size:8.5pt; font-weight:bold; text-align:center;">ملاحظات</th>
                    </tr>
                </thead>
                <tbody>
                    ' . $coursesRows . '
                </tbody>
                <tfoot>
                    <tr>
                        <td style="border:1px solid #000; font-size:8pt; font-weight:bold; text-align:center; background-color:#fff; padding:3px;">فصلي &nbsp;:</td>
                        <td colspan="4" style="border:1px solid #000; padding:2px 4px; background-color:#fff;">
                            <table style="width:100%; border:none;">
                                <tr>
                                    <td style="border:none; width:30%; text-align:right; font-size:7.8pt; font-weight:bold;">الساعات المعتمدة المسجلة &nbsp;:&nbsp; ' . $sem['sem_registered_hours'] . '</td>
                                    <td style="border:none; width:30%; text-align:right; font-size:7.8pt; font-weight:bold;">ساعات النجاح المعتمدة &nbsp;:&nbsp; ' . $sem['sem_passed_hours'] . '</td>
                                    <td style="border:none; width:22%; text-align:right; font-size:7.8pt; font-weight:bold;">المجموع &nbsp;:&nbsp; ' . $sem['sem_total_points'] . '</td>
                                    <td style="border:none; width:18%; text-align:left; font-size:7.8pt; font-weight:bold;">المعدل &nbsp;:&nbsp; ' . $sem['sem_gpa'] . '</td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                    <tr>
                        <td style="border:1px solid #000; font-size:8pt; font-weight:bold; text-align:center; background-color:#fff; padding:3px;">تراكمي &nbsp;:</td>
                        <td colspan="4" style="border:1px solid #000; padding:2px 4px; background-color:#fff;">
                            <table style="width:100%; border:none;">
                                <tr>
                                    <td style="border:none; width:30%; text-align:right; font-size:7.8pt; font-weight:bold;">الساعات المعتمدة المسجلة &nbsp;:&nbsp; ' . $sem['cum_registered_hours'] . '</td>
                                    <td style="border:none; width:30%; text-align:right; font-size:7.8pt; font-weight:bold;">ساعات النجاح المعتمدة &nbsp;:&nbsp; ' . $sem['cum_passed_hours'] . '</td>
                                    <td style="border:none; width:22%; text-align:right; font-size:7.8pt; font-weight:bold;">المجموع &nbsp;:&nbsp; ' . $sem['cum_total_points'] . '</td>
                                    <td style="border:none; width:18%; text-align:left; font-size:7.8pt; font-weight:bold;">المعدل &nbsp;:&nbsp; ' . $sem['cum_gpa'] . '</td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                </tfoot>
            </table>';
        }

        return '<html dir="rtl" xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
    <style>
        body {
            font-family: "DejaVu Sans", "Segoe UI", Tahoma, Arial, sans-serif;
            direction: rtl;
            text-align: right;
            margin: 0;
            padding: 8px 12px;
            font-size: 8.5pt;
            color: #000;
        }
        table { border-collapse: collapse; width: 100%; }
        .plain, .plain td, .plain th { border: none; padding: 2px; }
    </style>
</head>
<body>

    <!-- 1. ترويسة الوكالة والمعهد الرسمية ثلاثية الأعمدة -->
    <table style="border: 1.5px solid #000; width: 100%; margin-bottom: 5px;">
        <tr>
            <!-- العمود الأيمن (العربي) -->
            <td style="border: none; width: 34%; text-align: right; vertical-align: middle; padding: 5px 8px; line-height: 1.35;">
                <div style="font-size: 11pt; font-weight: bold; margin-bottom: 2px;">وكالة الأمم المتحدة</div>
                <div style="font-size: 8pt;">لإغاثة وتشغيل اللاجئين الفلسطينيين</div>
                <div style="font-size: 8pt;">في الشرق الأدنى</div>
                <div style="font-size: 8.5pt; font-weight: bold; margin-top: 3px;">برنامج التدريب الفني والمهني/سوريا</div>
                <div style="font-size: 9pt; font-weight: bold;">معهد دمشق المتوسط</div>
            </td>

            <!-- العمود الأوسط (الشعار المزدوج) -->
            <td style="border: none; width: 32%; text-align: center; vertical-align: middle; padding: 4px;">
                <div style="display: inline-block;">
                    ' . $unrwaLogo . '
                    ' . $dtcLogo . '
                </div>
                <div style="font-weight: bold; font-size: 10.5pt; letter-spacing: 1px; margin-top: 2px;">UNRWA</div>
                <div style="font-size: 7.5pt; font-weight: bold; color: #004085; margin-top: 1px;">Edu-Bridge Academic Control System</div>
            </td>

            <!-- العمود الأيسر (الإنجليزي) -->
            <td style="border: none; width: 34%; text-align: left; vertical-align: middle; padding: 5px 8px; line-height: 1.3; direction: ltr;">
                <div style="font-size: 10.5pt; font-weight: bold; letter-spacing: 0.5px; margin-bottom: 2px;">UNITED NATIONS</div>
                <div style="font-size: 7.5pt;">RELIEF AND WORKS AGENCY FOR</div>
                <div style="font-size: 7.5pt;">PALESTINE REFUGEES IN THE NEAR EAST</div>
                <div style="font-size: 8pt; font-weight: bold; margin-top: 3px;">Technical and Vocational Training Programme \Syria</div>
                <div style="font-size: 8.5pt; font-weight: bold;">Damascus Training Center</div>
            </td>
        </tr>
    </table>

    <!-- 2. عنوان الكشف وتاريخ الإصدار -->
    <div style="text-align: center; font-size: 13.5pt; font-weight: bold; text-decoration: underline; margin: 3px 0 2px;">كشف علامات الطالب</div>
    <div style="text-align: left; font-size: 8pt; font-weight: bold; margin-bottom: 3px;">
        تاريخ إصدار الشهادة &nbsp;:&nbsp; <span>' . htmlspecialchars($cumulativeStats['issue_date']) . '</span>
    </div>

    <!-- 3. بطاقة بيانات الطالب الرسمية -->
    <table style="border: 1.5px solid #000; width: 100%; margin-bottom: 4px; border-collapse: collapse;">
        <tr>
            <td colspan="2" style="background-color: #f1f5f9; border-bottom: 1.5px solid #000; padding: 4px 8px;">
                <table class="plain" style="width: 100%;">
                    <tr>
                        <td class="plain" style="width: 60%; font-size: 10pt; font-weight: bold; text-align: right;">
                            اسم الطالب &nbsp;:&nbsp; <span>' . htmlspecialchars($student->full_name) . '</span>
                        </td>
                        <td class="plain" style="width: 40%; font-size: 10pt; font-weight: bold; text-align: left; font-family: monospace;">
                            <span style="direction: ltr; display: inline-block;">' . htmlspecialchars($student->student_code ?? '-') . '</span> &nbsp;:&nbsp; رقم الطالب
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
        <tr>
            <td style="width: 50%; padding: 3px 8px; font-size: 8.5pt; border: none; text-align: right;">
                مكان الولادة &nbsp;:&nbsp; <span>' . htmlspecialchars($cumulativeStats['birth_place']) . '</span>
            </td>
            <td style="width: 50%; padding: 3px 8px; font-size: 8.5pt; border: none; text-align: left;">
                تاريخ الولادة &nbsp;:&nbsp; <span>' . htmlspecialchars($cumulativeStats['birth_date']) . '</span>
            </td>
        </tr>
        <tr>
            <td style="width: 50%; padding: 3px 8px; font-size: 8.5pt; border: none; text-align: right;">
                الجنسية &nbsp;:&nbsp; <span>' . htmlspecialchars($cumulativeStats['nationality']) . '</span>
            </td>
            <td style="width: 50%; padding: 3px 8px; font-size: 8.5pt; border: none; text-align: left;">
                تاريخ الالتحاق &nbsp;:&nbsp; <span>' . htmlspecialchars($cumulativeStats['joined_at']) . '</span>
            </td>
        </tr>
        <tr>
            <td style="width: 50%; padding: 3px 8px; font-size: 8.5pt; border: none; text-align: right;">
                التخصص &nbsp;:&nbsp; <span>' . htmlspecialchars($student->program_name ?? 'نظم المعلومات الحاسوبية') . '</span>
            </td>
            <td style="width: 50%; padding: 3px 8px; font-size: 8.5pt; border: none; text-align: left;">
                الخطة الدراسية &nbsp;:&nbsp; <span>' . htmlspecialchars($cumulativeStats['plan_code']) . '</span>
            </td>
        </tr>
        <tr>
            <td colspan="2" style="border-top: 1.5px solid #000; padding: 4px 8px; background-color: #fafafa;">
                <table class="plain" style="width: 100%;">
                    <tr>
                        <td class="plain" style="width: 50%; font-size: 9.5pt; font-weight: bold; text-align: right;">
                            المعدل التراكمي &nbsp;:&nbsp; <span style="font-size: 11pt;">' . htmlspecialchars($cumulativeStats['cum_gpa']) . '</span>
                        </td>
                        <td class="plain" style="width: 50%; font-size: 9.5pt; font-weight: bold; text-align: left;">
                            التقدير &nbsp;:&nbsp; <span style="font-size: 10.5pt;">' . htmlspecialchars($cumulativeStats['appreciation']) . '</span>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    <div style="border-bottom: 3px solid #000; margin-bottom: 10px;"></div>

    <!-- 4. جداول الفصول الدراسية والمقررات -->
    ' . $semestersHtml . '

    <!-- 5. خانة التوقيعات والاعتماد الرسمي المزدوج -->
    <table class="plain" style="width: 100%; margin-top: 25px;">
        <tr>
            <td class="plain" style="width: 50%; text-align: center; vertical-align: top;">
                <div style="font-size: 10pt; font-weight: bold; text-decoration: underline; margin-bottom: 5px;">العميد / المدير</div>
                <div style="font-size: 10pt; font-weight: bold;">' . htmlspecialchars($cumulativeStats['dean']) . '</div>
                <div style="font-size: 8pt; color: #64748b; margin-top: 25px;">التوقيع وخاتم العمادة: ............................</div>
            </td>
            <td class="plain" style="width: 50%; text-align: center; vertical-align: top;">
                <div style="font-size: 10pt; font-weight: bold; text-decoration: underline; margin-bottom: 5px;">المسجل</div>
                <div style="font-size: 10pt; font-weight: bold;">' . htmlspecialchars($cumulativeStats['registrar']) . '</div>
                <div style="font-size: 8pt; color: #64748b; margin-top: 25px;">التوقيع وخاتم المسجل: ............................</div>
            </td>
        </tr>
    </table>

    <!-- 6. خط الفوتر مع الهواتف -->
    <table class="plain" style="width: 100%; margin-top: 15px; border-top: 1.5px solid #000; padding-top: 4px; font-size: 7.5pt; font-weight: bold;">
        <tr>
            <td class="plain" style="width: 30%; text-align: right;">فاكس &nbsp;:&nbsp; +963116133035</td>
            <td class="plain" style="width: 40%; text-align: center; color: #004085;">منظومة Edu-Bridge للكنترول الأكاديمي والامتحانات</td>
            <td class="plain" style="width: 30%; text-align: left;">هاتف &nbsp;:&nbsp; +963116133035</td>
        </tr>
    </table>

    <!-- التنبيه القانوني الإلزامي -->
    <div style="margin-top: 6px; padding: 4px 8px; background-color: #fff1f2; border: 1px solid #fecdd3; border-radius: 4px; text-align: center; font-size: 6.8pt; color: #9f1239; line-height: 1.35;">
        <strong>تنبيه قانوني هام:</strong> تعتبر هذه الوثيقة مسودة إلكترونية صادرة وموثقة عبر نظام Edu-Bridge، وتفقد مصداقيتها وصلاحيتها القانونية والرسمية بشكل كامل عند طباعتها ورقياً ما لم تكن ممهورة بالختم الرسمي الحي والتوقيع المعتمد لكل من شؤون الطلاب وعمادة المعهد.
    </div>

</body>
</html>';
    }

    /**
     * إخراج مستند PDF
     */
    private function outputPdfDocument(string $html, string $fileName)
    {
        $pdfContent = null;

        if (class_exists('\Mpdf\Mpdf')) {
            try {
                $mpdf = new \Mpdf\Mpdf([
                    'mode' => 'utf-8',
                    'format' => 'A4',
                    'orientation' => 'P',
                    'margin_left' => 10,
                    'margin_right' => 10,
                    'margin_top' => 8,
                    'margin_bottom' => 8,
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
            'Content-Disposition' => 'inline; filename="' . $fileName . '"',
            'Content-Length' => strlen($pdfContent),
            'Cache-Control' => 'no-cache, no-store, must-revalidate',
            'Pragma' => 'no-cache',
            'Expires' => '0',
        ]);
    }

    public function exportCourseWeightsCohort(Request $request)
    {
        $departmentId = $request->input('department_id');
        $programId    = $request->input('program_id');
        $year         = $request->input('year', 'both');
        $semesterId   = $request->input('semester_id', 'both');
        $standing     = $request->input('standing', 'all');
        $format       = $request->input('format', 'pdf');

        $studentsQuery = DB::table('students')
            ->join('users', 'students.user_id', '=', 'users.user_id')
            ->leftJoin('programs', 'students.program_id', '=', 'programs.id')
            ->leftJoin('departments', 'programs.department_id', '=', 'departments.department_id')
            ->select(
                'students.student_id',
                'students.student_code',
                'users.full_name',
                'students.program_id',
                'programs.name as program_name',
                'departments.department_id',
                'departments.name as department_name',
                'students.level',
                'users.academic_year',
                'users.status as user_status'
            );

        if ($departmentId && $departmentId !== 'all') {
            $studentsQuery->where('departments.department_id', $departmentId);
        }
        if ($programId && $programId !== 'all') {
            $studentsQuery->where('students.program_id', $programId);
        }
        if ($year === '1') {
            $studentsQuery->where(function($q) {
                $q->where('students.level', 'like', '%الأولى%')->orWhere('users.academic_year', 'like', '%الأولى%');
            });
        } elseif ($year === '2') {
            $studentsQuery->where(function($q) {
                $q->where('students.level', 'like', '%الثانية%')->orWhere('users.academic_year', 'like', '%الثانية%');
            });
        }

        $rawStudents = $studentsQuery->get();

        $studentGrades = DB::table('enrollments')
            ->join('courses', 'enrollments.course_id', '=', 'courses.course_id')
            ->leftJoin('grade_events', 'courses.course_id', '=', 'grade_events.course_id')
            ->leftJoin('grade_entries', function($join) {
                $join->on('grade_events.id', '=', 'grade_entries.grade_event_id')
                     ->on('enrollments.student_id', '=', 'grade_entries.student_id');
            })
            ->where('enrollments.status', 'active');

        if ($semesterId !== 'both' && !empty($semesterId)) {
            $studentGrades->where('courses.semester_id', $semesterId);
        }

        $gradesData = $studentGrades->select(
            'enrollments.student_id',
            'courses.course_id',
            'courses.title',
            'courses.year',
            'courses.semester_id',
            'courses.weight',
            DB::raw('COALESCE(SUM(grade_entries.score), 0) as total_score')
        )
        ->groupBy('enrollments.student_id', 'courses.course_id', 'courses.title', 'courses.year', 'courses.semester_id', 'courses.weight')
        ->get()
        ->groupBy('student_id');

        $filteredStudentsList = [];
        $excelRows = collect();

        foreach ($rawStudents as $s) {
            $isYear1 = str_contains(trim((string)$s->level), 'الأولى');
            $allStudentCourses = $gradesData->get($s->student_id, collect());

            // المقررات السارية التقييمية
            $activeCourses = $allStudentCourses->filter(function($c) use ($isYear1) {
                $cYear = (int)($c->year ?? 1);
                $cSem = (int)($c->semester_id ?? 1);
                return $isYear1 ? ($cYear === 1 && $cSem === 1) : ($cYear === 2 && $cSem === 1);
            });

            $sumWeight = 0;
            $sumPoints = 0;
            $failedCourses = [];

            foreach ($activeCourses as $c) {
                $pct = min(100, max(0, (float)$c->total_score));
                $w = (float)($c->weight ?? 1);
                if ($w <= 0) $w = 1;
                $sumWeight += $w;
                $sumPoints += ($pct * $w);
                if ($pct < 50) {
                    $failedCourses[] = $c->title;
                }
            }

            $activeSemGpa = $sumWeight > 0 ? round($sumPoints / $sumWeight, 2) : 0;
            // المعادلة المعتمدة: طالب السنة الأولى مقامه 1، طالب السنة الثانية مقامه 3
            $cumGpa = $isYear1 ? $activeSemGpa : round($activeSemGpa / 3, 2);
            $failedCount = count($failedCourses);
            $failedCoursesTitles = $failedCount === 0 ? 'لا يوجد (مستوفٍ المقررات كافة)' : implode('، ', $failedCourses);

            // هل التقرير يفرز الفصل الثاني (نهاية السنة الأولى أو نهاية السنة الثانية)؟
            $isSem2Cohort = ($semesterId === '2');

            // تحديد القرار والوسام
            $decisionType = 'pass';
            $decisionLabel = '';
            if ($failedCount === 0) {
                if ($cumGpa >= 90) {
                    $decisionType = 'honor';
                    if ($isYear1) {
                        $decisionLabel = $isSem2Cohort ? 'ترفيع بمرتبة شرف للسنة الثانية' : 'ترفيع بمرتبة شرف للفصل الثاني';
                    } else {
                        $decisionLabel = $isSem2Cohort ? 'تخرج رسمي بمرتبة الشرف الأولى 🎓' : 'ترفيع بمرتبة شرف للفصل الرابع';
                    }
                } else {
                    $decisionType = 'pass';
                    if ($isYear1) {
                        $decisionLabel = $isSem2Cohort ? 'ترفيع نظامي إلى السنة الثانية' : 'ترفيع نظامي إلى الفصل الثاني';
                    } else {
                        $decisionLabel = $isSem2Cohort ? 'تخرج نظامي معتمد 🎓' : 'ترفيع نظامي إلى الفصل الرابع';
                    }
                }
            } else {
                if ($failedCount <= 4) {
                    $decisionType = 'supplementary';
                    $decisionLabel = 'تحويل لدورة تكميلية واستدراك';
                } else {
                    $decisionType = 'fail';
                    $decisionLabel = $isYear1 ? 'رسوب وإعادة السنة الأولى' : 'رسوب وإعادة السنة الثانية';
                }
            }

            // تطبيق فلترة الحالة الأكاديمية
            if ($standing !== 'all') {
                if ($standing === 'passed' && $failedCount > 0) continue;
                if ($standing === 'failed' && $failedCount === 0) continue;
                if ($standing === 'supplementary' && ($isYear1 || $failedCount === 0)) continue;
            }

            $studentItem = [
                'student_id'            => $s->student_id,
                'student_code'          => $s->student_code ?? '-',
                'full_name'             => $s->full_name,
                'program_name'          => $s->program_name ?? 'عام',
                'department_name'       => $s->department_name ?? 'عام',
                'level'                 => $s->level ?? 'السنة الأولى',
                'is_year_1'             => $isYear1,
                'total_hours'           => $sumWeight,
                'cum_gpa'               => $cumGpa,
                'failed_count'          => $failedCount,
                'failed_courses_titles' => $failedCoursesTitles,
                'decision_type'         => $decisionType,
                'decision_label'        => $decisionLabel,
            ];

            $filteredStudentsList[] = $studentItem;

            $excelRows->push([
                'name'    => $s->full_name,
                'code'    => $s->student_code ?? '-',
                'program' => $s->program_name ?? '-',
                'level'   => $s->level ?? 'السنة الأولى',
                'gpa'     => $cumGpa . '%',
                'failed'  => $failedCoursesTitles,
                'status'  => $decisionLabel,
            ]);
        }

        $deptName = ($departmentId && $departmentId !== 'all') ? (DB::table('departments')->where('department_id', $departmentId)->value('name') ?? 'كل الأقسام') : 'جميع الأقسام';
        $progName = ($programId && $programId !== 'all') ? (DB::table('programs')->where('id', $programId)->value('name') ?? 'كل التخصصات') : 'جميع التخصصات';
        $yearLabel = $year === '1' ? 'السنة الأولى' : ($year === '2' ? 'السنة الثانية' : 'كافة السنوات');
        $semesterLabel = $semesterId === 'both' ? 'كافة الفصول' : ('الفصل ' . $semesterId);

        // إحصائيات الدفعة (KPIs)
        $totalStudentsCount  = count($filteredStudentsList);
        $passedStudentsCount = count(array_filter($filteredStudentsList, fn($st) => $st['failed_count'] === 0));
        $supplementaryCount  = count(array_filter($filteredStudentsList, fn($st) => $st['decision_type'] === 'supplementary'));
        $repeatCount         = count(array_filter($filteredStudentsList, fn($st) => $st['decision_type'] === 'fail'));
        $failedTotalCount    = $supplementaryCount + $repeatCount;
        $passRate            = $totalStudentsCount > 0 ? round(($passedStudentsCount / $totalStudentsCount) * 100, 2) : 0;
        $gpaValues           = array_column($filteredStudentsList, 'cum_gpa');
        $maxGpa              = !empty($gpaValues) ? max($gpaValues) : 0;
        $minGpa              = !empty($gpaValues) ? min($gpaValues) : 0;
        $avgGpa              = !empty($gpaValues) ? round(array_sum($gpaValues) / count($gpaValues), 2) : 0;

        $academicYearLabel  = '2024 - 2025';
        $yearScopeLabel     = $year === '1' ? 'السنة الأولى (الدفعة المستجدة)' : ($year === '2' ? 'السنة الثانية' : 'كافة السنوات (الأولى والثانية)');
        $semesterScopeLabel = $semesterId === '1' ? 'الفصل الأول' : ($semesterId === '2' ? 'الفصل الثاني' : 'الفصلان (الأول والثاني التراكمي)');
        $standingScopeLabel = $standing === 'all' ? 'جميع الحالات' : ($standing === 'passed' ? 'الناجحون والمترفعون' : ($standing === 'supplementary' ? 'الدورة التكميلية' : 'الراسبون'));
        $reportNumber       = 'DTC-COHORT-' . date('Y') . '/REG-' . str_pad($programId && $programId !== 'all' ? $programId : '04', 2, '0', STR_PAD_LEFT);
        $issueDateFormatted = now()->format('d-m-Y (h:i') . ' ' . (now()->format('A') === 'AM' ? 'ص' : 'م') . ')';

        $dtcLogoPath   = public_path('images/logos/dtc.png');
        $dtcLogoBase64 = file_exists($dtcLogoPath) ? ('data:image/png;base64,' . base64_encode(file_get_contents($dtcLogoPath))) : '';

        $watermarkPath   = public_path('images/logos/edubridge_clean.png');
        $watermarkBase64 = file_exists($watermarkPath) ? ('data:image/png;base64,' . base64_encode(file_get_contents($watermarkPath))) : '';

        $verificationHash = 'DTC-' . substr(md5(json_encode($request->all()) . 'edubridge_cohort_salt'), 0, 16) . '-ARCH';

        // تجهيز قائمة الكادر الإداري والأكاديمي بالأسماء والمناصب للمشاركة المحددة
        $academicStaffList = collect();

        // 1. العمادة وإدارة المعهد
        $admins = DB::table('users')
            ->where('role_id', 1)
            ->where('status', 'active')
            ->select('user_id', 'full_name', 'department')
            ->get()
            ->map(function ($u) {
                return (object)[
                    'user_id'        => $u->user_id,
                    'name'           => $u->full_name,
                    'position'       => 'عمادة وإدارة المعهد المركزية',
                    'category'       => 'admin',
                    'category_label' => 'العمادة والإدارة',
                    'badge_class'    => 'bg-purple-100 text-purple-800 dark:bg-purple-900/40 dark:text-purple-300',
                ];
            });
        $academicStaffList = $academicStaffList->concat($admins);

        // 2. رؤساء الأقسام الأكاديمية
        $hods = DB::table('users')
            ->join('heads', 'users.user_id', '=', 'heads.user_id')
            ->leftJoin('departments', 'heads.department_id', '=', 'departments.department_id')
            ->where('users.role_id', 5)
            ->where('users.status', 'active')
            ->select('users.user_id', 'users.full_name', 'departments.name as dept_name', 'users.department')
            ->get()
            ->map(function ($u) {
                $dept = $u->dept_name ?? $u->department ?? 'الأكاديمي';
                return (object)[
                    'user_id'        => $u->user_id,
                    'name'           => $u->full_name,
                    'position'       => 'رئيس قسم ' . $dept,
                    'category'       => 'hod',
                    'category_label' => 'رؤساء الأقسام الأكاديمية',
                    'badge_class'    => 'bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-300',
                ];
            });
        $academicStaffList = $academicStaffList->concat($hods);

        // 3. المعلمون ومدرسو المقررات
        $teachers = DB::table('users')
            ->leftJoin('teachers', 'users.user_id', '=', 'teachers.user_id')
            ->where('users.role_id', 2)
            ->where('users.status', 'active')
            ->select('users.user_id', 'users.full_name', 'users.department', 'teachers.specialization')
            ->get()
            ->map(function ($u) {
                $pos = 'أستاذ ومدرس';
                if ($u->department) {
                    $pos .= ' بقسم ' . $u->department;
                }
                if ($u->specialization) {
                    $pos .= ' (' . $u->specialization . ')';
                }
                return (object)[
                    'user_id'        => $u->user_id,
                    'name'           => $u->full_name,
                    'position'       => $pos,
                    'category'       => 'teachers',
                    'category_label' => 'المعلمون ومدرسو المقررات',
                    'badge_class'    => 'bg-blue-100 text-blue-800 dark:bg-blue-900/40 dark:text-blue-300',
                ];
            });
        $academicStaffList = $academicStaffList->concat($teachers);

        if ($format === 'pdf') {
            return view('affairs.cohort_results_template', compact(
                'filteredStudentsList',
                'academicStaffList',
                'deptName',
                'progName',
                'year',
                'yearLabel',
                'semesterId',
                'standing',
                'totalStudentsCount',
                'passedStudentsCount',
                'supplementaryCount',
                'repeatCount',
                'failedTotalCount',
                'passRate',
                'maxGpa',
                'minGpa',
                'avgGpa',
                'academicYearLabel',
                'yearScopeLabel',
                'semesterScopeLabel',
                'standingScopeLabel',
                'reportNumber',
                'issueDateFormatted',
                'dtcLogoBase64',
                'watermarkBase64',
                'verificationHash'
            ));
        }

        // Excel format
        $title    = 'المحضر العام لنتائج واعتمادات الدفعة الدراسية - ' . $progName;
        $columns  = ['اسم الطالب', 'الرقم الجامعي', 'التخصص', 'السنة/المستوى', 'المعدل التراكمي الموزون', 'المقررات غير المستوفاة', 'الحالة والقرار الأكاديمي'];
        $footer   = 'إجمالي عدد طلاب الدفعة المعتمدين في المحضر: ' . $excelRows->count() . ' - متوسط المعدل العام: ' . $avgGpa . '%';
        $fileBase = $this->sanitizeFileName('محضر_نتائج_الدفعة_' . $progName . '_' . $yearLabel);

        $dossier = [
            'القسم العلمي'       => $deptName,
            'الفرع / التخصص'     => $progName,
            'السنة الأكاديمية'   => $yearLabel,
            'الفصل الدراسي'      => $semesterLabel,
            'فلتر الحالة'        => $standingScopeLabel,
            'إجمالي الطلاب'      => $excelRows->count(),
            'نسبة النجاح'        => $passRate . '%',
            'تاريخ إصدار المحضر' => now()->format('Y-m-d H:i'),
        ];

        return $this->downloadExcelTable($title, $columns, $excelRows, $fileBase, $footer, $dossier);
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
     * يحوّل ملف شعار إلى data URI مضمّن بالـ HTML
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
     * HTML موحّد للـ PDF بكافة المعايير المؤسساتية والاعتمادات والأختام والتنبيه القانوني
     */
    private function buildResultsHtml(string $title, array $columns, $rows, ?string $footer = null, ?array $dossier = null): string
    {
        $headerCells = '';
        foreach ($columns as $col) {
            $headerCells .= '<th>' . htmlspecialchars($col) . '</th>';
        }

        $bodyRows = '';
        foreach ($rows as $row) {
            $statusText = (string)($row['status'] ?? '');
            $isFail = str_contains($statusText, 'راسب') || str_contains($statusText, 'تكميلية');
            $rowClass = $isFail ? 'fail' : 'pass';

            $bodyRows .= '<tr>';
            foreach ($row as $cellKey => $cellVal) {
                $cellClass = ($cellKey === 'status') ? $rowClass : '';
                $bodyRows .= '<td class="' . $cellClass . '">' . htmlspecialchars((string)$cellVal) . '</td>';
            }
            $bodyRows .= '</tr>';
        }

        $colCount = count($columns);
        $footerHtml = $footer ? '<tr><td colspan="' . $colCount . '" class="footer-row"><b>' . htmlspecialchars($footer) . '</b></td></tr>' : '';

        $activeSemester = DB::table('semesters')->where('is_active', 1)->value('name') ?? 'الفصل الدراسي الحالي';

        $logoStyle     = 'max-height:50px; max-width:110px;';
        $edubridgeLogo = $this->logoImgTag('edubridge.png', 'EduBridge', $logoStyle);
        $unrwaLogo     = $this->logoImgTag('unrwa.png', 'UNRWA', $logoStyle);
        $dtcLogo       = $this->logoImgTag('dtc.png', 'Damascus Training Centre', $logoStyle);

        // جدول البطاقة المؤسساتية إن وجدت
        $dossierHtml = '';
        if (!empty($dossier)) {
            $dossierHtml .= '<table style="width: 100%; margin-bottom: 12px; background-color: #f8fafc; border: 1px solid #cbd5e1; border-radius: 6px;"><tbody>';
            $dossierPairs = [];
            foreach ($dossier as $k => $v) {
                $dossierPairs[] = ['k' => $k, 'v' => $v];
            }
            for ($i = 0; $i < count($dossierPairs); $i += 2) {
                $dossierHtml .= '<tr>';
                $dossierHtml .= '<td class="plain" style="width: 20%; font-weight: bold; color: #1e293b; background-color: #f1f5f9; padding: 6px 10px; border: 1px solid #e2e8f0; font-size: 9pt;">' . htmlspecialchars($dossierPairs[$i]['k']) . ':</td>';
                $dossierHtml .= '<td class="plain" style="width: 30%; color: #0f172a; padding: 6px 10px; border: 1px solid #e2e8f0; font-size: 9.5pt;">' . htmlspecialchars($dossierPairs[$i]['v']) . '</td>';
                if (isset($dossierPairs[$i+1])) {
                    $dossierHtml .= '<td class="plain" style="width: 20%; font-weight: bold; color: #1e293b; background-color: #f1f5f9; padding: 6px 10px; border: 1px solid #e2e8f0; font-size: 9pt;">' . htmlspecialchars($dossierPairs[$i+1]['k']) . ':</td>';
                    $dossierHtml .= '<td class="plain" style="width: 30%; color: #0f172a; padding: 6px 10px; border: 1px solid #e2e8f0; font-size: 9.5pt;">' . htmlspecialchars($dossierPairs[$i+1]['v']) . '</td>';
                } else {
                    $dossierHtml .= '<td colspan="2" class="plain" style="border: 1px solid #e2e8f0;"></td>';
                }
                $dossierHtml .= '</tr>';
            }
            $dossierHtml .= '</tbody></table>';
        }

        return '<html dir="rtl" xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns="http://www.w3.org/TR/REC-html40">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
    <style>
        body { font-family: "DejaVu Sans", "Segoe UI", Tahoma, Arial, sans-serif; direction: rtl; text-align: right; margin: 0; padding: 0; }
        .doc-frame { border: 2px solid #0f172a; padding: 14px; border-radius: 4px; }
        table { border-collapse: collapse; width: 100%; }
        th, td { border: 1px solid #94a3b8; padding: 7px 8px; text-align: center; vertical-align: middle; font-size: 9pt; }
        th { background-color: #0f172a; color: #ffffff; font-weight: bold; font-size: 9.5pt; }
        .plain, .plain td, .plain th { border: none; padding: 3px; }
        .letterhead-name { font-size: 13pt; font-weight: bold; color: #0f172a; }
        .letterhead-sub { font-size: 9pt; color: #475569; }
        .doc-title { font-size: 14pt; font-weight: bold; color: #ffffff; background-color: #0f172a; text-align: center; padding: 10px; }
        .doc-subtitle { font-size: 10pt; font-weight: normal; color: #e2e8f0; }
        .meta-row { text-align: center; font-size: 8.5pt; color: #475569; background-color: #f1f5f9; padding: 6px; }
        .pass { color: #15803d; font-weight: bold; background-color: #dcfce7; }
        .fail { color: #b91c1c; font-weight: bold; background-color: #fee2e2; }
        .footer-row { background-color: #fef9c3; text-align: center; font-size: 10pt; padding: 9px; color: #854d0e; }
        .stamp-box { border: 1.5px dashed #64748b; border-radius: 6px; padding: 10px; text-align: center; vertical-align: top; }
        .stamp-title { font-weight: bold; font-size: 10pt; color: #0f172a; margin-bottom: 4px; }
        .stamp-sub { font-size: 8pt; color: #64748b; margin-bottom: 36px; }
        .stamp-line { font-size: 8.5pt; color: #334155; border-top: 1px solid #cbd5e1; padding-top: 4px; width: 80%; margin: 0 auto; }
        .legal-notice { margin-top: 18px; padding: 8px 12px; background-color: #fff1f2; border: 1px solid #fecdd3; border-radius: 4px; text-align: center; font-size: 8pt; color: #9f1239; line-height: 1.5; }
    </style>
</head>
<body>
<div class="doc-frame">
    <table class="plain">
        <tr>
            <td class="plain" style="width: 30%; text-align: right; vertical-align: middle;">' . $unrwaLogo . '</td>
            <td class="plain" style="width: 40%; text-align: center; vertical-align: middle;">
                <div class="letterhead-name">معهد دمشق المتوسط (DTC)</div>
                <div class="letterhead-sub">مديرية شؤون الطلاب والامتحانات - نظام الكنترول الأكاديمي</div>
            </td>
            <td class="plain" style="width: 30%; text-align: left; vertical-align: middle;">' . $edubridgeLogo . ' ' . $dtcLogo . '</td>
        </tr>
    </table>

    <div style="border-bottom: 2px solid #0f172a; margin: 8px 0 12px;"></div>

    ' . $dossierHtml . '

    <table>
        <tr><td colspan="' . $colCount . '" class="doc-title">وثيقة كشف درجات واعتماد رسمي<br><span class="doc-subtitle">' . htmlspecialchars($title) . '</span></td></tr>
        <tr><td colspan="' . $colCount . '" class="meta-row">الفصل الدراسي: ' . htmlspecialchars($activeSemester) . ' &nbsp;|&nbsp; تاريخ التوليد الإلكتروني: ' . now()->format('Y-m-d H:i') . '</td></tr>
        <thead><tr>' . $headerCells . '</tr></thead>
        <tbody>' . $bodyRows . $footerHtml . '</tbody>
    </table>

    <!-- خانة الاعتماد المزدوجة الرسمية -->
    <table class="plain" style="width: 100%; margin-top: 25px;">
        <tr>
            <td class="plain" style="width: 50%; padding-left: 10px;">
                <div class="stamp-box">
                    <div class="stamp-title">اعتماد وتوقيع شؤون الطلاب</div>
                    <div class="stamp-sub">تدقيق السجلات والدرجات الأكاديمية</div>
                    <div class="stamp-line">التوقيع والختم الحي: .......................................</div>
                </div>
            </td>
            <td class="plain" style="width: 50%; padding-right: 10px;">
                <div class="stamp-box">
                    <div class="stamp-title">اعتماد عمادة المعهد / الإدارة العليا</div>
                    <div class="stamp-sub">المصادقة الرسمية والاعتماد النهائي للوثيقة</div>
                    <div class="stamp-line">التوقيع وخاتم العمادة: .......................................</div>
                </div>
            </td>
        </tr>
    </table>

    <!-- التنبيه القانوني الإلزامي الصريح -->
    <div class="legal-notice">
        <strong>تنبيه قانوني هام:</strong> تعتبر هذه الوثيقة مسودة إلكترونية، وتفقد مصداقيتها وصلاحيتها القانونية والرسمية بشكل كامل عند طباعتها ورقياً ما لم تكن ممهورة بالختم الرسمي الحي والتوقيع المعتمد لكل من شؤون الطلاب والإدارة العليا للمعهد.
    </div>
</div>
</body>
</html>';
    }

    /**
     * HTML مخصّص لتصدير Excel
     */
    private function buildExcelHtml(string $title, array $columns, $rows, ?string $footer = null, ?array $dossier = null): string
    {
        $colCount = count($columns);

        $headerCells = '';
        foreach ($columns as $col) {
            $headerCells .= '<th>' . htmlspecialchars($col) . '</th>';
        }

        $bodyRows = '';
        foreach ($rows as $row) {
            $statusText = (string)($row['status'] ?? '');
            $isFail = str_contains($statusText, 'راسب') || str_contains($statusText, 'تكميلية');
            $rowClass = $isFail ? 'fail' : 'pass';

            $bodyRows .= '<tr>';
            foreach ($row as $cellKey => $cellVal) {
                $cellClass = ($cellKey === 'status') ? $rowClass : '';
                $bodyRows .= '<td class="' . $cellClass . '">' . htmlspecialchars((string)$cellVal) . '</td>';
            }
            $bodyRows .= '</tr>';
        }

        $footerHtml = $footer ? '<tr><td colspan="' . $colCount . '" class="footer-row"><b>' . htmlspecialchars($footer) . '</b></td></tr>' : '';

        $dossierRows = '';
        if (!empty($dossier)) {
            foreach ($dossier as $k => $v) {
                $dossierRows .= '<tr><td style="font-weight:bold; background-color:#f1f5f9;">' . htmlspecialchars($k) . '</td><td colspan="' . ($colCount - 1) . '">' . htmlspecialchars((string)$v) . '</td></tr>';
            }
        }

        $activeSemester = DB::table('semesters')->where('is_active', 1)->value('name') ?? '-';

        return '<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns="http://www.w3.org/TR/REC-html40">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
    <style>
        body { font-family: "Segoe UI", Tahoma, Arial, sans-serif; direction: rtl; text-align: right; }
        table { border-collapse: collapse; width: 100%; }
        th, td { border: 1px solid #94a3b8; padding: 8px; text-align: center; vertical-align: middle; font-size: 10pt; }
        th { background-color: #0f172a; color: #ffffff; font-weight: bold; font-size: 10pt; }
        .institution-row { font-size: 11pt; font-weight: bold; color: #0f172a; background-color: #f1f5f9; text-align: center; padding: 8px; }
        .header-title { font-size: 15pt; font-weight: bold; color: #ffffff; background-color: #0f172a; text-align: center; padding: 12px; }
        .subtitle-row { font-size: 11pt; color: #334155; text-align: center; padding: 8px; }
        .meta-row { font-size: 9pt; color: #475569; background-color: #f1f5f9; text-align: center; padding: 6px; }
        .pass { color: #15803d; font-weight: bold; background-color: #dcfce7; }
        .fail { color: #b91c1c; font-weight: bold; background-color: #fee2e2; }
        .footer-row { background-color: #fef9c3; text-align: center; font-size: 11pt; padding: 10px; }
    </style>
</head>
<body>
    <table>
        <tr><td colspan="' . $colCount . '" class="institution-row">معهد دمشق المتوسط (DTC) - شؤون الطلاب والكنترول</td></tr>
        <tr><td colspan="' . $colCount . '" class="header-title">كشف درجات واعتماد رسمي</td></tr>
        <tr><td colspan="' . $colCount . '" class="subtitle-row">' . htmlspecialchars($title) . '</td></tr>
        <tr><td colspan="' . $colCount . '" class="meta-row">الفصل الدراسي: ' . htmlspecialchars($activeSemester) . '  |  تاريخ الإصدار: ' . now()->format('Y-m-d H:i') . '</td></tr>
        ' . $dossierRows . '
        <thead><tr>' . $headerCells . '</tr></thead>
        <tbody>' . $bodyRows . $footerHtml . '</tbody>
    </table>
</body>
</html>';
    }

    private function downloadExcelTable(string $title, array $columns, $rows, string $fileBase, ?string $footer = null, ?array $dossier = null)
    {
        $html = $this->buildExcelHtml($title, $columns, $rows, $footer, $dossier);
        $fileName = $fileBase . '_' . now()->format('Y-m-d') . '.xls';

        return response($html, 200, [
            'Content-Type' => 'application/vnd.ms-excel; charset=utf-8',
            'Content-Disposition' => 'attachment; filename="' . $fileName . '"',
        ]);
    }

    private function downloadPdfTable(string $title, array $columns, $rows, string $fileBase, ?string $footer = null, ?array $dossier = null)
    {
        $html = $this->buildResultsHtml($title, $columns, $rows, $footer, $dossier);
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

        // إذا كان نوع الطلب تحديث صورة بصمة الوجه
        if ($studentReq->type === 'face_photo') {
            $studentReq->status = $request->decision === 'approved' ? 'approved' : 'rejected';
            $studentReq->save();

            $student = $studentReq->student;
            if ($student && $request->decision === 'approved') {
                $photoPath = null;
                if ($request->hasFile('photo')) {
                    $request->validate([
                        'photo' => 'image|mimes:jpeg,png,jpg,webp|max:5120',
                    ]);
                    $photoPath = $request->file('photo')->store('photo_requests', 'public');
                } else {
                    $raw = $studentReq->details;
                    $decoded = json_decode($raw, true);
                    if (is_array($decoded) && !empty($decoded['photo'])) {
                        $photoPath = $decoded['photo'];
                    }
                }

                if ($photoPath) {
                    DB::table('users')->where('user_id', $student->user_id)->update(['avatar' => $photoPath]);
                    DB::table('students')->where('user_id', $student->user_id)->update(['reference_photo' => $photoPath]);
                    DB::table('photo_change_requests')->where('user_id', $student->user_id)->where('status', 'pending')->update(['status' => 'approved', 'updated_at' => now()]);
                }

                \App\Models\Notification::create([
                    'user_id' => $student->user_id,
                    'title'   => 'تمت الموافقة على تغيير صورة بصمة الوجه',
                    'message' => 'وافقت شؤون الطلاب على طلب تحديث صورة بصمة الوجه الخاصة بك، وتم اعتماد الصورة بنجاح.',
                    'type'    => 'academic',
                ]);

                \App\Services\FcmService::sendToUser($student->user_id, 'تمت الموافقة على تغيير صورة الوجه', 'وافقت شؤون الطلاب على طلب تحديث صورة بصمة الوجه الخاصة بك وتم اعتمادها.', ['type' => 'academic']);
            }

            return back()->with('success', $request->decision === 'approved' 
                ? 'تمت الموافقة على طلب تحديث صورة بصمة الوجه واعتماد الصورة بنجاح.' 
                : 'تم رفض طلب تحديث صورة بصمة الوجه.');
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

    /**
     * فك قفل الجهاز مباشرة من جدول الخدمات الطلابية
     */
    public function directResetDeviceFromRequest(Request $request, $id)
    {
        $studentReq = \App\Models\StudentRequest::findOrFail($id);

        if ($studentReq->type !== 'device_reset') {
            return back()->with('error', 'هذا الإجراء مخصص لطلبات فك قفل الجهاز فقط.');
        }

        $student = $studentReq->student;
        if (!$student) {
            return back()->with('error', 'سجل الطالب غير موجود.');
        }

        // تصفير قفل الجهاز
        $student->update([
            'device_id'        => null,
            'is_device_locked' => 0,
        ]);

        // حذف التوكنات لتسجيل الخروج من الجهاز القديم
        DB::table('personal_access_tokens')
            ->where('tokenable_id', $student->user_id)
            ->delete();

        // تحديث حالة الطلب إلى تمت الموافقة
        $studentReq->affairs_decision = 'approved';
        $studentReq->affairs_notes = 'تم فك قفل الجهاز وتصفير التقييد مباشرة بواسطة موظف الشؤون من جدول الطلبات.';
        $studentReq->status = 'approved';
        $studentReq->save();

        // إرسال إشعار للطالب
        \App\Models\Notification::create([
            'user_id' => $student->user_id,
            'title'   => 'تم فك قفل الجهاز',
            'message' => 'وافقت شؤون الطلاب على طلب فك قفل الجهاز الخاص بك. تم تصفير القفل بنجاح، يمكنك الآن تسجيل الدخول مباشرة من جهازك الجديد.',
            'type'    => 'academic',
        ]);

        $studentName = $student->user?->full_name ?? 'الطالب';
        return back()->with('success', "تم فك قفل الجهاز للطالب ($studentName) وتصفير بيانات الجهاز بنجاح ✓");
    }

    // ─────────────────────────── Accounts ───────────────────────────
    public function accounts(Request $request)
    {
        $roleFilter = $request->input('role', 'all');
        $search = trim($request->input('search', ''));

        $query = DB::table('users')
            ->select(['user_id', 'role_id', 'full_name', 'username', 'email', 'phone', 'university_id', 'status', 'created_at'])
            ->where('role_id', '!=', 1); // استبعاد المدير العام

        if ($roleFilter !== 'all') {
            $roleMap = [
                'student' => 3,
                'teacher' => 2,
                'hod'     => 5,
                'parent'  => 4,
                'affairs' => 6,
            ];
            if (isset($roleMap[$roleFilter])) {
                $query->where('role_id', $roleMap[$roleFilter]);
            }
        }

        if (!empty($search)) {
            $query->where(function($q) use ($search) {
                $q->where('full_name', 'LIKE', "%{$search}%")
                  ->orWhere('username', 'LIKE', "%{$search}%")
                  ->orWhere('email', 'LIKE', "%{$search}%")
                  ->orWhere('phone', 'LIKE', "%{$search}%")
                  ->orWhere('university_id', 'LIKE', "%{$search}%");
            });
        }

        $users = $query->orderByDesc('created_at')->paginate(24)->withQueryString();

        $counts = [
            'all'     => DB::table('users')->where('role_id', '!=', 1)->count(),
            'student' => DB::table('users')->where('role_id', 3)->count(),
            'teacher' => DB::table('users')->where('role_id', 2)->count(),
            'hod'     => DB::table('users')->where('role_id', 5)->count(),
            'parent'  => DB::table('users')->where('role_id', 4)->count(),
            'affairs' => DB::table('users')->where('role_id', 6)->count(),
        ];

        $pendingUsers = DB::table('users')
            ->where('status', 'inactive')
            ->where('role_id', '!=', 1)
            ->orderByDesc('created_at')
            ->get();

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

        return view('affairs.accounts', compact(
            'users', 'roleFilter', 'search', 'counts', 'pendingUsers',
            'departments', 'courses', 'deptCourses', 'deptBranches'
        ));
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

                    // الطالب انضبط لتوّه على "السنة الأولى" أعلاه، فلازم يسجَّل بمواد سنته فقط (لا كل سنوات البرنامج)
                    $courseIds = collect();
                    if ($program) {
                        $student->update(['program_id' => $program->id]);
                        $courseIds = \DB::table('course_program')
                            ->join('courses', 'course_program.course_id', '=', 'courses.course_id')
                            ->where('course_program.program_id', $program->id)
                            ->where('courses.year', 1)
                            ->pluck('course_program.course_id');
                    }

                    if ($courseIds->isEmpty()) {
                        $courseIds = \DB::table('courses')->where('year', 1)->pluck('course_id');
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
        $user = DB::table('users')->where('user_id', $id)->first();
        if (!$user) {
            return back()->with('error', 'الحساب غير موجود.');
        }

        $newStatus = ($user->status === 'active') ? 'inactive' : 'active';
        DB::table('users')->where('user_id', $id)->update([
            'status'     => $newStatus,
            'updated_at' => now(),
        ]);

        $statusText = ($newStatus === 'active') ? 'تفعيل' : 'إيقاف';
        \App\Models\UserActivity::log('تغيير حالة حساب', "قام موظف الشؤون بـ {$statusText} حساب: {$user->full_name} ({$user->username})");

        return back()->with('success', "تم {$statusText} حساب ({$user->full_name}) بنجاح.");
    }

    public function unlinkAccount(Request $request, $id)
    {
        $user = DB::table('users')->where('user_id', $id)->first();
        if (!$user) {
            return back()->with('error', 'الحساب غير موجود.');
        }

        $details = [];

        // 1. فك ربط جهاز الطالب
        if ($user->role_id == 3) {
            DB::table('students')->where('user_id', $id)->update([
                'device_id'        => null,
                'is_device_locked' => 0,
            ]);
            $details[] = 'فك ربط الجهاز';
        }

        // 2. فك ربط الأبناء بحساب ولي الأمر
        if ($user->role_id == 4) {
            $deletedLinks = DB::table('parent_students')->where('parent_id', $id)->delete();
            if ($deletedLinks > 0) {
                $details[] = 'فك ربط الأبناء (' . $deletedLinks . ' طالب)';
            }
        }

        // 3. إنهاء الجلسات ورموز الوصول النشطة (Tokens)
        if (\Illuminate\Support\Facades\Schema::hasTable('personal_access_tokens')) {
            DB::table('personal_access_tokens')->where('tokenable_id', $id)->delete();
            $details[] = 'إنهاء الجلسات النشطة';
        }

        $actionDesc = !empty($details) ? implode(' و ', $details) : 'فك ربط الحساب والجلسات';

        \App\Models\UserActivity::log('فك ربط حساب', "قام موظف الشؤون بفك ربط حساب: {$user->full_name} ({$user->username})");

        return back()->with('success', "تم {$actionDesc} لحساب ({$user->full_name}) بنجاح!");
    }

    public function deleteAccount($id)
    {
        $usr = DB::table('users')->where('user_id', $id)->first();
        if (!$usr) {
            return back()->with('error', 'الحساب غير موجود.');
        }

        $roleId = intval($usr->role_id);

        DB::beginTransaction();
        try {
            if ($roleId == 3) {
                $student = DB::table('students')->where('user_id', $id)->first();
                if ($student) {
                    DB::table('parent_students')->where('student_id', $id)->delete();
                    DB::table('enrollments')->where('student_id', $student->student_id)->delete();
                    DB::table('students')->where('student_id', $student->student_id)->delete();
                }
            } elseif ($roleId == 2) {
                $teacher = DB::table('teachers')->where('user_id', $id)->first();
                if ($teacher) {
                    DB::table('course_teachers')->where('teacher_id', $teacher->teacher_id)->delete();
                    DB::table('teachers')->where('teacher_id', $teacher->teacher_id)->delete();
                }
            } elseif ($roleId == 5) {
                DB::table('heads')->where('user_id', $id)->delete();
            } elseif ($roleId == 4) {
                $parent = DB::table('parents')->where('user_id', $id)->first();
                if ($parent) {
                    DB::table('parent_students')->where('parent_id', $id)->delete();
                    DB::table('parents')->where('parent_id', $parent->parent_id)->delete();
                }
            }

            if (\Illuminate\Support\Facades\Schema::hasTable('personal_access_tokens')) {
                DB::table('personal_access_tokens')->where('tokenable_id', $id)->delete();
            }
            if (\Illuminate\Support\Facades\Schema::hasTable('notifications')) {
                DB::table('notifications')->where('user_id', $id)->delete();
            }

            DB::table('users')->where('user_id', $id)->delete();
            DB::commit();

            \App\Models\UserActivity::log('حذف حساب', "قام موظف الشؤون بحذف حساب: {$usr->full_name} ({$usr->username})");

            return back()->with('success', "تم حذف حساب ({$usr->full_name}) بنجاح!");
        } catch (\Throwable $e) {
            DB::rollBack();
            return back()->with('error', 'حدث خطأ أثناء حذف الحساب: ' . $e->getMessage());
        }
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

        if (request()->expectsJson() || request()->ajax()) {
            return response()->json(['status' => 'success']);
        }

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

    public function shareStudentTranscript(Request $request)
    {
        $request->validate([
            'student_id' => 'required|exists:students,student_id',
            'target'     => 'required|in:student,parent,both',
            'notes'      => 'nullable|string|max:500',
        ]);

        $student = \App\Models\Student::with('user')->findOrFail($request->student_id);
        $studentUser = $student->user;
        $studentName = $studentUser?->full_name ?? 'الطالب';

        $paperNotice = "⚠️ تنبيه رسمي: هذه النسخة المعروضة للمعاينة الرقمية المعتمدة فقط. للحصول على كشف علامات ورقي رسمي مختوم وموقع، يرجى مراجعة موظف شؤون الطلاب بالمعهد.";
        $sharedTitle = "كشف درجات وسجل أكاديمي موثق 🎓";

        $notifiedUsers = [];

        // 1. إرسال للطالب
        if (in_array($request->target, ['student', 'both']) && $student->user_id) {
            $msg = "قامت شؤون الطلاب بمشاركة كشف العلامات والسجل الأكاديمي الرقمي المعتمد لك. يمكنك معاينته فورياً داخل التطبيق.\n" . $paperNotice;
            if ($request->filled('notes')) {
                $msg .= "\nملاحظات الشؤون: " . $request->notes;
            }

            \App\Models\Notification::create([
                'user_id'    => $student->user_id,
                'sender_id'  => auth()->id(),
                'title'      => $sharedTitle,
                'message'    => $msg,
                'type'       => 'transcript_shared',
                'category'   => 'academic',
                'related_id' => $student->student_id,
                'is_read'    => false,
            ]);
            $notifiedUsers[] = 'الطالب (' . $studentName . ')';
        }

        // 2. إرسال لولي الأمر
        if (in_array($request->target, ['parent', 'both'])) {
            $parentUserIds = [];
            if ($student->parent_id) {
                $pUser = \DB::table('parents')->where('parent_id', $student->parent_id)->value('user_id');
                if ($pUser) $parentUserIds[] = $pUser;
            }
            $pivotParents = \DB::table('parent_students')->where('student_id', $student->user_id)->pluck('parent_id')->toArray();
            $parentUserIds = array_unique(array_merge($parentUserIds, $pivotParents));

            foreach ($parentUserIds as $pUserId) {
                $msg = "قامت شؤون الطلاب بمشاركة كشف علامات وسجل ابنكم الأكاديمي ({$studentName}). يمكنكم معاينته فورياً داخل التطبيق.\n" . $paperNotice;
                if ($request->filled('notes')) {
                    $msg .= "\nملاحظات الشؤون: " . $request->notes;
                }

                \App\Models\Notification::create([
                    'user_id'    => $pUserId,
                    'sender_id'  => auth()->id(),
                    'title'      => $sharedTitle,
                    'message'    => $msg,
                    'type'       => 'transcript_shared',
                    'category'   => 'academic',
                    'related_id' => $student->student_id,
                    'is_read'    => false,
                ]);
            }

            if (!empty($parentUserIds)) {
                $notifiedUsers[] = 'ولي أمر الطالب';
            } elseif ($request->target === 'parent') {
                return response()->json([
                    'success' => false,
                    'message' => 'لم يتم العثور على حساب ولي أمر مرتبط بهذا الطالب في النظام.'
                ], 422);
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'تمت مشاركة كشف العلامات بنجاح مع: ' . implode(' و ', $notifiedUsers),
        ]);
    }

    public function shareCohortResults(Request $request)
    {
        $request->validate([
            'user_ids'    => 'nullable|array|min:1',
            'user_ids.*'  => 'integer|exists:users,user_id',
            'targets'     => 'nullable|array',
            'targets.*'   => 'in:admin,hod,teachers',
            'dept_name'   => 'nullable|string',
            'prog_name'   => 'nullable|string',
            'year_label'  => 'nullable|string',
            'notes'       => 'nullable|string|max:500',
        ]);

        $cohortDesc = ($request->dept_name ?? 'جميع الأقسام') . ' - ' . ($request->prog_name ?? 'جميع التخصصات') . ' (' . ($request->year_label ?? 'كافة السنوات') . ')';

        $users = collect();
        if ($request->filled('user_ids')) {
            $users = \App\Models\User::whereIn('user_id', $request->user_ids)->where('status', 'active')->pluck('user_id');
        } elseif ($request->filled('targets')) {
            $recipientRoles = [];
            if (in_array('admin', $request->targets))    $recipientRoles[] = 1; // إدارة
            if (in_array('teachers', $request->targets)) $recipientRoles[] = 2; // معلمون
            if (in_array('hod', $request->targets))      $recipientRoles[] = 5; // رؤساء أقسام
            $users = \App\Models\User::whereIn('role_id', $recipientRoles)->where('status', 'active')->pluck('user_id');
        }

        if ($users->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'يرجى تحديد شخص واحد على الأقل من القائمة لمشاركة المحضر معه.',
            ], 422);
        }

        $notifTitle = "محضر نتائج الدفعة وقرارات الترفيع 📑";
        $notifMsg = "شاركت إدارة شؤون الطلاب محضر وقرارات نتائج الدفعة: {$cohortDesc}.";
        if ($request->filled('notes')) {
            $notifMsg .= "\nملاحظات الشؤون: " . $request->notes;
        }

        foreach ($users as $uId) {
            \App\Models\Notification::create([
                'user_id'    => $uId,
                'sender_id'  => auth()->id(),
                'title'      => $notifTitle,
                'message'    => $notifMsg,
                'type'       => 'cohort_results_shared',
                'category'   => 'administrative',
                'is_read'    => false,
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'تمت مشاركة محضر الدفعة بنجاح مع ' . count($users) . ' عضو من الكادر الأكاديمي والإداري.',
        ]);
    }
}


