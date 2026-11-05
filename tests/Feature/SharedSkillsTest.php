<?php

namespace Tests\Feature;

use App\Models\JobSeeker;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SharedSkillsTest extends TestCase
{
    use RefreshDatabase;

    public function test_job_seeker_can_add_a_private_skill_that_other_profiles_cannot_see_or_select(): void
    {
        $user = User::create([
            'name' => 'Candidate',
            'email' => 'candidate@example.test',
            'password' => 'password123',
            'role' => 'job_seeker',
            'status' => true,
        ]);
        $jobSeeker = JobSeeker::create(['user_id' => $user->id, 'status' => true]);

        $response = $this->actingAs($user)->postJson('/api/skills', [
            'name' => '  Specialist Calibration Workflow  ',
            'type' => 'hard',
        ])->assertCreated()->assertJsonPath('skill.name', 'Specialist Calibration Workflow')->assertJsonPath('skill.type', 'hard');

        $this->getJson('/api/skills')->assertOk()->assertJsonFragment([
            'id' => $response->json('skill.id'),
            'name' => 'Specialist Calibration Workflow',
            'type' => 'hard',
        ])->assertJsonFragment(['name' => 'AI Literacy', 'type' => 'hard'])
            ->assertJsonFragment(['name' => 'Kubernetes', 'type' => 'hard'])
            ->assertJsonFragment(['name' => 'Cisco IOS', 'type' => 'hard'])
            ->assertJsonFragment(['name' => 'PC Assembly and Repair', 'type' => 'hard']);
        $this->assertDatabaseHas('skills', [
            'id' => $response->json('skill.id'),
            'created_by_job_seeker_id' => $jobSeeker->id,
            'is_builtin' => false,
        ]);

        $otherUser = User::create([
            'name' => 'Other Candidate',
            'email' => 'other@example.test',
            'password' => 'password123',
            'role' => 'job_seeker',
            'status' => true,
        ]);
        JobSeeker::create(['user_id' => $otherUser->id, 'status' => true]);
        $this->actingAs($otherUser)->getJson('/api/skills')->assertOk()->assertJsonMissingExact([
            'id' => $response->json('skill.id'),
            'name' => 'Specialist Calibration Workflow',
            'type' => 'hard',
        ]);
        $this->postJson('/profile', [
            '_method' => 'PATCH',
            'name' => $otherUser->name,
            'email' => $otherUser->email,
            'skills_catalog_submitted' => '1',
            'hard_skill_ids' => [$response->json('skill.id')],
        ])->assertUnprocessable()->assertJsonValidationErrors(['hard_skill_ids.0']);
    }

    public function test_non_job_seekers_cannot_add_skills_and_invalid_types_are_rejected(): void
    {
        $employer = User::create([
            'name' => 'Employer',
            'email' => 'employer@example.test',
            'password' => 'password123',
            'role' => 'employer',
            'status' => true,
        ]);
        $this->actingAs($employer)->postJson('/api/skills', ['name' => 'Negotiation', 'type' => 'soft'])->assertForbidden();

        $candidate = User::create([
            'name' => 'Candidate',
            'email' => 'candidate@example.test',
            'password' => 'password123',
            'role' => 'job_seeker',
            'status' => true,
        ]);
        JobSeeker::create(['user_id' => $candidate->id, 'status' => true]);
        $this->actingAs($candidate)->postJson('/api/skills', ['name' => 'Negotiation', 'type' => 'unclassified'])
            ->assertUnprocessable()->assertJsonValidationErrors(['type']);
    }
}
