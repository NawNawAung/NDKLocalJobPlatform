<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Region;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GuestAuthenticationFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_can_open_profile_shell_but_cannot_access_or_change_private_profile_data(): void
    {
        $this->get('/profile')
            ->assertOk()
            ->assertViewHas('authBootstrap.authenticated', false)
            ->assertViewHas('authBootstrap.profile', null);

        $this->patchJson('/profile', ['name' => 'Unauthorized change'])->assertUnauthorized();
        $employer = $this->user('employer');
        $this->actingAs($employer)->get('/profile')->assertForbidden();
    }

    public function test_login_and_registration_remember_only_safe_local_return_urls(): void
    {
        $user = $this->user('job_seeker');
        $returnTo = '/#details?job=42';

        $this->get('/login?redirect='.urlencode($returnTo))
            ->assertOk()
            ->assertSessionHas('url.intended', $returnTo);
        $this->post('/login', ['email' => $user->email, 'password' => 'password123'])
            ->assertRedirect($returnTo);

        auth()->logout();
        $this->get('/register?redirect='.urlencode($returnTo).'&role=employer')
            ->assertOk()
            ->assertViewHas('authBootstrap.registrationRole', 'employer')
            ->assertSessionHas('url.intended', $returnTo);

        $this->get('/login?redirect='.urlencode('https://example.com/unsafe'))
            ->assertOk()
            ->assertSessionHas('url.intended', $returnTo);
    }

    public function test_guest_api_requests_for_private_actions_are_rejected(): void
    {
        $this->postJson('/api/jobs/123/applications', [])->assertUnauthorized();
        $this->postJson('/api/jobs/123/save')->assertUnauthorized();
        $this->postJson('/api/applications/123/employer-review', [])->assertUnauthorized();
        $this->postJson('/api/reports', [])->assertUnauthorized();
        $this->getJson('/api/conversations')->assertUnauthorized();
    }

    public function test_admin_sign_in_can_return_to_a_safe_admin_destination(): void
    {
        $admin = $this->user('admin');
        $returnTo = '/admin#admin-billing';

        $this->get('/admin/login?redirect='.urlencode($returnTo))
            ->assertOk()
            ->assertViewHas('authBootstrap.adminLogin', true)
            ->assertSessionHas('url.intended', $returnTo);

        $this->post('/admin/login', ['email' => $admin->email, 'password' => 'password123'])
            ->assertRedirect($returnTo);
    }

    public function test_registration_returns_the_new_job_seeker_to_the_requested_public_job(): void
    {
        $region = Region::create(['name' => 'Yangon', 'type' => 'region', 'sort_order' => 1]);
        $returnTo = '/#details?job=67';

        $this->get('/register?redirect='.urlencode($returnTo).'&role=job_seeker')
            ->assertViewHas('authBootstrap.registrationRole', 'job_seeker')
            ->assertSessionHas('url.intended', $returnTo);

        $this->post('/register', [
            'name' => 'New Job Seeker',
            'email' => 'new-job-seeker@example.test',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'role' => 'job_seeker',
            'region_id' => $region->id,
        ])->assertRedirect($returnTo);
    }

    private function user(string $role): User
    {
        return User::create([
            'name' => ucfirst($role).' Account',
            'email' => $role.'-'.uniqid().'@example.test',
            'password' => 'password123',
            'role' => $role,
            'status' => true,
        ]);
    }
}
