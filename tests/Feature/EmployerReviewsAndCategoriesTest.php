<?php

namespace Tests\Feature;

use App\Models\Application;
use App\Models\Employer;
use App\Models\EmployerReview;
use App\Models\Job;
use App\Models\JobCategory;
use App\Models\JobSeeker;
use App\Models\User;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EmployerReviewsAndCategoriesTest extends TestCase
{
    use RefreshDatabase;

    private function account(string $name, string $role): User
    {
        return User::create(['name' => $name, 'email' => strtolower(str_replace(' ', '.', $name)).'@example.test', 'password' => 'password123', 'role' => $role, 'status' => true]);
    }

    private function reviewableApplication(): array
    {
        $employerUser = $this->account('Company Contact', 'employer');
        $employer = Employer::create(['user_id' => $employerUser->id, 'company_name' => 'Example Co']);
        $seekerUser = $this->account('Job Seeker', 'job_seeker');
        $seeker = JobSeeker::create(['user_id' => $seekerUser->id, 'status' => true]);
        $category = JobCategory::create(['name' => 'Technology', 'slug' => 'technology', 'is_active' => true]);
        $job = Job::create(['employer_id' => $employer->id, 'category_id' => $category->id, 'category' => $category->name, 'title' => 'Developer', 'description' => 'Build services.', 'location' => 'Yangon', 'employment_type' => 'full_time', 'status' => 'published']);
        $application = Application::create(['job_id' => $job->id, 'job_seeker_id' => $seeker->id, 'status' => 'shortlisted', 'submitted_at' => now()]);
        return compact('employerUser', 'employer', 'seekerUser', 'seeker', 'category', 'job', 'application');
    }

    public function test_eligible_job_seeker_can_submit_one_review_and_admin_can_moderate_it(): void
    {
        $records = $this->reviewableApplication();
        $this->actingAs($records['seekerUser'])->postJson("/api/applications/{$records['application']->id}/employer-review", ['rating' => 5, 'title' => 'Good process', 'review' => 'The recruitment process was clear and respectful.'])->assertCreated();
        $this->actingAs($records['seekerUser'])->postJson("/api/applications/{$records['application']->id}/employer-review", ['rating' => 4, 'review' => 'A duplicate review should be prevented.'])->assertUnprocessable();
        $review = EmployerReview::firstOrFail();
        $admin = $this->account('Platform Admin', 'admin');
        $this->actingAs($admin)->patchJson("/api/admin/employer-reviews/{$review->id}", ['decision' => 'approve'])->assertOk();
        $this->getJson("/api/employers/{$records['employer']->id}/reviews")->assertOk()->assertJsonPath('average_rating', 5)->assertJsonPath('review_count', 1);
        $this->assertDatabaseHas('admin_audit_logs', ['administrator_id' => $admin->id, 'action' => 'employer_review.approve']);
    }

    public function test_review_requires_owned_eligible_application_and_role_and_cannot_review_self(): void
    {
        $records = $this->reviewableApplication();
        $otherSeeker = $this->account('Other Seeker', 'job_seeker');
        JobSeeker::create(['user_id' => $otherSeeker->id, 'status' => true]);
        $this->actingAs($otherSeeker)->postJson("/api/applications/{$records['application']->id}/employer-review", ['rating' => 5, 'review' => 'This account does not own the application.'])->assertNotFound();
        $records['application']->update(['status' => 'submitted']);
        $this->actingAs($records['seekerUser'])->postJson("/api/applications/{$records['application']->id}/employer-review", ['rating' => 5, 'review' => 'This application has not yet been reviewed.'])->assertUnprocessable();
        $this->actingAs($records['employerUser'])->postJson("/api/applications/{$records['application']->id}/employer-review", ['rating' => 5, 'review' => 'The employer cannot review their own employer account.'])->assertForbidden();
    }

    public function test_admin_role_changes_cannot_grant_admin_and_changes_are_audited(): void
    {
        $records = $this->reviewableApplication();
        $admin = $this->account('Platform Admin', 'admin');
        $this->actingAs($admin)->patchJson("/api/admin/users/{$records['seekerUser']->id}", ['role' => 'admin'])->assertUnprocessable();
        $this->actingAs($admin)->patchJson("/api/admin/users/{$records['seekerUser']->id}", ['status' => false])->assertOk();
        $this->assertDatabaseHas('admin_audit_logs', ['administrator_id' => $admin->id, 'action' => 'user.updated', 'target_id' => $records['seekerUser']->id]);
        $this->actingAs($records['seekerUser'])->getJson('/api/admin/audit-logs')->assertForbidden();
    }

    public function test_category_crud_and_filtering_use_category_ids(): void
    {
        $records = $this->reviewableApplication();
        $admin = $this->account('Platform Admin', 'admin');
        $response = $this->actingAs($admin)->postJson('/api/admin/categories', ['name' => 'Finance', 'sort_order' => 2])->assertCreated();
        $financeId = $response->json('category.id');
        $this->actingAs($records['employerUser'])->getJson('/api/jobs/search?category_id='.$records['category']->id)->assertOk();
        $this->actingAs($admin)->patchJson("/api/admin/categories/{$records['category']->id}", ['name' => 'Software Engineering'])->assertOk();
        $this->assertDatabaseHas('job_listings', ['id' => $records['job']->id, 'category' => 'Software Engineering', 'category_id' => $records['category']->id]);
        $viewer = $this->account('Search Viewer', 'job_seeker');
        JobSeeker::create(['user_id' => $viewer->id, 'status' => true]);
        $this->actingAs($viewer)->getJson('/api/jobs/search?category=Software%20Engineering')->assertOk()->assertJsonPath('data.0.id', $records['job']->id);
        $this->actingAs($viewer)->getJson('/api/jobs/search?category_id='.$records['category']->id)->assertOk()->assertJsonPath('data.0.id', $records['job']->id);
        $this->actingAs($admin)->patchJson("/api/admin/categories/{$financeId}", ['is_active' => false])->assertOk();
        $this->getJson('/api/job-categories')->assertOk()->assertJsonMissing(['id' => $financeId]);
        $this->assertDatabaseHas('admin_audit_logs', ['administrator_id' => $admin->id, 'action' => 'job_category.updated']);
    }

    public function test_database_enforces_one_application_per_job_and_job_seeker(): void
    {
        $records = $this->reviewableApplication();
        $this->actingAs($records['seekerUser'])
            ->postJson("/api/jobs/{$records['job']->id}/applications", [])
            ->assertUnprocessable();

        $this->expectException(UniqueConstraintViolationException::class);
        Application::create([
            'job_id' => $records['job']->id,
            'job_seeker_id' => $records['seeker']->id,
            'status' => 'submitted',
            'submitted_at' => now(),
        ]);
    }

    public function test_application_uniqueness_migration_refuses_duplicate_data_and_rolls_back_without_deleting_it(): void
    {
        $records = $this->reviewableApplication();
        $migration = require database_path('migrations/2026_10_10_000000_enforce_unique_job_applications.php');
        $migration->down();

        Application::create([
            'job_id' => $records['job']->id,
            'job_seeker_id' => $records['seeker']->id,
            'status' => 'submitted',
            'submitted_at' => now(),
        ]);

        try {
            $migration->up();
            $this->fail('The migration must refuse to add uniqueness while duplicates exist.');
        } catch (\RuntimeException $exception) {
            $this->assertStringContainsString('duplicate application groups exist', $exception->getMessage());
        }

        $this->assertSame(2, Application::query()
            ->where('job_id', $records['job']->id)
            ->where('job_seeker_id', $records['seeker']->id)
            ->count());
    }

    public function test_verification_status_overrides_legacy_boolean_when_present(): void
    {
        $user = $this->account('Verification Contact', 'employer');
        $employer = Employer::create([
            'user_id' => $user->id,
            'company_name' => 'Verification Example',
            'is_verified' => true,
            'verification_status' => 'pending',
        ]);

        $this->assertFalse($employer->fresh()->is_verified);

        $employer->update(['is_verified' => false, 'verification_status' => 'verified']);
        $this->assertTrue($employer->fresh()->is_verified);
    }

    public function test_category_backfill_migration_preserves_legacy_listing_values(): void
    {
        $employerUser = $this->account('Legacy Employer', 'employer');
        $employer = Employer::create(['user_id' => $employerUser->id, 'company_name' => 'Legacy Co']);
        $job = Job::create(['employer_id' => $employer->id, 'title' => 'Old category job', 'description' => 'Existing listing.', 'location' => 'Yangon', 'category' => 'Research & Development', 'employment_type' => 'full_time', 'status' => 'draft']);
        $uncategorized = Job::create(['employer_id' => $employer->id, 'title' => 'No category job', 'description' => 'Missing a legacy category.', 'location' => 'Yangon', 'category' => '', 'employment_type' => 'full_time', 'status' => 'draft']);
        $migration = require database_path('migrations/2026_10_09_000000_add_categories_reviews_and_admin_audit.php');
        $migration->up();
        $this->assertDatabaseHas('job_categories', ['name' => 'Research & Development']);
        $this->assertDatabaseHas('job_listings', ['id' => $job->id, 'category' => 'Research & Development']);
        $this->assertNotNull(Job::findOrFail($job->id)->category_id);
        $this->assertNotNull(Job::findOrFail($uncategorized->id)->category_id);
        $this->assertDatabaseHas('job_categories', ['name' => 'Uncategorized (Legacy)']);
    }
}
