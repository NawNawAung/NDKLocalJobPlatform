<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PlanFeature extends Model
{
    protected $fillable = ['pricing_plan_id', 'feature_key', 'feature_value'];
    protected function casts(): array { return ['feature_value' => 'json']; }
    public function plan(): BelongsTo { return $this->belongsTo(PricingPlan::class, 'pricing_plan_id'); }
}
