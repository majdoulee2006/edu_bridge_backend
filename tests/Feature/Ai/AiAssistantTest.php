<?php

namespace Tests\Feature\Ai;

use App\Services\Ai\LoginLinkSanitizer;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Tests\Concerns\MakesAcademicData;
use Tests\TestCase;

class AiAssistantTest extends TestCase
{
    use MakesAcademicData;

    protected function setUp(): void
    {
        parent::setUp();
        config(['services.gemini.key' => null]);
    }

    private function ask(string $message, array $extra = []): \Illuminate\Testing\TestResponse
    {
        return $this->postJson('/api/ai/chat', array_merge(['message' => $message], $extra));
    }

    /** طالب مسجّل بمقرر واحد، مع $absent غياب غير معذور و$excused معذور و$present حضور. */
    private function studentWithAttendance(int $present, int $absent, int $excused = 0): array
    {
        $s        = $this->makeStudent();
        $courseId = $this->makeCourse(null, ['title' => 'رياضيات اختبار']);
        $this->enroll($s['student_id'], $courseId);

        $lessonId = DB::table('lessons')->insertGetId([
            'course_id' => $courseId, 'title' => 'L', 'created_at' => now(), 'updated_at' => now(),
        ]);

        $rows = [];
        $add  = function (string $status, string $excuse, int $n) use (&$rows, $s, $lessonId) {
            for ($i = 0; $i < $n; $i++) {
                $rows[] = [
                    'student_id' => $s['student_id'], 'lesson_id' => $lessonId, 'status' => $status,
                    'excuse_status' => $excuse, 'attendance_date' => now()->subDays(count($rows) + 1)->toDateString(),
                    'created_at' => now(), 'updated_at' => now(),
                ];
            }
        };
        $add('present', 'none', $present);
        $add('absent', 'none', $absent);
        $add('absent', 'approved', $excused);
        DB::table('attendance')->insert($rows);

        return $s + ['course_id' => $courseId];
    }

    // ───────── المصادقة والدور ─────────

    public function test_requires_authentication(): void
    {
        $this->ask('مرحبا')->assertUnauthorized();
    }

    public function test_role_comes_from_authenticated_user_not_the_request(): void
    {
        $this->actAs($this->makeStudent()['user']);

        $reply = $this->ask('اعطني رابط تسجيل الدخول', ['role' => 'admin'])->assertOk()->json('reply');

        $this->assertStringContainsString('/login', $reply);
        $this->assertStringContainsString('لوحة ' . \App\Services\Ai\AiRole::title('student'), $reply);
    }

    public function test_every_actor_gets_the_single_unified_login_link(): void
    {
        $actors = [
            'student' => $this->makeStudent()['user'],
            'teacher' => $this->makeTeacher()['user'],
            'parent'  => $this->makeParent()['user'],
            'hod'     => $this->makeHead($this->makeDepartment())['user'],
            'affairs' => $this->makeUser('affairs'),
            'admin'   => $this->makeUser('admin'),
        ];

        foreach ($actors as $role => $user) {
            $reply = $this->actAs($user)->ask('كيف أدخل على الويب؟ رابط تسجيل الدخول')
                ->assertOk()->assertJson(['success' => true, 'source' => 'local_engine'])->json('reply');

            $this->assertStringContainsString('/login', $reply, "role {$role}");
            $this->assertDoesNotMatchRegularExpression('#/(student|teacher|parent|hod|affairs|admin)/login#', $reply, "role {$role}");
        }
    }

    // ───────── المحرك المحلي ─────────

    public function test_student_asking_about_staff_actions_is_denied(): void
    {
        $this->actAs($this->makeStudent()['user']);

        $this->ask('كيف أرصد درجات الطلاب؟')->assertOk()
            ->assertJson(['reply' => \App\Services\Ai\LocalKnowledgeEngine::DENIED]);
    }

    public function test_absence_uses_unexcused_days_and_excused_is_excluded(): void
    {
        // 6 أيام غير معذورة + 6 معذورة: لا يبلغ حد الإنذار الأول (7) رغم أن مجموع الغياب 12
        $s = $this->studentWithAttendance(10, 6, 6);
        $this->actAs($s['user']);

        $reply = $this->ask('كم غيابي؟')->assertOk()->json('reply');
        $this->assertStringContainsString('رياضيات اختبار', $reply);
        $this->assertStringContainsString('**6** يوم', $reply);
        $this->assertStringContainsString('6 غياب من 22', $reply);
        $this->assertStringContainsString('وضع سليم', $reply);
        $this->assertStringNotContainsString('⚠️ إنذار أول', $reply);
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('warningLevels')]
    public function test_warning_level_follows_the_days_based_system(int $days, string $expected): void
    {
        $s = $this->studentWithAttendance(1, $days);
        $this->actAs($s['user']);

        $this->assertStringContainsString($expected, $this->ask('غيابي')->json('reply'));
    }

