<?php

namespace App\Http\Controllers;

use App\Models\BillingProduct;
use App\Models\Employer;
use App\Models\FeatureUsage;
use App\Models\Invoice;
use App\Models\Job;
use App\Models\JobPromotion;
use App\Models\Notification;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\PlanFeature;
use App\Models\PricingPlan;
use App\Models\Promotion;
use App\Models\PromotionRedemption;
use App\Models\Refund;
use App\Models\Subscription;
use App\Models\SubscriptionItem;
use App\Models\User;
use App\Services\EmployerEntitlementService;
use App\Services\AdminAuditLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\File;

class BillingController extends Controller
{
    public function __construct(private readonly EmployerEntitlementService $entitlements, private readonly AdminAuditLogger $audit) {}

    private function employer(Request $request): Employer
    {
        abort_unless($request->user()->status && $request->user()->role === 'employer' && $request->user()->employer, 403);
        return $request->user()->employer;
    }

    private function admin(Request $request): User
    {
        abort_unless($request->user()->status && $request->user()->role === 'admin', 403);
        return $request->user();
    }

    public function overview(Request $request): JsonResponse
    {
        $employer = $this->employer($request);
        $plan = $this->entitlements->currentPlan($employer);
        $subscription = $this->entitlements->currentSubscription($employer);
        $jobLimit = (int) ($plan?->feature('max_active_jobs', 1) ?? 1);
        $cvLimit = (int) ($plan?->feature('candidate_cv_downloads', 0) ?? 0);
        $periodStart = $this->entitlements->monthStart();

        return response()->json([
            'plan' => $plan ? [
                'id' => $plan->id, 'name' => $plan->name, 'slug' => $plan->slug,
                'description' => $plan->description, 'price_amount' => $plan->price_amount,
                'currency' => $plan->currency, 'billing_interval' => $plan->billing_interval,
                'features' => $plan->features->mapWithKeys(fn (PlanFeature $feature) => [$feature->feature_key => $feature->feature_value]),
                'subscription_ends_at' => $subscription?->ends_at?->toIso8601String(),
            ] : null,
            'usage' => [
                'active_jobs' => Job::published()->where('employer_id', $employer->id)->count(),
                'active_jobs_limit' => $jobLimit,
                'candidate_cv_downloads' => $this->entitlements->usage($employer, 'candidate_cv_downloads'),
                'candidate_cv_downloads_limit' => $cvLimit,
                'period_start' => $periodStart->toDateString(),
            ],
            'plans' => PricingPlan::query()->where('is_active', true)->orderBy('sort_order')->with('features')->get()
                ->map(fn (PricingPlan $item) => [
                    'id' => $item->id, 'name' => $item->name, 'slug' => $item->slug,
                    'description' => $item->description, 'price_amount' => $item->price_amount,
                    'currency' => $item->currency, 'billing_interval' => $item->billing_interval,
                    'features' => $item->features->mapWithKeys(fn (PlanFeature $feature) => [$feature->feature_key => $feature->feature_value]),
                ]),
            'products' => BillingProduct::query()->where('is_active', true)->orderBy('sort_order')->get()
                ->map(fn (BillingProduct $product) => [
                    'slug' => $product->slug, 'name' => $product->name, 'description' => $product->description,
                    'product_type' => $product->product_type, 'price_amount' => $product->price_amount,
                    'currency' => $product->currency, 'duration_days' => $product->duration_days,
                ]),
            'jobs' => $employer->jobs()->where('status', 'published')->orderBy('title')->get(['id', 'title']),
            'orders' => $employer->orders()->with(['items', 'payments', 'invoice'])->latest()->limit(20)->get()
                ->map(fn (Order $order) => [
                    'id' => $order->id, 'order_number' => $order->order_number, 'status' => $order->status,
                    'currency' => $order->currency, 'total_amount' => $order->total_amount,
                    'created_at' => $order->created_at?->toIso8601String(),
                    'items' => $order->items->map(fn (OrderItem $item) => ['name' => $item->item_name, 'quantity' => $item->quantity]),
                    'latest_payment_status' => $order->payments->sortByDesc('id')->first()?->status,
                    'invoice_id' => $order->invoice?->id,
                ]),
            'transfer_instructions' => [
                'bank_name' => config('services.billing.bank_name'),
                'account_name' => config('services.billing.account_name'),
                'account_number' => config('services.billing.account_number'),
                'payment_note' => config('services.billing.payment_note'),
                'configured' => filled(config('services.billing.bank_name')) && filled(config('services.billing.account_number')),
            ],
        ]);
    }

