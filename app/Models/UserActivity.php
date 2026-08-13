<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Auth;

class UserActivity extends Model
{
    protected $table = 'user_activities';

    protected $fillable = [
        'user_id',
        'user_name',
        'role_name',
        'action',
        'description',
        'ip_address',
        'user_agent',
    ];

    /**
     * تسجيل حركة جديدة للمستخدم
     */
    public static function log($action, $description = null, $user = null)
    {
        try {
            $user = $user ?? Auth::user();

            $roleMap = [
                1 => 'إدارة',
                2 => 'معلم',
                3 => 'طالب',
                4 => 'ولي أمر',
                5 => 'رئيس قسم',
                6 => 'شؤون طلاب',
            ];

            $roleName = 'مستخدم';
            if ($user) {
                if (isset($user->role_id) && isset($roleMap[$user->role_id])) {
                    $roleName = $roleMap[$user->role_id];
                } elseif (!empty($user->role)) {
                    $roleName = $roleMap[$user->role] ?? $user->role;
                }
            }

            return static::create([
                'user_id'     => $user ? ($user->user_id ?? $user->id) : null,
                'user_name'   => $user ? ($user->full_name ?? $user->name ?? 'غير معروف') : 'زائر',
                'role_name'   => $roleName,
                'action'      => $action,
                'description' => $description,
                'ip_address'  => Request::ip(),
                'user_agent'  => Request::header('User-Agent'),
            ]);
        } catch (\Throwable $e) {
            // كتم الخطأ لضمان عدم توقف عمليات تسجيل الدخول والعمليات الأخرى
            return null;
        }
    }
}
