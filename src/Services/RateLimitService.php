<?php

namespace VanToanTG\AntiCrawler\Services;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redis;
use VanToanTG\AntiCrawler\Models\BlockedIp;

class RateLimitService
{
    protected string $redisPrefix;
    protected string $redisConnection;

    public function __construct()
    {
        $this->redisPrefix = config('anti-crawler.redis.prefix', 'anti_crawler:');
        $this->redisConnection = config('anti-crawler.redis.connection', 'default');
    }

    /**
     * Check if request should be rate limited
     */
    public function checkRateLimit(Request $request, string $tier = 'anonymous'): array
    {
        $ip = $request->ip();
        $userId = auth()->id();

        // Determine tier if not specified
        if ($tier === 'anonymous' && $userId) {
            $tier = 'authenticated';
        }

        // Get rate limits for tier
        $limits = config("anti-crawler.rate_limits.{$tier}", [
            'per_minute' => 60,
            'per_hour' => 500,
        ]);

        // Check minute limit
        $minuteKey = $this->getKey($ip, $userId, 'minute');
        $minuteCount = $this->getCounter($minuteKey);
        $minuteLimit = $limits['per_minute'] ?? 60;

        if ($minuteCount >= $minuteLimit) {
            return [
                'allowed' => false,
                'reason' => 'Rate limit exceeded (per minute)',
                'limit' => $minuteLimit,
                'remaining' => 0,
                'retry_after' => 60,
            ];
        }

        // Check hour limit if configured
        if (isset($limits['per_hour'])) {
            $hourKey = $this->getKey($ip, $userId, 'hour');
            $hourCount = $this->getCounter($hourKey);
            $hourLimit = $limits['per_hour'];

            if ($hourCount >= $hourLimit) {
                return [
                    'allowed' => false,
                    'reason' => 'Rate limit exceeded (per hour)',
                    'limit' => $hourLimit,
                    'remaining' => 0,
                    'retry_after' => 3600,
                ];
            }
        }

        return [
            'allowed' => true,
            'limit' => $minuteLimit,
            'remaining' => $minuteLimit - $minuteCount,
        ];
    }

    /**
     * Increment rate limit counter
     */
    public function incrementCounter(Request $request, string $tier = 'anonymous'): void
    {
        $ip = $request->ip();
        $userId = auth()->id();

        // Increment minute counter
        $minuteKey = $this->getKey($ip, $userId, 'minute');
        $this->increment($minuteKey, 60);

        // Increment hour counter
        $hourKey = $this->getKey($ip, $userId, 'hour');
        $this->increment($hourKey, 3600);

        // Track violations
        $this->trackViolations($ip);
    }

    /**
     * Get remaining attempts
     */
    public function getRemainingAttempts(string $ip, ?int $userId = null, string $tier = 'anonymous'): int
    {
        $limits = config("anti-crawler.rate_limits.{$tier}", [
            'per_minute' => 60,
        ]);

        $minuteKey = $this->getKey($ip, $userId, 'minute');
        $count = $this->getCounter($minuteKey);
        $limit = $limits['per_minute'] ?? 60;

        return max(0, $limit - $count);
    }

    /**
     * Block IP temporarily after threshold violations
     */
    public function blockIpTemporarily(string $ip, int $minutes = 60): void
    {
        $threshold = config('anti-crawler.detection.auto_block_threshold', 5);
        $violationKey = $this->redisPrefix . "violations:{$ip}";
        
        $violations = (int) Redis::connection($this->redisConnection)->get($violationKey);

        if ($violations >= $threshold) {
            BlockedIp::blockIp(
                $ip,
                "Automatically blocked after {$violations} rate limit violations",
                'auto',
                null,
                now()->addMinutes($minutes)
            );

            // Reset violation counter
            Redis::connection($this->redisConnection)->del($violationKey);
        }
    }

    /**
     * Track rate limit violations
     */
    protected function trackViolations(string $ip): void
    {
        $violationKey = $this->redisPrefix . "violations:{$ip}";
        
        Redis::connection($this->redisConnection)->incr($violationKey);
        Redis::connection($this->redisConnection)->expire($violationKey, 3600); // 1 hour
    }

    /**
     * Get Redis key for rate limiting
     */
    protected function getKey(string $ip, ?int $userId, string $window): string
    {
        $identifier = $userId ? "user:{$userId}" : "ip:{$ip}";
        $timestamp = $window === 'minute' 
            ? floor(time() / 60) 
            : floor(time() / 3600);

        return $this->redisPrefix . "ratelimit:{$identifier}:{$window}:{$timestamp}";
    }

    /**
     * Get counter value from Redis
     */
    protected function getCounter(string $key): int
    {
        $value = Redis::connection($this->redisConnection)->get($key);
        
        return $value ? (int) $value : 0;
    }

    /**
     * Increment counter in Redis
     */
    protected function increment(string $key, int $ttl): void
    {
        $redis = Redis::connection($this->redisConnection);
        
        $redis->incr($key);
        $redis->expire($key, $ttl);
    }

    /**
     * Clear rate limit for an IP (useful for whitelisting)
     */
    public function clearRateLimit(string $ip, ?int $userId = null): void
    {
        $patterns = [
            $this->redisPrefix . "ratelimit:ip:{$ip}:*",
        ];

        if ($userId) {
            $patterns[] = $this->redisPrefix . "ratelimit:user:{$userId}:*";
        }

        foreach ($patterns as $pattern) {
            $keys = Redis::connection($this->redisConnection)->keys($pattern);
            
            if (!empty($keys)) {
                Redis::connection($this->redisConnection)->del($keys);
            }
        }
    }

    /**
     * Get rate limit headers for response
     */
    public function getRateLimitHeaders(Request $request, string $tier = 'anonymous'): array
    {
        $ip = $request->ip();
        $userId = auth()->id();
        
        $limits = config("anti-crawler.rate_limits.{$tier}", [
            'per_minute' => 60,
        ]);

        $minuteKey = $this->getKey($ip, $userId, 'minute');
        $count = $this->getCounter($minuteKey);
        $limit = $limits['per_minute'] ?? 60;

        return [
            'X-RateLimit-Limit' => $limit,
            'X-RateLimit-Remaining' => max(0, $limit - $count),
            'X-RateLimit-Reset' => (floor(time() / 60) + 1) * 60,
        ];
    }
}
