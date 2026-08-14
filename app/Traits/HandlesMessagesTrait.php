<?php

namespace App\Traits;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Models\Message;
use App\Models\User;

trait HandlesMessagesTrait
{
    /**
     * Get conversation with a user, excluding expired disappearing messages.
     */
    public function getConversation($userId)
    {
        $currentUserId = Auth::id();

        // 1. Clean up / delete expired messages
        Message::whereNotNull('expires_at')
            ->where('expires_at', '<=', now())
            ->delete();

        // 2. Retrieve non-deleted, non-expired messages
        $messages = Message::with(['sender', 'receiver'])
            ->where('deleted_for_everyone', false)
            ->where(function ($q) {
                $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
            })
            ->where(function ($q) use ($currentUserId, $userId) {
                $q->where(function ($sub) use ($currentUserId, $userId) {
                    $sub->where('sender_id', $currentUserId)->where('receiver_id', $userId)->where('deleted_for_sender', false);
                })->orWhere(function ($sub) use ($currentUserId, $userId) {
                    $sub->where('sender_id', $userId)->where('receiver_id', $currentUserId)->where('deleted_for_receiver', false);
                });
            })
            ->orderBy('created_at', 'asc')
            ->get();

        // Mark incoming messages as read
        Message::where('sender_id', $userId)
            ->where('receiver_id', $currentUserId)
            ->where('is_read', false)
            ->update(['is_read' => true]);

        return response()->json($messages);
    }

