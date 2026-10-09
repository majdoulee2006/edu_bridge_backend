<?php

namespace App\Services\Risk\Factors;

use App\Services\Risk\RiskContext;
use App\Services\Risk\Scale;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * الانقطاع عن المنصة: كم يومًا مضى على آخر دخول (تطبيق أو موقع). من لا يوجد له أي سجل دخول يُعدّ "لا بيانات":
 * قد لا يكون الدخول مسجَّلًا أصلًا، ولا يجوز اتهام الطالب بذلك.
 */
final class EngagementFactor implements RiskFactor
{
    public function key(): string
    {
        return 'engagement';
    }

    public function label(): string
    {
        return 'النشاط على المنصة';
    }

    public function evaluate(RiskContext $ctx): ?array
    {
        $candidates = [];

        $lastLogin = DB::table('users')->where('user_id', $ctx->userId)->value('last_login');
        if ($lastLogin) {
            $candidates[] = Carbon::parse($lastLogin);
        }

        $activity = DB::table('user_activities')
            ->where('user_id', $ctx->userId)
            ->where('action', 'like', 'تسجيل دخول%')
            ->where('created_at', '<=', $ctx->asOf)
            ->max('created_at');
        if ($activity) {
            $candidates[] = Carbon::parse($activity);
        }

        // آخر دخول بعد يوم التقييم لا يُعتدّ به (إعادة بناء الماضي)
        $candidates = array_filter($candidates, fn (Carbon $c) => $c->lte($ctx->asOf));
        if (!$candidates) {
            return null;
        }

        $last = max($candidates);
        $daysAgo = (int) $last->copy()->startOfDay()->diffInDays($ctx->asOfDay);

        return [
            'score' => Scale::curve($daysAgo, config('risk.engagement.curve')),
            'reason' => $daysAgo <= 1 ? 'دخل المنصة مؤخرًا' : 'لم يدخل المنصة منذ ' . $daysAgo . ' يومًا',
            'facts' => ['days_since_login' => $daysAgo, 'last_login_at' => $last->toDateString()],
        ];
    }
}
