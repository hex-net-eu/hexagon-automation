<?php
if (!defined('ABSPATH')) exit;

class Hexagon_System_Tester {
    
    private static $test_results = [];
    
    public static function init() {
        add_action('wp_ajax_hexagon_run_tests', [__CLASS__, 'run_system_tests']);
        add_action('wp_ajax_hexagon_test_module', [__CLASS__, 'test_specific_module']);
    }
    
    public static function run_system_tests() {
        check_ajax_referer('hexagon_test_nonce', 'nonce');
        
        hexagon_log('System Test', 'Starting comprehensive system tests', 'info');
        
        $tests = [
            'core' => self::test_core_functionality(),
            'database' => self::test_database(),
            'ai' => self::test_ai_modules(),
            'email' => self::test_email_system(),
            'social' => self::test_social_integration(),
            'api' => self::test_rest_api(),
            'auto_repair' => self::test_auto_repair(),
            'security' => self::test_security()
        ];
        
        $overall_success = true;
        $total_tests = 0;
        $passed_tests = 0;
        
        foreach ($tests as $module => $results) {
            $total_tests += count($results);
            foreach ($results as $test) {
                if ($test['status'] === 'pass') {
                    $passed_tests++;
                } else {
                    $overall_success = false;
                }
            }
        }
        
        $summary = [
            'overall_status' => $overall_success ? 'PASS' : 'FAIL',
            'total_tests' => $total_tests,
            'passed_tests' => $passed_tests,
            'failed_tests' => $total_tests - $passed_tests,
            'success_rate' => round(($passed_tests / $total_tests) * 100, 2),
            'timestamp' => current_time('mysql'),
            'version' => HEXAGON_VERSION
        ];
        
        $result = [
            'summary' => $summary,
            'tests' => $tests
        ];
        
        // Save test results
        update_option('hexagon_last_test_results', $result);
        
        hexagon_log('System Test Complete', "Tests: $passed_tests/$total_tests passed ({$summary['success_rate']}%)", 
                    $overall_success ? 'success' : 'warning');
        
        wp_send_json_success($result);
    }
    
    public static function test_core_functionality() {
        $tests = [];
        
        // Test constants
        $tests[] = [
            'name' => 'Plugin Constants',
            'status' => (defined('HEXAGON_PATH') && defined('HEXAGON_URL') && defined('HEXAGON_VERSION')) ? 'pass' : 'fail',
            'message' => defined('HEXAGON_PATH') ? 'All constants defined' : 'Missing plugin constants'
        ];
        
        // Test main class
        $tests[] = [
            'name' => 'Main Class Loading',
            'status' => class_exists('Hexagon_Automation') ? 'pass' : 'fail',
            'message' => class_exists('Hexagon_Automation') ? 'Main class loaded' : 'Main class not found'
        ];
        
        // Test module loading
        $required_classes = [
            'Hexagon_Hexagon_Ai_Manager',
            'Hexagon_Email_Integration',
            'Hexagon_Social_Integration',
            'Hexagon_Rest_Api',
            'Hexagon_Auto_Repair'
        ];
        
        $loaded_classes = 0;
        foreach ($required_classes as $class) {
            if (class_exists($class)) {
                $loaded_classes++;
            }
        }
        
        $tests[] = [
            'name' => 'Module Classes',
            'status' => ($loaded_classes === count($required_classes)) ? 'pass' : 'fail',
            'message' => "$loaded_classes/" . count($required_classes) . ' modules loaded'
        ];
        
        // Test WordPress integration
        $tests[] = [
            'name' => 'WordPress Integration',
            'status' => (function_exists('wp_mail') && function_exists('wp_remote_post')) ? 'pass' : 'fail',
            'message' => 'WordPress functions available'
        ];
        
        return $tests;
    }
    