    public function createOrder(Request $request): JsonResponse
    {
        $employer = $this->employer($request);
        $data = $request->validate([
            'plan_slug' => ['nullable', 'string', 'exists:pricing_plans,slug', 'required_without:product_slug'],
            'product_slug' => ['nullable', 'string', 'exists:billing_products,slug', 'required_without:plan_slug'],
            'job_id' => ['nullable', 'integer'],
            'promotion_code' => ['nullable', 'string', 'max:40'],
        ]);
        abort_if(! empty($data['plan_slug']) && ! empty($data['product_slug']), 422, 'Choose one plan or one add-on per order.');

        $item = null;
        $plan = null;
        $product = null;
        $job = null;
        if (! empty($data['plan_slug'])) {
            $plan = PricingPlan::query()->where('slug', $data['plan_slug'])->where('is_active', true)->firstOrFail();
            abort_if($plan->billing_interval === 'custom' || $plan->price_amount <= 0, 422, 'This plan requires a custom quote or is already free.');
            $item = ['type' => 'subscription', 'name' => $plan->name.' plan', 'amount' => $plan->price_amount, 'plan' => $plan];
        } else {
            $product = BillingProduct::query()->where('slug', $data['product_slug'])->where('is_active', true)->firstOrFail();
            abort_unless($product->product_type === 'job_promotion', 422, 'This product is not available for employer checkout.');
            $job = $employer->jobs()->whereKey($data['job_id'] ?? null)->where('status', 'published')->first();
            abort_unless($job, 422, 'Select one of your published jobs to promote.');
            $item = ['type' => 'job_promotion', 'name' => $product->name, 'amount' => $product->price_amount, 'product' => $product, 'job' => $job];
        }

        $order = DB::transaction(function () use ($request, $employer, $data, $item, $plan, $product, $job) {
            $pending = $employer->orders()->whereIn('status', ['pending_payment', 'awaiting_review'])->exists();
            abort_if($pending, 422, 'Complete or cancel your pending order before creating another.');

            $subtotal = (int) $item['amount'];
            $discount = 0;
            $promotion = null;
            if (filled($data['promotion_code'] ?? null)) {
                $promotion = Promotion::query()->whereRaw('upper(code) = ?', [Str::upper($data['promotion_code'])])->lockForUpdate()->first();
                abort_unless($promotion && $promotion->is_active, 422, 'That promotion code is not available.');
                abort_if($promotion->starts_at && $promotion->starts_at->isFuture(), 422, 'That promotion code is not active yet.');
                abort_if($promotion->ends_at && $promotion->ends_at->isPast(), 422, 'That promotion code has expired.');
                abort_unless($promotion->currency === ($plan?->currency ?? $product?->currency ?? 'MMK'), 422, 'That promotion code does not apply to this currency.');
                abort_if($promotion->max_redemptions !== null && $promotion->redemptions_count >= $promotion->max_redemptions, 422, 'That promotion code has reached its redemption limit.');
                abort_if($promotion->redemptions()->where('user_id', $request->user()->id)->exists(), 422, 'You have already used that promotion code.');
                $discount = $promotion->discount_type === 'percent'
                    ? (int) floor($subtotal * min(100, $promotion->discount_value) / 100)
                    : min($subtotal, $promotion->discount_value);
            }

            $order = Order::create([
                'order_number' => 'NDK-'.now()->format('Ymd').'-'.Str::upper(Str::random(8)),
                'user_id' => $request->user()->id,
                'employer_id' => $employer->id,
                'status' => 'pending_payment',
                'currency' => $plan?->currency ?? $product?->currency ?? 'MMK',
                'subtotal_amount' => $subtotal,
                'discount_amount' => $discount,
                'total_amount' => $subtotal - $discount,
                'billing_snapshot' => array_filter([
                    'contact_name' => $request->user()->name, 'contact_email' => $request->user()->email,
                    'company_name' => $employer->company_name, 'promotion_id' => $promotion?->id,
                ], fn ($value) => $value !== null),
            ]);
            $orderItem = $order->items()->create([
                'pricing_plan_id' => $plan?->id,
                'billing_product_id' => $product?->id,
                'job_id' => $job?->id,
                'item_type' => $item['type'],
                'item_name' => $item['name'],
                'quantity' => 1,
                'unit_amount' => $subtotal,
                'total_amount' => $subtotal,
                'item_snapshot' => $plan ? ['billing_interval' => $plan->billing_interval, 'interval_count' => $plan->interval_count] : ['duration_days' => $product?->duration_days, 'metadata' => $product?->metadata],
            ]);
            if ($promotion) {
                $order->update(['billing_snapshot' => [...$order->billing_snapshot, 'promotion_code' => $promotion->code]]);
            }
            return $order;
        });

        return response()->json(['message' => 'Order created. Submit a bank-transfer receipt for review.', 'order' => ['id' => $order->id, 'order_number' => $order->order_number, 'total_amount' => $order->total_amount, 'currency' => $order->currency]], 201);
    }

