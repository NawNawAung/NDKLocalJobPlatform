<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SubscriptionItem extends Model
{
    protected $fillable = ['subscription_id', 'order_item_id', 'pricing_plan_id', 'price_amount', 'currency', 'starts_at', 'ends_at', 'status'];
    protected function casts(): array { return ['starts_at' => 'datetime', 'ends_at' => 'datetime']; }
    public function subscription(): BelongsTo { return $this->belongsTo(Subscription::class); }
    public function orderItem(): BelongsTo { return $this->belongsTo(OrderItem::class); }
    public function plan(): BelongsTo { return $this->belongsTo(PricingPlan::class, 'pricing_plan_id'); }
}
