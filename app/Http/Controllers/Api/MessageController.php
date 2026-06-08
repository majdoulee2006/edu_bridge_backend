<?php



namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Message;
use App\Models\User;

class MessageController extends Controller
{
    // 🌟 1. جلب سجل المحادثة بين الطالب الحالي وأي مستخدم آخر (مدرب مثلاً)
    public function fetchChatHistory($receiver_id)
    {
        $userId = auth()->user()->user_id; // الـ ID تبع الطالب اللي مسجل دخول

        // جلب الرسائل اللي بين هدول الشخصين وترتيبها من الأقدم للأحدث
        $messages = Message::where(function ($query) use ($userId, $receiver_id) {
            $query->where('sender_id', $userId)
                  ->where('receiver_id', $receiver_id);
        })->orWhere(function ($query) use ($userId, $receiver_id) {
            $query->where('sender_id', $receiver_id)
                  ->where('receiver_id', $userId);
        })
        ->orderBy('created_at', 'asc')
        ->get();

        // تحضير البيانات لتناسب الموديل اللي عملناه بالفلاتر (MessageModel)
        $formattedMessages = $messages->map(function ($msg) use ($userId) {
            return [
                'id' => $msg->id,
                'message' => $msg->message,
                'sender_id' => $msg->sender_id, // الفلاتر رح يقارن هاد مع الـ ID تبعه ليعرف إذا isMe = true
                'time' => $msg->created_at->format('h:i A'), // شكل الوقت: 10:30 AM
            ];
        });

        return response()->json([
            'status' => 'success',
            'data' => $formattedMessages
        ]);
    }

// 🌟 2. إرسال رسالة جديدة
    public function sendMessage(Request $request)
    {
        // التحقق من البيانات الجاية من الفلاتر
        $request->validate([
            'receiver_id' => 'required|exists:users,user_id',
            'message' => 'required|string',
        ]);

        $userId = auth()->user()->user_id;

        // إنشاء الرسالة وحفظها بالداتابيز
        $message = Message::create([
            'sender_id' => $userId,
            'receiver_id' => $request->receiver_id,
            'message' => $request->message,
            'is_read' => false,
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'تم إرسال الرسالة بنجاح',
            'data' => [
                'id' => $message->id,
                'message' => $message->message,
                'sender_id' => $message->sender_id,
                // 🌟 الحل السحري لتجنب خطأ الـ 500
                'time' => now()->format('h:i A'), 
            ]
        ], 201);
    }
}

