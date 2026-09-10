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
    // parent_students.parent_id هو FK على users.user_id (وليس parents.parent_id)
    public function parentStudents()
    {
        return $this->hasMany(StudentParent::class, 'parent_id', 'user_id');
    }

    // علاقة مباشرة بالطلاب
    // parent_students.parent_id/student_id هما FK على users.user_id، لذا نربط عبر عمود user_id
    // على كلا الجدولين (parents.user_id و students.user_id) بدل المفتاحين الأساسيين parent_id/student_id
    public function students()
    {
        return $this->belongsToMany(Student::class, 'parent_students', 'parent_id', 'student_id', 'user_id', 'user_id');
    }

    public static function autoLinkStudentByPhoneOrId($parentId, $phone = null, $email = null)
    {
        $parent = static::find($parentId);
        if (!$parent) return;

        $user = $parent->user;
        $phoneSearch = $phone ?? $user?->phone;

        if ($phoneSearch && $user) {
            $students = Student::whereHas('user', function($q) use ($phoneSearch) {
                $q->where('phone', $phoneSearch)
                  ->orWhere('username', $phoneSearch);
            })->get();

            foreach ($students as $student) {
                // parent_students.parent_id/student_id هما FK على users.user_id
                \DB::table('parent_students')->updateOrInsert([
                    'parent_id'  => $user->user_id,
                    'student_id' => $student->user_id,
                ], [
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }
}

