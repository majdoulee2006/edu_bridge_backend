<?php

namespace Tests\Feature;

use App\Http\Middleware\SetLocaleMiddleware;
use App\Services\PhotoChangeService;
use Illuminate\Http\Request;
use Tests\Concerns\MakesAcademicData;
use Tests\TestCase;

class LocaleAndServicesTest extends TestCase
{
    use MakesAcademicData;

    private function localeFor(Request $request): string
    {
        (new SetLocaleMiddleware())->handle($request, fn () => response('ok'));

        return app()->getLocale();
    }

    public function test_saved_user_language_is_used_when_the_api_client_sends_no_header(): void
    {
        $user = $this->makeUser('student', ['locale' => 'en']);
        $request = Request::create('/api/anything');
        $request->setUserResolver(fn () => $user);

        $this->assertSame('en', $this->localeFor($request));
    }

    public function test_accept_language_header_wins_over_the_saved_language(): void
    {
        $user = $this->makeUser('student', ['locale' => 'en']);
        $request = Request::create('/api/anything', 'GET', [], [], [], ['HTTP_ACCEPT_LANGUAGE' => 'ar']);
        $request->setUserResolver(fn () => $user);

        $this->assertSame('ar', $this->localeFor($request));
    }

    public function test_unsupported_language_falls_back_to_arabic(): void
    {
        $request = Request::create('/api/anything', 'GET', [], [], [], ['HTTP_ACCEPT_LANGUAGE' => 'fr']);

        $this->assertSame('ar', $this->localeFor($request));
    }

    public function test_photo_change_service_namespace_is_correct_and_autoloadable(): void
    {
        // كان الـ namespace بلا شرطات (AppServices) فلا يمكن تحميل الكلاس إطلاقاً
        $this->assertTrue(class_exists(PhotoChangeService::class));
        $this->assertTrue(method_exists(PhotoChangeService::class, 'submitRequest'));
    }
}
