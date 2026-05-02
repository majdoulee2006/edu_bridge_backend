<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Announcement;
use Illuminate\Http\Request;

class AnnouncementController extends Controller
{
    public function getHomeAnnouncements()
    {
        
        $announcements = Announcement::with(['user' => function($query) {
            $query->select('user_id', 'full_name', 'role_id'); 
        }])
        ->orderBy('created_at', 'desc') // الترتيب من الأحدث للأقدم
        ->get();

        return response()->json([
            'status' => 'success',
            'data' => $announcements
        ]);
    }
}