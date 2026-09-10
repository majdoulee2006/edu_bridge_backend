<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class AdminCommunicationController extends Controller
{
    use \App\Traits\NormalizesAccountCredentialsTrait;

    public function createAnnouncement()
    {
        return view('admin.announcements.create');
    }

    public function storeAnnouncement(\Illuminate\Http\Request $request)
    {
        $this->normalizeAccountCredentials($request);


        $request->validate([
            'title'           => 'required|string|max:255',
            'content'         => 'required|string|max:5000',
            'image'           => 'nullable|image|mimes:jpeg,png,jpg,webp|max:5120',
            'images.*'        => 'nullable|image|mimes:jpeg,png,jpg,webp|max:5120',
            'link_url'        => 'nullable|url|max:500',
            'target_audience' => 'nullable|in:all,students,teachers,department',
            'department_id'   => 'nullable|exists:departments,department_id',
        ]);

        $imagesList = [];
        if ($request->hasFile('images')) {
            $files = is_array($request->file('images')) ? $request->file('images') : [$request->file('images')];
            foreach ($files as $file) {
                if ($file && $file->isValid()) {
                    $imagesList[] = $file->store('announcements', 'public');
                }
            }
        }
        if (empty($imagesList) && $request->hasFile('image')) {
            $imagesList[] = $request->file('image')->store('announcements', 'public');
        }

        $primaryImage = !empty($imagesList) ? $imagesList[0] : null;

        $announcement = \App\Models\Announcement::create([
            'user_id'         => Auth::id(),
            'title'           => $request->title,
            'content'         => $request->content,
            'image'           => $primaryImage,
            'images'          => $imagesList,
            'link_url'        => $request->input('link_url'),
            'target_audience' => $request->input('target_audience', 'all'),
            'department_id'   => $request->input('target_audience') === 'department' ? $request->input('department_id') : null,
            'type'            => 'general',
        ]);

        // FCM حسب الجمهور المستهدف
        $target  = $request->input('target_audience', 'all');
        $roleIds = match($target) { 'students'=>[3], 'teachers'=>[2], 'department'=>[2,3], default=>[2,3] };
        $query   = \App\Models\User::whereIn('role_id', $roleIds)->where('status','active');

        if ($target === 'department' && $request->filled('department_id')) {
            $deptName = \App\Models\Department::find($request->department_id)?->name;
            if ($deptName) $query->where('department', $deptName);
        }
        $userIds = $query->pluck('user_id');
        $now     = now();
        $rows    = $userIds->map(fn($uid) => [
            'user_id'=>$uid, 'sender_id'=>Auth::id(),
            'title'=>'إعلان جديد من الإدارة', 'message'=>$request->title,
            'type'=>'announcement', 'category'=>'administrative',
            'related_id'=>$announcement->id ?? $announcement->announcement_id,
            'is_read'=>0, 'created_at'=>$now, 'updated_at'=>$now,
        ])->all();
        if (!empty($rows)) {
            DB::table('notifications')->insert($rows);
            foreach ($userIds as $uid) {
                \App\Services\FcmService::sendToUser($uid, 'إعلان جديد من الإدارة', $request->title, ['type'=>'announcement']);
            }
        }

        return redirect()->route('admin.dashboard')->with('success', 'تم نشر الإعلان بنجاح!');
    }

    public function editAnnouncement($id)
    {
        $announcement = \App\Models\Announcement::where('announcement_id', $id)
            ->where('user_id', Auth::id())
            ->firstOrFail();
        return view('admin.announcements.edit', compact('announcement'));
    }

    public function updateAnnouncement(\Illuminate\Http\Request $request, $id)
    {
        $announcement = \App\Models\Announcement::where('announcement_id', $id)
            ->where('user_id', Auth::id())
            ->firstOrFail();

        $request->validate([
            'title'   => 'required|string|max:255',
            'content' => 'required|string|max:5000',
            'image'   => 'nullable|image|mimes:jpeg,png,jpg,webp|max:5120',
        ]);

        $updates = [
            'title'      => $request->title,
            'content'    => $request->content,
            'updated_at' => now(),
        ];

        if ($request->hasFile('image')) {
            if ($announcement->image) {
                \Illuminate\Support\Facades\Storage::disk('public')->delete($announcement->image);
            }
            $updates['image'] = $request->file('image')->store('announcements', 'public');
        }

        $announcement->update($updates);

        return redirect()->route('admin.dashboard')->with('success', 'تم تحديث الإعلان بنجاح!');
    }

    public function deleteAnnouncement($id)
    {
        $announcement = \App\Models\Announcement::where('announcement_id', $id)
            ->where('user_id', Auth::id())
            ->firstOrFail();

        if ($announcement->image) {
            \Illuminate\Support\Facades\Storage::disk('public')->delete($announcement->image);
        }
        $announcement->delete();

        return redirect()->route('admin.dashboard')->with('success', 'تم حذف الإعلان.');
    }

    public function notifications(Request $request)
    {
        $adminUserIds = DB::table('users')->whereIn('role_id', [1, 4])->pluck('user_id')->toArray();
        $allAdminIds = array_unique(array_merge([Auth::id()], $adminUserIds));

        $query = DB::table('notifications')
            ->whereIn('user_id', $allAdminIds)
            ->orderByDesc('created_at');

        if ($request->has('filter')) {
            if ($request->filter == 'unread') {
                $query->where('is_read', false);
            } elseif ($request->filter == 'read') {
                $query->where('is_read', true);
            }
        }

        $notifications = $query->paginate(15);
        $unreadCount = DB::table('notifications')->whereIn('user_id', $allAdminIds)->where('is_read', false)->count();

        return view('admin.notifications', compact('notifications', 'unreadCount'));
    }

    public function markNotificationRead($id)
    {
        DB::table('notifications')
            ->where('id', $id)
            ->where('user_id', Auth::id())
            ->update(['is_read' => true]);

        return response()->json(['success' => true]);
    }

    public function markAllNotificationsRead()
    {
        DB::table('notifications')
            ->where('user_id', Auth::id())
            ->update(['is_read' => true]);

        return redirect()->back()->with('success', 'تم تحديد جميع الإشعارات كمقروءة.');
    }

    public function sendNotification(Request $request)
    {
        $request->validate([
            'subject'            => 'required|string|max:255',
            'message'            => 'required|string|max:2000',
            'recipient_type'     => 'required|in:all,departments,heads,hod',
            'target_departments' => 'nullable|array',
        ]);

        $title = $request->subject;
        $message = $request->message;
        $type = $request->recipient_type;
        $senderId = Auth::id();

        // Prevent duplicate notification sending within 5 seconds (Server-side Anti-Spam protection)
        $cacheKey = 'admin_notif_sent_' . $senderId . '_' . md5($title . '_' . $message . '_' . $type);
        if (\Illuminate\Support\Facades\Cache::has($cacheKey)) {
            return redirect()->back()->with('success', 'تم إرسال الإشعار بنجاح!');
        }
        \Illuminate\Support\Facades\Cache::put($cacheKey, true, 5);

        $query = DB::table('users')->where('status', 'active');

        if ($type === 'heads' || $type === 'hod') {
            // رؤساء الأقسام بس (role_id = 5)
            $query->where('role_id', 5);
        } elseif ($type === 'departments' && !empty($request->target_departments)) {
            // قسم معين / أقسام محددة
            $deptNames = DB::table('departments')
                ->whereIn('department_id', $request->target_departments)
                ->pluck('name')
                ->toArray();

            $query->where(function($q) use ($request, $deptNames) {
                foreach ($deptNames as $dName) {
                    $q->orWhere('department', 'LIKE', '%' . $dName . '%');
                }
                if (empty($deptNames)) {
                    $q->whereIn('department_id', $request->target_departments);
                }
            });
        } else {
            // كافة المستخدمين (جميع الأدوار ما عدا الأدمن المسترسل)
            $query->where('user_id', '!=', $senderId);
        }

        $users = $query->get(['user_id', 'device_token']);

        $now = now();
        $insertedCount = 0;

        foreach ($users as $u) {
            DB::table('notifications')->insert([
                'user_id'    => $u->user_id,
                'sender_id'  => $senderId,
                'title'      => $title,
                'message'    => $message,
                'type'       => 'administrative',
                'category'   => 'administrative',
                'is_read'    => false,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            if (!empty($u->device_token)) {
                \App\Services\FcmService::send($u->device_token, $title, $message, [
                    'type'     => 'administrative',
                    'category' => 'administrative',
                ]);
            }
            $insertedCount++;
        }

        return redirect()->back()->with('success', "تم إرسال الإشعار الإداري بنجاح إلى {$insertedCount} مستخدم!");
    }

    public function storeCalendarEvent(Request $request)
    {
        $this->normalizeAccountCredentials($request);


        $request->validate([
            'event_date' => 'required|date',
            'title'      => 'required|string|max:255',
            'event_time' => 'nullable',
            'location'   => 'nullable|string|max:255',
        ]);

        \App\Models\CalendarEvent::create([
            'user_id'    => Auth::id(),
            'event_date' => $request->event_date,
            'title'      => $request->title,
            'event_time' => $request->event_time,
            'location'   => $request->location,
        ]);

        return back()->with('success', 'تم إضافة الحدث بنجاح إلى التقويم.');
    }
}
