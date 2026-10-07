<?php

namespace Tests\Feature\Authorization;

use Illuminate\Support\Facades\DB;
use Tests\Concerns\MakesAcademicData;
use Tests\TestCase;

/**
 * معاينة وتحميل المحاضرات للأدمن: لا يُقدَّم إلا ملف داخل مجلدات التخزين العامة.
 * content_url يكتبه المعلم بحرية، فلا يجوز أن يقود إلى ملفات خارجها (.env، storage/app/private...)،
 * ولا يجوز عرض ملف احتياطي ثابت لأي محاضرة ناقصة.
 */
class AdminLectureFilesTest extends TestCase
{
    use MakesAcademicData;

    private array $created = [];

    protected function tearDown(): void
    {
        foreach ($this->created as $file) {
            @unlink($file);
        }
        parent::tearDown();
    }

    private function writeFile(string $dir, string $name, string $content): string
    {
        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }
        $path = $dir . DIRECTORY_SEPARATOR . $name;
        file_put_contents($path, $content);
        $this->created[] = $path;

        return $path;
    }

    private function lesson(array $attrs): int
    {
        return DB::table('lessons')->insertGetId(array_merge([
            'course_id'  => $this->makeCourse(),
            'title'      => 'Lecture',
            'created_at' => now(),
            'updated_at' => now(),
        ], $attrs));
    }

    private function admin()
    {
        return $this->actingAs($this->makeUser('admin'));
    }

    public function test_file_inside_public_storage_is_served(): void
    {
        $name = 'test_lecture_' . uniqid() . '.txt';
        $this->writeFile(storage_path('app/public/lectures'), $name, 'LECTURE-BODY');
        $id = $this->lesson(['file_path' => "lectures/$name"]);

        $response = $this->admin()->get("/admin/lectures/$id/preview");

        $response->assertOk();
        $this->assertSame('LECTURE-BODY', file_get_contents($response->baseResponse->getFile()->getPathname()));
        $this->admin()->get("/admin/lectures/$id/download")->assertOk()->assertDownload();
    }

    public function test_path_traversal_in_content_url_is_refused(): void
    {
        $id = $this->lesson(['content_url' => '../../.env']);

        $this->admin()->get("/admin/lectures/$id/preview")->assertRedirect()->assertSessionHas('error');
        $this->admin()->get("/admin/lectures/$id/download")->assertRedirect()->assertSessionHas('error');
    }

    public function test_files_in_private_storage_are_refused(): void
    {
        $name = 'secret_' . uniqid() . '.txt';
        $this->writeFile(storage_path('app/private'), $name, 'TOP-SECRET');
        $id = $this->lesson(['file_path' => "private/$name"]);

        $this->admin()->get("/admin/lectures/$id/preview")->assertRedirect()->assertSessionHas('error');
        $this->admin()->get("/admin/lectures/$id/download")->assertRedirect()->assertSessionHas('error');
    }

    public function test_missing_file_is_an_error_and_never_replaced_by_a_fallback_file(): void
    {
        $id = $this->lesson(['file_path' => 'lectures/does_not_exist_' . uniqid() . '.pdf']);

        $this->admin()->get("/admin/lectures/$id/preview")->assertRedirect()->assertSessionHas('error');
        $this->admin()->get("/admin/lectures/$id/download")->assertRedirect()->assertSessionHas('error');
    }

    public function test_web_links_still_redirect(): void
    {
        $id = $this->lesson(['content_url' => 'https://example.test/video']);

        $this->admin()->get("/admin/lectures/$id/preview")->assertRedirect('https://example.test/video');
    }
}
