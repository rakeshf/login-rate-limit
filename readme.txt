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
2. (Optional) Run `composer install` to install dev tools; for production bundle vendor into the zip.
3. Activate the plugin in the WordPress admin.

== Frequently Asked Questions ==

= Does this require Redis?
No — Redis is optional. If Redis is selected but unavailable, the plugin falls back to filesystem caching.

== Changelog ==

= 0.1.0 =
* Initial release: login rate limiting and filesystem/Redis support.
