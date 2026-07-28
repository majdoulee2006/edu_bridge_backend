<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Parents extends Model
{
    protected $table = 'parents';
    protected $primaryKey = 'parent_id';
    public $timestamps = true;

    protected $fillable = ['user_id'];

    // علاقة باليوزر
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }

    // علاقة بالطلاب عبر الجدول الوسيط
    public function parentStudents()
    {
        return $this->hasMany(StudentParent::class, 'parent_id');
    }

    // علاقة مباشرة بالطلاب
    public function students()
    {
        return $this->belongsToMany(Student::class, 'parent_students', 'parent_id', 'student_id');
    }

    public static function autoLinkStudentByPhoneOrId($parentId, $phone = null, $email = null)
    {
        $parent = static::find($parentId);
        if (!$parent) return;

        $user = $parent->user;
        $phoneSearch = $phone ?? $user?->phone;

        if ($phoneSearch) {
            $students = Student::whereHas('user', function($q) use ($phoneSearch) {
                $q->where('phone', $phoneSearch)
                  ->orWhere('username', $phoneSearch);
            })->get();

            foreach ($students as $student) {
                \DB::table('parent_students')->updateOrInsert([
                    'parent_id'  => $parentId,
                    'student_id' => $student->student_id,
                ], [
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }
}

