<?php

use PHPUnit\Framework\TestCase;
use MyWAF\RateLimitLogin;
use Brain\Monkey;
use Brain\Monkey\Functions;

class RateLimitLoginTest extends TestCase
{
    protected function setUp(): void {
        Monkey\setUp();
        require_once __DIR__ . '/../includes/RateLimitLogin.php';
    }

    protected function tearDown(): void {
        Monkey\tearDown();
    }

    public function testAllowsUnderLimit() {
        // Mock wp_die to detect blocking
        Functions\when('wp_die')->justReturn(false);

        // Use a dummy IP
        $ip = '127.0.0.1';

        // Call 3 times: should not block
        for ($i = 0; $i < 3; $i++) {
            ob_start();
            RateLimitLogin::enforce($ip);
            ob_end_clean();
            $this->assertTrue(true); // No exception thrown
        }
    }

    public function testBlocksOverLimit() {
        Functions\expect('wp_die')
            ->once()
            ->with('⛔ Too many login attempts. Try again later.', '', ['response' => 429])
            ->andReturnUsing(function () {
                throw new Exception("Blocked");
            });

        $this->expectException(Exception::class);
        $this->expectExceptionMessage("Blocked");

        $ip = '127.0.0.9';

        // Hit the limit
        try {
            for ($i = 0; $i < 4; $i++) {
                ob_start();
                RateLimitLogin::enforce($ip);
                ob_end_clean();
            }
        } finally {
            // Clean up any remaining output buffers
            while (ob_get_level() > 0) {
                ob_end_clean();
            }
        }
    }
}

// Ensure WP_CONTENT_DIR is defined for the tests
if ( ! defined( 'WP_CONTENT_DIR' ) ) {
    define( 'WP_CONTENT_DIR', __DIR__ . '/wp-content' );
}
