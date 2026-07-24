<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StudentRequest extends Model
{
    use HasFactory;

    protected $table = 'student_requests';

    protected $fillable = [
        'student_id',
        'type',
        'details',
        'status',
        'affairs_decision',
        'hod_decision',
        'admin_decision',
        'affairs_notes',
        'hod_notes',
        'admin_notes',
    ];

    /**
     * ربط الطلب مع الطالب صاحب الطلب
     */
    public function student()
    {
        return $this->belongsTo(Student::class, 'student_id', 'student_id');
    }
}
