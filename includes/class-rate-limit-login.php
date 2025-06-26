<?php

namespace LoginRateLimit;

use Symfony\Component\RateLimiter\RateLimiterFactory;
use Symfony\Component\RateLimiter\Storage\CacheStorage;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;

class Rate_Limit_Login {
    public static function enforce($ip, $allowedAttempts = 5) {
        $cache = new FilesystemAdapter('waf', 0, WP_CONTENT_DIR . '/uploads/waf-login-cache');
        $storage = new CacheStorage($cache);

        $factory = new RateLimiterFactory([
            'id' => $ip,
            'policy' => 'fixed_window',
            'limit' => $allowedAttempts,
            'interval' => '60 Seconds',
        ], $storage);

        $limiter = $factory->create($ip);
        $limit = $limiter->consume();

        if (!$limit->isAccepted()) {
            wp_die('⛔ Too many login attempts. Try again later.', '', ['response' => 429]);
        }
    }
}
    