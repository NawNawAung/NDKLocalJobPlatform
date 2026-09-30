<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BillingProduct extends Model
{
    protected $fillable = ['name', 'slug', 'description', 'product_type', 'price_amount', 'currency', 'duration_days', 'metadata', 'is_active', 'sort_order'];
    protected function casts(): array { return ['metadata' => 'array', 'is_active' => 'boolean']; }
    public function orderItems(): HasMany { return $this->hasMany(OrderItem::class); }
}
