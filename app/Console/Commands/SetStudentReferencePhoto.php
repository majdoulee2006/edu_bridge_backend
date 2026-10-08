<?php

namespace App\Console\Commands;

use App\Models\Student;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

/**
 * يضع الصورة الرسمية (المرجعية) لطالب كما تفعل شؤون الطلبة: تُنسخ إلى student_photos وتُصفَّر البصمة،
 * فيبني التطبيق بصمة الوجه من هذه الصورة عند أول مسح حضور.
 * مفيد للتجارب (طالب منشأ بالـ seeder لا صورة له فيرفض التطبيق مسحه) وللإدخال اليدوي.
 */
class SetStudentReferencePhoto extends Command
{
    protected $signature = 'students:set-photo
        {student : اسم دخول الطالب أو رقمه الجامعي أو student_code}
        {file : مسار صورة وجه واضحة (jpg/png) على هذا الجهاز}';

    protected $description = 'يضع الصورة الرسمية لطالب ويصفّر بصمة وجهه لتُبنى من الصورة الجديدة';

    public function handle(): int
    {
        $path = (string) $this->argument('file');
        if (!is_file($path)) {
            $this->error("الملف غير موجود: {$path}");

            return self::FAILURE;
        }

        $mime = mime_content_type($path) ?: '';
        $ext  = ['image/jpeg' => 'jpg', 'image/png' => 'png'][$mime] ?? null;
        if (!$ext) {
            $this->error("الصيغة غير مدعومة ({$mime}). استخدم jpg أو png.");

            return self::FAILURE;
        }

        $key = (string) $this->argument('student');
        $student = Student::where('student_code', $key)->first()
            ?? Student::whereIn('user_id', User::where('username', $key)->orWhere('university_id', $key)->pluck('user_id'))->first();
        if (!$student) {
            $this->error("لا يوجد طالب بالمعرّف: {$key}");

            return self::FAILURE;
        }

        $stored = 'student_photos/' . $student->student_code . '_' . time() . '.' . $ext;
        Storage::disk('public')->put($stored, file_get_contents($path));

        $student->forceFill([
            'reference_photo'     => $stored,
            'face_embedding'      => null,
            'requires_face_reset' => false,
        ])->save();

        $this->info("تم: {$student->student_code} ← {$stored} (البصمة ستُبنى من الصورة عند أول مسح).");

        return self::SUCCESS;
    }
}
