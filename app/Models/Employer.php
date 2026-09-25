<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Employer extends Model
{
    protected $fillable = ['user_id', 'region_id', 'township_id', 'company_name', 'company_description', 'location', 'is_verified'];
    protected function casts(): array { return ['is_verified' => 'boolean']; }

    public function user(): BelongsTo { return $this->belongsTo(User::class); }
    public function region(): BelongsTo { return $this->belongsTo(Region::class); }
    public function township(): BelongsTo { return $this->belongsTo(Township::class); }
    public function jobs(): HasMany { return $this->hasMany(Job::class); }
    public function interviews(): HasMany { return $this->hasMany(Interview::class); }

    public function postJob(array $attributes): Job { return $this->jobs()->create($attributes); }

    public function editJob(Job $job, array $attributes): Job
    {
        abort_unless($job->employer_id === $this->id, 403);
        $job->update($attributes);
        return $job->refresh();
    }

    public function reviewApplication(Application $application, string $status): Application
    {
        abort_unless($application->job?->employer_id === $this->id, 403);
        $application->updateStatus($status);
        return $application->refresh();
    }

    public function scheduleInterview(Application $application, array $attributes): Interview
    {
        abort_unless($application->job?->employer_id === $this->id, 403);
        return $this->interviews()->create([...$attributes, 'application_id' => $application->id, 'status' => 'scheduled']);
    }
}
