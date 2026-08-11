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

    protected $appends = ['formatted_details'];

    /**
     * ربط الطلب مع الطالب صاحب الطلب
     */
    public function student()
    {
        return $this->belongsTo(Student::class, 'student_id', 'student_id');
    }

    /**
     * تنسيق تفاصيل الطلب بشكل مفهوم للإنسان
     */
    public function getFormattedDetailsAttribute()
    {
        $raw = $this->details;
        if (empty($raw)) return '';

        $trimmed = trim($raw);
        if (str_starts_with($trimmed, '{') || str_starts_with($trimmed, '[')) {
            $decoded = json_decode($raw, true);
            if (is_array($decoded)) {
                $parts = [];
                if (!empty($decoded['reason'])) {
                    $parts[] = "السبب: " . $decoded['reason'];
                }
                if (!empty($decoded['new_device_id'])) {
                    $parts[] = "معرف الجهاز الجديد: " . $decoded['new_device_id'];
                }
                if (!empty($parts)) {
                    return implode(' | ', $parts);
                }
            }
        }
        return $raw;
    }
}
