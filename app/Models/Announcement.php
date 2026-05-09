<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Announcement extends Model
{
    use HasFactory;

    // هاد السطر بيسمحلك تتعاملي مع الداتابيز بدون قيود
    protected $guarded = [];
} 
