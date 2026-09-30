<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Refund extends Model
{
    protected $fillable = ['payment_id', 'processed_by', 'amount', 'currency', 'status', 'reason', 'reference_number', 'processed_at'];
    protected function casts(): array { return ['processed_at' => 'datetime']; }
    public function payment(): BelongsTo { return $this->belongsTo(Payment::class); }
    public function processor(): BelongsTo { return $this->belongsTo(User::class, 'processed_by'); }
}
