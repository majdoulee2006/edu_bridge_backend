<?php

namespace Tests\Feature\Attendance;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Tests\Concerns\MakesAcademicData;
use Tests\TestCase;

class SecureFaceImagesCommandTest extends TestCase
{
    use MakesAcademicData;

    public function test_it_moves_legacy_public_images_into_private_storage(): void
    {
        Storage::fake('local');

        $student  = $this->makeStudent();
        $lessonId = DB::table('lessons')->insertGetId([
            'course_id' => $this->makeCourse(), 'title' => 'L', 'created_at' => now(), 'updated_at' => now(),
        ]);

        // مجلد مؤقت معزول: الاختبار لا يلمس public/uploads/faces الحقيقي أبداً
        $dir  = storage_path('framework/testing/tmp_faces_' . bin2hex(random_bytes(4)));
        File::ensureDirectoryExists($dir);
        $name = 'legacy_test_' . bin2hex(random_bytes(3)) . '.jpg';
        File::put($dir . '/' . $name, 'bytes');

        $id = DB::table('attendance')->insertGetId([
            'student_id' => $student['student_id'], 'lesson_id' => $lessonId, 'status' => 'present',
            'attendance_date' => now()->toDateString(), 'face_image' => 'uploads/faces/' . $name,
            'created_at' => now(), 'updated_at' => now(),
        ]);

        // تجربة فقط: لا شيء يتغيّر
        $this->artisan('faces:secure', ['--dry-run' => true, '--source' => $dir])->assertSuccessful();
        $this->assertFileExists($dir . '/' . $name);

        // تنفيذ حقيقي
        $this->artisan('faces:secure', ['--source' => $dir])->assertSuccessful();

        $this->assertFileDoesNotExist($dir . '/' . $name);
        Storage::disk('local')->assertExists('faces/' . $name);
        $this->assertSame('faces/' . $name, DB::table('attendance')->where('attendance_id', $id)->value('face_image'));

        File::deleteDirectory($dir);
    }
}
