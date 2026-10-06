<?php

namespace Tests\Feature;

use App\Services\FcmService;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * B-11: الـ access token يُطلب من Google مرة واحدة ثم يُخزَّن (لا طلب جديد عند كل إشعار).
 * الاختبار يستعمل ملف اعتماد مؤقتاً ومفتاح RSA مولَّداً، ولا يلمس ملف Firebase الحقيقي.
 */
class FcmServiceTest extends TestCase
{
    private string $credPath = '';

    protected function setUp(): void
    {
        parent::setUp();

        $privatePem = $this->generatePrivateKey();
        if ($privatePem === null) {
            $this->markTestSkipped('OpenSSL cannot generate a key in this environment (missing openssl.cnf).');
        }

        $this->credPath = storage_path('framework/testing/fcm_' . bin2hex(random_bytes(4)) . '.json');
        File::ensureDirectoryExists(dirname($this->credPath));
        File::put($this->credPath, json_encode([
            'project_id'   => 'demo-project',
            'client_email' => 'svc@demo-project.iam.gserviceaccount.com',
            'private_key'  => $privatePem,
        ]));

        config(['services.fcm.credentials' => $this->credPath]);
        Cache::flush();
    }

    /** يولّد مفتاح RSA مؤقتاً. على ويندوز قد يلزم مسار openssl.cnf من مجلد PHP. */
    private function generatePrivateKey(): ?string
    {
        $candidates = [
            [],
            ['config' => dirname(PHP_BINARY) . '/extras/ssl/openssl.cnf'],
            ['config' => dirname(PHP_BINARY) . '/../apache/conf/openssl.cnf'],
        ];

        foreach ($candidates as $extra) {
            if (isset($extra['config']) && !is_file($extra['config'])) {
                continue;
            }
            $opts = array_merge(['private_key_bits' => 2048, 'private_key_type' => OPENSSL_KEYTYPE_RSA], $extra);
            $key  = @openssl_pkey_new($opts);
            if ($key && @openssl_pkey_export($key, $pem, null, $extra)) {
                return $pem;
            }
        }

        return null;
    }

    protected function tearDown(): void
    {
        if ($this->credPath !== '') {
            File::delete($this->credPath);
        }
        parent::tearDown();
    }

    public function test_access_token_is_requested_once_and_reused(): void
    {
        Http::fake([
            'oauth2.googleapis.com/*' => Http::response(['access_token' => 'tok123'], 200),
            'fcm.googleapis.com/*'    => Http::response(['name' => 'ok'], 200),
        ]);

        $this->assertTrue(FcmService::send('device-1', 'T', 'B'));
        $this->assertTrue(FcmService::send('device-2', 'T', 'B'));
        $this->assertTrue(FcmService::send('device-3', 'T', 'B'));

        $oauth = Http::recorded(fn (Request $r) => str_contains($r->url(), 'oauth2.googleapis.com'));
        $fcm   = Http::recorded(fn (Request $r) => str_contains($r->url(), 'fcm.googleapis.com'));

        $this->assertCount(1, $oauth, 'access token must be requested once');
        $this->assertCount(3, $fcm);
    }

    public function test_send_returns_false_without_credentials_file(): void
    {
        if ($this->credPath !== '') {
            File::delete($this->credPath);
        }
        Http::fake();

        $this->assertFalse(FcmService::send('device-1', 'T', 'B'));
        Http::assertNothingSent();
    }
}
