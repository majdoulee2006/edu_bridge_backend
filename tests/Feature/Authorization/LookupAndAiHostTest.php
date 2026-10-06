<?php

namespace Tests\Feature\Authorization;

use App\Http\Controllers\Api\AiAssistantController;
use Illuminate\Http\Request;
use ReflectionMethod;
use Tests\Concerns\MakesAcademicData;
use Tests\TestCase;

/**
 * B-01: بحث الطالب للموظفين على الويب (بنطاق الدور).  S-19: server_url في المساعد الذكي.
 */
class LookupAndAiHostTest extends TestCase
{
    use MakesAcademicData;

    // ── بحث الطالب بالرقم الجامعي ───────────────────────────────

    public function test_admin_and_affairs_can_look_up_any_student(): void
    {
        $student = $this->makeStudent(['university_id' => 'U' . $this->nextSeq()]);

        foreach (['admin', 'affairs'] as $role) {
            $this->actingAs($this->makeUser($role))
                ->getJson('/staff/student-lookup?uid=' . $student['user']->university_id)
                ->assertOk()
                ->assertJson(['full_name' => $student['user']->full_name]);
        }
    }

    public function test_head_sees_only_own_department_students(): void
    {
        $deptA = 'Lookup A ' . $this->nextSeq();
        $deptB = 'Lookup B ' . $this->nextSeq();
        $head  = $this->makeHead($this->makeDepartment($deptA), ['department' => $deptA]);

        $mine    = $this->makeStudent(['university_id' => 'M' . $this->nextSeq(), 'department' => $deptA]);
        $foreign = $this->makeStudent(['university_id' => 'F' . $this->nextSeq(), 'department' => $deptB]);

        $this->actingAs($head['user']);
        $this->getJson('/staff/student-lookup?uid=' . $mine['user']->university_id)->assertJson(['full_name' => $mine['user']->full_name]);
        $this->getJson('/staff/student-lookup?uid=' . $foreign['user']->university_id)->assertExactJson([]);
    }

    public function test_students_parents_and_guests_cannot_use_lookup(): void
    {
        $target = $this->makeStudent(['university_id' => 'T' . $this->nextSeq()]);
        $url = '/staff/student-lookup?uid=' . $target['user']->university_id;

        $this->actingAs($this->makeStudent()['user'])->getJson($url)->assertForbidden();
        $this->actingAs($this->makeParent()['user'])->getJson($url)->assertForbidden();
        auth()->logout();
        $this->getJson($url)->assertUnauthorized();
    }

    // ── المساعد الذكي: server_url ───────────────────────────────

    private function resolve(string $serverUrl, string $requestHost = 'edu.example.org'): string
    {
        $request = Request::create("https://{$requestHost}/api/ai/chat", 'POST', ['server_url' => $serverUrl]);
        $m = new ReflectionMethod(AiAssistantController::class, 'resolveServerBaseUrl');
        $m->setAccessible(true);

        return $m->invoke(app(AiAssistantController::class), $request);
    }

    public function test_untrusted_server_url_is_ignored(): void
    {
        $this->assertStringNotContainsString('evil.example', $this->resolve('https://evil.example/phish'));
    }

    public function test_lan_ip_and_same_host_server_url_are_accepted(): void
    {
        $this->assertSame('http://192.168.1.20:8000', $this->resolve('http://192.168.1.20:8000/anything'));
        $this->assertSame('https://edu.example.org', $this->resolve('https://edu.example.org/x'));
    }

    public function test_explicitly_allowed_host_is_accepted(): void
    {
        config(['app.ai_allowed_hosts' => 'campus.example.com, other.example.com']);

        $this->assertSame('https://campus.example.com', $this->resolve('https://campus.example.com/path'));
    }
}
