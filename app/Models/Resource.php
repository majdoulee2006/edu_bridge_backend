<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Resource extends Model
{
    // جدول resources مفتاحه resource_id (بدون هذا السطر كان Resource::find() يبحث عن عمود id غير الموجود)
    protected $primaryKey = 'resource_id';
}