    public function submitPayment(Request $request, Order $order): JsonResponse
    {
        $employer = $this->employer($request);
        abort_unless($order->employer_id === $employer->id, 404);
        abort_unless(in_array($order->status, ['pending_payment', 'payment_failed'], true), 422, 'This order is not accepting a payment submission.');
        abort_if($order->total_amount <= 0, 422, 'A zero-value order does not require payment.');
        abort_if($order->payments()->whereIn('status', ['pending', 'processing'])->exists(), 422, 'A payment is already awaiting review.');
        $data = $request->validate([
            'payment_method' => ['required', Rule::in(['bank_transfer'])],
            'reference_number' => ['nullable', 'string', 'max:120'],
            'proof' => ['required', File::types(['jpg', 'jpeg', 'png', 'pdf'])->max(5120)],
        ]);
        $path = $request->file('proof')->store('billing/payment-proofs', 'local');
        try {
            $payment = DB::transaction(function () use ($request, $order, $data, $path) {
                $payment = $order->payments()->create([
                    'user_id' => $request->user()->id,
                    'payment_method' => $data['payment_method'],
                    'payment_provider' => 'manual_bank_transfer',
                    'status' => 'pending',
                    'amount' => $order->total_amount,
                    'currency' => $order->currency,
                    'reference_number' => $data['reference_number'] ?? null,
                    'proof_path' => $path,
                    'submitted_at' => now(),
                ]);
                $order->update(['status' => 'awaiting_review']);
                return $payment;
            });
        } catch (\Throwable $exception) {
            Storage::disk('local')->delete($path);
            throw $exception;
        }
        return response()->json(['message' => 'Payment receipt submitted. The platform team will review it.', 'payment_status' => $payment->status], 201);
    }

    public function invoice(Request $request, Invoice $invoice)
    {
        abort_unless($request->user()->role === 'admin' || $invoice->user_id === $request->user()->id, 404);
        $invoice->load(['order.items', 'user']);
        return view('billing.invoice', compact('invoice'));
    }