    public static function test_database() {
        $tests = [];
        global $wpdb;
        
        // Test database connection
        $tests[] = [
            'name' => 'Database Connection',
            'status' => $wpdb->check_connection() ? 'pass' : 'fail',
            'message' => $wpdb->check_connection() ? 'Connection OK' : 'Connection failed'
        ];
        
        // Test required tables
        $required_tables = [
            $wpdb->prefix . 'hex_logs'
        ];
        
        $tables_exist = true;
        $missing_tables = [];
        
        foreach ($required_tables as $table) {
            $exists = $wpdb->get_var("SHOW TABLES LIKE '$table'");
            if (!$exists) {
                $tables_exist = false;
                $missing_tables[] = $table;
            }
        }
        
        $tests[] = [
            'name' => 'Required Tables',
            'status' => $tables_exist ? 'pass' : 'fail',
            'message' => $tables_exist ? 'All tables exist' : 'Missing: ' . implode(', ', $missing_tables)
        ];
        
        // Test logging functionality
        $test_log_id = hexagon_log('System Test', 'Database test log entry', 'info');
        $tests[] = [
            'name' => 'Logging Functionality',
            'status' => $test_log_id ? 'pass' : 'fail',
            'message' => $test_log_id ? 'Logging works' : 'Logging failed'
        ];
        
        // Test database performance
        $start_time = microtime(true);
        $wpdb->get_results("SELECT * FROM {$wpdb->prefix}hex_logs LIMIT 10");
        $query_time = microtime(true) - $start_time;
        
        $tests[] = [
            'name' => 'Database Performance',
            'status' => ($query_time < 1.0) ? 'pass' : 'warning',
            'message' => 'Query time: ' . round($query_time, 4) . 's'
        ];
        
        return $tests;
    }
    
    public static function test_ai_modules() {
        $tests = [];
        
        // Test AI Manager class
        $tests[] = [
            'name' => 'AI Manager Class',
            'status' => class_exists('Hexagon_Hexagon_Ai_Manager') ? 'pass' : 'fail',
            'message' => class_exists('Hexagon_Hexagon_Ai_Manager') ? 'Class loaded' : 'Class not found'
        ];
        
        // Test AI configuration
        $ai_providers = ['chatgpt', 'claude', 'perplexity'];
        $configured_providers = 0;
        
        foreach ($ai_providers as $provider) {
            $api_key = hexagon_get_option("hexagon_ai_{$provider}_api_key");
            if (!empty($api_key)) {
                $configured_providers++;
            }
        }
        
        $tests[] = [
            'name' => 'AI Provider Configuration',
            'status' => ($configured_providers > 0) ? 'pass' : 'warning',
            'message' => "$configured_providers/" . count($ai_providers) . ' providers configured'
        ];
        
        // Test system prompts
        if (class_exists('Hexagon_Hexagon_Ai_Manager')) {
            $reflection = new ReflectionClass('Hexagon_Hexagon_Ai_Manager');
            $method = $reflection->getMethod('get_system_prompt');
            $method->setAccessible(true);
            
            try {
                $prompt = $method->invoke(null, 'article', 'pl');
                $tests[] = [
                    'name' => 'System Prompts',
                    'status' => !empty($prompt) ? 'pass' : 'fail',
                    'message' => !empty($prompt) ? 'Prompts generated' : 'Prompt generation failed'
                ];
            } catch (Exception $e) {
                $tests[] = [
                    'name' => 'System Prompts',
                    'status' => 'fail',
                    'message' => 'Error: ' . $e->getMessage()
                ];
            }
        }
        
        return $tests;
    }
    
