<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class JobSeeker extends Model
{
    protected $fillable = [
        'user_id', 'region_id', 'township_id', 'phone', 'profile_photo_path', 'cv_path', 'cv_original_name',
        'professional_title', 'years_experience', 'desired_job_title', 'employment_type',
        'work_mode', 'expected_salary_min', 'expected_salary_max', 'availability',
        'skills', 'languages', 'status', 'bio',
    ];
    protected function casts(): array { return ['skills' => 'array', 'languages' => 'array', 'status' => 'boolean']; }

    public function user(): BelongsTo { return $this->belongsTo(User::class); }
    public function region(): BelongsTo { return $this->belongsTo(Region::class); }
    public function township(): BelongsTo { return $this->belongsTo(Township::class); }
    public function applications(): HasMany { return $this->hasMany(Application::class); }
    public function savedJobs(): HasMany { return $this->hasMany(SavedJob::class); }
    public function experiences(): HasMany { return $this->hasMany(JobSeekerExperience::class)->orderBy('sort_order')->orderByDesc('started_on'); }
    public function educations(): HasMany { return $this->hasMany(JobSeekerEducation::class)->orderBy('sort_order')->orderByDesc('graduated_year'); }

    public function searchJobs(array $filters = []): Builder
    {
        return Job::published()
            ->when($filters['keyword'] ?? null, fn (Builder $query, string $keyword) => $query->where(fn (Builder $q) => $q->where('title', 'like', "%{$keyword}%")->orWhere('description', 'like', "%{$keyword}%")))
            ->when($filters['township_id'] ?? null, fn (Builder $query, int $townshipId) => $query->where('township_id', $townshipId))
            ->when($filters['location'] ?? null, fn (Builder $query, string $location) => $query->where('location', 'like', "%{$location}%"))
            ->when($filters['category'] ?? null, fn (Builder $query, string $category) => $query->where('category', $category));
    }

    public function applyForJob(Job $job, array $attributes = []): Application
    {
        abort_unless($job->status === 'published', 422, 'This job is not accepting applications.');
        $application = $this->applications()->create([...$attributes, 'job_id' => $job->id, 'cv_path' => $attributes['cv_path'] ?? $this->cv_path, 'status' => 'submitted', 'submitted_at' => now()]);
        $employerUser = $job->employer?->user;

        if ($employerUser) {
            $conversation = Conversation::firstOrCreate(
                ['application_id' => $application->id],
                ['created_by' => $this->user_id],
            );
            $conversation->participants()->syncWithoutDetaching([$this->user_id, $employerUser->id]);
        }

        return $application;
    }

    public function saveJob(Job $job): SavedJob { return $this->savedJobs()->firstOrCreate(['job_id' => $job->id]); }
    public function removeSavedJob(Job $job): bool { return (bool) $this->savedJobs()->where('job_id', $job->id)->delete(); }
    public function updateProfile(array $attributes): bool { return $this->fill($attributes)->save(); }
}