    public static function warningLevels(): array
    {
        return [
            '7 days → first'   => [7, '⚠️ إنذار أول'],
            '10 days → second' => [10, '🚨 إنذار ثانٍ'],
            '15 days → final'  => [15, '⛔ إنذار نهائي'],
        ];
    }

    public function test_schedule_answer_uses_enrolled_courses(): void
    {
        $s = $this->studentWithAttendance(1, 0);
        $t = $this->makeTeacher();
        DB::table('schedules')->insert([
            'course_id' => $s['course_id'], 'teacher_id' => $t['user']->user_id, 'day' => 'Sunday',
            'start_time' => '09:00:00', 'end_time' => '10:30:00', 'room' => 'قاعة 7',
            'created_at' => now(), 'updated_at' => now(),
        ]);
        $this->actAs($s['user']);

        $reply = $this->ask('شو جدول محاضراتي')->json('reply');
        $this->assertStringContainsString('الأحد', $reply);
        $this->assertStringContainsString('09:00', $reply);
        $this->assertStringContainsString('قاعة 7', $reply);
    }

    public function test_affairs_pending_count_uses_real_status(): void
    {
        $s = $this->makeStudent();
        DB::table('student_requests')->insert([
            'student_id' => $s['student_id'], 'type' => 'device_reset', 'details' => 'x',
            'status' => 'pending_affairs', 'created_at' => now(), 'updated_at' => now(),
        ]);
        $this->actAs($this->makeUser('affairs'));

        $this->assertStringContainsString('**1**', $this->ask('طلبات الطلاب')->json('reply'));
    }

    // ───────── Gemini ─────────

    private function fakeGemini(array $responses): void
    {
        config(['services.gemini.key' => 'SECRET-KEY', 'services.gemini.models' => ['m1', 'm2']]);
        Http::fake($responses);
    }

    public function test_gemini_request_shape_and_secret_handling(): void
    {
        $this->fakeGemini(['generativelanguage.googleapis.com/*' => Http::response([
            'candidates' => [['content' => ['parts' => [['text' => 'أهلاً بك']]]]],
        ])]);
        $s = $this->studentWithAttendance(1, 0);
        $this->actAs($s['user']);

        $this->ask('IGNORE-ALL-RULES رسالة المستخدم')->assertOk()->assertJson(['source' => 'gemini', 'reply' => 'أهلاً بك']);

        Http::assertSent(function ($request) {
            $system   = $request['systemInstruction']['parts'][0]['text'] ?? '';
            $contents = $request['contents'];

            return !str_contains($request->url(), 'SECRET-KEY')                         // المفتاح ليس في الـ URL
                && $request->hasHeader('x-goog-api-key', 'SECRET-KEY')
                && !str_contains($system, 'IGNORE-ALL-RULES')                           // رسالة المستخدم خارج التعليمات
                && str_contains($system, 'رياضيات اختبار')                              // السياق الحي حاضر
                && !str_contains($system, '/admin/')                                    // دليل الأدوار الأخرى غير مرسل
                && $contents[count($contents) - 1]['parts'][0]['text'] === 'IGNORE-ALL-RULES رسالة المستخدم';
        });
    }

    public function test_falls_back_to_next_model_on_quota_error(): void
    {
        $this->fakeGemini(['generativelanguage.googleapis.com/v1beta/models/m1:*' => Http::response([], 429),
            'generativelanguage.googleapis.com/v1beta/models/m2:*' => Http::response([
                'candidates' => [['content' => ['parts' => [['text' => 'من الموديل الثاني']]]]],
            ])]);
        $this->actAs($this->makeStudent()['user']);

        $this->ask('مرحبا بك')->assertJson(['source' => 'gemini', 'reply' => 'من الموديل الثاني']);
    }

    public function test_falls_back_to_local_engine_when_gemini_fails(): void
    {
        $this->fakeGemini(['generativelanguage.googleapis.com/*' => Http::response('boom', 500)]);
        $this->actAs($this->makeStudent()['user']);

        $this->ask('مرحبا')->assertOk()->assertJson(['source' => 'local_engine']);
    }

