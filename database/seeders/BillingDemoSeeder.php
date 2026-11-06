<?php

namespace Database\Seeders;

use App\Models\BillingProduct;
use App\Models\Employer;
use App\Models\Invoice;
use App\Models\Job;
use App\Models\JobCategory;
use App\Models\JobPromotion;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\PricingPlan;
use App\Models\Subscription;
use App\Models\SubscriptionItem;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use RuntimeException;

class BillingDemoSeeder extends Seeder
{
    private const MARKER = '[FICTIONAL BILLING DEMO]';

    public function run(): void
    {
        if (app()->environment('production')) {
            throw new RuntimeException('Fictional billing demo data cannot be seeded in production.');
        }

        $password = (string) env('DEMO_BILLING_PASSWORD');
        if (strlen($password) < 12) {
            throw new RuntimeException('Set DEMO_BILLING_PASSWORD to a local-only password of at least 12 characters before running this seeder.');
        }

        $plans = PricingPlan::query()->whereIn('slug', ['free', 'professional', 'enterprise'])->get()->keyBy('slug');
        $product = BillingProduct::query()->where('slug', 'featured-job-7-days')->first();
        if (! $plans->has('free') || ! $plans->has('professional') || ! $plans->has('enterprise') || ! $product) {
            throw new RuntimeException('The existing billing catalogue is incomplete. Verify the platform catalogue before seeding; this seeder will not create or change plans or prices.');
        }

        DB::transaction(function () use ($password, $plans, $product): void {
            $accounts = [];
            foreach ($this->accounts() as $scenario => $details) {
                $accounts[$scenario] = $this->account($details, $password);
            }

            $this->ensureOrder($accounts['paid'], 'DEMO-BILLING-PAID-2026', $plans['professional'], 'paid');
            $this->ensureSubscription($accounts['paid'], $plans['professional'], 'active', now()->subDays(5), now()->addDays(25));

            $this->ensureOrder($accounts['pending'], 'DEMO-BILLING-PENDING-2026', $plans['professional'], 'pending');
            $this->ensureOrder($accounts['failed'], 'DEMO-BILLING-FAILED-2026', $plans['professional'], 'failed');

            $expiredOrder = $this->ensureOrder($accounts['expired'], 'DEMO-BILLING-EXPIRED-2026', $plans['professional'], 'paid', now()->subMonths(2));
            $this->ensureSubscription($accounts['expired'], $plans['professional'], 'expired', now()->subMonths(2), now()->subMonth(), $expiredOrder);

            $this->ensurePromotionPurchase($accounts['featured'], $product);
        });

        $this->command?->info('Billing demo scenarios are ready. No payment gateway, notification, or live charge was used.');
        foreach ($this->accounts() as $details) {
            $this->command?->line($details['email'].' (password: the value supplied in DEMO_BILLING_PASSWORD)');
        }
    }

    private function accounts(): array
    {
        return [
            'free' => ['email' => 'demo-billing-free@example.test', 'name' => 'Demo Free Plan Contact', 'company' => '[DEMO] Shwe Pyi Digital Services', 'scenario' => 'Free plan with no paid order.'],
            'paid' => ['email' => 'demo-billing-active@example.test', 'name' => 'Demo Active Plan Contact', 'company' => '[DEMO] Irrawaddy Product Studio', 'scenario' => 'Paid Professional subscription, invoice, and payment history.'],
            'pending' => ['email' => 'demo-billing-pending@example.test', 'name' => 'Demo Pending Payment Contact', 'company' => '[DEMO] Golden Delta Retail Systems', 'scenario' => 'Professional order awaiting manual receipt review.'],
            'failed' => ['email' => 'demo-billing-failed@example.test', 'name' => 'Demo Failed Payment Contact', 'company' => '[DEMO] Thanlwin Business Software', 'scenario' => 'Failed Professional payment available for a retry demonstration.'],
            'expired' => ['email' => 'demo-billing-expired@example.test', 'name' => 'Demo Expired Plan Contact', 'company' => '[DEMO] Mandalay Analytics Works', 'scenario' => 'Historical paid Professional subscription that has expired.'],
            'featured' => ['email' => 'demo-billing-featured@example.test', 'name' => 'Demo Featured Job Contact', 'company' => '[DEMO] Ayeyar Digital Careers', 'scenario' => 'Paid seven-day featured-job promotion.'],
        ];
    }

