# Installing Laravel Anti-Crawler Package Locally

## For Local Development (Recommended)

Since the package is in your local `packages` directory, follow these steps:

### Step 1: Update Your Main Project's composer.json

Navigate to your main Laravel project (e.g., `read-books-online/src`) and edit `composer.json`:

```json
{
    "repositories": [
        {
            "type": "path",
            "url": "../packages/laravel-anti-crawler",
            "options": {
                "symlink": true
            }
        }
    ],
    "require": {
        "vantoantg/laravel-anti-crawler": "@dev"
    }
}
```

**Note**: Adjust the `url` path based on your directory structure. The path should be relative from your main project to the package directory.

### Step 2: Install the Package

```bash
cd /path/to/your/main/laravel/project
composer require vantoantg/laravel-anti-crawler:@dev
```

Or if you already added it to composer.json:

```bash
composer update vantoantg/laravel-anti-crawler
```

### Step 3: Verify Installation

```bash
composer show vantoantg/laravel-anti-crawler
```

You should see the package details.

---

## Alternative: Direct Installation Without Composer

If you prefer not to use Composer for local development:

### Step 1: Create Symlink

```bash
# From your main Laravel project root
ln -s ../packages/laravel-anti-crawler vendor/vantoantg/laravel-anti-crawler
```

### Step 2: Register Service Provider Manually

In `config/app.php` (Laravel 10) or `bootstrap/app.php` (Laravel 11):

```php
// Laravel 11 - bootstrap/app.php
return Application::configure(basePath: dirname(__DIR__))
    ->withProviders([
        \VanToanTG\AntiCrawler\AntiCrawlerServiceProvider::class,
    ])
    // ...
```

### Step 3: Add Autoload to composer.json

In your main project's `composer.json`:

```json
{
    "autoload": {
        "psr-4": {
            "VanToanTG\\AntiCrawler\\": "vendor/vantoantg/laravel-anti-crawler/src/"
        }
    }
}
```

Then run:

```bash
composer dump-autoload
```

---

## Troubleshooting

### Issue: "Class not found" errors

**Solution**: Run `composer dump-autoload` in your main project.

### Issue: Changes in package not reflecting

**Solution**: 
1. Clear cache: `php artisan config:clear && php artisan cache:clear`
2. If using symlink, changes should reflect immediately
3. If not using symlink, run `composer update vantoantg/laravel-anti-crawler`

### Issue: Dependency conflicts

The package requires Laravel 11. If you're using a different version, you need to:

1. **Option A**: Update your Laravel to version 11
2. **Option B**: Modify the package's `composer.json` to support your Laravel version

To modify for Laravel 10:

```json
{
    "require": {
        "php": "^8.1",
        "illuminate/support": "^10.0|^11.0",
        "illuminate/database": "^10.0|^11.0",
        "illuminate/http": "^10.0|^11.0",
        "illuminate/redis": "^10.0|^11.0",
        "predis/predis": "^2.0"
    }
}
```

---

## After Installation

Once installed, follow the main GUIDE.md for:
1. Publishing assets
2. Running migrations
3. Configuration
4. Usage

Quick start:

```bash
# Publish all assets
php artisan vendor:publish --provider="VanToanTG\AntiCrawler\AntiCrawlerServiceProvider"

# Run migrations
php artisan migrate

# Configure .env
# Add CAPTCHA keys and other settings
```

---

## Example Directory Structure

```
your-project/
├── packages/
│   └── laravel-anti-crawler/     # Your package
│       ├── src/
│       ├── composer.json
│       └── ...
└── your-main-app/                 # Your Laravel app
    ├── app/
    ├── config/
    ├── composer.json              # Add repository here
    └── ...
```

In this case, from `your-main-app`, the path would be:
```json
"url": "../packages/laravel-anti-crawler"
```
