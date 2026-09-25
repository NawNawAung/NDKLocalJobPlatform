<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable
{
    public const ROLES = ['job_seeker', 'employer', 'admin'];

    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'status',
        'role',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'status' => 'boolean',
        ];
    }

    public function employer(): HasOne { return $this->hasOne(Employer::class); }
    public function jobSeeker(): HasOne { return $this->hasOne(JobSeeker::class); }
    public function notifications(): HasMany { return $this->hasMany(Notification::class); }
    public function conversations(): BelongsToMany { return $this->belongsToMany(Conversation::class, 'conversation_participants')->withPivot(['id', 'joined_at'])->withTimestamps(); }
    public function sentMessages(): HasMany { return $this->hasMany(Message::class, 'sender_id'); }

    public function updateProfile(array $attributes): bool
    {
        return $this->fill($attributes)->save();
    }
}
