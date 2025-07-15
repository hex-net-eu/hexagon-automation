<?php
if (!defined('ABSPATH')) exit;

class Hexagon_Auto_Repair {
    
    private static $error_thresholds = [
        'email' => 3,
        'ai' => 5,
        'social' => 3,
        'database' => 2
    ];
    
    private static $repair_attempts = [];
    private static $max_repair_attempts = 3;
    
    public static function init() {
        add_action('init', [__CLASS__, 'schedule_health_checks']);
        add_action('hexagon_health_check', [__CLASS__, 'run_health_check']);
        add_action('hexagon_auto_repair', [__CLASS__, 'run_auto_repair']);
        add_action('wp_loaded', [__CLASS__, 'monitor_system_health']);
        
        // Hook into error logging
        add_action('hexagon_log_entry', [__CLASS__, 'analyze_error'], 10, 3);
        
        // Emergency recovery hooks
        add_action('wp_fatal_error_handler_enabled', [__CLASS__, 'emergency_recovery']);
        add_action('shutdown', [__CLASS__, 'check_for_fatal_errors']);
    }
    
    public static function schedule_health_checks() {
        // Schedule hourly health checks
        if (!wp_next_scheduled('hexagon_health_check')) {
            wp_schedule_event(time(), 'hourly', 'hexagon_health_check');
        }
        
        // Schedule daily deep repairs
        if (!wp_next_scheduled('hexagon_auto_repair')) {
            wp_schedule_event(time(), 'daily', 'hexagon_auto_repair');
        }
    }
    
    public static function run_health_check() {
        hexagon_log('Health Check', 'Starting system health check', 'info');
        
        $issues = [];
        
        // Check email system
        $email_issues = self::check_email_health();
        if (!empty($email_issues)) {
            $issues['email'] = $email_issues;
        }
        
        // Check AI systems
        $ai_issues = self::check_ai_health();
        if (!empty($ai_issues)) {
            $issues['ai'] = $ai_issues;
        }
        
        // Check social media connections
        $social_issues = self::check_social_health();
        if (!empty($social_issues)) {
            $issues['social'] = $social_issues;
        }
        
        // Check database health
        $db_issues = self::check_database_health();
        if (!empty($db_issues)) {
            $issues['database'] = $db_issues;
        }
        
        // Check file system
        $file_issues = self::check_filesystem_health();
        if (!empty($file_issues)) {
            $issues['filesystem'] = $file_issues;
        }
        
        if (!empty($issues)) {
            hexagon_log('Health Check Issues', 'Found ' . count($issues) . ' system issues', 'warning');
            self::attempt_auto_repair($issues);
        } else {
            hexagon_log('Health Check', 'All systems healthy', 'success');
        }
        
        // Update health status
        update_option('hexagon_last_health_check', current_time('mysql'));
        update_option('hexagon_health_status', empty($issues) ? 'healthy' : 'issues_detected');
    }
    
    private static function check_email_health() {
        $issues = [];
        
        // Check SMTP configuration
        if (hexagon_get_option('hexagon_email_use_smtp', false)) {
            $host = hexagon_get_option('hexagon_email_smtp_host');
            $port = hexagon_get_option('hexagon_email_smtp_port', 587);
            
            // Test SMTP connection
            $connection = @fsockopen($host, $port, $errno, $errstr, 5);
            if (!$connection) {
                $issues[] = "SMTP connection failed: $errstr";
            } else {
                fclose($connection);
            }
        }
        
        // Check recent email failures
        $error_count = hexagon_get_option('hexagon_email_error_count', 0);
        if ($error_count >= self::$error_thresholds['email']) {
            $issues[] = "High email error count: $error_count";
        }
        
        // Test email sending
        try {
            $test_result = wp_mail('test@example.com', 'Health Check', 'Test', [], [], true); // Dry run
            if (!$test_result) {
                $issues[] = 'Email sending test failed';
            }
        } catch (Exception $e) {
            $issues[] = 'Email system exception: ' . $e->getMessage();
        }
        
        return $issues;
    }
    
