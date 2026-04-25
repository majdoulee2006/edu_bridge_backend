<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Announcement extends Model
{
    use HasFactory;

    // ملاحظة هامة: بما أن المفتاح الأساسي في الـ Migration هو announcement_id
    protected $primaryKey = 'announcement_id';

    // الحقول التي نسمح بتعبئتها (يجب أن تطابق الـ Migration الخاص بكِ)
    protected $fillable = [
        'user_id',
        'title',
        'content',
        'type',
        'course_id'
    ];

    // علاقة تربط الإعلان باليوزر الذي كتبه
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }
}
