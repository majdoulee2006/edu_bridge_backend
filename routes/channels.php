<?php

use Illuminate\Support\Facades\Broadcast;

// القناة الخاصة بالدردشة: chat.5 (يعني الطالب رقم 5)
Broadcast::channel('chat.{id}', function ($user, $id) {
    // هون بنشيك: هل الـ ID اللي عم يحاول يفتح القناة هو نفسه الـ ID تبع اليوزر اللي مسجل دخول؟
    return (int) $user->user_id === (int) $id;
});

// قناة لمعرفة المتصلين (Presence Channel)
Broadcast::channel('presence-online', function ($user) {
    if ($user) {
        // بنرجع بيانات المستخدم عشان تظهر للكل إنه أونلاين
        return ['id' => $user->user_id, 'name' => $user->first_name]; 
    }
    return false;
});