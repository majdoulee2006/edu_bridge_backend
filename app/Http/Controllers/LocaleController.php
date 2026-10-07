<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Session;

class LocaleController extends Controller
{
    /**
     * Switch language for Web portal.
     */
    public function switchLanguage(Request $request, string $locale)
    {
        if (!in_array($locale, ['ar', 'en'])) {
            $locale = 'ar';
        }

        Session::put('locale', $locale);
        App::setLocale($locale);

        if ($request->user() && $request->user()->locale !== $locale) {
            \Illuminate\Support\Facades\DB::table('users')
                ->where('user_id', $request->user()->user_id)
                ->update(['locale' => $locale]);
        }

        return redirect()->back();
    }

    /**
     * Set language for API / Mobile client.
     */
    public function setApiLocale(Request $request)
    {
        $validated = $request->validate([
            'locale' => 'required|string|in:ar,en',
        ]);

        $locale = $validated['locale'];

        if ($request->user()) {
            $user = $request->user();
            $user->locale = $locale;
            $user->save();
        }

        return response()->json([
            'success' => true,
            'message' => $locale === 'ar' ? 'تم تغيير اللغة بنجاح' : 'Language updated successfully',
            'locale'  => $locale,
        ]);
    }
}
