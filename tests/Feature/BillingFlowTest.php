<?php

namespace Tests\Feature;

use App\Models\Employer;
use App\Models\Invoice;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Subscription;
use App\Models\User;
use Database\Seeders\BillingCatalogSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class BillingFlowTest extends TestCase
{
    use RefreshDatabase;

    private User $employerUser;
    private Employer $employer;
    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(BillingCatalogSeeder::class);
        $this->employerUser = User::create(['name' => 'Employer Contact', 'email' => 'billing-employer@example.test', 'password' => 'password123', 'role' => 'employer', 'status' => true]);
        $this->employer = Employer::create(['user_id' => $this->employerUser->id, 'company_name' => 'Billing Co', 'verification_status' => 'pending']);
        $this->admin = User::create(['name' => 'Billing Admin', 'email' => 'billing-admin@example.test', 'password' => 'password123', 'role' => 'admin', 'status' => true]);
    }

    public function test_successful_manual_payment_activates_order_and_subscription_once(): void
    {
        $order = $this->createOrder();
        $payment = $this->submitReceipt($order);

        $this->actingAs($this->admin)->patchJson("/api/admin/billing/payments/{$payment->id}/review", ['decision' => 'approve'])->assertOk();
        $this->assertDatabaseHas('payments', ['id' => $payment->id, 'status' => 'paid', 'reviewed_by' => $this->admin->id]);
        $this->assertDatabaseHas('orders', ['id' => $order->id, 'status' => 'paid']);
        $this->assertDatabaseHas('invoices', ['order_id' => $order->id, 'status' => 'paid']);
        $this->assertSame(1, Subscription::query()->where('employer_id', $this->employer->id)->count());
        $this->assertDatabaseHas('admin_audit_logs', ['administrator_id' => $this->admin->id, 'action' => 'payment.reviewed.approve']);

        $this->actingAs($this->admin)->patchJson("/api/admin/billing/payments/{$payment->id}/review", ['decision' => 'approve'])->assertUnprocessable();
        $this->assertSame(1, Subscription::query()->where('employer_id', $this->employer->id)->count());
        $this->assertSame(1, Invoice::query()->where('order_id', $order->id)->count());
    }

    public function test_rejected_receipt_can_be_resubmitted_and_unpaid_order_cancelled(): void
    {
        $order = $this->createOrder();
        $firstPayment = $this->submitReceipt($order);
        $this->actingAs($this->admin)->patchJson("/api/admin/billing/payments/{$firstPayment->id}/review", ['decision' => 'reject', 'notes' => 'Reference could not be verified.'])->assertOk();
        $this->assertDatabaseHas('orders', ['id' => $order->id, 'status' => 'payment_failed']);
        $this->assertDatabaseHas('payments', ['id' => $firstPayment->id, 'status' => 'failed', 'failure_reason' => 'Reference could not be verified.']);

        $secondPayment = $this->submitReceipt($order->fresh(), 'TRANSFER-2');
        $this->assertNotSame($firstPayment->id, $secondPayment->id);
        $this->assertDatabaseHas('orders', ['id' => $order->id, 'status' => 'awaiting_review']);
        $this->actingAs($this->admin)->patchJson("/api/admin/billing/payments/{$secondPayment->id}/review", ['decision' => 'reject', 'notes' => 'Still not verified.'])->assertOk();

        $this->actingAs($this->employerUser)->patchJson("/api/employer/billing/orders/{$order->id}/cancel")->assertOk();
        $this->assertDatabaseHas('orders', ['id' => $order->id, 'status' => 'cancelled']);
        $this->actingAs($this->employerUser)->post("/api/employer/billing/orders/{$order->id}/payments", [
            'payment_method' => 'bank_transfer', 'proof' => UploadedFile::fake()->create('receipt.pdf', 20, 'application/pdf'),
        ], ['Accept' => 'application/json'])->assertUnprocessable();
    }

    public function test_validation_duplicate_submission_authorization_and_pending_order_cancellation(): void
    {
        $order = $this->createOrder();
        $proof = UploadedFile::fake()->create('receipt.pdf', 20, 'application/pdf');
        $this->actingAs($this->employerUser)->post("/api/employer/billing/orders/{$order->id}/payments", ['payment_method' => 'credit_card', 'proof' => $proof], ['Accept' => 'application/json'])->assertUnprocessable();
        $this->assertSame(0, Payment::query()->count());
        $this->actingAs($this->employerUser)->post("/api/employer/billing/orders/{$order->id}/payments", [
            'payment_method' => 'bank_transfer', 'proof' => UploadedFile::fake()->create('receipt.txt', 20, 'text/plain'),
        ], ['Accept' => 'application/json'])->assertUnprocessable();
        $this->assertSame(0, Payment::query()->count());

        $payment = $this->submitReceipt($order);
        $this->actingAs($this->employerUser)->getJson('/api/employer/billing')->assertOk()
            ->assertJsonPath('orders.0.latest_payment_status', 'pending')
            ->assertJsonPath('orders.0.payments.0.reference', 'TRANSFER-1');
        $this->actingAs($this->employerUser)->post("/api/employer/billing/orders/{$order->id}/payments", [
            'payment_method' => 'bank_transfer', 'proof' => UploadedFile::fake()->create('receipt.pdf', 20, 'application/pdf'),
        ], ['Accept' => 'application/json'])->assertUnprocessable();
        $this->assertSame(1, Payment::query()->count());
        $this->actingAs($this->employerUser)->patchJson("/api/employer/billing/orders/{$order->id}/cancel")->assertUnprocessable();

        $otherUser = User::create(['name' => 'Other Employer', 'email' => 'other-billing@example.test', 'password' => 'password123', 'role' => 'employer', 'status' => true]);
        Employer::create(['user_id' => $otherUser->id, 'company_name' => 'Other Co', 'verification_status' => 'pending']);
        $this->actingAs($otherUser)->post("/api/employer/billing/orders/{$order->id}/payments", [
            'payment_method' => 'bank_transfer', 'proof' => UploadedFile::fake()->create('receipt.pdf', 20, 'application/pdf'),
        ], ['Accept' => 'application/json'])->assertNotFound();
        $this->actingAs($otherUser)->patchJson("/api/employer/billing/orders/{$order->id}/cancel")->assertNotFound();
        $this->assertSame('pending', $payment->fresh()->status);

        $this->actingAs($this->admin)->patchJson("/api/admin/billing/payments/{$payment->id}/review", ['decision' => 'reject', 'notes' => 'Close test receipt.'])->assertOk();
        $this->actingAs($this->employerUser)->patchJson("/api/employer/billing/orders/{$order->id}/cancel")->assertOk();
        $pendingOrder = $this->createOrder();
        $this->actingAs($this->employerUser)->patchJson("/api/employer/billing/orders/{$pendingOrder->id}/cancel")->assertOk();
        $this->assertDatabaseHas('orders', ['id' => $pendingOrder->id, 'status' => 'cancelled']);
    }

    public function test_full_and_excess_refunds_update_payment_entitlements_and_audit(): void
    {
        $order = $this->createOrder();
        $payment = $this->submitReceipt($order);
        $this->actingAs($this->admin)->patchJson("/api/admin/billing/payments/{$payment->id}/review", ['decision' => 'approve'])->assertOk();

        $this->actingAs($this->admin)->postJson("/api/admin/billing/payments/{$payment->id}/refunds", ['amount' => 0, 'reason' => 'Invalid test refund amount'])->assertUnprocessable();
        $partial = $payment->amount - 1;
        $this->actingAs($this->admin)->postJson("/api/admin/billing/payments/{$payment->id}/refunds", ['amount' => $partial, 'reason' => 'Sandbox partial refund'])->assertOk();
        $this->assertDatabaseHas('payments', ['id' => $payment->id, 'status' => 'partially_refunded']);
        $this->actingAs($this->admin)->getJson('/api/admin/billing/payments')->assertJsonPath('payments.data.0.refund_remaining', 1);
        $this->actingAs($this->admin)->postJson("/api/admin/billing/payments/{$payment->id}/refunds", ['amount' => 2, 'reason' => 'Exceeds remainder'])->assertUnprocessable();
        $this->actingAs($this->admin)->postJson("/api/admin/billing/payments/{$payment->id}/refunds", ['amount' => 1, 'reason' => 'Sandbox final refund'])->assertOk();
        $this->assertDatabaseHas('payments', ['id' => $payment->id, 'status' => 'refunded']);
        $this->assertDatabaseHas('orders', ['id' => $order->id, 'status' => 'refunded']);
        $this->assertDatabaseHas('invoices', ['order_id' => $order->id, 'status' => 'refunded']);
        $this->assertDatabaseHas('subscriptions', ['employer_id' => $this->employer->id, 'status' => 'cancelled']);
        $this->assertDatabaseHas('admin_audit_logs', ['administrator_id' => $this->admin->id, 'action' => 'payment.refund_recorded']);
    }

    public function test_admin_payment_monitoring_is_authorized_searchable_and_paginated(): void
    {
        $order = $this->createOrder();
        $payment = $this->submitReceipt($order, 'UNIQUE-REF-42');
        $this->app['auth']->logout();
        $this->getJson('/api/admin/billing/payments')->assertUnauthorized();
        $this->actingAs($this->employerUser)->getJson('/api/admin/billing/payments')->assertForbidden();
        $this->actingAs($this->admin)->getJson('/api/admin/billing/payments?q=UNIQUE-REF-42&status=pending')
            ->assertOk()->assertJsonPath('payments.total', 1)->assertJsonPath('payments.data.0.reference', 'UNIQUE-REF-42');
        $this->actingAs($this->admin)->getJson('/api/admin/billing/payments?status=invalid')->assertUnprocessable();
        $this->assertSame('pending', $payment->fresh()->status);
    }

    private function createOrder(): Order
    {
        $response = $this->actingAs($this->employerUser)->postJson('/api/employer/billing/orders', ['plan_slug' => 'professional'])->assertCreated();
        return Order::findOrFail($response->json('order.id'));
    }

    private function submitReceipt(Order $order, string $reference = 'TRANSFER-1'): Payment
    {
        $this->actingAs($this->employerUser)->post("/api/employer/billing/orders/{$order->id}/payments", [
            'payment_method' => 'bank_transfer',
            'reference_number' => $reference,
            'proof' => UploadedFile::fake()->create('receipt.pdf', 20, 'application/pdf'),
        ], ['Accept' => 'application/json'])->assertCreated();
        return Payment::query()->where('order_id', $order->id)->latest('id')->firstOrFail();
    }
}
