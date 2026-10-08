<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AttendanceFlag;
use App\Services\AttendanceIntelligence\FlagPresenter;
use App\Services\AttendanceIntelligence\FlagReviewService;
use App\Support\Access;
use Illuminate\Http\Request;

/**
 * طابور مراجعة الاشتباهات في الحضور (المعلّم): يرى اشتباهات مقرراته فقط ويقرر أكّد/رفض.
 * التأكيد لا يغيّر الحضور إلا إذا طلب المعلّم ذلك صراحةً (mark_absent).
 */
class AttendanceFlagController extends Controller
{
    public function __construct(protected FlagReviewService $flags)
    {
    }

    public function index(Request $request)
    {
        $teacher = $request->user()->teacher;
        $courseIds = $teacher ? $teacher->courses()->pluck('courses.course_id')->all() : [];

        $request->validate([
            'status'    => 'nullable|in:open,confirmed,dismissed,all',
            'severity'  => 'nullable|in:low,medium,high',
            'course_id' => 'nullable|integer',
        ]);

        $page = $this->flags
            ->queryForCourses($courseIds, $request->query('status', 'open'), $request->query('severity'), $request->filled('course_id') ? (int) $request->course_id : null)
            ->paginate(min((int) $request->query('per_page', 20), 50));

        return response()->json([
            'success' => true,
            'summary' => $this->flags->openSummary($courseIds),
            'data'    => collect($page->items())->map(fn ($f) => $this->present($f))->values(),
            'meta'    => ['current_page' => $page->currentPage(), 'last_page' => $page->lastPage(), 'total' => $page->total()],
        ]);
    }

    public function review(Request $request, $id)
    {
        $data = $request->validate([
            'status'      => 'required|in:confirmed,dismissed',
            'note'        => 'nullable|string|max:500',
            'mark_absent' => 'nullable|boolean',
        ]);

        $flag = AttendanceFlag::find($id);
        $teacher = $request->user()->teacher;

        // اشتباه خارج مقرراتك يبدو كأنه غير موجود (لا نكشف وجوده)
        if (!$flag || !$teacher || !Access::teacherTeachesCourse((int) $teacher->teacher_id, $flag->course_id)) {
            return response()->json(['success' => false, 'message' => 'الاشتباه غير موجود'], 404);
        }

        try {
            $flag = $this->flags->review($flag, $request->user(), $data['status'], $data['note'] ?? null, (bool) ($data['mark_absent'] ?? false));
        } catch (\DomainException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 409);
        }

        $markAbsent = (bool) ($flag->details['marked_absent'] ?? false);

        return response()->json([
            'success' => true,
            'message' => $markAbsent ? 'تم تأكيد الاشتباه وتسجيل الطالب غائبًا' : ($data['status'] === 'confirmed' ? 'تم تأكيد الاشتباه' : 'تم رفض الاشتباه'),
            'data'    => $this->present($flag),
        ]);
    }

    protected function present(AttendanceFlag $f): array
    {
        $a = $f->attendance;

        return [
            'id'          => $f->id,
            'rule'        => $f->rule,
            'rule_label'  => FlagPresenter::label($f->rule),
            'severity'    => $f->severity,
            'severity_label' => FlagPresenter::severityLabel($f->severity),
            'status'      => $f->status,
            'explanation' => FlagPresenter::explain($f),
            'student'     => ['id' => $f->student_id, 'name' => $f->student->user->full_name ?? ''],
            'course'      => ['id' => $f->course_id, 'title' => $f->course->title ?? ''],
            'attendance'  => $a ? [
                'id' => $a->attendance_id, 'date' => optional($a->attendance_date)->toDateString(),
                'status' => $a->status, 'face_score' => $a->face_score,
            ] : null,
            'review'      => $f->status === AttendanceFlag::OPEN ? null : [
                'note' => $f->review_note, 'at' => optional($f->reviewed_at)->toIso8601String(),
                'marked_absent' => (bool) ($f->details['marked_absent'] ?? false),
            ],
            'created_at'  => $f->created_at->toIso8601String(),
        ];
    }
}
