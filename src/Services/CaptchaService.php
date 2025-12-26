<?php

namespace VanToanTG\AntiCrawler\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use VanToanTG\AntiCrawler\Models\CaptchaChallenge;

class CaptchaService
{
    protected string $provider;
    protected string $siteKey;
    protected string $secretKey;

    public function __construct()
    {
        $this->provider = config('anti-crawler.captcha.provider', 'turnstile');
        $this->siteKey = config('anti-crawler.captcha.site_key', '');
        $this->secretKey = config('anti-crawler.captcha.secret_key', '');
    }

    /**
     * Generate a new CAPTCHA challenge
     */
    public function generateChallenge(string $ip, ?int $userId = null): string
    {
        $token = Str::random(64);

        CaptchaChallenge::create([
            'ip_address' => $ip,
            'user_id' => $userId,
            'challenge_type' => $this->provider,
            'challenge_token' => $token,
            'is_solved' => false,
        ]);

        return $token;
    }

    /**
     * Verify CAPTCHA response
     */
    public function verifyChallenge(string $response, string $ip): bool
    {
        if (empty($this->secretKey)) {
            // If no CAPTCHA is configured, always pass
            return true;
        }

        $verifyUrl = config("anti-crawler.captcha.verify_urls.{$this->provider}");

        if (!$verifyUrl) {
            return false;
        }

        try {
            $result = Http::asForm()->post($verifyUrl, [
                'secret' => $this->secretKey,
                'response' => $response,
                'remoteip' => $ip,
            ]);

            $data = $result->json();

            // Different providers have different response formats
            $success = match ($this->provider) {
                'turnstile' => $data['success'] ?? false,
                'recaptcha_v2', 'recaptcha_v3' => $data['success'] ?? false,
                'hcaptcha' => $data['success'] ?? false,
                default => false,
            };

            return $success;
        } catch (\Exception $e) {
            // Log error but don't block user
            \Log::error('CAPTCHA verification failed', [
                'provider' => $this->provider,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Record challenge completion
     */
    public function recordChallenge(string $token, bool $solved = true): void
    {
        $challenge = CaptchaChallenge::where('challenge_token', $token)->first();

        if ($challenge) {
            $challenge->markAsSolved();
        }
    }

    /**
     * Check if IP needs CAPTCHA challenge
     */
    public function needsChallenge(string $ip, int $riskScore): bool
    {
        if (!config('anti-crawler.captcha.enabled', true)) {
            return false;
        }

        $threshold = config('anti-crawler.captcha.trigger_threshold', 60);

        // Check if risk score is above threshold
        if ($riskScore < $threshold) {
            return false;
        }

        // Check if IP has recently solved a CAPTCHA
        $recentlySolved = CaptchaChallenge::byIp($ip)
            ->solved()
            ->recent(30) // Last 30 minutes
            ->exists();

        return !$recentlySolved;
    }

    /**
     * Get CAPTCHA widget HTML
     */
    public function getWidgetHtml(): string
    {
        if (empty($this->siteKey)) {
            return '<p>CAPTCHA not configured</p>';
        }

        return match ($this->provider) {
            'turnstile' => $this->getTurnstileWidget(),
            'recaptcha_v2' => $this->getRecaptchaV2Widget(),
            'recaptcha_v3' => $this->getRecaptchaV3Widget(),
            'hcaptcha' => $this->getHCaptchaWidget(),
            default => '<p>Unknown CAPTCHA provider</p>',
        };
    }

    /**
     * Get Cloudflare Turnstile widget
     */
    protected function getTurnstileWidget(): string
    {
        return <<<HTML
        <script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>
        <div class="cf-turnstile" data-sitekey="{$this->siteKey}"></div>
        HTML;
    }

    /**
     * Get reCAPTCHA v2 widget
     */
    protected function getRecaptchaV2Widget(): string
    {
        return <<<HTML
        <script src="https://www.google.com/recaptcha/api.js" async defer></script>
        <div class="g-recaptcha" data-sitekey="{$this->siteKey}"></div>
        HTML;
    }

    /**
     * Get reCAPTCHA v3 widget
     */
    protected function getRecaptchaV3Widget(): string
    {
        return <<<HTML
        <script src="https://www.google.com/recaptcha/api.js?render={$this->siteKey}"></script>
        <script>
            grecaptcha.ready(function() {
                grecaptcha.execute('{$this->siteKey}', {action: 'submit'}).then(function(token) {
                    document.getElementById('captcha-response').value = token;
                });
            });
        </script>
        <input type="hidden" id="captcha-response" name="cf-turnstile-response">
        HTML;
    }

    /**
     * Get hCaptcha widget
     */
    protected function getHCaptchaWidget(): string
    {
        return <<<HTML
        <script src="https://js.hcaptcha.com/1/api.js" async defer></script>
        <div class="h-captcha" data-sitekey="{$this->siteKey}"></div>
        HTML;
    }

    /**
     * Get CAPTCHA script URLs
     */
    public function getScriptUrls(): array
    {
        return match ($this->provider) {
            'turnstile' => ['https://challenges.cloudflare.com/turnstile/v0/api.js'],
            'recaptcha_v2', 'recaptcha_v3' => ['https://www.google.com/recaptcha/api.js'],
            'hcaptcha' => ['https://js.hcaptcha.com/1/api.js'],
            default => [],
        };
    }
}