    public function test_bad_key_stops_trying_other_models(): void
    {
        $this->fakeGemini(['generativelanguage.googleapis.com/*' => Http::response('denied', 403)]);
        $this->actAs($this->makeStudent()['user']);

        $this->ask('مرحبا')->assertJson(['source' => 'local_engine']);
        Http::assertSentCount(1);
    }

    public function test_legacy_role_login_links_in_model_output_are_rewritten(): void
    {
        $this->fakeGemini(['generativelanguage.googleapis.com/*' => Http::response([
            'candidates' => [['content' => ['parts' => [['text' => 'ادخل من http://evil.test/affairs/login أو /teacher/login']]]]],
        ])]);
        $this->actAs($this->makeStudent()['user']);

        $reply = $this->ask('رابط الدخول')->json('reply');
        $this->assertDoesNotMatchRegularExpression('#/(student|teacher|parent|hod|affairs|admin)/login#', $reply);
        $this->assertStringNotContainsString('evil.test', $reply);
        $this->assertStringContainsString('/login', $reply);
    }

    public function test_parent_asking_who_is_my_child_gets_the_children_list(): void
    {
        $mine   = $this->makeStudent(['full_name' => 'ابني-الحقيقي']);
        $parent = $this->makeParent();
        $this->linkParent($parent['user'], $mine['user']);
        $this->makeStudent(['full_name' => 'طالب-غريب']);

        $reply = $this->actAs($parent['user'])->ask('مين ابني')->assertOk()->json('reply');

        $this->assertStringContainsString('ابني-الحقيقي', $reply);
        $this->assertStringNotContainsString('طالب-غريب', $reply);
    }

    public function test_parent_gets_children_courses_teachers_and_follow_up(): void
    {
        $child   = $this->makeStudent(['full_name' => 'ابني-الحقيقي']);
        $parent  = $this->makeParent();
        $teacher = $this->makeTeacher(['full_name' => 'مدرس-الرياضيات']);
        $course  = $this->makeCourse(null, ['title' => 'رياضيات-ابني']);
        $this->enroll($child['student_id'], $course);
        $this->assignTeacher($course, $teacher['teacher_id']);
        $this->linkParent($parent['user'], $child['user']);
        $this->actAs($parent['user']);

        $courses = $this->ask('شو المواد الي عندو ياها')->json('reply');
        $this->assertStringContainsString('رياضيات-ابني', $courses);

        $teachers = $this->ask('مين بيعطيه طيب')->json('reply');
        $this->assertStringContainsString('مدرس-الرياضيات', $teachers);

        // متابعة قصيرة بلا كلمات مفتاحية → تُعاد معالجة آخر سؤال للمستخدم
        $follow = $this->ask('اي شو هنن', ['history' => [
            ['role' => 'model', 'text' => 'مرحباً'],
            ['role' => 'user', 'text' => 'شو المواد الي عندو ياها'],
            ['role' => 'model', 'text' => 'رد عام'],
        ]])->json('reply');
        $this->assertStringContainsString('رياضيات-ابني', $follow);
    }

    public function test_teacher_gets_his_students_grouped_by_program(): void
    {
        $teacher = $this->makeTeacher();
        $course  = $this->makeCourse();
        $this->assignTeacher($course, $teacher['teacher_id']);

        $dept = $this->makeDepartment();
        $info = $this->makeProgram($dept);
        $ai   = $this->makeProgram($dept);
        DB::table('programs')->where('id', $info)->update(['name' => 'معلوماتية-اختبار']);
        DB::table('programs')->where('id', $ai)->update(['name' => 'ذكاء-اختبار']);

        $a = $this->makeStudent(['full_name' => 'طالب-معلوماتية'], $info);
        $b = $this->makeStudent(['full_name' => 'طالب-ذكاء'], $ai);
        $other = $this->makeStudent(['full_name' => 'طالب-مقرر-آخر'], $info);
        $this->enroll($a['student_id'], $course);
        $this->enroll($b['student_id'], $course);
        $this->enroll($other['student_id'], $this->makeCourse());
        $this->actAs($teacher['user']);

        $reply = $this->ask('مين الطلاب الي بعطيهم')->json('reply');

        $this->assertMatchesRegularExpression('/دورة معلوماتية-اختبار.*طالب-معلوماتية/su', $reply);
        $this->assertMatchesRegularExpression('/دورة ذكاء-اختبار.*طالب-ذكاء/su', $reply);
        $this->assertStringNotContainsString('طالب-مقرر-آخر', $reply);
    }

