<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Skill extends Model
{
    protected $fillable = ['name', 'type', 'created_by_job_seeker_id', 'is_builtin', 'is_active'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function jobSeekers(): BelongsToMany
    {
        return $this->belongsToMany(JobSeeker::class)->withTimestamps();
    }
}
