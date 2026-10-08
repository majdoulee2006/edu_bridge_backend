<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ParentDigest extends Model
{
    protected $fillable = [
        'parent_user_id', 'student_id', 'week_start', 'week_end',
        'facts', 'tone', 'title', 'body', 'source', 'sent_at', 'read_at',
    ];

    protected $casts = [
        'facts'      => 'array',
        'week_start' => 'date',
        'week_end'   => 'date',
        'sent_at'    => 'datetime',
        'read_at'    => 'datetime',
    ];

    public function student()
    {
        return $this->belongsTo(Student::class, 'student_id', 'student_id');
    }
}
