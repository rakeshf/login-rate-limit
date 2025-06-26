<?php
/**
 * Plugin Name:     Login Rate Limiter
 * Plugin URI:      https://github.com/rakeshf/login-rate-limit
 * Description:     📈 Brute Force Protection for login page.
 * Author:          Rakesh Falke
 * Author URI:      https://github.com/rakeshf
 * Text Domain:     stock-analyzer
 * Domain Path:     /languages
 * Version:         0.1.0
 *
 * @package         Login_Rate_Limit
 */

// === Configuration ===
function waf_get_allowed_attempts() {
    $default = 5;
    $option = get_option('waf_allowed_attempts', $default);
    return intval($option) > 0 ? intval($option) : $default;
}

function waf_get_storage_type() {
    $default = 'filesystem';
    $option = get_option('waf_storage_type', $default);
    return in_array($option, ['filesystem', 'redis']) ? $option : $default;
}

// Composer autoload
$autoload = __DIR__ . '/vendor/autoload.php';
if (file_exists($autoload)) {
    require_once $autoload;
} else {
    wp_die('Autoload file not found. Run composer install inside the plugin directory.');
}

require_once __DIR__ . '/includes/class-rate-limit-login.php';

// Admin menu for configuration
add_action('admin_menu', function () {
    add_options_page(
        'Login Rate Limit Settings',
        'Login Rate Limit Plugin',
        'manage_options',
        'login-rate-limit-plugin-settings',
        'login_rate_limit_plugin_settings_page'
    );
});

function login_rate_limit_plugin_settings_page() {
    if (isset($_POST['waf_allowed_attempts'])) {
        check_admin_referer('waf_plugin_settings');
        $attempts = max(1, intval($_POST['waf_allowed_attempts']));
        update_option('waf_allowed_attempts', $attempts);
        echo '<div class="updated"><p>Allowed attempts updated!</p></div>';
    }

    if (isset($_POST['waf_storage_type'])) {
        check_admin_referer('waf_plugin_settings');
        $storage_type = in_array($_POST['waf_storage_type'], ['filesystem', 'redis']) ? $_POST['waf_storage_type'] : 'filesystem';
        update_option('waf_storage_type', $storage_type);
        echo '<div class="updated"><p>Storage type updated!</p></div>';
    }

    if (isset($_POST['waf_redis_dsn'])) {
        update_option('waf_redis_dsn', sanitize_text_field($_POST['waf_redis_dsn']));
    }

    $current_attempts = waf_get_allowed_attempts();
    $current_storage = waf_get_storage_type();
    ?>
    <div class="wrap">
        <h1>Login Rate Limit Plugin Settings</h1>
        <form method="post">
            <?php wp_nonce_field('waf_plugin_settings'); ?>
            <table class="form-table">
                <tr>
                    <th scope="row"><label for="waf_allowed_attempts">Allowed Login Attempts</label></th>
                    <td>
                        <input name="waf_allowed_attempts" type="number" id="waf_allowed_attempts" value="<?php echo esc_attr($current_attempts); ?>" min="1" class="small-text" />
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="waf_storage_type">Storage Type</label></th>
                    <td>
                        <select name="waf_storage_type" id="waf_storage_type">
                            <option value="filesystem" <?php selected($current_storage, 'filesystem'); ?>>Filesystem</option>
                            <option value="redis" <?php selected($current_storage, 'redis'); ?>>Redis</option>
                        </select>
                    </td>
                </tr>
                <tr class="redis-settings" style="<?php echo $current_storage === 'redis' ? '' : 'display:none;'; ?>">
                    <th scope="row"><label for="waf_redis_dsn">Redis DSN</label></th>
                    <td>
                        <input name="waf_redis_dsn" type="text" id="waf_redis_dsn" value="<?php echo esc_attr(get_option('waf_redis_dsn', 'redis://localhost')); ?>" class="regular-text" />
                        <p class="description">Example: redis://localhost or redis://password@localhost:6379</p>
                    </td>
                </tr>
            </table>
            <?php submit_button('Save Changes'); ?>
        </form>
    </div>
    <script>
        (function($) {
            $('#waf_storage_type').change(function() {
                if ($(this).val() === 'redis') {
                    $('.redis-settings').show();
                } else {
                    $('.redis-settings').hide();
                }
            }).change();
        })(jQuery);
    </script>
    <?php
}

use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use Symfony\Component\Cache\Adapter\RedisAdapter;
use Symfony\Component\RateLimiter\Storage\CacheRateLimiterStorage;

function waf_get_storage() {
    $type = get_option('waf_storage_type', 'filesystem');
    if ($type === 'redis') {
        $dsn = get_option('waf_redis_dsn', 'redis://localhost');
        try {
            $redis = RedisAdapter::createConnection($dsn);
            $cache = new RedisAdapter($redis);
        } catch (Exception $e) {
            // Fallback to filesystem if Redis fails
            $cache = new FilesystemAdapter();
        }
    } else {
        $cache = new FilesystemAdapter();
    }
    return new CacheRateLimiterStorage($cache);
}

// Use the configured value in your rate limiter
add_action('login_init', function () {
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    LoginRateLimit\Rate_Limit_Login::enforce($ip, waf_get_allowed_attempts());
});