    private function account(array $details, string $password): Employer
    {
        $user = User::query()->where('email', $details['email'])->first();
        if (! $user) {
            $user = User::create([
                'name' => $details['name'],
                'email' => $details['email'],
                'password' => Hash::make($password),
                'role' => 'employer',
                'status' => true,
            ]);
        } elseif ($user->role !== 'employer' || ! str_contains((string) Employer::query()->where('user_id', $user->id)->value('company_description'), self::MARKER)) {
            throw new RuntimeException("Reserved demo email [{$details['email']}] is already used by a non-demo account. No records were changed.");
        }

        $employer = Employer::query()->where('user_id', $user->id)->first();
        if (! $employer) {
            $employer = Employer::create([
                'user_id' => $user->id,
                'company_name' => $details['company'],
                'company_description' => self::MARKER.' Fictional company account for demonstration: '.$details['scenario'],
                'location' => 'Yangon, Myanmar',
                'is_verified' => false,
                'verification_status' => 'unverified',
            ]);
        } elseif ($employer->company_name !== $details['company'] || ! str_contains((string) $employer->company_description, self::MARKER)) {
            throw new RuntimeException("Demo employer [{$details['email']}] no longer matches its seeder marker. No records were changed.");
        }

        return $employer;
    }

    private function ensureOrder(Employer $employer, string $number, PricingPlan $plan, string $scenario, $createdAt = null): Order
    {
        $order = Order::query()->where('order_number', $number)->first();
        if ($order) {
            $this->assertDemoOrder($order, $employer);

            return $order;
        }

        $user = $employer->user;
        $companySnapshot = [
            'contact_name' => $user->name,
            'contact_email' => $user->email,
            'company_name' => $employer->company_name,
            'demo_marker' => self::MARKER,
        ];
        $amount = (int) $plan->price_amount;
        $isPending = $scenario === 'pending';
        $isFailed = $scenario === 'failed';
        $orderStatus = $isPending ? 'awaiting_review' : ($isFailed ? 'payment_failed' : 'paid');
        $paidAt = $isPending || $isFailed ? null : ($createdAt ?? now()->subDays(5));

        $order = Order::create([
            'order_number' => $number,
            'user_id' => $user->id,
            'employer_id' => $employer->id,
            'status' => $orderStatus,
            'currency' => $plan->currency,
            'subtotal_amount' => $amount,
            'discount_amount' => 0,
            'total_amount' => $amount,
            'billing_snapshot' => [...$companySnapshot, 'demo_scenario' => $scenario],
            'paid_at' => $paidAt,
        ]);
        $item = OrderItem::create([
            'order_id' => $order->id,
            'pricing_plan_id' => $plan->id,
            'item_type' => 'subscription',
            'item_name' => $plan->name.' plan',
            'quantity' => 1,
            'unit_amount' => $amount,
            'total_amount' => $amount,
            'item_snapshot' => ['billing_interval' => $plan->billing_interval, 'interval_count' => $plan->interval_count, 'demo_marker' => self::MARKER],
        ]);

        Payment::create([
            'order_id' => $order->id,
            'user_id' => $user->id,
            'payment_method' => 'bank_transfer',
            'payment_provider' => 'demo_manual_bank_transfer',
            'status' => $isPending ? 'pending' : ($isFailed ? 'failed' : 'paid'),
            'amount' => $amount,
            'currency' => $plan->currency,
            'reference_number' => 'DEMO-'.Str::upper(Str::after($number, 'DEMO-BILLING-')),
            'failure_reason' => $isFailed ? 'DEMO: receipt was not verified; no real funds were transferred.' : null,
            'submitted_at' => $createdAt ?? now()->subDays(5),
            'paid_at' => $isPending || $isFailed ? null : $paidAt,
            'failed_at' => $isFailed ? now()->subDays(4) : null,
        ]);

        if (! $isPending && ! $isFailed) {
            $this->ensureInvoice($order, $companySnapshot, $createdAt ?? now()->subDays(5));
        }

        $item->forceFill(['created_at' => $createdAt ?? now()->subDays(5)])->saveQuietly();
        $order->forceFill(['created_at' => $createdAt ?? now()->subDays(5)])->saveQuietly();

        return $order->refresh();
    }

    private function ensureInvoice(Order $order, array $snapshot, $issuedAt): Invoice
    {
        return Invoice::query()->firstOrCreate(['order_id' => $order->id], [
            'invoice_number' => 'DEMO-INV-'.Str::upper(Str::after($order->order_number, 'DEMO-BILLING-')),
            'user_id' => $order->user_id,
            'status' => 'paid',
            'currency' => $order->currency,
            'subtotal_amount' => $order->subtotal_amount,
            'discount_amount' => $order->discount_amount,
            'total_amount' => $order->total_amount,
            'bill_to_snapshot' => [...$snapshot, 'demo_marker' => self::MARKER],
            'issued_at' => $issuedAt,
            'paid_at' => $order->paid_at,
        ]);
    }

