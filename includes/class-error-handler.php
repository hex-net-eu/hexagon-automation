<?php
if (!defined('ABSPATH')) exit;

class Hexagon_Error_Handler {
    
    private static $error_log = [];
    private static $max_errors = 100;
    
    public static function init() {
        // Set custom error handler for PHP errors
        set_error_handler([__CLASS__, 'handle_php_error']);
        set_exception_handler([__CLASS__, 'handle_exception']);
        
        // WordPress hooks
        add_action('wp_ajax_hexagon_get_errors', [__CLASS__, 'ajax_get_errors']);
        add_action('wp_ajax_hexagon_clear_errors', [__CLASS__, 'ajax_clear_errors']);
        add_action('wp_ajax_hexagon_export_errors', [__CLASS__, 'ajax_export_errors']);
        
        // Register shutdown function for fatal errors
        register_shutdown_function([__CLASS__, 'handle_fatal_error']);
    }
    
    public static function handle_php_error($severity, $message, $file, $line) {
        // Only handle errors in our plugin directory
        if (strpos($file, HEXAGON_PATH) === false) {
            return false; // Let WordPress handle it
        }
        
        $error_types = [
            E_ERROR => 'Fatal Error',
            E_WARNING => 'Warning',
            E_NOTICE => 'Notice',
            E_STRICT => 'Strict',
            E_DEPRECATED => 'Deprecated',
            E_USER_ERROR => 'User Error',
            E_USER_WARNING => 'User Warning',
            E_USER_NOTICE => 'User Notice'
        ];
        
        $error_type = isset($error_types[$severity]) ? $error_types[$severity] : 'Unknown';
        
        self::log_error([
            'type' => 'PHP Error',
            'severity' => $error_type,
            'message' => $message,
            'file' => str_replace(HEXAGON_PATH, '', $file),
            'line' => $line,
            'trace' => self::get_clean_backtrace()
        ]);
        
        // For fatal errors, don't suppress
        if ($severity === E_ERROR || $severity === E_USER_ERROR) {
            return false;
        }
        
        return true; // Suppress the error
    }
    
    public static function handle_exception($exception) {
        self::log_error([
            'type' => 'Exception',
            'severity' => 'Fatal',
            'message' => $exception->getMessage(),
            'file' => str_replace(HEXAGON_PATH, '', $exception->getFile()),
            'line' => $exception->getLine(),
            'trace' => self::format_exception_trace($exception->getTrace())
        ]);
    }
    
    public static function handle_fatal_error() {
        $error = error_get_last();
        
        if ($error && $error['type'] === E_ERROR) {
            // Only handle fatal errors in our plugin
            if (strpos($error['file'], HEXAGON_PATH) !== false) {
                self::log_error([
                    'type' => 'Fatal Error',
                    'severity' => 'Fatal',
                    'message' => $error['message'],
                    'file' => str_replace(HEXAGON_PATH, '', $error['file']),
                    'line' => $error['line'],
                    'trace' => []
                ]);
            }
        }
    }
    
    public static function log_error($error_data) {
        $error_entry = [
            'id' => uniqid(),
            'timestamp' => current_time('mysql'),
            'type' => $error_data['type'],
            'severity' => $error_data['severity'],
            'message' => $error_data['message'],
            'file' => $error_data['file'],
            'line' => $error_data['line'],
            'trace' => $error_data['trace'],
            'context' => self::get_context()
        ];
        
        // Add to memory log
        self::$error_log[] = $error_entry;
        
        // Keep only last N errors in memory
        if (count(self::$error_log) > self::$max_errors) {
            array_shift(self::$error_log);
        }
        
        // Store in database
        global $wpdb;
        $table_name = $wpdb->prefix . 'hex_error_log';
        
        $wpdb->insert($table_name, [
            'error_id' => $error_entry['id'],
            'timestamp' => $error_entry['timestamp'],
            'type' => $error_entry['type'],
            'severity' => $error_entry['severity'],
            'message' => $error_entry['message'],
            'file' => $error_entry['file'],
            'line' => $error_entry['line'],
            'trace' => json_encode($error_entry['trace']),
            'context' => json_encode($error_entry['context'])
        ]);
        
        // Log critical errors to WordPress debug log as well
        if (in_array($error_data['severity'], ['Fatal', 'Fatal Error', 'Error'])) {
            error_log("Hexagon Automation Error: {$error_data['message']} in {$error_data['file']}:{$error_data['line']}");
            
            // Also log to our system
            hexagon_log('System Error', $error_data['message'], 'error');
        }
        
        // Send alert for critical errors if enabled
        if (hexagon_get_option('hexagon_error_alerts', false)) {
            self::send_error_alert($error_entry);
        }
    }
    
