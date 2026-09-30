<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PromotionRedemption extends Model
{
    protected $fillable = ['promotion_id', 'order_id', 'user_id', 'discount_amount', 'redeemed_at'];
    protected function casts(): array { return ['redeemed_at' => 'datetime']; }
    public function promotion(): BelongsTo { return $this->belongsTo(Promotion::class); }
    public function order(): BelongsTo { return $this->belongsTo(Order::class); }
    public function user(): BelongsTo { return $this->belongsTo(User::class); }
}