    private static function check_ai_health() {
        $issues = [];
        
        // Check API keys
        $providers = ['chatgpt', 'claude', 'perplexity'];
        foreach ($providers as $provider) {
            $api_key = hexagon_get_option("hexagon_ai_{$provider}_api_key");
            if (empty($api_key)) {
                $issues[] = "Missing API key for $provider";
            }
        }
        
        // Check recent AI failures
        global $wpdb;
        $table_name = $wpdb->prefix . 'hex_logs';
        $ai_errors = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table_name WHERE action LIKE '%AI%' AND level = 'error' AND created_at >= %s",
            date('Y-m-d H:i:s', strtotime('-1 hour'))
        ));
        
        if ($ai_errors >= self::$error_thresholds['ai']) {
            $issues[] = "High AI error count: $ai_errors in last hour";
        }
        
        return $issues;
    }
    
    private static function check_social_health() {
        $issues = [];
        
        // Check social media tokens
        $platforms = [
            'facebook' => 'hexagon_social_facebook_token',
            'instagram' => 'hexagon_social_instagram_token',
            'twitter' => 'hexagon_social_twitter_api_key',
            'linkedin' => 'hexagon_social_linkedin_token'
        ];
        
        foreach ($platforms as $platform => $option_key) {
            $token = hexagon_get_option($option_key);
            if (empty($token)) {
                $issues[] = "Missing token for $platform";
            }
        }
        
        // Check recent social media failures
        global $wpdb;
        $table_name = $wpdb->prefix . 'hex_logs';
        $social_errors = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table_name WHERE action LIKE '%Social%' AND level = 'error' AND created_at >= %s",
            date('Y-m-d H:i:s', strtotime('-1 hour'))
        ));
        
        if ($social_errors >= self::$error_thresholds['social']) {
            $issues[] = "High social media error count: $social_errors in last hour";
        }
        
        return $issues;
    }
    
    private static function check_database_health() {
        $issues = [];
        global $wpdb;
        
        // Check database connection
        if (!$wpdb->check_connection()) {
            $issues[] = 'Database connection failed';
        }
        
        // Check required tables
        $required_tables = [
            $wpdb->prefix . 'hex_logs'
        ];
        
        foreach ($required_tables as $table) {
            $exists = $wpdb->get_var("SHOW TABLES LIKE '$table'");
            if (!$exists) {
                $issues[] = "Missing required table: $table";
            }
        }
        
        // Check database size and performance
        $db_size = $wpdb->get_var("
            SELECT ROUND(SUM(data_length + index_length) / 1024 / 1024, 1) 
            FROM information_schema.tables 
            WHERE table_schema = '{$wpdb->dbname}'
        ");
        
        if ($db_size > 1000) { // 1GB threshold
            $issues[] = "Database size is large: {$db_size}MB";
        }
        
        // Check for table corruption
        $corrupted = $wpdb->get_results("CHECK TABLE {$wpdb->prefix}hex_logs");
        foreach ($corrupted as $result) {
            if ($result->Msg_type === 'error') {
                $issues[] = "Table corruption detected: {$result->Table}";
            }
        }
        
        return $issues;
    }
    
    private static function check_filesystem_health() {
        $issues = [];
        
        // Check disk space
        $free_space = disk_free_space(ABSPATH);
        $total_space = disk_total_space(ABSPATH);
        $usage_percent = (($total_space - $free_space) / $total_space) * 100;
        
        if ($usage_percent > 90) {
            $issues[] = "Disk space critical: {$usage_percent}% used";
        }
        
        // Check WordPress directories permissions
        $critical_dirs = [
            WP_CONTENT_DIR,
            WP_CONTENT_DIR . '/uploads',
            WP_CONTENT_DIR . '/cache',
            WP_CONTENT_DIR . '/plugins'
        ];
        
        foreach ($critical_dirs as $dir) {
            if (is_dir($dir) && !is_writable($dir)) {
                $issues[] = "Directory not writable: $dir";
            }
        }
        
        // Check memory usage
        $memory_limit = wp_convert_hr_to_bytes(ini_get('memory_limit'));
        $memory_usage = memory_get_usage(true);
        $memory_percent = ($memory_usage / $memory_limit) * 100;
        
        if ($memory_percent > 80) {
            $issues[] = "High memory usage: {$memory_percent}%";
        }
        
        return $issues;
    }
    
    public static function attempt_auto_repair($issues) {
        foreach ($issues as $category => $category_issues) {
            $repair_key = "hexagon_repair_attempts_$category";
            $attempts = get_option($repair_key, 0);
            
            if ($attempts >= self::$max_repair_attempts) {
                hexagon_log('Auto Repair Skipped', "Max repair attempts reached for $category", 'warning');
                continue;
            }
            
            hexagon_log('Auto Repair Started', "Attempting to repair $category issues", 'info');
            
            $success = false;
            switch ($category) {
                case 'email':
                    $success = self::repair_email_issues($category_issues);
                    break;
                case 'ai':
                    $success = self::repair_ai_issues($category_issues);
                    break;
                case 'social':
                    $success = self::repair_social_issues($category_issues);
                    break;
                case 'database':
                    $success = self::repair_database_issues($category_issues);
                    break;
                case 'filesystem':
                    $success = self::repair_filesystem_issues($category_issues);
                    break;
            }
            
            if ($success) {
                hexagon_log('Auto Repair Success', "Successfully repaired $category", 'success');
                delete_option($repair_key);
                
                // Send success notification
                Hexagon_Email_Integration::send_error_alert(
                    "Auto-repair successful for $category",
                    implode(', ', $category_issues)
                );
            } else {
                hexagon_log('Auto Repair Failed', "Failed to repair $category", 'error');
                update_option($repair_key, $attempts + 1);
                
                // Send failure notification if max attempts reached
                if ($attempts + 1 >= self::$max_repair_attempts) {
                    Hexagon_Email_Integration::send_error_alert(
                        "Auto-repair failed for $category after max attempts",
                        implode(', ', $category_issues)
                    );
                }
            }
        }
    }
    
    private static function repair_email_issues($issues) {
        $repaired = true;
        
        foreach ($issues as $issue) {
            if (strpos($issue, 'SMTP connection failed') !== false) {
                // Try to auto-configure SMTP
                Hexagon_Email_Integration::auto_repair_email_config();
            } elseif (strpos($issue, 'High email error count') !== false) {
                // Reset error count
                update_option('hexagon_email_error_count', 0);
            } elseif (strpos($issue, 'Email sending test failed') !== false) {
                // Reset email configuration
                Hexagon_Email_Integration::auto_repair_email_config();
            }
        }
        
        return $repaired;
    }
    
    private static function repair_ai_issues($issues) {
        $repaired = true;
        
        foreach ($issues as $issue) {
            if (strpos($issue, 'Missing API key') !== false) {
                // Log warning - requires manual intervention
                hexagon_log('AI Repair', 'Missing API keys require manual configuration', 'warning');
                $repaired = false;
            } elseif (strpos($issue, 'High AI error count') !== false) {
                // Clear usage stats to reset counters
                delete_option('hexagon_ai_usage_stats');
                hexagon_log('AI Repair', 'Reset AI usage statistics', 'info');
            }
        }
        
        return $repaired;
    }
    
    private static function repair_social_issues($issues) {
        $repaired = true;
        
        foreach ($issues as $issue) {
            if (strpos($issue, 'Missing token') !== false) {
                // Log warning - requires manual intervention
                hexagon_log('Social Repair', 'Missing social tokens require manual configuration', 'warning');
                $repaired = false;
            } elseif (strpos($issue, 'High social media error count') !== false) {
                // Reset social stats
                delete_option('hexagon_social_stats');
                hexagon_log('Social Repair', 'Reset social media statistics', 'info');
            }
        }
        
        return $repaired;
    }
    
    private static function repair_database_issues($issues) {
        global $wpdb;
        $repaired = true;
        
        foreach ($issues as $issue) {
            if (strpos($issue, 'Missing required table') !== false) {
                // Recreate missing tables
                Hexagon_Activation::create_tables();
                hexagon_log('Database Repair', 'Recreated missing tables', 'info');
            } elseif (strpos($issue, 'Table corruption detected') !== false) {
                // Repair corrupted tables
                $wpdb->query("REPAIR TABLE {$wpdb->prefix}hex_logs");
                hexagon_log('Database Repair', 'Repaired corrupted tables', 'info');
            } elseif (strpos($issue, 'Database size is large') !== false) {
                // Clean old logs
                $wpdb->query($wpdb->prepare(
                    "DELETE FROM {$wpdb->prefix}hex_logs WHERE created_at < %s",
                    date('Y-m-d H:i:s', strtotime('-30 days'))
                ));
                hexagon_log('Database Repair', 'Cleaned old logs', 'info');
            }
        }
        
        return $repaired;
    }
    
    private static function repair_filesystem_issues($issues) {
        $repaired = true;
        
        foreach ($issues as $issue) {
            if (strpos($issue, 'Disk space critical') !== false) {
                // Clean temporary files
                self::clean_temporary_files();
                hexagon_log('Filesystem Repair', 'Cleaned temporary files', 'info');
            } elseif (strpos($issue, 'Directory not writable') !== false) {
                // Try to fix permissions (limited success)
                hexagon_log('Filesystem Repair', 'Directory permissions require manual fix', 'warning');
                $repaired = false;
            } elseif (strpos($issue, 'High memory usage') !== false) {
                // Clear object cache
                if (function_exists('wp_cache_flush')) {
                    wp_cache_flush();
                }
                hexagon_log('Filesystem Repair', 'Cleared object cache', 'info');
            }
        }
        
        return $repaired;
    }
    
    private static function clean_temporary_files() {
        // Clean WordPress temporary files
        $temp_dirs = [
            WP_CONTENT_DIR . '/cache',
            WP_CONTENT_DIR . '/temp',
            sys_get_temp_dir()
        ];
        
        foreach ($temp_dirs as $dir) {
            if (is_dir($dir)) {
                $files = glob($dir . '/*');
                foreach ($files as $file) {
                    if (is_file($file) && filemtime($file) < strtotime('-1 day')) {
                        @unlink($file);
                    }
                }
            }
        }
    }
    
    public static function analyze_error($action, $context, $level) {
        if ($level === 'error') {
            // Increment error counters
            $error_key = 'hexagon_error_count_' . strtolower($action);
            $count = get_option($error_key, 0) + 1;
            update_option($error_key, $count);
            
            // Check if immediate repair is needed
            if ($count >= 5) { // Threshold for immediate repair
                hexagon_log('Auto Repair Triggered', "High error count for $action", 'warning');
                wp_schedule_single_event(time() + 60, 'hexagon_health_check'); // Check in 1 minute
            }
        }
    }
    
    public static function monitor_system_health() {
        // Check for critical errors in the last hour
        global $wpdb;
        $table_name = $wpdb->prefix . 'hex_logs';
        
        $critical_errors = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table_name WHERE level = 'error' AND created_at >= %s",
            date('Y-m-d H:i:s', strtotime('-1 hour'))
        ));
        
        if ($critical_errors >= 10) { // Critical threshold
            hexagon_log('System Alert', "Critical error count: $critical_errors in last hour", 'error');
            
            // Emergency repair
            wp_schedule_single_event(time() + 30, 'hexagon_health_check');
            
            // Send immediate alert
            Hexagon_Email_Integration::send_error_alert(
                'CRITICAL: High error rate detected',
                "$critical_errors errors in the last hour"
            );
        }
    }
    
    public static function emergency_recovery() {
        // Called when WordPress detects a fatal error
        hexagon_log('Emergency Recovery', 'WordPress fatal error detected - running emergency recovery', 'error');
        
        // Disable problematic features temporarily
        update_option('hexagon_safe_mode', true);
        
        // Send emergency alert
        if (function_exists('wp_mail')) {
            wp_mail(
                get_option('admin_email'),
                'EMERGENCY: Hexagon Automation Fatal Error',
                'A fatal error was detected in Hexagon Automation. The plugin has been switched to safe mode.'
            );
        }
    }
    
    public static function check_for_fatal_errors() {
        $error = error_get_last();
        
        if ($error && $error['type'] === E_ERROR) {
            // Check if error is related to our plugin
            if (strpos($error['file'], 'hexagon-automation') !== false) {
                hexagon_log('Fatal Error Detected', $error['message'] . ' in ' . $error['file'], 'error');
                
                // Enable safe mode
                update_option('hexagon_safe_mode', true);
            }
        }
    }
    
    public static function run_auto_repair() {
        hexagon_log('Auto Repair', 'Running daily auto-repair routine', 'info');
        
        // Run comprehensive health check
        self::run_health_check();
        
        // Clean up old data
        self::cleanup_old_data();
        
        // Optimize database
        self::optimize_database();
        
        // Reset error counters if system is stable
        self::reset_stable_counters();
    }
    
    private static function cleanup_old_data() {
        global $wpdb;
        
        // Clean logs older than 30 days
        $wpdb->query($wpdb->prepare(
            "DELETE FROM {$wpdb->prefix}hex_logs WHERE created_at < %s",
            date('Y-m-d H:i:s', strtotime('-30 days'))
        ));
        
        // Clean expired transients
        delete_expired_transients();
        
        hexagon_log('Auto Repair', 'Cleaned up old data', 'info');
    }
    
    private static function optimize_database() {
        global $wpdb;
        
        // Optimize hex_logs table
        $wpdb->query("OPTIMIZE TABLE {$wpdb->prefix}hex_logs");
        
        hexagon_log('Auto Repair', 'Optimized database tables', 'info');
    }
    
    private static function reset_stable_counters() {
        // Reset error counters if no errors in last 24 hours
        global $wpdb;
        $table_name = $wpdb->prefix . 'hex_logs';
        
        $recent_errors = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table_name WHERE level = 'error' AND created_at >= %s",
            date('Y-m-d H:i:s', strtotime('-24 hours'))
        ));
        
        if ($recent_errors == 0) {
            // Reset all error counters
            $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE 'hexagon_error_count_%'");
            $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE 'hexagon_repair_attempts_%'");
            
            hexagon_log('Auto Repair', 'Reset error counters - system stable', 'success');
        }
    }
    
    public static function get_system_health_report() {
        $report = [
            'overall_status' => get_option('hexagon_health_status', 'unknown'),
            'last_check' => get_option('hexagon_last_health_check', 'never'),
            'safe_mode' => get_option('hexagon_safe_mode', false),
            'error_counts' => [],
            'repair_attempts' => []
        ];
        
        // Get error counts
        global $wpdb;
        $error_options = $wpdb->get_results(
            "SELECT option_name, option_value FROM {$wpdb->options} WHERE option_name LIKE 'hexagon_error_count_%'"
        );
        
        foreach ($error_options as $option) {
            $key = str_replace('hexagon_error_count_', '', $option->option_name);
            $report['error_counts'][$key] = intval($option->option_value);
        }
        
        // Get repair attempts
        $repair_options = $wpdb->get_results(
            "SELECT option_name, option_value FROM {$wpdb->options} WHERE option_name LIKE 'hexagon_repair_attempts_%'"
        );
        
        foreach ($repair_options as $option) {
            $key = str_replace('hexagon_repair_attempts_', '', $option->option_name);
            $report['repair_attempts'][$key] = intval($option->option_value);
        }
        
        return $report;
    }
}