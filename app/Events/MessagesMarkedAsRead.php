<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MessagesMarkedAsRead implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $readerId; // اللي قرأ الرسائل
    public $senderId; // اللي أرسل الرسائل (واللي لازم يوصله إشعار القراءة)

    public function __construct($readerId, $senderId)
    {
        $this->readerId = $readerId;
        $this->senderId = $senderId;
    }

    public function broadcastOn()
    {
        // نبعث الإشعار على قناة الشخص اللي أرسل الرسالة عشان نحدث شاشته
        return new PrivateChannel('chat.' . $this->senderId);
    }
}
