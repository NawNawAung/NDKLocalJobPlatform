<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Subscription extends Model
{
    protected $fillable = ['employer_id', 'pricing_plan_id', 'status', 'price_amount', 'currency', 'billing_interval', 'starts_at', 'ends_at', 'cancelled_at'];
    protected function casts(): array { return ['starts_at' => 'datetime', 'ends_at' => 'datetime', 'cancelled_at' => 'datetime']; }
    public function employer(): BelongsTo { return $this->belongsTo(Employer::class); }
    public function plan(): BelongsTo { return $this->belongsTo(PricingPlan::class, 'pricing_plan_id'); }
    public function items(): HasMany { return $this->hasMany(SubscriptionItem::class); }
    public function isCurrent(): bool { return $this->status === 'active' && ($this->ends_at === null || $this->ends_at->isFuture()); }
}
