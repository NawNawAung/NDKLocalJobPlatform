<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class JobSeekerExperience extends Model
{
    protected $table = 'job_seeker_experiences';

    protected $fillable = [
        'job_seeker_id', 'job_title', 'employer_name', 'location', 'started_on',
        'ended_on', 'is_current', 'description', 'sort_order',
    ];

    protected function casts(): array
    {
        return ['started_on' => 'date', 'ended_on' => 'date', 'is_current' => 'boolean'];
    }

    public function jobSeeker(): BelongsTo { return $this->belongsTo(JobSeeker::class); }
}
