<?php

namespace VanToanTG\AntiCrawler\Models;

use Illuminate\Database\Eloquent\Model;

class BotDetectionLog extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = [
        'ip_address',
        'user_id',
        'user_agent',
        'request_url',
        'request_method',
        'detection_reason',
        'risk_score',
        'headers',
        'action_taken',
    ];

    protected $casts = [
        'headers' => 'array',
        'risk_score' => 'integer',
        'created_at' => 'datetime',
    ];

    /**
     * Scope to filter by high risk scores
     */
    public function scopeHighRisk($query, int $threshold = 70)
    {
        return $query->where('risk_score', '>=', $threshold);
    }

    /**
     * Scope to filter by action taken
     */
    public function scopeByAction($query, string $action)
    {
        return $query->where('action_taken', $action);
    }

    /**
     * Scope to filter by IP address
     */
    public function scopeByIp($query, string $ip)
    {
        return $query->where('ip_address', $ip);
    }

    /**
     * Scope to filter by date range
     */
    public function scopeDateRange($query, $from, $to = null)
    {
        $query->where('created_at', '>=', $from);
        
        if ($to) {
            $query->where('created_at', '<=', $to);
        }
        
        return $query;
    }

    /**
     * Scope to get recent logs
     */
    public function scopeRecent($query, int $hours = 24)
    {
        return $query->where('created_at', '>=', now()->subHours($hours));
    }

    /**
     * Get the user associated with this log
     */
    public function user()
    {
        return $this->belongsTo(config('auth.providers.users.model', 'App\Models\User'));
    }
}
