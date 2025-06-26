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
    $current = waf_get_allowed_attempts();
    ?>
    <div class="wrap">
        <h1>Login Rate Limit Plugin Settings</h1>
        <form method="post">
            <?php wp_nonce_field('waf_plugin_settings'); ?>
            <table class="form-table">
                <tr>
                    <th scope="row"><label for="waf_allowed_attempts">Allowed Login Attempts</label></th>
                    <td>
                        <input name="waf_allowed_attempts" type="number" id="waf_allowed_attempts" value="<?php echo esc_attr($current); ?>" min="1" class="small-text" />
                    </td>
                </tr>
            </table>
            <?php submit_button('Save Changes'); ?>
        </form>
    </div>
    <?php
}

// Use the configured value in your rate limiter
add_action('login_init', function () {
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    LoginRateLimit\Rate_Limit_Login::enforce($ip, waf_get_allowed_attempts());
});