    /**
     * Send a new message, with support for self-disappearing timer and attachments.
     */
    public function sendMessage(Request $request)
    {
        $request->validate([
            'receiver_id'      => 'required|exists:users,user_id',
            'message'          => 'required|string|max:2000',
            'attachment'       => 'nullable|file|max:51200', // max 50MB
            'disappears_after' => 'nullable|integer|min:0', // in seconds
        ]);

        $attachmentPath = null;
        if ($request->hasFile('attachment')) {
            $file = $request->file('attachment');
            $folder = 'chat_attachments';
            
            if ($request->message === '[Voice Note]' || strpos($file->getMimeType(), 'audio') !== false) {
                $folder = 'chat_voice_notes';
            }
            
            $path = $file->store($folder, 'public');
            $attachmentPath = asset('storage/' . $path);
        }

        $expiresAt = null;
        if ($request->filled('disappears_after') && (int)$request->disappears_after > 0) {
            $expiresAt = now()->addSeconds((int)$request->disappears_after);
        }

        $message = Message::create([
            'sender_id'        => Auth::id(),
            'receiver_id'      => $request->receiver_id,
            'message'          => $request->message,
            'attachment'       => $attachmentPath,
            'is_read'          => false,
            'disappears_after' => $request->disappears_after ? (int)$request->disappears_after : null,
            'expires_at'       => $expiresAt,
        ]);

        // Broadcast real-time event via Pusher / Echo
        try {
            broadcast(new \App\Events\MessageSent($message))->toOthers();
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('MessageSent Broadcast Error: ' . $e->getMessage());
        }

        // Add Notification for recipient
        DB::table('notifications')->insert([
            'user_id'    => $request->receiver_id,
            'title'      => 'رسالة جديدة',
            'message'    => 'لقد تلقيت رسالة جديدة من ' . Auth::user()->full_name,
            'type'       => 'message',
            'is_read'    => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        
        \App\Services\FcmService::sendToUser(
            $request->receiver_id,
            'رسالة جديدة',
            'لقد تلقيت رسالة جديدة من ' . Auth::user()->full_name,
            ['type' => 'message']
        );

        if ($request->expectsJson()) {
            return response()->json(['status' => 'success', 'success' => true, 'data' => $message, 'message' => $message]);
        }

        return redirect()->back()->with('success', 'تم إرسال الرسالة بنجاح!');
    }

    /**
     * Download attachment file directly to user device.
     */
    public function downloadAttachment($id)
    {
        $message = Message::findOrFail($id);
        $currentUserId = Auth::id();

        if ($message->sender_id != $currentUserId && $message->receiver_id != $currentUserId) {
            abort(403, 'غير مصرح للوصول إلى هذا الملف.');
        }

        if (!$message->attachment) {
            abort(404, 'لا يوجد ملف مرفق لهذه الرسالة.');
        }

        $path = str_replace(asset('storage/'), '', $message->attachment);
        $fullPath = storage_path('app/public/' . $path);

        if (!file_exists($fullPath)) {
            $fullPath = public_path('storage/' . $path);
        }

        if (!file_exists($fullPath)) {
            return back()->with('error', 'الملف غير موجود على السيرفر.');
        }

        $originalName = basename($fullPath);
        return response()->download($fullPath, $originalName);
    }

    /**
     * Forward a message or file to one or multiple recipients.
     */
    public function forwardMessage(Request $request)
    {
        $request->validate([
            'message_id'     => 'required|exists:messages,id',
            'receiver_ids'   => 'required|array|min:1',
            'receiver_ids.*' => 'exists:users,user_id',
        ]);

        $original = Message::findOrFail($request->message_id);
        $currentUserId = Auth::id();

        if ($original->sender_id != $currentUserId && $original->receiver_id != $currentUserId) {
            return response()->json(['status' => 'error', 'message' => 'غير مصرح بك'], 403);
        }

        $forwardedCount = 0;
        foreach ($request->receiver_ids as $receiverId) {
            if ($receiverId == $currentUserId) continue;

            Message::create([
                'sender_id'    => $currentUserId,
                'receiver_id'  => $receiverId,
                'message'      => $original->message,
                'attachment'   => $original->attachment,
                'is_read'      => false,
                'is_forwarded' => true,
            ]);

            DB::table('notifications')->insert([
                'user_id'    => $receiverId,
                'title'      => 'رسالة معاد توجيهها',
                'message'    => 'تمت إعادة توجيه رسالة إليك من ' . Auth::user()->full_name,
                'type'       => 'message',
                'is_read'    => false,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            \App\Services\FcmService::sendToUser(
                $receiverId,
                'رسالة معاد توجيهها',
                'تمت إعادة توجيه رسالة إليك من ' . Auth::user()->full_name,
                ['type' => 'message']
            );

            $forwardedCount++;
        }

        return response()->json([
            'status'  => 'success',
            'message' => "تمت إعادة توجيه الرسالة بنجاح إلى {$forwardedCount} مستخدم.",
            'count'   => $forwardedCount
        ]);
    }

    /**
     * Delete message (Dual-Mode: 'me' vs 'everyone').
     */
    public function deleteMessage(Request $request, $id)
    {
        $message = Message::findOrFail($id);
        $currentUserId = Auth::id();
        $type = $request->input('type', 'me');

        if ($message->sender_id != $currentUserId && $message->receiver_id != $currentUserId) {
            return response()->json(['status' => 'error', 'message' => 'غير مصرح'], 403);
        }

        if ($type === 'everyone') {
            if ($message->sender_id != $currentUserId) {
                return response()->json(['status' => 'error', 'message' => 'لا يمكنك حذف رسالة شخص آخر لدى الجميع'], 403);
            }
            $message->deleted_for_everyone = true;
            if ($message->attachment) {
                $path = str_replace(asset('storage/'), '', $message->attachment);
                \Illuminate\Support\Facades\Storage::disk('public')->delete($path);
            }
        } else {
            if ($message->sender_id == $currentUserId) {
                $message->deleted_for_sender = true;
            }
            if ($message->receiver_id == $currentUserId) {
                $message->deleted_for_receiver = true;
            }
        }

        $message->save();

        if ($message->deleted_for_everyone || ($message->deleted_for_sender && $message->deleted_for_receiver)) {
            $message->delete();
        }

        return response()->json(['status' => 'success', 'message' => 'تم حذف الرسالة بنجاح']);
    }
}
