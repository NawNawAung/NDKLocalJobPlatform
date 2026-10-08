<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmployerReview extends Model
{
    protected $fillable = ['employer_id', 'job_seeker_id', 'application_id', 'rating', 'title', 'review', 'status', 'moderation_notes', 'moderated_by', 'moderated_at'];
    protected function casts(): array { return ['rating' => 'integer', 'moderated_at' => 'datetime']; }
    public function employer(): BelongsTo { return $this->belongsTo(Employer::class); }
    public function jobSeeker(): BelongsTo { return $this->belongsTo(JobSeeker::class); }
    public function application(): BelongsTo { return $this->belongsTo(Application::class); }
    public function moderator(): BelongsTo { return $this->belongsTo(User::class, 'moderated_by'); }
}
