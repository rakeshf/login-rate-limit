# Login Rate Limit

Lightweight login rate-limiting for WordPress. Provides Redis- or filesystem-backed limits and blocks excessive login attempts.

---

**WordPress.org submission note**

When submitting to the WordPress.org plugin directory include a `readme.txt` with the required headers. Example metadata you should provide:

- Stable tag: `trunk` or a version like `1.0.0`
- Requires at least: `5.0`
- Tested up to: `6.6`
- Requires PHP: `7.0`
- Tags: `security, login, rate-limit, firewall`
- License: `GPLv2 or later`

You can put the following content into `readme.txt` for submission:

```
=== Login Rate Limit ===

Contributors: rakeshf
Donate link: https://github.com/sponsors/rakeshf
Tags: security, login, rate-limit, firewall
Requires at least: 5.0
Tested up to: 6.6
Requires PHP: 7.0
Stable tag: trunk
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

== Description ==

Blocks excessive login attempts using a lightweight rate limiter. Supports Redis (phpredis) or a filesystem fallback for environments without Redis.

== Installation ==

1. Upload the plugin folder to `/wp-content/plugins/`.
2. Install dependencies with `composer install` (for development) or bundle a release zip with `vendor/` included.
3. Activate the plugin in the WordPress admin.

== Frequently Asked Questions ==

= Does this require Redis?
No — Redis is optional. If Redis is selected but unavailable, the plugin falls back to filesystem caching.

== Changelog ==

= 0.1.0
+ Initial release: login rate limiting and filesystem/Redis support.

```

---

## Quick Start

1. Install the plugin (upload or clone into `wp-content/plugins/login-rate-limit`).
2. (Optional) Configure `waf_storage_type` and `waf_redis_dsn` in the plugin settings page.

## Notes for packagers

- If you publish a ZIP for WordPress.org, include the `vendor/` directory populated by `composer install` so users don't need composer on production.
- The plugin implementation is compatible down to PHP 7.0; however, many modern dependencies require PHP >= 8.0. Prefer PHP 8.x for development and CI.

---

If you want, I can also generate a proper `readme.txt` file and add a `RELEASE.md` with packaging instructions for WordPress.org.
