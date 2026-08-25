<?php

namespace App\Jobs;

use App\Services\TelegramService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendTelegramMessageJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $chatId;
    protected $text;
    protected $type;
    protected $extraData;

    /**
     * Create a new job instance.
     */
    public function __construct(int $chatId, string $text, string $type = 'message', array $extraData = [])
    {
        $this->chatId = $chatId;
        $this->text = $text;
        $this->type = $type;
        $this->extraData = $extraData;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $telegram = new TelegramService();
        
        if ($this->type === 'otp') {
            $telegram->sendOtpSync($this->chatId, $this->text, $this->extraData['name'] ?? '');
        } elseif ($this->type === 'credentials') {
            $telegram->sendCredentialsSync(
                $this->chatId, 
                $this->extraData['universityId'] ?? '', 
                $this->extraData['defaultPassword'] ?? '', 
                $this->extraData['name'] ?? '', 
                $this->extraData['nationalId'] ?? '', 
                $this->extraData['birthDate'] ?? ''
            );
        } else {
            $telegram->sendMessageSync($this->chatId, $this->text);
        }
    }
}
