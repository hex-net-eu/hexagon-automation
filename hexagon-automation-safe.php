<?php
/**
 * Plugin Name:     Hexagon Automation (Safe Mode)
 * Plugin URI:      https://hex-net.eu/hexagon-automation
 * Description:     AI-powered content & social media automation for WordPress - Safe testing version.
 * Version:         3.0.1
 * Requires at least: 5.0
 * Requires PHP: 7.4
 * Tested up to:    6.4
 * Author:          Hexagon Technology
 * Author URI:      https://hex-net.eu
 * Text Domain:     hexagon-automation
 * Domain Path:     /languages
 */
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Guarded definitions
if ( ! defined( 'HEXAGON_PATH' ) ) {
    define( 'HEXAGON_PATH', plugin_dir_path( __FILE__ ) );
}
if ( ! defined( 'HEXAGON_URL' ) ) {
    define( 'HEXAGON_URL', plugin_dir_url( __FILE__ ) );
}
if ( ! defined( 'HEXAGON_VERSION' ) ) {
    define( 'HEXAGON_VERSION', '3.0.1' );
}

// Load essential files only
require_once HEXAGON_PATH . 'includes/functions.php';

/**
 * Simple activation handler for testing
 */
class Hexagon_Safe_Activation {
    public static function activate() {
        try {
            // Check minimum requirements
            if (version_compare(PHP_VERSION, '7.4', '<')) {
                wp_die('Hexagon Automation requires PHP 7.4 or higher. Current version: ' . PHP_VERSION);
            }
            
            if (version_compare(get_bloginfo('version'), '5.0', '<')) {
                wp_die('Hexagon Automation requires WordPress 5.0 or higher. Current version: ' . get_bloginfo('version'));
            }
            
            global $wpdb;
            
            // Create logs table
            $table = $wpdb->prefix . 'hex_logs';
            $charset_collate = $wpdb->get_charset_collate();
            $sql = "CREATE TABLE IF NOT EXISTS $table (
                id bigint(20) NOT NULL AUTO_INCREMENT,
                action varchar(100) NOT NULL,
                context text NOT NULL,
                level varchar(20) NOT NULL DEFAULT 'info',
                created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY  (id),
                KEY idx_level_created (level, created_at),
                KEY idx_action (action)
            ) $charset_collate;";
            
            require_once ABSPATH . 'wp-admin/includes/upgrade.php';
            dbDelta( $sql );
            
            // Create default options
            add_option( 'hexagon_ai_provider', 'chatgpt' );
            add_option( 'hexagon_plugin_version', '3.0.1' );
            add_option( 'hexagon_safe_mode', true );
            
            // Log successful activation
            hexagon_log('Plugin Activated', 'Hexagon Automation v3.0.1 activated in safe mode', 'success');
            
            // Add admin notice
            add_option('hexagon_activation_notice', 'Plugin activated successfully in safe mode!');
            
        } catch (Exception $e) {
            wp_die('Hexagon Automation activation failed: ' . $e->getMessage());
        }
    }
    
    public static function uninstall() {
        global $wpdb;
        $table = $wpdb->prefix . 'hex_logs';
        $wpdb->query( "DROP TABLE IF EXISTS $table" );
        
        delete_option( 'hexagon_ai_provider' );
        delete_option( 'hexagon_plugin_version' );
        delete_option( 'hexagon_safe_mode' );
        delete_option( 'hexagon_activation_notice' );
    }
}

/**
 * Simple admin interface
 */
class Hexagon_Safe_Admin {
    public static function init() {
        add_action('admin_menu', [__CLASS__, 'add_admin_menu']);
        add_action('admin_notices', [__CLASS__, 'show_activation_notice']);
    }
    
    public static function add_admin_menu() {
        add_menu_page(
            'Hexagon Automation',
            'Hexagon Auto',
            'manage_options',
            'hexagon-automation',
            [__CLASS__, 'admin_page'],
            'dashicons-admin-generic',
            30
        );
    }
    
