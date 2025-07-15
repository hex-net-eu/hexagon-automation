<?php
if (!defined('ABSPATH')) exit;

class Hexagon_System_Diagnostic {
    
    private static $tests = [];
    private static $test_results = [];
    
    public static function init() {
        add_action('wp_ajax_hexagon_run_full_diagnostic', [__CLASS__, 'ajax_run_full_diagnostic']);
        add_action('wp_ajax_hexagon_run_specific_test', [__CLASS__, 'ajax_run_specific_test']);
        add_action('wp_ajax_hexagon_get_system_health', [__CLASS__, 'ajax_get_system_health']);
        
        self::register_tests();
    }
    
    private static function register_tests() {
        self::$tests = [
            'environment' => [
                'name' => 'Environment Check',
                'description' => 'Check PHP version, WordPress version, and server requirements',
                'critical' => true
            ],
            'database' => [
                'name' => 'Database Check',
                'description' => 'Verify database connectivity and table structure',
                'critical' => true
            ],
            'file_permissions' => [
                'name' => 'File Permissions',
                'description' => 'Check file and directory permissions',
                'critical' => true
            ],
            'modules' => [
                'name' => 'Module Status',
                'description' => 'Test all enabled modules',
                'critical' => false
            ],
            'api_endpoints' => [
                'name' => 'API Endpoints',
                'description' => 'Test REST API endpoints functionality',
                'critical' => false
            ],
            'ai_providers' => [
                'name' => 'AI Providers',
                'description' => 'Test AI provider connections',
                'critical' => false
            ],
            'social_platforms' => [
                'name' => 'Social Platforms',
                'description' => 'Test social media platform connections',
                'critical' => false
            ],
            'email_service' => [
                'name' => 'Email Service',
                'description' => 'Test email configuration and sending',
                'critical' => false
            ],
            'performance' => [
                'name' => 'Performance Check',
                'description' => 'Check memory usage, execution time, and optimization',
                'critical' => false
            ],
            'security' => [
                'name' => 'Security Check',
                'description' => 'Verify security settings and configurations',
                'critical' => false
            ]
        ];
    }
    
    public static function run_all_tests($detailed = false) {
        $start_time = microtime(true);
        $results = [];
        
        foreach (self::$tests as $test_key => $test_info) {
            $results[$test_key] = self::run_single_test($test_key, $detailed);
        }
        
        $execution_time = microtime(true) - $start_time;
        
        $summary = self::generate_summary($results);
        
        return [
            'summary' => $summary,
            'results' => $results,
            'execution_time' => round($execution_time, 2),
            'timestamp' => current_time('mysql')
        ];
    }
    
    public static function run_single_test($test_key, $detailed = false) {
        if (!isset(self::$tests[$test_key])) {
            return [
                'status' => 'error',
                'message' => 'Test not found',
                'details' => []
            ];
        }
        
        $start_time = microtime(true);
        
        try {
            switch ($test_key) {
                case 'environment':
                    $result = self::test_environment($detailed);
                    break;
                case 'database':
                    $result = self::test_database($detailed);
                    break;
                case 'file_permissions':
                    $result = self::test_file_permissions($detailed);
                    break;
                case 'modules':
                    $result = self::test_modules($detailed);
                    break;
                case 'api_endpoints':
                    $result = self::test_api_endpoints($detailed);
                    break;
                case 'ai_providers':
                    $result = self::test_ai_providers($detailed);
                    break;
                case 'social_platforms':
                    $result = self::test_social_platforms($detailed);
                    break;
                case 'email_service':
                    $result = self::test_email_service($detailed);
                    break;
                case 'performance':
                    $result = self::test_performance($detailed);
                    break;
                case 'security':
                    $result = self::test_security($detailed);
                    break;
                default:
                    $result = [
                        'status' => 'error',
                        'message' => 'Unknown test',
                        'details' => []
                    ];
            }
        } catch (Exception $e) {
            $result = [
                'status' => 'error',
                'message' => 'Test failed: ' . $e->getMessage(),
                'details' => []
            ];
        }
        
        $result['execution_time'] = microtime(true) - $start_time;
        $result['test_info'] = self::$tests[$test_key];
        
        return $result;
    }
    
