<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class AffairsController extends Controller
{
    // ── الأرقام الجامعية ──────────────────────────────────────────
    public function listUniversityIds(Request $request)
    {
        $ids = DB::table('university_ids')->orderByDesc('created_at')->get()->map(function ($item) {
            $item->photo_url = $item->photo ? url('storage/' . $item->photo) : null;
            return $item;
        });
        return response()->json(['success' => true, 'data' => $ids]);
    }

    public function addUniversityId(Request $request)
    {
        $v = Validator::make($request->all(), [
            'university_id'    => 'required|string|unique:university_ids,university_id',
            'full_name'        => 'nullable|string|max:255',
            'first_name'       => 'nullable|string|max:255',
            'last_name'        => 'nullable|string|max:255',
            'date_of_birth'    => 'nullable|date',
            'phone'            => 'nullable|string|max:20',
            'telegram_chat_id' => 'nullable|string|max:50',
            'photo'            => 'nullable|image|mimes:jpeg,png,jpg,gif|max:5120',
        ]);
        if ($v->fails()) {
            return response()->json(['success' => false, 'message' => $v->errors()->first()], 422);
        }

        $fullName = $request->full_name ?? trim(($request->first_name ?? '') . ' ' . ($request->last_name ?? ''));

        $photoPath = null;
        if ($request->hasFile('photo')) {
            $photoPath = $request->file('photo')->store('student_photos', 'public');
        }

        $telegramChatId = $request->telegram_chat_id ? trim($request->telegram_chat_id) : null;

        DB::table('university_ids')->insert([
            'university_id'    => $request->university_id,
            'full_name'        => $fullName,
            'first_name'       => $request->first_name,
            'last_name'        => $request->last_name,
            'date_of_birth'    => $request->date_of_birth,
            'phone'            => $request->phone,
            'photo'            => $photoPath,
            'telegram_chat_id' => $telegramChatId,
            'role'             => 'student',
            'is_used'          => false,
            'created_by'       => $request->user()->user_id,
            'created_at'       => now(),
            'updated_at'       => now(),
        ]);

        // إرسال رسالة تليجرام
        if ($telegramChatId) {
            try {
                $telegram = new \App\Services\TelegramService();
                $telegram->sendCredentials(
                    (int) $telegramChatId,
                    $request->university_id,
                    $request->university_id,
                    $fullName,
                    '',
                    $request->date_of_birth ?? ''
                );
            } catch (\Exception $e) {
                \Log::error('Telegram sendCredentials error: ' . $e->getMessage());
            }
        }

        return response()->json(['success' => true, 'message' => 'تم إضافة الرقم الجامعي بنجاح']);
    }

    public function deleteUniversityId(Request $request, $id)
    {
        $uid = DB::table('university_ids')->find($id);
        if (!$uid) return response()->json(['success' => false, 'message' => 'غير موجود'], 404);
        if ($uid->is_used) return response()->json(['success' => false, 'message' => 'الرقم مستخدم، لا يمكن حذفه'], 422);

        DB::table('university_ids')->where('id', $id)->delete();
        return response()->json(['success' => true, 'message' => 'تم الحذف']);
    }

    // ── طلبات الحسابات المعلّقة ───────────────────────────────────
    public function pendingAccounts()
    {
        $users = User::whereIn('role_id', [3, 4]) // student + parent
            ->where('status', 'inactive')
            ->orderByDesc('created_at')
            ->get()
            ->map(fn($u) => [
                'user_id'       => $u->user_id,
                'full_name'     => $u->full_name,
                'email'         => $u->email,
                'role'          => $u->role,
                'university_id' => $u->university_id,
                'created_at'    => $u->created_at?->format('Y-m-d H:i'),
            ]);

        return response()->json(['success' => true, 'data' => $users]);
    }

    public function approveAccount(Request $request, $userId)
    {
        $user = User::find($userId);
        if (!$user) return response()->json(['success' => false, 'message' => 'المستخدم غير موجود'], 404);

        $user->update(['status' => 'active']);

        $notifTitle = 'تم تفعيل حسابك ✓';
        $notifMsg   = 'مرحباً ' . $user->full_name . '! تم مراجعة طلبك وتفعيل حسابك. يمكنك الآن تسجيل الدخول.';

        // إشعار داخل DB
        DB::table('notifications')->insert([
            'user_id'    => $user->user_id,
            'sender_id'  => $request->user()->user_id,
            'title'      => $notifTitle,
            'message'    => $notifMsg,
            'type'       => 'administrative',
            'category'   => 'administrative',
            'is_read'    => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // FCM push للمستخدم المُفعَّل (إذا سجّل fcm_token عند التسجيل)
        \App\Services\FcmService::sendToUser($user->user_id, $notifTitle, $notifMsg, [
            'type'   => 'account_approved',
            'screen' => 'login',
        ]);

        return response()->json(['success' => true, 'message' => 'تم تفعيل الحساب']);
    }

    public function rejectAccount(Request $request, $userId)
    {
        $user = User::find($userId);
        if (!$user) return response()->json(['success' => false, 'message' => 'المستخدم غير موجود'], 404);

        // إلغاء استخدام الرقم الجامعي
        if ($user->university_id) {
            DB::table('university_ids')
                ->where('university_id', $user->university_id)
                ->update(['is_used' => false]);
        }

        // حذف الطالب/الولي والحساب
        DB::table('students')->where('user_id', $userId)->delete();
        DB::table('parents')->where('user_id', $userId)->delete();
        $user->delete();

        return response()->json(['success' => true, 'message' => 'تم رفض وحذف الطلب']);
    }

    // ── إعادة تسجيل جهاز الطالب ──────────────────────────────────────
    public function resetDevice(Request $request, int $studentId)
    {
        $student = Student::find($studentId);

        if (!$student) {
            return response()->json(['success' => false, 'message' => 'الطالب غير موجود'], 404);
        }

        $student->update([
            'device_id'        => null,
            'is_device_locked' => 0,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'تم إعادة تسجيل الجهاز بنجاح، يمكن للطالب الآن تسجيل الدخول من جهاز جديد.',
        ]);
    }
}

