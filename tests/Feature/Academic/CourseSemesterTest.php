<?php

namespace Tests\Feature\Academic;

use App\Models\Semester;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Tests\Concerns\MakesAcademicData;
use Tests\TestCase;

/**
 * لكل مقرر فصل دراسي، دون استثناء: قاعدة بيانات (NOT NULL + RESTRICT) وفرض في كل مسارات الإنشاء والتعديل.
 */
class CourseSemesterTest extends TestCase
{
    use MakesAcademicData;

    private function rawCourse(array $attrs = []): int
    {
        return DB::table('courses')->insertGetId(array_merge([
            'title' => 'C' . $this->nextSeq(), 'level' => '1', 'created_at' => now(), 'updated_at' => now(),
        ], $attrs));
    }

    // ───────────────────────────── قاعدة البيانات ─────────────────────────────

    public function test_the_database_refuses_a_course_without_a_semester(): void
    {
        $this->expectException(QueryException::class);

        $this->rawCourse(); // بلا semester_id
    }

    public function test_the_database_refuses_to_clear_a_courses_semester(): void
    {
        $courseId = $this->makeCourse();

        $this->expectException(QueryException::class);

        DB::table('courses')->where('course_id', $courseId)->update(['semester_id' => null]);
    }

    public function test_a_semester_that_has_courses_cannot_be_deleted(): void
    {
        $semester = Semester::currentOrCreate();
        $this->makeCourse(null, ['semester_id' => $semester->semester_id]);

        $this->expectException(QueryException::class);

        DB::table('semesters')->where('semester_id', $semester->semester_id)->delete();
    }

    public function test_an_empty_semester_can_still_be_deleted(): void
    {
        $id = DB::table('semesters')->insertGetId([
            'name' => 'فصل فارغ', 'start_date' => '2027-02-01', 'end_date' => '2027-06-30', 'is_active' => false,
            'created_at' => now(), 'updated_at' => now(),
        ]);

        $this->assertSame(1, DB::table('semesters')->where('semester_id', $id)->delete());
    }

    // ───────────────────────────── Semester::current ─────────────────────────────

    public function test_current_is_the_active_semester_and_fails_clearly_when_none(): void
    {
        $this->assertNull(Semester::current());

        try {
            Semester::currentOrFail();
            $this->fail('كان يجب أن يرفض عند غياب فصل فعّال');
        } catch (\DomainException $e) {
            $this->assertStringContainsString('فصل', $e->getMessage());
        }

        $created = Semester::currentOrCreate();
        $this->assertTrue($created->is_active);
        $this->assertSame($created->semester_id, Semester::currentId());
        $this->assertSame($created->semester_id, Semester::currentOrCreate()->semester_id, 'لا يكرّر الإنشاء');
    }

    public function test_the_newest_active_semester_wins_when_several_are_active(): void
    {
        $old = DB::table('semesters')->insertGetId(['name' => 'قديم', 'start_date' => '2026-01-01', 'end_date' => '2026-05-01', 'is_active' => true, 'created_at' => now(), 'updated_at' => now()]);
        $new = DB::table('semesters')->insertGetId(['name' => 'جديد', 'start_date' => '2026-09-01', 'end_date' => '2027-01-01', 'is_active' => true, 'created_at' => now(), 'updated_at' => now()]);

        $this->assertSame($new, Semester::currentId());
        $this->assertNotSame($old, Semester::currentId());
    }

    // ───────────────────────────── API: إنشاء وتعديل ─────────────────────────────

    private function admin()
    {
        return $this->actAs($this->makeUser('admin'));
    }

    public function test_api_create_defaults_to_the_active_semester(): void
    {
        $semester = Semester::currentOrCreate();

        $this->admin()->postJson('/api/admin/courses', ['title' => 'مقرر جديد'])->assertSuccessful();

        $this->assertSame($semester->semester_id, (int) DB::table('courses')->where('title', 'مقرر جديد')->value('semester_id'));
    }

    public function test_api_create_accepts_an_explicit_semester(): void
    {
        Semester::currentOrCreate();
        $next = DB::table('semesters')->insertGetId(['name' => 'القادم', 'start_date' => '2027-02-01', 'end_date' => '2027-06-30', 'is_active' => false, 'created_at' => now(), 'updated_at' => now()]);

        $this->admin()->postJson('/api/admin/courses', ['title' => 'للفصل القادم', 'semester_id' => $next])->assertSuccessful();

        $this->assertSame($next, (int) DB::table('courses')->where('title', 'للفصل القادم')->value('semester_id'));
    }

