# Changelog

All notable changes to the Laravel Anti-Crawler package will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.0.0] - 2025-12-26

### Added
- Initial release of Laravel Anti-Crawler package
- Multi-layered bot detection system
  - User agent analysis and classification
  - Behavioral pattern detection
  - Headless browser detection
  - Risk scoring system (0-100)
- Advanced rate limiting
  - Redis-based sliding window algorithm
  - Per-IP and per-user rate limits
  - Configurable tiers (anonymous, authenticated, custom)
  - Automatic IP blocking after threshold violations
- CAPTCHA integration
  - Cloudflare Turnstile support
  - Google reCAPTCHA v2/v3 support
  - hCaptcha support
  - Configurable trigger thresholds
- IP management
  - Automatic and manual IP blocking
  - IP whitelisting for trusted sources
  - Expiration support for temporary blocks
- Comprehensive logging
  - Bot detection event tracking
  - Risk score logging
  - Request header analysis
  - Filterable admin interface
- Admin dashboard
  - Statistics overview
  - Recent activity monitoring
  - Top offenders tracking
  - CAPTCHA success rate metrics
- Admin panel features
  - Detection logs with advanced filtering
  - Blocked IPs management
  - Whitelist management
  - Bootstrap 5 responsive UI
- Database migrations
  - `bot_detection_logs` table
  - `blocked_ips` table
  - `ip_whitelist` table
  - `captcha_challenges` table
- Middleware
  - `anti-crawler` - Main protection middleware
  - `bot-detect` - Specialized bot detection
- Service classes
  - `BotDetectionService` - Bot detection logic
  - `RateLimitService` - Rate limiting logic
  - `CaptchaService` - CAPTCHA handling
- Eloquent models with scopes and helpers
- Comprehensive configuration options
- Package auto-discovery for Laravel 11+
- Asset publishing (config, migrations, views)

### Features
- Configurable detection rules and thresholds
- Progressive throttling
- Customizable response messages
- Support for custom bot detection logic
- Extensible service architecture
- Redis caching for performance
- Proper indexing for database queries
- Bootstrap 5 admin interface with icons

### Documentation
- Complete README with installation guide
- Usage examples and code snippets
- Configuration documentation
- CAPTCHA setup instructions
- Customization guide
- MIT License

[1.0.0]: https://github.com/vantoantg/laravel-anti-crawler/releases/tag/v1.0.0
