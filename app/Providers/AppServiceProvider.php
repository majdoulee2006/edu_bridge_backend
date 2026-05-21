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
        \Illuminate\Support\Facades\DB::statement("SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci");
    }
}