    public static function log_custom_error($message, $severity = 'error', $context = []) {
        $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 5);
        $caller = isset($trace[1]) ? $trace[1] : $trace[0];
        
        self::log_error([
            'type' => 'Custom Error',
            'severity' => ucfirst($severity),
            'message' => $message,
            'file' => isset($caller['file']) ? str_replace(HEXAGON_PATH, '', $caller['file']) : 'unknown',
            'line' => isset($caller['line']) ? $caller['line'] : 0,
            'trace' => self::format_backtrace($trace),
            'custom_context' => $context
        ]);
    }
    
    private static function get_context() {
        global $wp_query, $current_user;
        
        return [
            'url' => isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '',
            'user_agent' => isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '',
            'user_id' => $current_user ? $current_user->ID : 0,
            'memory_usage' => memory_get_usage(true),
            'memory_peak' => memory_get_peak_usage(true),
            'php_version' => PHP_VERSION,
            'wp_version' => get_bloginfo('version'),
            'plugin_version' => HEXAGON_VERSION,
            'is_admin' => is_admin(),
            'is_ajax' => defined('DOING_AJAX') && DOING_AJAX,
            'is_cron' => defined('DOING_CRON') && DOING_CRON
        ];
    }
    
    private static function get_clean_backtrace() {
        $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 10);
        return self::format_backtrace($trace);
    }
    
    private static function format_backtrace($trace) {
        $clean_trace = [];
        
        foreach ($trace as $step) {
            if (isset($step['file']) && strpos($step['file'], HEXAGON_PATH) !== false) {
                $clean_trace[] = [
                    'file' => str_replace(HEXAGON_PATH, '', $step['file']),
                    'line' => isset($step['line']) ? $step['line'] : 0,
                    'function' => isset($step['function']) ? $step['function'] : '',
                    'class' => isset($step['class']) ? $step['class'] : ''
                ];
            }
        }
        
        return $clean_trace;
    }
    
    private static function format_exception_trace($trace) {
        $clean_trace = [];
        
        foreach ($trace as $step) {
            $clean_trace[] = [
                'file' => isset($step['file']) ? str_replace(HEXAGON_PATH, '', $step['file']) : '',
                'line' => isset($step['line']) ? $step['line'] : 0,
                'function' => isset($step['function']) ? $step['function'] : '',
                'class' => isset($step['class']) ? $step['class'] : ''
            ];
        }
        
        return $clean_trace;
    }
    
    public static function get_errors($filters = []) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'hex_error_log';
        
        $where_clauses = [];
        $params = [];
        
        // Apply filters
        if (!empty($filters['severity'])) {
            $where_clauses[] = 'severity = %s';
            $params[] = $filters['severity'];
        }
        
        if (!empty($filters['type'])) {
            $where_clauses[] = 'type = %s';
            $params[] = $filters['type'];
        }
        
        if (!empty($filters['since'])) {
            $where_clauses[] = 'timestamp >= %s';
            $params[] = $filters['since'];
        }
        
        $where_sql = !empty($where_clauses) ? 'WHERE ' . implode(' AND ', $where_clauses) : '';
        
        $limit = isset($filters['limit']) ? intval($filters['limit']) : 50;
        $offset = isset($filters['offset']) ? intval($filters['offset']) : 0;
        
        $query = "SELECT * FROM $table_name $where_sql ORDER BY timestamp DESC LIMIT %d OFFSET %d";
        $params[] = $limit;
        $params[] = $offset;
        
        if (!empty($params)) {
            $prepared_query = $wpdb->prepare($query, $params);
        } else {
            $prepared_query = $query;
        }
        
        $errors = $wpdb->get_results($prepared_query);
        
        // Decode JSON fields
        foreach ($errors as $error) {
            $error->trace = json_decode($error->trace, true);
            $error->context = json_decode($error->context, true);
        }
        
        return $errors;
    }
    
    public static function get_error_stats($days = 7) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'hex_error_log';
        
        $since = date('Y-m-d H:i:s', strtotime("-$days days"));
        
        $stats = [
            'total' => $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM $table_name WHERE timestamp >= %s",
                $since
            )),
            'by_severity' => [],
            'by_type' => [],
            'by_day' => []
        ];
        
        // By severity
        $severity_stats = $wpdb->get_results($wpdb->prepare(
            "SELECT severity, COUNT(*) as count FROM $table_name WHERE timestamp >= %s GROUP BY severity",
            $since
        ));
        
        foreach ($severity_stats as $stat) {
            $stats['by_severity'][$stat->severity] = intval($stat->count);
        }
        
        // By type
        $type_stats = $wpdb->get_results($wpdb->prepare(
            "SELECT type, COUNT(*) as count FROM $table_name WHERE timestamp >= %s GROUP BY type",
            $since
        ));
        
        foreach ($type_stats as $stat) {
            $stats['by_type'][$stat->type] = intval($stat->count);
        }
        
        // By day
        $daily_stats = $wpdb->get_results($wpdb->prepare(
            "SELECT DATE(timestamp) as date, COUNT(*) as count FROM $table_name WHERE timestamp >= %s GROUP BY DATE(timestamp) ORDER BY date",
            $since
        ));
        
        foreach ($daily_stats as $stat) {
            $stats['by_day'][$stat->date] = intval($stat->count);
        }
        
        return $stats;
    }
    
    private static function send_error_alert($error) {
        $admin_email = get_option('admin_email');
        $site_name = get_bloginfo('name');
        
        $subject = "[{$site_name}] Hexagon Automation Error Alert";
        
        $message = "A critical error has occurred in Hexagon Automation:\n\n";
        $message .= "Type: {$error['type']}\n";
        $message .= "Severity: {$error['severity']}\n";
        $message .= "Message: {$error['message']}\n";
        $message .= "File: {$error['file']}:{$error['line']}\n";
        $message .= "Time: {$error['timestamp']}\n\n";
        $message .= "Please check your dashboard for more details.";
        
        wp_mail($admin_email, $subject, $message);
    }
    
    public static function clear_errors($older_than_days = null) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'hex_error_log';
        
        if ($older_than_days) {
            $cutoff_date = date('Y-m-d H:i:s', strtotime("-$older_than_days days"));
            $result = $wpdb->query($wpdb->prepare(
                "DELETE FROM $table_name WHERE timestamp < %s",
                $cutoff_date
            ));
        } else {
            $result = $wpdb->query("TRUNCATE TABLE $table_name");
        }
        
        // Clear memory log
        self::$error_log = [];
        
        return $result;
    }
    
    public static function export_errors($format = 'json') {
        $errors = self::get_errors(['limit' => 1000]);
        
        switch ($format) {
            case 'csv':
                return self::export_errors_csv($errors);
            case 'json':
            default:
                return json_encode($errors, JSON_PRETTY_PRINT);
        }
    }
    
    private static function export_errors_csv($errors) {
        $csv = "ID,Timestamp,Type,Severity,Message,File,Line\n";
        
        foreach ($errors as $error) {
            $csv .= sprintf(
                '"%s","%s","%s","%s","%s","%s","%s"' . "\n",
                $error->error_id,
                $error->timestamp,
                $error->type,
                $error->severity,
                str_replace('"', '""', $error->message),
                $error->file,
                $error->line
            );
        }
        
        return $csv;
    }
    
    // AJAX handlers
    public static function ajax_get_errors() {
        check_ajax_referer('hexagon_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die('Insufficient permissions');
        }
        
        $filters = [
            'severity' => sanitize_text_field($_POST['severity'] ?? ''),
            'type' => sanitize_text_field($_POST['type'] ?? ''),
            'limit' => intval($_POST['limit'] ?? 50),
            'offset' => intval($_POST['offset'] ?? 0)
        ];
        
        $errors = self::get_errors($filters);
        $stats = self::get_error_stats();
        
        wp_send_json_success([
            'errors' => $errors,
            'stats' => $stats
        ]);
    }
    
    public static function ajax_clear_errors() {
        check_ajax_referer('hexagon_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die('Insufficient permissions');
        }
        
        $older_than = intval($_POST['older_than'] ?? 0);
        $result = self::clear_errors($older_than ?: null);
        
        wp_send_json_success(['cleared' => $result]);
    }
    
    public static function ajax_export_errors() {
        check_ajax_referer('hexagon_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die('Insufficient permissions');
        }
        
        $format = sanitize_text_field($_POST['format'] ?? 'json');
        $data = self::export_errors($format);
        
        $filename = 'hexagon-errors-' . date('Y-m-d-H-i-s') . '.' . $format;
        
        wp_send_json_success([
            'data' => $data,
            'filename' => $filename
        ]);
    }
}

// Convenience function for logging custom errors
function hexagon_log_error($message, $severity = 'error', $context = []) {
    Hexagon_Error_Handler::log_custom_error($message, $severity, $context);
}