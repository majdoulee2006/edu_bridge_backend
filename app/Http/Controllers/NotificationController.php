<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Notification;
use Illuminate\Support\Facades\DB;

class NotificationController extends Controller
{
  public function getNotifications()
  {
      $notifications = DB::table('notifications')
          ->where('notifications.user_id', auth()->id())
          ->leftJoin('leave_requests', function ($join) {
              $join->on('leave_requests.id', '=', 'notifications.related_id')
                   ->where('notifications.type', '=', 'leave_request');
          })
          ->leftJoin('announcements', function ($join) {
              $join->on('announcements.announcement_id', '=', 'notifications.related_id')
                   ->where('notifications.type', '=', 'announcement');
          })
          ->select(
              'notifications.*',
              'leave_requests.status as leave_status',
              DB::raw("CASE WHEN notifications.type = 'announcement' AND announcements.image IS NOT NULL THEN CONCAT('" . url('storage') . "/', announcements.image) ELSE NULL END as image_url")
          )
          ->orderBy('notifications.created_at', 'desc')
          ->get();

      return response()->json($notifications);
  }

  public function markAsRead(Request $request, $id)
  {
      $updated = DB::table('notifications')
          ->where('id', $id)
          ->where('user_id', auth()->id())
          ->update(['is_read' => true]);

      if (!$updated) {
          return response()->json(['success' => false, 'message' => 'الإشعار غير موجود'], 404);
      }

      return response()->json(['success' => true, 'message' => 'تم تحديد الإشعار كمقروء']);
  }

  public function markAllAsRead(Request $request)
  {
      DB::table('notifications')
          ->where('user_id', auth()->id())
          ->where('is_read', false)
          ->update(['is_read' => true]);

      return response()->json(['success' => true, 'message' => 'تم تحديد جميع الإشعارات كمقروءة']);
  }
}