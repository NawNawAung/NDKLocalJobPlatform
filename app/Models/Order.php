<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Order extends Model
{
    protected $fillable = ['order_number', 'user_id', 'employer_id', 'status', 'currency', 'subtotal_amount', 'discount_amount', 'total_amount', 'billing_snapshot', 'paid_at', 'cancelled_at'];
    protected function casts(): array { return ['billing_snapshot' => 'array', 'paid_at' => 'datetime', 'cancelled_at' => 'datetime']; }
    public function user(): BelongsTo { return $this->belongsTo(User::class); }
    public function employer(): BelongsTo { return $this->belongsTo(Employer::class); }
    public function items(): HasMany { return $this->hasMany(OrderItem::class); }
    public function payments(): HasMany { return $this->hasMany(Payment::class); }
    public function invoice(): HasOne { return $this->hasOne(Invoice::class); }
    public function promotionRedemption(): HasOne { return $this->hasOne(PromotionRedemption::class); }
}
