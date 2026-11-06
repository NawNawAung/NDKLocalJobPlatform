<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExternalAdPayment extends Model
{
    protected $fillable = ['external_advertisement_id', 'amount', 'currency', 'payment_method', 'reference_number', 'status', 'notes', 'recorded_by', 'paid_at'];

    protected function casts(): array
    {
        return ['amount' => 'integer', 'paid_at' => 'datetime'];
    }

    public function advertisement(): BelongsTo
    {
        return $this->belongsTo(ExternalAdvertisement::class, 'external_advertisement_id');
    }

    public function recorder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }
}
