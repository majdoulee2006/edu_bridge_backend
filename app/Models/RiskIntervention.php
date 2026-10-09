<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** إجراء متابعة سجّله المرشد/رئيس القسم لطالب معرَّض للتعثر. */
class RiskIntervention extends Model
{
    public const TYPES = [
        'call'           => 'اتصال بالطالب',
        'meeting'        => 'لقاء مع الطالب',
        'message'        => 'رسالة للطالب',
        'parent_contact' => 'تواصل مع ولي الأمر',
        'referral'       => 'إحالة (إدارة/دعم)',
        'note'           => 'ملاحظة',
        'no_action'      => 'لا إجراء مطلوب',
    ];

    protected $fillable = ['student_id', 'author_user_id', 'type', 'note', 'follow_up_on', 'score_at_time', 'level_at_time'];

    protected $casts = ['follow_up_on' => 'date', 'score_at_time' => 'float'];

    public function author()
    {
        return $this->belongsTo(User::class, 'author_user_id', 'user_id');
    }
}