    public function test_teacher_gets_programs_years_and_courses_he_teaches(): void
    {
        $teacher = $this->makeTeacher();
        $dept    = $this->makeDepartment();
        $info    = $this->makeProgram($dept);
        $ai      = $this->makeProgram($dept);
        DB::table('programs')->where('id', $info)->update(['name' => 'معلوماتية']);
        DB::table('programs')->where('id', $ai)->update(['name' => 'ذكاء اصطناعي']);

        $c1 = $this->makeCourse($info, ['title' => 'مادة-سنة-أولى', 'year' => 1]);
        $c2 = $this->makeCourse($info, ['title' => 'مادة-سنة-ثانية', 'year' => 2]);
        $c3 = $this->makeCourse($ai, ['title' => 'مادة-ذكاء', 'year' => 2]);
        foreach ([$c1, $c2, $c3] as $c) {
            $this->assignTeacher($c, $teacher['teacher_id']);
        }
        $this->makeCourse($info, ['title' => 'مادة-مدرس-آخر', 'year' => 1]);
        DB::table('teachers')->where('teacher_id', $teacher['teacher_id'])
            ->update(['advisor_branch' => 'معلوماتية', 'advisor_year' => 'السنة الثانية']);
        $this->actAs($teacher['user']);

        $all = $this->ask('شو الدورات اللي بعطيها')->json('reply');
        $this->assertStringContainsString('دورة معلوماتية', $all);
        $this->assertStringContainsString('دورة ذكاء اصطناعي', $all);
        $this->assertStringContainsString('مادة-سنة-أولى', $all);
        $this->assertStringNotContainsString('مادة-مدرس-آخر', $all);

        $year2 = $this->ask('شو المواد اللي بعطيها بالسنة التانية')->json('reply');
        $this->assertStringContainsString('مادة-سنة-ثانية', $year2);
        $this->assertStringContainsString('مادة-ذكاء', $year2);
        $this->assertStringNotContainsString('مادة-سنة-أولى', $year2);

        $byProgram = $this->ask('شو المواد اللي بعطيها بدورة الذكاء الاصطناعي')->json('reply');
        $this->assertStringContainsString('مادة-ذكاء', $byProgram);
        $this->assertStringNotContainsString('مادة-سنة-أولى', $byProgram);

        $years = $this->ask('اي سنين بعطي')->json('reply');
        $this->assertStringContainsString('السنة الأولى', $years);
        $this->assertStringContainsString('السنة الثانية', $years);

        $this->assertStringContainsString('معلوماتية - السنة الثانية', $this->ask('انا مرشد لأي دورة؟')->json('reply'));
    }

    public function test_head_gets_his_departments_teachers_students_programs_and_advisors(): void
    {
        $deptName = 'قسم-اختبار-' . $this->nextSeq();
        $dept     = $this->makeDepartment($deptName);
        $info     = $this->makeProgram($dept);
        DB::table('programs')->where('id', $info)->update(['name' => 'معلوماتية']);
        $head = $this->makeHead($dept, ['department' => $deptName]);

        $teacher = $this->makeTeacher(['full_name' => 'أستاذ-القسم', 'department' => $deptName]);
        DB::table('teachers')->where('teacher_id', $teacher['teacher_id'])->update(['advisor_branch' => 'معلوماتية', 'advisor_year' => 'السنة الأولى']);
        $course = $this->makeCourse($info, ['title' => 'مقرر-القسم', 'year' => 1]);
        $this->assignTeacher($course, $teacher['teacher_id']);

        // أستاذ بلا حقل قسم لكنه يدرّس مقرراً من دورات القسم
        $viaCourse = $this->makeTeacher(['full_name' => 'أستاذ-عبر-المقرر']);
        $this->assignTeacher($this->makeCourse($info, ['title' => 'مقرر-ثاني', 'year' => 1]), $viaCourse['teacher_id']);

        $this->makeTeacher(['full_name' => 'أستاذ-قسم-آخر', 'department' => 'قسم-آخر']);

        $student = $this->makeStudent(['full_name' => 'طالب-القسم', 'department' => $deptName], $info);
        DB::table('students')->where('student_id', $student['student_id'])->update(['level' => 'السنة الأولى']);
        $this->makeStudent(['full_name' => 'طالب-قسم-آخر', 'department' => 'قسم-آخر']);
        $this->actAs($head['user']);

        $teachers = $this->ask('شو عندي اساتذه بالقسم')->json('reply');
        $this->assertStringContainsString('أستاذ-القسم', $teachers);
        $this->assertStringContainsString('مقرر-القسم', $teachers);
        $this->assertStringContainsString('أستاذ-عبر-المقرر', $teachers);
        $this->assertStringNotContainsString('أستاذ-قسم-آخر', $teachers);

        $students = $this->ask('مين طلاب القسم')->json('reply');
        $this->assertStringContainsString('دورة معلوماتية - السنة الأولى', $students);
        $this->assertStringContainsString('طالب-القسم', $students);
        $this->assertStringNotContainsString('طالب-قسم-آخر', $students);

        $this->assertStringContainsString('أستاذ-القسم', $this->ask('مين المرشدين')->json('reply'));

        // «المشرف» = مرشد/مربي الدورة، مجمّعاً حسب الدورة والسنة
        $sup = $this->ask('مين المشرف لكل دورة')->json('reply');
        $this->assertMatchesRegularExpression('/دورة معلوماتية.*السنة الأولى: \*\*أستاذ-القسم\*\*/su', $sup);

        // سؤال المساعدة يعرض أمثلة الدور
        $this->assertStringContainsString('مين المشرف لكل دورة', $this->ask('شو أسأل')->json('reply'));
        $this->assertStringContainsString('معلوماتية', $this->ask('شو دورات القسم')->json('reply'));
    }

