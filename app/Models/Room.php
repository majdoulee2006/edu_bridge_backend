<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * قاعة دراسية. department_id فارغ = قاعة مشتركة بين الأقسام.
 * program_id + year = **قاعة خاصة بشعبة** (دورة وسنة)؛ فارغان = قاعة مشتركة لأي شعبة.
 */
class Room extends Model
{
    public const TYPES = ['classroom' => 'قاعة شعبة', 'lab' => 'مخبر', 'hall' => 'قاعة كبرى'];

    protected $fillable = ['name', 'capacity', 'department_id', 'program_id', 'year', 'type', 'is_active'];

    protected $casts = ['is_active' => 'boolean', 'year' => 'integer', 'capacity' => 'integer'];

    public function program()
    {
        return $this->belongsTo(Program::class, 'program_id', 'id');
    }

    /** مفتاح الشعبة الذي تخصه القاعة (مثل "4-1")، أو null للقاعة المشتركة. */
    public function groupKey(): ?string
    {
        return $this->program_id && $this->year ? $this->program_id . '-' . $this->year : null;
    }
}
