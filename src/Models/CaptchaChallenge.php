<?php

namespace VanToanTG\AntiCrawler\Models;

use Illuminate\Database\Eloquent\Model;

class CaptchaChallenge extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = [
        'ip_address',
        'user_id',
        'challenge_type',
        'challenge_token',
        'is_solved',
        'solved_at',
    ];

    protected $casts = [
        'is_solved' => 'boolean',
        'solved_at' => 'datetime',
        'created_at' => 'datetime',
    ];

    /**
     * Scope to get unsolved challenges
     */
    public function scopeUnsolved($query)
    {
        return $query->where('is_solved', false);
    }

    /**
     * Scope to get solved challenges
     */
    public function scopeSolved($query)
    {
        return $query->where('is_solved', true);
    }

    /**
     * Scope to filter by IP address
     */
    public function scopeByIp($query, string $ip)
    {
        return $query->where('ip_address', $ip);
    }

    /**
     * Scope to get recent challenges
     */
    public function scopeRecent($query, int $minutes = 60)
    {
        return $query->where('created_at', '>=', now()->subMinutes($minutes));
    }

    /**
     * Mark challenge as solved
     */
    public function markAsSolved(): bool
    {
        $this->is_solved = true;
        $this->solved_at = now();
        
        return $this->save();
    }

    /**
     * Check if challenge has expired (older than 5 minutes)
     */
    public function isExpired(): bool
    {
        return $this->created_at->addMinutes(5)->isPast();
    }

    /**
     * Get the user associated with this challenge
     */
    public function user()
    {
        return $this->belongsTo(config('auth.providers.users.model', 'App\Models\User'));
    }

    /**
     * Get success rate for an IP
     */
    public static function getSuccessRate(string $ip): float
    {
        $total = static::byIp($ip)->count();
        
        if ($total === 0) {
            return 0.0;
        }

        $solved = static::byIp($ip)->solved()->count();
        
        return ($solved / $total) * 100;
    }
}
