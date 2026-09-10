<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Cache;

class AdminDashboardController extends Controller
{
    public function dashboard()
    {
        // 1. Fetch latest announcements
        $announcements = DB::table('announcements')
            ->orderByDesc('created_at')
            ->limit(5)
            ->get();

        // 2. Fetch upcoming events (from calendar events)
        $events = DB::table('calendar_events')
            ->orderBy('event_date')
            ->limit(3)
            ->get();

        return view('admin.dashboard', compact('announcements', 'events'));
    }

    public function profile()
    {
        $user = Auth::user();
        $stats = Cache::remember('admin_profile_stats', 60, function () {
            return DB::table('admin_profile_stats_view')->first();
        });
        $totalUsers = $stats->total_users ?? 0;
        $totalCourses = $stats->total_courses ?? 0;

        return view('admin.profile', compact('user', 'totalUsers', 'totalCourses'));
    }

    public function settings()
    {
        $user = Auth::user();
        $themeSettings = \App\Models\SystemSetting::getThemeSettings();
        return view('admin.settings', compact('user', 'themeSettings'));
    }

    public function updateThemeSettings(Request $request)
    {
        $request->validate([
            'primary_color' => 'required|string|max:10',
            'accent_name'   => 'nullable|string|max:50',
            'theme_mode'    => 'nullable|string|in:dark,light',
        ]);

        if ($request->filled('primary_color')) {
            \App\Models\SystemSetting::setSetting('primary_color', $request->primary_color);
        }
        if ($request->filled('accent_name')) {
            \App\Models\SystemSetting::setSetting('accent_name', $request->accent_name);
        }
        if ($request->filled('theme_mode')) {
            \App\Models\SystemSetting::setSetting('theme_mode', $request->theme_mode);
        }

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'تم حفظ ثيمة الألوان وإعدادات النظام بنجاح',
                'data'    => \App\Models\SystemSetting::getThemeSettings()
            ]);
        }

        return redirect()->back()->with('success', 'تم حفظ ثيمة الألوان وإعدادات النظام بنجاح');
    }

    public function activityLogs(Request $request)
    {
        $query = \App\Models\UserActivity::orderByDesc('created_at');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('user_name', 'LIKE', "%{$search}%")
                  ->orWhere('action', 'LIKE', "%{$search}%")
                  ->orWhere('description', 'LIKE', "%{$search}%")
                  ->orWhere('ip_address', 'LIKE', "%{$search}%");
            });
        }

        if ($request->filled('role')) {
            $roleMap = [
                'إدارة'     => ['إدارة', 'admin'],
                'معلم'     => ['معلم', 'teacher'],
                'طالب'     => ['طالب', 'student'],
                'ولي أمر'   => ['ولي أمر', 'parent'],
                'رئيس قسم' => ['رئيس قسم', 'head'],
                'شؤون طلاب' => ['شؤون طلاب', 'affairs'],
            ];
            $searchRoles = $roleMap[$request->role] ?? [$request->role];
            $query->whereIn('role_name', $searchRoles);
        }

        if ($request->filled('action_type')) {
            $query->where('action', 'LIKE', "%{$request->action_type}%");
        }

        $activities = $query->paginate(20)->withQueryString();

        $distinctActions = \Illuminate\Support\Facades\Cache::remember('distinct_user_actions', 300, function () {
            return \App\Models\UserActivity::select('action')->distinct()->whereNotNull('action')->pluck('action')->toArray();
        });
        $standardActions = [
            'تسجيل دخول',
            'تسجيل خروج',
            'خمول',
            'تفعيل حساب',
            'رفض حساب',
            'فك قفل جهاز',
            'تسليم واجب',
            'تقديم طلب إجازة',
            'رصد درجات',
            'إعادة تعيين بصمة',
            'تحديث الملف الشخصي',
            'إرسال استدعاء',
            'طلب موعد',
        ];
        $allActions = array_values(array_unique(array_merge($standardActions, $distinctActions)));

        $roleActionsMap = [
            'إدارة' => [
                'تسجيل دخول', 'تسجيل خروج', 'خمول', 'إضافة حساب', 'محاولة دخول مرفوضة', 'تخصيص مربي دفعة'
            ],
            'شؤون طلاب' => [
                'تسجيل دخول', 'تسجيل خروج', 'تفعيل حساب', 'رفض حساب', 'فك قفل جهاز', 'تفعيل فصل دراسي', 'ترفيع الطلاب أكاديمياً', 'موافقة على عذر غياب'
            ],
            'رئيس قسم' => [
                'تسجيل دخول', 'تسجيل خروج', 'معالجة طلب إجازة', 'إرسال استدعاء لولي أمر', 'الرد على طلب موعد مقابلة', 'طلب تقرير أداء'
            ],
            'معلم' => [
                'تسجيل دخول', 'تسجيل خروج', 'رصد درجات امتحان', 'تصحيح واجب', 'إعادة تعيين بصمة الوجه', 'إنشاء إعلان', 'تسجيل حضور'
            ],
            'طالب' => [
                'تسجيل دخول', 'تسجيل خروج', 'تسليم واجب', 'تقديم طلب إجازة', 'تسجيل حضور (QR)', 'تحديث الملف الشخصي', 'تقديم طلب خدمة طلابية'
            ],
            'ولي أمر' => [
                'تسجيل دخول', 'تسجيل خروج', 'ربط طالب بولي أمر', 'طلب موعد مقابلة', 'طلب تقرير أداء'
            ]
        ];

        return view('admin.activity_logs', compact('activities', 'allActions', 'roleActionsMap'));
    }

    public function cleanActivityLogs(Request $request)
    {
        $days = (int) $request->input('days', 90);
        $cutoffDate = now()->subDays($days);

        $deletedCount = \App\Models\UserActivity::where('created_at', '<', $cutoffDate)->delete();

        \Illuminate\Support\Facades\Cache::forget('distinct_user_actions');

        \App\Models\UserActivity::log('تنظيف السجلات', "تم إزالة {$deletedCount} سجلاً قدامى يتجاوز تاريخها {$days} يوماً.");

        return back()->with('success', "تم بنجاح تنظيف {$deletedCount} سجلاً قديماً يتجاوز تاريخها {$days} يوماً للحفاظ على سرعة النظام ومرونة قاعدة البيانات!");
    }

    public function updateProfile(Request $request)
    {
        $user = Auth::user();

        $request->validate([
            'full_name' => 'required|string|max:255',
            'phone'     => 'nullable|string|max:20',
        ]);

        DB::table('users')
            ->where('user_id', $user->user_id)
            ->update([
                'full_name'  => $request->full_name,
                'phone'      => $request->phone,
                'updated_at' => now(),
            ]);

        return redirect()->back()->with('success', 'تم تحديث الملف الشخصي بنجاح!');
    }

    public function updatePassword(Request $request)
    {
        $request->validate([
            'current_password' => 'required',
            'new_password'     => 'required|min:6|confirmed',
        ], [
            'current_password.required' => 'كلمة المرور الحالية مطلوبة.',
            'new_password.required'     => 'كلمة المرور الجديدة مطلوبة.',
            'new_password.min'          => 'كلمة المرور الجديدة يجب ألا تقل عن 6 أحرف.',
            'new_password.confirmed'    => 'تأكيد كلمة المرور الجديدة غير متطابق.',
        ]);

        $user = Auth::user();

        if (!Hash::check($request->current_password, $user->password)) {
            return back()->withErrors(['current_password' => 'كلمة المرور الحالية غير صحيحة.']);
        }

        DB::table('users')
            ->where('user_id', $user->user_id)
            ->update([
                'password'   => Hash::make($request->new_password),
                'updated_at' => now(),
            ]);

        return redirect()->back()->with('success', 'تم تغيير كلمة المرور بنجاح!');
    }

    public function sendOTP(Request $request)
    {
        $request->validate([
            'full_name'        => 'nullable|string|max:255',
            'phone'            => 'nullable|string|max:20',
            'email'            => 'nullable|email|max:255|unique:users,email,' . Auth::id() . ',user_id',
            'current_password' => 'nullable|string',
            'new_password'     => 'nullable|string|min:6',
            'telegram_chat_id' => 'nullable|string',
        ]);

        $user = Auth::user();

        if ($request->filled('current_password')) {
            if (!Hash::check($request->current_password, $user->password)) {
                return response()->json([
                    'success' => false,
                    'message' => 'كلمة المرور الحالية غير صحيحة.'
                ]);
            }
        }

        $otp = (string) rand(100000, 999999);

        $telegramService = new \App\Services\TelegramService();
        $telegramResult  = $telegramService->sendProfileOtpToUser($user, $otp, $request->input('telegram_chat_id'));

        if (!$telegramResult['success']) {
            return response()->json([
                'success' => false,
                'message' => $telegramResult['message']
            ]);
        }

        session([
            'admin_profile_otp'          => $otp,
            'admin_pending_profile_data' => $request->only(['full_name', 'phone', 'new_password'])
        ]);

        return response()->json([
            'success' => true,
            'message' => 'تم إرسال رمز التحقق (OTP) إلى حسابك في بوت تيليغرام بنجاح!'
        ]);
    }

    public function verifyOTP(Request $request)
    {
        $request->validate([
            'otp' => 'required|numeric'
        ]);

        if (session('admin_profile_otp') == $request->otp) {
            $user = Auth::user();
            $data = session('admin_pending_profile_data');

            $updates = ['updated_at' => now()];

            if (!empty($data['full_name'])) {
                $updates['full_name'] = $data['full_name'];
            }
            if (!empty($data['phone'])) {
                $updates['phone'] = $data['phone'];
            }
            if (!empty($data['new_password'])) {
                $updates['password'] = Hash::make($data['new_password']);
            }

            DB::table('users')
                ->where('user_id', $user->user_id)
                ->update($updates);

            session()->forget(['admin_profile_otp', 'admin_pending_profile_data']);

            return response()->json([
                'success' => true,
                'message' => 'تم تحديث البيانات بنجاح!'
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'رمز التحقق غير صحيح، يرجى المحاولة مرة أخرى.'
        ]);
    }
}
