<?php

namespace VanToanTG\AntiCrawler\Models;

use Illuminate\Database\Eloquent\Model;

class IpWhitelist extends Model
{
    protected $table = 'ip_whitelist';

    protected $fillable = [
        'ip_address',
        'description',
        'created_by_user_id',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Check if a specific IP is whitelisted
     */
    public static function isWhitelisted(string $ip): bool
    {
        // Check database whitelist
        $inDatabase = static::where('ip_address', $ip)->exists();
        
        if ($inDatabase) {
            return true;
        }

        // Check default whitelist from config
        $defaultWhitelist = config('anti-crawler.default_whitelist', []);
        
        return in_array($ip, $defaultWhitelist);
    }

    /**
     * Add IP to whitelist
     */
    public static function addIp(string $ip, ?string $description = null, ?int $userId = null): self
    {
        return static::updateOrCreate(
            ['ip_address' => $ip],
            [
                'description' => $description,
                'created_by_user_id' => $userId,
            ]
        );
    }

    /**
     * Remove IP from whitelist
     */
    public static function removeIp(string $ip): bool
    {
        return static::where('ip_address', $ip)->delete();
    }

    /**
     * Get the user who added this IP to whitelist
     */
    public function createdByUser()
    {
        return $this->belongsTo(config('auth.providers.users.model', 'App\Models\User'), 'created_by_user_id');
    }
}
