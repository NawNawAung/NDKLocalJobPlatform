<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Promotion extends Model
{
    protected $fillable = ['code', 'discount_type', 'discount_value', 'currency', 'max_redemptions', 'redemptions_count', 'starts_at', 'ends_at', 'is_active'];
    protected function casts(): array { return ['starts_at' => 'datetime', 'ends_at' => 'datetime', 'is_active' => 'boolean']; }
    public function redemptions(): HasMany { return $this->hasMany(PromotionRedemption::class); }
}
