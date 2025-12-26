<?php

namespace VanToanTG\AntiCrawler\Models;

use Illuminate\Database\Eloquent\Model;

class BlockedIp extends Model
{
    protected $fillable = [
        'ip_address',
        'reason',
        'blocked_by',
        'blocked_by_user_id',
        'expires_at',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Check if IP is currently blocked (not expired)
     */
    public function scopeActive($query)
    {
        return $query->where(function ($q) {
            $q->whereNull('expires_at')
              ->orWhere('expires_at', '>', now());
        });
    }

    /**
     * Check if a specific IP is blocked
     */
    public static function isBlocked(string $ip): bool
    {
        return static::where('ip_address', $ip)
            ->active()
            ->exists();
    }

    /**
     * Block an IP address
     */
    public static function blockIp(
        string $ip,
        string $reason,
        string $blockedBy = 'auto',
        ?int $userId = null,
        ?\DateTime $expiresAt = null
    ): self {
        return static::updateOrCreate(
            ['ip_address' => $ip],
            [
                'reason' => $reason,
                'blocked_by' => $blockedBy,
                'blocked_by_user_id' => $userId,
                'expires_at' => $expiresAt,
            ]
        );
    }

    /**
     * Unblock an IP address
     */
    public static function unblockIp(string $ip): bool
    {
        return static::where('ip_address', $ip)->delete();
    }

    /**
     * Get the user who blocked this IP
     */
    public function blockedByUser()
    {
        return $this->belongsTo(config('auth.providers.users.model', 'App\Models\User'), 'blocked_by_user_id');
    }

    /**
     * Check if block has expired
     */
    public function isExpired(): bool
    {
        if ($this->expires_at === null) {
            return false; // Permanent block
        }

        return $this->expires_at->isPast();
    }

    /**
     * Check if block is permanent
     */
    public function isPermanent(): bool
    {
        return $this->expires_at === null;
    }
}
