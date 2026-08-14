<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Message;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;

class MessageController extends Controller
{
    /**
     * Clean up expired messages
     */
    private function cleanExpiredMessages()
    {
        $expiredMessages = Message::whereNotNull('expires_at')
            ->where('expires_at', '<=', now())
            ->get();

        foreach ($expiredMessages as $msg) {
            if ($msg->attachment) {
                $path = str_replace(asset('storage/'), '', $msg->attachment);
                Storage::disk('public')->delete($path);
            }
            $msg->delete();
        }
    }

    // 🌟 1. جلب سجل المحادثة بين المستخدم الحالي وأي مستخدم آخر
    public function fetchChatHistory($receiver_id)
    {
        $this->cleanExpiredMessages();

        $userId = auth()->user()->user_id;

        $messages = Message::where(function ($query) use ($userId, $receiver_id) {
            $query->where('sender_id', $userId)
                  ->where('receiver_id', $receiver_id)
                  ->where('deleted_for_sender', false);
        })->orWhere(function ($query) use ($userId, $receiver_id) {
            $query->where('sender_id', $receiver_id)
                  ->where('receiver_id', $userId)
                  ->where('deleted_for_receiver', false);
        })
        ->where('deleted_for_everyone', false)
        ->where(function ($q) {
            $q->whereNull('expires_at')
              ->orWhere('expires_at', '>', now());
        })
        ->orderBy('created_at', 'asc')
        ->get();

        // تحديث حالة القراءة
        Message::where('sender_id', $receiver_id)
            ->where('receiver_id', $userId)
            ->where('is_read', false)
            ->update(['is_read' => true]);

        $formattedMessages = $messages->map(function ($msg) use ($userId) {
            return [
                'id'               => $msg->id,
                'message'          => $msg->message,
                'sender_id'        => $msg->sender_id,
                'receiver_id'      => $msg->receiver_id,
                'attachment'       => $msg->attachment,
                'is_forwarded'     => (bool) $msg->is_forwarded,
                'disappears_after' => $msg->disappears_after,
                'expires_at'       => $msg->expires_at ? $msg->expires_at->toIso8601String() : null,
                'is_read'          => (bool) $msg->is_read,
                'time'             => $msg->created_at ? $msg->created_at->format('h:i A') : now()->format('h:i A'),
                'created_at'       => $msg->created_at ? $msg->created_at->toIso8601String() : null,
            ];
        });

        return response()->json([
            'status' => 'success',
            'data'   => $formattedMessages
        ]);
    }

    // 🌟 2. إرسال رسالة جديدة
    public function sendMessage(Request $request)
    {
        $request->validate([
            'receiver_id'      => 'required|exists:users,user_id',
            'message'          => 'required_without:attachment|string|nullable',
            'attachment'       => 'nullable|file|max:51200',
            'disappears_after' => 'nullable|string',
        ]);

        $userId = auth()->user()->user_id;

        // حساب وقت انتهاء الصلاحية
        $expiresAt = null;
        $disappearsAfter = $request->input('disappears_after');

        if ($disappearsAfter) {
            $minutesMap = [
                '5m'  => 5,
                '1h'  => 60,
                '24h' => 1440,
                '7d'  => 10080,
            ];

            $minutes = $minutesMap[$disappearsAfter] ?? (is_numeric($disappearsAfter) ? (int)$disappearsAfter : null);

            if ($minutes) {
                $expiresAt = now()->addMinutes($minutes);
            }
        }

        $attachmentPath = null;
        if ($request->hasFile('attachment')) {
            $file = $request->file('attachment');
            $folder = 'chat_attachments';
            if ($request->message === '[Voice Note]' || str_contains($file->getMimeType(), 'audio')) {
                $folder = 'chat_voice_notes';
            }
            $path = $file->store($folder, 'public');
            $attachmentPath = asset('storage/' . $path);
        }

        $message = Message::create([
            'sender_id'        => $userId,
            'receiver_id'      => $request->receiver_id,
            'message'          => $request->message ?? '',
            'attachment'       => $attachmentPath,
            'disappears_after' => $disappearsAfter,
            'expires_at'       => $expiresAt,
            'is_read'          => false,
        ]);

        // إرسال إشعار FCM
        $senderName = auth()->user()->full_name ?? 'مستخدم';
        \App\Services\FcmService::sendToUser(
            $request->receiver_id,
            'رسالة جديدة من ' . $senderName,
            mb_substr($message->message ?: 'مرفق جديد', 0, 100),
            ['type' => 'message', 'sender_id' => (string)$userId]
        );

        return response()->json([
            'status'  => 'success',
            'message' => 'تم إرسال الرسالة بنجاح',
            'data'    => [
                'id'               => $message->id,
                'message'          => $message->message,
                'sender_id'        => $message->sender_id,
                'receiver_id'      => $message->receiver_id,
                'attachment'       => $message->attachment,
                'is_forwarded'     => false,
                'disappears_after' => $message->disappears_after,
                'expires_at'       => $message->expires_at ? $message->expires_at->toIso8601String() : null,
                'time'             => now()->format('h:i A'),
            ]
        ], 201);
    }

