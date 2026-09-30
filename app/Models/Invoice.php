<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Invoice extends Model
{
    protected $fillable = ['invoice_number', 'order_id', 'user_id', 'status', 'currency', 'subtotal_amount', 'discount_amount', 'total_amount', 'bill_to_snapshot', 'issued_at', 'due_at', 'paid_at'];
    protected function casts(): array { return ['bill_to_snapshot' => 'array', 'issued_at' => 'datetime', 'due_at' => 'datetime', 'paid_at' => 'datetime']; }
    public function order(): BelongsTo { return $this->belongsTo(Order::class); }
    public function user(): BelongsTo { return $this->belongsTo(User::class); }
}
