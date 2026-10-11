<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Program extends Model
{
    protected $fillable = ['name', 'department_id'];

    public function courses()
    {
        return $this->belongsToMany(Course::class, 'course_program', 'program_id', 'course_id');
    }

    public function department()
    {
        return $this->belongsTo(Department::class, 'department_id', 'department_id');
    }

    // الاسم المترجم للاختصاص بحسب اللغة الحالية
    public function getDisplayNameAttribute(): string
    {
        $name = trim($this->attributes['name'] ?? '');
        $map = [
            'معلوماتية'                   => 'prog_informatics',
            'اتصالات'                     => 'prog_telecom',
            'الكترون'                     => 'prog_electronics',
            'ذكاء اصطناعي'                => 'prog_ai',
            'مخبري'                       => 'prog_medical_lab',
            'صيدلة'                       => 'prog_pharmacy',
            'هندسة مدني'                  => 'prog_civil_eng',
            'هندسة عمارة'                 => 'prog_arch_eng',
            'هندسة ديكور وتصميم داخلي'   => 'prog_interior_design',
            'إدارة اعمال'                 => 'prog_business_admin',
            'محاسبة'                      => 'prog_accounting',
            'مصارف و تأمين'               => 'prog_banking_insurance',
        ];

        $key = $map[$name] ?? null;
        if ($key && \Illuminate\Support\Facades\Lang::has("messages.{$key}")) {
            return __("messages.{$key}");
        }

        return $name;
    }
}
