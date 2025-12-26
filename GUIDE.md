# Laravel Anti-Crawler Package - Installation & Usage Guide

## Table of Contents

1. [Requirements](#requirements)
2. [Installation](#installation)
3. [Configuration](#configuration)
4. [Basic Usage](#basic-usage)
5. [Advanced Usage](#advanced-usage)
6. [Admin Panel](#admin-panel)
7. [CAPTCHA Setup](#captcha-setup)
8. [Customization](#customization)
9. [Troubleshooting](#troubleshooting)
10. [API Reference](#api-reference)

---

## Requirements

Before installing the package, ensure your system meets these requirements:

- **PHP**: 8.2 or higher
- **Laravel**: 11.x
- **Redis**: Required for rate limiting
- **Database**: MySQL, PostgreSQL, or SQLite

---

## Installation

### Step 1: Install the Package

#### Option A: Via Composer (When Published)

```bash
composer require vantoantg/laravel-anti-crawler
```

#### Option B: Local Development

Add to your project's `composer.json`:

```json
{
    "repositories": [
        {
            "type": "path",
            "url": "./packages/laravel-anti-crawler"
        }
    ],
    "require": {
        "vantoantg/laravel-anti-crawler": "@dev"
    }
}
```

Then run:

```bash
composer update vantoantg/laravel-anti-crawler
```

### Step 2: Publish Package Assets

Publish all assets at once:

```bash
php artisan vendor:publish --provider="VanToanTG\AntiCrawler\AntiCrawlerServiceProvider"
```

Or publish individually:

```bash
# Configuration file
php artisan vendor:publish --tag=anti-crawler-config

# Database migrations
php artisan vendor:publish --tag=anti-crawler-migrations

# Views (optional, for customization)
php artisan vendor:publish --tag=anti-crawler-views
```

### Step 3: Run Migrations

```bash
php artisan migrate
```

This creates four tables:
- `bot_detection_logs` - Tracks all bot detection events
- `blocked_ips` - Manages blocked IP addresses
- `ip_whitelist` - Stores whitelisted IPs
- `captcha_challenges` - Tracks CAPTCHA attempts

### Step 4: Configure Redis

Ensure Redis is configured in your `.env`:

```env
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379
```

---

## Configuration

### Environment Variables

Add these to your `.env` file:

```env
# Enable/Disable Protection
ANTI_CRAWLER_ENABLED=true

# Rate Limits - Anonymous Users
ANTI_CRAWLER_RATE_ANONYMOUS_MINUTE=60
ANTI_CRAWLER_RATE_ANONYMOUS_HOUR=500

# Rate Limits - Authenticated Users
ANTI_CRAWLER_RATE_AUTH_MINUTE=120
ANTI_CRAWLER_RATE_AUTH_HOUR=2000

# Rate Limits - Chapter Reading (Custom Tier)
ANTI_CRAWLER_RATE_CHAPTER_MINUTE=10

# Bot Detection
ANTI_CRAWLER_RISK_THRESHOLD=70
ANTI_CRAWLER_AUTO_BLOCK_THRESHOLD=5
ANTI_CRAWLER_AUTO_BLOCK_DURATION=60

# CAPTCHA Configuration
CAPTCHA_ENABLED=true
CAPTCHA_PROVIDER=turnstile
CAPTCHA_SITE_KEY=your-site-key-here
CAPTCHA_SECRET_KEY=your-secret-key-here
CAPTCHA_TRIGGER_THRESHOLD=60

# Logging
ANTI_CRAWLER_LOG_ALL=false
ANTI_CRAWLER_MIN_LOG_SCORE=50
ANTI_CRAWLER_LOG_RETENTION=30

# Redis
ANTI_CRAWLER_REDIS_CONNECTION=default
ANTI_CRAWLER_REDIS_PREFIX=anti_crawler:
```

### Configuration File

After publishing, edit `config/anti-crawler.php` to customize:

- Rate limit tiers
- Bot detection rules
- User agent blacklist/whitelist
- CAPTCHA settings
- Response messages

---

## Basic Usage

### Protecting Routes

Apply the `anti-crawler` middleware to any route:

```php
use App\Http\Controllers\BookController;

// Single route
Route::get('/books/{slug}', [BookController::class, 'show'])
    ->middleware('anti-crawler');

// Route group
Route::middleware(['anti-crawler'])->group(function () {
    Route::get('/books', [BookController::class, 'index']);
    Route::get('/books/{slug}', [BookController::class, 'show']);
    Route::get('/chapters/{slug}', [ChapterController::class, 'show']);
});
```

### Using Both Middlewares

For maximum protection, combine both middlewares:

```php
Route::get('/api/data', [ApiController::class, 'data'])
    ->middleware(['anti-crawler', 'bot-detect']);
```

### Custom Rate Limit Tiers

Pass a tier name to the middleware:

```php
// Use 'api' tier from config
Route::get('/api/books', [ApiController::class, 'books'])
    ->middleware('anti-crawler:api');

// Use 'chapter_reading' tier
Route::get('/chapters/{slug}', [ChapterController::class, 'show'])
    ->middleware('anti-crawler:chapter_reading');
```

Define custom tiers in `config/anti-crawler.php`:

```php
'rate_limits' => [
    'api' => [
        'per_minute' => 30,
        'per_hour' => 1000,
    ],
    'search' => [
        'per_minute' => 20,
    ],
],
```

---

## Advanced Usage

### Programmatic IP Management

#### Block an IP Address

```php
use VanToanTG\AntiCrawler\Models\BlockedIp;

// Block temporarily (1 hour)
BlockedIp::blockIp(
    ip: '123.45.67.89',
    reason: 'Suspicious activity detected',
    blockedBy: 'auto',
    userId: null,
    expiresAt: now()->addHour()
);

// Block permanently
BlockedIp::blockIp(
    ip: '123.45.67.89',
    reason: 'Confirmed malicious bot',
    blockedBy: 'manual',
    userId: auth()->id(),
    expiresAt: null
);
```

#### Unblock an IP Address

```php
BlockedIp::unblockIp('123.45.67.89');
```

#### Check if IP is Blocked

```php
if (BlockedIp::isBlocked($ip)) {
    // Handle blocked IP
}
```

### Whitelist Management

#### Add IP to Whitelist

```php
use VanToanTG\AntiCrawler\Models\IpWhitelist;

IpWhitelist::addIp(
    ip: '192.168.1.100',
    description: 'Office Network',
    userId: auth()->id()
);
```

#### Remove from Whitelist

```php
IpWhitelist::removeIp('192.168.1.100');
```

#### Check if IP is Whitelisted

```php
if (IpWhitelist::isWhitelisted($ip)) {
    // IP is trusted
}
```

### Accessing Detection Logs

```php
use VanToanTG\AntiCrawler\Models\BotDetectionLog;

// Get high-risk detections from today
$suspiciousLogs = BotDetectionLog::highRisk(70)
    ->whereDate('created_at', today())
    ->orderBy('created_at', 'desc')
    ->get();

// Get logs for specific IP
$ipLogs = BotDetectionLog::byIp('123.45.67.89')
    ->recent(24) // Last 24 hours
    ->get();

// Get blocked actions
$blockedRequests = BotDetectionLog::byAction('blocked')
    ->recent(1) // Last hour
    ->get();
```

### Custom Bot Detection Logic

Extend the `BotDetectionService`:

```php
namespace App\Services;

use VanToanTG\AntiCrawler\Services\BotDetectionService;
use Illuminate\Http\Request;

class CustomBotDetectionService extends BotDetectionService
{
    public function detectBot(Request $request): array
    {
        $result = parent::detectBot($request);
        
        // Add your custom detection logic
        if ($this->isMyCustomBotPattern($request)) {
            $result['is_bot'] = true;
            $result['risk_score'] = 100;
            $result['reason'] = 'Custom bot pattern detected';
        }
        
        return $result;
    }
    
    private function isMyCustomBotPattern(Request $request): bool
    {
        // Your custom logic here
        // Example: Check for specific headers, cookies, etc.
        return false;
    }
}
```

Bind in `AppServiceProvider`:

```php
use VanToanTG\AntiCrawler\Services\BotDetectionService;
use App\Services\CustomBotDetectionService;

public function register(): void
{
    $this->app->bind(
        BotDetectionService::class,
        CustomBotDetectionService::class
    );
}
```

---

## Admin Panel

### Accessing the Dashboard

Navigate to: `http://your-app.test/admin/bot-protection`

**Note**: Requires authentication. Apply your auth middleware in routes.

### Dashboard Features

**Statistics Overview**
- Total detection logs
- Blocked requests today
- High-risk detections
- Active IP blocks
- Whitelisted IPs count
- CAPTCHA success rate

**Recent Activity**
- Last 10 detection events
- Risk scores and actions taken

**Top Offenders**
- IPs with most requests (last 7 days)

### Detection Logs Page

**URL**: `/admin/bot-protection/logs`

**Filters Available**:
- IP Address
- Action (logged, challenged, blocked)
- Minimum risk score
- Date range

**Information Displayed**:
- Timestamp
- IP address
- User agent
- Request URL
- Risk score
- Detection reason
- Action taken

### Blocked IPs Page

**URL**: `/admin/bot-protection/blocked-ips`

**Features**:
- View all blocked IPs
- Block new IP manually
- Set expiration time
- Unblock IPs
- View block reason and type (auto/manual)

### Whitelist Page

**URL**: `/admin/bot-protection/whitelist`

**Features**:
- View whitelisted IPs
- Add new IP to whitelist
- Add description for each IP
- Remove from whitelist

### Adding to Your Admin Navigation

```blade
<!-- In your admin layout -->
<nav>
    <a href="{{ route('admin.dashboard') }}">Dashboard</a>
    <a href="{{ route('admin.bot-protection.index') }}">
        🛡️ Bot Protection
    </a>
    <!-- Other menu items -->
</nav>
```

---

## CAPTCHA Setup

### Cloudflare Turnstile (Recommended)

1. **Get API Keys**
   - Visit: https://dash.cloudflare.com/
   - Go to Turnstile section
   - Create a new site
   - Copy Site Key and Secret Key

2. **Configure in .env**
   ```env
   CAPTCHA_PROVIDER=turnstile
   CAPTCHA_SITE_KEY=your-turnstile-site-key
   CAPTCHA_SECRET_KEY=your-turnstile-secret-key
   ```

### Google reCAPTCHA v3

1. **Get API Keys**
   - Visit: https://www.google.com/recaptcha/admin
   - Register a new site (reCAPTCHA v3)
   - Copy Site Key and Secret Key

2. **Configure in .env**
   ```env
   CAPTCHA_PROVIDER=recaptcha_v3
   CAPTCHA_SITE_KEY=your-recaptcha-site-key
   CAPTCHA_SECRET_KEY=your-recaptcha-secret-key
   ```

### Google reCAPTCHA v2

1. **Get API Keys**
   - Visit: https://www.google.com/recaptcha/admin
   - Register a new site (reCAPTCHA v2)
   - Copy Site Key and Secret Key

2. **Configure in .env**
   ```env
   CAPTCHA_PROVIDER=recaptcha_v2
   CAPTCHA_SITE_KEY=your-recaptcha-site-key
   CAPTCHA_SECRET_KEY=your-recaptcha-secret-key
   ```

### hCaptcha

1. **Get API Keys**
   - Visit: https://www.hcaptcha.com/
   - Sign up and create a new site
   - Copy Site Key and Secret Key

2. **Configure in .env**
   ```env
   CAPTCHA_PROVIDER=hcaptcha
   CAPTCHA_SITE_KEY=your-hcaptcha-site-key
   CAPTCHA_SECRET_KEY=your-hcaptcha-secret-key
   ```

---

## Customization

### Customize Views

Publish views first:

```bash
php artisan vendor:publish --tag=anti-crawler-views
```

Views will be in: `resources/views/vendor/anti-crawler/`

Edit as needed:
- `admin/index.blade.php` - Dashboard
- `admin/logs.blade.php` - Detection logs
- `admin/blocked-ips.blade.php` - Blocked IPs
- `admin/whitelist.blade.php` - Whitelist
- `captcha-challenge.blade.php` - CAPTCHA page

### Customize Response Messages

In `config/anti-crawler.php`:

```php
'messages' => [
    'rate_limit_exceeded' => 'Too many requests. Please try again later.',
    'bot_detected' => 'Automated access detected. Please verify you are human.',
    'ip_blocked' => 'Your IP address has been blocked due to suspicious activity.',
    'captcha_required' => 'Please complete the verification challenge.',
],
```

### Add Custom User Agents to Blacklist

In `config/anti-crawler.php`:

```php
'detection' => [
    'user_agent_blacklist' => [
        'curl',
        'wget',
        'python-requests',
        'scrapy',
        // Add your custom patterns
        'my-custom-bot',
        'suspicious-crawler',
    ],
],
```

### Whitelist Legitimate Crawlers

In `config/anti-crawler.php`:

```php
'detection' => [
    'user_agent_whitelist' => [
        'Googlebot',
        'Bingbot',
        'DuckDuckBot',
        // Add more legitimate crawlers
        'YourLegitimateBot',
    ],
],
```

---

## Troubleshooting

### Issue: Rate Limiting Not Working

**Solution**: Ensure Redis is running and configured correctly.

```bash
# Check Redis connection
redis-cli ping
# Should return: PONG

# Check Laravel can connect to Redis
php artisan tinker
>>> Redis::ping()
```

### Issue: CAPTCHA Not Displaying

**Solutions**:
1. Check CAPTCHA keys are correct in `.env`
2. Ensure CAPTCHA provider is supported
3. Check browser console for JavaScript errors
4. Verify CAPTCHA_ENABLED is true

### Issue: All Requests Being Blocked

**Solutions**:
1. Check risk threshold: `ANTI_CRAWLER_RISK_THRESHOLD`
2. Add your IP to whitelist
3. Temporarily disable: `ANTI_CRAWLER_ENABLED=false`
4. Review detection logs to see why requests are flagged

### Issue: Admin Panel Not Accessible

**Solutions**:
1. Ensure routes are loaded (check `routes/web.php`)
2. Apply authentication middleware
3. Clear route cache: `php artisan route:clear`
4. Check user has admin permissions

### Issue: Migrations Fail

**Solutions**:
1. Ensure database is configured correctly
2. Check for table name conflicts
3. Run: `php artisan migrate:fresh` (development only)
4. Check database user has CREATE TABLE permissions

---

## API Reference

### Services

#### BotDetectionService

```php
use VanToanTG\AntiCrawler\Services\BotDetectionService;

$service = app(BotDetectionService::class);

// Detect bot from request
$result = $service->detectBot($request);
// Returns: ['is_bot' => bool, 'risk_score' => int, 'reason' => string, ...]

// Check if user agent is known bot
$isBot = $service->isKnownBot($userAgent);

// Check if whitelisted bot
$isWhitelisted = $service->isWhitelistedBot($userAgent);

// Calculate risk score
$score = $service->calculateRiskScore($request);
```

#### RateLimitService

```php
use VanToanTG\AntiCrawler\Services\RateLimitService;

$service = app(RateLimitService::class);

// Check rate limit
$result = $service->checkRateLimit($request, 'anonymous');
// Returns: ['allowed' => bool, 'limit' => int, 'remaining' => int, ...]

// Increment counter
$service->incrementCounter($request, 'anonymous');

// Get remaining attempts
$remaining = $service->getRemainingAttempts($ip, $userId, 'anonymous');

// Block IP temporarily
$service->blockIpTemporarily($ip, 60); // 60 minutes

// Get rate limit headers
$headers = $service->getRateLimitHeaders($request, 'anonymous');
```

#### CaptchaService

```php
use VanToanTG\AntiCrawler\Services\CaptchaService;

$service = app(CaptchaService::class);

// Generate challenge
$token = $service->generateChallenge($ip, $userId);

// Verify challenge
$isValid = $service->verifyChallenge($response, $ip);

// Check if needs challenge
$needs = $service->needsChallenge($ip, $riskScore);

// Get widget HTML
$html = $service->getWidgetHtml();
```

### Models

#### BotDetectionLog

```php
// Scopes
BotDetectionLog::highRisk(70)->get();
BotDetectionLog::byAction('blocked')->get();
BotDetectionLog::byIp('123.45.67.89')->get();
BotDetectionLog::recent(24)->get(); // Last 24 hours
BotDetectionLog::dateRange($from, $to)->get();
```

#### BlockedIp

```php
// Static methods
BlockedIp::isBlocked($ip);
BlockedIp::blockIp($ip, $reason, $blockedBy, $userId, $expiresAt);
BlockedIp::unblockIp($ip);

// Scopes
BlockedIp::active()->get();

// Instance methods
$blocked->isExpired();
$blocked->isPermanent();
```

#### IpWhitelist

```php
// Static methods
IpWhitelist::isWhitelisted($ip);
IpWhitelist::addIp($ip, $description, $userId);
IpWhitelist::removeIp($ip);
```

#### CaptchaChallenge

```php
// Scopes
CaptchaChallenge::unsolved()->get();
CaptchaChallenge::solved()->get();
CaptchaChallenge::byIp($ip)->get();
CaptchaChallenge::recent(60)->get(); // Last 60 minutes

// Static methods
CaptchaChallenge::getSuccessRate($ip);

// Instance methods
$challenge->markAsSolved();
$challenge->isExpired();
```

---

## Performance Considerations

- **Redis**: Uses Redis for caching, adds ~5-10ms per request
- **Database**: Proper indexing on all tables for fast queries
- **Logging**: Configure `ANTI_CRAWLER_LOG_ALL=false` to only log suspicious activity
- **Retention**: Set `ANTI_CRAWLER_LOG_RETENTION` to auto-clean old logs

---

## Security Best Practices

1. **Keep CAPTCHA keys secret** - Never commit to version control
2. **Use HTTPS** - Especially for CAPTCHA verification
3. **Regular monitoring** - Check admin panel for unusual patterns
4. **Whitelist carefully** - Only add trusted IPs
5. **Adjust thresholds** - Based on your traffic patterns
6. **Update regularly** - Keep package updated for security patches

---

## Support

For issues, questions, or contributions:
- GitHub Issues: [Repository URL]
- Documentation: This guide
- Email: nguyennguyen.vt88@gmail.com

---

## License

MIT License - See LICENSE file for details
