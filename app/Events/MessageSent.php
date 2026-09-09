<?php

namespace App\Events;

use App\Models\Message;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MessageSent implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $message;

    public function __construct(Message $message)
    {
        $this->message = $message;
    }

    // رح نبعتها لقناة خاصة بالمستقبل (عشان كل طالب يوصله إشعاراته بس)
    public function broadcastOn(): array
    {
        return [
            new \Illuminate\Broadcasting\PrivateChannel('chat.' . $this->message->receiver_id),
            new \Illuminate\Broadcasting\PrivateChannel('chat.' . $this->message->sender_id),
        ];
    }

    // البيانات اللي رح توصل للفلاتر
    public function broadcastWith(): array
    {
        $payload = [
            'id'                  => (int) $this->message->id,
            'message'             => $this->message->message,
            'sender_id'           => (int) $this->message->sender_id,
            'receiver_id'         => (int) $this->message->receiver_id,
            'attachment'          => $this->message->attachment,
            'is_read'             => (bool) $this->message->is_read,
            'reply_to_message_id' => $this->message->reply_to_message_id,
            'created_at'          => $this->message->created_at ? $this->message->created_at->toIso8601String() : null,
        ];

        // نرسل المفاتيح مباشرة ونوفر كائن message متداخل لضمان التوافق المطلق
        return array_merge($payload, [
            'message' => $this->message->message,
            'message_data' => $payload,
        ]);
    }
}