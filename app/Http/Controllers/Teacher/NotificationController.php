<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Models\Notification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class NotificationController extends Controller
{
    // عرض صفحة الإشعارات
    public function index()
    {
        $user_id = Auth::user()->user_id;
        
        $notifications = Notification::where('user_id', $user_id)
            ->orderBy('created_at', 'desc')
            ->get();

        return view('teacher.notifications', compact('notifications'));
    }

    // تحديد إشعار كمقروء
    public function markAsRead($id)
    {
        $notification = Notification::findOrFail($id);
        $notification->update(['is_read' => true]);

        return response()->json(['success' => true]);
    }

    // تحديد كل الإشعارات كمقروءة
    public function markAllAsRead()
    {
        $user_id = Auth::user()->user_id;
        
        Notification::where('user_id', $user_id)
            ->where('is_read', false)
            ->update(['is_read' => true]);

        return response()->json(['success' => true]);
    }
}