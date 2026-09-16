<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use App\Services\TelegramBotHandler;

class TelegramPoll extends Command
{
    protected $signature   = 'telegram:poll';
    protected $description = 'Poll Telegram and run the full bot (student services) — بديل عن الـwebhook لما ما فينا نضمن وصول تيليغرام لرابط ngrok العام';

    public function handle(TelegramBotHandler $telegramHandler): void
    {
        $token  = config('services.telegram.bot_token');
        $apiUrl = "https://api.telegram.org/bot{$token}";
        $offset = 0;

        $this->info('Telegram bot polling (full handler)... (Ctrl+C to stop)');

        while (true) {
            try {
                $response = Http::timeout(35)->get("{$apiUrl}/getUpdates", [
                    'offset'          => $offset,
                    'timeout'         => 30,
                    'allowed_updates' => ['message', 'callback_query'],
                ]);
            } catch (\Exception $e) {
                // 🐛 انقطاع شبكة لحظي (DNS/Timeout/اتصال) كان عم يرمي استثناء
                // غير ملتقط فيوقف السكربت بالكامل — بينما الهدف منه إنه يضل
                // شغال للأبد. هلق أي انقطاع مؤقت بس بيسجل تحذير ويعيد
                // المحاولة، بدل ما يطيح البرنامج كامل.
                $this->warn('Connection error, retrying in 3s: ' . $e->getMessage());
                sleep(3);
                continue;
            }

            if (!$response->successful()) {
                sleep(3);
                continue;
            }

            foreach ($response->json('result', []) as $update) {
                $offset = $update['update_id'] + 1;

                try {
                    // 🔁 نفس بالضبط منطق TelegramWebhookController@handle، بس
                    // عن طريق سحب التحديثات (getUpdates) بدل استقبالها عبر
                    // رابط عام (webhook) — نتفادى تماماً مشاكل ngrok والنفق
                    // العام يلي كانت بتمنع تيليغرام من التوصيل.
                    $telegramHandler->handleUpdate($update);
                    $chatId = $update['message']['chat']['id'] ?? ($update['callback_query']['message']['chat']['id'] ?? null);
                    $this->info("Handled update {$update['update_id']}" . ($chatId ? " (chat_id: {$chatId})" : ''));
                } catch (\Exception $e) {
                    $this->error("Error handling update {$update['update_id']}: " . $e->getMessage());
                }
            }
        }
    }
}
