<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;

class SettingsController extends Controller
{
    /**
     * عرض صفحة الإعدادات
     */
    public function index()
    {
        $teacher = Auth::user();
        
        // جلب الإعدادات المحفوظة
        $settings = [
            'theme' => Session::get('theme', 'light'),
            'fontSize' => Session::get('fontSize', '16'),
            'lang' => Session::get('lang', 'ar'),
        ];

        return view('settein', compact('teacher', 'settings'));
    }

    /**
     * حفظ إعداد معين (AJAX)
     */
    public function save(Request $request)
    {
        $request->validate([
            'key' => 'required|string|in:theme,fontSize,lang,dir',
            'value' => 'required|string',
        ]);

        // حفظ في Session
        Session::put($request->key, $request->value);

        return response()->json([
            'success' => true,
            'message' => 'تم حفظ الإعداد بنجاح',
            'data' => [
                'key' => $request->key,
                'value' => $request->value
            ]
        ]);
    }
}
