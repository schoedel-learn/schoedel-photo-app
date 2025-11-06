<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Download extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'photo_id',
        'user_id',
        'token',
        'download_url',
        'expires_at',
        'attempts',
        'max_attempts',
        'downloaded_at',
        'ip_address',
        'user_agent',
    ];

    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'downloaded_at' => 'datetime',
            'attempts' => 'integer',
            'max_attempts' => 'integer',
        ];
    }

    /**
     * Get the order that owns the download.
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * Get the photo being downloaded.
     */
    public function photo(): BelongsTo
    {
        return $this->belongsTo(Photo::class);
    }

    /**
     * Get the user who can download.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Check if download has expired.
     */
    public function isExpired(): bool
    {
        if (!$this->expires_at) {
            return true;
        }

        return $this->expires_at->isPast();
    }

    /**
     * Check if download has attempts remaining.
     */
    public function hasAttemptsRemaining(): bool
    {
        if (!$this->max_attempts) {
            return true;
        }

        return $this->attempts < $this->max_attempts;
    }

    /**
     * Check if download is valid (not expired and has attempts).
     */
    public function isValid(): bool
    {
        return !$this->isExpired() && $this->hasAttemptsRemaining();
    }

    /**
     * Mark download as used.
     */
    public function markAsDownloaded(string $ipAddress, ?string $userAgent = null): void
    {
        // Update metadata first
        $this->update([
            'downloaded_at' => now(),
            'ip_address' => $ipAddress,
            'user_agent' => $userAgent,
        ]);
        
        // Atomic increment to prevent race conditions
        $this->increment('attempts');
    }

    /**
     * Scope to get active downloads.
     */
    public function scopeActive($query)
    {
        return $query->where('expires_at', '>', now())
            ->whereColumn('attempts', '<', 'max_attempts');
    }

    /**
     * Scope to get expired downloads.
     */
    public function scopeExpired($query)
    {
        return $query->where('expires_at', '<=', now())
            ->orWhereColumn('attempts', '>=', 'max_attempts');
    }
}
