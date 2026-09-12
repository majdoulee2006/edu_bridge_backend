<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

/**
 * مصدر الحقيقة الوحيد لحساب معدل الطالب الموزون بتثقيل المواد (courses.weight).
 * المعادلة: Σ(نتيجة المادة × وزن المادة) / Σ(وزن المادة)
 * منسوخة عن AffairsController::getStudentAcademicCardForAffairs لضمان نفس النتيجة
 * أينما ظهر معدل الطالب (الشؤون، الطالب، ولي الأمر، رئيس القسم، الأدمن...).
 */
class StudentAcademicService
{
    public static function getAcademicSummary(int $studentId, ?int $year = null): array
    {
        $coursesQuery = DB::table('enrollments')
            ->join('courses', 'enrollments.course_id', '=', 'courses.course_id')
            ->where('enrollments.student_id', $studentId);

        if ($year !== null) {
            $coursesQuery->where('courses.year', $year);
        }

        $enrolledCourses = $coursesQuery->select('courses.*')->get();

        if ($enrolledCourses->isEmpty()) {
            $fallback = DB::table('courses');
            if ($year !== null) {
                $fallback->where('year', $year);
            }
            $enrolledCourses = $fallback->get();
        }

        $academicCardData = [];
        $totalWeightedScores = 0;
        $totalWeightsSum = 0;
        $passedCount = 0;
        $failedCount = 0;
        $notAttendedCount = 0;

        foreach ($enrolledCourses as $course) {
            $examGrades = DB::table('grades')
                ->join('exams', 'grades.exam_id', '=', 'exams.exam_id')
                ->where('grades.student_id', $studentId)
                ->where('exams.course_id', $course->course_id)
                ->select('grades.score', 'exams.exam_name')
                ->get();

            $eventGrades = DB::table('grade_entries')
                ->join('grade_events', 'grade_entries.grade_event_id', '=', 'grade_events.id')
                ->where('grade_entries.student_id', $studentId)
                ->where('grade_events.course_id', $course->course_id)
                ->select('grade_entries.score', 'grade_events.type as event_type', 'grade_events.title')
                ->get();

            $quizScore = null;
            $oralScore = null;
            $finalScore = null;

            foreach ($eventGrades as $eg) {
                $type = strtolower($eg->event_type ?? '');
                if (str_contains($type, 'quiz') || str_contains($type, 'مذاكرة')) {
                    $quizScore = $eg->score;
                } elseif (str_contains($type, 'oral') || str_contains($type, 'عملي') || str_contains($type, 'شفهي')) {
                    $oralScore = $eg->score;
                } elseif (str_contains($type, 'exam') || str_contains($type, 'امتحان') || str_contains($type, 'نهائي')) {
                    $finalScore = $eg->score;
                } else {
                    if ($quizScore === null) $quizScore = $eg->score;
                }
            }

            foreach ($examGrades as $eg) {
                $name = mb_strtolower($eg->exam_name ?? '');
                if (str_contains($name, 'مذاكرة') || str_contains($name, 'quiz')) {
                    if ($quizScore === null) $quizScore = $eg->score;
                } elseif (str_contains($name, 'عملي') || str_contains($name, 'شفهي')) {
                    if ($oralScore === null) $oralScore = $eg->score;
                } else {
                    if ($finalScore === null) $finalScore = $eg->score;
                }
            }

            $hasAnyScore = ($quizScore !== null || $oralScore !== null || $finalScore !== null);
            $totalScore = null;
            $weightedScore = null;
            $courseWeight = $course->weight ?? 1;
            $status = 'لم يتم التقدم';

            if ($hasAnyScore) {
                $q = $quizScore ?? 0;
                $o = $oralScore ?? 0;
                $f = $finalScore ?? 0;
                $totalScore = min(100, $q + $o + $f);
                $weightedScore = $totalScore * $courseWeight;

                if ($totalScore >= 50) {
                    $status = 'ناجح';
                    $passedCount++;
                } else {
                    $status = 'راسب';
                    $failedCount++;
                }

                $totalWeightedScores += $weightedScore;
                $totalWeightsSum += $courseWeight;
            } else {
                $notAttendedCount++;
            }

            $academicCardData[] = [
                'course_id'      => $course->course_id,
                'title'          => $course->title,
                'code'           => $course->code ?? '',
                'year'           => $course->year,
                'semester'       => $course->semester ?? 1,
                'weight'         => $courseWeight,
                'quiz_score'     => $quizScore !== null ? (float)$quizScore : null,
                'oral_score'     => $oralScore !== null ? (float)$oralScore : null,
                'final_score'    => $finalScore !== null ? (float)$finalScore : null,
                'total_score'    => $totalScore !== null ? (float)$totalScore : null,
                'weighted_score' => $weightedScore !== null ? (float)$weightedScore : null,
                'status'         => $status,
            ];
        }

        $average = $totalWeightsSum > 0 ? round($totalWeightedScores / $totalWeightsSum, 2) : 0;

        return [
            'average'        => $average,
            'total_courses'  => count($academicCardData),
            'passed_courses' => $passedCount,
            'failed_courses' => $failedCount,
            'not_attended'   => $notAttendedCount,
            'academic_card'  => $academicCardData,
        ];
    }

    /**
     * اختصار عند الحاجة للمعدل الموزون فقط (0-100) دون تفاصيل المواد.
     */
    public static function getWeightedAverage(int $studentId, ?int $year = null): float
    {
        return self::getAcademicSummary($studentId, $year)['average'];
    }
}
