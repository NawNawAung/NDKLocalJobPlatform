<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Township extends Model
{
    protected $fillable = ['region_id', 'name', 'is_featured'];
    protected function casts(): array { return ['is_featured' => 'boolean']; }

    public function region(): BelongsTo { return $this->belongsTo(Region::class); }
    public function jobSeekers(): HasMany { return $this->hasMany(JobSeeker::class); }
    public function employers(): HasMany { return $this->hasMany(Employer::class); }
    public function jobListings(): HasMany { return $this->hasMany(Job::class); }
}
