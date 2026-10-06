<?php

namespace Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use RuntimeException;

abstract class TestCase extends BaseTestCase
{
    use RefreshDatabase {
        refreshDatabase as protected laravelRefreshDatabase;
    }

    /**
     * الاختبارات تمسح القاعدة وتعيد بناءها (migrate:fresh). لذلك نرفض تشغيلها
     * على أي قاعدة لا ينتهي اسمها بـ "_test" حتى لا تُمسح بيانات حقيقية بالخطأ.
     */
    public function refreshDatabase(): void
    {
        $connection = config('database.default');
        $database   = (string) config("database.connections.{$connection}.database");

        if (!str_ends_with($database, '_test')) {
            throw new RuntimeException(
                "Refusing to run tests against database '{$database}'. " .
                "The test database name must end with '_test' (see phpunit.xml)."
            );
        }

        $this->laravelRefreshDatabase();
    }
}
