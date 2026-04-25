<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Models\Message;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class MessageController extends Controller
{
    // عرض صفحة المراسلة مع جهات الاتصال
    public function index()
    {
        $current_user_id = Auth::user()->user_id;
        
        // جلب المستخدمين الآخرين للمراسلة
        $contacts = User::where('user_id', '!=', $current_user_id)->get(); 
        
        return view('teacher.messages', compact('contacts'));
    }

    // إرسال الرسالة وحفظها في الداتابيز
    public function sendMessage(Request $request)
    {
        $request->validate([
            'receiver_id' => 'required',
            'message_text' => 'required|string',
        ]);

        try {
            Message::create([
                'sender_id'   => Auth::user()->user_id,
                'receiver_id' => $request->receiver_id,
                'message'     => $request->message_text, // تم التعديل ليطابق المايجريشن
            ]);

            return response()->json([
                'success' => true,
                'message' => 'تم إرسال الرسالة بنجاح! ✨'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ: ' . $e->getMessage()
            ], 500);
        }
    }
}