    private function ensureSubscription(Employer $employer, PricingPlan $plan, string $status, $startsAt, $endsAt, ?Order $order = null): Subscription
    {
        $subscription = Subscription::query()->where('employer_id', $employer->id)->where('pricing_plan_id', $plan->id)->where('status', $status)->first();
        if (! $subscription) {
            $subscription = Subscription::create([
                'employer_id' => $employer->id,
                'pricing_plan_id' => $plan->id,
                'status' => $status,
                'price_amount' => $plan->price_amount,
                'currency' => $plan->currency,
                'billing_interval' => $plan->billing_interval,
                'starts_at' => $startsAt,
                'ends_at' => $endsAt,
            ]);
        }

        $order ??= Order::query()->where('employer_id', $employer->id)->where('status', 'paid')->latest('id')->first();
        $orderItem = $order?->items()->where('pricing_plan_id', $plan->id)->first();
        $itemStatus = $status === 'expired' ? 'expired' : 'active';
        if (! $subscription->items()->exists()) {
            SubscriptionItem::create([
                'subscription_id' => $subscription->id,
                'order_item_id' => $orderItem?->id,
                'pricing_plan_id' => $plan->id,
                'price_amount' => $plan->price_amount,
                'currency' => $plan->currency,
                'starts_at' => $startsAt,
                'ends_at' => $endsAt,
                'status' => $itemStatus,
            ]);
        }

        return $subscription;
    }

    private function ensurePromotionPurchase(Employer $employer, BillingProduct $product): void
    {
        $orderNumber = 'DEMO-BILLING-FEATURED-2026';
        $existingOrder = Order::query()->where('order_number', $orderNumber)->first();
        if ($existingOrder) {
            $this->assertDemoOrder($existingOrder, $employer);

            return;
        }

        $category = JobCategory::query()->where('is_active', true)->orderBy('sort_order')->first();
        if (! $category) {
            throw new RuntimeException('An active job category is required for the featured-job billing scenario. No category will be created or changed.');
        }

        $job = Job::query()->where('employer_id', $employer->id)->where('title', '[DEMO] Billing plan showcase vacancy')->first();
        if (! $job) {
            $job = Job::create([
                'employer_id' => $employer->id,
                'category_id' => $category->id,
                'category' => $category->name,
                'title' => '[DEMO] Billing plan showcase vacancy',
                'description' => self::MARKER.' Fictional vacancy created to demonstrate the existing featured-job purchase and entitlement display.',
                'requirements' => 'Demonstration listing only. No real applications are being solicited.',
                'location' => 'Yangon, Myanmar',
                'employment_type' => 'full_time',
                'experience_level' => 'mid',
                'work_mode' => 'on_site',
                'salary_min' => null,
                'salary_max' => null,
                'salary_currency' => $product->currency,
                'status' => 'published',
                'posted_at' => now()->subDay(),
                'application_deadline' => now()->addDays(30),
            ]);
        } elseif (! str_contains((string) $job->description, self::MARKER)) {
            throw new RuntimeException('The reserved demo billing job title is already used by a non-demo listing. No records were changed.');
        }

        $user = $employer->user;
        $snapshot = ['contact_name' => $user->name, 'contact_email' => $user->email, 'company_name' => $employer->company_name, 'demo_marker' => self::MARKER, 'demo_scenario' => 'featured_job'];
        $amount = (int) $product->price_amount;
        $order = Order::create([
            'order_number' => $orderNumber,
            'user_id' => $user->id,
            'employer_id' => $employer->id,
            'status' => 'paid',
            'currency' => $product->currency,
            'subtotal_amount' => $amount,
            'discount_amount' => 0,
            'total_amount' => $amount,
            'billing_snapshot' => $snapshot,
            'paid_at' => now()->subDay(),
        ]);
        $item = OrderItem::create([
            'order_id' => $order->id,
            'billing_product_id' => $product->id,
            'job_id' => $job->id,
            'item_type' => $product->product_type,
            'item_name' => $product->name,
            'quantity' => 1,
            'unit_amount' => $amount,
            'total_amount' => $amount,
            'item_snapshot' => ['duration_days' => $product->duration_days, 'metadata' => $product->metadata, 'demo_marker' => self::MARKER],
        ]);
        Payment::create([
            'order_id' => $order->id,
            'user_id' => $user->id,
            'payment_method' => 'bank_transfer',
            'payment_provider' => 'demo_manual_bank_transfer',
            'status' => 'paid',
            'amount' => $amount,
            'currency' => $product->currency,
            'reference_number' => 'DEMO-FEATURED-2026',
            'submitted_at' => now()->subDay(),
            'paid_at' => now()->subDay(),
        ]);
        $this->ensureInvoice($order, $snapshot, now()->subDay());
        JobPromotion::firstOrCreate(['order_item_id' => $item->id], [
            'job_id' => $job->id,
            'employer_id' => $employer->id,
            'promotion_type' => $product->metadata['promotion_type'] ?? 'featured',
            'status' => 'active',
            'starts_at' => now()->subDay(),
            'ends_at' => now()->addDays(max(1, (int) $product->duration_days) - 1),
            'metadata' => ['product_name' => $product->name, 'demo_marker' => self::MARKER],
        ]);
    }

    private function assertDemoOrder(Order $order, Employer $employer): void
    {
        if ($order->employer_id !== $employer->id || ($order->billing_snapshot['demo_marker'] ?? null) !== self::MARKER) {
            throw new RuntimeException("Reserved demo order [{$order->order_number}] is not marked as this seeder's fictional data. No records were changed.");
        }
    }
}
