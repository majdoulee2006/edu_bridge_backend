<?php

namespace App\Http\Controllers;

use App\Models\Message;
use App\Models\User;
use App\Events\MessageSent;
use Illuminate\Http\Request;

class ChatController extends Controller
{
    public function getContacts(Request $request)
    {
        $user = $request->user();
        $myRoleId = $user->role_id;

        $roleAdmin   = 1; // الإدارة
        $roleTeacher = 2; // المدرب
        $roleStudent = 3; // الطالب
        $roleParent  = 4; // الأهل
        $roleHead    = 5; // رئيس القسم
        $roleAffairs = 6; // موظف الشؤون

        $allowedRoles = [];

        switch ($myRoleId) {
            case $roleTeacher:
                $allowedRoles = [$roleStudent, $roleTeacher, $roleHead];
                break;
            case $roleStudent:
                $allowedRoles = [$roleTeacher, $roleHead];
                break;
            case $roleParent:
                $allowedRoles = [$roleAdmin, $roleHead];
                break;
            case $roleHead:
                $allowedRoles = [$roleStudent, $roleTeacher, $roleParent, $roleAdmin];
                break;
            case $roleAdmin:
                $allowedRoles = [$roleHead, $roleAffairs, $roleTeacher];
                break;
            case $roleAffairs:
                $allowedRoles = [$roleAdmin];
                break;
        }

        if (empty($allowedRoles)) {
            return response()->json(['status' => 'success', 'data' => []]);
        }

        // Get department information for filtering
        $userDeptName = $user->department;
        $deptId = null;

        if ($myRoleId == 5) { // HOD
            $myHead = \DB::table('heads')->where('user_id', $user->user_id)->first();
            if ($myHead) {
                $deptId = $myHead->department_id;
                $userDeptName = \DB::table('departments')->where('department_id', $deptId)->value('name');
            }
        } else {
            $dept = $userDeptName ? \DB::table('departments')->where('name', $userDeptName)->first() : null;
            $deptId = $dept ? $dept->department_id : null;
        }

        $contactsQuery = \App\Models\User::whereIn('role_id', $allowedRoles)
            ->where('user_id', '!=', $user->user_id);

        if ($myRoleId == 4) { // Parent
            // Get child departments
            $childDepartments = collect();
            $parentRecord = \DB::table('parents')->where('user_id', $user->user_id)->first();
            if ($parentRecord) {
                $studentIds = \DB::table('parent_students')->where('parent_id', $parentRecord->parent_id)->pluck('student_id');
                $childUserIds = \DB::table('students')->whereIn('student_id', $studentIds)->pluck('user_id');
                $childDepts = \App\Models\User::whereIn('user_id', $childUserIds)->pluck('department')->filter()->unique();
                $childDepartments = $childDepartments->merge($childDepts);
            }
            if (!empty($user->children_ids)) {
                $childDepts = \App\Models\User::whereIn('user_id', $user->children_ids)->pluck('department')->filter()->unique();
                $childDepartments = $childDepartments->merge($childDepts);
            }
            $childDepartments = $childDepartments->unique()->values();

            $deptIds = \DB::table('departments')
                ->whereIn('name', $childDepartments)
                ->pluck('department_id');

            // Parent should only see HODs of their children's departments OR Manager/Admin
            $contactsQuery->where(function ($q) use ($deptIds) {
                $q->where(function ($subQ) use ($deptIds) {
                    $subQ->where('role_id', 5) // HOD role
                      ->whereIn('user_id', function ($sub) use ($deptIds) {
                          $sub->select('user_id')
                              ->from('heads')
                              ->whereIn('department_id', $deptIds);
                      });
                })->orWhere('role_id', 1); // Manager/Admin role
            });
        } elseif ($userDeptName) {
            // Filter by department for students, teachers, and department heads
            $contactsQuery->where(function ($query) use ($userDeptName, $deptId) {
                // Students and Teachers matching the department name string
                $query->where(function ($q) use ($userDeptName) {
                    $q->whereIn('role_id', [2, 3])
                      ->where('department', $userDeptName);
                });
                
                // Department Heads matching the department_id in heads table
                if ($deptId) {
                    $query->orWhere(function ($q) use ($deptId) {
                        $q->where('role_id', 5)
                          ->whereIn('user_id', function ($sub) use ($deptId) {
                              $sub->select('user_id')
                                  ->from('heads')
                                  ->where('department_id', $deptId);
                          });
                    });
                }
                
                // Allow other roles without department restriction
                $query->orWhereNotIn('role_id', [2, 3, 5]);
            });
        }

        $contactsList = $contactsQuery->get();
        $contactIds = $contactsList->pluck('user_id')->toArray();

        // Batch-fetch latest messages (avoids N+1: was 1 query per contact)
        $latestMessages = collect();
        $unreadCounts = collect();

        if (!empty($contactIds)) {
            $latestMessageIds = \DB::table('messages')
                ->select(\DB::raw('MAX(id) as max_id'))
                ->where(function ($q) use ($user, $contactIds) {
                    $q->where('sender_id', $user->user_id)->whereIn('receiver_id', $contactIds);
                })->orWhere(function ($q) use ($user, $contactIds) {
                    $q->whereIn('sender_id', $contactIds)->where('receiver_id', $user->user_id);
                })
                ->groupBy(\DB::raw('LEAST(sender_id, receiver_id), GREATEST(sender_id, receiver_id)'))
                ->pluck('max_id');

            $latestMessages = \App\Models\Message::whereIn('id', $latestMessageIds)
                ->get()
                ->keyBy(function ($msg) use ($user) {
                    return $msg->sender_id == $user->user_id ? $msg->receiver_id : $msg->sender_id;
                });

            $unreadCounts = \App\Models\Message::whereIn('sender_id', $contactIds)
                ->where('receiver_id', $user->user_id)
                ->where('is_read', 0)
                ->select('sender_id', \DB::raw('COUNT(*) as unread_count'))
                ->groupBy('sender_id')
                ->get()
                ->keyBy('sender_id');
        }

        $contacts = $contactsList->map(function ($contact) use ($user, $latestMessages, $unreadCounts) {
                $roleName = 'User';
                switch ($contact->role_id) {
                    case 1: $roleName = 'Administration'; break;
                    case 2: $roleName = 'Teacher'; break;
                    case 3: $roleName = 'Student'; break;
                    case 4: $roleName = 'Parent'; break;
                    case 5: $roleName = 'Head of Department'; break;
                    case 6: $roleName = 'Affairs Officer'; break;
                }

                $latestMessage = $latestMessages->get($contact->user_id);
                $unreadCount = $unreadCounts->has($contact->user_id)
                    ? $unreadCounts->get($contact->user_id)->unread_count
                    : 0;

                // Format time for the UI
                $timeStr = null;
                if ($latestMessage) {
                    $createdAt = $latestMessage->created_at;
                    if ($createdAt->isToday()) {
                        $timeStr = $createdAt->format('h:i A');
                        $timeStr = str_replace(['AM', 'PM'], ['ص', 'م'], $timeStr);
                    } elseif ($createdAt->isYesterday()) {
                        $timeStr = 'أمس';
                    } else {
                        $timeStr = $createdAt->format('Y-m-d');
                    }
                }

                return [
                    'id' => (string) $contact->user_id,
                    'name' => $contact->full_name ?? $contact->name ?? 'Unknown',
                    'role' => $roleName,
                    'unread' => $unreadCount,
                    'image' => $contact->avatar ? asset('storage/' . $contact->avatar) : null,
                    'last_message' => $latestMessage ? $latestMessage->message : null,
                    'last_message_time' => $latestMessage ? $latestMessage->created_at->toIso8601String() : null,
                    'time' => $timeStr,
                ];
            });

        // Sort by latest message time, keeping contacts with no messages at the bottom
        $contacts = $contacts->sortByDesc('last_message_time')->values();

        return response()->json(['status' => 'success', 'data' => $contacts]);
    }

