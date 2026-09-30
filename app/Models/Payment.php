<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Payment extends Model
{
    protected $fillable = ['order_id', 'user_id', 'payment_method', 'payment_provider', 'gateway_transaction_id', 'status', 'amount', 'currency', 'reference_number', 'proof_path', 'failure_reason', 'reviewed_by', 'submitted_at', 'paid_at', 'failed_at'];
    protected function casts(): array { return ['submitted_at' => 'datetime', 'paid_at' => 'datetime', 'failed_at' => 'datetime']; }
    public function order(): BelongsTo { return $this->belongsTo(Order::class); }
    public function user(): BelongsTo { return $this->belongsTo(User::class); }
    public function reviewer(): BelongsTo { return $this->belongsTo(User::class, 'reviewed_by'); }
    public function refunds(): HasMany { return $this->hasMany(Refund::class); }
}
