<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Routing\ResponseFactory;
use Illuminate\Contracts\View\Factory as ViewFactory;
use Illuminate\Routing\Redirector;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $factory = function ($app) {
            return new class($app[ViewFactory::class], $app[Redirector::class]) extends ResponseFactory {
                public function json($data = [], $status = 200, array $headers = [], $options = 0)
                {
                    return parent::json(
                        $data,
                        $status,
                        $headers,
                        $options | JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE
                    );
                }
            };
        };

        $this->app->singleton(ResponseFactory::class, $factory);
        $this->app->singleton(\Illuminate\Contracts\Routing\ResponseFactory::class, $factory);
    }

    public function boot(): void
    {
        if (config('app.env') === 'production' || request()->secure() || request()->header('X-Forwarded-Proto') === 'https') {
            \Illuminate\Support\Facades\URL::forceScheme('https');
        }

        if (\Illuminate\Support\Facades\DB::getDriverName() === 'mysql') {
            \Illuminate\Support\Facades\DB::statement("SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci");
        }
        
        \Illuminate\Support\Facades\RateLimiter::for('api', function (\Illuminate\Http\Request $request) {
            // 🐛 كانت هون ->id بينما مفتاح المستخدم الحقيقي هو user_id (primary
            // key مخصص بموديل User)، فـ ->id كانت دايماً null وبالتالي كل
            // المستخدمين المصادقين كانوا يشاركوا نفس سقف الـ IP الواحد بدل
            // سقف مستقل لكل مستخدم — وهاد كان بيسبب 429 بسرعة لأي عدد أجهزة
            // خلف نفس الشبكة/الـ IP (متل كل الطلاب بجامعة وحدة، أو هون عبر
            // نفق USB reverse يلي بيخلي كل الطلبات تبدو جايي من 127.0.0.1).
            // كمان رفعت السقف لأن شات فعلي بـ polling (رسائل كل ٢-٥ ثواني +
            // جهات اتصال) بيحتاج أكتر بكتير من ٦٠ طلب بالدقيقة الواحدة.
            return \Illuminate\Cache\RateLimiting\Limit::perMinute(240)->by($request->user()?->user_id ?: $request->ip());
        });

        // Register Observers
        \App\Models\Grade::observe(\App\Observers\GradeObserver::class);
        \App\Models\Attendance::observe(\App\Observers\AttendanceObserver::class);

        // منع تسجيل الدخول المتزامن على الويب: كل تسجيل دخول ناجح (عبر
        // Auth::login أو Auth::attempt من أي controller) يسجّل جلسته كـ
        // "الجلسة الحالية" الوحيدة الصالحة للحساب (راجع EnsureSingleWebSession
        // و SingleSessionGuard::isOccupied لمنطق الرفض عند تسجيل الدخول).
        \Illuminate\Support\Facades\Event::listen(
            \Illuminate\Auth\Events\Login::class,
            function (\Illuminate\Auth\Events\Login $event) {
                if ($event->guard === 'web') {
                    $event->user->forceFill([
                        'current_session_id'     => request()->session()->getId(),
                        'session_last_active_at' => now(),
                    ])->save();
                }
            }
        );

        // مسح بيانات الجلسة النشطة على الويب عند تسجيل الخروج
        \Illuminate\Support\Facades\Event::listen(
            \Illuminate\Auth\Events\Logout::class,
            function (\Illuminate\Auth\Events\Logout $event) {
                if ($event->guard === 'web' && $event->user instanceof \App\Models\User) {
                    $event->user->forceFill([
                        'current_session_id'     => null,
                        'session_last_active_at' => null,
                    ])->save();
                }
            }
        );
    }
}
