<?php

namespace Tests\Feature;

use App\Models\Employer;
use App\Models\JobSeeker;
use App\Models\User;
use Database\Seeders\BillingCatalogSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class EmployerTalentSearchTest extends TestCase
{
    use RefreshDatabase;

    private User $employerUser;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(BillingCatalogSeeder::class);
        $this->employerUser = User::create([
            'name' => 'Talent Search Employer',
            'email' => 'talent-employer@example.test',
            'password' => 'password123',
            'role' => 'employer',
            'status' => true,
        ]);
        Employer::create(['user_id' => $this->employerUser->id, 'company_name' => 'Talent Search Co']);
    }

    public function test_search_returns_persistent_opted_in_candidates_without_preview_limits_or_usage_charges(): void
    {
        foreach (range(1, 32) as $number) {
            $user = User::create([
                'name' => "Discoverable Candidate {$number}",
                'email' => "discoverable-{$number}@example.test",
                'password' => 'password123',
                'role' => 'job_seeker',
                'status' => true,
            ]);
            JobSeeker::create([
                'user_id' => $user->id,
                'status' => true,
                'profile_searchable' => true,
                'professional_title' => 'Product Designer',
                'skills' => ['Figma', 'Research'],
                'bio' => 'Fictional demonstration profile.',
            ]);
        }

        $hiddenUser = User::create(['name' => 'Private Candidate', 'email' => 'private@example.test', 'password' => 'password123', 'role' => 'job_seeker', 'status' => true]);
        JobSeeker::create(['user_id' => $hiddenUser->id, 'status' => true, 'profile_searchable' => false, 'professional_title' => 'Product Designer']);
        $inactiveUser = User::create(['name' => 'Inactive Candidate', 'email' => 'inactive@example.test', 'password' => 'password123', 'role' => 'job_seeker', 'status' => true]);
        JobSeeker::create(['user_id' => $inactiveUser->id, 'status' => false, 'profile_searchable' => true, 'professional_title' => 'Product Designer']);

        $firstPage = $this->actingAs($this->employerUser)
            ->getJson('/api/employer/talent?keyword=Designer&page=1')
            ->assertOk()
            ->assertJsonCount(30, 'candidates')
            ->assertJsonPath('pagination.total', 32)
            ->assertJsonPath('pagination.current_page', 1)
            ->assertJsonPath('candidates.0.name', 'Discoverable Candidate 1')
            ->assertJsonPath('candidates.0.email', 'discoverable-1@example.test')
            ->assertJsonPath('candidates.0.bio', 'Fictional demonstration profile.')
            ->assertJsonMissingPath('remaining')
            ->assertJsonMissingPath('upgrade_required');

        $this->getJson('/api/employer/talent?keyword=Designer&page=2')
            ->assertOk()
            ->assertJsonCount(2, 'candidates')
            ->assertJsonPath('pagination.current_page', 2)
            ->assertJsonPath('pagination.total', 32);
        $this->assertSame([], $this->getJson('/api/employer/talent?keyword=NotAMatch')->assertOk()->json('candidates'));
        $this->assertDatabaseCount('feature_usages', 0);
        $this->assertNotContains('Private Candidate', collect($firstPage->json('candidates'))->pluck('name')->all());
    }

    public function test_talent_search_cv_download_is_available_without_candidate_cv_entitlement_or_usage_charge(): void
    {
        Storage::fake('local');
        Storage::disk('local')->put('resumes/talent-candidate.pdf', 'demo resume content');

        $user = User::create(['name' => 'CV Candidate', 'email' => 'cv-candidate@example.test', 'password' => 'password123', 'role' => 'job_seeker', 'status' => true]);
        $candidate = JobSeeker::create([
            'user_id' => $user->id,
            'status' => true,
            'profile_searchable' => true,
            'cv_path' => 'resumes/talent-candidate.pdf',
            'cv_original_name' => 'candidate-resume.pdf',
        ]);

        $this->actingAs($this->employerUser)
            ->getJson('/api/employer/talent')
            ->assertOk()
            ->assertJsonPath('candidates.0.cv_url', "/api/employer/talent/{$candidate->id}/resume");

        $this->get("/api/employer/talent/{$candidate->id}/resume")
            ->assertOk()
            ->assertHeader('content-disposition', 'attachment; filename=candidate-resume.pdf');

        $this->assertDatabaseCount('feature_usages', 0);
    }

    public function test_candidate_search_keeps_role_and_profile_visibility_authorization(): void
    {
        $candidateUser = User::create(['name' => 'Regular Seeker', 'email' => 'regular-seeker@example.test', 'password' => 'password123', 'role' => 'job_seeker', 'status' => true]);
        $this->actingAs($candidateUser)->getJson('/api/employer/talent')->assertForbidden();

        auth()->logout();
        $this->getJson('/api/employer/talent')->assertUnauthorized();
    }

    public function test_billing_plans_no_longer_advertise_paid_candidate_search_benefits(): void
    {
        $response = $this->actingAs($this->employerUser)->getJson('/api/employer/billing')->assertOk();

        $this->assertArrayNotHasKey('candidate_preview_limit', $response->json('plan.features'));
        $this->assertArrayNotHasKey('candidate_search', $response->json('plan.features'));
        $this->assertArrayNotHasKey('candidate_contact_view', $response->json('plan.features'));
        $this->assertArrayHasKey('candidate_cv_downloads', $response->json('plan.features'));
    }
}
