<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AdCampaign extends Model
{
    public const COMPANY_SPOTLIGHT = 'home_company_spotlight';

    protected $fillable = [
        'employer_id', 'order_item_id', 'campaign_type', 'placement', 'status',
        'starts_at', 'ends_at', 'impression_limit', 'impressions_count', 'metadata',
    ];

    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'impression_limit' => 'integer',
            'impressions_count' => 'integer',
            'metadata' => 'array',
        ];
    }

    public function employer(): BelongsTo
    {
        return $this->belongsTo(Employer::class);
    }

    public function orderItem(): BelongsTo
    {
        return $this->belongsTo(OrderItem::class);
    }
}