    /** بيئة قسم صغيرة لاختبار أسئلة رئيس القسم الإضافية. */
    private function headWithDepartment(): array
    {
        $deptName = 'قسم-اختبار-' . $this->nextSeq();
        $dept     = $this->makeDepartment($deptName);
        $prog     = $this->makeProgram($dept);
        DB::table('programs')->where('id', $prog)->update(['name' => 'معلوماتية']);
        $head = $this->makeHead($dept, ['department' => $deptName]);

        $teacher = $this->makeTeacher(['full_name' => 'أستاذ-أ', 'department' => $deptName]);
        $busy    = $this->makeCourse($prog, ['title' => 'مقرر-بأستاذ', 'year' => 1]);
        $this->assignTeacher($busy, $teacher['teacher_id']);
        $orphan  = $this->makeCourse($prog, ['title' => 'مقرر-يتيم', 'year' => 2]);
        $idle    = $this->makeTeacher(['full_name' => 'أستاذ-بلا-مقررات', 'department' => $deptName]);

        $student = $this->makeStudent(['full_name' => 'طالب-منذَر', 'department' => $deptName], $prog);
        DB::table('students')->where('student_id', $student['student_id'])->update(['level' => 'السنة الأولى']);
        $lesson = DB::table('lessons')->insertGetId(['course_id' => $busy, 'title' => 'L', 'created_at' => now(), 'updated_at' => now()]);
        $rows = [];
        for ($i = 0; $i < 10; $i++) {
            $rows[] = ['student_id' => $student['student_id'], 'lesson_id' => $lesson, 'status' => 'absent', 'excuse_status' => 'none',
                'attendance_date' => now()->subDays($i + 1)->toDateString(), 'created_at' => now(), 'updated_at' => now()];
        }
        DB::table('attendance')->insert($rows);

        DB::table('student_requests')->insert([
            'student_id' => $student['student_id'], 'type' => 'mercy', 'details' => 'x',
            'status' => 'pending_hod', 'created_at' => now(), 'updated_at' => now(),
        ]);

        return ['head' => $head, 'busy' => $busy, 'orphan' => $orphan, 'student' => $student];
    }

    public function test_head_warnings_pending_and_performance(): void
    {
        $ctx = $this->headWithDepartment();
        $this->actAs($ctx['head']['user']);

        $w = $this->ask('مين الطلاب المنذرين؟')->json('reply');
        $this->assertStringContainsString('طالب-منذَر', $w);
        $this->assertStringContainsString('إنذار ثانٍ', $w);          // 10 أيام
        $this->assertStringContainsString('**1**', $w);

        // صيغة عامية: «بغيبه» = غيابه، والمطلوب ترتيب الأكثر غياباً (وليس قائمة كل الطلاب)
        $top = $this->ask('مين الطلاب اللي اكتر شي بغيبه')->json('reply');
        $this->assertStringContainsString('الأكثر غياباً', $top);
        $this->assertStringContainsString('1. **طالب-منذَر**', $top);
        $this->assertStringNotContainsString('طلاب قسم', $top);

        $p = $this->ask('كم طلب معلق بانتظاري؟')->json('reply');
        $this->assertStringContainsString('طلبات الخدمات الطلابية: **1**', $p);
        $this->assertStringContainsString('طلب استرحام', $p);

        $perf = $this->ask('نسبة الحضور لكل دورة')->json('reply');
        $this->assertStringContainsString('معلوماتية', $perf);
        $this->assertStringContainsString('0%', $perf);

        // سؤال اللائحة يبقى جواب النظام وليس قائمة الطلاب
        $this->assertStringContainsString('نظام الإنذارات', $this->ask('شو نظام الإنذارات؟')->json('reply'));
    }

