<?php

namespace Tests\Feature\Authorization;

use Illuminate\Support\Facades\DB;
use Tests\Concerns\MakesAcademicData;
use Tests\TestCase;

/**
 * شؤون الطلاب: تفعيل الفصل الدراسي وترفيع طلاب السنة الأولى.
 *  - التفعيل كان يستعلم عن عمود semester_name وجدول semesters فيه name فقط (خطأ SQL)
 *  - المساران (/promote/year2 و /students/promote) صارا يعتمدان نفس المنطق
 */
class AffairsSemesterPromotionTest extends TestCase
{
    use MakesAcademicData;

    private function affairs()
    {
        return $this->actingAs($this->makeUser('affairs'));
    }

    public function test_activating_a_semester_creates_it_and_keeps_a_single_active_one(): void
    {
        DB::table('semesters')->insert([
            'name' => 'الفصل القديم', 'start_date' => '2025-09-01', 'end_date' => '2026-01-15',
            'is_active' => true, 'created_at' => now(), 'updated_at' => now(),
        ]);

        $this->affairs()->post('/affairs/semester/activate', [
            'semester_name' => 'الفصل الأول',
            'start_date'    => '2026-09-20',
            'end_date'      => '2027-01-20',
        ])->assertRedirect()->assertSessionHas('success');

        $this->assertSame(1, DB::table('semesters')->where('is_active', true)->count());
        $this->assertSame('الفصل الأول', DB::table('semesters')->where('is_active', true)->value('name'));

        // إعادة التفعيل بنفس الاسم تحدّث التواريخ ولا تكرر السجل
        $this->affairs()->post('/affairs/semester/activate', [
            'semester_name' => 'الفصل الأول',
            'start_date'    => '2026-09-25',
            'end_date'      => '2027-01-25',
        ])->assertRedirect();

        $this->assertSame(1, DB::table('semesters')->where('name', 'الفصل الأول')->count());
        $this->assertSame('2026-09-25', (string) DB::table('semesters')->where('name', 'الفصل الأول')->value('start_date'));
    }

    public function test_promote_year2_promotes_first_year_students_only_and_updates_everything(): void
    {
        $first  = $this->makeStudent(['academic_year' => 'السنة الأولى']);
        $second = $this->makeStudent(['academic_year' => 'السنة الثانية']);
        DB::table('students')->where('student_id', $first['student_id'])->update(['level' => 'السنة الأولى']);
        DB::table('students')->where('student_id', $second['student_id'])->update(['level' => 'السنة الثانية']);

        $this->affairs()->post('/affairs/promote/year2')->assertRedirect()->assertSessionHas('success');

        $this->assertSame('السنة الثانية', DB::table('students')->where('student_id', $first['student_id'])->value('level'));
        $this->assertSame('السنة الثانية', DB::table('users')->where('user_id', $first['user']->user_id)->value('academic_year'));
        $this->assertSame(1, DB::table('notifications')->where('user_id', $first['user']->user_id)->where('category', 'academic')->count());
        $this->assertSame(0, DB::table('notifications')->where('user_id', $second['user']->user_id)->count());
    }

    public function test_both_promotion_routes_behave_the_same(): void
    {
        $a = $this->makeStudent(['academic_year' => 'السنة الأولى']);
        $b = $this->makeStudent(['academic_year' => 'السنة الأولى']);
        DB::table('students')->whereIn('student_id', [$a['student_id'], $b['student_id']])->update(['level' => 'السنة الأولى']);

        $this->affairs()->post('/affairs/students/promote', ['student_id' => $a['student_id']])->assertRedirect();
        $this->affairs()->post('/affairs/students/promote', ['student_id' => $b['student_id']])->assertRedirect();

        foreach ([$a, $b] as $st) {
            $this->assertSame('السنة الثانية', DB::table('students')->where('student_id', $st['student_id'])->value('level'));
            $this->assertSame('السنة الثانية', DB::table('users')->where('user_id', $st['user']->user_id)->value('academic_year'));
        }
    }
}