    // 🌟 3. إعادة توجيه رسالة (Forward Message)
    public function forwardMessage(Request $request)
    {
        $request->validate([
            'message_id'       => 'required|exists:messages,id',
            'receiver_id'      => 'required|exists:users,user_id',
            'disappears_after' => 'nullable|string',
        ]);

        $currentUserId = auth()->user()->user_id;
        $originalMessage = Message::findOrFail($request->message_id);

        if ((int)$originalMessage->sender_id !== (int)$currentUserId && (int)$originalMessage->receiver_id !== (int)$currentUserId) {
            return response()->json(['status' => 'error', 'message' => 'غير مصرح بإعادة توجيه هذه الرسالة'], 403);
        }

        $expiresAt = null;
        $disappearsAfter = $request->input('disappears_after');

        if ($disappearsAfter) {
            $minutesMap = [
                '5m'  => 5,
                '1h'  => 60,
                '24h' => 1440,
                '7d'  => 10080,
            ];
            $minutes = $minutesMap[$disappearsAfter] ?? (is_numeric($disappearsAfter) ? (int)$disappearsAfter : null);
            if ($minutes) {
                $expiresAt = now()->addMinutes($minutes);
            }
        }

        $forwardedMessage = Message::create([
            'sender_id'        => $currentUserId,
            'receiver_id'      => $request->receiver_id,
            'message'          => $originalMessage->message,
            'attachment'       => $originalMessage->attachment,
            'is_forwarded'     => true,
            'disappears_after' => $disappearsAfter,
            'expires_at'       => $expiresAt,
            'is_read'          => false,
        ]);

        $senderName = auth()->user()->full_name ?? 'مستخدم';
        \App\Services\FcmService::sendToUser(
            $request->receiver_id,
            'رسالة معاد توجيهها من ' . $senderName,
            mb_substr($forwardedMessage->message ?: 'مرفق', 0, 100),
            ['type' => 'message', 'sender_id' => (string)$currentUserId]
        );

        return response()->json([
            'status'  => 'success',
            'message' => 'تمت إعادة توجيه الرسالة بنجاح',
            'data'    => [
                'id'               => $forwardedMessage->id,
                'message'          => $forwardedMessage->message,
                'sender_id'        => $forwardedMessage->sender_id,
                'receiver_id'      => $forwardedMessage->receiver_id,
                'attachment'       => $forwardedMessage->attachment,
                'is_forwarded'     => true,
                'disappears_after' => $forwardedMessage->disappears_after,
                'expires_at'       => $forwardedMessage->expires_at ? $forwardedMessage->expires_at->toIso8601String() : null,
                'time'             => now()->format('h:i A'),
            ]
        ], 201);
    }
}
