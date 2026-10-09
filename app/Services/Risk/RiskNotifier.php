<?php

namespace App\Services\Risk;

use App\Models\Notification;
use App\Services\FcmService;
use App\Support\Access;
use Illuminate\Support\Facades\Log;

/**
 * تنبيه المرشد عندما يرتفع خطر طلابه: إشعار واحد مجمَّع لكل مرشد في كل تشغيل (لا إشعار لكل طالب)،
 * كي لا يتحول التنبيه إلى ضجيج يُتجاهل.
 */
class RiskNotifier
{
    private const LEVEL_LABELS = ['high' => 'عالٍ', 'medium' => 'متوسط', 'low' => 'منخفض'];

    /**
     * @param  array<int,array{student_id:int,name:string,level:string}> $rises
     * @return int عدد المستلمين الذين أُشعروا
     */
    public function notify(array $rises): int
    {
        $byRecipient = [];
        foreach ($rises as $r) {
            foreach (RiskScope::recipientsFor($r['student_id']) as $uid) {
                $byRecipient[$uid][] = $r;
            }
        }

        foreach ($byRecipient as $userId => $items) {
            $count = count($items);
            $names = collect($items)->take(3)->map(fn ($i) => $i['name'] . ' (' . (self::LEVEL_LABELS[$i['level']] ?? $i['level']) . ')')->implode('، ');
            $more  = $count > 3 ? ' و' . ($count - 3) . ' آخرين' : '';

            $title = '⚠️ طلاب ارتفع خطر تعثرهم';
            $body  = "ارتفع مستوى الخطر الأكاديمي لـ {$count} " . ($count === 1 ? 'طالب' : 'طلاب') . ": {$names}{$more}. راجع قائمة الطلاب المعرَّضين للتعثر.";

            try {
                Notification::create([
                    'user_id' => $userId, 'sender_id' => Access::systemSenderId(), 'title' => $title, 'message' => $body,
                    'type' => 'risk_alert', 'category' => 'administrative', 'related_id' => null, 'is_read' => false,
                ]);
                FcmService::sendToUser($userId, $title, $body, ['type' => 'risk_alert']);
            } catch (\Throwable $e) {
                Log::warning('Risk alert notification failed: ' . $e->getMessage());
            }
        }

        return count($byRecipient);
    }
}
