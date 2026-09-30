<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderItem extends Model
{
    protected $fillable = ['order_id', 'pricing_plan_id', 'billing_product_id', 'job_id', 'item_type', 'item_name', 'quantity', 'unit_amount', 'total_amount', 'item_snapshot'];
    protected function casts(): array { return ['item_snapshot' => 'array']; }
    public function order(): BelongsTo { return $this->belongsTo(Order::class); }
    public function plan(): BelongsTo { return $this->belongsTo(PricingPlan::class, 'pricing_plan_id'); }
    public function product(): BelongsTo { return $this->belongsTo(BillingProduct::class, 'billing_product_id'); }
    public function job(): BelongsTo { return $this->belongsTo(Job::class); }
}
