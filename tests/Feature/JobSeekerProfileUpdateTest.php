<?php

namespace Tests\Feature;

use App\Models\JobSeeker;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class JobSeekerProfileUpdateTest extends TestCase
{
    use RefreshDatabase;

    public function test_job_seeker_can_save_profile_fields_and_collections_through_json(): void
    {
        $user = User::create([
            'name' => 'Original Name',
            'email' => 'original@example.test',
            'password' => 'password123',
            'role' => 'job_seeker',
            'status' => true,
        ]);
        $seeker = JobSeeker::create(['user_id' => $user->id, 'status' => true]);

        $this->actingAs($user)->postJson('/profile', [
            '_method' => 'PATCH',
            'name' => 'Updated Name',
            'email' => 'updated@example.test',
            'phone' => '09123456789',
            'skills_text' => 'Laravel, Vue.js',
            'languages_text' => 'Burmese, English',
            'job_alerts_enabled' => '1',
            'profile_searchable' => '0',
            'experiences' => [[
                'job_title' => 'Developer',
                'employer_name' => 'Example Company',
                'is_current' => '1',
            ]],
            'educations' => [[
                'institution' => 'Example University',
                'qualification' => 'BSc Computer Science',
            ]],
        ])->assertOk()
            ->assertJsonPath('message', 'Your profile has been saved.')
            ->assertJsonPath('profile.user.name', 'Updated Name')
            ->assertJsonPath('profile.phone', '09123456789')
            ->assertJsonPath('profile.skills.0', 'Laravel')
            ->assertJsonPath('profile.experiences.0.job_title', 'Developer')
            ->assertJsonPath('profile.educations.0.institution', 'Example University');

        $this->assertDatabaseHas('users', ['id' => $user->id, 'name' => 'Updated Name', 'email' => 'updated@example.test']);
        $this->assertDatabaseHas('job_seekers', ['id' => $seeker->id, 'phone' => '09123456789', 'job_alerts_enabled' => true]);
        $this->assertDatabaseHas('job_seeker_experiences', ['job_seeker_id' => $seeker->id, 'job_title' => 'Developer']);
        $this->assertDatabaseHas('job_seeker_educations', ['job_seeker_id' => $seeker->id, 'institution' => 'Example University']);
    }

    public function test_profile_update_returns_field_validation_errors_as_json(): void
    {
        $user = User::create([
            'name' => 'Job Seeker',
            'email' => 'seeker@example.test',
            'password' => 'password123',
            'role' => 'job_seeker',
            'status' => true,
        ]);
        JobSeeker::create(['user_id' => $user->id, 'status' => true]);

        $this->actingAs($user)->postJson('/profile', [
            '_method' => 'PATCH',
            'name' => '',
            'email' => 'invalid-email',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors(['name', 'email']);
    }
}
