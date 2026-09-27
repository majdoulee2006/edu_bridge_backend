<?php

namespace App\Services;

use App\Models\Schedule;
use App\Models\Student;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ScheduleImageService
{
    /**
     * توليد صورة الجدول الأسبوعي الرسمي للطالب وحفظها كـ PNG عالية الدقة
     */
    public static function generateOfficialScheduleImage(Student $student): ?string
    {
        try {
            $student->loadMissing(['user', 'program']);
            $user = $student->user;

            $academicYearStr = str_replace('السنة ال', 'سنة ', $user->academic_year ?? $student->level ?? 'سنة أولى');
            $branchName = $student->program->name ?? $user->branch ?? 'معلوماتية';
            $classGroup = trim($branchName . ' - ' . $academicYearStr);

            // جلب المحاضرات
            $schedules = Schedule::where('class_group', $classGroup)
                ->with(['course', 'course.teachers.user'])
                ->orderByRaw("CASE day WHEN 'Sunday' THEN 1 WHEN 'Monday' THEN 2 WHEN 'Tuesday' THEN 3 WHEN 'Wednesday' THEN 4 WHEN 'Thursday' THEN 5 ELSE 6 END")
                ->orderBy('start_time')
                ->get();

            if ($schedules->isEmpty()) {
                $schedules = Schedule::whereHas('course', function($qCourse) use ($student) {
                        $qCourse->whereHas('students', function($qEnrolled) use ($student) {
                            $qEnrolled->where('enrollments.student_id', $student->student_id);
                        });
                    })
                    ->with(['course', 'course.teachers.user'])
                    ->orderByRaw("CASE day WHEN 'Sunday' THEN 1 WHEN 'Monday' THEN 2 WHEN 'Tuesday' THEN 3 WHEN 'Wednesday' THEN 4 WHEN 'Thursday' THEN 5 ELSE 6 END")
                    ->orderBy('start_time')
                    ->get();
            }

            // إذا ما زال فارغاً، نجلب حسب الشعبة أو القسم
            if ($schedules->isEmpty()) {
                $schedules = Schedule::where('class_group', 'like', '%' . $academicYearStr . '%')
                    ->orWhere('class_group', 'like', '%' . $branchName . '%')
                    ->with(['course', 'course.teachers.user'])
                    ->orderByRaw("CASE day WHEN 'Sunday' THEN 1 WHEN 'Monday' THEN 2 WHEN 'Tuesday' THEN 3 WHEN 'Wednesday' THEN 4 WHEN 'Thursday' THEN 5 ELSE 6 END")
                    ->orderBy('start_time')
                    ->get();
            }

            foreach ($schedules as $s) {
                $s->course_title = $s->course->title ?? $s->course->name ?? 'مقرر دراسي';
                $s->course_code = $s->course->code ?? 'CS' . rand(101, 109);
                $teacherUser = $s->course->teachers->first()?->user;
                $s->teacher_name = $teacherUser->full_name ?? $teacherUser->name ?? 'هيئة التدريس';
            }

            // المرشد الأكاديمي
            $advisorTeacher = ['name' => 'أ. جمال العمري'];
            $teacherRow = DB::table('teachers')
                ->join('users', 'teachers.user_id', '=', 'users.user_id')
                ->where('teachers.advisor_year', $user->academic_year ?? $student->level)
                ->select('users.full_name')
                ->first();
            if ($teacherRow && !empty($teacherRow->full_name)) {
                $advisorTeacher['name'] = $teacherRow->full_name;
            }

            $activeSemester = DB::table('semesters')->where('is_active', true)->first();
            $semesterName = $activeSemester?->name ?? 'الفصل الدراسي الأول (2025/2026)';

            $html = view('exports.schedule_official_image', [
                'student' => $student,
                'schedules' => $schedules,
                'semesterName' => $semesterName,
                'advisorTeacher' => $advisorTeacher,
            ])->render();

            $directory = public_path('exports');
            if (!file_exists($directory)) {
                mkdir($directory, 0755, true);
            }

            $timestamp = time();
            $tempHtmlPath = $directory . '/temp_sched_' . $student->student_id . '_' . $timestamp . '.html';
            $outPngName = 'schedule_' . $student->student_id . '_' . $timestamp . '.png';
            $outPngPath = $directory . '/' . $outPngName;

            file_put_contents($tempHtmlPath, $html);

            $scriptPath = base_path('render_schedule.cjs');
            $cmd = 'node "' . str_replace('\\', '/', $scriptPath) . '" "' . str_replace('\\', '/', $tempHtmlPath) . '" "' . str_replace('\\', '/', $outPngPath) . '"';

            exec($cmd, $output, $code);

            if (file_exists($tempHtmlPath)) {
                @unlink($tempHtmlPath);
            }

            if ($code === 0 && file_exists($outPngPath)) {
                return $outPngPath;
            }

            Log::error("Schedule Image Generation failed with exit code $code: " . implode("\n", $output));
            return null;
        } catch (\Exception $e) {
            Log::error("Schedule Image Service Exception: " . $e->getMessage());
            return null;
        }
    }

    /**
     * توليد صورة جدول الامتحانات الرسمي للطالب وحفظها كـ PNG عالية الدقة
     */
    public static function generateOfficialExamImage(Student $student): ?string
    {
        try {
            $student->loadMissing(['user', 'program']);
            $user = $student->user;

            $myCourseIds = DB::table('enrollments')
                ->where('student_id', $student->student_id)
                ->pluck('course_id')
                ->toArray();

            $academicYearStr = str_replace('السنة ال', 'سنة ', $user->academic_year ?? $student->level ?? 'سنة أولى');
            $branchName = $student->program->name ?? $user->branch ?? 'معلوماتية';
            $classGroup = trim($branchName . ' - ' . $academicYearStr);

            $examsQuery = DB::table('exams')
                ->join('courses', 'exams.course_id', '=', 'courses.course_id')
                ->whereIn('exams.course_id', $myCourseIds);

            if (!empty($classGroup) || !empty($academicYearStr)) {
                $examsQuery->where(function($q) use ($classGroup, $academicYearStr) {
                    if (!empty($classGroup)) {
                        $q->where('exams.class_group', $classGroup)
                          ->orWhere('exams.class_group', 'like', '%' . $academicYearStr . '%')
                          ->orWhereNull('exams.class_group');
                    } elseif (!empty($academicYearStr)) {
                        $q->where('exams.class_group', 'like', '%' . $academicYearStr . '%')
                          ->orWhereNull('exams.class_group');
                    }
                });
            }

            $exams = $examsQuery
                ->select('exams.*', 'courses.title as course_title', 'courses.code as course_code')
                ->orderBy('exams.exam_date')
                ->get();

            if ($exams->isEmpty()) {
                $exams = DB::table('exams')
                    ->join('courses', 'exams.course_id', '=', 'courses.course_id')
                    ->where('exams.class_group', $classGroup)
                    ->select('exams.*', 'courses.title as course_title', 'courses.code as course_code')
                    ->orderBy('exams.exam_date')
                    ->get();
            }

            if ($exams->isEmpty()) {
                $exams = DB::table('grade_events')
                    ->leftJoin('courses', 'grade_events.course_id', '=', 'courses.course_id')
                    ->whereIn('grade_events.type', ['exam', 'quiz'])
                    ->whereNotNull('grade_events.date')
                    ->select(
                        'grade_events.id as exam_id',
                        'grade_events.course_id',
                        'grade_events.title as exam_name',
                        'grade_events.date as exam_date',
                        'courses.title as course_title',
                        'courses.code as course_code'
                    )
                    ->orderBy('grade_events.date')
                    ->get();
            }

            foreach ($exams as $e) {
                $teacher = DB::table('course_teachers')
                    ->join('teachers', 'course_teachers.teacher_id', '=', 'teachers.teacher_id')
                    ->join('users', 'teachers.user_id', '=', 'users.user_id')
                    ->where('course_teachers.course_id', $e->course_id)
                    ->select('users.full_name')
                    ->first();
                $e->teacher_name = $teacher->full_name ?? 'هيئة التدريس';
            }

            $activeSemester = DB::table('semesters')->where('is_active', true)->first();
            $semesterName = $activeSemester?->name ?? 'الفصل الدراسي الأول (2025/2026)';

            $html = view('exports.exams_official_image', [
                'student' => $student,
                'exams' => $exams,
                'semesterName' => $semesterName,
            ])->render();

            $directory = public_path('exports');
            if (!file_exists($directory)) {
                mkdir($directory, 0755, true);
            }

            $timestamp = time();
            $tempHtmlPath = $directory . '/temp_exam_' . $student->student_id . '_' . $timestamp . '.html';
            $outPngName = 'exams_' . $student->student_id . '_' . $timestamp . '.png';
            $outPngPath = $directory . '/' . $outPngName;

            file_put_contents($tempHtmlPath, $html);

            $scriptPath = base_path('render_schedule.cjs');
            $cmd = 'node "' . str_replace('\\', '/', $scriptPath) . '" "' . str_replace('\\', '/', $tempHtmlPath) . '" "' . str_replace('\\', '/', $outPngPath) . '"';

            exec($cmd, $output, $code);

            if (file_exists($tempHtmlPath)) {
                @unlink($tempHtmlPath);
            }

            if ($code === 0 && file_exists($outPngPath)) {
                return $outPngPath;
            }

            Log::error("Exam Image Generation failed with exit code $code: " . implode("\n", $output));
            return null;
        } catch (\Exception $e) {
            Log::error("Exam Image Service Exception: " . $e->getMessage());
            return null;
        }
    }
}
