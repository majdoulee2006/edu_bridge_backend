<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AttendanceFlag;
use App\Services\AttendanceIntelligence\AttendanceAnalyticsService;
use App\Services\AttendanceIntelligence\FlagPresenter;
use App\Support\Access;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * تحليلات الحضور لرئيس القسم (قسمه فقط) وللأدمن (الكل): خريطة حرارية وترتيب واتجاه وملخص اشتباهات.
 * قراءة فقط: رئيس القسم يرى الاشتباهات لكن القرار فيها للمعلم.
 */
class AttendanceInsightsController extends Controller
{
    public function insights(Request $request, AttendanceAnalyticsService $analytics)
    {
        $request->validate([
            'from'       => 'nullable|date',
            'to'         => 'nullable|date|after_or_equal:from',
            'program_id' => 'nullable|integer',
            'course_id'  => 'nullable|integer',
        ]);

        [$scope, $error] = $this->resolveScope($request);
        if ($error) {
            return $error;
        }

        $to   = $request->filled('to') ? Carbon::parse($request->to) : now();
        $from = $request->filled('from')
            ? Carbon::parse($request->from)
            : $to->copy()->subDays((int) config('attendance_intelligence.analytics.default_days', 42));

        return response()->json([
            'success' => true,
            'data'    => $analytics->build($scope, $from, $to),
            'filters' => $this->filters($request),
        ]);
    }

    /** اشتباهات النطاق (للقراءة فقط). */
    public function flags(Request $request)
    {
        $request->validate(['status' => 'nullable|in:open,confirmed,dismissed,all']);

        [$scope, $error] = $this->resolveScope($request);
        if ($error) {
            return $error;
        }

        $status = $request->query('status', 'open');

        $page = AttendanceFlag::with(['student.user:user_id,full_name', 'course:course_id,title'])
            ->when($scope !== null, fn ($q) => $q->whereIn('course_id', $scope))
            ->when($status !== 'all', fn ($q) => $q->where('status', $status))
            ->orderByRaw("FIELD(severity, 'high', 'medium', 'low')")
            ->orderByDesc('id')
            ->paginate(min((int) $request->query('per_page', 20), 50));

        return response()->json([
            'success' => true,
            'data' => collect($page->items())->map(fn ($f) => [
                'id' => $f->id, 'rule' => $f->rule, 'rule_label' => FlagPresenter::label($f->rule),
                'severity' => $f->severity, 'severity_label' => FlagPresenter::severityLabel($f->severity),
                'status' => $f->status, 'explanation' => FlagPresenter::explain($f),
                'student' => $f->student->user->full_name ?? '', 'course' => $f->course->title ?? '',
                'created_at' => $f->created_at->toIso8601String(),
            ])->values(),
            'meta' => ['current_page' => $page->currentPage(), 'last_page' => $page->lastPage(), 'total' => $page->total()],
        ]);
    }

    /**
     * نطاق المقررات المسموح للمستخدم الحالي (انظر AttendanceAnalyticsService::scopeCourseIds).
     *
     * @return array{0: ?array, 1: ?\Illuminate\Http\JsonResponse}
     */
    private function resolveScope(Request $request): array
    {
        try {
            $scope = app(AttendanceAnalyticsService::class)->scopeCourseIds(
                $request->user(),
                $request->filled('program_id') ? (int) $request->program_id : null,
                $request->filled('course_id') ? (int) $request->course_id : null,
            );
        } catch (\Illuminate\Auth\Access\AuthorizationException $e) {
            return [null, response()->json(['success' => false, 'message' => $e->getMessage()], 403)];
        }

        return [$scope, null];
    }

    /** خيارات الفلترة المتاحة لهذا المستخدم (لتعبئة القوائم في الواجهة). */
    private function filters(Request $request): array
    {
        $user = $request->user();
        $deptId = (int) $user->role_id === 5 ? Access::headDepartment($user)['id'] : null;

        $programs = DB::table('programs')
            ->when($deptId, fn ($q) => $q->where('department_id', $deptId))
            ->orderBy('name')->get(['id', 'name']);

        return ['programs' => $programs, 'rules' => FlagPresenter::allLabels()];
    }
}
