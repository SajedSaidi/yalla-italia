<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Mail\UserApproved;
use App\Support\SafeMail;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Factories\HasFactory;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class User extends Authenticatable implements FilamentUser
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'is_approved',
        'approved_at',
        'approved_by',
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
            'is_approved' => 'boolean',
            'approved_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        // Keep the approval metadata consistent wherever is_approved is changed
        // (approve actions, edit forms, bulk actions...).
        static::saving(function (User $user) {
            if (! $user->isDirty('is_approved')) {
                return;
            }

            if ($user->is_approved) {
                $user->approved_at ??= now();
                $user->approved_by ??= Auth::id();
            } else {
                $user->approved_at = null;
                $user->approved_by = null;
            }
        });

        // Single place that emails a user when their account gets approved.
        static::updated(function (User $user) {
            if ($user->wasChanged('is_approved') && $user->is_approved) {
                DB::afterCommit(fn () => SafeMail::send($user->email, new UserApproved($user)));
            }
        });

        // students.user_id is a restricting foreign key: remove the profile (and its files) first.
        static::deleting(function (User $user) {
            $user->student?->delete();
        });
    }

    /**
     * Without this, Filament returns 403 for everyone outside the "local" environment.
     * Student approval is enforced by the Login page and EnsureUserIsApproved.
     */
    public function canAccessPanel(Panel $panel): bool
    {
        return in_array($this->role, ['admin', 'manager', 'student'], true);
    }

    public function student()
    {
        return $this->hasOne(Student::class);
    }

    public function approvedBy()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }
    public function isStudent(): bool
    {
        return $this->role === 'student';
    }
    public function isManager(): bool
    {
        return $this->role === 'manager';
    }

    public function isManagerOrAdmin(): bool
    {
        return $this->role === 'admin' || $this->role === 'manager';
    }

    public function isApproved(): bool
    {
        return $this->is_approved;
    }

    public function approve(?User $approver = null): void
    {
        $this->update([
            'is_approved' => true,
            'approved_at' => now(),
            'approved_by' => $approver?->id ?? Auth::id(),
        ]);
    }

    public function unapprove(): void
    {
        $this->update(['is_approved' => false]);
    }
}
