<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SavedJob extends Model
{
    protected $fillable = ['job_id', 'job_seeker_id', 'saved_at'];
    protected function casts(): array { return ['saved_at' => 'datetime']; }
    public function job(): BelongsTo { return $this->belongsTo(Job::class); }
    public function jobSeeker(): BelongsTo { return $this->belongsTo(JobSeeker::class); }
    public static function saveJob(JobSeeker $jobSeeker, Job $job): self { return $jobSeeker->saveJob($job); }
    public function removeJob(): bool { return (bool) $this->delete(); }
    public static function viewSavedJobs(JobSeeker $jobSeeker) { return $jobSeeker->savedJobs()->with('job')->latest('saved_at')->get(); }
}
