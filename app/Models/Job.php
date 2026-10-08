<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Job extends Model
{
    use SoftDeletes;

    protected $table = 'job_listings';

    public const STATUSES = ['draft', 'published', 'paused', 'closed'];
    protected $fillable = ['employer_id', 'township_id', 'category_id', 'title', 'description', 'requirements', 'location', 'category', 'employment_type', 'experience_level', 'work_mode', 'salary_min', 'salary_max', 'salary_currency', 'status', 'posted_at', 'application_deadline'];
    protected function casts(): array { return ['posted_at' => 'datetime', 'application_deadline' => 'datetime']; }

    public function employer(): BelongsTo { return $this->belongsTo(Employer::class); }
    public function township(): BelongsTo { return $this->belongsTo(Township::class); }
    public function jobCategory(): BelongsTo { return $this->belongsTo(JobCategory::class, 'category_id'); }
    public function applications(): HasMany { return $this->hasMany(Application::class); }
    public function savedBy(): HasMany { return $this->hasMany(SavedJob::class); }
    public function promotions(): HasMany { return $this->hasMany(JobPromotion::class); }

    public static function createJob(Employer $employer, array $attributes): self
    {
        return $employer->postJob($attributes);
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', 'published')
            ->whereHas('employer.user', fn (Builder $user) => $user->where('status', true))
            ->where(fn (Builder $q) => $q->whereNull('application_deadline')->orWhereDate('application_deadline', '>=', today()));
    }

    public function updateJob(array $attributes): bool { return $this->update($attributes); }
    public function deleteJob(): bool { return (bool) $this->delete(); }
    public function viewJobDetails(): self { return $this->loadMissing('employer'); }
    public function closeJob(): bool { return $this->update(['status' => 'closed']); }
}
