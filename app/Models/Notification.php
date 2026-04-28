<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Notification extends Model
{
<<<<<<< HEAD
    protected $fillable = [
        'user_id',
        'title',
        'message',
        'type',
        'is_read'
    ];

    protected $casts = [
        'is_read' => 'boolean',
    ];

    // العلاقة مع المستخدم
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }
}
=======
// protected $fillable = ['user_id', 'title', 'message', 'type', 'is_read'];
 protected $guarded = [];
}
>>>>>>> a35b4cf9f7a4fcbf94da956bbede2fb41b4436c8