    public static function test_email_system() {
        $tests = [];
        
        // Test Email Integration class
        $tests[] = [
            'name' => 'Email Integration Class',
            'status' => class_exists('Hexagon_Email_Integration') ? 'pass' : 'fail',
            'message' => class_exists('Hexagon_Email_Integration') ? 'Class loaded' : 'Class not found'
        ];
        
        // Test SMTP configuration
        $smtp_enabled = hexagon_get_option('hexagon_email_use_smtp', false);
        if ($smtp_enabled) {
            $host = hexagon_get_option('hexagon_email_smtp_host');
            $port = hexagon_get_option('hexagon_email_smtp_port', 587);
            
            $connection = @fsockopen($host, $port, $errno, $errstr, 5);
            $tests[] = [
                'name' => 'SMTP Connection',
                'status' => $connection ? 'pass' : 'warning',
                'message' => $connection ? 'SMTP reachable' : "Cannot reach $host:$port"
            ];
            
            if ($connection) {
                fclose($connection);
            }
        } else {
            $tests[] = [
                'name' => 'SMTP Configuration',
                'status' => 'warning',
                'message' => 'SMTP not configured, using wp_mail'
            ];
        }
        
        // Test wp_mail function
        $tests[] = [
            'name' => 'WordPress Mail Function',
            'status' => function_exists('wp_mail') ? 'pass' : 'fail',
            'message' => function_exists('wp_mail') ? 'wp_mail available' : 'wp_mail not found'
        ];
        
        return $tests;
    }
    
    public static function test_social_integration() {
        $tests = [];
        
        // Test Social Integration class
        $tests[] = [
            'name' => 'Social Integration Class',
            'status' => class_exists('Hexagon_Social_Integration') ? 'pass' : 'fail',
            'message' => class_exists('Hexagon_Social_Integration') ? 'Class loaded' : 'Class not found'
        ];
        
        // Test social platform configuration
        $platforms = [
            'facebook' => 'hexagon_social_facebook_token',
            'instagram' => 'hexagon_social_instagram_token',
            'twitter' => 'hexagon_social_twitter_api_key',
            'linkedin' => 'hexagon_social_linkedin_token'
        ];
        
        $configured_platforms = 0;
        foreach ($platforms as $platform => $option_key) {
            $token = hexagon_get_option($option_key);
            if (!empty($token)) {
                $configured_platforms++;
            }
        }
        
        $tests[] = [
            'name' => 'Social Platform Configuration',
            'status' => ($configured_platforms > 0) ? 'pass' : 'warning',
            'message' => "$configured_platforms/" . count($platforms) . ' platforms configured'
        ];
        
        // Test API endpoints accessibility
        $endpoints_to_test = [
            'Facebook' => 'https://graph.facebook.com',
            'Twitter' => 'https://api.twitter.com',
            'LinkedIn' => 'https://api.linkedin.com'
        ];
        
        $reachable_endpoints = 0;
        foreach ($endpoints_to_test as $name => $url) {
            $response = wp_remote_get($url, ['timeout' => 5]);
            if (!is_wp_error($response)) {
                $reachable_endpoints++;
            }
        }
        
        $tests[] = [
            'name' => 'Social API Endpoints',
            'status' => ($reachable_endpoints === count($endpoints_to_test)) ? 'pass' : 'warning',
            'message' => "$reachable_endpoints/" . count($endpoints_to_test) . ' endpoints reachable'
        ];
        
        return $tests;
    }
    
    public static function test_rest_api() {
        $tests = [];
        
        // Test REST API class
        $tests[] = [
            'name' => 'REST API Class',
            'status' => class_exists('Hexagon_Rest_Api') ? 'pass' : 'fail',
            'message' => class_exists('Hexagon_Rest_Api') ? 'Class loaded' : 'Class not found'
        ];
        
        // Test REST API registration
        $rest_server = rest_get_server();
        $routes = $rest_server->get_routes();
        $hexagon_routes = 0;
        
        foreach ($routes as $route => $handlers) {
            if (strpos($route, '/hexagon/v1') === 0) {
                $hexagon_routes++;
            }
        }
        
        $tests[] = [
            'name' => 'REST API Routes',
            'status' => ($hexagon_routes > 0) ? 'pass' : 'fail',
            'message' => "$hexagon_routes Hexagon routes registered"
        ];
        
        // Test API authentication
        $api_key = hexagon_get_option('hexagon_api_key');
        $tests[] = [
            'name' => 'API Authentication',
            'status' => !empty($api_key) ? 'pass' : 'warning',
            'message' => !empty($api_key) ? 'API key configured' : 'No API key set'
        ];
        
        return $tests;
    }
    
