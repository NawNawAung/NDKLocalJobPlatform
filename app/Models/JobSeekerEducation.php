<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class JobSeekerEducation extends Model
{
    protected $table = 'job_seeker_educations';

    protected $fillable = [
        'job_seeker_id', 'institution', 'qualification', 'field_of_study',
        'started_year', 'graduated_year', 'description', 'sort_order',
    ];

    public function jobSeeker(): BelongsTo { return $this->belongsTo(JobSeeker::class); }
}
