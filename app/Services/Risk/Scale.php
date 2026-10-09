<?php

namespace App\Services\Risk;

/** تحويلات رقمية صغيرة مشتركة بين عوامل الخطر. */
final class Scale
{
    /**
     * يحوّل قيمة من مجال [$at0 .. $at100] إلى 0..100 خطيًا ويقصّ خارجه.
     * يعمل بالاتجاهين: scale(حضور, 90, 30) يعطي 0 عند 90 و100 عند 30.
     */
    public static function linear(float $value, float $at0, float $at100): float
    {
        if ($at0 === $at100) {
            return 0.0;
        }

        $t = ($value - $at0) / ($at100 - $at0);

        return round(max(0.0, min(1.0, $t)) * 100, 1);
    }

    /** نقاط من منحنى نقاط مفتاحية [قيمة => نقاط] مع استكمال خطي بينها. */
    public static function curve(float $value, array $points): float
    {
        ksort($points);
        $prevX = null;
        $prevY = null;

        foreach ($points as $x => $y) {
            if ($value <= $x) {
                if ($prevX === null) {
                    return (float) $y;
                }
                $t = ($value - $prevX) / ($x - $prevX);

                return round($prevY + $t * ($y - $prevY), 1);
            }
            $prevX = $x;
            $prevY = $y;
        }

        return (float) $prevY;
    }

    /** يُظهر رقمًا بلا كسر زائد: 90.0 → "90"، 87.5 → "87.5". */
    public static function pretty(float $n): string
    {
        return rtrim(rtrim(number_format($n, 1, '.', ''), '0'), '.');
    }
}
