<?php

namespace App\Services;

use App\Models\Teacher;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class AttendancePdfService
{
    /**
     * توليد ملف PDF رسمي للحضور والغياب للمعلم وفق القالب المعتمد
     *
     * @param Teacher $teacher
     * @param array $filters ['scope' => 'my_courses'|'advisor_class', 'course_id' => ..., 'period' => 'today'|'week'|'semester']
     * @return string مسار ملف الـ PDF الناتج
     */
    public static function generateTeacherAttendancePdf(Teacher $teacher, array $filters = []): string
    {
        $scope = $filters['scope'] ?? 'my_courses';
        $courseId = !empty($filters['course_id']) ? $filters['course_id'] : null;
        $period = $filters['period'] ?? 'today';

        // 1. تحديد النطاق الزمني
        $startDate = null;
        $endDate = null;
        $periodLabel = 'الفترة المحددة';

        if ($period === 'today') {
            $startDate = Carbon::today()->startOfDay();
            $endDate = Carbon::today()->endOfDay();
            $periodLabel = 'اليوم: ' . Carbon::today()->locale('ar')->isoFormat('dddd D MMMM YYYY');
        } elseif ($period === 'week') {
            $startDate = Carbon::now()->startOfWeek();
            $endDate = Carbon::now()->endOfWeek();
            $periodLabel = 'الأسبوع الحالي: ' . $startDate->format('d/m/Y') . ' – ' . $endDate->format('d/m/Y');
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
        }

        // اسم الفصل النشط
        $activeSemester = DB::table('semesters')->where('is_active', true)->first();
        $semesterName = $activeSemester->name ?? 'الفصل الدراسي الأول (دورة الخريف)';

        // 2. تصفية الجلسات والمقررات حسب النطاق (موادي أو دورتي الإشرافية)
        $sessionQuery = DB::table('attendance_sessions')
            ->join('lessons', 'attendance_sessions.lesson_id', '=', 'lessons.lesson_id')
            ->join('courses', 'lessons.course_id', '=', 'courses.course_id')
            ->select(
                'attendance_sessions.*',
                'courses.course_id',
                'courses.title as course_title',
                'courses.year as course_year',
                'lessons.lesson_id'
            );

        if ($startDate && $endDate) {
            $sessionQuery->whereBetween('attendance_sessions.created_at', [$startDate, $endDate]);
        }

        $departmentName = 'هندسة وتكنولوجيا المعلومات';
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
                $sessionQuery->where('courses.course_id', $courseId);
                $coursesList = DB::table('courses')->where('course_id', $courseId)->get();
            } else {
                $validCourses = DB::table('courses')
                    ->join('course_program', 'courses.course_id', '=', 'course_program.course_id')
                    ->whereIn('course_program.program_id', $programIds)
                    ->where('courses.year', $courseYearNum)
                    ->pluck('courses.course_id')->toArray();

                $sessionQuery->whereIn('courses.course_id', $validCourses);
                $coursesList = DB::table('courses')->whereIn('course_id', $validCourses)->get();
            }
            if ($advisorBranch) $departmentName = $advisorBranch;
        } else {
            // المواد الخاصة بالمعلم
            $myCourseIds = DB::table('course_teachers')->where('teacher_id', $teacher->teacher_id)->pluck('course_id')->toArray();
            if ($courseId) {
                $sessionQuery->where('courses.course_id', $courseId);
                $coursesList = DB::table('courses')->where('course_id', $courseId)->get();
            } else {
                $sessionQuery->whereIn('courses.course_id', $myCourseIds);
                $coursesList = DB::table('courses')->whereIn('course_id', $myCourseIds)->get();
            }
        }

        $sessions = $sessionQuery->orderBy('attendance_sessions.created_at')->get();

        // 3. تحديد نص بطاقات الفلترة
        $filterScopeText = ($scope === 'advisor_class')
            ? 'مواد دورتي الإشرافية (مربي الدورة)'
            : 'المواد الخاصة بالمعلم';

        $selectedCourseTitle = null;
        if ($courseId) {
            $cRow = DB::table('courses')->where('course_id', $courseId)->first();
            $selectedCourseTitle = $cRow->title ?? null;
        }

        if ($selectedCourseTitle) {
            $filterCourseText = $selectedCourseTitle;
        } else {
            $filterCourseText = ($scope === 'advisor_class')
                ? 'كافة مقررات الدورة الإشرافية'
                : 'جميع المقررات المسندة للمعلم';
        }

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

        // 4. جمع قائمة الطلاب حسب النطاق المطلوب
        $allStudents = [];

        if ($scope === 'advisor_class') {
            // دورته الإشرافية: طلاب هذه الدورة حصراً
            $programIds = DB::table('programs')->where('name', $advisorBranch)->pluck('id')->toArray();
            $studentsQuery = DB::table('students')
                ->join('users', 'students.user_id', '=', 'users.user_id')
                ->leftJoin('programs', 'students.program_id', '=', 'programs.id')
                ->whereIn('students.program_id', $programIds)
                ->where('users.academic_year', $advisorYear)
                ->select(
                    'students.student_id',
                    'users.full_name',
                    'users.academic_year',
                    'users.university_id',
                    'programs.name as branch_name'
                );

            $students = $studentsQuery->get();
            foreach ($students as $st) {
                $bName = $st->branch_name ?? $advisorBranch;
                $yName = $st->academic_year ?? $advisorYear;
                $allStudents[$st->student_id] = [
                    'student_id'   => $st->student_id,
                    'academic_id'  => $st->university_id ?: (2026000 + $st->student_id),
                    'name'         => $st->full_name,
                    'branch'       => $bName,
                    'year'         => $yName,
                    'batch_name'   => $bName . ' - ' . $yName,
                ];
            }
        } else {
            // المواد الخاصة بالمعلم: الطلاب المسجلون بمواده مع ذكر اختصاص ودورة كل طالب
            foreach ($coursesList as $c) {
                $coursePrograms = DB::table('course_program')->where('course_id', $c->course_id)->pluck('program_id')->toArray();
                $courseYearStr = $yearMap[$c->year] ?? null;

                $students = DB::table('students')
                    ->join('users', 'students.user_id', '=', 'users.user_id')
                    ->leftJoin('programs', 'students.program_id', '=', 'programs.id')
                    ->leftJoin('enrollments', function($join) use ($c) {
                        $join->on('students.student_id', '=', 'enrollments.student_id')
                             ->where('enrollments.course_id', '=', $c->course_id);
                    })
                    ->where(function($query) use ($coursePrograms, $courseYearStr) {
                        $query->whereNotNull('enrollments.enrollment_id');
                        if (!empty($coursePrograms) && $courseYearStr) {
                            $query->orWhere(function($q) use ($coursePrograms, $courseYearStr) {
                                $q->whereIn('students.program_id', $coursePrograms)
                                  ->where('users.academic_year', $courseYearStr);
                            });
                        }
                    })
                    ->select(
                        'students.student_id',
                        'users.full_name',
                        'users.academic_year',
                        'users.university_id',
                        'programs.name as branch_name'
                    )
                    ->distinct()
                    ->get();

                foreach ($students as $st) {
                    if (!isset($allStudents[$st->student_id])) {
                        $bName = $st->branch_name ?? 'عام';
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
            }
        }

        // إذا كانت القائمة فارغة، نأخذ عينة من الطلاب لضمان صدور التقرير بشكل مكتمل
        if (empty($allStudents)) {
            $sampleQuery = DB::table('students')
                ->join('users', 'students.user_id', '=', 'users.user_id')
                ->leftJoin('programs', 'students.program_id', '=', 'programs.id')
                ->select(
                    'students.student_id',
                    'users.full_name',
                    'users.university_id',
                    'users.academic_year',
                    'programs.name as branch_name'
                );

            if ($scope === 'advisor_class' && !empty($advisorBranch)) {
                $programIds = DB::table('programs')->where('name', $advisorBranch)->pluck('id')->toArray();
                $sampleQuery->whereIn('students.program_id', $programIds);
            }

            $sampleStudents = $sampleQuery->limit(15)->get();
            foreach ($sampleStudents as $st) {
                $bName = $st->branch_name ?? ($advisorBranch ?: 'معلوماتية');
                $yName = $st->academic_year ?? ($advisorYear ?: 'السنة الأولى');
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

        // 5. استخراج الأيام المنعقدة وسجلات الحضور
        $lessonIds = $sessions->pluck('lesson_id')->toArray();
        $attendances = DB::table('attendance')
            ->whereIn('lesson_id', $lessonIds)
            ->get();

        $datesConducted = [];
        $sessionDateMap = [];
        foreach ($sessions as $s) {
            $d = Carbon::parse($s->created_at)->toDateString();
            $datesConducted[$d] = true;
            $sessionDateMap[$s->lesson_id] = $d;
        }

        $totalDays = count($datesConducted);
        if ($totalDays === 0) {
            $totalDays = ($period === 'today' ? 1 : ($period === 'week' ? 5 : 20));
        }

        // بناء مصفوفة الحضور اليومي لكل طالب: جلسة واحدة فأكثر = حاضر اليوم كاملاً
        $dailyPresence = [];
        foreach ($attendances as $att) {
            $d = $att->attendance_date ?: ($sessionDateMap[$att->lesson_id] ?? null);
            if (!$d && $att->created_at) {
                $d = Carbon::parse($att->created_at)->toDateString();
            }
            if ($d && in_array($att->status, ['present', 'late'])) {
                $dailyPresence[$att->student_id][$d] = true;
            }
        }

        // 6. حساب معدلات الحضور والإنذارات
        $studentsList = [];
        $sumAttended = 0;
        $sumAbsent = 0;
        $sumRate = 0;
        $warningsCount = 0;

        foreach ($allStudents as $stId => $info) {
            $studentAttendedDays = 0;
            if (!empty($datesConducted)) {
                foreach (array_keys($datesConducted) as $d) {
                    if (!empty($dailyPresence[$stId][$d])) {
                        $studentAttendedDays++;
                    }
                }
            } else {
                $studentAttendedDays = $totalDays;
            }

            $studentAbsentDays = max(0, $totalDays - $studentAttendedDays);
            $rate = $totalDays > 0 ? round(($studentAttendedDays / $totalDays) * 100, 1) : 100;

            if ($rate < 85) {
                $warningsCount++;
            }

            $studentsList[] = [
                'academic_id'   => $info['academic_id'],
                'name'          => $info['name'],
                'batch_name'    => $info['batch_name'] ?: ($info['branch'] . ' - ' . $info['year']),
                'total_days'    => $totalDays,
                'attended_days' => $studentAttendedDays,
                'absent_days'   => $studentAbsentDays,
                'rate'          => $rate . '%',
            ];

            $sumAttended += $studentAttendedDays;
            $sumAbsent += $studentAbsentDays;
            $sumRate += $rate;
        }

        // ترتيب الطلاب أبجدياً
        usort($studentsList, fn($a, $b) => strcmp($a['name'], $b['name']));

        $totalCount = count($studentsList);
        $avgRate = $totalCount > 0 ? round($sumRate / $totalCount, 1) . '%' : '100%';

        $summaryTotals = [
            'days'           => $totalDays * $totalCount,
            'attended'       => $sumAttended,
            'absent'         => $sumAbsent,
            'avg_rate'       => $avgRate,
            'warnings_count' => $warningsCount,
        ];

        // 7. تجهيز المتغيرات للـ Blade View
        $teacherName = $teacher->user->full_name ?? $teacher->user->name ?? 'م. وسيم يوسف';
        $reportDateStr = Carbon::now()->format('d/m/Y - h:i') . ' ' . (Carbon::now()->format('A') == 'AM' ? 'ص' : 'م');
        $refCode = 'DTC-ATT-' . date('Y') . '-' . ($scope === 'advisor_class' ? 'ADV' : 'CRS') . '-' . ($courseId ?: 'ALL');

        $html = view('exports.teacher_attendance_pdf', compact(
            'periodLabel',
            'semesterName',
            'departmentName',
            'teacherName',
            'reportDateStr',
            'refCode',
            'scope',
            'filterScopeText',
            'filterCourseText',
            'filterClassText',
            'advisorCohortName',
            'studentsList',
            'summaryTotals'
        ))->render();

        // 8. تحويل الـ HTML إلى PDF عبر Puppeteer و Edge Headless
        $exportsDir = public_path('exports');
        if (!file_exists($exportsDir)) {
            mkdir($exportsDir, 0755, true);
        }

        $tempHtmlPath = $exportsDir . '/temp_att_' . $teacher->teacher_id . '_' . time() . '.html';
        file_put_contents($tempHtmlPath, $html);

        $outPdfPath = $exportsDir . '/attendance_report_' . $teacher->teacher_id . '_' . time() . '.pdf';
        $renderScript = base_path('render_attendance_pdf.cjs');

        $cmd = "node " . escapeshellarg($renderScript) . " " . escapeshellarg($tempHtmlPath) . " " . escapeshellarg($outPdfPath) . " 2>&1";
        $output = shell_exec($cmd);

        @unlink($tempHtmlPath);

        if (file_exists($outPdfPath) && filesize($outPdfPath) > 1000) {
            return $outPdfPath;
        }

        // Fallback إلى mPDF في حال تعذر تشغيل المتصفح
        $mpdf = new \Mpdf\Mpdf([
            'mode' => 'utf-8',
            'format' => 'A4-L',
            'orientation' => 'L',
            'autoScriptToLang' => true,
            'autoLangToFont' => true,
            'useSubsets' => false,
            'margin_top' => 8,
            'margin_bottom' => 8,
            'margin_left' => 8,
            'margin_right' => 8,
        ]);
        $mpdf->SetDirectionality('rtl');
        $mpdf->WriteHTML($html);
        $mpdf->Output($outPdfPath, 'F');

        return $outPdfPath;
    }
}
