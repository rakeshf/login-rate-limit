<?php

// Compatibility wrapper for tests expecting MyWAF\RateLimitLogin class

namespace MyWAF;

use LoginRateLimit\Rate_Limit_Login;

// Ensure the actual implementation is loaded when running tests without Composer autoload
@require_once __DIR__ . '/class-rate-limit-login.php';

if (!class_exists(__NAMESPACE__ . '\\RateLimitLogin')) {
    class RateLimitLogin {
        public static function enforce($ip, $allowedAttempts = null) {
            if ($allowedAttempts === null) {
                $allowedAttempts = function_exists('waf_get_allowed_attempts') ? \waf_get_allowed_attempts() : 5;
            }
            return Rate_Limit_Login::enforce($ip, $allowedAttempts);
        }
    }
}

// Also expose a global class name for older code expecting \RateLimitLogin
if (!class_exists('RateLimitLogin')) {
    class_alias(__NAMESPACE__ . '\\RateLimitLogin', 'RateLimitLogin');
}
