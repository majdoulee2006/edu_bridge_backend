<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\FollowUp\FollowUpDashboard;
use App\Services\Risk\RiskScope;
use Illuminate\Http\Request;

/**
 * GET /api/{teacher|department-head|admin}/follow-up: نفس لوحة الويب كـ JSON لتطبيق Flutter.
 * النطاق من RiskScope: المرشد طلاب فرعه وسنته، رئيس القسم قسمه، الأدمن الكل، وغيرهم 403.
 */
class FollowUpController extends Controller
{
    public function __construct(private FollowUpDashboard $dashboard)
    {
    }

    public function index(Request $request)
    {
        $user = $request->user();
        if (RiskScope::kind($user) === null) {
            return response()->json(['success' => false, 'message' => 'غير مصرح'], 403);
        }

        $f = $request->validate([
            'program_id' => 'nullable|integer',
            'year'       => 'nullable|integer|between:1,6',
            'status'     => 'nullable|in:' . implode(',', FollowUpDashboard::STATUSES),
            'sort'       => 'nullable|in:' . implode(',', FollowUpDashboard::SORTS),
            'search'     => 'nullable|string|max:100',
            'days'       => 'nullable|integer|between:7,120',
        ]);

        return response()->json(['success' => true] + $this->dashboard->build($user, $f), 200);
    }
}
