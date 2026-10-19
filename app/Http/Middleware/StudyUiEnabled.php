<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

/** واجهات رفيق المذاكرة (ويب) معطّلة مؤقتاً: الصفحات تُرجع 404 حتى تُفعَّل من config/study.php. */
class StudyUiEnabled
{
    public function handle(Request $request, Closure $next)
    {
        abort_unless(config('study.ui_enabled'), 404);

        return $next($request);
    }
}
