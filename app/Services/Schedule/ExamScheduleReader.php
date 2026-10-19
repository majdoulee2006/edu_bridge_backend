<?php

namespace App\Services\Schedule;

use App\Models\Exam;
use Carbon\Carbon;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * ما يراه الطالب والمعلم من الجدول الامتحاني المعتمد (ويب وتطبيق وصورة رسمية).
 * يُكتب الجدول في exams/exam_seatings/exam_invigilators عند الاعتماد فقط، فلا شيء هنا يظهر قبله.
 */
final class ExamScheduleReader
{
    /**
     * يحصر استعلام exams على ما يخص الطالب: تقييم موزّع على قاعات يظهر لمن له فيه مقعد فقط
     * (فلا يرى طالب دورة أخرى تقييماً لمقرر مشترك)، والتقييمات القديمة بلا توزيع تبقى كما كانت.
     */
    public static function restrictToStudent(Builder $query, int $studentId): Builder
    {
        return $query->where(function ($q) use ($studentId) {
            $q->whereExists(fn ($s) => $s->select(DB::raw(1))->from('exam_seatings')->whereColumn('exam_seatings.exam_id', 'exams.exam_id')->where('exam_seatings.student_id', $studentId))
              ->orWhereNotExists(fn ($s) => $s->select(DB::raw(1))->from('exam_seatings')->whereColumn('exam_seatings.exam_id', 'exams.exam_id'));
        });
    }

    /**
     * يضيف لكل تقييم حقول العرض: النوع (مذاكرة/امتحان)، البداية والنهاية والمدة، وقاعة الطالب ومقعده إن وُزّع.
     *
     * @param Collection<int,object> $exams صفوف exams (stdClass) مع course_title
     * @return Collection<int,object>
     */
    public static function decorateForStudent(Collection $exams, int $studentId): Collection
    {
        $seats = DB::table('exam_seatings')->where('student_id', $studentId)->whereIn('exam_id', $exams->pluck('exam_id')->filter())->get(['exam_id', 'room', 'seat'])->keyBy('exam_id');

        return $exams->map(function ($e) use ($seats) {
            self::decorate($e);
            $seat = $seats->get($e->exam_id ?? null);
            if ($seat) {
                $e->room = $seat->room;
                $e->seat = (int) $seat->seat;
            }

            return $e;
        });
    }

    /** يضيف حقول العرض المشتقة من صف exams واحد. */
    public static function decorate(object $e): object
    {
        $type = $e->exam_type ?? 'final';
        $start = isset($e->exam_date) ? Carbon::parse($e->exam_date) : null;
        $duration = (int) ($e->duration_minutes ?? 120);

        $e->kind = $type;
        $e->type_label = Exam::TYPES[$type] ?? 'امتحان';
        $e->duration_minutes = $duration;
        $e->start_hm = $start?->format('H:i');
        $e->end_hm = $start?->copy()->addMinutes($duration)->format('H:i');
        $e->seat = $e->seat ?? null;

        return $e;
    }

    /**
     * تقييمات مقررات المعلم، كلٌّ مع النوع والمدة وقاعاته وطلاب كل قاعة ومراقبها.
     *
     * @param int[] $courseIds
     * @return Collection<int,array<string,mixed>>
     */
    public static function forCourses(array $courseIds): Collection
    {
        if (!$courseIds) {
            return collect();
        }
        $exams = DB::table('exams')->join('courses', 'courses.course_id', '=', 'exams.course_id')
            ->whereIn('exams.course_id', $courseIds)->orderBy('exams.exam_date')->select('exams.*', 'courses.title as course_title')->get();

        return self::withRooms($exams);
    }

    /**
     * قاعات المراقبة المسندة لمعلم (user_id): التقييم وقاعته وقائمة طلابها.
     *
     * @return Collection<int,array<string,mixed>>
     */
    public static function invigilationOf(int $userId): Collection
    {
        $rows = DB::table('exam_invigilators')->where('user_id', $userId)->get(['exam_id', 'room']);
        if ($rows->isEmpty()) {
            return collect();
        }
        $exams = DB::table('exams')->join('courses', 'courses.course_id', '=', 'exams.course_id')
            ->whereIn('exams.exam_id', $rows->pluck('exam_id'))->orderBy('exams.exam_date')->select('exams.*', 'courses.title as course_title')->get();
        $mine = $rows->groupBy('exam_id')->map(fn ($r) => $r->pluck('room')->all());

        return self::withRooms($exams, $mine);
    }

    /**
     * @param Collection<int,object> $exams
     * @param Collection<int,string[]>|null $onlyRooms exam_id => القاعات المطلوبة (فارغ = كلها)
     */
    private static function withRooms(Collection $exams, ?Collection $onlyRooms = null): Collection
    {
        $ids = $exams->pluck('exam_id')->all();
        $seats = DB::table('exam_seatings')->join('students', 'students.student_id', '=', 'exam_seatings.student_id')->join('users', 'users.user_id', '=', 'students.user_id')
            ->whereIn('exam_seatings.exam_id', $ids)->orderBy('exam_seatings.room')->orderBy('exam_seatings.seat')
            ->get(['exam_seatings.exam_id', 'exam_seatings.room', 'exam_seatings.seat', 'exam_seatings.student_id', 'users.full_name'])->groupBy('exam_id');
        $proctors = DB::table('exam_invigilators')->join('users', 'users.user_id', '=', 'exam_invigilators.user_id')
            ->whereIn('exam_invigilators.exam_id', $ids)->get(['exam_invigilators.exam_id', 'exam_invigilators.room', 'users.full_name', 'users.user_id'])->groupBy('exam_id');

        return $exams->map(function ($e) use ($seats, $proctors, $onlyRooms) {
            self::decorate($e);
            $byRoom = ($seats[$e->exam_id] ?? collect())->groupBy('room');
            $rooms = [];
            foreach ($byRoom as $room => $students) {
                if ($onlyRooms && !in_array($room, $onlyRooms[$e->exam_id] ?? [], true)) {
                    continue;
                }
                $p = ($proctors[$e->exam_id] ?? collect())->firstWhere('room', $room);
                $rooms[] = [
                    'name' => $room, 'count' => $students->count(), 'proctor' => $p->full_name ?? null, 'proctor_id' => $p->user_id ?? null,
                    'students' => $students->map(fn ($s) => ['id' => (int) $s->student_id, 'name' => $s->full_name, 'seat' => (int) $s->seat])->values()->all(),
                ];
            }

            return [
                'exam_id' => (int) $e->exam_id, 'course_id' => (int) $e->course_id, 'course' => $e->course_title, 'class_group' => $e->class_group,
                'kind' => $e->kind, 'type_label' => $e->type_label, 'date' => Carbon::parse($e->exam_date)->toDateString(), 'start' => $e->start_hm, 'end' => $e->end_hm,
                'duration_minutes' => $e->duration_minutes, 'rooms' => $rooms,
            ];
        })->filter(fn ($x) => !$onlyRooms || $x['rooms'])->values();
    }
}
