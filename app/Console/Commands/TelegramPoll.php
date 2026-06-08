<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class TelegramPoll extends Command
{
    protected $signature   = 'telegram:poll';
    protected $description = 'Poll Telegram and auto-reply with chat_id';

    public function handle(): void
    {
        $token  = config('services.telegram.bot_token');
        $apiUrl = "https://api.telegram.org/bot{$token}";
        $offset = 0;

        $this->info('Telegram bot polling... (Ctrl+C to stop)');

        while (true) {
            $response = Http::timeout(35)->get("{$apiUrl}/getUpdates", [
                'offset'          => $offset,
                'timeout'         => 30,
                'allowed_updates' => ['message'],
            ]);

            if (!$response->successful()) {
                sleep(3);
                continue;
            }

            foreach ($response->json('result', []) as $update) {
                $offset  = $update['update_id'] + 1;
                $chatId  = $update['message']['chat']['id'] ?? null;
                $name    = $update['message']['from']['first_name'] ?? 'مستخدم';

                if (!$chatId) continue;

                $text = "مرحباً {$name}! 👋\n\n"
                      . "رقم Chat ID الخاص بك:\n\n"
                      . "<code>{$chatId}</code>\n\n"
                      . "انسخ هذا الرقم وضعه في حقل\n(رقم تيليغرام) عند التسجيل في Edu Bridge 🎓";

                Http::post("{$apiUrl}/sendMessage", [
                    'chat_id'    => $chatId,
                    'text'       => $text,
                    'parse_mode' => 'HTML',
                ]);

                $this->info("Replied to {$name} (chat_id: {$chatId})");
            }
        }
    }
}
