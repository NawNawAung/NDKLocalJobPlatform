<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Interview extends Model
{
    public const STATUSES = ['scheduled', 'completed', 'cancelled', 'no_show'];
    protected $fillable = ['application_id', 'employer_id', 'interview_at', 'interview_type', 'status', 'meeting_url', 'notes'];
    protected function casts(): array { return ['interview_at' => 'datetime']; }
    public function application(): BelongsTo { return $this->belongsTo(Application::class); }
    public function employer(): BelongsTo { return $this->belongsTo(Employer::class); }
    public function rescheduleInterview(string $dateTime): bool
    {
        abort_if($this->status === 'cancelled', 422, 'A cancelled interview cannot be rescheduled.');
        return $this->update(['interview_at' => $dateTime, 'status' => 'scheduled']);
    }
    public function cancelInterview(): bool { return $this->update(['status' => 'cancelled']); }
    public function updateStatus(string $status): bool
    {
        abort_unless(in_array($status, self::STATUSES, true), 422, 'Invalid interview status.');
        return $this->update(['status' => $status]);
    }
}