    public function adminPayments(Request $request): JsonResponse
    {
        $this->admin($request);
        $payments = Payment::query()->with(['user:id,name,email', 'order.employer:id,company_name', 'order.items', 'reviewer:id,name'])
            ->latest()->paginate(40);
        return response()->json([
            'payments' => $payments->through(fn (Payment $payment) => [
                'id' => $payment->id, 'status' => $payment->status, 'method' => $payment->payment_method,
                'reference' => $payment->reference_number, 'amount' => $payment->amount, 'currency' => $payment->currency,
                'proof_url' => $payment->proof_path ? "/api/admin/billing/payments/{$payment->id}/proof" : null,
                'submitted_at' => $payment->submitted_at?->toIso8601String(), 'failure_reason' => $payment->failure_reason,
                'customer' => ['name' => $payment->user?->name, 'email' => $payment->user?->email],
                'company' => $payment->order?->employer?->company_name,
                'order' => ['id' => $payment->order?->id, 'number' => $payment->order?->order_number, 'status' => $payment->order?->status, 'items' => $payment->order?->items->pluck('item_name') ?? []],
                'reviewer' => $payment->reviewer?->name,
            ]),
        ]);
    }

    public function paymentProof(Request $request, Payment $payment)
    {
        $this->admin($request);
        abort_unless($payment->proof_path && Storage::disk('local')->exists($payment->proof_path), 404);
        return Storage::disk('local')->response($payment->proof_path);
    }

