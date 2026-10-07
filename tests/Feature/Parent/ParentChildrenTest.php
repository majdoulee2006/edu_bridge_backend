<?php

namespace Tests\Feature\Parent;

use Tests\Concerns\MakesAcademicData;
use Tests\TestCase;

class ParentChildrenTest extends TestCase
{
    use MakesAcademicData;

    public function test_parent_sees_own_children_with_and_without_parent_id_in_the_url(): void
    {
        $child  = $this->makeStudent(['full_name' => 'ابني-الحقيقي']);
        $parent = $this->makeParent();
        $this->linkParent($parent['user'], $child['user']);
        $this->actAs($parent['user']);

        foreach (['/api/parent/children', "/api/parent/children/{$parent['parent_id']}"] as $url) {
            $this->getJson($url)->assertOk()
                ->assertJsonPath('success', true)
                ->assertJsonPath('data.0.full_name', 'ابني-الحقيقي');
        }
    }

    public function test_parent_cannot_list_another_parents_children(): void
    {
        $other = $this->makeParent();
        $this->actAs($this->makeParent()['user']);

        $this->getJson("/api/parent/children/{$other['parent_id']}")->assertForbidden();
    }
}
