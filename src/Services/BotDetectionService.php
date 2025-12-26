<?php

namespace VanToanTG\AntiCrawler\Services;

use Illuminate\Http\Request;
use VanToanTG\AntiCrawler\Models\BotDetectionLog;
use VanToanTG\AntiCrawler\Models\IpWhitelist;

class BotDetectionService
{
    /**
     * Detect if request is from a bot
     */
    public function detectBot(Request $request): array
    {
        $ip = $request->ip();
        $userAgent = $request->userAgent() ?? '';

        // Check if IP is whitelisted
        if (IpWhitelist::isWhitelisted($ip)) {
            return [
                'is_bot' => false,
                'risk_score' => 0,
                'reason' => 'Whitelisted IP',
                'should_block' => false,
                'should_challenge' => false,
            ];
        }

        // Check if user agent is whitelisted (legitimate crawlers)
        if ($this->isWhitelistedBot($userAgent)) {
            return [
                'is_bot' => true,
                'risk_score' => 0,
                'reason' => 'Legitimate crawler',
                'should_block' => false,
                'should_challenge' => false,
            ];
        }

        // Calculate risk score
        $riskScore = $this->calculateRiskScore($request);
        $reason = $this->getDetectionReason($request, $riskScore);

        $riskThreshold = config('anti-crawler.detection.risk_threshold', 70);
        $captchaThreshold = config('anti-crawler.captcha.trigger_threshold', 60);

        return [
            'is_bot' => $riskScore >= $riskThreshold,
            'risk_score' => $riskScore,
            'reason' => $reason,
            'should_block' => $riskScore >= $riskThreshold,
            'should_challenge' => $riskScore >= $captchaThreshold && $riskScore < $riskThreshold,
        ];
    }

    /**
     * Calculate risk score (0-100)
     */
    public function calculateRiskScore(Request $request): int
    {
        $score = 0;
        $userAgent = $request->userAgent() ?? '';
        $patterns = config('anti-crawler.detection.suspicious_patterns', []);

        // Check user agent
        if ($this->isKnownBot($userAgent)) {
            $score += $patterns['suspicious_user_agent'] ?? 30;
        }

        // Check for missing headers
        if (!$request->header('Accept')) {
            $score += $patterns['missing_accept_header'] ?? 20;
        }

        if (!$request->header('Accept-Language')) {
            $score += $patterns['missing_accept_language'] ?? 15;
        }

        if (!$request->header('Accept-Encoding')) {
            $score += $patterns['missing_accept_encoding'] ?? 10;
        }

        // Check for missing referrer (except for direct navigation)
        if (!$request->header('Referer') && $request->method() !== 'GET') {
            $score += $patterns['no_referrer'] ?? 5;
        }

        // Check behavioral patterns
        $behavioralScore = $this->checkBehavioralPatterns($request->ip());
        $score += $behavioralScore;

        // Cap at 100
        return min($score, 100);
    }

    /**
     * Check if user agent is a known bot
     */
    public function isKnownBot(string $userAgent): bool
    {
        $blacklist = config('anti-crawler.detection.user_agent_blacklist', []);
        $userAgentLower = strtolower($userAgent);

        foreach ($blacklist as $pattern) {
            if (str_contains($userAgentLower, strtolower($pattern))) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if user agent is a whitelisted bot (legitimate crawler)
     */
    public function isWhitelistedBot(string $userAgent): bool
    {
        $whitelist = config('anti-crawler.detection.user_agent_whitelist', []);
        $userAgentLower = strtolower($userAgent);

        foreach ($whitelist as $pattern) {
            if (str_contains($userAgentLower, strtolower($pattern))) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check behavioral patterns for an IP
     */
    protected function checkBehavioralPatterns(string $ip): int
    {
        $score = 0;

        // Check recent request frequency
        $recentLogs = BotDetectionLog::byIp($ip)
            ->recent(1) // Last hour
            ->count();

        // If more than 100 requests in the last hour, add risk
        if ($recentLogs > 100) {
            $score += 25;
        } elseif ($recentLogs > 50) {
            $score += 15;
        } elseif ($recentLogs > 30) {
            $score += 10;
        }

        // Check if IP has been flagged before
        $highRiskLogs = BotDetectionLog::byIp($ip)
            ->highRisk(70)
            ->recent(24)
            ->count();

        if ($highRiskLogs > 0) {
            $score += min($highRiskLogs * 5, 20);
        }

        return $score;
    }

    /**
     * Get human-readable detection reason
     */
    protected function getDetectionReason(Request $request, int $riskScore): string
    {
        $reasons = [];
        $userAgent = $request->userAgent() ?? '';

        if ($this->isKnownBot($userAgent)) {
            $reasons[] = 'Known bot user agent';
        }

        if (!$request->header('Accept')) {
            $reasons[] = 'Missing Accept header';
        }

        if (!$request->header('Accept-Language')) {
            $reasons[] = 'Missing Accept-Language header';
        }

        if (!$request->header('Accept-Encoding')) {
            $reasons[] = 'Missing Accept-Encoding header';
        }

        $recentLogs = BotDetectionLog::byIp($request->ip())->recent(1)->count();
        if ($recentLogs > 50) {
            $reasons[] = "High request frequency ({$recentLogs} requests/hour)";
        }

        if (empty($reasons)) {
            return "Risk score: {$riskScore}";
        }

        return implode(', ', $reasons);
    }

    /**
     * Log bot detection event
     */
    public function logDetection(Request $request, array $detectionResult, string $action = 'logged'): void
    {
        // Only log if configured to do so
        $logAll = config('anti-crawler.logging.log_all_requests', false);
        $minScore = config('anti-crawler.logging.min_risk_score_to_log', 50);

        if (!$logAll && $detectionResult['risk_score'] < $minScore) {
            return;
        }

        BotDetectionLog::create([
            'ip_address' => $request->ip(),
            'user_id' => auth()->id(),
            'user_agent' => $request->userAgent() ?? 'Unknown',
            'request_url' => $request->fullUrl(),
            'request_method' => $request->method(),
            'detection_reason' => $detectionResult['reason'],
            'risk_score' => $detectionResult['risk_score'],
            'headers' => $request->headers->all(),
            'action_taken' => $action,
        ]);
    }

    /**
     * Analyze user agent string
     */
    public function analyzeUserAgent(string $userAgent): array
    {
        return [
            'is_known_bot' => $this->isKnownBot($userAgent),
            'is_whitelisted' => $this->isWhitelistedBot($userAgent),
            'user_agent' => $userAgent,
        ];
    }
}
