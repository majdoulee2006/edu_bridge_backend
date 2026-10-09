<?php

namespace App\Services\Risk\Factors;

use App\Services\Risk\RiskContext;

/**
 * عامل واحد من عوامل الخطر. يرجع نقاطًا من 0 (آمن) إلى 100 (شديد) مع سبب مكتوب وحقائق خام،
 * أو null إذا لم تتوفر بيانات كافية (فلا يُعاقَب الطالب على غياب البيانات).
 */
interface RiskFactor
{
    public function key(): string;

    public function label(): string;

    /** @return array{score: float, reason: string, facts: array}|null */
    public function evaluate(RiskContext $ctx): ?array;
}