    public function reviewPayment(Request $request, Payment $payment): JsonResponse
    {
        $admin = $this->admin($request);
        $data = $request->validate([
            'decision' => ['required', Rule::in(['approve', 'reject'])],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);
        $before = ['payment_status' => $payment->status, 'order_status' => $payment->order?->status];
        DB::transaction(function () use ($payment, $admin, $data) {
            $payment = Payment::query()->lockForUpdate()->with(['order.items.plan', 'order.items.product', 'order.employer'])->findOrFail($payment->id);
            abort_unless($payment->status === 'pending' && $payment->order?->status === 'awaiting_review', 422, 'This payment has already been reviewed.');
            if ($data['decision'] === 'reject') {
                $payment->update(['status' => 'failed', 'reviewed_by' => $admin->id, 'failure_reason' => $data['notes'] ?: 'Receipt could not be verified.', 'failed_at' => now()]);
                $payment->order->update(['status' => 'payment_failed']);
                if ($payment->order->employer?->user) {
                    Notification::sendNotification($payment->order->employer->user, 'Payment receipt needs attention', $data['notes'] ?: 'Your transfer receipt could not be verified. Submit a new receipt from billing.', null, 'billing', '/#billing');
                }
                return;
            }

            $promotionId = $payment->order->billing_snapshot['promotion_id'] ?? null;
            if ($promotionId && ! $payment->order->promotionRedemption) {
                $promotion = Promotion::query()->lockForUpdate()->findOrFail($promotionId);
                abort_if(! $promotion->is_active || ($promotion->max_redemptions !== null && $promotion->redemptions_count >= $promotion->max_redemptions), 422, 'This promotion code is no longer available. Reject this payment receipt and ask the employer to create a new order.');
                abort_if($promotion->redemptions()->where('user_id', $payment->order->user_id)->exists(), 422, 'This user has already redeemed the promotion code.');
            }

            $payment->update(['status' => 'paid', 'reviewed_by' => $admin->id, 'paid_at' => now(), 'failure_reason' => null]);
            $order = $payment->order;
            $order->update(['status' => 'paid', 'paid_at' => now()]);
            $invoice = Invoice::firstOrCreate(['order_id' => $order->id], [
                'invoice_number' => 'INV-'.now()->format('Ymd').'-'.Str::upper(Str::random(8)),
                'user_id' => $order->user_id,
                'status' => 'paid',
                'currency' => $order->currency,
                'subtotal_amount' => $order->subtotal_amount,
                'discount_amount' => $order->discount_amount,
                'total_amount' => $order->total_amount,
                'bill_to_snapshot' => $order->billing_snapshot,
                'issued_at' => now(),
                'paid_at' => now(),
            ]);

            foreach ($order->items as $item) {
                if ($item->item_type === 'subscription' && $item->plan && $order->employer) {
                    $this->activateSubscription($order->employer, $item);
                } elseif ($item->item_type === 'job_promotion' && $item->job && $order->employer) {
                    abort_unless($item->job->employer_id === $order->employer_id && $item->job->status === 'published', 422, 'The promoted job is no longer eligible.');
                    $productMeta = $item->product?->metadata ?? [];
                    $type = $productMeta['promotion_type'] ?? 'featured';
                    $prior = JobPromotion::query()->where('job_id', $item->job_id)->where('promotion_type', $type)
                        ->whereIn('status', ['active', 'scheduled'])->where('ends_at', '>', now())->latest('ends_at')->first();
                    $start = $prior && $prior->ends_at->isFuture() ? $prior->ends_at->copy() : now();
                    $duration = (int) ($item->product?->duration_days ?? 7);
                    JobPromotion::create([
                        'job_id' => $item->job_id, 'employer_id' => $order->employer_id, 'order_item_id' => $item->id,
                        'promotion_type' => $type, 'status' => $start->isFuture() ? 'scheduled' : 'active',
                        'starts_at' => $start, 'ends_at' => $start->copy()->addDays($duration),
                        'metadata' => ['product_name' => $item->item_name],
                    ]);
                }
            }

            $promotionCode = $order->billing_snapshot['promotion_id'] ?? null;
            if ($promotionCode) {
                $promotion = Promotion::query()->lockForUpdate()->find($promotionCode);
                if ($promotion && ! $order->promotionRedemption) {
                    PromotionRedemption::create(['promotion_id' => $promotion->id, 'order_id' => $order->id, 'user_id' => $order->user_id, 'discount_amount' => $order->discount_amount, 'redeemed_at' => now()]);
                    $promotion->increment('redemptions_count');
                }
            }
            if ($order->employer?->user) {
                Notification::sendNotification($order->employer->user, 'Payment approved', "Order {$order->order_number} has been paid and its items are active.", null, 'billing', '/#billing');
            }
        });
        $payment->refresh()->load('order');
        $this->audit->record($request, $admin, 'payment.reviewed.'.$data['decision'], $payment, $before, ['payment_status' => $payment->status, 'order_status' => $payment->order?->status, 'notes' => $data['notes'] ?? null]);
        return response()->json(['message' => $data['decision'] === 'approve' ? 'Payment approved and order fulfilled.' : 'Payment rejected; the employer can submit a new receipt.']);
    }

    private function activateSubscription(Employer $employer, OrderItem $item): void
    {
        $plan = $item->plan;
        $current = $this->entitlements->currentSubscription($employer);
        $startsAt = now();
        if ($current && $current->pricing_plan_id === $plan->id && $current->ends_at?->isFuture()) {
            $startsAt = $current->ends_at->copy();
        }
        $endsAt = match ($plan->billing_interval) {
            'year' => $startsAt->copy()->addYears($plan->interval_count),
            'month' => $startsAt->copy()->addMonths($plan->interval_count),
            'week' => $startsAt->copy()->addWeeks($plan->interval_count),
            default => null,
        };
        $subscription = Subscription::create([
            'employer_id' => $employer->id, 'pricing_plan_id' => $plan->id, 'status' => 'active',
            'price_amount' => $item->total_amount, 'currency' => $item->order->currency,
            'billing_interval' => $plan->billing_interval, 'starts_at' => $startsAt, 'ends_at' => $endsAt,
        ]);
        SubscriptionItem::create([
            'subscription_id' => $subscription->id, 'order_item_id' => $item->id, 'pricing_plan_id' => $plan->id,
            'price_amount' => $item->total_amount, 'currency' => $item->order->currency,
            'starts_at' => $startsAt, 'ends_at' => $endsAt, 'status' => 'active',
        ]);
    }

    public function recordRefund(Request $request, Payment $payment): JsonResponse
    {
        $admin = $this->admin($request);
        abort_unless(in_array($payment->status, ['paid', 'partially_refunded'], true), 422, 'Only captured payments can be refunded.');
        $data = $request->validate(['amount' => ['required', 'integer', 'min:1'], 'reason' => ['required', 'string', 'max:2000'], 'reference_number' => ['nullable', 'string', 'max:120']]);
        $before = ['status' => $payment->status, 'amount' => $payment->amount];
        $refund = DB::transaction(function () use ($payment, $admin, $data) {
            $payment = Payment::query()->lockForUpdate()->with('order.items')->findOrFail($payment->id);
            abort_unless(in_array($payment->status, ['paid', 'partially_refunded'], true), 422, 'Only captured payments can be refunded.');
            $refunded = (int) $payment->refunds()->where('status', 'processed')->sum('amount');
            abort_if($refunded + $data['amount'] > $payment->amount, 422, 'Refund amount exceeds the amount paid.');
            $refund = Refund::create([...$data, 'payment_id' => $payment->id, 'processed_by' => $admin->id, 'currency' => $payment->currency, 'status' => 'processed', 'processed_at' => now()]);
            $newTotal = $refunded + $refund->amount;
            $fullyRefunded = $newTotal >= $payment->amount;
            $payment->update(['status' => $fullyRefunded ? 'refunded' : 'partially_refunded']);
            if ($payment->order) {
                $payment->order->update(['status' => $fullyRefunded ? 'refunded' : 'partially_refunded']);
                $payment->order->invoice?->update(['status' => $fullyRefunded ? 'refunded' : 'partially_refunded']);
                if ($fullyRefunded) {
                    $itemIds = $payment->order->items->pluck('id');
                    $subscriptionIds = SubscriptionItem::query()->whereIn('order_item_id', $itemIds)->pluck('subscription_id');
                    Subscription::query()->whereIn('id', $subscriptionIds)->where('status', 'active')->update(['status' => 'cancelled', 'cancelled_at' => now()]);
                    SubscriptionItem::query()->whereIn('subscription_id', $subscriptionIds)->where('status', 'active')->update(['status' => 'cancelled']);
                    JobPromotion::query()->whereIn('order_item_id', $itemIds)->whereIn('status', ['active', 'scheduled'])->update(['status' => 'cancelled']);
                }
            }
            return $refund;
        });
        $payment->refresh();
        $this->audit->record($request, $admin, 'payment.refund_recorded', $payment, $before, ['status' => $payment->status, 'refund_id' => $refund->id, 'refund_amount' => $refund->amount, 'reason' => $refund->reason]);
        return response()->json(['message' => 'Manual refund recorded. Ensure the funds were returned outside the platform before recording it.', 'refund_id' => $refund->id]);
    }

    public function talent(Request $request): JsonResponse
    {
        $employer = $this->employer($request);
        $data = $request->validate([
            'keyword' => ['nullable', 'string', 'max:100'], 'region_id' => ['nullable', 'integer', 'exists:regions,id'],
            'township_id' => ['nullable', 'integer', 'exists:townships,id'], 'experience_min' => ['nullable', 'integer', 'min:0', 'max:60'],
            'employment_type' => ['nullable', Rule::in(['full_time', 'part_time', 'contract', 'temporary', 'internship'])],
        ]);
        $limit = (int) $this->entitlements->value($employer, 'candidate_preview_limit', 5);
        $used = $this->entitlements->usage($employer, 'candidate_profile_preview');
        $available = $limit < 0 ? PHP_INT_MAX : max(0, $limit - $used);
        if ($available === 0) {
            return response()->json(['message' => 'You have used this month’s candidate profile previews. Upgrade to Professional for full candidate search.', 'upgrade_required' => true, 'candidates' => [], 'remaining' => 0], 403);
        }

        $viewedIds = $limit < 0 ? [] : $employer->featureUsages()
            ->where('feature_key', 'candidate_profile_preview')->where('period_start', $this->entitlements->monthStart())
            ->where('resource_type', 'job_seeker')->pluck('resource_id')->filter()->unique()->all();
        $query = \App\Models\JobSeeker::query()->where('status', true)->where('profile_searchable', true)
            ->with(['user:id,name,email', 'region:id,name', 'township:id,name'])
            ->when($viewedIds, fn ($query) => $query->whereNotIn('id', $viewedIds))
            ->when($data['keyword'] ?? null, function ($query, $keyword) {
                $query->where(function ($q) use ($keyword) {
                    $q->where('professional_title', 'like', "%{$keyword}%")
                        ->orWhere('desired_job_title', 'like', "%{$keyword}%")
                        ->orWhereJsonContains('skills', $keyword);
                });
            })
            ->when($data['region_id'] ?? null, fn ($query, $id) => $query->where('region_id', $id))
            ->when($data['township_id'] ?? null, fn ($query, $id) => $query->where('township_id', $id))
            ->when(isset($data['experience_min']), fn ($query) => $query->where('years_experience', '>=', $data['experience_min']))
            ->when($data['employment_type'] ?? null, fn ($query, $type) => $query->where('employment_type', $type))
            ->orderByDesc('updated_at');
        $candidates = $query->limit(min(30, $available))->get();
        $canViewContact = (bool) $this->entitlements->value($employer, 'candidate_contact_view', false);
        $canDownload = (int) $this->entitlements->value($employer, 'candidate_cv_downloads', 0) !== 0;
        foreach ($candidates as $candidate) {
            $this->entitlements->record($employer, 'candidate_profile_preview', 1, 'job_seeker', $candidate->id);
        }
        return response()->json([
            'candidates' => $candidates->map(fn ($candidate) => [
                'id' => $candidate->id,
                'name' => $canViewContact ? $candidate->user?->name : 'Candidate profile',
                'email' => $canViewContact ? $candidate->user?->email : null,
                'title' => $candidate->professional_title ?: $candidate->desired_job_title,
                'experience' => $candidate->years_experience,
                'skills' => $candidate->skills ?? [],
                'location' => collect([$candidate->township?->name, $candidate->region?->name])->filter()->join(', '),
                'has_cv' => filled($candidate->cv_path),
                'cv_url' => $canDownload && filled($candidate->cv_path) ? "/api/employer/talent/{$candidate->id}/resume" : null,
                'portfolio_url' => $canViewContact ? $candidate->portfolio_url : null,
                'social_links' => $canViewContact ? ($candidate->social_links ?? []) : [],
                'employment_type' => $candidate->employment_type,
                'work_mode' => $candidate->work_mode,
            ]),
            'remaining' => $limit < 0 ? null : max(0, $available - $candidates->count()),
            'unlimited' => $limit < 0,
            'can_view_contact' => $canViewContact,
            'can_download_cv' => $canDownload,
            'upgrade_required' => ! $canViewContact,
        ]);
    }

    public function downloadTalentResume(Request $request, int $jobSeekerId)
    {
        $employer = $this->employer($request);
        $limit = (int) $this->entitlements->value($employer, 'candidate_cv_downloads', 0);
        $remaining = $this->entitlements->remaining($employer, 'candidate_cv_downloads', $limit);
        abort_if($remaining === 0, 403, 'Your plan has reached its monthly candidate CV download limit. Upgrade your plan to continue.');
        $candidate = \App\Models\JobSeeker::query()->whereKey($jobSeekerId)->where('status', true)->where('profile_searchable', true)->firstOrFail();
        abort_unless($candidate->cv_path && Storage::disk('local')->exists($candidate->cv_path), 404);
        $this->entitlements->record($employer, 'candidate_cv_downloads', 1, 'job_seeker', $candidate->id);
        return Storage::disk('local')->download($candidate->cv_path, basename($candidate->cv_original_name ?: $candidate->cv_path));
    }
}
