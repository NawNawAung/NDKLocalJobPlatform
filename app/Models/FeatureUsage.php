<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FeatureUsage extends Model
{
    protected $fillable = ['employer_id', 'feature_key', 'resource_type', 'resource_id', 'quantity', 'period_start', 'period_end', 'context'];
    protected function casts(): array { return ['period_start' => 'datetime', 'period_end' => 'datetime', 'context' => 'array']; }
    public function employer(): BelongsTo { return $this->belongsTo(Employer::class); }
}
