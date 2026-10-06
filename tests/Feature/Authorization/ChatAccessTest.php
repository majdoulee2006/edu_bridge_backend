<?php

namespace Tests\Feature\Authorization;

use Illuminate\Support\Facades\DB;
use Tests\Concerns\MakesAcademicData;
use Tests\TestCase;

/**
 * الدردشة: مصفوفة "من يراسل من" تنطبق على الرسائل المباشرة وعلى المجموعات،
 * ولا يقرأ الرسائل إلا أطرافها.
 */
class ChatAccessTest extends TestCase
{
    use MakesAcademicData;

    public function test_student_cannot_message_parent(): void
    {
        $student = $this->makeStudent();
        $parent  = $this->makeParent();

        $this->actAs($student['user'])
            ->postJson('/api/send-message', ['receiver_id' => $parent['user']->user_id, 'message' => 'hi'])
            ->assertForbidden();
    }

    public function test_student_cannot_bypass_matrix_by_creating_group_with_parent(): void
    {
        $student = $this->makeStudent();
        $parent  = $this->makeParent();

        $this->actAs($student['user'])
            ->postJson('/api/groups', ['name' => 'g', 'user_ids' => [$parent['user']->user_id]])
            ->assertForbidden();

        $this->assertSame(0, DB::table('groups')->count());
    }

    public function test_teacher_can_create_group_with_students(): void
    {
        $teacher = $this->makeTeacher();
        $s1 = $this->makeStudent();
        $s2 = $this->makeStudent();

        $this->actAs($teacher['user'])
            ->postJson('/api/groups', ['name' => 'class', 'user_ids' => [$s1['user']->user_id, $s2['user']->user_id]])
            ->assertCreated();
    }

    public function test_third_party_cannot_read_a_conversation_attachment_or_group(): void
    {
        $teacher  = $this->makeTeacher();
        $student  = $this->makeStudent();
        $outsider = $this->makeStudent();

        $messageId = DB::table('messages')->insertGetId([
            'sender_id' => $teacher['user']->user_id, 'receiver_id' => $student['user']->user_id,
            'message' => 'private', 'attachment' => 'http://x/storage/chat_attachments/none.pdf',
            'is_read' => 0, 'created_at' => now(), 'updated_at' => now(),
        ]);

        $this->actAs($outsider['user'])
            ->getJson("/api/messages/$messageId/download")
            ->assertForbidden();

        $groupId = DB::table('groups')->insertGetId(['name' => 'private', 'created_at' => now(), 'updated_at' => now()]);
        $this->actAs($outsider['user'])->getJson("/api/groups/$groupId/messages")->assertForbidden();
    }
}
