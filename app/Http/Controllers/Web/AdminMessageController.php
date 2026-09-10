<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;

class AdminMessageController extends Controller
{
    use \App\Traits\HandlesMessagesTrait;

    public function messages()
    {
        $currentUserId = Auth::id();

        // Admin can chat with all active users
        $allUsers = \App\Models\User::where('user_id', '!=', $currentUserId)->get();

        return view('admin.messages', compact('allUsers'));
    }
}
