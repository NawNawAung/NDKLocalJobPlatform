<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PricingPlan extends Model
{
    protected $fillable = ['name', 'slug', 'description', 'plan_type', 'price_amount', 'currency', 'billing_interval', 'interval_count', 'duration_days', 'is_active', 'sort_order'];
    protected function casts(): array { return ['is_active' => 'boolean']; }
    public function features(): HasMany { return $this->hasMany(PlanFeature::class); }
    public function subscriptions(): HasMany { return $this->hasMany(Subscription::class); }
    public function feature(string $key, mixed $default = null): mixed { return $this->features->firstWhere('feature_key', $key)?->feature_value ?? $default; }
}
