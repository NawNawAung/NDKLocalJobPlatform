<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ExternalAdvertisement extends Model
{
    protected $fillable = ['advertiser_name', 'advertiser_email', 'advertiser_website', 'campaign_name', 'headline', 'banner_path', 'destination_url', 'placement', 'status', 'starts_at', 'ends_at', 'impression_limit', 'impressions_count', 'price_amount', 'currency', 'created_by', 'updated_by'];

    protected function casts(): array
    {
        return ['starts_at' => 'datetime', 'ends_at' => 'datetime', 'impression_limit' => 'integer', 'impressions_count' => 'integer', 'price_amount' => 'integer'];
    }

    public function payments(): HasMany
    {
        return $this->hasMany(ExternalAdPayment::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function scopeDisplayable($query)
    {
        return $query->where(fn ($q) => $q->where('status', 'active')->orWhere(fn ($scheduled) => $scheduled->where('status', 'scheduled')->whereNotNull('starts_at')->where('starts_at', '<=', now())))
            ->where(fn ($q) => $q->whereNull('starts_at')->orWhere('starts_at', '<=', now()))
            ->where('ends_at', '>', now())
            ->where(fn ($q) => $q->whereNull('impression_limit')->orWhereColumn('impressions_count', '<', 'impression_limit'))
            ->whereHas('payments', fn ($payments) => $payments->where('status', 'paid')
                ->whereColumn('external_ad_payments.amount', 'external_advertisements.price_amount'));
    }
}
