<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Notification; // تأكدي من وجود موديل بهذا الاسم

class NotificationController extends Controller
{
    public function getNotifications($id)
    {
        // جلب الإشعارات الخاصة بالمستخدم (الأب)
        $notifications = Notification::where('user_id', $id)
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json($notifications);
    }
}