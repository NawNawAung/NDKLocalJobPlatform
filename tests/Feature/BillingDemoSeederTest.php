<?php

namespace Tests\Feature;

use App\Models\Employer;
use App\Models\JobCategory;
use App\Models\Order;
use App\Models\Payment;
use App\Models\User;
use Database\Seeders\BillingCatalogSeeder;
use Database\Seeders\BillingDemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Tests\TestCase;

class BillingDemoSeederTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        putenv('DEMO_BILLING_PASSWORD=Local-Demo-Password-2026!');
        $_ENV['DEMO_BILLING_PASSWORD'] = 'Local-Demo-Password-2026!';
        $_SERVER['DEMO_BILLING_PASSWORD'] = 'Local-Demo-Password-2026!';
    }

    protected function tearDown(): void
    {
        putenv('DEMO_BILLING_PASSWORD');
        unset($_ENV['DEMO_BILLING_PASSWORD'], $_SERVER['DEMO_BILLING_PASSWORD']);
        parent::tearDown();
    }

    public function test_billing_demo_seeder_creates_supported_scenarios_and_is_repeatable(): void
    {
        $this->seed(BillingCatalogSeeder::class);
        JobCategory::create(['name' => 'Technology', 'slug' => 'technology', 'is_active' => true]);

        $this->seed(BillingDemoSeeder::class);

        $this->assertSame(6, User::query()->where('email', 'like', 'demo-billing-%@example.test')->count());
        $this->assertSame(6, Employer::query()->where('company_name', 'like', '[DEMO]%')->count());
        $this->assertSame(5, Order::query()->where('order_number', 'like', 'DEMO-BILLING-%-2026')->count());
        $this->assertDatabaseHas('orders', ['order_number' => 'DEMO-BILLING-PENDING-2026', 'status' => 'awaiting_review']);
        $this->assertDatabaseHas('payments', ['reference_number' => 'DEMO-PENDING-2026', 'status' => 'pending']);
        $this->assertDatabaseHas('orders', ['order_number' => 'DEMO-BILLING-FAILED-2026', 'status' => 'payment_failed']);
        $this->assertDatabaseHas('payments', ['reference_number' => 'DEMO-FAILED-2026', 'status' => 'failed']);
        $this->assertDatabaseHas('invoices', ['invoice_number' => 'DEMO-INV-PAID-2026', 'status' => 'paid']);
        $this->assertDatabaseHas('subscriptions', ['status' => 'active']);
        $this->assertDatabaseHas('subscriptions', ['status' => 'expired']);
        $this->assertDatabaseHas('job_promotions', ['promotion_type' => 'featured', 'status' => 'active']);
        $this->assertSame(3, DB::table('invoices')->count());
        $this->assertSame(5, Payment::query()->count());

        $user = User::query()->where('email', 'demo-billing-active@example.test')->firstOrFail();
        $user->update(['name' => 'Reviewed demo contact']);
        $this->seed(BillingDemoSeeder::class);

        $this->assertSame(6, User::query()->where('email', 'like', 'demo-billing-%@example.test')->count());
        $this->assertSame(5, Order::query()->where('order_number', 'like', 'DEMO-BILLING-%-2026')->count());
        $this->assertSame('Reviewed demo contact', $user->fresh()->name);
        $this->assertSame(3, DB::table('invoices')->count());
    }

    public function test_billing_demo_seeder_requires_an_explicit_local_demo_password(): void
    {
        putenv('DEMO_BILLING_PASSWORD');
        unset($_ENV['DEMO_BILLING_PASSWORD'], $_SERVER['DEMO_BILLING_PASSWORD']);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Set DEMO_BILLING_PASSWORD');
        app(BillingDemoSeeder::class)->run();
    }

    public function test_billing_demo_seeder_refuses_reserved_emails_owned_by_real_accounts(): void
    {
        $this->seed(BillingCatalogSeeder::class);
        User::create([
            'name' => 'Unrelated Existing Contact',
            'email' => 'demo-billing-active@example.test',
            'password' => 'not-a-demo-password',
            'role' => 'job_seeker',
            'status' => true,
        ]);

        try {
            app(BillingDemoSeeder::class)->run();
            $this->fail('The seeder should reject a reserved address belonging to a non-demo account.');
        } catch (RuntimeException $exception) {
            $this->assertStringContainsString('non-demo account', $exception->getMessage());
        }

        $this->assertSame(1, User::query()->where('email', 'demo-billing-active@example.test')->count());
        $this->assertSame(0, Employer::query()->count());
        $this->assertSame(0, Order::query()->count());
    }

    public function test_employer_and_admin_billing_screens_receive_the_seeded_scenarios(): void
    {
        $this->seed(BillingCatalogSeeder::class);
        JobCategory::create(['name' => 'Technology', 'slug' => 'technology', 'is_active' => true]);
        $this->seed(BillingDemoSeeder::class);

        $active = User::where('email', 'demo-billing-active@example.test')->firstOrFail();
        $this->actingAs($active)->getJson('/api/employer/billing')->assertOk()
            ->assertJsonPath('plan.slug', 'professional')
            ->assertJsonPath('orders.0.latest_payment_status', 'paid');

        $pending = User::where('email', 'demo-billing-pending@example.test')->firstOrFail();
        $this->actingAs($pending)->getJson('/api/employer/billing')->assertOk()
            ->assertJsonPath('orders.0.status', 'awaiting_review')
            ->assertJsonPath('orders.0.latest_payment_status', 'pending');

        $failed = User::where('email', 'demo-billing-failed@example.test')->firstOrFail();
        $this->actingAs($failed)->getJson('/api/employer/billing')->assertOk()
            ->assertJsonPath('orders.0.status', 'payment_failed')
            ->assertJsonPath('orders.0.latest_payment_status', 'failed');

        $expired = User::where('email', 'demo-billing-expired@example.test')->firstOrFail();
        $this->actingAs($expired)->getJson('/api/employer/billing')->assertOk()
            ->assertJsonPath('plan.slug', 'free')
            ->assertJsonPath('orders.0.status', 'paid');

        $administrator = User::create([
            'name' => 'Billing Demo Administrator',
            'email' => 'billing-demo-admin@example.test',
            'password' => 'not-used-in-demo-login',
            'role' => 'admin',
            'status' => true,
        ]);
        $this->actingAs($administrator)->getJson('/api/admin/billing/payments?status=pending')->assertOk()
            ->assertJsonPath('payments.total', 1)
            ->assertJsonPath('payments.data.0.reference', 'DEMO-PENDING-2026');
    }
}
