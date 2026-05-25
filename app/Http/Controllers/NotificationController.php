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
          ->select(
              'notifications.*',
              'leave_requests.status as leave_status'
          )
          ->orderBy('notifications.created_at', 'desc')
          ->get();

      return response()->json($notifications);
  }
}