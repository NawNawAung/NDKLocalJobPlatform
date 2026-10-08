<?php

namespace App\Services;

use App\Models\AdminAuditLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

class AdminAuditLogger
{
    public function record(Request $request, User $admin, string $action, Model $target, ?array $before, ?array $after): void
    {
        AdminAuditLog::create([
            'administrator_id' => $admin->id,
            'action' => $action,
            'target_type' => $target->getMorphClass(),
            'target_id' => $target->getKey(),
            'before' => $before,
            'after' => $after,
            'ip_address' => $request->ip(),
            'created_at' => now(),
        ]);
    }
}