    public static function show_activation_notice() {
        $notice = get_option('hexagon_activation_notice');
        if ($notice) {
            echo '<div class="notice notice-success is-dismissible"><p>' . esc_html($notice) . '</p></div>';
            delete_option('hexagon_activation_notice');
        }
    }
    
    public static function admin_page() {
        global $wpdb;
        
        $table = $wpdb->prefix . 'hex_logs';
        $logs = $wpdb->get_results("SELECT * FROM $table ORDER BY created_at DESC LIMIT 10");
        
        ?>
        <div class="wrap">
            <h1>Hexagon Automation - Safe Mode</h1>
            
            <div class="notice notice-info">
                <p><strong>Safe Mode Active:</strong> Plugin is running in testing mode with basic functionality only.</p>
            </div>
            
            <h2>System Information</h2>
            <table class="widefat">
                <tr><td><strong>Plugin Version:</strong></td><td><?php echo HEXAGON_VERSION; ?></td></tr>
                <tr><td><strong>WordPress Version:</strong></td><td><?php echo get_bloginfo('version'); ?></td></tr>
                <tr><td><strong>PHP Version:</strong></td><td><?php echo PHP_VERSION; ?></td></tr>
                <tr><td><strong>Safe Mode:</strong></td><td><?php echo get_option('hexagon_safe_mode') ? 'Enabled' : 'Disabled'; ?></td></tr>
            </table>
            
            <h2>Test Logging System</h2>
            <p>
                <button type="button" onclick="testLog()" class="button button-primary">Test Log Entry</button>
                <button type="button" onclick="location.reload()" class="button">Refresh Logs</button>
            </p>
            
            <h3>Recent Logs</h3>
            <table class="widefat striped">
                <thead>
                    <tr>
                        <th>Time</th>
                        <th>Action</th>
                        <th>Context</th>
                        <th>Level</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($logs): ?>
                        <?php foreach ($logs as $log): ?>
                            <tr>
                                <td><?php echo esc_html($log->created_at); ?></td>
                                <td><?php echo esc_html($log->action); ?></td>
                                <td><?php echo esc_html($log->context); ?></td>
                                <td><span class="log-level-<?php echo esc_attr($log->level); ?>"><?php echo esc_html($log->level); ?></span></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="4">No logs found</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
            
            <div class="hexagon-debug-export" style="margin: 20px 0; padding: 15px; background: #f9f9f9; border-left: 4px solid #007cba;">
                <h3>🔧 Debug Export dla bizrun.eu</h3>
                <p>Pobierz pliki diagnostyczne do załączenia w zgłoszeniu o pomoc:</p>
                <p>
                    <button onclick="downloadLogs()" class="button button-primary">📄 Pobierz Logi (JSON)</button>
                    <button onclick="downloadSystemInfo()" class="button button-secondary" style="margin-left: 10px;">⚙️ Pobierz Info Systemowe (JSON)</button>
                </p>
                <p><small>
                    <strong>Logi:</strong> Zawierają ostatnie 7 dni logów aktywności plugina<br>
                    <strong>Info Systemowe:</strong> Konfiguracja WordPress, PHP, bazy danych i ustawienia plugina
                </small></p>
            </div>
            
            <style>
                .log-level-success { color: green; font-weight: bold; }
                .log-level-error { color: red; font-weight: bold; }
                .log-level-warning { color: orange; font-weight: bold; }
                .log-level-info { color: blue; }
            </style>
            
            <script>
                function testLog() {
                    fetch('<?php echo admin_url('admin-ajax.php'); ?>', {
                        method: 'POST',
                        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                        body: 'action=hexagon_test_log&_wpnonce=<?php echo wp_create_nonce('hexagon_test'); ?>'
                    }).then(() => location.reload());
                }
                
                function downloadLogs() {
                    const data = <?php echo json_encode(self::exportLogs()); ?>;
                    const blob = new Blob([JSON.stringify(data, null, 2)], { type: 'application/json' });
                    const url = URL.createObjectURL(blob);
                    const a = document.createElement('a');
                    a.href = url;
                    a.download = 'hexagon-logs-bizrun-' + new Date().toISOString().slice(0, 19).replace(/:/g, '-') + '.json';
                    document.body.appendChild(a);
                    a.click();
                    document.body.removeChild(a);
                    URL.revokeObjectURL(url);
                }
                
                function downloadSystemInfo() {
                    const data = <?php echo json_encode(self::exportSystemInfo()); ?>;
                    const blob = new Blob([JSON.stringify(data, null, 2)], { type: 'application/json' });
                    const url = URL.createObjectURL(blob);
                    const a = document.createElement('a');
                    a.href = url;
                    a.download = 'hexagon-system-info-bizrun-' + new Date().toISOString().slice(0, 19).replace(/:/g, '-') + '.json';
                    document.body.appendChild(a);
                    a.click();
                    document.body.removeChild(a);
                    URL.revokeObjectURL(url);
                }
            </script>
        </div>
        <?php
    }
    
