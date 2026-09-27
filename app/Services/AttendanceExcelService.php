<?php

namespace App\Services;

use App\Models\Teacher;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;

class AttendanceExcelService
{
    /**
     * توليد مصنف Excel (.xlsx) معتمد ومتكامل بجميع أوراق العمل والتنسيقات والفلاتر
     *
     * @param Teacher $teacher
     * @param array $filters ['scope' => 'my_courses'|'advisor_class', 'course_id' => ..., 'period' => 'today'|'week'|'semester']
     * @return string مسار الملف الناتج للتنزيل المباشر
     */
    public static function generateAttendanceWorkbookXlsx(Teacher $teacher, array $filters = []): string
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
            $startOfWeek = Carbon::now()->startOfWeek(Carbon::SUNDAY);
            $startDate = $startOfWeek->copy()->startOfDay();
            $endDate = $startOfWeek->copy()->addDays(4)->endOfDay();
            $periodLabel = 'الأسبوع الحالي (5 أيام دوام): ' . $startDate->format('d/m/Y') . ' – ' . $startOfWeek->copy()->addDays(4)->format('d/m/Y');

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
        }

        // 2. تصفية المقررات والمعلم
        $advisorBranch = $teacher->advisor_branch;
        $advisorYear = $teacher->advisor_year;
        $advisorCohortName = (!empty($advisorBranch) && !empty($advisorYear))
            ? ($advisorBranch . ' - ' . $advisorYear)
            : 'غير محدد كمربي دورة';

        $yearMap = [1 => 'السنة الأولى', 2 => 'السنة الثانية', 3 => 'السنة الثالثة', 4 => 'السنة الرابعة', 5 => 'السنة الخامسة'];
        $yearMapRev = ['السنة الأولى' => 1, 'السنة الثانية' => 2, 'السنة الثالثة' => 3, 'السنة الرابعة' => 4, 'السنة الخامسة' => 5];

        $coursesList = collect();

        $branch = trim($advisorBranch ?? '');
        $year = trim($advisorYear ?? '');
        $programIds = DB::table('programs')->where('name', $branch)->pluck('id')->toArray();
        $courseYearNum = $yearMapRev[$year] ?? 1;

        if ($scope === 'advisor_class') {
            // استخراج المقررات الخاصة بالدفعة الإشرافية من جدول الدوام الأسبوعي
            $cohortSchedQuery = DB::table('schedules')
                ->join('courses', 'schedules.course_id', '=', 'courses.course_id')
                ->where(function($q) use ($branch, $year) {
                    if (!empty($branch)) {
                        $q->where('schedules.class_group', 'like', "%{$branch}%");
                        if (str_contains($year, 'أولى') || str_contains($year, '1')) {
                            $q->where(function($sub) {
                                $sub->where('schedules.class_group', 'like', '%أولى%')
                                    ->orWhere('schedules.class_group', 'like', '%1%');
                            });
                        } elseif (str_contains($year, 'ثانية') || str_contains($year, '2')) {
                            $q->where(function($sub) {
                                $sub->where('schedules.class_group', 'like', '%ثانية%')
                                    ->orWhere('schedules.class_group', 'like', '%2%');
                            });
                        } elseif (str_contains($year, 'ثالثة') || str_contains($year, '3')) {
                            $q->where(function($sub) {
                                $sub->where('schedules.class_group', 'like', '%ثالثة%')
                                    ->orWhere('schedules.class_group', 'like', '%3%');
                            });
                        }
                    }
                })
                ->select('schedules.*', 'courses.title as course_title')
                ->orderByRaw("FIELD(schedules.day, 'Sunday','Monday','Tuesday','Wednesday','Thursday')")
                ->orderBy('schedules.start_time')
                ->get();

            if ($cohortSchedQuery->isEmpty() && !empty($programIds)) {
                $cohortSchedQuery = DB::table('schedules')
                    ->join('courses', 'schedules.course_id', '=', 'courses.course_id')
                    ->join('course_program', 'courses.course_id', '=', 'course_program.course_id')
                    ->whereIn('course_program.program_id', $programIds)
                    ->where('courses.year', $courseYearNum)
                    ->select('schedules.*', 'courses.title as course_title')
                    ->orderByRaw("FIELD(schedules.day, 'Sunday','Monday','Tuesday','Wednesday','Thursday')")
                    ->orderBy('schedules.start_time')
                    ->get();
            }

            if ($courseId) {
                $coursesList = DB::table('courses')->where('course_id', $courseId)->get();
            } else {
                $cohortCourseIds = $cohortSchedQuery->pluck('course_id')->unique()->toArray();
                if (empty($cohortCourseIds)) {
                    $cohortCourseIds = DB::table('courses')
                        ->join('course_program', 'courses.course_id', '=', 'course_program.course_id')
                        ->whereIn('course_program.program_id', $programIds)
                        ->where('courses.year', $courseYearNum)
                        ->pluck('courses.course_id')->toArray();
                }
                $coursesList = DB::table('courses')->whereIn('course_id', $cohortCourseIds)->get();
            }

            $schedulesToUse = $cohortSchedQuery;
        } else {
            $myCourseIds = DB::table('course_teachers')->where('teacher_id', $teacher->teacher_id)->pluck('course_id')->toArray();
            if ($courseId) {
                $coursesList = DB::table('courses')->where('course_id', $courseId)->get();
            } else {
                $coursesList = DB::table('courses')->whereIn('course_id', $myCourseIds)->get();
            }

            $mySchedQuery = DB::table('schedules')
                ->join('courses', 'schedules.course_id', '=', 'courses.course_id')
                ->whereIn('schedules.course_id', $coursesList->pluck('course_id')->toArray())
                ->select('schedules.*', 'courses.title as course_title')
                ->orderByRaw("FIELD(schedules.day, 'Sunday','Monday','Tuesday','Wednesday','Thursday')")
                ->orderBy('schedules.start_time')
                ->get();

            $schedulesToUse = $mySchedQuery;
        }

        $relevantCourseIds = $coursesList->pluck('course_id')->toArray();

        $teacherUser = DB::table('users')->where('user_id', $teacher->user_id)->first();
        $supervisorName = $teacherUser ? $teacherUser->full_name : 'أ. خالد اسماعيل';

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

        // 3. جلب الطلاب المسجلين بالمواد
        $allStudents = [];
        $courseStudentsMap = [];

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

            if ($scope === 'advisor_class') {
                if (!empty($programIds)) {
                    $stQuery->whereIn('students.program_id', $programIds);
                }
                if (!empty($advisorYear)) {
                    $stQuery->where('users.academic_year', $advisorYear);
                }
            } else {
                if (!empty($coursePrograms)) {
                    $stQuery->whereIn('students.program_id', $coursePrograms);
                }
                if ($courseYearStr) {
                    $stQuery->where('users.academic_year', $courseYearStr);
                }
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

        uasort($allStudents, fn($a, $b) => strcmp($a['name'], $b['name']));

        // 4. استخراج الجلسات وسجلات الحضور
        $sessionsQuery = DB::table('attendance_sessions')
            ->join('lessons', 'attendance_sessions.lesson_id', '=', 'lessons.lesson_id')
            ->join('courses', 'lessons.course_id', '=', 'courses.course_id')
            ->whereIn('lessons.course_id', $relevantCourseIds)
            ->select('attendance_sessions.*', 'lessons.course_id', 'lessons.lesson_id', 'courses.title as course_title');

        if ($startDate && $endDate) {
            $sessionsQuery->whereBetween('attendance_sessions.created_at', [$startDate, $endDate]);
        }

        $sessions = $sessionsQuery->orderBy('attendance_sessions.created_at')->get();

        $attQuery = DB::table('attendance')
            ->join('lessons', 'attendance.lesson_id', '=', 'lessons.lesson_id')
            ->whereIn('lessons.course_id', $relevantCourseIds)
            ->select('attendance.*', 'lessons.course_id');

        if ($startDate && $endDate) {
            $attQuery->where(function($q) use ($startDate, $endDate) {
                $q->whereBetween('attendance.attendance_date', [$startDate->toDateString(), $endDate->toDateString()])
                  ->orWhereBetween('attendance.created_at', [$startDate, $endDate]);
            });
        }

        $attendances = $attQuery->get();

        $matrix = [];
        $matrixByCourseDate = [];
        foreach ($attendances as $att) {
            $matrix[$att->student_id][$att->lesson_id] = $att->status;
            $dStr = $att->attendance_date ? Carbon::parse($att->attendance_date)->toDateString() : null;
            if ($dStr) {
                if (!isset($matrixByCourseDate[$att->student_id][$att->course_id][$dStr]) || in_array($att->status, ['present', 'late'])) {
                    $matrixByCourseDate[$att->student_id][$att->course_id][$dStr] = $att->status;
                }
            }
        }

        // إعداد أيام الدوام الرسمية (الأحد إلى الخميس)
        if (empty($daysList)) {
            $refDate = $startDate ? Carbon::parse($startDate) : Carbon::now();
            $startOfWeek = $refDate->copy()->startOfWeek(Carbon::SUNDAY);
            for ($i = 0; $i < 5; $i++) {
                $daysList[] = $startOfWeek->copy()->addDays($i);
            }
        }

        // 5. بناء هيكل الجلسات لكل يوم وفق جدول الدوام الأسبوعي الفعلي للدفعة
        $dayColumnsMaster = [];
        $dayColumnsByCourse = [];

        foreach ($daysList as $dCarbon) {
            $dStr = $dCarbon->toDateString();
            $dName = $dCarbon->locale('ar')->isoFormat('dddd');
            $dLabel = $dName . ' ' . $dCarbon->format('d/m/Y');
            $dayNameEn = $dCarbon->format('l');

            // الجلسات المجدولة في جدول الدوام الأسبوعي لهذا اليوم
            $dayScheds = $schedulesToUse->filter(fn($s) => strcasecmp($s->day, $dayNameEn) === 0)->values();

            $masterSessList = [];
            if ($dayScheds->isEmpty()) {
                $masterSessList[] = [
                    'name'      => 'لا توجد جلسات',
                    'course_id' => null,
                    'lesson_id' => null,
                    'is_empty'  => true,
                ];
                $hasMasterSessions = false;
            } else {
                $hasMasterSessions = true;
                foreach ($dayScheds as $sch) {
                    $masterSessList[] = [
                        'name'      => mb_substr($sch->course_title, 0, 16),
                        'course_id' => $sch->course_id,
                        'lesson_id' => $sch->schedule_id ?? null,
                        'is_empty'  => false,
                    ];
                }
            }

            $dayColumnsMaster[$dStr] = [
                'date'         => $dStr,
                'label'        => $dLabel,
                'has_sessions' => $hasMasterSessions,
                'sessions'     => $masterSessList,
            ];

            // لكل مقرر دراسي على حدة
            foreach ($coursesList as $c) {
                $courseDayScheds = $dayScheds->filter(fn($s) => $s->course_id == $c->course_id)->values();
                $cList = [];
                if ($courseDayScheds->isEmpty()) {
                    $cList[] = [
                        'name'      => 'لا توجد جلسات',
                        'course_id' => $c->course_id,
                        'lesson_id' => null,
                        'is_empty'  => true,
                    ];
                    $hasCourseSessions = false;
                } else {
                    $hasCourseSessions = true;
                    foreach ($courseDayScheds as $sch) {
                        $cList[] = [
                            'name'      => mb_substr($c->title, 0, 16),
                            'course_id' => $c->course_id,
                            'lesson_id' => $sch->schedule_id ?? null,
                            'is_empty'  => false,
                        ];
                    }
                }

                $dayColumnsByCourse[$c->course_id][$dStr] = [
                    'date'         => $dStr,
                    'label'        => $dLabel,
                    'has_sessions' => $hasCourseSessions,
                    'sessions'     => $cList,
                ];
            }
        }

        // 6. إنشاء مصنف PhpSpreadsheet
        $spreadsheet = new Spreadsheet();
        $spreadsheet->removeSheetByIndex(0); // حذف الورقة الافتراضية الفارغة

        // أ) ورقة السجل اليومي الموحد (Master)
        if (count($coursesList) > 1 && !$courseId) {
            self::renderSpreadsheetWorksheet(
                $spreadsheet,
                'السجل اليومي الموحد (Master)',
                $filterClassText,
                $supervisorName,
                $periodLabel,
                $dayColumnsMaster,
                $allStudents,
                $matrix,
                $courseStudentsMap,
                true, // isMaster
                null,
                $matrixByCourseDate
            );
        }

        // ب) ورقة لكل مقرر دراسي على حدة
        foreach ($coursesList as $c) {
            $cStudents = $courseStudentsMap[$c->course_id] ?? $allStudents;
            uasort($cStudents, fn($a, $b) => strcmp($a['name'], $b['name']));

            $cDays = $dayColumnsByCourse[$c->course_id] ?? $dayColumnsMaster;

            self::renderSpreadsheetWorksheet(
                $spreadsheet,
                'مقرر ' . $c->title,
                $c->title . ' (' . ($yearMap[$c->year] ?? 'سنة اولى') . ')',
                $supervisorName,
                $periodLabel,
                $cDays,
                $cStudents,
                $matrix,
                $courseStudentsMap,
                false,
                $c,
                $matrixByCourseDate
            );
        }

        // ج) ورقة ملخص الإنذارات والحرمان الأكاديمي
        self::renderAlertsWorksheet(
            $spreadsheet,
            'ملخص الإنذارات والحرمان',
            $filterClassText,
            $supervisorName,
            $periodLabel,
            $allStudents,
            $coursesList,
            $sessions,
            $matrix,
            $courseStudentsMap
        );

        // تعيين الورقة الأولى كنشطة
        $spreadsheet->setActiveSheetIndex(0);

        // حفظ المصنف كملف .xlsx
        $exportDir = storage_path('app/exports');
        if (!file_exists($exportDir)) {
            mkdir($exportDir, 0755, true);
        }

        $fileName = 'attendance_report_' . $teacher->teacher_id . '_' . time() . '.xlsx';
        $fullPath = $exportDir . DIRECTORY_SEPARATOR . $fileName;

        $writer = new Xlsx($spreadsheet);
        $writer->save($fullPath);

        return $fullPath;
    }

    /**
     * بناء ورقة عمل واحدة في مصنف Excel مع التنسيقات والألوان والأوتوفلتر
     */
    protected static function renderSpreadsheetWorksheet(
        Spreadsheet $spreadsheet,
        string $sheetTitle,
        string $cohortName,
        string $supervisorName,
        string $periodStr,
        array $dayColumns,
        array $students,
        array $matrix,
        array $courseStudentsMap,
        bool $isMaster = false,
        $course = null,
        array $matrixByCourseDate = []
    ): void {
        // حماية الاسم ألا يتجاوز 31 حرفاً
        $safeTitle = mb_substr(str_replace(['*', ':', '?', '/', '\\', '[', ']'], '', $sheetTitle), 0, 30);
        $sheet = new \PhpOffice\PhpSpreadsheet\Worksheet\Worksheet($spreadsheet, $safeTitle);
        $spreadsheet->addSheet($sheet);
        $sheet->setRightToLeft(true);

        // ── السطر 1: شريط المعلومات الرسمي (Metadata Banner) ──
        $sheet->mergeCells('A1:D1');
        $sheet->setCellValue('A1', 'الشعبة: ' . $cohortName);

        $sheet->mergeCells('E1:G1');
        $sheet->setCellValue('E1', 'المشرف: ' . $supervisorName);

        $sheet->mergeCells('H1:N1');
        $sheet->setCellValue('H1', 'الفترة: ' . $periodStr);

        $sheet->getStyle('A1:N1')->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 11, 'name' => 'Segoe UI'],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1E3A8A']],
            'alignment' => ['vertical' => Alignment::VERTICAL_CENTER, 'horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);
        $sheet->getRowDimension(1)->setRowHeight(30);

        // ── السطر 2: تصنيفات الأيام وحزم الأعمدة (Day Bands) ──
        $sheet->mergeCells('A2:D2');
        $sheet->setCellValue('A2', 'بيانات الطالب الأكاديمية');
        $sheet->getStyle('A2:D2')->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => '1E3A8A'], 'size' => 10, 'name' => 'Segoe UI'],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'DBEAFE']],
            'alignment' => ['vertical' => Alignment::VERTICAL_CENTER, 'horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);

        $colIndex = 5; // Col E (Day 1 starts after Col D: الدورة والسنة)
        $dayLoop = 0;
        foreach ($dayColumns as $dayKey => $dayInfo) {
            $sessCount = count($dayInfo['sessions']);
            $totalDayCols = $sessCount + 1; // الجلسات + حضور اليوم
            $startCol = Coordinate::stringFromColumnIndex($colIndex);
            $endCol = Coordinate::stringFromColumnIndex($colIndex + $totalDayCols - 1);
            $colIndex += $totalDayCols;

            $sheet->mergeCells("{$startCol}2:{$endCol}2");
            $sheet->setCellValue("{$startCol}2", $dayInfo['label']);
            $dayBg = ($dayLoop++ % 2 === 0) ? 'F1F5F9' : 'E2E8F0';
            $sheet->getStyle("{$startCol}2:{$endCol}2")->applyFromArray([
                'font' => ['bold' => true, 'color' => ['rgb' => '0F172A'], 'size' => 10, 'name' => 'Segoe UI'],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $dayBg]],
                'alignment' => ['vertical' => Alignment::VERTICAL_CENTER, 'horizontal' => Alignment::HORIZONTAL_CENTER],
            ]);
        }

        // إحصاء الجلسات
        $sStart = Coordinate::stringFromColumnIndex($colIndex);
        $sEnd = Coordinate::stringFromColumnIndex($colIndex + 3);
        $colIndex += 4;
        $sheet->mergeCells("{$sStart}2:{$sEnd}2");
        $sheet->setCellValue("{$sStart}2", 'إحصاء الجلسات (Sessions)');
        $sheet->getStyle("{$sStart}2:{$sEnd}2")->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => '92400E'], 'size' => 10, 'name' => 'Segoe UI'],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'FEF3C7']],
            'alignment' => ['vertical' => Alignment::VERTICAL_CENTER, 'horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);

        // إحصاء الأيام
        $dStart = Coordinate::stringFromColumnIndex($colIndex);
        $dEnd = Coordinate::stringFromColumnIndex($colIndex + 2);
        $colIndex += 3;
        $sheet->mergeCells("{$dStart}2:{$dEnd}2");
        $sheet->setCellValue("{$dStart}2", 'إحصاء الأيام (Daily Basis)');
        $sheet->getStyle("{$dStart}2:{$dEnd}2")->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => '0369A1'], 'size' => 10, 'name' => 'Segoe UI'],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'E0F2FE']],
            'alignment' => ['vertical' => Alignment::VERTICAL_CENTER, 'horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);

        // الإنذار الأكاديمي
        $aCol = Coordinate::stringFromColumnIndex($colIndex);
        $sheet->setCellValue("{$aCol}2", 'الإنذار الأكاديمي');
        $sheet->getStyle("{$aCol}2")->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => '991B1B'], 'size' => 10, 'name' => 'Segoe UI'],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'FEE2E2']],
            'alignment' => ['vertical' => Alignment::VERTICAL_CENTER, 'horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);
        $sheet->getRowDimension(2)->setRowHeight(24);

        // ── السطر 3: الترويسات الفرعية (Sub-Headers) ──
        $sheet->setCellValue('A3', 'م');
        $sheet->setCellValue('B3', 'الرقم الأكاديمي');
        $sheet->setCellValue('C3', 'اسم الطالب الرباعي');
        $sheet->setCellValue('D3', 'الدورة والسنة');

        $colIndex = 5;
        $sessionColMap = []; // يحفظ الحرف المقابل لكل جلسة

        foreach ($dayColumns as $dayKey => $dayInfo) {
            foreach ($dayInfo['sessions'] as $sIdx => $sessItem) {
                $cLet = Coordinate::stringFromColumnIndex($colIndex++);
                if (!empty($sessItem['is_empty'])) {
                    $sheet->setCellValue("{$cLet}3", 'لا توجد جلسات');
                    $sheet->getStyle("{$cLet}3")->applyFromArray([
                        'font' => ['italic' => true, 'color' => ['rgb' => '94A3B8'], 'size' => 9, 'name' => 'Segoe UI'],
                        'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'F8FAFC']],
                    ]);
                } else {
                    $sheet->setCellValue("{$cLet}3", 'ج' . ($sIdx + 1) . ': ' . $sessItem['name']);
                    $sheet->getStyle("{$cLet}3")->applyFromArray([
                        'font' => ['bold' => true, 'color' => ['rgb' => '0F172A'], 'size' => 9, 'name' => 'Segoe UI'],
                        'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'F1F5F9']],
                    ]);
                }
                $sessionColMap[] = [
                    'col'        => $cLet,
                    'day'        => $dayKey,
                    'lesson_id'  => $sessItem['lesson_id'] ?? null,
                    'course_id'  => $sessItem['course_id'] ?? null,
                    'is_empty'   => !empty($sessItem['is_empty']),
                    'is_virtual' => !empty($sessItem['is_virtual']),
                ];
            }
            // العمود الأخير في اليوم: حضور اليوم (ذهبي ناعم مريح للعين)
            $cLet = Coordinate::stringFromColumnIndex($colIndex++);
            $sheet->setCellValue("{$cLet}3", 'حضور اليوم');
            $sheet->getStyle("{$cLet}3")->applyFromArray([
                'font' => ['bold' => true, 'color' => ['rgb' => 'B45309'], 'size' => 9, 'name' => 'Segoe UI'],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'FEF3C7']],
            ]);
        }

        // إحصائيات
        $cLet = Coordinate::stringFromColumnIndex($colIndex++);
        $sheet->setCellValue("{$cLet}3", 'المنعقدة');

        $cLet = Coordinate::stringFromColumnIndex($colIndex++);
        $sheet->setCellValue("{$cLet}3", 'حضور');
        $sheet->getStyle("{$cLet}3")->getFont()->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('15803D'));

        $cLet = Coordinate::stringFromColumnIndex($colIndex++);
        $sheet->setCellValue("{$cLet}3", 'غياب');
        $sheet->getStyle("{$cLet}3")->getFont()->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('B91C1C'));

        $cLet = Coordinate::stringFromColumnIndex($colIndex++);
        $sheet->setCellValue("{$cLet}3", 'نسبة الجلسات');
        $sheet->getStyle("{$cLet}3")->getFont()->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('B45309'));

        $cLet = Coordinate::stringFromColumnIndex($colIndex++);
        $sheet->setCellValue("{$cLet}3", 'الكلية');

        $cLet = Coordinate::stringFromColumnIndex($colIndex++);
        $sheet->setCellValue("{$cLet}3", 'المحضورة');
        $sheet->getStyle("{$cLet}3")->getFont()->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('15803D'));

        $cLet = Coordinate::stringFromColumnIndex($colIndex++);
        $sheet->setCellValue("{$cLet}3", 'التزام الأيام');
        $sheet->getStyle("{$cLet}3")->getFont()->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('B45309'));

        $cLet = Coordinate::stringFromColumnIndex($colIndex++);
        $sheet->setCellValue("{$cLet}3", 'الحالة الأكاديمية');
        $sheet->getStyle("{$cLet}3")->getFont()->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('991B1B'));

        $lastCol = Coordinate::stringFromColumnIndex($colIndex - 1);
        $sheet->getStyle("A3:{$lastCol}3")->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => '334155'], 'size' => 9, 'name' => 'Segoe UI'],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'F8FAFC']],
            'alignment' => ['vertical' => Alignment::VERTICAL_CENTER, 'horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);
        $sheet->getRowDimension(3)->setRowHeight(26);

        // ضبط السطر 1 ليمتد عبر كامل الجدول
        $sheet->mergeCells("H1:{$lastCol}1");
        $sheet->getStyle("A1:{$lastCol}1")->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 11, 'name' => 'Segoe UI'],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1E3A8A']],
            'alignment' => ['vertical' => Alignment::VERTICAL_CENTER, 'horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);

        // تجميد الأعمدة الأساسية عند E4
        $sheet->freezePane('E4');

        // ── سطور بيانات الطلاب (Rows 4+) ──
        $rowNum = 4;
        $totalDays = count($dayColumns);
        $stCounter = 1;

        foreach ($students as $stIdKey => $st) {
            $stId = $st['student_id'];
            $sheet->setCellValue("A{$rowNum}", str_pad($stCounter++, 2, '0', STR_PAD_LEFT));
            $sheet->setCellValue("B{$rowNum}", $st['academic_id']);
            $sheet->setCellValue("C{$rowNum}", $st['name']);
            $sheet->setCellValue("D{$rowNum}", $st['batch_name']);

            $isEvenRow = ($rowNum % 2 === 0);
            $rowBg = $isEvenRow ? 'F8FAFC' : 'FFFFFF';
            $sheet->getStyle("A{$rowNum}:D{$rowNum}")->applyFromArray([
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $rowBg]],
            ]);

            $sheet->getStyle("A{$rowNum}")->applyFromArray([
                'font' => ['color' => ['rgb' => '475569'], 'name' => 'Segoe UI'],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            ]);
            $sheet->getStyle("B{$rowNum}")->applyFromArray([
                'font' => ['bold' => true, 'color' => ['rgb' => '1D4ED8'], 'name' => 'Segoe UI'],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            ]);
            $sheet->getStyle("C{$rowNum}")->applyFromArray([
                'font' => ['bold' => true, 'color' => ['rgb' => '0F172A'], 'name' => 'Segoe UI'],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_RIGHT],
            ]);
            $sheet->getStyle("D{$rowNum}")->applyFromArray([
                'font' => ['bold' => true, 'color' => ['rgb' => '475569'], 'name' => 'Segoe UI', 'size' => 9],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            ]);

            $colIdx = 5;
            $stAttendedSessions = 0;
            $stTotalSessions = 0;
            $stAttendedDays = 0;

            foreach ($dayColumns as $dayKey => $dayInfo) {
                $hasSessionsInDay = !empty($dayInfo['has_sessions']);
                $dayAttendedAny = false;

                foreach ($dayInfo['sessions'] as $sItem) {
                    $cellLet = Coordinate::stringFromColumnIndex($colIdx++);

                    if (!empty($sItem['is_empty']) || !$hasSessionsInDay) {
                        $sheet->setCellValue("{$cellLet}{$rowNum}", '-');
                        $sheet->getStyle("{$cellLet}{$rowNum}")->applyFromArray([
                            'font' => ['color' => ['rgb' => '9CA3AF'], 'name' => 'Segoe UI'],
                            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                        ]);
                        continue;
                    }

                    $cId = $sItem['course_id'];
                    $lId = $sItem['lesson_id'] ?? null;

                    // فحص التسجيل
                    $isEnrolled = isset($courseStudentsMap[$cId][$stId]);
                    if (!$isMaster) $isEnrolled = true;

                    if ($isEnrolled) {
                        $stTotalSessions++;
                        $statusRaw = $matrixByCourseDate[$stId][$cId][$dayKey] ?? null;
                        if (!$statusRaw && !empty($lId)) {
                            $baseLessonId = explode('_', $lId)[0];
                            $statusRaw = $matrix[$stId][$baseLessonId] ?? null;
                        }
                        if (!$statusRaw) {
                            $statusRaw = 'absent';
                        }

                        if (in_array($statusRaw, ['present', 'late'])) {
                            $stAttendedSessions++;
                            $dayAttendedAny = true;
                            $sheet->setCellValue("{$cellLet}{$rowNum}", 'حاضر');
                            $sheet->getStyle("{$cellLet}{$rowNum}")->applyFromArray([
                                'font' => ['bold' => true, 'color' => ['rgb' => '166534'], 'name' => 'Segoe UI'],
                                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'DCFCE7']],
                                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                            ]);
                        } else {
                            $sheet->setCellValue("{$cellLet}{$rowNum}", 'غائب');
                            $sheet->getStyle("{$cellLet}{$rowNum}")->applyFromArray([
                                'font' => ['bold' => true, 'color' => ['rgb' => '991B1B'], 'name' => 'Segoe UI'],
                                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'FEE2E2']],
                                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                            ]);
                        }
                    } else {
                        $sheet->setCellValue("{$cellLet}{$rowNum}", '-');
                        $sheet->getStyle("{$cellLet}{$rowNum}")->applyFromArray([
                            'font' => ['color' => ['rgb' => '9CA3AF']],
                            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                        ]);
                    }
                }

                // عمود حضور اليوم (في نهاية اليوم)
                $dayCellLet = Coordinate::stringFromColumnIndex($colIdx++);
                if (!$hasSessionsInDay) {
                    $sheet->setCellValue("{$dayCellLet}{$rowNum}", '-');
                    $sheet->getStyle("{$dayCellLet}{$rowNum}")->applyFromArray([
                        'font' => ['color' => ['rgb' => '9CA3AF'], 'name' => 'Segoe UI'],
                        'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                    ]);
                } else {
                    if ($dayAttendedAny) {
                        $stAttendedDays++;
                        $sheet->setCellValue("{$dayCellLet}{$rowNum}", 'حاضر');
                        $sheet->getStyle("{$dayCellLet}{$rowNum}")->applyFromArray([
                            'font' => ['bold' => true, 'color' => ['rgb' => '065F46'], 'name' => 'Segoe UI'],
                            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'D1FAE5']],
                            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                        ]);
                    } else {
                        $sheet->setCellValue("{$dayCellLet}{$rowNum}", 'غائب');
                        $sheet->getStyle("{$dayCellLet}{$rowNum}")->applyFromArray([
                            'font' => ['bold' => true, 'color' => ['rgb' => '9F1239'], 'name' => 'Segoe UI'],
                            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'FFE4E6']],
                            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                        ]);
                    }
                }
            }

            // أعمدة الإحصائيات
            $activeDaysCount = count(array_filter($dayColumns, fn($d) => !empty($d['has_sessions'])));
            $rate = $stTotalSessions > 0 ? round(($stAttendedSessions / $stTotalSessions) * 100, 1) : 0;
            $dayRate = $activeDaysCount > 0 ? round(($stAttendedDays / $activeDaysCount) * 100, 1) : 0;

            $cLet = Coordinate::stringFromColumnIndex($colIdx++);
            $sheet->setCellValue("{$cLet}{$rowNum}", $stTotalSessions);
            $sheet->getStyle("{$cLet}{$rowNum}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

            $cLet = Coordinate::stringFromColumnIndex($colIdx++);
            $sheet->setCellValue("{$cLet}{$rowNum}", $stAttendedSessions);
            $sheet->getStyle("{$cLet}{$rowNum}")->applyFromArray([
                'font' => ['bold' => true, 'color' => ['rgb' => '166534']],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            ]);

            $cLet = Coordinate::stringFromColumnIndex($colIdx++);
            $abs = $stTotalSessions - $stAttendedSessions;
            $sheet->setCellValue("{$cLet}{$rowNum}", $abs);
            $sheet->getStyle("{$cLet}{$rowNum}")->applyFromArray([
                'font' => ['bold' => true, 'color' => ['rgb' => $abs > 0 ? '991B1B' : '6B7280']],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            ]);

            $cLet = Coordinate::stringFromColumnIndex($colIdx++);
            $sheet->setCellValue("{$cLet}{$rowNum}", $rate . '%');
            $sheet->getStyle("{$cLet}{$rowNum}")->applyFromArray([
                'font' => ['bold' => true, 'color' => ['rgb' => 'D97706']],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            ]);

            $cLet = Coordinate::stringFromColumnIndex($colIdx++);
            $sheet->setCellValue("{$cLet}{$rowNum}", $activeDaysCount);
            $sheet->getStyle("{$cLet}{$rowNum}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

            $cLet = Coordinate::stringFromColumnIndex($colIdx++);
            $sheet->setCellValue("{$cLet}{$rowNum}", $stAttendedDays);
            $sheet->getStyle("{$cLet}{$rowNum}")->applyFromArray([
                'font' => ['bold' => true, 'color' => ['rgb' => '166534']],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            ]);

            $cLet = Coordinate::stringFromColumnIndex($colIdx++);
            $sheet->setCellValue("{$cLet}{$rowNum}", $dayRate . '%');
            $sheet->getStyle("{$cLet}{$rowNum}")->applyFromArray([
                'font' => ['bold' => true, 'color' => ['rgb' => 'D97706']],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            ]);

            // الحالة الأكاديمية
            $cLet = Coordinate::stringFromColumnIndex($colIdx++);
            if ($rate >= 85) {
                $sheet->setCellValue("{$cLet}{$rowNum}", 'سليم أكاديمياً');
                $sheet->getStyle("{$cLet}{$rowNum}")->applyFromArray([
                    'font' => ['bold' => true, 'color' => ['rgb' => '166534']],
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'DCFCE7']],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                ]);
            } elseif ($rate >= 80) {
                $sheet->setCellValue("{$cLet}{$rowNum}", 'تنبيه متابعة إدارية');
                $sheet->getStyle("{$cLet}{$rowNum}")->applyFromArray([
                    'font' => ['bold' => true, 'color' => ['rgb' => '92400E']],
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'FEF3C7']],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                ]);
            } else {
                $sheet->setCellValue("{$cLet}{$rowNum}", 'إنذار وحرمان');
                $sheet->getStyle("{$cLet}{$rowNum}")->applyFromArray([
                    'font' => ['bold' => true, 'color' => ['rgb' => '991B1B']],
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'FEE2E2']],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                ]);
            }

            $sheet->getRowDimension($rowNum)->setRowHeight(21);
            $rowNum++;
        }

        // ── تفعيل الأوتوفلتر التفاعلي (Excel Native AutoFilter) ──
        $sheet->setAutoFilter("A3:{$lastCol}" . ($rowNum - 1));

        // ── السطر الختامي: الإجماليات (SUM & AVERAGE) ──
        $sheet->mergeCells("A{$rowNum}:D{$rowNum}");
        $sheet->setCellValue("A{$rowNum}", 'متوسط ونسب حضور الدفعة الإجمالية (SUM & AVERAGE)');
        $sheet->getStyle("A{$rowNum}:{$lastCol}{$rowNum}")->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => '1E3A8A'], 'size' => 10, 'name' => 'Segoe UI'],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'F1F5F9']],
            'alignment' => ['vertical' => Alignment::VERTICAL_CENTER, 'horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);
        $sheet->setCellValue("{$lastCol}{$rowNum}", 'جاهز للاعتماد');
        $sheet->getStyle("{$lastCol}{$rowNum}")->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => '15803D'], 'size' => 10, 'name' => 'Segoe UI'],
        ]);
        $sheet->getRowDimension($rowNum)->setRowHeight(26);

        // حدود واضحة لكافة الخلايا
        $sheet->getStyle("A2:{$lastCol}{$rowNum}")->applyFromArray([
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => 'CBD5E1'],
                ],
            ],
        ]);

        // ضبط أحجام الأعمدة تلقائياً
        for ($i = 1; $i <= $colIndex - 1; $i++) {
            $c = Coordinate::stringFromColumnIndex($i);
            $sheet->getColumnDimension($c)->setAutoSize(true);
        }
    }

    /**
     * بناء ورقة الإنذارات والحرمان الأكاديمي
     */
    protected static function renderAlertsWorksheet(
        Spreadsheet $spreadsheet,
        string $sheetTitle,
        string $cohortName,
        string $supervisorName,
        string $periodStr,
        array $allStudents,
        $coursesList,
        $sessions,
        array $matrix,
        array $courseStudentsMap
    ): void {
        $safeTitle = mb_substr($sheetTitle, 0, 30);
        $sheet = new \PhpOffice\PhpSpreadsheet\Worksheet\Worksheet($spreadsheet, $safeTitle);
        $spreadsheet->addSheet($sheet);
        $sheet->setRightToLeft(true);

        // الترويسة
        $sheet->mergeCells('A1:C1');
        $sheet->setCellValue('A1', 'الشعبة: ' . $cohortName);
        $sheet->mergeCells('D1:G1');
        $sheet->setCellValue('D1', 'المشرف: ' . $supervisorName);
        $sheet->mergeCells('H1:K1');
        $sheet->setCellValue('H1', 'الفترة: ' . $periodStr);

        $sheet->getStyle('A1:K1')->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 11, 'name' => 'Segoe UI'],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1E3A8A']],
            'alignment' => ['vertical' => Alignment::VERTICAL_CENTER, 'horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);
        $sheet->getRowDimension(1)->setRowHeight(30);

        // عناوين الأعمدة
        $headers = [
            'A2' => 'م',
            'B2' => 'الرقم الأكاديمي',
            'C2' => 'اسم الطالب الرباعي',
            'D2' => 'الدورة / الاختصاص',
            'E2' => 'المقررات المعنية',
            'F2' => 'الجلسات الكلية',
            'G2' => 'حضور',
            'H2' => 'غياب',
            'I2' => 'نسبة الغياب',
            'J2' => 'درجة الإنذار',
            'K2' => 'الإجراء الإداري المطلوب',
        ];

        foreach ($headers as $cell => $txt) {
            $sheet->setCellValue($cell, $txt);
        }

        $sheet->getStyle('A2:K2')->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => '991B1B'], 'size' => 10, 'name' => 'Segoe UI'],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'FEE2E2']],
            'alignment' => ['vertical' => Alignment::VERTICAL_CENTER, 'horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);
        $sheet->getRowDimension(2)->setRowHeight(26);

        // استخراج الطلاب المتجاوزين
        $rowNum = 3;
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

                    $sheet->setCellValue("A{$rowNum}", str_pad($num, 2, '0', STR_PAD_LEFT));
                    $sheet->setCellValue("B{$rowNum}", $info['academic_id']);
                    $sheet->setCellValue("C{$rowNum}", $info['name']);
                    $sheet->setCellValue("D{$rowNum}", $info['batch_name']);
                    $sheet->setCellValue("E{$rowNum}", implode('، ', $studentCourses));
                    $sheet->setCellValue("F{$rowNum}", $totalSess);
                    $sheet->setCellValue("G{$rowNum}", $attendedSess);
                    $sheet->setCellValue("H{$rowNum}", $absentSess);
                    $sheet->setCellValue("I{$rowNum}", $absenceRate . '%');
                    $sheet->setCellValue("J{$rowNum}", $isBanned ? 'حرمان نهائي (20% فما فوق)' : 'إنذار وتنبيه غياب (15%)');
                    $sheet->setCellValue("K{$rowNum}", $isBanned ? 'إشعار خطي + حرمان رسمي من الامتحان' : 'توجيه إنذار خطي + استدعاء ولي أمر');

                    $sheet->getStyle("A{$rowNum}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                    $sheet->getStyle("B{$rowNum}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                    $sheet->getStyle("C{$rowNum}")->getFont()->setBold(true);
                    $sheet->getStyle("F{$rowNum}:I{$rowNum}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                    
                    $sheet->getStyle("I{$rowNum}")->applyFromArray([
                        'font' => ['bold' => true, 'color' => ['rgb' => '991B1B']],
                        'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'FEE2E2']],
                    ]);

                    $sheet->getStyle("J{$rowNum}")->applyFromArray([
                        'font' => ['bold' => true, 'color' => ['rgb' => $isBanned ? '991B1B' : '92400E']],
                        'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $isBanned ? 'FEE2E2' : 'FEF3C7']],
                        'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                    ]);

                    $sheet->getRowDimension($rowNum)->setRowHeight(22);
                    $rowNum++;
                }
            }
        }

        // أوتوفلتر
        if ($rowNum > 3) {
            $sheet->setAutoFilter("A2:K" . ($rowNum - 1));
            $sheet->getStyle("A2:K" . ($rowNum - 1))->applyFromArray([
                'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'D1D5DB']]],
            ]);
        }

        for ($i = 1; $i <= 11; $i++) {
            $sheet->getColumnDimension(Coordinate::stringFromColumnIndex($i))->setAutoSize(true);
        }
    }
}
