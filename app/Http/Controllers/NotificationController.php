<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Notification; // تأكدي من وجود موديل بهذا الاسم

class NotificationController extends Controller
{
  public function getNotifications()
{
    $notifications = DB::table('notifications')
        ->where('user_id', auth()->id()) // تأكدي أن auth()->id() هنا تعطي 4
        ->orderBy('created_at', 'desc')
        ->get();

    return response()->json($notifications); // ترسل مصفوفة مباشرة [{}, {}]
}
}