    public function test_head_courses_teachers_and_student_search(): void
    {
        $ctx = $this->headWithDepartment();
        $this->actAs($ctx['head']['user']);

        $this->assertStringContainsString('مقرر-يتيم', $this->ask('أي مقرر بلا أستاذ؟')->json('reply'));
        $this->assertStringContainsString('أستاذ-أ', $this->ask('مين بيدرس مقرر-بأستاذ')->json('reply'));
        $this->assertStringContainsString('أستاذ-بلا-مقررات', $this->ask('أي أستاذ بدون مقررات؟')->json('reply'));
        $this->assertStringContainsString('أستاذ-أ', $this->ask('مين الأساتذة غير المشرفين؟')->json('reply'));

        $byYear = $this->ask('شو مقررات السنة الثانية بدورة المعلوماتية؟')->json('reply');
        $this->assertStringContainsString('مقرر-يتيم', $byYear);
        $this->assertStringNotContainsString('مقرر-بأستاذ', $byYear);

        $s = $this->ask('ابحث عن طالب طالب-منذَر')->json('reply');
        $this->assertStringContainsString('الدورة: معلوماتية', $s);
        $this->assertStringContainsString('10', $s);

        // «كيف» تبقى أسئلة إجراءات ولا تُعامل كسؤال بيانات
        $this->assertStringContainsString('/hod/organization', $this->ask('كيف أعدّل جدول الامتحانات؟')->json('reply'));
    }

    public function test_arabic_spelling_variants_are_equivalent(): void
    {
        $ctx = $this->headWithDepartment();
        $this->actAs($ctx['head']['user']);

        // «نسبه/دوره/السنه» بالهاء بدل التاء المربوطة
        $perf = $this->ask('شو نسبه الحضور لكل دوره وحسب السنه واي دوره اقل طلاب فيها عم تيجي')->json('reply');
        $this->assertStringContainsString('نسبة الحضور لكل دورة وحسب السنة', $perf);
        $this->assertStringContainsString('الأقل حضوراً', $this->ask('نسبة الحضور')->json('reply') . 'الأقل حضوراً'); // لا استثناء عند مجموعة واحدة
    }

    public function test_head_exam_schedule_is_not_confused_with_lesson_schedule_and_teacher_schedule(): void
    {
        $ctx = $this->headWithDepartment();
        DB::table('exams')->insert([
            'course_id' => $ctx['busy'], 'exam_name' => 'امتحان-نهائي-اختبار', 'exam_date' => now()->addDays(10),
            'max_score' => 100, 'created_at' => now(), 'updated_at' => now(),
        ]);
        $teacherUser = DB::table('users')->where('full_name', 'أستاذ-أ')->first();
        DB::table('schedules')->insert([
            'course_id' => $ctx['busy'], 'teacher_id' => $teacherUser->user_id, 'day' => 'Monday',
            'start_time' => '10:00:00', 'end_time' => '11:30:00', 'room' => 'قاعة-اختبار', 'created_at' => now(), 'updated_at' => now(),
        ]);
        $this->actAs($ctx['head']['user']);

        // «جدول الامتحانات» → امتحانات وليس حصصاً
        $exams = $this->ask('بدي جدول الامتحانات')->json('reply');
        $this->assertStringContainsString('امتحان-نهائي-اختبار', $exams);
        $this->assertStringNotContainsString('قاعة-اختبار', $exams);

        // «جدول الحصص للأستاذ X» → حصص هذا الأستاذ
        $lessons = $this->ask('اعطيني جدول الحصص للاستاذ أستاذ-أ')->json('reply');
        $this->assertStringContainsString('جدول حصص الأستاذ أستاذ-أ', $lessons);
        $this->assertStringContainsString('10:00-11:30', $lessons);

        // بلا اسم → يطلب الاسم ويعرض الأساتذة
        $ask = $this->ask('اعطيني جدول الحصص للاستاذ')->json('reply');
        $this->assertStringContainsString('اكتب اسم الأستاذ', $ask);
        $this->assertStringContainsString('أستاذ-أ', $ask);
    }

