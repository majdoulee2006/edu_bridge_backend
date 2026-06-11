<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TelegramService
{
    private string $token;
    private string $apiUrl;

    public function __construct()
    {
        $this->token  = config('services.telegram.bot_token') ?? '';
        $this->apiUrl = "https://api.telegram.org/bot{$this->token}";
    }

    public function findChatIdByUsername(string $identifier): ?int
    {
        // إذا كان رقماً (Chat ID مباشر) نرجعه فوراً
        if (is_numeric($identifier)) {
            return (int) $identifier;
        }

        $username = strtolower(ltrim($identifier, '@'));

        try {
            $response = Http::timeout(10)->get("{$this->apiUrl}/getUpdates", [
                'limit'  => 100,
                'offset' => -100,
            ]);

            if (!$response->successful()) return null;

            $updates = $response->json('result', []);

            foreach (array_reverse($updates) as $update) {
                $from   = $update['message']['from']
                    ?? $update['callback_query']['from']
                    ?? null;
                $chatId = $update['message']['chat']['id']
                    ?? $update['callback_query']['message']['chat']['id']
                    ?? null;

                if ($from && $chatId &&
                    strtolower($from['username'] ?? '') === $username) {
                    return (int) $chatId;
                }
            }
        } catch (\Exception $e) {
            Log::error('Telegram getUpdates error: ' . $e->getMessage());
        }

        return null;
    }

    public function sendOtp(int $chatId, string $otp, string $name = ''): bool
    {
        $nameText = $name ? "مرحباً <b>{$name}</b>،\n\n" : '';

        $text = "🎓 <b>Edu Bridge</b>\n\n"
              . $nameText
              . "رمز التحقق الخاص بك هو:\n\n"
              . "<b>┌─────────────┐</b>\n"
              . "<b>│   {$otp}   │</b>\n"
              . "<b>└─────────────┘</b>\n\n"
              . "⏰ صالح لمدة <b>15 دقيقة</b> فقط.\n"
              . "🔒 لا تشارك هذا الرمز مع أحد.";

        try {
            $response = Http::timeout(10)->post("{$this->apiUrl}/sendMessage", [
                'chat_id'    => $chatId,
                'text'       => $text,
                'parse_mode' => 'HTML',
            ]);

            return $response->successful() && $response->json('ok') === true;
        } catch (\Exception $e) {
            Log::error('Telegram sendMessage error: ' . $e->getMessage());
            return false;
        }
    }

    public function sendCredentials(int $chatId, string $universityId, string $defaultPassword, string $name = '', string $nationalId = '', string $birthDate = ''): bool
    {
        $nameText = $name ? "👤 <b>الاسم الكامل:</b> {$name}\n" : '';
        $nationalText = $nationalId ? "🪪 <b>الرقم الشخصي (الوطني):</b> <code>{$nationalId}</code>\n" : '';
        $birthText = $birthDate ? "📅 <b>تاريخ الميلاد:</b> <code>{$birthDate}</code>\n" : '';

        $text = "🎓 <b>مرحباً بك في جامعة Edu-Bridge!</b> 🎉\n\n"
              . "تم توليد رقمك الجامعي بنجاح من قِبل موظف الشؤون. إليك تفاصيل حسابك المعرف:\n\n"
              . $nameText
              . $nationalText
              . $birthText
              . "🔑 <b>الرقم الجامعي (اسم المستخدم):</b> <code>{$universityId}</code>\n"
              . "🔒 <b>كلمة المرور الافتراضية:</b> <code>{$defaultPassword}</code>\n\n"
              . "📲 يمكنك الآن تحميل وفتح تطبيق الجامعة لتسجيل الدخول وتفعيل حسابك باستخدام هذه البيانات.";

        try {
            $response = Http::timeout(10)->post("{$this->apiUrl}/sendMessage", [
                'chat_id'    => $chatId,
                'text'       => $text,
                'parse_mode' => 'HTML',
            ]);

            return $response->successful() && $response->json('ok') === true;
        } catch (\Exception $e) {
            Log::error('Telegram sendCredentials error: ' . $e->getMessage());
            return false;
        }
    }

    public function sendMessage(int $chatId, string $text): bool
    {
        try {
            $response = Http::timeout(10)->post("{$this->apiUrl}/sendMessage", [
                'chat_id'    => $chatId,
                'text'       => $text,
                'parse_mode' => 'HTML',
            ]);

            return $response->successful() && $response->json('ok') === true;
        } catch (\Exception $e) {
            Log::error('Telegram sendMessage error: ' . $e->getMessage());
            return false;
        }
    }
}