    private static function test_environment($detailed) {
        $details = [];
        $issues = [];
        
        // PHP Version
        $php_version = PHP_VERSION;
        $min_php = '7.4';
        $details['php_version'] = $php_version;
        if (version_compare($php_version, $min_php, '<')) {
            $issues[] = "PHP version {$php_version} is below minimum required {$min_php}";
        }
        
        // WordPress Version
        $wp_version = get_bloginfo('version');
        $min_wp = '5.0';
        $details['wp_version'] = $wp_version;
        if (version_compare($wp_version, $min_wp, '<')) {
            $issues[] = "WordPress version {$wp_version} is below minimum required {$min_wp}";
        }
        
        // Memory Limit
        $memory_limit = ini_get('memory_limit');
        $memory_bytes = wp_convert_hr_to_bytes($memory_limit);
        $min_memory = 128 * 1024 * 1024; // 128MB
        $details['memory_limit'] = $memory_limit;
        if ($memory_bytes < $min_memory) {
            $issues[] = "Memory limit {$memory_limit} is below recommended 128M";
        }
        
        // Required PHP Extensions
        $required_extensions = ['curl', 'json', 'mbstring', 'openssl'];
        $missing_extensions = [];
        foreach ($required_extensions as $ext) {
            $details["extension_{$ext}"] = extension_loaded($ext);
            if (!extension_loaded($ext)) {
                $missing_extensions[] = $ext;
            }
        }
        if (!empty($missing_extensions)) {
            $issues[] = "Missing PHP extensions: " . implode(', ', $missing_extensions);
        }
        
        // Max Execution Time
        $max_execution = ini_get('max_execution_time');
        $details['max_execution_time'] = $max_execution;
        if ($max_execution > 0 && $max_execution < 30) {
            $issues[] = "Max execution time {$max_execution}s may be too low for AI operations";
        }
        
        return [
            'status' => empty($issues) ? 'success' : 'warning',
            'message' => empty($issues) ? 'Environment check passed' : 'Environment issues detected',
            'details' => $detailed ? $details : [],
            'issues' => $issues
        ];
    }
    
    private static function test_database($detailed) {
        global $wpdb;
        $details = [];
        $issues = [];
        
        // Database Connection
        $details['connection'] = $wpdb->check_connection();
        if (!$wpdb->check_connection()) {
            $issues[] = 'Database connection failed';
            return [
                'status' => 'error',
                'message' => 'Database connection failed',
                'details' => $detailed ? $details : [],
                'issues' => $issues
            ];
        }
        
        // Check Required Tables
        $required_tables = [
            $wpdb->prefix . 'hex_logs',
            $wpdb->prefix . 'hex_generated_images'
        ];
        
        foreach ($required_tables as $table) {
            $exists = $wpdb->get_var("SHOW TABLES LIKE '$table'") === $table;
            $details["table_" . str_replace($wpdb->prefix, '', $table)] = $exists;
            if (!$exists) {
                $issues[] = "Required table {$table} is missing";
            }
        }
        
        // Database Version
        $db_version = $wpdb->db_version();
        $details['mysql_version'] = $db_version;
        if (version_compare($db_version, '5.6', '<')) {
            $issues[] = "MySQL version {$db_version} is below recommended 5.6";
        }
        
        // Character Set
        $charset = $wpdb->get_charset_collate();
        $details['charset'] = $charset;
        
        return [
            'status' => empty($issues) ? 'success' : 'error',
            'message' => empty($issues) ? 'Database check passed' : 'Database issues detected',
            'details' => $detailed ? $details : [],
            'issues' => $issues
        ];
    }
    
    private static function test_file_permissions($detailed) {
        $details = [];
        $issues = [];
        
        // Check critical directories
        $directories_to_check = [
            HEXAGON_PATH => 'Plugin directory',
            HEXAGON_PATH . 'includes/' => 'Includes directory',
            WP_CONTENT_DIR . '/uploads/' => 'Uploads directory'
        ];
        
        foreach ($directories_to_check as $path => $name) {
            $readable = is_readable($path);
            $writable = is_writable($path);
            
            $details[sanitize_key($name)] = [
                'readable' => $readable,
                'writable' => $writable,
                'permissions' => substr(sprintf('%o', fileperms($path)), -4)
            ];
            
            if (!$readable) {
                $issues[] = "{$name} is not readable";
            }
            if (!$writable && strpos($path, 'uploads') !== false) {
                $issues[] = "{$name} is not writable";
            }
        }
        
        return [
            'status' => empty($issues) ? 'success' : 'error',
            'message' => empty($issues) ? 'File permissions OK' : 'Permission issues detected',
            'details' => $detailed ? $details : [],
            'issues' => $issues
        ];
    }
    
