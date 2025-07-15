<?php
if (!defined('ABSPATH')) exit;

class Hexagon_Debug_Exporter {
    
    public static function init() {
        add_action('wp_ajax_hexagon_export_logs', [__CLASS__, 'export_logs']);
        add_action('wp_ajax_hexagon_export_system_info', [__CLASS__, 'export_system_info']);
        add_action('admin_post_hexagon_download_logs', [__CLASS__, 'download_logs']);
        add_action('admin_post_hexagon_download_system', [__CLASS__, 'download_system_info']);
    }
    
    public static function export_logs() {
        check_ajax_referer('hexagon_debug_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Insufficient permissions']);
        }
        
        global $wpdb;
        $table = $wpdb->prefix . 'hex_logs';
        
        // Get logs from last 7 days
        $logs = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $table WHERE created_at >= %s ORDER BY created_at DESC LIMIT 1000",
            date('Y-m-d H:i:s', strtotime('-7 days'))
        ));
        
        $log_data = [
            'export_date' => current_time('mysql'),
            'plugin_version' => HEXAGON_VERSION,
            'total_logs' => count($logs),
            'logs' => $logs
        ];
        
        wp_send_json_success([
            'data' => $log_data,
            'download_url' => admin_url('admin-post.php?action=hexagon_download_logs&_wpnonce=' . wp_create_nonce('hexagon_download'))
        ]);
    }
    
    public static function export_system_info() {
        check_ajax_referer('hexagon_debug_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Insufficient permissions']);
        }
        
        $system_info = self::gather_system_info();
        
        wp_send_json_success([
            'data' => $system_info,
            'download_url' => admin_url('admin-post.php?action=hexagon_download_system&_wpnonce=' . wp_create_nonce('hexagon_download'))
        ]);
    }
    
    public static function download_logs() {
        check_admin_referer('hexagon_download');
        
        if (!current_user_can('manage_options')) {
            wp_die('Insufficient permissions');
        }
        
        global $wpdb;
        $table = $wpdb->prefix . 'hex_logs';
        
        $logs = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $table WHERE created_at >= %s ORDER BY created_at DESC LIMIT 1000",
            date('Y-m-d H:i:s', strtotime('-7 days'))
        ));
        
        $log_data = [
            'export_info' => [
                'export_date' => current_time('mysql'),
                'plugin_version' => HEXAGON_VERSION,
                'wordpress_version' => get_bloginfo('version'),
                'php_version' => PHP_VERSION,
                'site_url' => home_url(),
                'total_logs' => count($logs)
            ],
            'logs' => $logs
        ];
        
        $filename = 'hexagon-logs-' . date('Y-m-d-H-i-s') . '.json';
        
        header('Content-Type: application/json');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: no-cache, must-revalidate');
        header('Expires: Sat, 26 Jul 1997 05:00:00 GMT');
        
        echo json_encode($log_data, JSON_PRETTY_PRINT);
        exit;
    }
    
    public static function download_system_info() {
        check_admin_referer('hexagon_download');
        
        if (!current_user_can('manage_options')) {
            wp_die('Insufficient permissions');
        }
        
        $system_info = self::gather_system_info();
        $filename = 'hexagon-system-info-' . date('Y-m-d-H-i-s') . '.json';
        
        header('Content-Type: application/json');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: no-cache, must-revalidate');
        header('Expires: Sat, 26 Jul 1997 05:00:00 GMT');
        
        echo json_encode($system_info, JSON_PRETTY_PRINT);
        exit;
    }
    
    private static function gather_system_info() {
        global $wpdb;
        
        // Basic WordPress info
        $wp_info = [
            'wordpress_version' => get_bloginfo('version'),
            'site_url' => home_url(),
            'admin_url' => admin_url(),
            'wp_content_dir' => WP_CONTENT_DIR,
            'wp_plugin_dir' => WP_PLUGIN_DIR,
            'wp_debug' => defined('WP_DEBUG') ? WP_DEBUG : false,
            'wp_debug_log' => defined('WP_DEBUG_LOG') ? WP_DEBUG_LOG : false,
            'multisite' => is_multisite(),
            'language' => get_locale(),
            'timezone' => get_option('timezone_string') ?: 'UTC',
            'date_format' => get_option('date_format'),
            'time_format' => get_option('time_format')
        ];
        
        // Server info
        $server_info = [
            'php_version' => PHP_VERSION,
            'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown',
            'mysql_version' => $wpdb->db_version(),
            'max_execution_time' => ini_get('max_execution_time'),
            'memory_limit' => ini_get('memory_limit'),
            'upload_max_filesize' => ini_get('upload_max_filesize'),
            'post_max_size' => ini_get('post_max_size'),
            'max_input_vars' => ini_get('max_input_vars'),
            'curl_version' => function_exists('curl_version') ? curl_version()['version'] : 'Not available',
            'openssl_version' => defined('OPENSSL_VERSION_TEXT') ? OPENSSL_VERSION_TEXT : 'Not available'
        ];
        
        // Hexagon plugin info
        $plugin_info = [
            'version' => HEXAGON_VERSION,
            'path' => HEXAGON_PATH,
            'url' => HEXAGON_URL,
            'safe_mode' => get_option('hexagon_safe_mode', false),
            'plugin_version_db' => get_option('hexagon_plugin_version'),
            'ai_provider' => get_option('hexagon_ai_provider'),
            'last_health_check' => get_option('hexagon_last_health_check'),
            'health_status' => get_option('hexagon_health_status')
        ];
        
        // Plugin settings
        $settings = [
            'ai_settings' => [
                'chatgpt_configured' => !empty(get_option('hexagon_ai_chatgpt_api_key')),
                'claude_configured' => !empty(get_option('hexagon_ai_claude_api_key')),
                'perplexity_configured' => !empty(get_option('hexagon_ai_perplexity_api_key'))
            ],
            'email_settings' => [
                'smtp_enabled' => get_option('hexagon_email_use_smtp', false),
                'smtp_host' => get_option('hexagon_email_smtp_host'),
                'smtp_port' => get_option('hexagon_email_smtp_port'),
                'daily_digest' => get_option('hexagon_email_daily_digest', false),
                'error_alerts' => get_option('hexagon_email_error_alerts', true)
            ],
            'social_settings' => [
                'auto_post' => get_option('hexagon_social_auto_post', false),
                'facebook_configured' => !empty(get_option('hexagon_social_facebook_token')),
                'instagram_configured' => !empty(get_option('hexagon_social_instagram_token')),
                'twitter_configured' => !empty(get_option('hexagon_social_twitter_api_key')),
                'linkedin_configured' => !empty(get_option('hexagon_social_linkedin_token'))
            ]
        ];
        
        // Active plugins
        $active_plugins = [];
        $all_plugins = get_plugins();
        foreach ($all_plugins as $plugin_path => $plugin_data) {
            if (is_plugin_active($plugin_path)) {
                $active_plugins[] = [
                    'name' => $plugin_data['Name'],
                    'version' => $plugin_data['Version'],
                    'path' => $plugin_path
                ];
            }
        }
        
        // Theme info
        $theme = wp_get_theme();
        $theme_info = [
            'name' => $theme->get('Name'),
            'version' => $theme->get('Version'),
            'template' => $theme->get_template(),
            'stylesheet' => $theme->get_stylesheet()
        ];
        
        // Database info
        $db_info = [
            'database_size' => self::get_database_size(),
            'hex_logs_count' => $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}hex_logs"),
            'hex_logs_size' => self::get_table_size($wpdb->prefix . 'hex_logs')
        ];
        
        // Memory usage
        $memory_info = [
            'current_usage' => memory_get_usage(true),
            'peak_usage' => memory_get_peak_usage(true),
            'limit' => wp_convert_hr_to_bytes(ini_get('memory_limit')),
            'usage_percentage' => round((memory_get_usage(true) / wp_convert_hr_to_bytes(ini_get('memory_limit'))) * 100, 2)
        ];
        
        // Error counts
        $error_counts = [];
        $error_options = $wpdb->get_results(
            "SELECT option_name, option_value FROM {$wpdb->options} WHERE option_name LIKE 'hexagon_error_count_%'"
        );
        foreach ($error_options as $option) {
            $key = str_replace('hexagon_error_count_', '', $option->option_name);
            $error_counts[$key] = intval($option->option_value);
        }
        
        // Recent critical errors
        $critical_errors = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}hex_logs WHERE level = 'error' AND created_at >= %s ORDER BY created_at DESC LIMIT 20",
            date('Y-m-d H:i:s', strtotime('-24 hours'))
        ));
        
        return [
            'export_info' => [
                'generated_at' => current_time('mysql'),
                'generated_by' => 'Hexagon Automation Debug Exporter',
                'plugin_version' => HEXAGON_VERSION,
                'purpose' => 'System diagnostic information for support'
            ],
            'wordpress' => $wp_info,
            'server' => $server_info,
            'plugin' => $plugin_info,
            'settings' => $settings,
            'active_plugins' => $active_plugins,
            'theme' => $theme_info,
            'database' => $db_info,
            'memory' => $memory_info,
            'error_counts' => $error_counts,
            'recent_critical_errors' => $critical_errors,
            'scheduled_tasks' => [
                'health_check' => wp_next_scheduled('hexagon_health_check'),
                'auto_repair' => wp_next_scheduled('hexagon_auto_repair'),
                'ai_cleanup' => wp_next_scheduled('hexagon_ai_cleanup')
            ]
        ];
    }
    
    private static function get_database_size() {
        global $wpdb;
        
        $size = $wpdb->get_var($wpdb->prepare("
            SELECT ROUND(SUM(data_length + index_length) / 1024 / 1024, 2) AS 'size_mb' 
            FROM information_schema.tables 
            WHERE table_schema = %s
        ", $wpdb->dbname));
        
        return floatval($size);
    }
    
    private static function get_table_size($table_name) {
        global $wpdb;
        
        $size = $wpdb->get_var($wpdb->prepare("
            SELECT ROUND((data_length + index_length) / 1024 / 1024, 2) AS 'size_mb' 
            FROM information_schema.tables 
            WHERE table_schema = %s AND table_name = %s
        ", $wpdb->dbname, $table_name));
        
        return floatval($size);
    }
    
    public static function get_download_links() {
        $nonce = wp_create_nonce('hexagon_download');
        
        return [
            'logs' => admin_url('admin-post.php?action=hexagon_download_logs&_wpnonce=' . $nonce),
            'system_info' => admin_url('admin-post.php?action=hexagon_download_system&_wpnonce=' . $nonce)
        ];
    }
    
    public static function add_debug_buttons() {
        if (!current_user_can('manage_options')) {
            return;
        }
        
        $links = self::get_download_links();
        ?>
        <div class="hexagon-debug-export" style="margin: 20px 0; padding: 15px; background: #f9f9f9; border-left: 4px solid #007cba;">
            <h3>🔧 Debug Export</h3>
            <p>Pobierz pliki diagnostyczne do załączenia w zgłoszeniu o pomoc:</p>
            <p>
                <a href="<?php echo esc_url($links['logs']); ?>" class="button button-primary" download>
                    📄 Pobierz Logi (JSON)
                </a>
                <a href="<?php echo esc_url($links['system_info']); ?>" class="button button-secondary" download style="margin-left: 10px;">
                    ⚙️ Pobierz Info Systemowe (JSON)
                </a>
            </p>
            <p><small>
                <strong>Logi:</strong> Zawierają ostatnie 7 dni logów aktywności plugina<br>
                <strong>Info Systemowe:</strong> Konfiguracja WordPress, PHP, bazy danych i ustawienia plugina
            </small></p>
        </div>
        <?php
    }
}