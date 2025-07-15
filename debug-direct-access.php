<?php
/**
 * Hexagon Debug - Direct Access
 * Bezpośredni dostęp do logów bez AJAX
 * URL: https://bizrun.eu/wp-content/plugins/hexagon-automation/debug-direct-access.php
 */

// Basic WordPress bootstrap
$wp_path = '../../../wp-load.php';
if (file_exists($wp_path)) {
    require_once $wp_path;
} else {
    die('WordPress not found');
}

// Security check - allow if debug key provided
if (!current_user_can('manage_options') && !isset($_GET['debug_key'])) {
    die('Access denied - add ?debug_key=hexagon to URL or login to WordPress admin');
}

header('Content-Type: text/plain; charset=utf-8');

echo "=== HEXAGON DEBUG EXPORT ===\n";
echo "Domain: " . home_url() . "\n";
echo "Date: " . current_time('mysql') . "\n";
echo "WordPress: " . get_bloginfo('version') . "\n";
echo "PHP: " . PHP_VERSION . "\n\n";

// 1. WordPress debug.log
echo "=== WORDPRESS DEBUG LOG ===\n";
$debug_log = WP_CONTENT_DIR . '/debug.log';
if (file_exists($debug_log)) {
    $content = file_get_contents($debug_log);
    // Ostatnie 100 linii
    $lines = explode("\n", $content);
    $recent_lines = array_slice($lines, -100);
    echo implode("\n", $recent_lines) . "\n\n";
} else {
    echo "Debug log not found at: $debug_log\n\n";
}

// 2. Hexagon database logs
echo "=== HEXAGON DATABASE LOGS ===\n";
global $wpdb;
$table_name = $wpdb->prefix . 'hex_logs';

if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") == $table_name) {
    $logs = $wpdb->get_results(
        "SELECT * FROM $table_name ORDER BY created_at DESC LIMIT 50"
    );
    
    if ($logs) {
        foreach ($logs as $log) {
            echo "[{$log->created_at}] [{$log->level}] {$log->action}: {$log->context}\n";
        }
    } else {
        echo "No logs in database\n";
    }
} else {
    echo "Hexagon logs table not found\n";
}

echo "\n=== PHP INFO ===\n";
echo "Memory Limit: " . ini_get('memory_limit') . "\n";
echo "Memory Usage: " . size_format(memory_get_usage(true)) . "\n";
echo "Max Execution Time: " . ini_get('max_execution_time') . "\n";
echo "Error Reporting: " . ini_get('error_reporting') . "\n";
echo "Display Errors: " . ini_get('display_errors') . "\n";

echo "\n=== SERVER INFO ===\n";
echo "Server: " . ($_SERVER['SERVER_SOFTWARE'] ?? 'Unknown') . "\n";
echo "User Agent: " . ($_SERVER['HTTP_USER_AGENT'] ?? 'Unknown') . "\n";

echo "\n=== ACTIVE PLUGINS ===\n";
$active_plugins = get_option('active_plugins', []);
foreach ($active_plugins as $plugin) {
    echo "- $plugin\n";
}

echo "\n=== HEXAGON PLUGIN STATUS ===\n";
$hexagon_files = [
    'hexagon-automation.php' => 'Main Plugin',
    'hexagon-automation-fixed.php' => 'Fixed Version', 
    'hexagon-automation-safe.php' => 'Safe Mode',
    'hexagon-automation-minimal.php' => 'Minimal Test'
];

foreach ($hexagon_files as $file => $desc) {
    $path = WP_PLUGIN_DIR . '/hexagon-automation/' . $file;
    if (file_exists($path)) {
        echo "✅ $desc: EXISTS\n";
    } else {
        echo "❌ $desc: NOT FOUND\n";
    }
}

echo "\n=== END DEBUG EXPORT ===\n";