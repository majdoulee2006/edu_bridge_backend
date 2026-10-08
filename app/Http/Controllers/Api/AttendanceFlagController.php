<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\AttendanceFlag;
use App\Services\AttendanceIntelligence\FlagPresenter;
use App\Support\Access;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * طابور مراجعة الاشتباهات في الحضور (المعلّم): يرى اشتباهات مقرراته فقط ويقرر أكّد/رفض.
 * التأكيد لا يغيّر الحضور إلا إذا طلب المعلّم ذلك صراحةً (mark_absent).
 */
class AttendanceFlagController extends Controller
{
    public function index(Request $request)
    {
        $teacher = $request->user()->teacher;
        $courseIds = $teacher ? $teacher->courses()->pluck('courses.course_id')->all() : [];

        $request->validate([
            'status'    => 'nullable|in:open,confirmed,dismissed,all',
            'severity'  => 'nullable|in:low,medium,high',
            'course_id' => 'nullable|integer',
        ]);

        $status = $request->query('status', 'open');

        $query = AttendanceFlag::with(['student.user:user_id,full_name', 'course:course_id,title', 'attendance'])
            ->whereIn('course_id', $courseIds)
            ->when($status !== 'all', fn ($q) => $q->where('status', $status))
            ->when($request->filled('severity'), fn ($q) => $q->where('severity', $request->severity))
            ->when($request->filled('course_id'), fn ($q) => $q->where('course_id', (int) $request->course_id))
            ->orderByRaw("FIELD(severity, 'high', 'medium', 'low')")
            ->orderByDesc('id');

        $page = $query->paginate(min((int) $request->query('per_page', 20), 50));

        $openCounts = AttendanceFlag::whereIn('course_id', $courseIds)->where('status', 'open')
            ->selectRaw('severity, COUNT(*) AS n')->groupBy('severity')->pluck('n', 'severity');

        return response()->json([
            'success' => true,
            'summary' => [
                'open' => (int) $openCounts->sum(),
                'by_severity' => [
                    'high' => (int) ($openCounts['high'] ?? 0),
                    'medium' => (int) ($openCounts['medium'] ?? 0),
                    'low' => (int) ($openCounts['low'] ?? 0),
                ],
            ],
            'data' => collect($page->items())->map(fn ($f) => $this->present($f))->values(),
            'meta' => ['current_page' => $page->currentPage(), 'last_page' => $page->lastPage(), 'total' => $page->total()],
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

        if ($flag->status !== AttendanceFlag::OPEN) {
            return response()->json(['success' => false, 'message' => 'تمت مراجعة هذا الاشتباه مسبقًا'], 409);
        }

        $markAbsent = $data['status'] === 'confirmed' && (bool) ($data['mark_absent'] ?? false);

        DB::transaction(function () use ($flag, $data, $request, $markAbsent) {
            $flag->update([
                'status'      => $data['status'],
                'review_note' => $data['note'] ?? null,
                'reviewed_by' => $request->user()->user_id,
                'reviewed_at' => now(),
                'details'     => array_merge($flag->details ?? [], ['marked_absent' => $markAbsent]),
            ]);

            if ($markAbsent) {
                // عبر الموديل ليعمل AttendanceObserver (إشعار الطالب وتحذيرات الغياب)
                Attendance::where('attendance_id', $flag->attendance_id)->first()?->update(['status' => 'absent']);
            }
        });

        return response()->json([
            'success' => true,
            'message' => $markAbsent ? 'تم تأكيد الاشتباه وتسجيل الطالب غائبًا' : ($data['status'] === 'confirmed' ? 'تم تأكيد الاشتباه' : 'تم رفض الاشتباه'),
            'data'    => $this->present($flag->fresh(['student.user:user_id,full_name', 'course:course_id,title', 'attendance'])),
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
