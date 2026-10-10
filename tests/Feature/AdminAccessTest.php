<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Employer;
use App\Models\Job;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_non_admin_cannot_access_admin_dashboard_api(): void
    {
        $user = User::create(['name' => 'Candidate', 'email' => 'candidate@example.test', 'password' => 'password123', 'role' => 'job_seeker', 'status' => true]);

        $this->actingAs($user)->getJson('/api/admin/dashboard')->assertForbidden();
    }

    public function test_active_admin_can_load_dashboard_and_admin_lists(): void
    {
        $admin = User::create(['name' => 'Platform Admin', 'email' => 'admin@example.test', 'password' => 'password123', 'role' => 'admin', 'status' => true]);

        $this->actingAs($admin)->getJson('/api/admin/dashboard')->assertOk()->assertJsonStructure(['stats' => ['users', 'job_seekers', 'employers', 'active_jobs', 'pending_jobs', 'applications', 'pending_verifications', 'open_reports'], 'recent_activity']);
        $this->actingAs($admin)->getJson('/api/admin/users')->assertOk()->assertJsonStructure(['users' => ['data']]);
    }

    public function test_inactive_admin_cannot_access_admin_dashboard_api(): void
    {
        $admin = User::create(['name' => 'Inactive Admin', 'email' => 'inactive-admin@example.test', 'password' => 'password123', 'role' => 'admin', 'status' => false]);

        $this->actingAs($admin)->getJson('/api/admin/dashboard')->assertForbidden();
    }

    public function test_admin_browser_entry_requires_an_active_administrator(): void
    {
        $candidate = User::create(['name' => 'Candidate', 'email' => 'admin-page-candidate@example.test', 'password' => 'password123', 'role' => 'job_seeker', 'status' => true]);
        $admin = User::create(['name' => 'Platform Admin', 'email' => 'admin-page@example.test', 'password' => 'password123', 'role' => 'admin', 'status' => true]);

        $this->get('/admin')->assertRedirect('/login')->assertSessionHas('url.intended');
        $this->actingAs($candidate)->get('/admin')->assertForbidden();
        $this->actingAs($admin)->get('/admin')->assertOk()->assertViewHas('authBootstrap.page', 'admin');
    }

    public function test_dedicated_admin_login_accepts_only_active_admin_credentials(): void
    {
        $admin = User::create(['name' => 'Platform Admin', 'email' => 'admin-login@example.test', 'password' => 'password123', 'role' => 'admin', 'status' => true]);
        $candidate = User::create(['name' => 'Candidate', 'email' => 'candidate-login@example.test', 'password' => 'password123', 'role' => 'job_seeker', 'status' => true]);
        $inactiveAdmin = User::create(['name' => 'Inactive Admin', 'email' => 'inactive-login@example.test', 'password' => 'password123', 'role' => 'admin', 'status' => false]);

        $this->get('/admin/login')->assertOk()->assertViewHas('authBootstrap.adminLogin', true);
        $this->post('/admin/login', ['email' => $candidate->email, 'password' => 'password123'])->assertSessionHasErrors('email');
        $this->assertGuest();
        $this->post('/admin/login', ['email' => $inactiveAdmin->email, 'password' => 'password123'])->assertSessionHasErrors('email');
        $this->assertGuest();
        $this->post('/admin/login', ['email' => $admin->email, 'password' => 'password123'])->assertRedirect('/admin');
        $this->assertAuthenticatedAs($admin);
    }

    public function test_public_registration_cannot_assign_the_admin_role(): void
    {
        $this->post('/register', [
            'name' => 'Public Admin',
            'email' => 'public-admin@example.test',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'role' => 'admin',
        ])->assertSessionHasErrors('role');

        $this->assertDatabaseMissing('users', ['email' => 'public-admin@example.test']);
    }

    public function test_admin_can_review_employer_and_moderate_a_job(): void
    {
        $admin = User::create(['name' => 'Platform Admin', 'email' => 'admin-review@example.test', 'password' => 'password123', 'role' => 'admin', 'status' => true]);
        $employerUser = User::create(['name' => 'Company Contact', 'email' => 'company@example.test', 'password' => 'password123', 'role' => 'employer', 'status' => true]);
        $employer = Employer::create(['user_id' => $employerUser->id, 'company_name' => 'Example Co', 'verification_status' => 'pending']);
        $job = Job::create(['employer_id' => $employer->id, 'title' => 'Support Engineer', 'description' => 'Provide technical support.', 'location' => 'Yangon', 'category' => 'Technology', 'employment_type' => 'full_time', 'status' => 'draft']);

        $this->actingAs($admin)->patchJson("/api/admin/employers/{$employer->id}/verification", ['decision' => 'approve'])->assertOk();
        $this->actingAs($admin)->patchJson("/api/admin/jobs/{$job->id}/moderation", ['status' => 'published'])->assertOk();

        $this->assertDatabaseHas('employers', ['id' => $employer->id, 'is_verified' => true, 'verification_status' => 'verified']);
        $this->assertDatabaseHas('job_listings', ['id' => $job->id, 'status' => 'published']);
    }

    public function test_report_submission_and_admin_action_deactivate_reported_user(): void
    {
        $reporter = User::create(['name' => 'Reporter', 'email' => 'reporter@example.test', 'password' => 'password123', 'role' => 'job_seeker', 'status' => true]);
        $reported = User::create(['name' => 'Reported Employer', 'email' => 'reported@example.test', 'password' => 'password123', 'role' => 'employer', 'status' => true]);
        $this->actingAs($reporter)->postJson('/api/reports', ['target_type' => 'user', 'target_id' => $reported->id, 'reason' => 'fraud', 'details' => 'Suspected fraudulent account'])->assertCreated();
        $reportId = (int) \App\Models\ContentReport::query()->value('id');
        $admin = User::create(['name' => 'Platform Admin', 'email' => 'admin-action@example.test', 'password' => 'password123', 'role' => 'admin', 'status' => true]);

        $this->actingAs($admin)->patchJson("/api/admin/reports/{$reportId}", ['decision' => 'actioned'])->assertOk();

        $this->assertDatabaseHas('users', ['id' => $reported->id, 'status' => false]);
        $this->assertDatabaseHas('content_reports', ['id' => $reportId, 'status' => 'actioned', 'reviewed_by' => $admin->id]);
    }
}
