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
            ->leftJoin('departments', 'announcements.department_id', '=', 'departments.department_id')
            ->leftJoin('courses', 'announcements.course_id', '=', 'courses.course_id')
            ->where(function($q) {
                $q->whereNull('announcements.target_audience')
                  ->orWhereIn('announcements.target_audience', ['all', 'students']);
            })
            ->where(function($q) {
                $q->whereNull('announcements.target_role')
                  ->orWhere('announcements.target_role', 'student');
            })
            ->orderBy('announcements.created_at', 'desc')
            ->get([
                'announcements.announcement_id',
                'announcements.title',
                'announcements.content',
                'announcements.image',
                'announcements.link_url',
                'announcements.target_audience',
                'announcements.created_at',
                'users.full_name as author_name',
                'departments.name as department_name',
                'courses.title as course_name',
            ])
            ->map(fn($a) => [
                'id'              => $a->announcement_id,
                'title'           => $a->title,
                'content'         => $a->content,
                'body'            => $a->content,
                'target_audience' => $a->target_audience ?? 'all',
                'department_name' => $a->department_name,
                'course_name'     => $a->course_name,
                'image_url'       => $a->image ? url('storage/' . $a->image) : null,
                'link_url'        => $a->link_url ?? null,
                'created_at'      => $a->created_at,
                'author_name'     => $a->author_name,
                'time_ago'        => \Carbon\Carbon::parse($a->created_at)->diffForHumans(),
            ]);

        return response()->json([
            'status' => 'success',
            'data'   => $announcements,
        ]);
    }
}