    public function test_api_create_without_any_active_semester_is_refused_with_a_clear_message(): void
    {
        $res = $this->admin()->postJson('/api/admin/courses', ['title' => 'بلا فصل'])->assertStatus(422);

        $this->assertStringContainsString('فصل', $res->json('message'));
        $this->assertSame(0, DB::table('courses')->where('title', 'بلا فصل')->count());
    }

    public function test_api_create_rejects_an_unknown_semester(): void
    {
        Semester::currentOrCreate();

        $this->admin()->postJson('/api/admin/courses', ['title' => 'x', 'semester_id' => 99999])->assertStatus(422);
        $this->assertSame(0, DB::table('courses')->where('title', 'x')->count());
    }

    public function test_api_update_cannot_remove_or_break_the_semester(): void
    {
        $semester = Semester::currentOrCreate();
        $courseId = $this->makeCourse();

        $this->admin()->putJson("/api/admin/courses/$courseId", ['semester_id' => null])->assertStatus(422);
        $this->admin()->putJson("/api/admin/courses/$courseId", ['semester_id' => 99999])->assertStatus(422);
        $this->assertSame($semester->semester_id, (int) DB::table('courses')->where('course_id', $courseId)->value('semester_id'));
    }

    public function test_api_update_can_move_a_course_to_another_existing_semester(): void
    {
        Semester::currentOrCreate();
        $courseId = $this->makeCourse();
        $next = DB::table('semesters')->insertGetId(['name' => 'القادم', 'start_date' => '2027-02-01', 'end_date' => '2027-06-30', 'is_active' => false, 'created_at' => now(), 'updated_at' => now()]);

        $this->admin()->putJson("/api/admin/courses/$courseId", ['semester_id' => $next])->assertSuccessful();

        $this->assertSame($next, (int) DB::table('courses')->where('course_id', $courseId)->value('semester_id'));
    }

    // ───────────────────────────── ويب الأدمن ─────────────────────────────

    public function test_web_admin_requires_an_existing_semester(): void
    {
        Semester::currentOrCreate();
        $program = $this->makeProgram($this->makeDepartment());
        $this->actingAs($this->makeUser('admin'));

        $payload = ['title' => 'مقرر ويب', 'year' => 1, 'program_id' => $program, 'hours' => 3];

        $this->post(route('admin.semesters-subjects.store'), $payload)->assertSessionHasErrors('semester_id');
        $this->post(route('admin.semesters-subjects.store'), $payload + ['semester_id' => 99999])->assertSessionHasErrors('semester_id');
        $this->assertSame(0, DB::table('courses')->where('title', 'مقرر ويب')->count());

        $this->post(route('admin.semesters-subjects.store'), $payload + ['semester_id' => Semester::currentId()])->assertSessionHasNoErrors();
        $this->assertSame(1, DB::table('courses')->where('title', 'مقرر ويب')->count());
    }

    // ───────────────────────────── الترحيل ─────────────────────────────

    public function test_backfill_assigns_the_active_semester_and_nothing_else_changes(): void
    {
        // أوامر DDL تُنهي معاملة الاختبار تلقائياً في MySQL، فلا تتراجع بياناتها. ننظّف يدوياً في finally.
        try {
            $active = Semester::currentOrCreate();
            $other = DB::table('semesters')->insertGetId(['name' => 'ثانٍ', 'start_date' => '2027-02-01', 'end_date' => '2027-06-30', 'is_active' => false, 'created_at' => now(), 'updated_at' => now()]);
            $kept = $this->makeCourse(null, ['semester_id' => $other]);

            // نعيد القاعدة إلى حالتها قبل الترحيل: العمود يقبل NULL (المفتاح الأجنبي يبقى، والترحيل نفسه يسقطه ويعيده)
            DB::statement('ALTER TABLE courses MODIFY semester_id BIGINT UNSIGNED NULL');
            $legacy = $this->rawCourse();

            (require base_path('database/migrations/2026_10_19_100000_enforce_course_semester.php'))->up();

            $this->assertSame($active->semester_id, (int) DB::table('courses')->where('course_id', $legacy)->value('semester_id'));
            $this->assertSame($other, (int) DB::table('courses')->where('course_id', $kept)->value('semester_id'), 'ما له فصل لا يتغير');

            // وبعد الترحيل عاد الإلزام
            $this->expectException(QueryException::class);
            $this->rawCourse();
        } finally {
            DB::table('courses')->delete();
            DB::table('semesters')->delete();
        }
    }
}
