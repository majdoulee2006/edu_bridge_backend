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
          ->where('user_id', auth()->id()) 
          ->orderBy('created_at', 'desc')
          ->get();

      return response()->json($notifications); 
  }
}