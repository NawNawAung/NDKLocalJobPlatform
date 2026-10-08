<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AdminAuditLog extends Model
{
    public $timestamps = false;
    protected $fillable = ['administrator_id', 'action', 'target_type', 'target_id', 'before', 'after', 'ip_address', 'created_at'];
    protected function casts(): array { return ['before' => 'array', 'after' => 'array', 'created_at' => 'datetime']; }
    public function administrator(): BelongsTo { return $this->belongsTo(User::class, 'administrator_id'); }
}
