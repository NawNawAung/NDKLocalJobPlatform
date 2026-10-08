<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ContentReport extends Model
{
    protected $fillable = ['reported_by', 'target_type', 'target_id', 'reason', 'details', 'status', 'reviewed_by', 'reviewed_at'];
    protected function casts(): array { return ['reviewed_at' => 'datetime']; }
    public function reporter(): BelongsTo { return $this->belongsTo(User::class, 'reported_by'); }
    public function reviewer(): BelongsTo { return $this->belongsTo(User::class, 'reviewed_by'); }
}
