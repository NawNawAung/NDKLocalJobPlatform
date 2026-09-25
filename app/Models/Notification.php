<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Notification extends Model
{
    protected $fillable = ['user_id', 'conversation_id', 'title', 'message', 'is_read'];
    protected function casts(): array { return ['is_read' => 'boolean']; }
    public function user(): BelongsTo { return $this->belongsTo(User::class); }
    public static function sendNotification(User $user, string $title, string $message, ?int $conversationId = null): self { return $user->notifications()->create(['title' => $title, 'message' => $message, 'conversation_id' => $conversationId]); }
    public function markAsRead(): bool { return $this->update(['is_read' => true]); }
    public function deleteNotification(): bool { return (bool) $this->delete(); }
    public static function viewNotifications(User $user): Collection { return $user->notifications()->latest()->get(); }
}
