<?php

namespace Tests\Feature\Attendance;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\Concerns\MakesAcademicData;
use Tests\TestCase;

class SetStudentReferencePhotoTest extends TestCase
{
    use MakesAcademicData;

    /** صورة PNG صغيرة صالحة (1×1) في ملف مؤقت. */
    private function png(): string
    {
        $path = tempnam(sys_get_temp_dir(), 'ref') . '.png';
        file_put_contents($path, base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNkYPhfDwAChwGA60e6kgAAAABJRU5ErkJggg=='));

        return $path;
    }

    public function test_sets_the_reference_photo_and_resets_the_face_embedding(): void
    {
        Storage::fake('public');
        $s = $this->makeStudent(['username' => '2026999']);
        DB::table('students')->where('student_id', $s['student_id'])->update([
            'student_code' => '2026999', 'face_embedding' => json_encode([0.1, 0.2]), 'requires_face_reset' => 1,
        ]);

        $this->artisan('students:set-photo', ['student' => '2026999', 'file' => $this->png()])->assertExitCode(0);

        $row = DB::table('students')->where('student_id', $s['student_id'])->first();
        $this->assertStringStartsWith('student_photos/2026999_', $row->reference_photo);
        $this->assertNull($row->face_embedding);
        $this->assertSame(0, (int) $row->requires_face_reset);
        Storage::disk('public')->assertExists($row->reference_photo);
    }

    public function test_rejects_unknown_student_missing_file_and_non_images(): void
    {
        Storage::fake('public');
        $s = $this->makeStudent(['username' => '2026998']);
        $txt = tempnam(sys_get_temp_dir(), 'ref') . '.txt';
        file_put_contents($txt, 'not an image');

        $this->artisan('students:set-photo', ['student' => 'nobody', 'file' => $this->png()])->assertExitCode(1);
        $this->artisan('students:set-photo', ['student' => '2026998', 'file' => '/no/such/file.png'])->assertExitCode(1);
        $this->artisan('students:set-photo', ['student' => '2026998', 'file' => $txt])->assertExitCode(1);

        $this->assertNull(DB::table('students')->where('student_id', $s['student_id'])->value('reference_photo'));
    }
}
