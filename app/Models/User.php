<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
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
        'email_unsubscribed',
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
            'email_unsubscribed' => 'boolean',
        ];
    }

    /**
     * Get the galleries created by this user (as photographer).
     */
    public function galleries()
    {
        return $this->hasMany(Gallery::class);
    }

    /**
     * Get the orders placed by this user (as client).
     */
    public function orders()
    {
        return $this->hasMany(Order::class, 'user_id');
    }

    /**
     * Get the orders for this photographer.
     */
    public function photographerOrders()
    {
        return $this->hasMany(Order::class, 'photographer_id');
    }

    /**
     * Check if user is a photographer.
     */
    public function isPhotographer(): bool
    {
        return $this->role === 'photographer';
    }

    /**
     * Check if user is a client.
     */
    public function isClient(): bool
    {
        return $this->role === 'client';
    }

    /**
     * Check if user is an admin.
     */
    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    /**
     * Check if user is a studio manager.
     */
    public function isStudioManager(): bool
    {
        return $this->role === 'studio_manager';
    }

    /**
     * Check if user is a guest.
     */
    public function isGuest(): bool
    {
        return $this->role === 'guest';
    }

    /**
     * Check if user is a developer.
     */
    public function isDeveloper(): bool
    {
        return $this->role === 'developer';
    }

    /**
     * Send the password reset notification for staff users.
     *
     * @param  string  $token
     */
    public function sendPasswordResetNotification($token): void
    {
        // Use custom notification for staff password resets
        // Laravel's Password facade will call this when using 'staff' broker
        $this->notify(new \App\Notifications\StaffResetPasswordNotification($token));
    }
}