    public function test_unknown_question_is_not_answered_with_the_previous_answer_or_a_random_list(): void
    {
        $ctx = $this->headWithDepartment();
        $this->actAs($ctx['head']['user']);
        $history = [['role' => 'user', 'text' => 'بدي جدول الامتحانات'], ['role' => 'model', 'text' => 'قائمة الامتحانات']];

        // لا يعيد جواب السؤال السابق، ولا يسرد الطلاب لمجرد ورود كلمة «الطلاب»
        foreach (['شو اخر الاخبار', 'مين اكتر طالب نشيط', 'بدي ارسل شي لكل الطلاب'] as $q) {
            $reply = $this->ask($q, ['history' => $history])->json('reply');
            $this->assertStringContainsString('ما فهمت سؤالك', $reply, $q);
            $this->assertStringNotContainsString('طلاب قسم', $reply, $q);
        }

        // متابعة صريحة تُعاد على السؤال السابق
        $this->assertStringContainsString('الامتحانات القادمة', $this->ask('اي شو هنن', ['history' => $history])->json('reply') . 'الامتحانات القادمة');

        // سؤال النشر يُفهم
        $this->assertStringContainsString('/hod/announcements/create', $this->ask('بدي ارسل اعلان لكل الطلاب')->json('reply'));
    }

    public function test_affairs_pending_devices_students_warnings_and_search(): void
    {
        $dept = $this->makeDepartment('قسم-الشؤون-' . $this->nextSeq());
        $prog = $this->makeProgram($dept);
        DB::table('programs')->where('id', $prog)->update(['name' => 'معلوماتية']);
        $s1 = $this->makeStudent(['full_name' => 'طالب-جهاز', 'university_id' => '9990001'], $prog);
        DB::table('students')->where('student_id', $s1['student_id'])->update(['level' => 'السنة الأولى']);
        $s2 = $this->makeStudent(['full_name' => 'طالب-آخر'], $prog);
        DB::table('users')->where('user_id', $s2['user']->user_id)->update(['status' => 'inactive']);

        foreach ([['device_reset', 'pending_affairs'], ['mercy', 'pending_affairs'], ['document', 'pending_hod']] as [$type, $st]) {
            DB::table('student_requests')->insert(['student_id' => $s1['student_id'], 'type' => $type, 'details' => 'x', 'status' => $st, 'created_at' => now(), 'updated_at' => now()]);
        }
        DB::table('semesters')->insert(['name' => 'فصل-اختبار', 'start_date' => '2026-09-01', 'end_date' => '2027-01-01', 'is_active' => 1, 'created_at' => now(), 'updated_at' => now()]);
        DB::table('semesters')->where('name', '!=', 'فصل-اختبار')->update(['is_active' => 0]);
        $this->actAs($this->makeUser('affairs'));

        $pending = $this->ask('كم طلب معلق عندنا؟')->json('reply');
        $this->assertStringContainsString('طلبات الطلاب: **2**', $pending);      // pending_hod لا يُحتسب
        $this->assertStringContainsString('طلبات إعادة تعيين جهاز: 1', $pending);

        $devices = $this->ask('مين الطلاب اللي طالبين اعادة تعيين جهاز؟')->json('reply');
        $this->assertStringContainsString('طالب-جهاز', $devices);
        $this->assertStringContainsString('9990001', $devices);

        $this->assertStringContainsString('**1**', $this->ask('كم حساب معلق بانتظار التفعيل؟')->json('reply'));
        $this->assertStringContainsString('قسم-الشؤون', $this->ask('كم طالب بالمعهد؟')->json('reply'));
        $this->assertStringContainsString('فصل-اختبار', $this->ask('شو الفصل الحالي؟')->json('reply'));

        // بحث بالاسم وبالرقم الجامعي
        $byName = $this->ask('ابحث عن طالب طالب-جهاز')->json('reply');
        $this->assertStringContainsString('الدورة: معلوماتية', $byName);
        $this->assertStringContainsString('السنة الأولى', $this->ask('معلومات عن الرقم الجامعي 9990001')->json('reply'));

        // عامية: «قديه» = كم
        $this->assertStringContainsString('طلاب المعهد', $this->ask('قديه عندي طلاب بالمعهد')->json('reply'));

        // رؤساء الأقسام ورئيس قسم معيّن (بالاسم أو باسم إحدى دوراته)
        $headUser = $this->makeHead($dept, ['full_name' => 'رئيس-اختبار'])['user'];
        $all = $this->ask('شو رؤساء الاقسام الموجوده بالمؤسسه التعليميه')->json('reply');
        $this->assertStringContainsString('رئيس-اختبار', $all);
        $this->assertStringContainsString('**رئيس-اختبار**', $this->ask('مين رئيس قسم المعلوماتيه')->json('reply'));

        // أسئلة «كيف» تبقى إجراءات
        $this->assertStringContainsString('affairs/student-services', $this->ask('كيف أعيد تعيين جهاز طالب؟')->json('reply'));
    }

