<?php

namespace Tests\Feature;

use App\Models\Employer;
use App\Models\Job;
use App\Models\JobPromotion;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CompanyDirectoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_job_seekers_can_search_public_company_information_and_current_job_counts(): void
    {
        $company = $this->employer('Blue Cedar Technologies', 'Software services in Yangon', true);
        $this->job($company, 'Backend Engineer');
        $this->job($company, 'Expired role', 'published', now()->subDay());
        $this->job($company, 'Draft role', 'draft');
        $this->employer('Inactive Company', 'Should not be public', true, false);
        $this->employer('Quiet Company', 'No current vacancies');

        $seeker = User::create([
            'name' => 'Company Directory Seeker', 'email' => 'company-directory-seeker@example.test',
            'password' => 'password123', 'role' => 'job_seeker', 'status' => true,
        ]);

        $response = $this->actingAs($seeker)->getJson('/api/companies?q=Blue Cedar');

        $response->assertOk()
            ->assertJsonPath('companies.total', 1)
            ->assertJsonPath('companies.data.0.name', 'Blue Cedar Technologies')
            ->assertJsonPath('companies.data.0.description', 'Software services in Yangon')
            ->assertJsonPath('companies.data.0.is_verified', true)
            ->assertJsonPath('companies.data.0.open_jobs_count', 1)
            ->assertJsonMissingPath('companies.data.0.email');

        $this->getJson('/api/companies?hiring_only=1')->assertOk()->assertJsonPath('companies.total', 1);
    }

    public function test_company_profile_only_exposes_active_employer_and_current_public_jobs(): void
    {
        $company = $this->employer('Harbor Logistics', 'Regional transport company');
        $this->job($company, 'Operations Coordinator');
        $this->job($company, 'Closed Dispatcher role', 'closed');

        $this->getJson("/api/companies/{$company->id}")
            ->assertOk()
            ->assertJsonPath('company.name', 'Harbor Logistics')
            ->assertJsonPath('company.open_jobs_count', 1)
            ->assertJsonPath('jobs.total', 1)
            ->assertJsonPath('jobs.data.0.title', 'Operations Coordinator');

        $inactive = $this->employer('Inactive Public Profile', 'Not visible', false, false);
        $this->getJson("/api/companies/{$inactive->id}")->assertNotFound();
    }

    public function test_company_directory_and_profile_routes_mount_the_correct_vue_pages(): void
    {
        $company = $this->employer('Maple Learning', 'Education company');

        $this->get('/companies')->assertOk()->assertViewHas('authBootstrap.page', 'companies');
        $this->get("/companies/{$company->id}")
            ->assertOk()
            ->assertViewHas('authBootstrap.page', 'company-profile')
            ->assertViewHas('authBootstrap.companyId', $company->id);
    }

    public function test_job_search_marks_only_current_featured_promotions_for_the_home_section(): void
    {
        $company = $this->employer('Featured Test Company', 'Testing featured listings');
        $featured = $this->job($company, 'Featured position');
        $regular = $this->job($company, 'Regular position');

        JobPromotion::create([
            'job_id' => $featured->id,
            'employer_id' => $company->id,
            'promotion_type' => 'featured',
            'status' => 'active',
            'starts_at' => now()->subHour(),
            'ends_at' => now()->addDay(),
        ]);

        $this->getJson('/api/jobs/search')
            ->assertOk()
            ->assertJsonPath('data.0.id', $featured->id)
            ->assertJsonPath('data.0.is_featured', true)
            ->assertJsonPath('data.1.id', $regular->id)
            ->assertJsonPath('data.1.is_featured', false);
    }

    private function employer(string $name, string $description, bool $verified = false, bool $active = true): Employer
    {
        $user = User::create([
            'name' => $name.' Owner', 'email' => str_replace(' ', '-', strtolower($name)).'@example.test',
            'password' => 'password123', 'role' => 'employer', 'status' => $active,
        ]);

        return Employer::create([
            'user_id' => $user->id,
            'company_name' => $name,
            'company_description' => $description,
            'location' => 'Yangon',
            'website_url' => 'https://example.test',
            'social_links' => ['linkedin' => 'https://linkedin.example.test/company'],
            'is_verified' => $verified,
            'verification_status' => $verified ? 'verified' : 'unverified',
        ]);
    }

    private function job(Employer $employer, string $title, string $status = 'published', $deadline = null): Job
    {
        return Job::create([
            'employer_id' => $employer->id,
            'title' => $title,
            'description' => 'A public role for company directory coverage.',
            'location' => 'Yangon',
            'category' => 'Operations',
            'employment_type' => 'full_time',
            'experience_level' => 'mid',
            'status' => $status,
            'posted_at' => now(),
            'application_deadline' => $deadline,
        ]);
    }
}