    private static function test_modules($detailed) {
        if (!class_exists('Hexagon_Module_Manager')) {
            return [
                'status' => 'error',
                'message' => 'Module Manager not available',
                'details' => [],
                'issues' => ['Module Manager class not found']
            ];
        }
        
        $modules = Hexagon_Module_Manager::get_modules();
        $details = [];
        $issues = [];
        
        foreach ($modules as $key => $module) {
            if ($module['status'] === 'enabled') {
                $test_result = Hexagon_Module_Manager::test_module($key);
                $details[$key] = $test_result;
                
                if ($test_result['status'] === 'error') {
                    $issues[] = "Module {$key}: {$test_result['message']}";
                }
            }
        }
        
        return [
            'status' => empty($issues) ? 'success' : 'warning',
            'message' => empty($issues) ? 'All modules OK' : 'Module issues detected',
            'details' => $detailed ? $details : [],
            'issues' => $issues
        ];
    }
    
    private static function test_api_endpoints($detailed) {
        $details = [];
        $issues = [];
        
        $endpoints_to_test = [
            '/status' => 'System status',
            '/dashboard' => 'Dashboard data',
            '/ai/stats' => 'AI statistics',
            '/social/stats' => 'Social statistics'
        ];
        
        foreach ($endpoints_to_test as $endpoint => $name) {
            $url = rest_url('hexagon/v1' . $endpoint);
            $response = wp_remote_get($url, ['timeout' => 10]);
            
            $status = !is_wp_error($response) && wp_remote_retrieve_response_code($response) === 200;
            $details[sanitize_key($name)] = [
                'status' => $status,
                'url' => $endpoint,
                'response_code' => is_wp_error($response) ? 'error' : wp_remote_retrieve_response_code($response)
            ];
            
            if (!$status) {
                $issues[] = "API endpoint {$endpoint} is not responding correctly";
            }
        }
        
        return [
            'status' => empty($issues) ? 'success' : 'warning',
            'message' => empty($issues) ? 'API endpoints OK' : 'API issues detected',
            'details' => $detailed ? $details : [],
            'issues' => $issues
        ];
    }
    
    private static function test_ai_providers($detailed) {
        $details = [];
        $issues = [];
        
        $providers = ['chatgpt', 'claude', 'perplexity'];
        
        foreach ($providers as $provider) {
            $api_key = hexagon_get_option("hexagon_ai_{$provider}_api_key");
            $configured = !empty($api_key);
            
            $details[$provider] = [
                'configured' => $configured,
                'api_key_length' => $configured ? strlen($api_key) : 0
            ];
            
            if (!$configured) {
                $issues[] = "{$provider} API key not configured";
            }
        }
        
        return [
            'status' => empty($issues) ? 'success' : 'warning',
            'message' => empty($issues) ? 'AI providers configured' : 'Some AI providers not configured',
            'details' => $detailed ? $details : [],
            'issues' => $issues
        ];
    }
    
    private static function test_social_platforms($detailed) {
        $details = [];
        $issues = [];
        
        $platforms = ['facebook', 'instagram', 'twitter', 'linkedin'];
        
        foreach ($platforms as $platform) {
            $token = hexagon_get_option("hexagon_social_{$platform}_token");
            $configured = !empty($token);
            
            $details[$platform] = [
                'configured' => $configured,
                'token_length' => $configured ? strlen($token) : 0
            ];
            
            if (!$configured) {
                $issues[] = "{$platform} not connected";
            }
        }
        
        return [
            'status' => count($issues) === count($platforms) ? 'warning' : 'success',
            'message' => empty($issues) ? 'Social platforms connected' : 'Some platforms not connected',
            'details' => $detailed ? $details : [],
            'issues' => $issues
        ];
    }
    
    private static function test_email_service($detailed) {
        $details = [];
        $issues = [];
        
        $use_smtp = hexagon_get_option('hexagon_email_use_smtp', false);
        $smtp_host = hexagon_get_option('hexagon_email_smtp_host', '');
        
        $details['smtp_enabled'] = $use_smtp;
        $details['smtp_configured'] = !empty($smtp_host);
        
        if ($use_smtp && empty($smtp_host)) {
            $issues[] = 'SMTP enabled but host not configured';
        }
        
        // Test basic email functionality
        $test_result = wp_mail(get_option('admin_email'), 'Hexagon Test', 'Test email', [], []);
        $details['wp_mail_test'] = $test_result;
        
        if (!$test_result) {
            $issues[] = 'WordPress mail function failed';
        }
        
        return [
            'status' => empty($issues) ? 'success' : 'warning',
            'message' => empty($issues) ? 'Email service OK' : 'Email issues detected',
            'details' => $detailed ? $details : [],
            'issues' => $issues
        ];
    }
    