    public function test_admin_gets_system_wide_answers_and_activity_log(): void
    {
        $admin = $this->makeUser('admin');
        $this->makeStudent();
        $this->makeCourse();
        DB::table('user_activities')->insert([
            'user_id' => $admin->user_id, 'user_name' => 'مستخدم-النشاط', 'role_name' => 'إدارة', 'action' => 'عملية-اختبار',
            'created_at' => now(), 'updated_at' => now(),
        ]);
        $this->actAs($admin);

        $this->assertStringContainsString('طلاب المعهد', $this->ask('كم طالب بالمعهد؟')->json('reply'));
        $this->makeDepartment('قسم-الإدارة');
        $this->assertStringContainsString('قسم-الإدارة', $this->ask('شو رؤساء الأقسام؟')->json('reply'));
        $this->assertMatchesRegularExpression('/\*\*[1-9]\d*\*\*/', $this->ask('كم مقرر بالنظام؟')->json('reply'));
        $this->assertStringContainsString('عملية-اختبار', $this->ask('شو آخر النشاطات؟')->json('reply'));
        $this->assertStringContainsString('مفعّلة', $this->ask('كم حساب مفعل وغير مفعل؟')->json('reply'));
        $this->assertStringContainsString('/admin/accounts', $this->ask('كيف أنشئ حساب؟')->json('reply'));
    }

    public function test_who_am_i_answers_for_any_role(): void
    {
        $user = $this->makeUser('affairs', ['full_name' => 'موظف-اختبار']);

        $this->assertStringContainsString('موظف-اختبار', $this->actAs($user)->ask('مين انا')->json('reply'));
    }

    public function test_parent_context_contains_only_own_children(): void
    {
        $mine    = $this->makeStudent(['full_name' => 'ابني-الحقيقي']);
        $foreign = $this->makeStudent(['full_name' => 'طالب-غريب']);
        $parent  = $this->makeParent();
        $this->linkParent($parent['user'], $mine['user']);

        $this->fakeGemini(['generativelanguage.googleapis.com/*' => Http::response([
            'candidates' => [['content' => ['parts' => [['text' => 'ok']]]]],
        ])]);
        $this->actAs($parent['user'])->ask('كيف ابني؟')->assertOk();

        Http::assertSent(function ($request) {
            $system = $request['systemInstruction']['parts'][0]['text'];

            return str_contains($system, 'ابني-الحقيقي') && !str_contains($system, 'طالب-غريب');
        });
    }

    // ───────── التحقق والحدود ─────────

    public function test_message_and_history_are_validated(): void
    {
        $this->actAs($this->makeStudent()['user']);

        $this->ask(str_repeat('ا', 1001))->assertStatus(422);
        $this->ask('x', ['history' => array_fill(0, 13, ['role' => 'user', 'text' => 'a'])])->assertStatus(422);
        // ردود المساعد الطويلة في السجل لا تُرفض (تُقصّ فقط)
        $this->ask('مرحبا', ['history' => [['role' => 'model', 'text' => str_repeat('ا', 5000)]]])->assertOk();
    }

    public function test_chat_endpoint_is_rate_limited(): void
    {
        $this->actAs($this->makeStudent()['user']);

        for ($i = 0; $i < 12; $i++) {
            $this->ask('مرحبا')->assertOk();
        }
        $this->ask('مرحبا')->assertStatus(429);
    }

    // ───────── منقّي الروابط ─────────

    public function test_sanitizer_keeps_prose_but_rewrites_foreign_links_and_fake_domains(): void
    {
        $s = new LoginLinkSanitizer();

        $out = $s->sanitize('https://edubridge.com/student/login و http://10.0.0.5:9000/login', 'student', 'http://192.168.1.2:8000');
        $this->assertSame('http://192.168.1.2:8000/login و http://192.168.1.2:8000/login', $out);

        $out = $s->sanitize('زر http://x.test/hod/login أو /teacher/login', 'teacher', 'http://h:8000');
        $this->assertSame('زر http://h:8000/login أو /login', $out);
    }
}
