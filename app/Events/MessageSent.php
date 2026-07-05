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
        return [
            'id' => $this->message->id,
            'message' => $this->message->message,
            'sender_id' => $this->message->sender_id,
            'receiver_id' => $this->message->receiver_id,
            'attachment' => $this->message->attachment,
            'is_read' => $this->message->is_read,
            'created_at' => $this->message->created_at,
        ];
    }
}