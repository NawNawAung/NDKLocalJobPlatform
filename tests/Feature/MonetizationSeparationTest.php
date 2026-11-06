<?php

namespace Tests\Feature;

use App\Models\BillingProduct;
use App\Models\Employer;
use App\Models\ExternalAdvertisement;
use App\Models\FeaturedJobPackage;
use App\Models\Job;
use App\Models\Order;
use App\Models\Payment;
use App\Models\User;
use Database\Seeders\BillingCatalogSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class MonetizationSeparationTest extends TestCase
{
    use RefreshDatabase;

    private User $employerUser;

    private Employer $employer;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
        $this->seed(BillingCatalogSeeder::class);
        $this->employerUser = User::create(['name' => 'Package Employer', 'email' => 'packages@example.test', 'password' => 'password123', 'role' => 'employer', 'status' => true]);
        $this->employer = Employer::create(['user_id' => $this->employerUser->id, 'company_name' => 'Package Co', 'verification_status' => 'pending']);
        $this->admin = User::create(['name' => 'Monetization Admin', 'email' => 'money-admin@example.test', 'password' => 'password123', 'role' => 'admin', 'status' => true]);
    }

    public function test_paid_featured_package_can_be_assigned_after_purchase_and_is_owner_scoped(): void
    {
        $product = BillingProduct::query()->where('slug', 'featured-job-7-days')->firstOrFail();
        $this->actingAs($this->admin)->patchJson("/api/admin/advertising/products/{$product->id}", [
            'price_amount' => $product->price_amount, 'duration_days' => 7, 'slots' => 2,
            'package_validity_days' => 30, 'is_active' => true,
        ])->assertOk();

        $job = Job::create(['employer_id' => $this->employer->id, 'title' => 'Published Developer', 'description' => 'Role details', 'location' => 'Yangon', 'category' => 'Technology', 'employment_type' => 'full_time', 'status' => 'published', 'posted_at' => now()]);
        $orderResponse = $this->actingAs($this->employerUser)->postJson('/api/employer/billing/orders', ['product_slug' => $product->slug])->assertCreated();
        $order = Order::findOrFail($orderResponse->json('order.id'));
        $this->assertDatabaseHas('order_items', ['order_id' => $order->id, 'item_type' => 'job_promotion', 'job_id' => null]);
        $this->assertDatabaseMissing('featured_job_packages', ['employer_id' => $this->employer->id]);

        $payment = $this->submitReceipt($order);
        $this->actingAs($this->admin)->patchJson("/api/admin/billing/payments/{$payment->id}/review", ['decision' => 'approve'])->assertOk();
        $package = FeaturedJobPackage::query()->where('employer_id', $this->employer->id)->firstOrFail();
        $this->assertSame(2, $package->slots_remaining);

        $this->actingAs($this->employerUser)->postJson("/api/employer/billing/featured-packages/{$package->id}/assign", ['job_id' => $job->id])->assertCreated();
        $this->assertDatabaseHas('job_promotions', ['job_id' => $job->id, 'order_item_id' => $package->order_item_id, 'status' => 'active']);
        $this->assertSame(1, $package->fresh()->slots_remaining);
        $this->actingAs($this->employerUser)->postJson("/api/employer/billing/featured-packages/{$package->id}/assign", ['job_id' => $job->id])->assertUnprocessable();

        $otherUser = User::create(['name' => 'Other Company', 'email' => 'other-package@example.test', 'password' => 'password123', 'role' => 'employer', 'status' => true]);
        Employer::create(['user_id' => $otherUser->id, 'company_name' => 'Other Co', 'verification_status' => 'pending']);
        $this->actingAs($otherUser)->postJson("/api/employer/billing/featured-packages/{$package->id}/assign", ['job_id' => $job->id])->assertNotFound();
        $secondJob = Job::create(['employer_id' => $this->employer->id, 'title' => 'Second Published Role', 'description' => 'Role details', 'location' => 'Yangon', 'category' => 'Technology', 'employment_type' => 'full_time', 'status' => 'published', 'posted_at' => now()]);
        $package->update(['expires_at' => now()->subSecond()]);
        $this->actingAs($this->employerUser)->postJson("/api/employer/billing/featured-packages/{$package->id}/assign", ['job_id' => $secondJob->id])->assertUnprocessable();
        $this->actingAs($this->employerUser)->getJson('/api/employer/billing')->assertOk()
            ->assertJsonPath('featured_packages.0.slots_remaining', 1)
            ->assertJsonPath('featured_packages.0.promotions.0.job_title', 'Published Developer');
    }

    public function test_admin_can_configure_additional_featured_job_packages_only(): void
    {
        $payload = ['name' => 'Featured Five', 'slug' => 'featured-five-test', 'description' => 'Five-day package', 'price_amount' => 18000, 'duration_days' => 5, 'slots' => 3, 'package_validity_days' => 60, 'is_active' => true];
        $this->actingAs($this->employerUser)->postJson('/api/admin/advertising/job-products', $payload)->assertForbidden();
        $this->actingAs($this->admin)->postJson('/api/admin/advertising/job-products', $payload)->assertCreated()
            ->assertJsonPath('product.product_type', 'job_promotion');
        $this->assertDatabaseHas('billing_products', ['slug' => 'featured-five-test', 'price_amount' => 18000, 'is_active' => true]);
        $this->actingAs($this->admin)->postJson('/api/admin/advertising/job-products', $payload)->assertUnprocessable();
        $this->assertDatabaseHas('admin_audit_logs', ['action' => 'featured_job.product.created', 'administrator_id' => $this->admin->id]);
    }

    public function test_catalog_seeding_does_not_reset_an_admin_configured_featured_package(): void
    {
        $product = BillingProduct::query()->where('slug', 'featured-job-7-days')->firstOrFail();
        $product->update(['price_amount' => 23456, 'metadata' => ['promotion_type' => 'featured', 'slots' => 4, 'package_validity_days' => 45]]);

        $this->seed(BillingCatalogSeeder::class);

        $this->assertSame(23456, $product->fresh()->price_amount);
        $this->assertSame(4, $product->fresh()->metadata['slots']);
        $this->assertSame(45, $product->fresh()->metadata['package_validity_days']);
    }

    public function test_external_advertisements_have_separate_admin_only_payment_records_and_expire_from_display(): void
    {
        $campaign = [
            'advertiser_name' => 'Fictional Beverage Co', 'advertiser_email' => 'ads@example.test',
            'advertiser_website' => 'https://advertiser.example.test', 'campaign_name' => 'Summer Offer',
            'headline' => 'A fictional local product promotion', 'banner' => UploadedFile::fake()->image('banner.png', 1200, 300),
            'destination_url' => 'https://advertiser.example.test/offer', 'placement' => 'home_banner',
            'starts_at' => now()->subMinute()->toIso8601String(), 'ends_at' => now()->addDays(5)->toIso8601String(),
            'impression_limit' => 1, 'price_amount' => 45000,
        ];
        $this->actingAs($this->employerUser)->postJson('/api/admin/advertising/external', $campaign)->assertForbidden();
        $this->app['auth']->logout();
        $this->getJson('/api/admin/advertising/external')->assertUnauthorized();

        $created = $this->actingAs($this->admin)->post('/api/admin/advertising/external', $campaign, ['Accept' => 'application/json'])->assertCreated();
        $adId = $created->json('advertisement.id');
        $this->assertDatabaseHas('external_advertisements', ['id' => $adId, 'status' => 'paused', 'price_amount' => 45000]);
        $this->getJson('/api/advertisements?placement=home_banner')->assertOk()->assertJsonCount(0, 'data');
        $this->actingAs($this->admin)->patchJson("/api/admin/advertising/external/{$adId}", ['status' => 'active'])->assertUnprocessable();

        $this->actingAs($this->admin)->postJson("/api/admin/advertising/external/{$adId}/payments", ['status' => 'paid', 'reference_number' => 'OFFLINE-AD-001'])->assertOk();
        $this->actingAs($this->admin)->postJson("/api/admin/advertising/external/{$adId}/payments", ['status' => 'paid', 'reference_number' => 'OFFLINE-AD-DUPLICATE'])->assertUnprocessable();
        $this->assertDatabaseHas('external_ad_payments', ['external_advertisement_id' => $adId, 'amount' => 45000, 'status' => 'paid']);
        $this->actingAs($this->admin)->patchJson("/api/admin/advertising/external/{$adId}", ['status' => 'active'])->assertOk();
        $this->getJson('/api/advertisements?placement=home_banner')->assertOk()->assertJsonPath('data.0.label', 'Advertisement');
        $this->assertDatabaseHas('external_advertisements', ['id' => $adId, 'impressions_count' => 1]);
        $this->getJson('/api/advertisements?placement=home_banner')->assertOk()->assertJsonCount(0, 'data');

        $ad = ExternalAdvertisement::findOrFail($adId);
        $this->actingAs($this->admin)->patchJson("/api/admin/advertising/external/{$adId}", ['status' => 'paused', 'starts_at' => now()->subDays(2)->toIso8601String(), 'ends_at' => now()->subDay()->toIso8601String()])->assertOk();
        $this->assertSame('paused', $ad->fresh()->status);
        $this->assertDatabaseHas('admin_audit_logs', ['administrator_id' => $this->admin->id, 'action' => 'external_ad.payment_recorded']);
    }

    private function submitReceipt(Order $order): Payment
    {
        $this->actingAs($this->employerUser)->post("/api/employer/billing/orders/{$order->id}/payments", [
            'payment_method' => 'bank_transfer', 'reference_number' => 'FEATURED-PACKAGE-001',
            'proof' => UploadedFile::fake()->create('receipt.pdf', 20, 'application/pdf'),
        ], ['Accept' => 'application/json'])->assertCreated();

        return Payment::query()->where('order_id', $order->id)->firstOrFail();
    }
}
