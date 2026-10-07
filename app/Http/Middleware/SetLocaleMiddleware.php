<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Session;
use Symfony\Component\HttpFoundation\Response;

class SetLocaleMiddleware
{
    /**
     * Handle an incoming request and set application locale.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $locale = 'ar'; // default

        // 1. Check Query parameter if explicitly provided ?lang=en
        if ($request->has('lang') && in_array($request->query('lang'), ['ar', 'en'])) {
            $locale = $request->query('lang');
            if ($request->hasSession()) {
                Session::put('locale', $locale);
            }
        }
        // 2. Check Session (Web Portals)
        elseif ($request->hasSession() && Session::has('locale')) {
            $sessionLocale = Session::get('locale');
            if (in_array($sessionLocale, ['ar', 'en'])) {
                $locale = $sessionLocale;
            }
        }
        // 3. Check Accept-Language Header (Mobile App API requests)
        elseif ($request->hasHeader('Accept-Language')) {
            $headerLocale = strtolower(substr($request->header('Accept-Language'), 0, 2));
            if (in_array($headerLocale, ['ar', 'en'])) {
                $locale = $headerLocale;
            }
        }
        // 4. Check Authenticated User preference
        elseif ($request->user() && in_array($request->user()->locale, ['ar', 'en'])) {
            $locale = $request->user()->locale;
        }

        App::setLocale($locale);

        $response = $next($request);

        // Add Content-Language header to response
        if (method_exists($response, 'header')) {
            $response->header('Content-Language', $locale);
        }

        return $response;
    }
}
