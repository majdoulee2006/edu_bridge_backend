<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class FcmService
{
    private static function getAccessToken(): ?string
    {
        $credPath = storage_path('app/firebase-service-account.json');
        if (!file_exists($credPath)) {
            Log::warning('FCM: firebase-service-account.json not found');
            return null;
        }

        try {
            $creds = json_decode(file_get_contents($credPath), true);
            $projectId = $creds['project_id'] ?? null;

            $now = time();
            $header = base64_encode(json_encode(['alg' => 'RS256', 'typ' => 'JWT']));
            $payload = base64_encode(json_encode([
                'iss'   => $creds['client_email'],
                'sub'   => $creds['client_email'],
                'aud'   => 'https://oauth2.googleapis.com/token',
                'iat'   => $now,
                'exp'   => $now + 3600,
                'scope' => 'https://www.googleapis.com/auth/firebase.messaging',
            ]));

            $sig = '';
            openssl_sign("$header.$payload", $sig, $creds['private_key'], 'sha256WithRSAEncryption');
            $jwt = "$header.$payload." . base64_encode($sig);

            $res = Http::asForm()->post('https://oauth2.googleapis.com/token', [
                'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
                'assertion'  => $jwt,
            ]);

            return $res->json('access_token');
        } catch (\Exception $e) {
            Log::error('FCM auth error: ' . $e->getMessage());
            return null;
        }
    }

    public static function sendToUser(int $userId, string $title, string $body, array $data = []): bool
    {
        $user = User::find($userId);
        if (!$user || empty($user->device_token)) return false;
        return self::send($user->device_token, $title, $body, $data);
    }

    public static function send(string $token, string $title, string $body, array $data = []): bool
    {
        $credPath = storage_path('app/firebase-service-account.json');
        if (!file_exists($credPath)) return false;

        $creds = json_decode(file_get_contents($credPath), true);
        $projectId = $creds['project_id'] ?? null;
        if (!$projectId) return false;

        $accessToken = self::getAccessToken();
        if (!$accessToken) return false;

        try {
            $res = Http::withToken($accessToken)
                ->post("https://fcm.googleapis.com/v1/projects/{$projectId}/messages:send", [
                    'message' => [
                        'token' => $token,
                        'notification' => ['title' => $title, 'body' => $body],
                        'data' => array_map('strval', $data),
                        'android' => [
                            'priority' => 'high',
                            'notification' => ['sound' => 'default', 'channel_id' => 'edu_bridge'],
                        ],
                    ],
                ]);

            if (!$res->successful()) {
                Log::error('FCM send error: ' . $res->body());
            }
            return $res->successful();
        } catch (\Exception $e) {
            Log::error('FCM exception: ' . $e->getMessage());
            return false;
        }
    }
}
