<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Announcement;
use Illuminate\Http\Request;

class AnnouncementController extends Controller
{
    public function getHomeAnnouncements(Request $request)
    {
        $user = auth('sanctum')->user();
        $userId = $user?->user_id;
        $userRoleId = $user?->role_id;
        $userDeptId = null;

        if ($user && !in_array($userRoleId, [1, 6])) { // Not Admin and Not Affairs
            $userDeptId = \DB::table('departments')->where('name', 'LIKE', '%' . ($user->department ?? '') . '%')->value('department_id');
        }

        $query = \DB::table('announcements')
            ->join('users', 'announcements.user_id', '=', 'users.user_id')
            ->leftJoin('departments', 'announcements.department_id', '=', 'departments.department_id')
            ->leftJoin('courses', 'announcements.course_id', '=', 'courses.course_id');

        if ($user && in_array($userRoleId, [1, 6])) {
            // Admin or Affairs sees all announcements
        } else {
            $query->where(function($q) use ($userId, $userDeptId) {
                if ($userId) {
                    $q->where('announcements.user_id', $userId);
                }
                $q->orWhereNull('announcements.department_id')
                  ->orWhere('announcements.target_audience', 'all');
                if ($userDeptId) {
                    $q->orWhere('announcements.department_id', $userDeptId);
                }
            });
        }

        $announcements = $query->orderBy('announcements.created_at', 'desc')
            ->get([
                'announcements.announcement_id',
                'announcements.title',
                'announcements.content',
                'announcements.image',
                'announcements.images',
                'announcements.link_url',
                'announcements.target_audience',
                'announcements.created_at',
                'users.full_name as author_name',
                'departments.name as department_name',
                'courses.title as course_name',
            ])
            ->map(function($a) {
                $images = [];
                $rawImages = json_decode($a->images ?? '[]', true);
                if (!empty($rawImages) && is_array($rawImages)) {
                    foreach ($rawImages as $img) {
                        if ($img) $images[] = str_starts_with($img, 'http') ? $img : url('storage/' . $img);
                    }
                } elseif ($a->image) {
                    $images[] = str_starts_with($a->image, 'http') ? $a->image : url('storage/' . $a->image);
                }

                return [
                    'id'              => $a->announcement_id,
                    'title'           => $a->title,
                    'content'         => $a->content,
                    'body'            => $a->content,
                    'target_audience' => $a->target_audience ?? 'all',
                    'department_name' => $a->department_name,
                    'course_name'     => $a->course_name,
                    'image_url'       => !empty($images) ? $images[0] : null,
                    'image_urls'      => $images,
                    'link_url'        => $a->link_url ?? null,
                    'created_at'      => $a->created_at,
                    'author_name'     => $a->author_name,
                    'time_ago'        => \Carbon\Carbon::parse($a->created_at)->diffForHumans(),
                ];
            });

        return response()->json([
            'status' => 'success',
            'data'   => $announcements,
        ]);
    }
}