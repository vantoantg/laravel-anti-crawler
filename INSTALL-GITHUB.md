# Installing Laravel Anti-Crawler from GitHub

## Prerequisites

- PHP 8.2 or higher
- Laravel 10.x, 11.x, or 12.x
- Redis installed and configured
- Composer

---

## Installation Steps

### Step 1: Add GitHub Repository to composer.json

In your Laravel project's `composer.json`, add the repository:

```json
{
    "repositories": [
        {
            "type": "vcs",
            "url": "https://github.com/vantoantg/laravel-anti-crawler"
        }
    ]
}
```

### Step 2: Require the Package

```bash
composer require vantoantg/laravel-anti-crawler:dev-master
```

Or for a specific version (when tagged):

```bash
composer require vantoantg/laravel-anti-crawler:^1.0
```

### Step 3: Publish Package Assets

```bash
# Publish all assets
php artisan vendor:publish --provider="VanToanTG\AntiCrawler\AntiCrawlerServiceProvider"
```

Or publish individually:

```bash
# Configuration
php artisan vendor:publish --tag=anti-crawler-config

# Migrations
php artisan vendor:publish --tag=anti-crawler-migrations

# Views (optional)
php artisan vendor:publish --tag=anti-crawler-views
```

### Step 4: Run Migrations

```bash
php artisan migrate
```

### Step 5: Configure Environment

Add to your `.env` file:

```env
# Anti-Crawler Configuration
ANTI_CRAWLER_ENABLED=true

# Rate Limits
ANTI_CRAWLER_RATE_ANONYMOUS_MINUTE=60
ANTI_CRAWLER_RATE_ANONYMOUS_HOUR=500
ANTI_CRAWLER_RATE_AUTH_MINUTE=120
ANTI_CRAWLER_RATE_AUTH_HOUR=2000

# Bot Detection
ANTI_CRAWLER_RISK_THRESHOLD=70
ANTI_CRAWLER_AUTO_BLOCK_THRESHOLD=5

# CAPTCHA (Get keys from https://dash.cloudflare.com/)
CAPTCHA_ENABLED=true
CAPTCHA_PROVIDER=turnstile
CAPTCHA_SITE_KEY=your-site-key
CAPTCHA_SECRET_KEY=your-secret-key
CAPTCHA_TRIGGER_THRESHOLD=60

# Redis
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379
```

### Step 6: Apply Middleware

In your `routes/web.php`:

```php
Route::middleware(['anti-crawler'])->group(function () {
    Route::get('/books', [BookController::class, 'index']);
    Route::get('/books/{slug}', [BookController::class, 'show']);
});
```

---

## Updating the Package

To update to the latest version:

```bash
composer update vantoantg/laravel-anti-crawler
```

---

## Troubleshooting

### Issue: "satisfiable by... but these were not loaded"

This means there's a Laravel version conflict. The package supports Laravel 10.x, 11.x, and 12.x.

**Check your Laravel version:**
```bash
php artisan --version
```

**If using Laravel 9 or below**, you need to upgrade Laravel or modify the package requirements.

### Issue: "Cannot create cache directory"

This is a permissions warning and can be ignored. If it bothers you:

```bash
# Fix permissions
sudo chown -R $USER:$USER /var/www/.cache
```

Or run with sudo (not recommended):

```bash
sudo composer require vantoantg/laravel-anti-crawler:dev-master
```

### Issue: Class not found after installation

**Solution:**
```bash
composer dump-autoload
php artisan config:clear
php artisan cache:clear
```

### Issue: Migrations already exist

If you've published migrations before:

```bash
# Delete old migrations
rm database/migrations/*_create_bot_detection_logs_table.php
rm database/migrations/*_create_blocked_ips_table.php
rm database/migrations/*_create_ip_whitelist_table.php
rm database/migrations/*_create_captcha_challenges_table.php

# Publish again
php artisan vendor:publish --tag=anti-crawler-migrations
```

---

## Verifying Installation

### Check if package is installed:

```bash
composer show vantoantg/laravel-anti-crawler
```

### Check if service provider is loaded:

```bash
php artisan about
```

Look for `VanToanTG\AntiCrawler\AntiCrawlerServiceProvider` in the providers list.

### Test middleware:

Create a test route:

```php
Route::get('/test-protection', function () {
    return 'Protected!';
})->middleware('anti-crawler');
```

Visit: `http://your-app.test/test-protection`

---

## Next Steps

1. **Get CAPTCHA keys** from Cloudflare Turnstile or your preferred provider
2. **Configure rate limits** based on your traffic
3. **Access admin panel** at `/admin/bot-protection`
4. **Monitor logs** and adjust thresholds as needed

For detailed usage, see [GUIDE.md](GUIDE.md)

---

## Uninstalling

```bash
# Remove package
composer remove vantoantg/laravel-anti-crawler

# Rollback migrations (optional)
php artisan migrate:rollback --step=4

# Remove published files
rm config/anti-crawler.php
rm -rf resources/views/vendor/anti-crawler
```

---

## Support

- **GitHub Issues**: https://github.com/vantoantg/laravel-anti-crawler/issues
- **Documentation**: [GUIDE.md](GUIDE.md)
- **Email**: nguyennguyen.vt88@gmail.com
