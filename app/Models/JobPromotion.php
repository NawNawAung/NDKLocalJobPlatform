<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class JobPromotion extends Model
{
    protected $fillable = ['job_id', 'employer_id', 'order_item_id', 'promotion_type', 'status', 'starts_at', 'ends_at', 'metadata'];
    protected function casts(): array { return ['starts_at' => 'datetime', 'ends_at' => 'datetime', 'metadata' => 'array']; }
}
