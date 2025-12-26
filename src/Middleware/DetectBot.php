<?php

namespace VanToanTG\AntiCrawler\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use VanToanTG\AntiCrawler\Services\BotDetectionService;

class DetectBot
{
    protected BotDetectionService $botDetection;

    public function __construct(BotDetectionService $botDetection)
    {
        $this->botDetection = $botDetection;
    }

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Specialized bot detection checks
        $userAgent = $request->userAgent() ?? '';
        $ip = $request->ip();

        // Check for headless browser signatures
        if ($this->isHeadlessBrowser($request)) {
            $this->botDetection->logDetection($request, [
                'is_bot' => true,
                'risk_score' => 90,
                'reason' => 'Headless browser detected',
            ], 'blocked');

            return response()->json([
                'error' => 'Access Denied',
                'message' => 'Headless browsers are not allowed.',
            ], 403);
        }

        // Check for automation tools
        if ($this->isAutomationTool($request)) {
            $this->botDetection->logDetection($request, [
                'is_bot' => true,
                'risk_score' => 95,
                'reason' => 'Automation tool detected',
            ], 'blocked');

            return response()->json([
                'error' => 'Access Denied',
                'message' => 'Automation tools are not allowed.',
            ], 403);
        }

        // Check for suspicious header combinations
        if ($this->hasSuspiciousHeaders($request)) {
            $this->botDetection->logDetection($request, [
                'is_bot' => true,
                'risk_score' => 75,
                'reason' => 'Suspicious header combination',
            ], 'logged');
        }

        return $next($request);
    }

    /**
     * Detect headless browsers (Puppeteer, Selenium, etc.)
     */
    protected function isHeadlessBrowser(Request $request): bool
    {
        $userAgent = strtolower($request->userAgent() ?? '');
        
        // Check for headless browser signatures
        $headlessSignatures = [
            'headless',
            'phantomjs',
            'htmlunit',
            'zombie',
        ];

        foreach ($headlessSignatures as $signature) {
            if (str_contains($userAgent, $signature)) {
                return true;
            }
        }

        // Check for WebDriver
        if ($request->header('X-WebDriver') || $request->header('Selenium')) {
            return true;
        }

        // Check for Chrome headless
        if (str_contains($userAgent, 'chrome') && str_contains($userAgent, 'headless')) {
            return true;
        }

        return false;
    }

    /**
     * Detect automation tools
     */
    protected function isAutomationTool(Request $request): bool
    {
        $userAgent = strtolower($request->userAgent() ?? '');
        
        $automationTools = [
            'selenium',
            'webdriver',
            'puppeteer',
            'playwright',
            'cypress',
        ];

        foreach ($automationTools as $tool) {
            if (str_contains($userAgent, $tool)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check for suspicious header combinations
     */
    protected function hasSuspiciousHeaders(Request $request): bool
    {
        $suspiciousCount = 0;

        // Missing common headers
        if (!$request->header('Accept')) {
            $suspiciousCount++;
        }

        if (!$request->header('Accept-Language')) {
            $suspiciousCount++;
        }

        if (!$request->header('Accept-Encoding')) {
            $suspiciousCount++;
        }

        // User agent but no other headers
        if ($request->userAgent() && $suspiciousCount >= 2) {
            return true;
        }

        // Check for unusual header order (advanced detection)
        // Real browsers send headers in a specific order
        $headers = array_keys($request->headers->all());
        
        // If User-Agent is not in the first few headers, it's suspicious
        $userAgentPosition = array_search('user-agent', $headers);
        if ($userAgentPosition !== false && $userAgentPosition > 5) {
            $suspiciousCount++;
        }

        return $suspiciousCount >= 3;
    }
}
