<?php
if (!defined('ABSPATH')) exit;
function hexagon_get_option($key, $default = '') {
    return get_option($key, $default);
}
function hexagon_log($category, $message, $level = 'info', $context = []) {
    // Use the new logger if available, fallback to old method
    if (class_exists('Hexagon_Logger')) {
        return Hexagon_Logger::log($category, $message, $level, $context);
    }
    
    // Fallback to old logging method for backwards compatibility
    global $wpdb;
    $table = $wpdb->prefix . 'hex_logs';
    
    // Check if table exists
    $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table'");
    if (!$table_exists) {
        return false; // Don't log if table doesn't exist yet
    }
    
    $result = $wpdb->insert($table, [
        'log_id' => 'log_' . uniqid(),
        'level' => sanitize_text_field($level),
        'category' => sanitize_text_field($category),
        'message' => sanitize_textarea_field($message),
        'context' => is_array($context) ? json_encode($context) : sanitize_textarea_field($context),
        'user_id' => get_current_user_id(),
        'ip_address' => $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0',
        'created_at' => current_time('mysql')
    ]);
    
    // Trigger action for other modules
    if ($result !== false) {
        do_action('hexagon_log_entry', $category, $message, $level, $context);
    }
    
    return $result !== false ? $wpdb->insert_id : false;
}
