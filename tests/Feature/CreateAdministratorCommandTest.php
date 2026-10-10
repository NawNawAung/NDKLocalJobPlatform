<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CreateAdministratorCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_server_command_creates_an_active_admin_with_a_hashed_password(): void
    {
        $this->artisan('admin:create')
            ->expectsQuestion('Administrator name', 'Initial Administrator')
            ->expectsQuestion('Administrator email', 'ADMIN@example.test')
            ->expectsQuestion('Administrator password (minimum 12 characters)', 'LongSecurePassword123')
            ->expectsQuestion('Confirm administrator password', 'LongSecurePassword123')
            ->expectsConfirmation('Create active administrator account for admin@example.test?', 'yes')
            ->expectsOutputToContain('Sign in at /admin/login.')
            ->assertExitCode(0);

        $admin = User::where('email', 'admin@example.test')->firstOrFail();
        $this->assertSame('admin', $admin->role);
        $this->assertTrue($admin->status);
        $this->assertNotSame('LongSecurePassword123', $admin->password);
        $this->assertTrue(password_verify('LongSecurePassword123', $admin->password));
    }

    public function test_server_command_refuses_an_existing_account_email(): void
    {
        User::create(['name' => 'Existing User', 'email' => 'existing@example.test', 'password' => 'password123', 'role' => 'job_seeker', 'status' => true]);

        $this->artisan('admin:create')
            ->expectsQuestion('Administrator name', 'Admin')
            ->expectsQuestion('Administrator email', 'existing@example.test')
            ->expectsQuestion('Administrator password (minimum 12 characters)', 'LongSecurePassword123')
            ->expectsQuestion('Confirm administrator password', 'LongSecurePassword123')
            ->expectsOutputToContain('The email has already been taken.')
            ->assertExitCode(1);

        $this->assertDatabaseHas('users', ['email' => 'existing@example.test', 'role' => 'job_seeker']);
    }
}
