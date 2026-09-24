<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Application extends Model
{
    public const STATUSES = ['submitted', 'reviewing', 'shortlisted', 'interview', 'offered', 'hired', 'rejected', 'withdrawn'];
    protected $fillable = ['job_id', 'job_seeker_id', 'cover_letter', 'cv_path', 'status', 'submitted_at'];
    protected function casts(): array { return ['submitted_at' => 'datetime']; }

    public function job(): BelongsTo { return $this->belongsTo(Job::class); }
    public function jobSeeker(): BelongsTo { return $this->belongsTo(JobSeeker::class); }
    public function interviews(): HasMany { return $this->hasMany(Interview::class); }

    public static function submitApplication(Job $job, JobSeeker $jobSeeker, array $attributes = []): self { return $jobSeeker->applyForJob($job, $attributes); }
    public function withdrawApplication(): bool
    {
        abort_unless(in_array($this->status, ['submitted', 'reviewing'], true), 422, 'This application can no longer be withdrawn.');
        return $this->update(['status' => 'withdrawn']);
    }
    public function viewStatus(): string { return $this->status; }
    public function updateStatus(string $status): bool
    {
        abort_unless(in_array($status, self::STATUSES, true), 422, 'Invalid application status.');
        return $this->update(['status' => $status]);
    }
}
