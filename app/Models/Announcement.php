<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Announcement extends Model
{
    use HasFactory;

    protected $primaryKey = 'announcement_id';

    protected $fillable = [
        'user_id',
        'title',
        'content',
        'type',
        'course_id',
    ];

    // Relationship with the user who created the announcement
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }
}
