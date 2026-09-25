<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FeaturedCity extends Model
{
    protected $fillable = ['region_id', 'name', 'highlight', 'sort_order'];

    public function region(): BelongsTo { return $this->belongsTo(Region::class); }
}
