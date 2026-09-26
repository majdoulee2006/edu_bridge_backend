<?php

namespace App\Services;

use App\Models\Teacher;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class AttendanceExcelService
{
    /**
     * بناء بيانات مصنف الإكسل الأكاديمي التفاعلي المعتمد (Excel Online Engine)
     *
     * @param Teacher $teacher
     * @param array $filters ['scope' => 'my_courses'|'advisor_class', 'course_id' => ..., 'period' => 'today'|'week'|'semester']
     * @return array
     */
    public static function buildWorkbookData(Teacher $teacher, array $filters = []): array
    {
        $scope = $filters['scope'] ?? 'my_courses';
        $courseId = !empty($filters['course_id']) ? $filters['course_id'] : null;
        $period = $filters['period'] ?? 'week';

        // 1. تحديد النطاق الزمني
        $startDate = null;
        $endDate = null;
        $periodLabel = '';
        $daysList = [];

        if ($period === 'today') {
            $startDate = Carbon::today()->startOfDay();
            $endDate = Carbon::today()->endOfDay();
            $periodLabel = 'اليوم: ' . Carbon::today()->locale('ar')->isoFormat('dddd D MMMM YYYY');
            $daysList[] = Carbon::today();
        } elseif ($period === 'week') {
            // أسبوع الدوام الرسمي الأكاديمي: الأحد إلى الخميس (5 أيام دوام)
            $startOfWeek = Carbon::now()->startOfWeek(Carbon::SUNDAY);
            $startDate = $startOfWeek->copy()->startOfDay();
            $endDate = $startOfWeek->copy()->addDays(4)->endOfDay();
            $periodLabel = 'الأسبوع الحالي (' . $startDate->format('d/m/Y') . ' – ' . $startOfWeek->copy()->addDays(4)->format('d/m/Y') . ')';

            for ($i = 0; $i < 5; $i++) {
                $daysList[] = $startOfWeek->copy()->addDays($i);
            }
        } elseif ($period === 'semester') {
            $activeSemester = DB::table('semesters')->where('is_active', true)->first();
            if ($activeSemester && $activeSemester->start_date && $activeSemester->end_date) {
                $startDate = Carbon::parse($activeSemester->start_date)->startOfDay();
                $endDate = Carbon::parse($activeSemester->end_date)->endOfDay();
                $periodLabel = ($activeSemester->name ?? 'الفصل الحالي') . ' (' . $startDate->format('d/m/Y') . ' – ' . $endDate->format('d/m/Y') . ')';
            } else {
                $startDate = Carbon::now()->subMonths(4)->startOfDay();
                $endDate = Carbon::now()->endOfDay();
                $periodLabel = 'الفصل الدراسي الأول 2025 / 2026';
            }
            // For semester, we'll collect the unique days with sessions
        }

        // 2. تصفية المقررات المعنية حسب النطاق (موادي أو دورتي الإشرافية)
        $advisorBranch = $teacher->advisor_branch;
        $advisorYear = $teacher->advisor_year;
        $advisorCohortName = (!empty($advisorBranch) && !empty($advisorYear))
            ? ($advisorBranch . ' - ' . $advisorYear)
            : 'غير محدد كمربي دورة';

        $yearMap = [1 => 'السنة الأولى', 2 => 'السنة الثانية', 3 => 'السنة الثالثة', 4 => 'السنة الرابعة', 5 => 'السنة الخامسة'];
        $yearMapRev = ['السنة الأولى' => 1, 'السنة الثانية' => 2, 'السنة الثالثة' => 3, 'السنة الرابعة' => 4, 'السنة الخامسة' => 5];

        $coursesList = collect();

        if ($scope === 'advisor_class') {
            $programIds = DB::table('programs')->where('name', $advisorBranch)->pluck('id')->toArray();
            $courseYearNum = $yearMapRev[$advisorYear] ?? 1;

            if ($courseId) {
                $coursesList = DB::table('courses')->where('course_id', $courseId)->get();
            } else {
                $validCourses = DB::table('courses')
                    ->join('course_program', 'courses.course_id', '=', 'course_program.course_id')
                    ->whereIn('course_program.program_id', $programIds)
                    ->where('courses.year', $courseYearNum)
                    ->pluck('courses.course_id')->toArray();

                $coursesList = DB::table('courses')->whereIn('course_id', $validCourses)->get();
            }
        } else {
            // المواد الخاصة بالمعلم
            $myCourseIds = DB::table('course_teachers')->where('teacher_id', $teacher->teacher_id)->pluck('course_id')->toArray();
            if ($courseId) {
                $coursesList = DB::table('courses')->where('course_id', $courseId)->get();
            } else {
                $coursesList = DB::table('courses')->whereIn('course_id', $myCourseIds)->get();
            }
        }

        $relevantCourseIds = $coursesList->pluck('course_id')->toArray();

        // 3. تحديد نصوص الترويسة والفلاتر
        $filterScopeText = ($scope === 'advisor_class')
            ? 'مواد دورتي الإشرافية (مربي الدورة)'
            : 'المواد الخاصة بالمعلم';

        $teacherUser = DB::table('users')->where('user_id', $teacher->user_id)->first();
        $supervisorName = $teacherUser ? $teacherUser->full_name : 'المشرف الأكاديمي';

        if ($scope === 'advisor_class') {
            $filterClassText = $advisorCohortName;
        } else {
            if ($courseId && $coursesList->isNotEmpty()) {
                $firstC = $coursesList->first();
                $progs = DB::table('course_program')
                    ->join('programs', 'course_program.program_id', '=', 'programs.id')
                    ->where('course_program.course_id', $firstC->course_id)
                    ->pluck('programs.name')
                    ->toArray();
                $filterClassText = (!empty($progs) ? implode(' / ', $progs) : 'عام') . ' - ' . ($yearMap[$firstC->year] ?? 'السنة الأولى');
            } else {
                $filterClassText = 'متعدد الشعب والاختصاصات';
            }
        }

        // 4. استخراج جميع الطلاب المسجلين بالمقررات المختارة وفق معايير الاختصاص والسنة
        $allStudents = [];
        $courseStudentsMap = []; // course_id => [student_id => info]

        foreach ($coursesList as $c) {
            $coursePrograms = DB::table('course_program')->where('course_id', $c->course_id)->pluck('program_id')->toArray();
            $courseYearStr = $yearMap[$c->year] ?? null;

            $stQuery = DB::table('students')
                ->join('users', 'students.user_id', '=', 'users.user_id')
                ->leftJoin('programs', 'students.program_id', '=', 'programs.id')
                ->join('enrollments', function($join) use ($c) {
                    $join->on('students.student_id', '=', 'enrollments.student_id')
                         ->where('enrollments.course_id', '=', $c->course_id)
                         ->where('enrollments.status', '!=', 'dropped');
                });

            if (!empty($coursePrograms)) {
                $stQuery->whereIn('students.program_id', $coursePrograms);
            }
            if ($courseYearStr) {
                $stQuery->where('users.academic_year', $courseYearStr);
            }

            $students = $stQuery->select(
                'students.student_id',
                'users.full_name',
                'users.academic_year',
                'users.university_id',
                'programs.name as branch_name'
            )->distinct()->get();

            foreach ($students as $st) {
                $bName = $st->branch_name ?? 'عام';
                $yName = $st->academic_year ?? 'السنة الأولى';
                $stData = [
                    'student_id'   => $st->student_id,
                    'academic_id'  => $st->university_id ?: (2026000 + $st->student_id),
                    'name'         => $st->full_name,
                    'branch'       => $bName,
                    'year'         => $yName,
                    'batch_name'   => $bName . ' - ' . $yName,
                ];

                $allStudents[$st->student_id] = $stData;
                $courseStudentsMap[$c->course_id][$st->student_id] = $stData;
            }
        }

        // Fallback إذا كانت القائمة فارغة
        if (empty($allStudents)) {
            $sampleStudents = DB::table('students')
                ->join('users', 'students.user_id', '=', 'users.user_id')
                ->leftJoin('programs', 'students.program_id', '=', 'programs.id')
                ->select('students.student_id', 'users.full_name', 'users.university_id', 'users.academic_year', 'programs.name as branch_name')
                ->limit(10)->get();

            foreach ($sampleStudents as $st) {
                $bName = $st->branch_name ?? 'معلوماتية';
                $yName = $st->academic_year ?? 'السنة الأولى';
                $allStudents[$st->student_id] = [
                    'student_id'   => $st->student_id,
                    'academic_id'  => $st->university_id ?: (2026000 + $st->student_id),
                    'name'         => $st->full_name,
                    'branch'       => $bName,
                    'year'         => $yName,
                    'batch_name'   => $bName . ' - ' . $yName,
                ];
            }
        }

        // ترتيب الطلاب أبجدياً
        uasort($allStudents, fn($a, $b) => strcmp($a['name'], $b['name']));

        // 5. استخراج الجلسات وسجلات الحضور
        $sessionsQuery = DB::table('attendance_sessions')
            ->join('lessons', 'attendance_sessions.lesson_id', '=', 'lessons.lesson_id')
            ->join('courses', 'lessons.course_id', '=', 'courses.course_id')
            ->whereIn('lessons.course_id', $relevantCourseIds)
            ->select('attendance_sessions.*', 'lessons.course_id', 'lessons.lesson_id', 'courses.title as course_title', 'courses.code as course_code');

        if ($startDate && $endDate) {
            $sessionsQuery->whereBetween('attendance_sessions.created_at', [$startDate, $endDate]);
        }

        $sessions = $sessionsQuery->orderBy('attendance_sessions.created_at')->get();

        // سجلات الحضور
        $attQuery = DB::table('attendance')
            ->join('lessons', 'attendance.lesson_id', '=', 'lessons.lesson_id')
            ->whereIn('lessons.course_id', $relevantCourseIds)
            ->select('attendance.*');

        if ($startDate && $endDate) {
            $attQuery->where(function($q) use ($startDate, $endDate) {
                $q->whereBetween('attendance.attendance_date', [$startDate->toDateString(), $endDate->toDateString()])
                  ->orWhereBetween('attendance.created_at', [$startDate, $endDate]);
            });
        }

        $attendances = $attQuery->get();

        // تجميع الحضور لكل طالب ولكل جلسة
        // matrix[student_id][lesson_id] = 'present'|'absent'|'late'|'excused'
        $matrix = [];
        $attKeyed = $attendances->groupBy('student_id');

        foreach ($attendances as $att) {
            $matrix[$att->student_id][$att->lesson_id] = $att->status;
        }

        // إذا كانت الفترة semester وكانت أيام الدوام فارغة، نجمع الأيام من الجلسات الفعلية
        if (empty($daysList)) {
            $uniqueDates = $sessions->map(fn($s) => Carbon::parse($s->created_at)->toDateString())->unique()->sort();
            foreach ($uniqueDates as $dStr) {
                $daysList[] = Carbon::parse($dStr);
            }
            if (empty($daysList)) {
                $startOfWeek = Carbon::now()->startOfWeek(Carbon::SUNDAY);
                for ($i = 0; $i < 5; $i++) {
                    $daysList[] = $startOfWeek->copy()->addDays($i);
                }
            }
        }

        // 6. بناء أوراق العمل (Sheets):
        // أ) ورقة لكل مادة على حدة أولاً (مثل القالب المرفق)
        $sheets = [];
        $firstTab = true;

        foreach ($coursesList as $c) {
            $cStudents = $courseStudentsMap[$c->course_id] ?? [];
            if (empty($cStudents)) {
                $cStudents = $allStudents;
            }
            uasort($cStudents, fn($a, $b) => strcmp($a['name'], $b['name']));

            $cSessions = $sessions->where('course_id', $c->course_id);

            $sheets[] = self::buildSheet(
                'sheet-course-' . $c->course_id,
                'مقرر ' . $c->title,
                $c->code ?: 'تخصصي',
                $firstTab,
                $cStudents,
                $daysList,
                $cSessions,
                $matrix,
                $courseStudentsMap,
                false,
                $c
            );
            $firstTab = false;
        }

        // ب) ورقة السجل اليومي الموحد (Daily Master) إذا كان هناك أكثر من مادة
        if (count($coursesList) > 1 && !$courseId) {
            $sheets[] = self::buildSheet(
                'sheet-master',
                'السجل اليومي الموحد (Daily Master)',
                'شامل لكافة المواد',
                false,
                $allStudents,
                $daysList,
                $sessions,
                $matrix,
                $courseStudentsMap,
                true // isMaster
            );
        }

        // ج) ورقة ملخص الإنذارات والحرمان الأكاديمي
        $alertsSheet = self::buildAlertsSheet($allStudents, $coursesList, $sessions, $matrix, $courseStudentsMap);
        $sheets[] = $alertsSheet;

        return [
            'teacher'        => $teacher,
            'supervisorName' => $supervisorName,
            'filterClass'    => $filterClassText,
            'filterScope'    => $filterScopeText,
            'periodLabel'    => $periodLabel,
            'sheets'         => $sheets,
            'totalStudents'  => count($allStudents),
            'totalSessions'  => $sessions->count(),
            'activeSheetId'  => $sheets[0]['id'] ?? 'sheet-master',
        ];
    }

    /**
     * بناء ورقة عمل واحدة (Sheet)
     */
    protected static function buildSheet(
        string $sheetId,
        string $title,
        string $badge,
        bool $isActive,
        array $students,
        array $daysList,
        $sessions,
        array $matrix,
        array $courseStudentsMap,
        bool $isMaster = false,
        $course = null
    ): array {
        $totalDays = count($daysList);
        $sessionsGroupedByDay = [];

        foreach ($daysList as $dayCarbon) {
            $dayStr = $dayCarbon->toDateString();
            $dayName = $dayCarbon->locale('ar')->isoFormat('dddd');
            $daySessions = $sessions->filter(function($s) use ($dayStr) {
                return Carbon::parse($s->created_at)->toDateString() === $dayStr;
            })->values();

            $sessionsGroupedByDay[$dayStr] = [
                'date'     => $dayStr,
                'day_name' => $dayName,
                'label'    => $dayName . ' ' . $dayCarbon->format('d/m/Y'),
                'sessions' => $daySessions,
            ];
        }

        $allDaySessionsCount = $sessions->count();
        $studentRows = [];
        $totalAttendedAll = 0;
        $totalAbsentAll = 0;
        $totalDaysAttendedAll = 0;

        $colPresentCounts = []; // lesson_id => count
        $colDayPresentCounts = []; // date => count

        $num = 0;
        foreach ($students as $stId => $info) {
            $num++;
            $stRow = [
                'num'             => str_pad($num, 2, '0', STR_PAD_LEFT),
                'student_id'      => $stId,
                'academic_id'     => $info['academic_id'],
                'name'            => $info['name'],
                'branch'          => $info['branch'],
                'year'            => $info['year'],
                'day_cells'       => [],
                'session_cells'   => [],
                'attended_sessions' => 0,
                'absent_sessions'   => 0,
                'total_sessions'    => 0,
                'attended_days'     => 0,
                'total_days'        => $totalDays,
            ];

            foreach ($sessionsGroupedByDay as $dayStr => $dayData) {
                $dayAttendedAny = false;
                $dayHasEnrolledSession = false;

                foreach ($dayData['sessions'] as $sess) {
                    $lId = $sess->lesson_id;
                    $cId = $sess->course_id;

                    // التحقق مما إذا كان الطالب مسجلاً بهذه المادة
                    $isEnrolled = isset($courseStudentsMap[$cId][$stId]);
                    if (!$isMaster) {
                        $isEnrolled = true;
                    }

                    if ($isEnrolled) {
                        $dayHasEnrolledSession = true;
                        $stRow['total_sessions']++;
                        $statusRaw = $matrix[$stId][$lId] ?? 'absent';

                        if (in_array($statusRaw, ['present', 'late'])) {
                            $stRow['attended_sessions']++;
                            $dayAttendedAny = true;
                            $stRow['session_cells'][$lId] = [
                                'status' => 'present',
                                'label'  => 'حاضر',
                                'bg'     => 'bg-emerald-900/40 text-emerald-300',
                            ];
                            $colPresentCounts[$lId] = ($colPresentCounts[$lId] ?? 0) + 1;
                        } else {
                            $stRow['absent_sessions']++;
                            $stRow['session_cells'][$lId] = [
                                'status' => 'absent',
                                'label'  => 'غائب',
                                'bg'     => 'bg-rose-900/40 text-rose-300 font-bold',
                            ];
                        }
                    } else {
                        // غير مسجل بهذه المادة
                        $stRow['session_cells'][$lId] = [
                            'status' => 'exempt',
                            'label'  => '-',
                            'bg'     => 'text-on-surface-variant/40',
                        ];
                    }
                }

                // حساب دوام اليوم لهذا الطالب
                if ($dayAttendedAny) {
                    $stRow['attended_days']++;
                    $stRow['day_cells'][$dayStr] = [
                        'status' => 'present',
                        'label'  => 'حاضر',
                        'bg'     => 'bg-emerald-900/60 text-emerald-300 font-bold',
                    ];
                    $colDayPresentCounts[$dayStr] = ($colDayPresentCounts[$dayStr] ?? 0) + 1;
                } else {
                    $stRow['day_cells'][$dayStr] = [
                        'status' => 'absent',
                        'label'  => 'غائب',
                        'bg'     => 'bg-rose-900/60 text-rose-300 font-bold',
                    ];
                }
            }

            // النسب المئوية
            $stRow['session_rate'] = $stRow['total_sessions'] > 0
                ? round(($stRow['attended_sessions'] / $stRow['total_sessions']) * 100, 1)
                : 0;

            $stRow['day_rate'] = $stRow['total_days'] > 0
                ? round(($stRow['attended_days'] / $stRow['total_days']) * 100, 1)
                : 0;

            // الحالة الأكاديمية
            $absenceRate = 100 - $stRow['session_rate'];
            if ($absenceRate < 15) {
                $stRow['academic_status'] = 'healthy';
                $stRow['status_label'] = 'سليم أكاديمياً';
                $stRow['badge_class'] = 'bg-emerald-950/50 text-emerald-400 border-emerald-800/40';
                $stRow['dot_class'] = 'bg-emerald-400';
            } elseif ($absenceRate < 20) {
                $stRow['academic_status'] = 'warning';
                $stRow['status_label'] = 'تنبيه متابعة إدارية';
                $stRow['badge_class'] = 'bg-amber-950/50 text-amber-400 border-amber-800/40';
                $stRow['dot_class'] = 'bg-amber-400';
            } else {
                $stRow['academic_status'] = 'deprived';
                $stRow['status_label'] = 'إنذار وحرمان';
                $stRow['badge_class'] = 'bg-rose-950/50 text-rose-400 border-rose-800/40';
                $stRow['dot_class'] = 'bg-rose-400';
            }

            $totalAttendedAll += $stRow['attended_sessions'];
            $totalAbsentAll += $stRow['absent_sessions'];
            $totalDaysAttendedAll += $stRow['attended_days'];

            $studentRows[] = $stRow;
        }

        // المجاميع لسطر tfoot
        $totalStudents = count($students);
        $totalSessionsCount = count($studentRows) > 0 ? array_sum(array_column($studentRows, 'total_sessions')) : 0;
        $overallSessionRate = $totalSessionsCount > 0 ? round(($totalAttendedAll / $totalSessionsCount) * 100, 1) : 0;
        $totalDaysAll = $totalStudents * $totalDays;
        $overallDayRate = $totalDaysAll > 0 ? round(($totalDaysAttendedAll / $totalDaysAll) * 100, 1) : 0;

        return [
            'id'                   => $sheetId,
            'title'                => $title,
            'badge'                => $badge,
            'is_active'            => $isActive,
            'is_alerts'            => false,
            'is_master'            => $isMaster,
            'days_grouped'         => $sessionsGroupedByDay,
            'students'             => $studentRows,
            'total_students'       => $totalStudents,
            'total_sessions_count' => $sessions->count(),
            'total_attended_all'   => $totalAttendedAll,
            'total_absent_all'     => $totalAbsentAll,
            'overall_session_rate' => $overallSessionRate,
            'total_days_all'       => $totalDaysAll,
            'total_days_attended'  => $totalDaysAttendedAll,
            'overall_day_rate'     => $overallDayRate,
            'col_present_counts'   => $colPresentCounts,
            'col_day_present_counts' => $colDayPresentCounts,
        ];
    }

    /**
     * بناء ورقة عمل الإنذارات والحرمان الأكاديمي
     */
    protected static function buildAlertsSheet(array $allStudents, $coursesList, $sessions, array $matrix, array $courseStudentsMap): array
    {
        $alertRows = [];
        $num = 0;

        foreach ($allStudents as $stId => $info) {
            $totalSess = 0;
            $attendedSess = 0;
            $studentCourses = [];

            foreach ($coursesList as $c) {
                if (isset($courseStudentsMap[$c->course_id][$stId])) {
                    $studentCourses[] = $c->title;
                }
            }

            foreach ($sessions as $s) {
                if (isset($courseStudentsMap[$s->course_id][$stId])) {
                    $totalSess++;
                    $status = $matrix[$stId][$s->lesson_id] ?? 'absent';
                    if (in_array($status, ['present', 'late'])) {
                        $attendedSess++;
                    }
                }
            }

            if ($totalSess > 0) {
                $absentSess = $totalSess - $attendedSess;
                $absenceRate = round(($absentSess / $totalSess) * 100, 1);

                if ($absenceRate >= 15) {
                    $num++;
                    $isBanned = $absenceRate >= 20;
                    $alertRows[] = [
                        'num'          => str_pad($num, 2, '0', STR_PAD_LEFT),
                        'student_id'   => $stId,
                        'academic_id'  => $info['academic_id'],
                        'name'         => $info['name'],
                        'branch_year'  => $info['batch_name'],
                        'courses'      => implode('، ', $studentCourses),
                        'total_sess'   => $totalSess,
                        'attended'     => $attendedSess,
                        'absent'       => $absentSess,
                        'absence_rate' => $absenceRate . '%',
                        'level'        => $isBanned ? 'حرمان نهائي (20% فما فوق)' : 'إنذار وتنبيه غياب (15%)',
                        'level_badge'  => $isBanned ? 'bg-rose-950/60 text-rose-300 border-rose-800' : 'bg-amber-950/60 text-amber-300 border-amber-800',
                        'action'       => $isBanned ? 'إشعار خطي + حرمان رسمي من الامتحان' : 'توجيه إنذار خطي + استدعاء ولي أمر',
                    ];
                }
            }
        }

        return [
            'id'             => 'sheet-alerts',
            'title'          => 'ملخص الإنذارات والحرمان الأكاديمي',
            'badge'          => (string)count($alertRows),
            'is_active'      => false,
            'is_alerts'      => true,
            'alert_students' => $alertRows,
            'total_students' => count($alertRows),
        ];
    }
}
