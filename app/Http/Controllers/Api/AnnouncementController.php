<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Announcement;
use Illuminate\Http\Request;

class AnnouncementController extends Controller
{
    public function getHomeAnnouncements()
    {
        $announcements = \DB::table('announcements')
            ->join('users', 'announcements.user_id', '=', 'users.user_id')
            ->where(function($q) {
                $q->whereNull('announcements.target_role')->orWhere('announcements.target_role', 'student');
            })
            ->orderBy('announcements.created_at', 'desc')
            ->get([
                'announcements.announcement_id',
                'announcements.title',
                'announcements.content',
                'announcements.created_at',
                'users.full_name as author_name',
            ])
            ->map(fn($a) => [
                'id'          => $a->announcement_id,
                'title'       => $a->title,
                'content'     => $a->content,
                'created_at'  => $a->created_at,
                'author_name' => $a->author_name,
            ]);

        return response()->json([
            'status' => 'success',
            'data'   => $announcements,
        ]);
    }
}