    private static function test_performance($detailed) {
        $details = [];
        $issues = [];
        
        // Memory usage
        $memory_used = memory_get_usage(true);
        $memory_peak = memory_get_peak_usage(true);
        $memory_limit = wp_convert_hr_to_bytes(ini_get('memory_limit'));
        
        $details['memory_usage'] = [
            'current' => $memory_used,
            'peak' => $memory_peak,
            'limit' => $memory_limit,
            'usage_percent' => round(($memory_used / $memory_limit) * 100, 2)
        ];
        
        if ($memory_used / $memory_limit > 0.8) {
            $issues[] = 'High memory usage detected';
        }
        
        // Database performance
        $start_time = microtime(true);
        global $wpdb;
        $wpdb->get_results("SELECT 1");
        $db_query_time = microtime(true) - $start_time;
        
        $details['database_performance'] = [
            'query_time' => $db_query_time
        ];
        
        if ($db_query_time > 0.1) {
            $issues[] = 'Slow database response detected';
        }
        
        return [
            'status' => empty($issues) ? 'success' : 'warning',
            'message' => empty($issues) ? 'Performance OK' : 'Performance issues detected',
            'details' => $detailed ? $details : [],
            'issues' => $issues
        ];
    }
    
    private static function test_security($detailed) {
        $details = [];
        $issues = [];
        
        // Check if debug mode is enabled in production
        $debug_enabled = defined('WP_DEBUG') && WP_DEBUG;
        $details['wp_debug'] = $debug_enabled;
        
        if ($debug_enabled && !WP_DEBUG_DISPLAY) {
            // Debug is on but not displaying - this is OK
        } elseif ($debug_enabled) {
            $issues[] = 'WP_DEBUG is enabled and displaying errors';
        }
        
        // Check API key security
        $api_key = hexagon_get_option('hexagon_api_key');
        $details['api_key_configured'] = !empty($api_key);
        $details['api_key_length'] = !empty($api_key) ? strlen($api_key) : 0;
        
        if (empty($api_key)) {
            $issues[] = 'API key not configured';
        } elseif (strlen($api_key) < 20) {
            $issues[] = 'API key appears to be too short';
        }
        
        // Check file permissions for security
        $wp_config_writable = is_writable(ABSPATH . 'wp-config.php');
        $details['wp_config_writable'] = $wp_config_writable;
        
        if ($wp_config_writable) {
            $issues[] = 'wp-config.php is writable (security risk)';
        }
        
        return [
            'status' => empty($issues) ? 'success' : 'warning',
            'message' => empty($issues) ? 'Security check passed' : 'Security issues detected',
            'details' => $detailed ? $details : [],
            'issues' => $issues
        ];
    }
    
    private static function generate_summary($results) {
        $total_tests = count($results);
        $passed = 0;
        $warnings = 0;
        $errors = 0;
        $critical_issues = 0;
        
        foreach ($results as $test_key => $result) {
            switch ($result['status']) {
                case 'success':
                    $passed++;
                    break;
                case 'warning':
                    $warnings++;
                    if (self::$tests[$test_key]['critical']) {
                        $critical_issues++;
                    }
                    break;
                case 'error':
                    $errors++;
                    if (self::$tests[$test_key]['critical']) {
                        $critical_issues++;
                    }
                    break;
            }
        }
        
        $overall_status = 'success';
        if ($errors > 0 || $critical_issues > 0) {
            $overall_status = 'error';
        } elseif ($warnings > 0) {
            $overall_status = 'warning';
        }
        
        return [
            'overall_status' => $overall_status,
            'total_tests' => $total_tests,
            'passed' => $passed,
            'warnings' => $warnings,
            'errors' => $errors,
            'critical_issues' => $critical_issues,
            'health_score' => round(($passed / $total_tests) * 100, 1)
        ];
    }
    
    public static function get_system_health_score() {
        // Run a quick health check without detailed results
        $results = self::run_all_tests(false);
        return $results['summary']['health_score'];
    }
    
    // AJAX Handlers
    public static function ajax_run_full_diagnostic() {
        check_ajax_referer('hexagon_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die('Insufficient permissions');
        }
        
        $detailed = isset($_POST['detailed']) && $_POST['detailed'] === 'true';
        $results = self::run_all_tests($detailed);
        
        wp_send_json_success($results);
    }
    
    public static function ajax_run_specific_test() {
        check_ajax_referer('hexagon_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die('Insufficient permissions');
        }
        
        $test_key = sanitize_text_field($_POST['test']);
        $detailed = isset($_POST['detailed']) && $_POST['detailed'] === 'true';
        
        $result = self::run_single_test($test_key, $detailed);
        
        wp_send_json_success($result);
    }
    
    public static function ajax_get_system_health() {
        check_ajax_referer('hexagon_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die('Insufficient permissions');
        }
        
        $health_score = self::get_system_health_score();
        
        wp_send_json_success(['health_score' => $health_score]);
    }
}