    public static function test_auto_repair() {
        $tests = [];
        
        // Test Auto Repair class
        $tests[] = [
            'name' => 'Auto Repair Class',
            'status' => class_exists('Hexagon_Auto_Repair') ? 'pass' : 'fail',
            'message' => class_exists('Hexagon_Auto_Repair') ? 'Class loaded' : 'Class not found'
        ];
        
        // Test scheduled tasks
        $health_check_scheduled = wp_next_scheduled('hexagon_health_check');
        $auto_repair_scheduled = wp_next_scheduled('hexagon_auto_repair');
        
        $tests[] = [
            'name' => 'Scheduled Health Checks',
            'status' => $health_check_scheduled ? 'pass' : 'warning',
            'message' => $health_check_scheduled ? 'Health checks scheduled' : 'No health checks scheduled'
        ];
        
        $tests[] = [
            'name' => 'Scheduled Auto Repair',
            'status' => $auto_repair_scheduled ? 'pass' : 'warning',
            'message' => $auto_repair_scheduled ? 'Auto repair scheduled' : 'No auto repair scheduled'
        ];
        
        // Test health status
        $health_status = get_option('hexagon_health_status', 'unknown');
        $tests[] = [
            'name' => 'System Health Status',
            'status' => ($health_status === 'healthy') ? 'pass' : 'warning',
            'message' => "Current status: $health_status"
        ];
        
        return $tests;
    }
    
    public static function test_security() {
        $tests = [];
        
        // Test nonce functionality
        $nonce = wp_create_nonce('hexagon_test');
        $tests[] = [
            'name' => 'Nonce Generation',
            'status' => !empty($nonce) ? 'pass' : 'fail',
            'message' => !empty($nonce) ? 'Nonces working' : 'Nonce generation failed'
        ];
        
        // Test capability checks
        $tests[] = [
            'name' => 'Capability Checks',
            'status' => function_exists('current_user_can') ? 'pass' : 'fail',
            'message' => 'WordPress capability system available'
        ];
        
        // Test data sanitization
        $test_data = '<script>alert("xss")</script>';
        $sanitized = sanitize_text_field($test_data);
        $tests[] = [
            'name' => 'Data Sanitization',
            'status' => ($sanitized !== $test_data) ? 'pass' : 'fail',
            'message' => 'Sanitization functions working'
        ];
        
        // Test ABSPATH protection
        $tests[] = [
            'name' => 'ABSPATH Protection',
            'status' => defined('ABSPATH') ? 'pass' : 'fail',
            'message' => defined('ABSPATH') ? 'WordPress path defined' : 'ABSPATH not defined'
        ];
        
        return $tests;
    }
    
    public static function test_specific_module($request) {
        check_ajax_referer('hexagon_test_nonce', 'nonce');
        
        $module = sanitize_text_field($_POST['module']);
        
        $result = [];
        switch ($module) {
            case 'ai':
                $result = self::test_ai_modules();
                break;
            case 'email':
                $result = self::test_email_system();
                break;
            case 'social':
                $result = self::test_social_integration();
                break;
            case 'api':
                $result = self::test_rest_api();
                break;
            case 'auto_repair':
                $result = self::test_auto_repair();
                break;
            default:
                wp_send_json_error(['message' => 'Unknown module']);
        }
        
        wp_send_json_success($result);
    }
    
    public static function get_last_test_results() {
        return get_option('hexagon_last_test_results', null);
    }
    
    public static function run_quick_health_check() {
        global $wpdb;
        $checks = [
            'database' => $wpdb && $wpdb->check_connection(),
            'logging' => function_exists('hexagon_log'),
            'modules' => class_exists('Hexagon_Automation'),
            'wp_functions' => function_exists('wp_mail') && function_exists('wp_remote_post')
        ];
        
        $healthy = true;
        foreach ($checks as $check => $status) {
            if (!$status) {
                $healthy = false;
                break;
            }
        }
        
        return [
            'status' => $healthy ? 'healthy' : 'issues',
            'checks' => $checks,
            'timestamp' => current_time('mysql')
        ];
    }
}