<?php

namespace App\Http\Controllers;

use App\Models\Message;
use App\Models\User;
use App\Events\MessageSent;
use Illuminate\Http\Request;

class ChatController extends Controller
{
 public function sendMessage(Request $request)
{
    // 1. التحقق (بدون تغيير، شلنا القيود الصارمة عشان نمرر الـ form-data)
    $request->validate([
        'sender_id'   => 'required',
        'receiver_id' => 'required',
        'message'     => 'nullable|string',
        'attachment'  => 'nullable|file|mimes:jpeg,png,jpg,pdf,doc,docx,mp3,wav,ogg,webm,m4a|max:10240',
        'reply_to_message_id' => 'nullable|exists:messages,id',
    ]);

    // السحر هون: تحويل المدخلات لأرقام (Integer) بغض النظر عن مصدرها
    $senderId   = (int) $request->input('sender_id');
    $receiverId = (int) $request->input('receiver_id');

    // 2. استخدام القيم بعد التحويل
    $sender = User::find($senderId);
    $receiver = User::find($receiverId);

    if (!$sender || !$receiver) {
        return response()->json(['error' => 'المستخدم غير موجود'], 404);
    }

    // 3. التحقق من الصلاحيات (نفس المنطق)
    if (!$this->canChat($sender->role_id, $receiver->role_id)) {
        return response()->json(['error' => 'عذراً، غير مسموح لك بالتواصل مع هذا المستخدم.'], 403);
    }

    // 4. رفع الملف (إذا وجد)
    $attachmentPath = null;
    if ($request->hasFile('attachment')) {
        $path = $request->file('attachment')->store('chat_attachments', 'public');
        $attachmentPath = asset('storage/' . $path);
    }

    // 5. حفظ الرسالة
    $message = Message::create([
        'sender_id'   => $senderId,
        'receiver_id' => $receiverId,
        'message'     => $request->message,
        'attachment'  => $attachmentPath,
        'is_read'     => 0,
        'reply_to_message_id' => $request->input('reply_to_message_id'),
    ]);

    broadcast(new MessageSent($message))->toOthers();

    return response()->json(['status' => 'success', 'data' => $message], 201);
}
    // 📩 دالة جلب المحادثة السابقة بين شخصين
    public function getMessages(Request $request, $otherUserId)
    {
        // 1. نجلب ID الشخص اللي مسجل دخول هلق من التوكن
        $myId = $request->user()->user_id;

        // 2. نجلب كل الرسائل اللي بيني وبين الشخص التاني (سواء أنا المرسل أو هو)
        // استدعاء المودل Message بشكل مباشر
        $messages = \App\Models\Message::where(function ($q) use ($myId, $otherUserId) {
            $q->where('sender_id', $myId)->where('receiver_id', $otherUserId);
        })->orWhere(function ($q) use ($myId, $otherUserId) {
            $q->where('sender_id', $otherUserId)->where('receiver_id', $myId);
        })->orderBy('created_at', 'asc')->get(); // ترتيب من الأقدم للأحدث

        // 3. نرجع الداتا للموبايل
        return response()->json([
            'status' => 'success',
            'data' => $messages
        ]);
    }

