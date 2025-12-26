# Laravel Anti-Crawler Package

[![License: MIT](https://img.shields.io/badge/License-MIT-yellow.svg)](https://opensource.org/licenses/MIT)

Comprehensive anti-crawler and bot protection package for Laravel 11+ applications. Provides multi-layered protection including bot detection, rate limiting, CAPTCHA challenges, and an admin dashboard.

## Features

- 🤖 **Bot Detection** - User agent analysis, behavioral patterns, headless browser detection
- ⚡ **Rate Limiting** - Redis-based sliding window with per-IP and per-user limits
- 🛡️ **CAPTCHA Integration** - Supports Cloudflare Turnstile, reCAPTCHA v2/v3, and hCaptcha
- 📊 **Admin Dashboard** - Monitor activity, manage blocked IPs, and whitelist trusted sources
- 🎯 **IP Management** - Automatic and manual IP blocking with expiration support
- 📝 **Comprehensive Logging** - Track all suspicious activity with risk scoring
- ⚙️ **Highly Configurable** - Customize thresholds, limits, and detection rules

## Requirements

- PHP 8.2+
- Laravel 10.x, 11.x, or 12.x
- Redis (for rate limiting)

## Installation

### 1. Install via Composer

```bash
composer require vantoantg/laravel-anti-crawler
```

### 2. Publish Package Assets

```bash
# Publish all assets
php artisan vendor:publish --provider="VanToanTG\AntiCrawler\AntiCrawlerServiceProvider"

# Or publish individually
php artisan vendor:publish --tag=anti-crawler-config
php artisan vendor:publish --tag=anti-crawler-migrations
php artisan vendor:publish --tag=anti-crawler-views
```

### 3. Run Migrations

```bash
php artisan migrate
```

This creates the following tables:
- `bot_detection_logs` - Tracks all bot detection events
- `blocked_ips` - Manages blocked IP addresses
- `ip_whitelist` - Stores whitelisted IPs
- `captcha_challenges` - Tracks CAPTCHA attempts

### 4. Configure Environment

Add to your `.env` file:

```env
ANTI_CRAWLER_ENABLED=true

# Rate Limits
ANTI_CRAWLER_RATE_ANONYMOUS_MINUTE=60
ANTI_CRAWLER_RATE_ANONYMOUS_HOUR=500
ANTI_CRAWLER_RATE_AUTH_MINUTE=120
ANTI_CRAWLER_RATE_AUTH_HOUR=2000

# Bot Detection
ANTI_CRAWLER_RISK_THRESHOLD=70
ANTI_CRAWLER_AUTO_BLOCK_THRESHOLD=5

# CAPTCHA (Cloudflare Turnstile)
CAPTCHA_ENABLED=true
CAPTCHA_PROVIDER=turnstile
CAPTCHA_SITE_KEY=your-site-key
CAPTCHA_SECRET_KEY=your-secret-key
CAPTCHA_TRIGGER_THRESHOLD=60

# Redis
ANTI_CRAWLER_REDIS_CONNECTION=default
```

### 5. Apply Middleware

The package automatically registers middleware aliases. Use them in your routes:

```php
// Protect specific routes
Route::get('/books/{slug}', [BookController::class, 'show'])
    ->middleware('anti-crawler');

// Protect with bot detection
Route::get('/chapters/{slug}', [ChapterController::class, 'show'])
    ->middleware(['anti-crawler', 'bot-detect']);

// Protect route groups
Route::middleware(['anti-crawler'])->group(function () {
    Route::get('/books', [BookController::class, 'index']);
    Route::get('/books/{slug}', [BookController::class, 'show']);
});
```

## Usage

### Basic Protection

Apply the `anti-crawler` middleware to any route:

```php
Route::get('/protected', function () {
    return 'Protected content';
})->middleware('anti-crawler');
```

### Custom Rate Limiting Tiers

Pass a tier name to the middleware:

```php
Route::get('/api/data', [ApiController::class, 'data'])
    ->middleware('anti-crawler:api');
```

Configure tiers in `config/anti-crawler.php`:

```php
'rate_limits' => [
    'api' => [
        'per_minute' => 30,
        'per_hour' => 1000,
    ],
],
```

### Programmatic IP Management

```php
use VanToanTG\AntiCrawler\Models\BlockedIp;
use VanToanTG\AntiCrawler\Models\IpWhitelist;

// Block an IP temporarily (1 hour)
BlockedIp::blockIp(
    '123.45.67.89',
    'Suspicious activity detected',
    'auto',
    null,
    now()->addHour()
);

// Block permanently
BlockedIp::blockIp(
    '123.45.67.89',
    'Confirmed malicious bot',
    'manual',
    auth()->id(),
    null // Permanent
);

// Whitelist an IP
IpWhitelist::addIp(
    '192.168.1.100',
    'Office Network',
    auth()->id()
);

// Check if IP is blocked
if (BlockedIp::isBlocked($ip)) {
    // Handle blocked IP
}
```

### Access Detection Logs

```php
use VanToanTG\AntiCrawler\Models\BotDetectionLog;

// Get high-risk detections from today
$suspiciousLogs = BotDetectionLog::highRisk(70)
    ->whereDate('created_at', today())
    ->get();

// Get logs for specific IP
$ipLogs = BotDetectionLog::byIp('123.45.67.89')
    ->orderBy('created_at', 'desc')
    ->get();
```

## Admin Panel

Access the admin dashboard at `/admin/bot-protection` (requires authentication).

### Features:
- **Dashboard** - Overview with statistics and recent activity
- **Detection Logs** - Filterable logs with search
- **Blocked IPs** - Manage blocked addresses
- **Whitelist** - Manage trusted IPs

### Add to Navigation

```blade
<li>
    <a href="{{ route('admin.bot-protection.index') }}">
        🛡️ Bot Protection
    </a>
</li>
```

## Configuration

All settings are in `config/anti-crawler.php`:

### Rate Limiting

```php
'rate_limits' => [
    'anonymous' => [
        'per_minute' => 60,
        'per_hour' => 500,
    ],
    'authenticated' => [
        'per_minute' => 120,
        'per_hour' => 2000,
    ],
],
```

### Bot Detection

```php
'detection' => [
    'user_agent_blacklist' => ['curl', 'wget', 'scrapy'],
    'user_agent_whitelist' => ['Googlebot', 'Bingbot'],
    'risk_threshold' => 70,
    'auto_block_threshold' => 5,
],
```

### CAPTCHA Providers

Supported providers:
- `turnstile` - Cloudflare Turnstile (recommended)
- `recaptcha_v2` - Google reCAPTCHA v2
- `recaptcha_v3` - Google reCAPTCHA v3
- `hcaptcha` - hCaptcha

## CAPTCHA Setup

### Cloudflare Turnstile (Recommended)

1. Get keys from https://dash.cloudflare.com/
2. Add to `.env`:
```env
CAPTCHA_PROVIDER=turnstile
CAPTCHA_SITE_KEY=your-site-key
CAPTCHA_SECRET_KEY=your-secret-key
```

### Google reCAPTCHA

1. Get keys from https://www.google.com/recaptcha/admin
2. Add to `.env`:
```env
CAPTCHA_PROVIDER=recaptcha_v3
CAPTCHA_SITE_KEY=your-site-key
CAPTCHA_SECRET_KEY=your-secret-key
```

## Customization

### Extend Bot Detection

```php
namespace App\Services;

use VanToanTG\AntiCrawler\Services\BotDetectionService;

class CustomBotDetectionService extends BotDetectionService
{
    public function detectBot($request): array
    {
        $result = parent::detectBot($request);
        
        // Add custom logic
        if ($this->isMyCustomPattern($request)) {
            $result['risk_score'] = 100;
        }
        
        return $result;
    }
}
```

Bind in `AppServiceProvider`:

```php
$this->app->bind(
    \VanToanTG\AntiCrawler\Services\BotDetectionService::class,
    \App\Services\CustomBotDetectionService::class
);
```

### Customize Views

```bash
php artisan vendor:publish --tag=anti-crawler-views
```

Edit views in `resources/views/vendor/anti-crawler/`.

## Testing

```bash
# Run package tests
cd packages/vantoantg/laravel-anti-crawler
vendor/bin/phpunit
```

## Performance

- Adds ~5-10ms latency per request
- Uses Redis for efficient caching
- Minimal database queries with proper indexing

## Security

- All IPs are validated
- SQL injection protection via Eloquent
- XSS protection in views
- CAPTCHA verification prevents bypassing

## License

MIT License. See [LICENSE](LICENSE) for details.

## Support

For issues and questions, please use the GitHub issue tracker.

## Credits

Created by Toan Nguyen
