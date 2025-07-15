<?php
if (!defined('ABSPATH')) exit;

class Hexagon_Logger {
    
    const LOG_LEVELS = ['debug', 'info', 'warning', 'error', 'critical'];
    const MAX_LOG_SIZE = 50000000; // 50MB
    const MAX_LOGS_PER_CATEGORY = 10000;
    
    private static $log_buffer = [];
    private static $performance_data = [];
    
    public static function init() {
        add_action('wp_ajax_hexagon_get_logs', [__CLASS__, 'ajax_get_logs']);
        add_action('wp_ajax_hexagon_clear_logs', [__CLASS__, 'ajax_clear_logs']);
        add_action('wp_ajax_hexagon_export_logs', [__CLASS__, 'ajax_export_logs']);
        add_action('wp_ajax_hexagon_get_log_stats', [__CLASS__, 'ajax_get_log_stats']);
        add_action('wp_ajax_hexagon_toggle_debug_mode', [__CLASS__, 'ajax_toggle_debug_mode']);
        add_action('wp_ajax_hexagon_get_performance_data', [__CLASS__, 'ajax_get_performance_data']);
        add_action('wp_ajax_hexagon_download_debug_info', [__CLASS__, 'ajax_download_debug_info']);
        
        // Real-time logging hooks
        add_action('wp_ajax_hexagon_start_real_time_logging', [__CLASS__, 'ajax_start_real_time_logging']);
        add_action('wp_ajax_hexagon_stop_real_time_logging', [__CLASS__, 'ajax_stop_real_time_logging']);
        
        // Cleanup old logs
        if (!wp_next_scheduled('hexagon_cleanup_logs')) {
            wp_schedule_event(time(), 'daily', 'hexagon_cleanup_logs');
        }
        add_action('hexagon_cleanup_logs', [__CLASS__, 'cleanup_old_logs']);
        
        // Performance monitoring
        add_action('init', [__CLASS__, 'start_performance_monitoring']);
        add_action('wp_footer', [__CLASS__, 'end_performance_monitoring']);
        add_action('admin_footer', [__CLASS__, 'end_performance_monitoring']);
        
        // Error handling
        add_action('wp_ajax_hexagon_get_error_details', [__CLASS__, 'ajax_get_error_details']);
        add_action('wp_ajax_hexagon_mark_error_resolved', [__CLASS__, 'ajax_mark_error_resolved']);
        
        // Flush buffer on shutdown
        add_action('shutdown', [__CLASS__, 'flush_log_buffer']);
    }
    
    public static function log($category, $message, $level = 'info', $context = []) {
        global $wpdb;
        
        // Validate log level
        if (!in_array($level, self::LOG_LEVELS)) {
            $level = 'info';
        }
        
        // Check if logging is enabled for this level
        if (!self::should_log($level)) {
            return false;
        }
        
        // Prepare log entry
        $log_entry = [
            'log_id' => 'log_' . uniqid(),
            'level' => $level,
            'category' => sanitize_text_field($category),
            'message' => $message,
            'context' => is_array($context) ? json_encode($context) : $context,
            'user_id' => get_current_user_id(),
            'ip_address' => self::get_client_ip(),
            'user_agent' => substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 255),
            'file' => self::get_caller_file(),
            'line' => self::get_caller_line(),
            'trace' => self::get_backtrace(),
            'created_at' => current_time('mysql')
        ];
        
        // Add to buffer for batch processing
        self::$log_buffer[] = $log_entry;
        
        // If it's a critical error, process immediately
        if ($level === 'critical' || $level === 'error') {
            self::flush_log_buffer();
            self::handle_critical_error($log_entry);
        }
        
        // Flush buffer if it gets too large
        if (count(self::$log_buffer) >= 50) {
            self::flush_log_buffer();
        }
        
