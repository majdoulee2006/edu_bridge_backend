<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\RiskIntervention;
use App\Services\Risk\RiskDirectory;
use App\Services\Risk\RiskScope;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * صفحات الإنذار المبكر للمرشد (/teacher/risk) ولرئيس القسم (/hod/risk): صفحتان مشتركتان بتخطيطين مختلفين.
 * المنطق كله في RiskDirectory/RiskScope، وهنا العرض فقط.
 */
class RiskWebController extends Controller
{
    public function __construct(private RiskDirectory $directory)
    {
    }

    public function index(Request $request)
    {
        if ($redirect = $this->guard($request)) {
            return $redirect;
        }

        $f = $request->validate([
            'level'      => 'nullable|in:high,medium,low,unknown,at_risk',
            'search'     => 'nullable|string|max:100',
            'program_id' => 'nullable|integer',
            'sort'       => 'nullable|in:score,change',
        ]);

        $user = Auth::user();
        $out  = $this->directory->list($user, $f, 15);

        // البرامج المتاحة للفلترة ضمن نطاق المستخدم
        $programs = RiskScope::students($user)
            ->whereNotNull('programs.id')->distinct()->orderBy('programs.name')->get(['programs.id', 'programs.name']);

        return view('risk.index', $this->common($request) + [
            'summary'   => $out['summary'],
            'rows'      => $out['rows'],
            'paginator' => $out['paginator'],
            'programs'  => $programs,
            'filters'   => $f + ['level' => $f['level'] ?? null],
        ]);
    }

    public function show(Request $request, $id)
    {
        if ($redirect = $this->guard($request)) {
            return $redirect;
        }

        $data = $this->directory->detail(Auth::user(), (int) $id);
        abort_unless($data, 404);

        return view('risk.show', $this->common($request) + ['d' => $data]);
    }

    public function storeIntervention(Request $request, $id)
    {
        if ($redirect = $this->guard($request)) {
            return $redirect;
        }

        $data = $request->validate([
            'type'         => 'required|in:' . implode(',', array_keys(RiskIntervention::TYPES)),
            'note'         => 'nullable|string|max:1000',
            'follow_up_on' => 'nullable|date|after_or_equal:today',
        ]);

        abort_unless($this->directory->addIntervention(Auth::user(), (int) $id, $data), 404);

        return back()->with('success', 'تم تسجيل الإجراء.');
    }

    // ----------------------------------------------------------------- مساعدة

    /** من ليس مرشدًا/رئيس قسم/أدمن يُعاد إلى لوحته برسالة. */
    private function guard(Request $request)
    {
        if (RiskScope::kind(Auth::user()) !== null) {
            return null;
        }

        return redirect('/' . $this->prefix($request) . '/dashboard')->with('error', 'هذه الصفحة للمرشدين ورؤساء الأقسام فقط.');
    }

    private function prefix(Request $request): string
    {
        return $request->is('hod/*') ? 'hod' : 'teacher';
    }

    private function common(Request $request): array
    {
        $prefix = $this->prefix($request);

        return ['layout' => $prefix === 'hod' ? 'layouts.hod' : 'layouts.teacher', 'prefix' => $prefix];
    }
}
