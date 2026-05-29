<?php

namespace App\Events;

use App\Models\Message;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MessageSent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $message;

    public function __construct(Message $message)
    {
        $this->message = $message;
    }

    // هون بنحدد وين تروح الرسالة
    public function broadcastOn(): Channel
    {
        // رح نبعتها لقناة خاصة بالمستقبل (عشان كل طالب يوصله إشعاراته بس)
        return new Channel('chat.' . $this->message->receiver_id);
    }

    // البيانات اللي رح توصل للفلاتر
    public function broadcastWith(): array
    {
        return [
            'id' => $this->message->id,
            'message' => $this->message->message,
            'sender_id' => $this->message->sender_id,
            'created_at' => $this->message->created_at,
        ];
    }
}