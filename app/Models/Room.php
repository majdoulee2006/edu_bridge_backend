<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** قاعة دراسية. department_id فارغ = قاعة مشتركة بين الأقسام. */
class Room extends Model
{
    protected $fillable = ['name', 'capacity', 'department_id', 'is_active'];

    protected $casts = ['is_active' => 'boolean'];
}
