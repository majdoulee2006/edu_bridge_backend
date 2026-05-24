<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AffairsWebController extends Controller
{
    public function showLoginForm()
    {
        return view('affairs.login');
    }

    public function login(Request $request)
    {
        // Mock login
        return redirect()->route('affairs.dashboard')->with('success', 'تم تسجيل الدخول بنجاح');
    }

    public function logout()
    {
        return redirect()->route('affairs.login');
    }

    public function dashboard()
    {
        return view('affairs.dashboard');
    }

    public function calendar()
    {
        return view('affairs.calendar');
    }

    public function activities()
    {
        return view('affairs.activities');
    }

    public function accounts()
    {
        return view('affairs.accounts');
    }

    public function leaves()
    {
        return view('affairs.leaves');
    }

    public function messages()
    {
        return view('affairs.messages');
    }

    public function notifications()
    {
        return view('affairs.notifications');
    }

    public function profile()
    {
        return view('affairs.profile');
    }

    public function settings()
    {
        return view('affairs.settings');
    }
}
