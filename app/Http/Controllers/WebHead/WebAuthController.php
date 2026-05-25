<?php

namespace App\Http\Controllers\WebHead;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;

class WebAuthController extends Controller
{
    public function showLogin()
    {
        return view('login');
    }

    public function login(Request $request)
    {
        $username = $request->input('username');
        $password = $request->input('password');

        // Attempt login using either username or email
        $user = User::where('username', $username)->orWhere('email', $username)->first();

        if ($user && \Hash::check($password, $user->password)) {
            Auth::login($user);
            
            if ($user->role_id == 5) {
                return redirect('/dashboard');
            }
            
            return back()->withErrors(['msg' => 'عذراً، لوحة التحكم الخاصة بهذا الدور ('. $user->role .') غير جاهزة بعد.']);
        }

        return back()->withErrors(['msg' => 'اسم المستخدم أو كلمة المرور غير صحيحة.']);
    }


}
