<?php

namespace App\Services;

use App\Models\Employer;
use App\Models\FeatureUsage;
use App\Models\PricingPlan;
use App\Models\Subscription;
use Illuminate\Support\Carbon;

class EmployerEntitlementService
{
    public function currentSubscription(Employer $employer): ?Subscription
    {
        return $employer->subscriptions()
            ->where('status', 'active')
            ->where('starts_at', '<=', now())
            ->where(fn ($query) => $query->whereNull('ends_at')->orWhere('ends_at', '>', now()))
            ->with(['plan.features'])
            ->latest('starts_at')
            ->first();
    }

    public function currentPlan(Employer $employer): ?PricingPlan
    {
        return $this->currentSubscription($employer)?->plan
            ?? PricingPlan::with('features')->where('slug', 'free')->first();
    }

    public function value(Employer $employer, string $feature, mixed $default = null): mixed
    {
        return $this->currentPlan($employer)?->feature($feature, $default) ?? $default;
    }

    public function monthStart(): Carbon
    {
        return now()->startOfMonth();
    }

    public function usage(Employer $employer, string $feature): int
    {
        return (int) $employer->featureUsages()
            ->where('feature_key', $feature)
            ->where('period_start', $this->monthStart())
            ->sum('quantity');
    }

    public function remaining(Employer $employer, string $feature, int $limit): ?int
    {
        if ($limit < 0) return null;
        return max(0, $limit - $this->usage($employer, $feature));
    }

    public function record(Employer $employer, string $feature, int $quantity = 1, ?string $resourceType = null, ?int $resourceId = null, array $context = []): FeatureUsage
    {
        return $employer->featureUsages()->create([
            'feature_key' => $feature,
            'resource_type' => $resourceType,
            'resource_id' => $resourceId,
            'quantity' => $quantity,
            'period_start' => $this->monthStart(),
            'period_end' => $this->monthStart()->copy()->addMonth(),
            'context' => $context ?: null,
        ]);
    }
}
