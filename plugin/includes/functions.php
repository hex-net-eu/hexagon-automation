<?php
if (!defined('ABSPATH')) exit;
function hexagon_get_option($key, $default = '') {
    return get_option($key, $default);
}
function hexagon_log($context, $message) {
    global $wpdb;
    $table = $wpdb->prefix . 'hex_logs';
    $wpdb->insert($table, [
        'context' => sanitize_text_field($context),
        'message' => sanitize_text_field($message),
        'created_at' => current_time('mysql')
    ]);
}
