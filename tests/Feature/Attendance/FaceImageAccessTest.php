<?php

namespace Tests\Feature\Attendance;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\Concerns\MakesAcademicData;
use Tests\TestCase;

/**
 * S-13: صور الوجه (بيانات حيوية) في تخزين خاص، ولا تُعرض إلا لمن يحق له.
 */
class FaceImageAccessTest extends TestCase
{
    use MakesAcademicData;

    private array $student;
    private array $otherStudent;
    private array $teacher;
    private array $otherTeacher;
    private int $attendanceId;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');

        $this->student      = $this->makeStudent();
        $this->otherStudent = $this->makeStudent();
        $this->teacher      = $this->makeTeacher();
        $this->otherTeacher = $this->makeTeacher();

        $courseId = $this->makeCourse();
        $this->assignTeacher($courseId, $this->teacher['teacher_id']);
        $this->enroll($this->student['student_id'], $courseId);

        $lessonId = DB::table('lessons')->insertGetId([
            'course_id' => $courseId, 'title' => 'L', 'created_at' => now(), 'updated_at' => now(),
        ]);

        Storage::disk('local')->put('faces/sample.jpg', 'fake-image-bytes');

        $this->attendanceId = DB::table('attendance')->insertGetId([
            'student_id' => $this->student['student_id'], 'lesson_id' => $lessonId, 'status' => 'present',
            'attendance_date' => now()->toDateString(), 'face_image' => 'faces/sample.jpg',
            'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    private function url(): string
    {
        return "/api/attendance/{$this->attendanceId}/face";
    }

    public function test_authorized_roles_can_view_the_image(): void
    {
        foreach ([
            $this->student['user'], $this->teacher['user'],
            $this->makeUser('admin'), $this->makeUser('affairs'),
        ] as $user) {
            $this->actAs($user)->get($this->url())->assertOk();
        }
    }

    public function test_unrelated_users_cannot_view_the_image(): void
    {
        foreach ([
            $this->otherStudent['user'], $this->otherTeacher['user'],
            $this->makeParent()['user'], $this->makeUser('head'),
        ] as $user) {
            $this->actAs($user)->get($this->url())->assertForbidden();
        }
    }

    public function test_image_requires_authentication(): void
    {
        $this->getJson($this->url())->assertUnauthorized();
    }

    public function test_saved_images_are_private_and_validated(): void
    {
        $png = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg==';

        $path = \App\Support\FaceImageStore::save($png, 'face_1');
        $this->assertNotNull($path);
        $this->assertStringStartsWith('faces/', $path);
        Storage::disk('local')->assertExists($path);
        $this->assertFileDoesNotExist(public_path('uploads/faces/' . basename($path)));

        // محتوى ليس صورة (مثلاً سكربت) يُرفض
        $this->assertNull(\App\Support\FaceImageStore::save(base64_encode('<?php echo 1;'), 'face_1'));
        $this->assertNull(\App\Support\FaceImageStore::save('not-base64!!', 'face_1'));
    }
}
