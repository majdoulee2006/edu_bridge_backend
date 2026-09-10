<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminReportController extends Controller
{
    public function reports(Request $request)
    {
        $departments = DB::table('departments')->get();
        $programs = DB::table('programs')->get();
        $semesters = DB::table('semesters')->orderByDesc('start_date')->get();

        $savedReports = DB::table('admin_generated_reports')->orderByDesc('created_at')->get();

        $previewReport = null;
        $reportData = null;
        $reportType = null;

        if ($request->has('view_id')) {
            $previewReport = DB::table('admin_generated_reports')->where('id', $request->view_id)->first();
            if ($previewReport) {
                $reportType = $previewReport->report_type;
                $reportData = $this->fetchReportData($previewReport);
            }
        }

        return view('admin.reports', compact('departments', 'programs', 'semesters', 'savedReports', 'previewReport', 'reportType', 'reportData'));
    }

    public function generateReport(Request $request)
    {
        $request->validate([
            'report_type' => 'required|in:attendance,performance',
            'from_date'   => 'nullable|date|before_or_equal:today',
            'to_date'     => 'nullable|date|before_or_equal:today|after_or_equal:from_date',
        ], [
            'from_date.before_or_equal' => 'تاريخ البداية لا يمكن أن يكون تاريخاً في المستقبل.',
            'to_date.before_or_equal'   => 'تاريخ النهاية لا يمكن أن يكون تاريخاً في المستقبل.',
            'to_date.after_or_equal'    => 'تاريخ النهاية يجب أن يكون بعد أو يطابق تاريخ البداية.',
        ]);

        $deptName = $request->department_id ? (DB::table('departments')->where('department_id', $request->department_id)->value('name') ?? 'جميع الأقسام') : 'جميع الأقسام';
        $progName = $request->program_id ? (DB::table('programs')->where('id', $request->program_id)->value('name') ?? 'جميع الدورات') : 'جميع الدورات';
        $semName  = $request->semester_id ? (DB::table('semesters')->where('semester_id', $request->semester_id)->value('name') ?? 'جميع الفصول') : 'جميع الفصول';

        $typeName = $request->report_type === 'attendance' ? 'تقرير نسب الحضور والغياب' : 'تقرير أداء ودرجات الطلاب';
        $title = $typeName . ' - قسم ' . $deptName;

        DB::table('admin_generated_reports')->insert([
            'title'           => $title,
            'report_type'     => $request->report_type,
            'department_id'   => $request->department_id,
            'department_name' => $deptName,
            'program_id'      => $request->program_id,
            'program_name'    => $progName,
            'semester_id'     => $request->semester_id,
            'semester_name'   => $semName,
            'from_date'       => $request->from_date,
            'to_date'         => $request->to_date,
            'created_at'      => now(),
            'updated_at'      => now(),
        ]);

        return redirect()->route('admin.reports')->with('success', 'تم إنشاء التقرير الإداري وإضافته إلى سجل التقارير بنجاح.');
    }

    public function deleteReport($id)
    {
        DB::table('admin_generated_reports')->where('id', $id)->delete();
        return redirect()->route('admin.reports')->with('success', 'تم حذف التقرير من السجل بنجاح.');
    }

    private function fetchReportData($report)
    {
        if ($report->report_type === 'attendance') {
            $query = DB::table('attendance')
                ->join('students', 'attendance.student_id', '=', 'students.student_id')
                ->join('users', 'students.user_id', '=', 'users.user_id')
                ->leftJoin('programs', 'students.program_id', '=', 'programs.id')
                ->leftJoin('departments', 'programs.department_id', '=', 'departments.department_id')
                ->join('lessons', 'attendance.lesson_id', '=', 'lessons.lesson_id')
                ->join('courses', 'lessons.course_id', '=', 'courses.course_id')
                ->leftJoin('semesters', 'courses.semester_id', '=', 'semesters.semester_id')
                ->select(
                    'users.full_name',
                    DB::raw("COALESCE(departments.name, 'عام') as department_name"),
                    DB::raw("COALESCE(programs.name, 'عام') as program_name"),
                    'courses.title as course_title',
                    DB::raw("COALESCE(semesters.name, 'عام') as semester_name"),
                    DB::raw('COUNT(*) as total_sessions'),
                    DB::raw("SUM(CASE WHEN attendance.status = 'present' THEN 1 ELSE 0 END) as present_count"),
                    DB::raw("SUM(CASE WHEN attendance.status = 'absent' THEN 1 ELSE 0 END) as absent_count")
                )
                ->groupBy('users.full_name', 'departments.name', 'programs.name', 'courses.title', 'semesters.name');

            if ($report->from_date) {
                $query->where('attendance.attendance_date', '>=', $report->from_date);
            }
            if ($report->to_date) {
                $query->where('attendance.attendance_date', '<=', $report->to_date);
            }
            if ($report->semester_id) {
                $query->where('courses.semester_id', $report->semester_id);
            }
            if ($report->program_id) {
                $courseIds = DB::table('course_program')->where('program_id', $report->program_id)->pluck('course_id');
                $query->whereIn('lessons.course_id', $courseIds);
            } elseif ($report->department_id) {
                $programIds = DB::table('programs')->where('department_id', $report->department_id)->pluck('id');
                $courseIds = DB::table('course_program')->whereIn('program_id', $programIds)->pluck('course_id');
                $query->whereIn('lessons.course_id', $courseIds);
            }

            return $query->limit(100)->get();
        } else {
            $query = DB::table('grades')
                ->join('students', 'grades.student_id', '=', 'students.student_id')
                ->join('users', 'students.user_id', '=', 'users.user_id')
                ->leftJoin('programs', 'students.program_id', '=', 'programs.id')
                ->leftJoin('departments', 'programs.department_id', '=', 'departments.department_id')
                ->join('exams', 'grades.exam_id', '=', 'exams.exam_id')
                ->join('courses', 'exams.course_id', '=', 'courses.course_id')
                ->leftJoin('semesters', 'courses.semester_id', '=', 'semesters.semester_id')
                ->select(
                    'users.full_name',
                    DB::raw("COALESCE(departments.name, 'عام') as department_name"),
                    DB::raw("COALESCE(programs.name, 'عام') as program_name"),
                    'courses.title as course_title',
                    'grades.score as grade',
                    DB::raw("COALESCE(semesters.name, 'عام') as semester")
                );

            if ($report->semester_id) {
                $query->where('courses.semester_id', $report->semester_id);
            }
            if ($report->program_id) {
                $courseIds = DB::table('course_program')->where('program_id', $report->program_id)->pluck('course_id');
                $query->whereIn('exams.course_id', $courseIds);
            } elseif ($report->department_id) {
                $programIds = DB::table('programs')->where('department_id', $report->department_id)->pluck('id');
                $courseIds = DB::table('course_program')->whereIn('program_id', $programIds)->pluck('course_id');
                $query->whereIn('exams.course_id', $courseIds);
            }

            return $query->limit(100)->get();
        }
    }

    public function exportReport(Request $request)
    {
        $request->validate(['report_type' => 'required|in:attendance,performance']);

        $reportType   = $request->report_type;
        $exportFormat = $request->input('export_format', 'excel');
        $deptName     = $request->department_id ? (DB::table('departments')->where('department_id', $request->department_id)->value('name') ?? 'جميع الأقسام') : 'جميع الأقسام';
        $progName     = $request->program_id    ? (DB::table('programs')->where('id', $request->program_id)->value('name') ?? 'جميع الدورات') : 'جميع الدورات';
        $semesterName = $request->semester_id   ? (DB::table('semesters')->where('semester_id', $request->semester_id)->value('name') ?? 'جميع الفصول') : 'جميع الفصول';
        $dateLabel    = date('Y-m-d H:i');

        if ($reportType === 'attendance') {
            $query = DB::table('attendance')
                ->join('students', 'attendance.student_id', '=', 'students.student_id')
                ->join('users', 'students.user_id', '=', 'users.user_id')
                ->leftJoin('programs', 'students.program_id', '=', 'programs.id')
                ->leftJoin('departments', 'programs.department_id', '=', 'departments.department_id')
                ->join('lessons', 'attendance.lesson_id', '=', 'lessons.lesson_id')
                ->join('courses', 'lessons.course_id', '=', 'courses.course_id')
                ->leftJoin('semesters', 'courses.semester_id', '=', 'semesters.semester_id')
                ->select(
                    'users.full_name as student_name',
                    DB::raw("COALESCE(departments.name, 'عام') as department_name"),
                    DB::raw("COALESCE(programs.name, 'عام') as program_name"),
                    'courses.title as course_title',
                    DB::raw("COALESCE(semesters.name, 'عام') as semester_name"),
                    DB::raw("COUNT(*) as total_sessions"),
                    DB::raw("SUM(CASE WHEN attendance.status = 'present' THEN 1 ELSE 0 END) as present_count"),
                    DB::raw("SUM(CASE WHEN attendance.status = 'absent' THEN 1 ELSE 0 END) as absent_count")
                )
                ->groupBy('users.full_name', 'departments.name', 'programs.name', 'courses.title', 'semesters.name');

            if ($request->semester_id) $query->where('courses.semester_id', $request->semester_id);
            if ($request->from_date)   $query->where('attendance.attendance_date', '>=', $request->from_date);
            if ($request->to_date)     $query->where('attendance.attendance_date', '<=', $request->to_date);
            if ($request->program_id) {
                $courseIds = DB::table('course_program')->where('program_id', $request->program_id)->pluck('course_id');
                $query->whereIn('lessons.course_id', $courseIds);
            } elseif ($request->department_id) {
                $programIds = DB::table('programs')->where('department_id', $request->department_id)->pluck('id');
                $courseIds  = DB::table('course_program')->whereIn('program_id', $programIds)->pluck('course_id');
                $query->whereIn('lessons.course_id', $courseIds);
            }

            $currentYear = date('Y');
            $data        = $query->orderBy('departments.name')->orderBy('users.full_name')->get();
            $reportTitle = "تقرير حضور {$currentYear}";
            $filename    = "{$reportTitle}_{$dateLabel}.xls";

            $rowsHtml = '';
            $i = 1;
            foreach ($data as $row) {
                $rate = $row->total_sessions > 0 ? round(($row->present_count / $row->total_sessions) * 100) : 0;

                $rowsHtml .= "<tr>
                    <td style='text-align:center;'>{$i}</td>
                    <td style='font-weight:bold;color:#0f172a;'>{$row->student_name}</td>
                    <td style='color:#2563eb;font-weight:bold;'>{$row->department_name}</td>
                    <td style='color:#475569;'>{$row->program_name}</td>
                    <td style='color:#0f172a;'>{$row->course_title}</td>
                    <td style='text-align:center;'>{$row->semester_name}</td>
                    <td style='color:#15803d;font-weight:bold;text-align:center;'>{$row->present_count}</td>
                    <td style='color:#b91c1c;font-weight:bold;text-align:center;'>{$row->absent_count}</td>
                    <td style='text-align:center;font-weight:bold;'>{$row->total_sessions}</td>
                    <td style='font-weight:bold;text-align:center;'>{$rate}%</td>
                </tr>";
                $i++;
            }

            $headersHtml = "<tr>
                <th style='width:40px;text-align:center;'>#</th>
                <th>اسم الطالب</th>
                <th>القسم</th>
                <th>الدورة / البرنامج</th>
                <th>المادة الدراسية</th>
                <th style='text-align:center;'>الفصل</th>
                <th style='text-align:center;'>حاضر</th>
                <th style='text-align:center;'>غائب</th>
                <th style='text-align:center;'>الإجمالي</th>
                <th style='text-align:center;'>نسبة الحضور</th>
            </tr>";

        } else {
            $query = DB::table('grades')
                ->join('students', 'grades.student_id', '=', 'students.student_id')
                ->join('users', 'students.user_id', '=', 'users.user_id')
                ->leftJoin('programs', 'students.program_id', '=', 'programs.id')
                ->leftJoin('departments', 'programs.department_id', '=', 'departments.department_id')
                ->join('exams', 'grades.exam_id', '=', 'exams.exam_id')
                ->join('courses', 'exams.course_id', '=', 'courses.course_id')
                ->leftJoin('semesters', 'courses.semester_id', '=', 'semesters.semester_id')
                ->select(
                    'users.full_name as student_name',
                    DB::raw("COALESCE(departments.name, 'عام') as department_name"),
                    DB::raw("COALESCE(programs.name, 'عام') as program_name"),
                    'courses.title as course_title',
                    'grades.score as grade',
                    DB::raw("COALESCE(semesters.name, 'عام') as semester_name")
                );

            if ($request->semester_id) $query->where('courses.semester_id', $request->semester_id);
            if ($request->program_id) {
                $courseIds = DB::table('course_program')->where('program_id', $request->program_id)->pluck('course_id');
                $query->whereIn('exams.course_id', $courseIds);
            } elseif ($request->department_id) {
                $programIds = DB::table('programs')->where('department_id', $request->department_id)->pluck('id');
                $courseIds  = DB::table('course_program')->whereIn('program_id', $programIds)->pluck('course_id');
                $query->whereIn('exams.course_id', $courseIds);
            }

            $currentYear = date('Y');
            $data        = $query->orderBy('departments.name')->orderBy('users.full_name')->get();
            $reportTitle = "تقرير أداء {$currentYear}";
            $filename    = "{$reportTitle}_{$dateLabel}.xls";

            $rowsHtml = '';
            $i = 1;
            foreach ($data as $row) {
                $g = $row->grade;
                if ($g >= 90) { $rating = 'ممتاز'; $pass = 'ناجح'; $bg = '#dcfce7'; $clr = '#15803d'; }
                elseif ($g >= 80) { $rating = 'جيد جداً'; $pass = 'ناجح'; $bg = '#dbeafe'; $clr = '#1d4ed8'; }
                elseif ($g >= 70) { $rating = 'جيد'; $pass = 'ناجح'; $bg = '#fef3c7'; $clr = '#b45309'; }
                elseif ($g >= 60) { $rating = 'مقبول'; $pass = 'ناجح'; $bg = '#f3e8ff'; $clr = '#6b21a8'; }
                else { $rating = 'راسب'; $pass = 'راسب'; $bg = '#fee2e2'; $clr = '#b91c1c'; }

                $rowsHtml .= "<tr>
                    <td style='text-align:center;'>{$i}</td>
                    <td style='font-weight:bold;color:#0f172a;'>{$row->student_name}</td>
                    <td style='color:#2563eb;font-weight:bold;'>{$row->department_name}</td>
                    <td style='color:#475569;'>{$row->program_name}</td>
                    <td style='color:#0f172a;'>{$row->course_title}</td>
                    <td style='text-align:center;'>{$row->semester_name}</td>
                    <td style='font-weight:bold;text-align:center;color:#0f172a;'>{$row->grade}</td>
                    <td style='background:{$bg};color:{$clr};font-weight:bold;text-align:center;'>{$rating}</td>
                    <td style='background:{$bg};color:{$clr};font-weight:bold;text-align:center;'>{$pass}</td>
                </tr>";
                $i++;
            }

            $headersHtml = "<tr>
                <th style='width:40px;text-align:center;'>#</th>
                <th>اسم الطالب</th>
                <th>القسم</th>
                <th>الدورة / البرنامج</th>
                <th>المادة الدراسية</th>
                <th style='text-align:center;'>الفصل</th>
                <th style='text-align:center;'>الدرجة (/100)</th>
                <th style='text-align:center;'>التقدير</th>
                <th style='text-align:center;'>النتيجة</th>
            </tr>";
        }

        if ($exportFormat === 'pdf') {
            $pdfHtml = "
            <!DOCTYPE html>
            <html lang='ar' dir='rtl'>
            <head>
                <meta charset='UTF-8'>
                <title>{$reportTitle}</title>
                <style>
                    body { font-family: 'Segoe UI', Tahoma, sans-serif; padding: 25px; color: #1e293b; background: #f8fafc; direction: rtl; }
                    .page-wrapper { background: #ffffff; border-radius: 16px; border: 1px solid #e2e8f0; padding: 30px; box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.05); }
                    .header-banner { text-align: center; background: #f1f5f9; border: 1px solid #e2e8f0; border-radius: 12px; padding: 16px; margin-bottom: 20px; }
                    .header-banner h1 { margin: 0; color: #0f172a; font-size: 22px; font-weight: 800; }
                    .meta-grid { display: flex; justify-content: space-between; background: #edf2f7; border: 1px solid #cbd5e1; border-radius: 10px; padding: 12px 20px; margin-bottom: 20px; font-size: 12px; color: #334155; }
                    table { width: 100%; border-collapse: collapse; margin-top: 10px; background: #ffffff; border-radius: 8px; overflow: hidden; }
                    th { background-color: #1e293b; color: #f8fafc; text-align: right; padding: 11px 12px; font-size: 12px; font-weight: bold; border: 1px solid #334155; }
                    td { padding: 9px 12px; font-size: 11px; border: 1px solid #e2e8f0; text-align: right; color: #1e293b; }
                    tr:nth-child(even) td { background-color: #f1f5f9; }
                    tr:nth-child(odd) td { background-color: #ffffff; }
                    .footer-note { margin-top: 30px; text-align: center; font-size: 11px; color: #64748b; border-top: 1px solid #e2e8f0; padding-top: 12px; }
                    @media print {
                        body { background: #ffffff; padding: 0; }
                        .page-wrapper { border: none; padding: 0; box-shadow: none; }
                        .no-print { display: none; }
                    }
                </style>
            </head>
            <body>
                <div class='no-print' style='margin-bottom: 18px; text-align: left;'>
                    <button onclick='window.print()' style='background: #2563eb; color: #fff; border: none; padding: 10px 24px; border-radius: 10px; font-weight: bold; cursor: pointer; font-size: 13px; box-shadow: 0 4px 12px rgba(37,99,235,0.2);'>طباعة / حفظ كـ PDF</button>
                </div>
                <div class='page-wrapper'>
                    <div class='header-banner'>
                        <h1>{$reportTitle}</h1>
                    </div>
                    <div class='meta-grid'>
                        <span><strong>القسم:</strong> {$deptName}</span>
                        <span><strong>الدورة:</strong> {$progName}</span>
                        <span><strong>الفصل:</strong> {$semesterName}</span>
                        <span><strong>تاريخ الإصدار:</strong> {$dateLabel}</span>
                    </div>
                    <table>
                        <thead>{$headersHtml}</thead>
                        <tbody>{$rowsHtml}</tbody>
                    </table>
                    <div class='footer-note'>تم استخراج التقرير بتاريخ {$dateLabel}</div>
                </div>
                <script>
                    window.onload = function() {
                        setTimeout(function() { window.print(); }, 400);
                    };
                </script>
            </body>
            </html>";

            return response($pdfHtml)->header('Content-Type', 'text/html; charset=utf-8');
        }

        // Excel Export formatting with eye-friendly tones (slate/soft gray)
        $excelHtml = "<html xmlns:o='urn:schemas-microsoft-com:office:office' xmlns:x='urn:schemas-microsoft-com:office:excel' xmlns='http://www.w3.org/TR/REC-html40'>
        <head>
        <meta http-equiv='Content-Type' content='text/html; charset=UTF-8'>
        <style>
            body { font-family: 'Segoe UI', Tahoma, sans-serif; direction: rtl; background: #f8fafc; }
            table { border-collapse: collapse; width: 100%; }
            .main-hdr { background: #1e293b; color: #f8fafc; font-size: 15px; font-weight: bold; text-align: center; padding: 14px; }
            .info-hdr { background: #edf2f7; color: #1e293b; font-size: 11px; font-weight: bold; padding: 9px; border: 1px solid #cbd5e1; }
            th { background: #334155; color: #ffffff; font-weight: bold; font-size: 12px; border: 1px solid #475569; padding: 10px; text-align: right; }
            td { border: 1px solid #cbd5e1; padding: 8px; font-size: 11px; text-align: right; color: #1e293b; }
            tr:nth-child(even) td { background: #f1f5f9; }
            tr:nth-child(odd) td { background: #ffffff; }
        </style>
        </head>
        <body>
        <table>
            <tr><td colspan='10' class='main-hdr'>{$reportTitle}</td></tr>
            <tr>
                <td colspan='3' class='info-hdr'><b>القسم المستهدف:</b> {$deptName}</td>
                <td colspan='3' class='info-hdr'><b>الدورة / البرنامج:</b> {$progName}</td>
                <td colspan='2' class='info-hdr'><b>الفصل الدراسي:</b> {$semesterName}</td>
                <td colspan='2' class='info-hdr'><b>تاريخ الإصدار:</b> {$dateLabel}</td>
            </tr>
            {$headersHtml}
            {$rowsHtml}
        </table>
        </body>
        </html>";

        return response("\xEF\xBB\xBF" . $excelHtml)
            ->header('Content-Type', 'application/vnd.ms-excel; charset=utf-8')
            ->header('Content-Disposition', 'attachment; filename="' . $filename . '"')
            ->header('Pragma', 'no-cache')
            ->header('Cache-Control', 'must-revalidate');
    }
}
