<?php

namespace Tests\Feature\Auth;

use Illuminate\Support\Facades\Route;
use Tests\Concerns\MakesAcademicData;
use Tests\TestCase;

/**
 * الويب له بوابة دخول واحدة /login لكل الأدوار؛ مسارات الدخول الخاصة بالأدوار محذوفة.
 */
class UnifiedLoginTest extends TestCase
{
    use MakesAcademicData;

    public function test_unified_login_page_is_served_and_never_cached(): void
    {
        $response = $this->get('/login')->assertOk();

        $this->assertStringContainsString('no-store', $response->headers->get('Cache-Control'));
    }

    public function test_role_specific_login_urls_no_longer_exist(): void
    {
        foreach (['student', 'teacher', 'parent', 'hod', 'affairs', 'admin'] as $role) {
            $this->get("/{$role}/login")->assertNotFound();
            $this->post("/{$role}/login", ['login' => 'x', 'password' => 'y'])->assertNotFound();
        }
        $this->get('/parents/login')->assertNotFound();
    }

    public function test_every_role_signs_in_from_the_same_page(): void
    {
        // محدد المحاولات (5/دقيقة) يعمل في الإنتاج؛ هنا نختبر 6 حسابات متتالية
        $this->withoutMiddleware(\Illuminate\Routing\Middleware\ThrottleRequests::class);

        $users = [
            'admin'   => $this->makeUser('admin'),
            'affairs' => $this->makeUser('affairs'),
            'teacher' => $this->makeTeacher()['user'],
            'student' => $this->makeStudent()['user'],
            'parent'  => $this->makeParent()['user'],
            'head'    => $this->makeHead($this->makeDepartment())['user'],
        ];

        foreach ($users as $role => $user) {
            $this->post('/login', ['login' => $user->username, 'password' => 'Password123!'])
                ->assertRedirect();
            $this->assertAuthenticatedAs($user, 'web');
            auth()->logout();
        }
    }

    public function test_wrong_password_returns_to_login_with_error(): void
    {
        $user = $this->makeUser('affairs');

        $this->from('/login')->post('/login', ['login' => $user->username, 'password' => 'nope'])
            ->assertRedirect('/login')
            ->assertSessionHasErrors('login');
        $this->assertGuest();
    }

    public function test_expired_page_419_redirects_to_login_with_a_friendly_message(): void
    {
        Route::middleware('web')->get('/_test-419', fn () => abort(419));

        $this->get('/_test-419')
            ->assertRedirect(route('login'))
            ->assertSessionHas('error');
    }
}
