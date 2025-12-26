<?php

namespace VanToanTG\AntiCrawler\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use VanToanTG\AntiCrawler\Models\BlockedIp;
use VanToanTG\AntiCrawler\Models\IpWhitelist;
use VanToanTG\AntiCrawler\Services\BotDetectionService;
use VanToanTG\AntiCrawler\Services\RateLimitService;
use VanToanTG\AntiCrawler\Services\CaptchaService;

class AntiCrawler
{
    protected BotDetectionService $botDetection;
    protected RateLimitService $rateLimit;
    protected CaptchaService $captcha;

    public function __construct(
        BotDetectionService $botDetection,
        RateLimitService $rateLimit,
        CaptchaService $captcha
    ) {
        $this->botDetection = $botDetection;
        $this->rateLimit = $rateLimit;
        $this->captcha = $captcha;
    }

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next, ?string $tier = null): Response
    {
        // Check if anti-crawler is enabled
        if (!config('anti-crawler.enabled', true)) {
            return $next($request);
        }

        $ip = $request->ip();

        // 1. Check if IP is whitelisted
        if (IpWhitelist::isWhitelisted($ip)) {
            return $next($request);
        }

        // 2. Check if IP is blocked
        if (BlockedIp::isBlocked($ip)) {
            return $this->blockedResponse();
        }

        // 3. Check rate limits
        $rateLimitResult = $this->rateLimit->checkRateLimit($request, $tier ?? 'anonymous');
        
        if (!$rateLimitResult['allowed']) {
            // Track violation and potentially auto-block
            $this->rateLimit->blockIpTemporarily($ip, config('anti-crawler.detection.auto_block_duration', 60));
            
            return $this->rateLimitResponse($rateLimitResult);
        }

        // 4. Detect bot behavior
        $detectionResult = $this->botDetection->detectBot($request);

        // 5. Handle based on risk score
        if ($detectionResult['should_block']) {
            // Log and block
            $this->botDetection->logDetection($request, $detectionResult, 'blocked');
            
            // Auto-block high-risk IPs
            BlockedIp::blockIp(
                $ip,
                $detectionResult['reason'],
                'auto',
                null,
                now()->addHour()
            );

            return $this->botDetectedResponse();
        }

        if ($detectionResult['should_challenge']) {
            // Check if CAPTCHA was already solved
            if ($request->has('captcha_verified') && session('captcha_verified_' . $ip)) {
                // CAPTCHA was solved, allow request
                $this->botDetection->logDetection($request, $detectionResult, 'challenged');
            } else {
                // Show CAPTCHA challenge
                $this->botDetection->logDetection($request, $detectionResult, 'challenged');
                return $this->captchaResponse($request, $detectionResult);
            }
        }

        // 6. Log if configured
        if (config('anti-crawler.logging.log_all_requests', false) || 
            $detectionResult['risk_score'] >= config('anti-crawler.logging.min_risk_score_to_log', 50)) {
            $this->botDetection->logDetection($request, $detectionResult, 'logged');
        }

        // 7. Increment rate limit counter
        $this->rateLimit->incrementCounter($request, $tier ?? 'anonymous');

        // 8. Add rate limit headers to response
        $response = $next($request);
        $headers = $this->rateLimit->getRateLimitHeaders($request, $tier ?? 'anonymous');
        
        foreach ($headers as $key => $value) {
            $response->headers->set($key, $value);
        }

        return $response;
    }

    /**
     * Response for blocked IPs
     */
    protected function blockedResponse(): Response
    {
        $message = config('anti-crawler.messages.ip_blocked', 'Your IP address has been blocked due to suspicious activity.');

        return response()->json([
            'error' => 'Access Denied',
            'message' => $message,
        ], 403);
    }

    /**
     * Response for rate limit exceeded
     */
    protected function rateLimitResponse(array $rateLimitResult): Response
    {
        $message = config('anti-crawler.messages.rate_limit_exceeded', 'Too many requests. Please try again later.');

        return response()->json([
            'error' => 'Too Many Requests',
            'message' => $message,
            'retry_after' => $rateLimitResult['retry_after'] ?? 60,
        ], 429)
        ->header('Retry-After', $rateLimitResult['retry_after'] ?? 60);
    }

    /**
     * Response for bot detection
     */
    protected function botDetectedResponse(): Response
    {
        $message = config('anti-crawler.messages.bot_detected', 'Automated access detected. Please verify you are human.');

        return response()->json([
            'error' => 'Bot Detected',
            'message' => $message,
        ], 403);
    }

    /**
     * Response with CAPTCHA challenge
     */
    protected function captchaResponse(Request $request, array $detectionResult): Response
    {
        $token = $this->captcha->generateChallenge($request->ip(), auth()->id());

        return response()->view('anti-crawler::captcha-challenge', [
            'token' => $token,
            'reason' => $detectionResult['reason'],
            'risk_score' => $detectionResult['risk_score'],
            'captcha_widget' => $this->captcha->getWidgetHtml(),
            'return_url' => $request->fullUrl(),
        ], 403);
    }
}
