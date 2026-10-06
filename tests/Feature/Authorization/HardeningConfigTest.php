<?php

namespace Tests\Feature\Authorization;

use App\Http\Controllers\Api\AuthController;
use Illuminate\Support\Facades\DB;
use ReflectionMethod;
use Tests\Concerns\MakesAcademicData;
use Tests\TestCase;

/**
 * إعدادات الأمان: CORS مقيَّد، انتهاء صلاحية التوكن، وعدم وجود رمز OTP ثابت افتراضياً.
 */
class HardeningConfigTest extends TestCase
{
    use MakesAcademicData;

    public function test_cors_does_not_allow_arbitrary_origins(): void
    {
        $response = $this->withHeaders([
            'Origin'                         => 'https://evil.example',
            'Access-Control-Request-Method'  => 'POST',
        ])->options('/api/login');

        $this->assertNull($response->headers->get('Access-Control-Allow-Origin'));
    }

    public function test_cors_allows_local_development_origin(): void
    {
        $response = $this->withHeaders([
            'Origin'                         => 'http://localhost:5173',
            'Access-Control-Request-Method'  => 'POST',
        ])->options('/api/login');

        $this->assertSame('http://localhost:5173', $response->headers->get('Access-Control-Allow-Origin'));
    }

    public function test_expired_token_is_rejected(): void
    {
        $user  = $this->makeStudent()['user'];
        $token = $user->createToken('auth_token');
        $user->forceFill(['current_token_id' => $token->accessToken->id])->save();

        $this->withToken($token->plainTextToken)->getJson('/api/student/dashboard')->assertStatus(200);

        DB::table('personal_access_tokens')->where('id', $token->accessToken->id)
            ->update(['created_at' => now()->subDays(31)]);

        // نمسح كاش الـ guard حتى يُعاد تقييم التوكن
        $this->app['auth']->forgetGuards();

        $this->withToken($token->plainTextToken)->getJson('/api/student/dashboard')->assertUnauthorized();
    }

    public function test_otp_is_random_by_default_and_fixed_only_when_explicitly_configured(): void
    {
        $generate = new ReflectionMethod(AuthController::class, 'generateOtp');
        $generate->setAccessible(true);
        $controller = new AuthController();

        config(['app.fixed_otp' => null]);
        $codes = collect(range(1, 8))->map(fn () => $generate->invoke($controller));
        $this->assertTrue($codes->every(fn ($c) => preg_match('/^\d{6}$/', $c) === 1));
        $this->assertGreaterThan(1, $codes->unique()->count(), 'OTP must not be constant');

        config(['app.fixed_otp' => '654321']);
        $this->assertSame('654321', $generate->invoke($controller));   // testing env + explicit opt-in
    }
}