    private static function exportLogs() {
        global $wpdb;
        $table = $wpdb->prefix . 'hex_logs';
        
        $logs = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $table WHERE created_at >= %s ORDER BY created_at DESC LIMIT 1000",
            date('Y-m-d H:i:s', strtotime('-7 days'))
        ));
        
        return [
            'export_info' => [
                'site' => 'bizrun.eu',
                'export_date' => current_time('mysql'),
                'plugin_version' => HEXAGON_VERSION,
                'total_logs' => count($logs),
                'days_exported' => 7
            ],
            'logs' => $logs ?: []
        ];
    }
    
    private static function exportSystemInfo() {
        global $wpdb;
        
        return [
            'export_info' => [
                'site' => 'bizrun.eu',
                'generated_at' => current_time('mysql'),
                'plugin_version' => HEXAGON_VERSION,
                'mode' => 'safe_mode'
            ],
            'wordpress' => [
                'version' => get_bloginfo('version'),
                'site_url' => home_url(),
                'admin_url' => admin_url(),
                'language' => get_locale(),
                'timezone' => get_option('timezone_string') ?: 'UTC',
                'wp_debug' => defined('WP_DEBUG') ? WP_DEBUG : false,
                'multisite' => is_multisite()
            ],
            'server' => [
                'php_version' => PHP_VERSION,
                'mysql_version' => $wpdb->db_version(),
                'memory_limit' => ini_get('memory_limit'),
                'max_execution_time' => ini_get('max_execution_time'),
                'upload_max_filesize' => ini_get('upload_max_filesize')
            ],
            'plugin' => [
                'version' => HEXAGON_VERSION,
                'safe_mode' => get_option('hexagon_safe_mode', false),
                'logs_count' => $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}hex_logs")
            ],
            'memory_usage' => [
                'current' => memory_get_usage(true),
                'peak' => memory_get_peak_usage(true),
                'limit' => wp_convert_hr_to_bytes(ini_get('memory_limit'))
            ]
        ];
    }
}

/**
 * Test AJAX handler
 */
add_action('wp_ajax_hexagon_test_log', function() {
    check_ajax_referer('hexagon_test');
    
    hexagon_log('Test Action', 'Manual test log entry from admin panel', 'info');
    
    wp_send_json_success(['message' => 'Test log created']);
});

// Register hooks
register_activation_hook( __FILE__, [ 'Hexagon_Safe_Activation', 'activate' ] );
register_uninstall_hook( __FILE__, [ 'Hexagon_Safe_Activation', 'uninstall' ] );

// Initialize admin interface
add_action('plugins_loaded', function() {
    if (is_admin()) {
        Hexagon_Safe_Admin::init();
    }
});