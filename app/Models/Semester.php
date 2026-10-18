<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Semester extends Model
{
    use HasFactory;

    protected $primaryKey = 'semester_id';
    protected $table = 'semesters';

    protected $fillable = ['name', 'start_date', 'end_date', 'is_active'];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'is_active' => 'boolean',
    ];

    // العلاقات
    public function courses()
    {
        return $this->hasMany(Course::class, 'semester_id', 'semester_id');
    }

    public function enrollments()
    {
        return $this->hasMany(Enrollment::class, 'semester_id', 'semester_id');
    }

    public function exams()
    {
        return $this->hasMany(Exam::class, 'semester_id', 'semester_id');
    }

    public function assignments()
    {
        return $this->hasMany(Assignment::class, 'semester_id', 'semester_id');
    }

    public function attendances()
    {
        return $this->hasMany(Attendance::class, 'semester_id', 'semester_id');
    }

    // Scope للفصل النشط
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /** الفصل الفعّال الحالي، أو null إن لم يوجد. (إن وُجد أكثر من واحد نأخذ الأحدث.) */
    public static function current(): ?self
    {
        return static::active()->orderByDesc('semester_id')->first();
    }

    public static function currentId(): ?int
    {
        return static::current()?->semester_id;
    }

    /**
     * الفصل الفعّال، أو استثناء برسالة عربية واضحة. لكل مقرر فصل، فلا يُنشأ مقرر قبل أن يوجد فصل فعّال.
     *
     * @throws \DomainException
     */
    public static function currentOrFail(): self
    {
        return static::current() ?? throw new \DomainException('لا يوجد فصل دراسي فعّال. فعّل فصلاً دراسياً أولاً أو اختر الفصل صراحة.');
    }

    /** للتجهيز (seeders/اختبارات): الفصل الفعّال، وإن لم يوجد يُنشأ فصل افتراضي فعّال. */
    public static function currentOrCreate(): self
    {
        return static::current() ?? static::create([
            'name' => 'الفصل الدراسي الأول', 'start_date' => now()->startOfMonth()->toDateString(),
            'end_date' => now()->addMonths(4)->toDateString(), 'is_active' => true,
        ]);
    }

    public function isCurrent(): bool
    {
        return (bool) $this->is_active;
    }
}