   // 🛡️ دالة التحقق من الصلاحيات
    private function canChat($senderRoleId, $receiverRoleId)
    {
        $roleAdmin   = 1; // الإدارة
        $roleTeacher = 2; // المدرب
        $roleStudent = 3; // الطالب
        $roleParent  = 4; // الأهل
        $roleHead    = 5; // رئيس القسم
        $roleAffairs = 6; // 👈 تم التعديل إلى 6 (موظف الشؤون)

        switch ($senderRoleId) {
            case $roleTeacher:
                // المدرب يحكي مع: مدربين، طلاب، رئيس قسم
                return in_array($receiverRoleId, [$roleTeacher, $roleStudent, $roleHead]);

            case $roleStudent:
                // الطالب يحكي مع: رئيس قسم، مدرب
                return in_array($receiverRoleId, [$roleHead, $roleTeacher]);

            case $roleParent:
                // الأهل يحكوا مع: إدارة، رئيس قسم
                return in_array($receiverRoleId, [$roleAdmin, $roleHead]);

            case $roleHead:
                // رئيس القسم يحكي مع: أهل، مدربين، طلاب، إدارة، شؤون
                return in_array($receiverRoleId, [$roleParent, $roleTeacher, $roleStudent, $roleAdmin, $roleAffairs]);

            case $roleAdmin:
                // الإدارة تحكي مع: رئيس قسم، شؤون، مدربين
                return in_array($receiverRoleId, [$roleHead, $roleAffairs, $roleTeacher]);

            case $roleAffairs:
                // الشؤون ترد على: الإدارة، رئيس القسم
                return in_array($receiverRoleId, [$roleAdmin, $roleHead]);

            default:
                return false;
        }
    }
    // 👁️ دالة تحديد الرسائل كمقروءة
    public function markAsRead(Request $request, $otherUserId)
    {
        $myId = $request->user()->user_id;

        // تحديث كل الرسائل اللي بعتها الشخص التاني إلي، وكانت غير مقروءة (0) لتصير مقروءة (1)
        \App\Models\Message::where('sender_id', $otherUserId)
            ->where('receiver_id', $myId)
            ->where('is_read', 0)
            ->update(['is_read' => 1]);

        return response()->json([
            'status' => 'success',
            'message' => 'تم تحديث حالة الرسائل إلى مقروءة'
        ]);
        // بعد سطر التحديث $updatedCount = ...
      if ($updatedCount > 0) {
           broadcast(new \App\Events\MessagesMarkedAsRead($myId, $otherUserId))->toOthers();
        }
    }
public function getUnreadCount(Request $request)
{
    $myId = $request->user()->user_id; // أو id حسب ما هو عندك بقاعدة البيانات

    // بنعد الرسائل باستخدام count() وليس get()
    $count = \App\Models\Message::where('receiver_id', $myId)
        ->where('is_read', 0)
        ->count();

    return response()->json([
        'status' => 'success',
        'data' => [
            'unread_count' => $count // عشان يرجع شكلها كـ JSON object
        ]
    ]);
}
public function searchMessages(Request $request, $otherUserId)
{
    $myId = $request->user()->user_id;
    $keyword = $request->query('q');

    $messages = \App\Models\Message::where(function ($query) use ($myId, $otherUserId) {
        // حطينا شروط المرسل والمستقبل جوا "مجموعة" لحالهم
        $query->where(function ($q) use ($myId, $otherUserId) {
            $q->where('sender_id', $myId)->where('receiver_id', $otherUserId);
        })->orWhere(function ($q) use ($myId, $otherUserId) {
            $q->where('sender_id', $otherUserId)->where('receiver_id', $myId);
        });
    })
    ->where('message', 'like', '%' . $keyword . '%') // وبعدين طبقنا البحث عليهم كلهم
    ->orderBy('created_at', 'asc')
    ->get();

    return response()->json(['status' => 'success', 'data' => $messages]);
}
public function deleteMessage(Request $request, $messageId)
{
    $myId = $request->user()->user_id; // أو id حسب الداتا بيز عندك

    $message = \App\Models\Message::find($messageId);

    // 1. هل الرسالة موجودة؟
    if (!$message) {
        return response()->json(['error' => 'الرسالة غير موجودة'], 404);
    }

    // 2. هل المستخدم هو مرسل الرسالة؟ (صلاحية الحذف)
    if ($message->sender_id !== $myId) {
        return response()->json(['error' => 'غير مصرح لك بحذف هذه الرسالة'], 403);
    }

    // 3. حذف الرسالة (وإذا فيها صورة، يفضل نحذفها من السيرفر كمان)
    if ($message->attachment) {
        // تنظيف مسار الصورة لحذفها من الـ Storage
        $imagePath = str_replace(asset('storage/'), '', $message->attachment);
        \Illuminate\Support\Facades\Storage::disk('public')->delete($imagePath);
    }

    $message->delete();

    return response()->json(['status' => 'success', 'message' => 'تم حذف الرسالة بنجاح']);
}
public function editMessage(Request $request, $messageId)
{
    // 1. التحقق من النص الجديد
    $request->validate([
        'message' => 'required|string',
    ]);

    $myId = $request->user()->user_id; // أو id
    $message = \App\Models\Message::find($messageId);

    // 2. هل الرسالة موجودة؟
    if (!$message) {
        return response()->json(['error' => 'الرسالة غير موجودة'], 404);
    }

    // 3. هل هو صاحب الرسالة؟
    if ($message->sender_id !== $myId) {
        return response()->json(['error' => 'غير مصرح لك بتعديل هذه الرسالة'], 403);
    }

    // 4. التحديث
    $message->update([
        'message' => $request->message
    ]);

    return response()->json(['status' => 'success', 'message' => 'تم تعديل الرسالة بنجاح', 'data' => $message]);
}
public function sendGroupMessage(Request $request, $groupId)
{
    $request->validate(['message' => 'required|string']);
    $myId = $request->user()->user_id;

    // 1. التأكد إن المستخدم عضو بالجروب (بافتراض عملتي العلاقة بالموديل)
    $group = \App\Models\Group::find($groupId);
    if (!$group || !$group->users()->where('users.user_id', $myId)->exists()) {
         return response()->json(['error' => 'لست عضواً في هذه المجموعة'], 403);
    }

    // 2. إرسال الرسالة للجروب (بدون receiver_id لأنه للكل)
    $message = \App\Models\Message::create([
        'sender_id' => $myId,
        'receiver_id' => null, // null لأنها رسالة جروب
        'group_id' => $groupId,
        'message' => $request->message,
        'is_read' => 0,
    ]);

    return response()->json(['status' => 'success', 'data' => $message]);
}
public function createGroup(Request $request)
{
    // 1. التحقق من البيانات (اسم الجروب مطلوب، والأعضاء عبارة عن مصفوفة)
    $request->validate([
        'name' => 'required|string|max:255',
        'user_ids' => 'required|array', // مصفوفة بأرقام الأعضاء
        'user_ids.*' => 'exists:users,user_id' // تأكيد إن الأعضاء موجودين بالداتا بيز
    ]);

    $myId = $request->user()->user_id;

    // 2. إنشاء الجروب الأساسي
    $group = \App\Models\Group::create([
        'name' => $request->name,
    ]);

    // 3. تجهيز قائمة الأعضاء (نضيف نفسنا كمدير/عضو معهم)
    $members = $request->user_ids;
    if (!in_array($myId, $members)) {
        $members[] = $myId; 
    }

    // 4. ربط الأعضاء بالجروب (بافتراض إنك عملتي دالة users() في موديل Group)
    $group->users()->attach($members);

    return response()->json([
        'status' => 'success',
        'message' => 'تم إنشاء المجموعة بنجاح',
        'data' => $group
    ], 201);
}
public function getGroupMessages(Request $request, $groupId)
{
    $myId = $request->user()->user_id;

    // 1. التحقق الأمني: هل المستخدم عضو في هذا الجروب؟
    $group = \App\Models\Group::find($groupId);
    
    if (!$group || !$group->users()->where('users.user_id', $myId)->exists()) {
        return response()->json(['error' => 'عذراً، لا يمكنك عرض رسائل مجموعة لست عضواً فيها'], 403);
    }

    // 2. جلب رسائل الجروب مرتبة من الأقدم للأحدث
    $messages = \App\Models\Message::where('group_id', $groupId)
        ->orderBy('created_at', 'asc')
        ->get();

    return response()->json([
        'status' => 'success',
        'data' => $messages
    ]);
}
}