        return true;
    }
    
    public static function debug($category, $message, $context = []) {
        return self::log($category, $message, 'debug', $context);
    }
    
    public static function info($category, $message, $context = []) {
        return self::log($category, $message, 'info', $context);
    }
    
    public static function warning($category, $message, $context = []) {
        return self::log($category, $message, 'warning', $context);
    }
    
    public static function error($category, $message, $context = []) {
        return self::log($category, $message, 'error', $context);
    }
    
    public static function critical($category, $message, $context = []) {
        return self::log($category, $message, 'critical', $context);
    }
    
    public static function flush_log_buffer() {
        if (empty(self::$log_buffer)) {
            return;
        }
        
        global $wpdb;
        $table = $wpdb->prefix . 'hex_logs';
        
        // Batch insert for performance
        $values = [];
        $placeholders = [];
        
        foreach (self::$log_buffer as $entry) {
            $placeholders[] = "(%s, %s, %s, %s, %s, %d, %s, %s, %s, %d, %s, %s)";
            $values = array_merge($values, [
                $entry['log_id'],
                $entry['level'],
                $entry['category'],
                $entry['message'],
                $entry['context'],
                $entry['user_id'],
                $entry['ip_address'],
                $entry['user_agent'],
                $entry['file'],
                $entry['line'],
                $entry['trace'],
                $entry['created_at']
            ]);
        }
        
        $sql = "INSERT INTO {$table} (log_id, level, category, message, context, user_id, ip_address, user_agent, file, line, trace, created_at) VALUES " . implode(', ', $placeholders);
        
        $wpdb->query($wpdb->prepare($sql, $values));
        
        // Clear buffer
        self::$log_buffer = [];
    }
    
    private static function should_log($level) {
        $debug_mode = get_option('hexagon_debug_mode', false);
        $log_level = get_option('hexagon_log_level', 'info');
        
        $level_priorities = [
            'debug' => 0,
            'info' => 1,
            'warning' => 2,
            'error' => 3,
            'critical' => 4
        ];
        
        // Always log errors and critical
        if (in_array($level, ['error', 'critical'])) {
            return true;
        }
        
        // In debug mode, log everything
        if ($debug_mode) {
            return true;
        }
        
        // Check configured log level
        $current_priority = $level_priorities[$log_level] ?? 1;
        $log_priority = $level_priorities[$level] ?? 1;
        
        return $log_priority >= $current_priority;
    }
    
    private static function handle_critical_error($log_entry) {
        // Store in error log table for quick access
        global $wpdb;
        $error_table = $wpdb->prefix . 'hex_error_log';
        
        $existing_error = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$error_table} WHERE message = %s AND file = %s AND line = %d AND resolved = 0",
            $log_entry['message'],
            $log_entry['file'],
            $log_entry['line']
        ));
        
        if ($existing_error) {
            // Update occurrence count
            $wpdb->update(
                $error_table,
                [
                    'occurrence_count' => $existing_error->occurrence_count + 1,
                    'timestamp' => current_time('mysql')
                ],
                ['id' => $existing_error->id],
                ['%d', '%s'],
                ['%d']
            );
        } else {
            // Create new error record
            $wpdb->insert(
                $error_table,
                [
                    'error_id' => 'err_' . uniqid(),
                    'timestamp' => current_time('mysql'),
                    'type' => $log_entry['category'],
                    'severity' => $log_entry['level'],
                    'message' => $log_entry['message'],
                    'file' => $log_entry['file'],
                    'line' => $log_entry['line'],
                    'trace' => $log_entry['trace'],
                    'context' => $log_entry['context'],
                    'user_id' => $log_entry['user_id'],
                    'ip_address' => $log_entry['ip_address'],
                    'resolved' => 0,
                    'occurrence_count' => 1
                ],
                ['%s', '%s', '%s', '%s', '%s', '%s', '%d', '%s', '%s', '%d', '%s', '%d', '%d']
            );
        }
        
        // Send notification if enabled
        if (get_option('hexagon_error_notifications', true)) {
            self::send_error_notification($log_entry);
        }
    }
    
    private static function send_error_notification($log_entry) {
        $admin_email = get_option('admin_email');
        $site_name = get_bloginfo('name');
        
        $subject = "[{$site_name}] Hexagon Critical Error Alert";
        $message = "A critical error occurred in Hexagon Automation:\n\n";
        $message .= "Category: {$log_entry['category']}\n";
        $message .= "Message: {$log_entry['message']}\n";
        $message .= "File: {$log_entry['file']}:{$log_entry['line']}\n";
        $message .= "Time: {$log_entry['created_at']}\n";
        $message .= "User: " . (get_user_by('id', $log_entry['user_id'])->user_login ?? 'Guest') . "\n";
        $message .= "IP: {$log_entry['ip_address']}\n\n";
        $message .= "Please check the Hexagon dashboard for more details.";
        
        wp_mail($admin_email, $subject, $message);
    }
    
    public static function start_performance_monitoring() {
        if (!self::should_log('debug')) {
            return;
        }
        
        self::$performance_data['start_time'] = microtime(true);
        self::$performance_data['start_memory'] = memory_get_usage(true);
        self::$performance_data['queries_start'] = get_num_queries();
    }
    
    public static function end_performance_monitoring() {
        if (empty(self::$performance_data['start_time'])) {
            return;
        }
        
        $end_time = microtime(true);
        $end_memory = memory_get_usage(true);
        $queries_end = get_num_queries();
        
        $performance = [
            'execution_time' => $end_time - self::$performance_data['start_time'],
            'memory_usage' => $end_memory - self::$performance_data['start_memory'],
            'peak_memory' => memory_get_peak_usage(true),
            'database_queries' => $queries_end - self::$performance_data['queries_start'],
            'page_url' => $_SERVER['REQUEST_URI'] ?? '',
            'is_admin' => is_admin()
        ];
        
        // Log performance if it's slow or uses too much memory
        if ($performance['execution_time'] > 2 || $performance['memory_usage'] > 10485760) { // 10MB
            self::warning('Performance', 'Slow page detected', $performance);
        }
        
        // Store performance data
        self::store_performance_data($performance);
    }
    
    private static function store_performance_data($performance) {
        global $wpdb;
        $table = $wpdb->prefix . 'hex_logs';
        
        $wpdb->insert(
            $table,
            [
                'log_id' => 'perf_' . uniqid(),
                'level' => 'debug',
                'category' => 'Performance',
                'message' => sprintf(
                    'Page load: %.2fs, Memory: %s, Queries: %d',
                    $performance['execution_time'],
                    size_format($performance['memory_usage']),
                    $performance['database_queries']
                ),
                'context' => json_encode($performance),
                'user_id' => get_current_user_id(),
                'ip_address' => self::get_client_ip(),
                'created_at' => current_time('mysql')
            ],
            ['%s', '%s', '%s', '%s', '%s', '%d', '%s', '%s']
        );
    }
    
    public static function cleanup_old_logs() {
        global $wpdb;
        
        $retention_days = get_option('hexagon_log_retention_days', 30);
        $cleanup_date = date('Y-m-d H:i:s', strtotime("-{$retention_days} days"));
        
        // Clean up old logs
        $logs_table = $wpdb->prefix . 'hex_logs';
        $deleted_logs = $wpdb->query($wpdb->prepare(
            "DELETE FROM {$logs_table} WHERE created_at < %s",
            $cleanup_date
        ));
        
        // Clean up resolved errors older than 7 days
        $error_table = $wpdb->prefix . 'hex_error_log';
        $deleted_errors = $wpdb->query($wpdb->prepare(
            "DELETE FROM {$error_table} WHERE resolved = 1 AND resolved_at < %s",
            date('Y-m-d H:i:s', strtotime('-7 days'))
        ));
        
        if ($deleted_logs > 0 || $deleted_errors > 0) {
            self::info('System', "Cleaned up {$deleted_logs} old logs and {$deleted_errors} resolved errors");
        }
        
        // Check log table size and warn if too large
        $table_size = self::get_table_size($logs_table);
        if ($table_size > 100) { // 100MB
            self::warning('System', "Log table is large ({$table_size}MB). Consider reducing retention period.");
        }
    }
    
    private static function get_table_size($table_name) {
        global $wpdb;
        
        $size = $wpdb->get_var($wpdb->prepare("
            SELECT ROUND((data_length + index_length) / 1024 / 1024, 2) AS size_mb 
            FROM information_schema.tables 
            WHERE table_schema = %s AND table_name = %s
        ", $wpdb->dbname, $table_name));
        
        return floatval($size);
    }
    
    private static function get_client_ip() {
        $ip_headers = ['HTTP_X_FORWARDED_FOR', 'HTTP_X_REAL_IP', 'HTTP_CLIENT_IP', 'REMOTE_ADDR'];
        
        foreach ($ip_headers as $header) {
            if (isset($_SERVER[$header]) && !empty($_SERVER[$header])) {
                $ip = $_SERVER[$header];
                if (strpos($ip, ',') !== false) {
                    $ip = trim(explode(',', $ip)[0]);
                }
                if (filter_var($ip, FILTER_VALIDATE_IP)) {
                    return $ip;
                }
            }
        }
        
        return '0.0.0.0';
    }
    
    private static function get_caller_file() {
        $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 5);
        foreach ($trace as $frame) {
            if (isset($frame['file']) && strpos($frame['file'], 'hexagon') !== false && !strpos($frame['file'], 'class-hexagon-logger.php')) {
                return str_replace(ABSPATH, '', $frame['file']);
            }
        }
        return '';
    }
    
    private static function get_caller_line() {
        $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 5);
        foreach ($trace as $frame) {
            if (isset($frame['file']) && strpos($frame['file'], 'hexagon') !== false && !strpos($frame['file'], 'class-hexagon-logger.php')) {
                return $frame['line'] ?? 0;
            }
        }
        return 0;
    }
    
    private static function get_backtrace() {
        if (!get_option('hexagon_debug_mode', false)) {
            return '';
        }
        
        $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 10);
        $stack = [];
        
        foreach ($trace as $frame) {
            if (isset($frame['file']) && !strpos($frame['file'], 'class-hexagon-logger.php')) {
                $stack[] = str_replace(ABSPATH, '', $frame['file']) . ':' . ($frame['line'] ?? 0);
            }
        }
        
        return implode("\n", array_slice($stack, 0, 5));
    }
    
    // AJAX Handlers
    public static function ajax_get_logs() {
        check_ajax_referer('hexagon_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
            return;
        }
        
        global $wpdb;
        $table = $wpdb->prefix . 'hex_logs';
        
        $level = sanitize_text_field($_POST['level'] ?? 'all');
        $category = sanitize_text_field($_POST['category'] ?? 'all');
        $limit = (int) ($_POST['limit'] ?? 100);
        $offset = (int) ($_POST['offset'] ?? 0);
        $search = sanitize_text_field($_POST['search'] ?? '');
        
        $where_conditions = [];
        $where_values = [];
        
        if ($level !== 'all') {
            $where_conditions[] = "level = %s";
            $where_values[] = $level;
        }
        
        if ($category !== 'all') {
            $where_conditions[] = "category = %s";
            $where_values[] = $category;
        }
        
        if (!empty($search)) {
            $where_conditions[] = "(message LIKE %s OR category LIKE %s)";
            $where_values[] = '%' . $search . '%';
            $where_values[] = '%' . $search . '%';
        }
        
        $where_clause = '';
        if (!empty($where_conditions)) {
            $where_clause = 'WHERE ' . implode(' AND ', $where_conditions);
        }
        
        $where_values[] = $limit;
        $where_values[] = $offset;
        
        $logs = $wpdb->get_results($wpdb->prepare("
            SELECT * FROM {$table} 
            {$where_clause}
            ORDER BY created_at DESC 
            LIMIT %d OFFSET %d
        ", ...$where_values), ARRAY_A);
        
        // Decode JSON context for each log
        foreach ($logs as &$log) {
            $log['context'] = json_decode($log['context'], true);
        }
        
        wp_send_json_success($logs);
    }
    
    public static function ajax_get_log_stats() {
        check_ajax_referer('hexagon_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
            return;
        }
        
        global $wpdb;
        $table = $wpdb->prefix . 'hex_logs';
        $error_table = $wpdb->prefix . 'hex_error_log';
        
        // Get log counts by level
        $level_stats = $wpdb->get_results("
            SELECT level, COUNT(*) as count 
            FROM {$table} 
            WHERE created_at > DATE_SUB(NOW(), INTERVAL 24 HOUR)
            GROUP BY level
        ", ARRAY_A);
        
        // Get category stats
        $category_stats = $wpdb->get_results("
            SELECT category, COUNT(*) as count 
            FROM {$table} 
            WHERE created_at > DATE_SUB(NOW(), INTERVAL 7 DAY)
            GROUP BY category 
            ORDER BY count DESC 
            LIMIT 10
        ", ARRAY_A);
        
        // Get error stats
        $error_stats = $wpdb->get_results("
            SELECT COUNT(*) as total_errors,
                   SUM(CASE WHEN resolved = 0 THEN 1 ELSE 0 END) as unresolved_errors,
                   SUM(occurrence_count) as total_occurrences
            FROM {$error_table}
        ", ARRAY_A);
        
        // Get recent performance data
        $performance_stats = $wpdb->get_results("
            SELECT AVG(JSON_EXTRACT(context, '$.execution_time')) as avg_execution_time,
                   AVG(JSON_EXTRACT(context, '$.memory_usage')) as avg_memory_usage,
                   AVG(JSON_EXTRACT(context, '$.database_queries')) as avg_queries
            FROM {$table} 
            WHERE category = 'Performance' 
            AND created_at > DATE_SUB(NOW(), INTERVAL 24 HOUR)
        ", ARRAY_A);
        
        wp_send_json_success([
            'level_stats' => $level_stats,
            'category_stats' => $category_stats,
            'error_stats' => $error_stats[0] ?? [],
            'performance_stats' => $performance_stats[0] ?? [],
            'debug_mode' => get_option('hexagon_debug_mode', false),
            'log_level' => get_option('hexagon_log_level', 'info'),
            'retention_days' => get_option('hexagon_log_retention_days', 30)
        ]);
    }
    
    public static function ajax_clear_logs() {
        check_ajax_referer('hexagon_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
            return;
        }
        
        global $wpdb;
        $table = $wpdb->prefix . 'hex_logs';
        
        $level = sanitize_text_field($_POST['level'] ?? 'all');
        $category = sanitize_text_field($_POST['category'] ?? 'all');
        
        $where_conditions = [];
        $where_values = [];
        
        if ($level !== 'all') {
            $where_conditions[] = "level = %s";
            $where_values[] = $level;
        }
        
        if ($category !== 'all') {
            $where_conditions[] = "category = %s";
            $where_values[] = $category;
        }
        
        if (!empty($where_conditions)) {
            $where_clause = 'WHERE ' . implode(' AND ', $where_conditions);
            $deleted = $wpdb->query($wpdb->prepare("DELETE FROM {$table} {$where_clause}", ...$where_values));
        } else {
            $deleted = $wpdb->query("TRUNCATE TABLE {$table}");
        }
        
        self::info('System', "Logs cleared by user: {$deleted} entries removed");
        
        wp_send_json_success(['deleted' => $deleted]);
    }
    
    public static function ajax_toggle_debug_mode() {
        check_ajax_referer('hexagon_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
            return;
        }
        
        $debug_mode = get_option('hexagon_debug_mode', false);
        $new_mode = !$debug_mode;
        
        update_option('hexagon_debug_mode', $new_mode);
        
        self::info('System', 'Debug mode ' . ($new_mode ? 'enabled' : 'disabled') . ' by user');
        
        wp_send_json_success(['debug_mode' => $new_mode]);
    }
    
    public static function ajax_get_error_details() {
        check_ajax_referer('hexagon_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
            return;
        }
        
        global $wpdb;
        $error_table = $wpdb->prefix . 'hex_error_log';
        
        $error_id = sanitize_text_field($_POST['error_id'] ?? '');
        
        $error = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$error_table} WHERE error_id = %s",
            $error_id
        ), ARRAY_A);
        
        if (!$error) {
            wp_send_json_error('Error not found');
            return;
        }
        
        $error['context'] = json_decode($error['context'], true);
        
        wp_send_json_success($error);
    }
    
    public static function ajax_mark_error_resolved() {
        check_ajax_referer('hexagon_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
            return;
        }
        
        global $wpdb;
        $error_table = $wpdb->prefix . 'hex_error_log';
        
        $error_id = sanitize_text_field($_POST['error_id'] ?? '');
        
        $result = $wpdb->update(
            $error_table,
            [
                'resolved' => 1,
                'resolved_at' => current_time('mysql')
            ],
            ['error_id' => $error_id],
            ['%d', '%s'],
            ['%s']
        );
        
        if ($result === false) {
            wp_send_json_error('Failed to mark error as resolved');
        } else {
            self::info('System', "Error {$error_id} marked as resolved by user");
            wp_send_json_success('Error marked as resolved');
        }
    }
}