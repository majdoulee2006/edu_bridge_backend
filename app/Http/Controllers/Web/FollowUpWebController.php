<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Services\FollowUp\FollowUpDashboard;
use App\Services\Risk\RiskScope;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * لوحة متابعة الطلاب: /hod/follow-up لرئيس القسم (قسمه كله، مع جدول المرشدين) و/teacher/follow-up للمرشد (طلابه).
 * المنطق كله في FollowUpDashboard؛ هنا العرض والتحقق فقط.
 */
class FollowUpWebController extends Controller
{
    public function __construct(private FollowUpDashboard $dashboard)
    {
    }

    public function index(Request $request)
    {
        $prefix = $request->is('hod/*') ? 'hod' : 'teacher';
        $user   = Auth::user();

        if (RiskScope::kind($user) === null) {
            return redirect('/' . $prefix . '/dashboard')->with('error', 'هذه الصفحة للمرشدين ورؤساء الأقسام فقط.');
        }

        $f = $request->validate([
            'program_id' => 'nullable|integer',
            'year'       => 'nullable|integer|between:1,6',
            'status'     => 'nullable|in:' . implode(',', FollowUpDashboard::STATUSES),
            'sort'       => 'nullable|in:' . implode(',', FollowUpDashboard::SORTS),
            'search'     => 'nullable|string|max:100',
            'days'       => 'nullable|integer|between:7,120',
            'pending'    => 'nullable|boolean',
        ]);

        return view('followup.index', [
            'layout'  => $prefix === 'hod' ? 'layouts.hod' : 'layouts.teacher',
            'prefix'  => $prefix,
            'd'       => $this->dashboard->build($user, $f),
            'filters' => $f,
        ]);
    }
}
