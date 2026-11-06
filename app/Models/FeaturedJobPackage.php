<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FeaturedJobPackage extends Model
{
    protected $fillable = ['employer_id', 'order_item_id', 'slots_total', 'slots_remaining', 'promotion_duration_days', 'purchased_at', 'expires_at'];

    protected function casts(): array
    {
        return ['slots_total' => 'integer', 'slots_remaining' => 'integer', 'promotion_duration_days' => 'integer', 'purchased_at' => 'datetime', 'expires_at' => 'datetime'];
    }

    public function employer(): BelongsTo
    {
        return $this->belongsTo(Employer::class);
    }

    public function orderItem(): BelongsTo
    {
        return $this->belongsTo(OrderItem::class);
    }

    public function promotions(): HasMany
    {
        return $this->hasMany(JobPromotion::class, 'order_item_id', 'order_item_id');
    }

    public function getSlotsUsedAttribute(): int
    {
        return max(0, $this->slots_total - $this->slots_remaining);
    }
}
