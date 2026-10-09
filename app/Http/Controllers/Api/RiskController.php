<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\RiskIntervention;
use App\Services\Risk\RiskDirectory;
use App\Services\Risk\RiskScope;
use Illuminate\Http\Request;

/**
 * الطلاب المعرَّضون للتعثر: للمرشد (فرعه وسنته)، ولرئيس القسم (قسمه)، وللأدمن (الكل).
 * الطالب وولي الأمر وبقية المعلمين لا يصلون إلى هذه البيانات إطلاقًا.
 */
class RiskController extends Controller
{
    public function __construct(private RiskDirectory $directory)
    {
    }

    public function index(Request $request)
    {
        if (RiskScope::kind($request->user()) === null) {
            return $this->forbidden();
        }

        $f = $request->validate([
            'level'      => 'nullable|in:high,medium,low,unknown,at_risk',
            'search'     => 'nullable|string|max:100',
            'program_id' => 'nullable|integer',
            'sort'       => 'nullable|in:score,change',
        ]);

        $out  = $this->directory->list($request->user(), $f, (int) $request->query('per_page', 20));
        $page = $out['paginator'];

        return response()->json([
            'success' => true,
            'summary' => $out['summary'],
            'data'    => $out['rows'],
            'meta'    => ['current_page' => $page->currentPage(), 'last_page' => $page->lastPage(), 'total' => $page->total()],
        ]);
    }

    public function show(Request $request, $studentId)
    {
        if (RiskScope::kind($request->user()) === null) {
            return $this->forbidden();
        }

        $data = $this->directory->detail($request->user(), (int) $studentId);

        return $data
            ? response()->json(['success' => true, 'data' => $data])
            : response()->json(['success' => false, 'message' => 'الطالب غير موجود'], 404);
    }

    public function storeIntervention(Request $request, $studentId)
    {
        if (RiskScope::kind($request->user()) === null) {
            return $this->forbidden();
        }

        $data = $request->validate([
            'type'         => 'required|in:' . implode(',', array_keys(RiskIntervention::TYPES)),
            'note'         => 'nullable|string|max:1000',
            'follow_up_on' => 'nullable|date|after_or_equal:today',
        ]);

        $iv = $this->directory->addIntervention($request->user(), (int) $studentId, $data);

        return $iv
            ? response()->json(['success' => true, 'message' => 'تم تسجيل الإجراء', 'data' => $iv], 201)
            : response()->json(['success' => false, 'message' => 'الطالب غير موجود'], 404);
    }

    private function forbidden()
    {
        return response()->json(['success' => false, 'message' => 'هذه الخدمة للمرشدين ورؤساء الأقسام فقط'], 403);
    }
}
