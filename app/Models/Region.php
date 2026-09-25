<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Region extends Model
{
    protected $fillable = ['name', 'type', 'sort_order'];

    public function townships(): HasMany { return $this->hasMany(Township::class); }
    public function featuredCities(): HasMany { return $this->hasMany(FeaturedCity::class); }
}
