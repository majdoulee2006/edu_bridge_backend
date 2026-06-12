<?php

require_once __DIR__ . '/../app/helpers.php';

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        channels: __DIR__.'/../routes/channels.php',
        health: '/up',
    )
    ->withSchedule(function (\Illuminate\Console\Scheduling\Schedule $schedule): void {
        // إرسال ملخص الحضور اليومي للمربين في نهاية كل يوم
        $schedule->command('attendance:daily-summary')->dailyAt('22:00');
    })
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'role'    => \App\Http\Middleware\RoleMiddleware::class,
            'hod'     => \App\Http\Middleware\CheckHodRole::class,
            'teacher' => \App\Http\Middleware\CheckTeacherRole::class,
            'affairs' => \App\Http\Middleware\CheckAffairsRole::class,
            'admin'   => \App\Http\Middleware\CheckAdminRole::class,
        ]);

        $middleware->validateCsrfTokens(except: [
            'affairs/accounts',
            'affairs/accounts/*',
        ]);

        // منع التحويل لـ api/login عند استخدام auth middleware
        $middleware->redirectGuestsTo('/affairs/login');
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