 public function sendMessage(Request $request)
{
    $request->validate([
        'receiver_id' => 'required',
        'message'     => 'nullable|string',
        'attachment'  => 'nullable|file|max:51200',
        'reply_to_message_id' => 'nullable|exists:messages,id',
    ]);

    // sender is always the authenticated user — never trust the client's sender_id
    $sender     = $request->user();
    $senderId   = $sender->user_id;
    $receiverId = (int) $request->input('receiver_id');

    $receiver = User::find($receiverId);

    if (!$receiver) {
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

    // إرسال إشعار FCM للمستلم
    $msgBody = $message->message ?: 'أرسل لك ملفاً مرفقاً';
    \App\Services\FcmService::sendToUser($receiverId, $sender->full_name ?? 'رسالة جديدة', $msgBody, [
        'type' => 'message',
        'sender_id' => (string) $senderId,
    ]);

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
        })->orderBy('created_at', 'desc')->get(); // ترتيب من الأحدث للأقدم

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
        $roleAffairs = 6; // موظف الشؤون

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
                // رئيس القسم يحكي مع: أهل، مدربين، طلاب، إدارة
                return in_array($receiverRoleId, [$roleParent, $roleTeacher, $roleStudent, $roleAdmin]);

            case $roleAdmin:
                // الإدارة تحكي مع: رئيس قسم، شؤون، مدربين
                return in_array($receiverRoleId, [$roleHead, $roleAffairs, $roleTeacher]);

            case $roleAffairs:
                // الشؤون ترد على: الإدارة فقط
                return in_array($receiverRoleId, [$roleAdmin]);

            default:
                return false;
        }
    }
    // 👁️ دالة تحديد الرسائل كمقروءة
    public function markAsRead(Request $request, $otherUserId)
    {
        $myId = $request->user()->user_id;

        // تحديث كل الرسائل اللي بعتها الشخص التاني إلي، وكانت غير مقروءة (0) لتصير مقروءة (1)
        $updatedCount = \App\Models\Message::where('sender_id', $otherUserId)
            ->where('receiver_id', $myId)
            ->where('is_read', 0)
            ->update(['is_read' => 1]);

        broadcast(new \App\Events\MessagesMarkedAsRead($myId, $otherUserId))->toOthers();

        return response()->json([
            'status' => 'success',
            'message' => 'تم تحديث حالة الرسائل إلى مقروءة'
        ]);
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
    ->orderBy('created_at', 'desc')
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
        ->orderBy('created_at', 'desc')
        ->get();

    return response()->json([
        'status' => 'success',
        'data' => $messages
    ]);
}
}