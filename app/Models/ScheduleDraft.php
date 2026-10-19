<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** مسودة جدول لقسم: لا تمس schedules الحقيقي حتى يعتمدها رئيس القسم. */
class ScheduleDraft extends Model
{
    public const DRAFT = 'draft';
    public const APPROVED = 'approved';
    public const DISCARDED = 'discarded';

    public const TYPE_WEEKLY = 'weekly';
    public const TYPE_EXAM = 'exam';

    protected $fillable = ['department_id', 'semester_id', 'type', 'created_by', 'name', 'status', 'settings', 'stats', 'backup', 'approved_by', 'approved_at', 'reverted_at'];

    protected $casts = [
        'settings' => 'array', 'stats' => 'array', 'backup' => 'array',
        'approved_at' => 'datetime', 'reverted_at' => 'datetime',
    ];

    public function entries()
    {
        return $this->hasMany(ScheduleDraftEntry::class, 'draft_id');
    }

    public function semester()
    {
        return $this->belongsTo(Semester::class, 'semester_id', 'semester_id');
    }

    /** مسودة لفصل غير الفعّال حالياً: تُحفظ للفصل القادم ولا تُعتمد الآن. */
    public function isSavedForNext(): bool
    {
        return $this->status === self::DRAFT && $this->semester_id !== null && (int) $this->semester_id !== (int) Semester::currentId();
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by', 'user_id');
    }
}
