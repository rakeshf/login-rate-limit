<?php

namespace LoginRateLimit;

class Rate_Limit_Login {
    public static function enforce($ip, $allowedAttempts = null) {
        if ($allowedAttempts === null) {
            $allowedAttempts = (\function_exists('waf_get_allowed_attempts') ? \waf_get_allowed_attempts() : 3);
        }
        $storageType = (\function_exists('get_option') ? \get_option('waf_storage_type', 'filesystem') : 'filesystem');
        $intervalOption = (\function_exists('get_option') ? \get_option('waf_interval', '60 seconds') : '60 seconds');
        if (preg_match('/\d+/', $intervalOption, $m)) {
            $interval = intval($m[0]);
        } else {
            $interval = 60;
        }

        // Redis-backed limiter (uses phpredis extension)
        if ($storageType === 'redis' && class_exists('Redis')) {
            $dsn = (\function_exists('get_option') ? \get_option('waf_redis_dsn', 'redis://localhost') : 'redis://localhost');
            $parsed = parse_url($dsn);
            $host = isset($parsed['host']) ? $parsed['host'] : '127.0.0.1';
            $port = isset($parsed['port']) ? $parsed['port'] : 6379;
            $pass = isset($parsed['pass']) ? $parsed['pass'] : null;
            try {
                $redis = new \Redis();
                $redis->connect($host, $port);
                if ($pass) {
                    @$redis->auth($pass);
                }
                $key = 'waf_login:' . $ip;
                $count = $redis->incr($key);
                if ($count === 1) {
                    $redis->expire($key, $interval);
                }
                if ($count > (int)$allowedAttempts) {
                    \wp_die('⛔ Too many login attempts. Try again later.', '', array('response' => 429));
                }
                return;
            } catch (\Exception $e) {
                // Fall back to filesystem storage below
            }
        }

        // Filesystem fallback limiter
        $dir = \defined('WP_CONTENT_DIR') ? WP_CONTENT_DIR . '/uploads/waf-login-cache' : \sys_get_temp_dir() . '/waf-login-cache';
        if (!\is_dir($dir)) {
            @\mkdir($dir, 0755, true);
        }
        $file = $dir . '/' . md5($ip) . '.json';
        $now = time();
        $data = array('count' => 0, 'first' => $now);

        $fp = @\fopen($file, 'c+');
        if ($fp === false) {
            // Fail-open if cache cannot be used
            return;
        }
        if (!\flock($fp, LOCK_EX)) {
            fclose($fp);
            return;
        }
        \clearstatcache(true, $file);
        $contents = \stream_get_contents($fp);
        if ($contents !== '') {
            $decoded = \json_decode($contents, true);
            if (is_array($decoded)) {
                $data = $decoded;
            }
        }

        if ($now - $data['first'] > $interval) {
            $data['count'] = 1;
            $data['first'] = $now;
        } else {
            $data['count'] = isset($data['count']) ? $data['count'] + 1 : 1;
        }

        \ftruncate($fp, 0);
        \rewind($fp);
        \fwrite($fp, \json_encode($data));
        \fflush($fp);
        \flock($fp, LOCK_UN);
        \fclose($fp);

        if ($data['count'] > (int)$allowedAttempts) {
            \wp_die('⛔ Too many login attempts. Try again later.', '', array('response' => 429));
        }
    }
}
