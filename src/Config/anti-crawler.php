<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Anti-Crawler Protection Enabled
    |--------------------------------------------------------------------------
    |
    | Enable or disable the entire anti-crawler protection system.
    |
    */
    'enabled' => env('ANTI_CRAWLER_ENABLED', true),

    /*
    |--------------------------------------------------------------------------
    | Rate Limiting Configuration
    |--------------------------------------------------------------------------
    |
    | Configure rate limits for different user types and actions.
    |
    */
    'rate_limits' => [
        'anonymous' => [
            'per_minute' => env('ANTI_CRAWLER_RATE_ANONYMOUS_MINUTE', 60),
            'per_hour' => env('ANTI_CRAWLER_RATE_ANONYMOUS_HOUR', 500),
        ],
        'authenticated' => [
            'per_minute' => env('ANTI_CRAWLER_RATE_AUTH_MINUTE', 120),
            'per_hour' => env('ANTI_CRAWLER_RATE_AUTH_HOUR', 2000),
        ],
        'chapter_reading' => [
            'per_minute' => env('ANTI_CRAWLER_RATE_CHAPTER_MINUTE', 10),
        ],
        'search' => [
            'per_minute' => env('ANTI_CRAWLER_RATE_SEARCH_MINUTE', 20),
        ],
        'api' => [
            'per_minute' => env('ANTI_CRAWLER_RATE_API_MINUTE', 30),
            'per_hour' => env('ANTI_CRAWLER_RATE_API_HOUR', 1000),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Bot Detection Configuration
    |--------------------------------------------------------------------------
    |
    | Configure bot detection rules and thresholds.
    |
    */
    'detection' => [
        // User agents that are always blocked
        'user_agent_blacklist' => [
            'curl',
            'wget',
            'python-requests',
            'scrapy',
            'bot',
            'crawler',
            'spider',
            'scraper',
            'headless',
            'phantomjs',
            'selenium',
            'webdriver',
        ],

        // User agents that are always allowed (legitimate crawlers)
        'user_agent_whitelist' => [
            'Googlebot',
            'Bingbot',
            'Slurp', // Yahoo
            'DuckDuckBot',
            'Baiduspider',
            'YandexBot',
            'facebookexternalhit',
            'LinkedInBot',
            'Twitterbot',
        ],

        // Risk score threshold (0-100) - block if above this
        'risk_threshold' => env('ANTI_CRAWLER_RISK_THRESHOLD', 70),

        // Number of violations before auto-blocking IP
        'auto_block_threshold' => env('ANTI_CRAWLER_AUTO_BLOCK_THRESHOLD', 5),

        // Auto-block duration in minutes
        'auto_block_duration' => env('ANTI_CRAWLER_AUTO_BLOCK_DURATION', 60),

        // Suspicious header patterns
        'suspicious_patterns' => [
            'missing_accept_header' => 20, // risk score points
            'missing_accept_language' => 15,
            'missing_accept_encoding' => 10,
            'suspicious_user_agent' => 30,
            'no_referrer' => 5,
            'rapid_requests' => 25,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | CAPTCHA Configuration
    |--------------------------------------------------------------------------
    |
    | Configure CAPTCHA challenge settings.
    | Supported providers: turnstile, recaptcha_v2, recaptcha_v3, hcaptcha
    |
    */
    'captcha' => [
        'enabled' => env('CAPTCHA_ENABLED', true),
        'provider' => env('CAPTCHA_PROVIDER', 'turnstile'), // turnstile, recaptcha_v2, recaptcha_v3, hcaptcha
        'site_key' => env('CAPTCHA_SITE_KEY'),
        'secret_key' => env('CAPTCHA_SECRET_KEY'),
        
        // Risk score that triggers CAPTCHA challenge
        'trigger_threshold' => env('CAPTCHA_TRIGGER_THRESHOLD', 60),
        
        // CAPTCHA verification endpoint URLs
        'verify_urls' => [
            'turnstile' => 'https://challenges.cloudflare.com/turnstile/v0/siteverify',
            'recaptcha_v2' => 'https://www.google.com/recaptcha/api/siteverify',
            'recaptcha_v3' => 'https://www.google.com/recaptcha/api/siteverify',
            'hcaptcha' => 'https://hcaptcha.com/siteverify',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Honeypot Configuration
    |--------------------------------------------------------------------------
    |
    | Configure honeypot traps for bots.
    |
    */
    'honeypot' => [
        'enabled' => env('ANTI_CRAWLER_HONEYPOT_ENABLED', true),
        'field_name' => env('ANTI_CRAWLER_HONEYPOT_FIELD', 'website_url'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Logging Configuration
    |--------------------------------------------------------------------------
    |
    | Configure what gets logged.
    |
    */
    'logging' => [
        // Log all requests or only suspicious ones
        'log_all_requests' => env('ANTI_CRAWLER_LOG_ALL', false),
        
        // Minimum risk score to log
        'min_risk_score_to_log' => env('ANTI_CRAWLER_MIN_LOG_SCORE', 50),
        
        // Keep logs for X days
        'retention_days' => env('ANTI_CRAWLER_LOG_RETENTION', 30),
    ],

    /*
    |--------------------------------------------------------------------------
    | Redis Configuration
    |--------------------------------------------------------------------------
    |
    | Configure Redis connection for rate limiting.
    |
    */
    'redis' => [
        'connection' => env('ANTI_CRAWLER_REDIS_CONNECTION', 'default'),
        'prefix' => env('ANTI_CRAWLER_REDIS_PREFIX', 'anti_crawler:'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Default Whitelisted IPs
    |--------------------------------------------------------------------------
    |
    | IPs that should always be whitelisted.
    |
    */
    'default_whitelist' => [
        '127.0.0.1',
        '::1',
    ],

    /*
    |--------------------------------------------------------------------------
    | Response Messages
    |--------------------------------------------------------------------------
    |
    | Customize response messages for blocked requests.
    |
    */
    'messages' => [
        'rate_limit_exceeded' => 'Too many requests. Please try again later.',
        'bot_detected' => 'Automated access detected. Please verify you are human.',
        'ip_blocked' => 'Your IP address has been blocked due to suspicious activity.',
        'captcha_required' => 'Please complete the verification challenge.',
    ],
];
