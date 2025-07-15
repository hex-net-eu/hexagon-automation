<?php
if (!defined('ABSPATH')) exit;

class Hexagon_Auth {
    
    private static $api_keys_table = 'hex_api_keys';
    private static $sessions_table = 'hex_sessions';
    
    public static function init() {
        add_action('wp_ajax_hexagon_auth_login', [__CLASS__, 'ajax_login']);
        add_action('wp_ajax_nopriv_hexagon_auth_login', [__CLASS__, 'ajax_login']);
        add_action('wp_ajax_hexagon_auth_logout', [__CLASS__, 'ajax_logout']);
        add_action('wp_ajax_hexagon_generate_api_key', [__CLASS__, 'ajax_generate_api_key']);
        add_action('wp_ajax_hexagon_revoke_api_key', [__CLASS__, 'ajax_revoke_api_key']);
        add_action('wp_ajax_hexagon_validate_session', [__CLASS__, 'ajax_validate_session']);
        
        // Add authentication middleware for API endpoints
        add_filter('rest_pre_dispatch', [__CLASS__, 'authenticate_rest_request'], 10, 3);
        
        // Schedule session cleanup
        if (!wp_next_scheduled('hexagon_cleanup_sessions')) {
            wp_schedule_event(time(), 'hourly', 'hexagon_cleanup_sessions');
        }
        add_action('hexagon_cleanup_sessions', [__CLASS__, 'cleanup_expired_sessions']);
    }
    
    public static function authenticate_user($username, $password, $remember_me = false) {
        // First try WordPress authentication
        $user = wp_authenticate($username, $password);
        
        if (is_wp_error($user)) {
            return new WP_Error('auth_failed', 'Invalid username or password');
        }
        
        // Check if user has required capabilities
        if (!user_can($user, 'manage_options')) {
            return new WP_Error('insufficient_permissions', 'User does not have required permissions');
        }
        
        // Check if 2FA is enabled for this user
        $two_fa_enabled = get_user_meta($user->ID, 'hexagon_2fa_enabled', true);
        if ($two_fa_enabled) {
            return new WP_Error('2fa_required', 'Two-factor authentication required');
        }
        
        // Create secure session
        $session_data = self::create_session($user->ID, $remember_me);
        
        if (is_wp_error($session_data)) {
            return $session_data;
        }
        
        // Log successful authentication
        hexagon_log('Authentication', "User {$user->user_login} logged in successfully", 'info');
        
        return [
            'success' => true,
            'user' => [
                'id' => $user->ID,
                'username' => $user->user_login,
                'email' => $user->user_email,
                'display_name' => $user->display_name,
                'capabilities' => array_keys($user->allcaps)
            ],
            'session' => $session_data,
            'redirect_url' => admin_url('admin.php?page=hexagon-dashboard')
        ];
    }
    
    public static function create_session($user_id, $remember_me = false) {
        global $wpdb;
        
        $table = $wpdb->prefix . self::$sessions_table;
        
        // Generate secure session token
        $session_token = self::generate_secure_token(64);
        $session_id = self::generate_secure_token(32);
        
        // Set expiration time
        $expires_at = $remember_me ? 
            current_time('mysql', 1) . ' + INTERVAL 30 DAY' : 
            current_time('mysql', 1) . ' + INTERVAL 24 HOUR';
        
        // Insert session into database
        $result = $wpdb->insert(
            $table,
            [
                'session_id' => $session_id,
                'session_token' => wp_hash_password($session_token),
                'user_id' => $user_id,
                'ip_address' => self::get_client_ip(),
                'user_agent' => substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 255),
                'created_at' => current_time('mysql'),
                'expires_at' => $expires_at,
                'is_active' => 1
            ],
            ['%s', '%s', '%d', '%s', '%s', '%s', '%s', '%d']
        );
        
        if ($result === false) {
            return new WP_Error('session_creation_failed', 'Failed to create session');
        }
        
        return [
            'session_id' => $session_id,
            'session_token' => $session_token,
            'expires_at' => $expires_at
        ];
    }
    
    public static function validate_session($session_id, $session_token) {
        global $wpdb;
        
        $table = $wpdb->prefix . self::$sessions_table;
        
        // Get session from database
        $session = $wpdb->get_row($wpdb->prepare("
            SELECT s.*, u.user_login, u.user_email, u.display_name
            FROM {$table} s
            JOIN {$wpdb->users} u ON s.user_id = u.ID
            WHERE s.session_id = %s 
            AND s.is_active = 1 
            AND s.expires_at > %s
        ", $session_id, current_time('mysql')), ARRAY_A);
        
        if (!$session) {
            return new WP_Error('invalid_session', 'Session not found or expired');
        }
        
        // Verify session token
        if (!wp_check_password($session_token, $session['session_token'])) {
            return new WP_Error('invalid_token', 'Invalid session token');
        }
        
        // Check IP address if enabled
        $ip_validation = get_option('hexagon_ip_validation', false);
        if ($ip_validation && $session['ip_address'] !== self::get_client_ip()) {
            return new WP_Error('ip_mismatch', 'IP address mismatch');
        }
        
        // Update last activity
        $wpdb->update(
            $table,
            ['last_activity' => current_time('mysql')],
            ['session_id' => $session_id],
            ['%s'],
            ['%s']
        );
        
        return [
            'valid' => true,
            'user_id' => $session['user_id'],
            'username' => $session['user_login'],
            'email' => $session['user_email'],
            'display_name' => $session['display_name']
        ];
    }
    
    public static function invalidate_session($session_id) {
        global $wpdb;
        
        $table = $wpdb->prefix . self::$sessions_table;
        
        $result = $wpdb->update(
            $table,
            ['is_active' => 0, 'invalidated_at' => current_time('mysql')],
            ['session_id' => $session_id],
            ['%d', '%s'],
            ['%s']
        );
        
        return $result !== false;
    }
    
    public static function generate_api_key($user_id, $name, $permissions = []) {
        global $wpdb;
        
        $table = $wpdb->prefix . self::$api_keys_table;
        
        // Generate secure API key
        $api_key = 'hex_' . self::generate_secure_token(32);
        $api_secret = self::generate_secure_token(64);
        
        // Default permissions if none provided
        if (empty($permissions)) {
            $permissions = ['read', 'write', 'manage_content', 'manage_social'];
        }
        
        $result = $wpdb->insert(
            $table,
            [
                'api_key' => $api_key,
                'api_secret' => wp_hash_password($api_secret),
                'user_id' => $user_id,
                'name' => $name,
                'permissions' => json_encode($permissions),
                'is_active' => 1,
                'created_at' => current_time('mysql'),
                'last_used' => null,
                'usage_count' => 0
            ],
            ['%s', '%s', '%d', '%s', '%s', '%d', '%s', '%s', '%d']
        );
        
        if ($result === false) {
            return new WP_Error('api_key_creation_failed', 'Failed to create API key');
        }
        
        hexagon_log('API Key', "API key '{$name}' created for user ID {$user_id}", 'info');
        
        return [
            'api_key' => $api_key,
            'api_secret' => $api_secret,
            'name' => $name,
            'permissions' => $permissions,
            'created_at' => current_time('mysql')
        ];
    }
    
    public static function validate_api_key($api_key, $api_secret = null) {
        global $wpdb;
        
        $table = $wpdb->prefix . self::$api_keys_table;
        
        // Get API key from database
        $key_data = $wpdb->get_row($wpdb->prepare("
            SELECT k.*, u.user_login, u.user_email
            FROM {$table} k
            JOIN {$wpdb->users} u ON k.user_id = u.ID
            WHERE k.api_key = %s AND k.is_active = 1
        ", $api_key), ARRAY_A);
        
        if (!$key_data) {
            return new WP_Error('invalid_api_key', 'Invalid or inactive API key');
        }
        
        // Verify API secret if provided
        if ($api_secret && !wp_check_password($api_secret, $key_data['api_secret'])) {
            return new WP_Error('invalid_api_secret', 'Invalid API secret');
        }
        
        // Update usage statistics
        $wpdb->update(
            $table,
            [
                'last_used' => current_time('mysql'),
                'usage_count' => $key_data['usage_count'] + 1
            ],
            ['api_key' => $api_key],
            ['%s', '%d'],
            ['%s']
        );
        
        return [
            'valid' => true,
            'user_id' => $key_data['user_id'],
            'permissions' => json_decode($key_data['permissions'], true),
            'name' => $key_data['name']
        ];
    }
    
    public static function authenticate_rest_request($result, $server, $request) {
        $route = $request->get_route();
        
        // Only authenticate Hexagon API endpoints
        if (strpos($route, '/hexagon/v1/') === false) {
            return $result;
        }
        
        // Allow public endpoints
        $public_endpoints = [
            '/hexagon/v1/auth',
            '/hexagon/v1/status'
        ];
        
        if (in_array($route, $public_endpoints)) {
            return $result;
        }
        
        // Check for session-based authentication
        $session_id = $request->get_header('X-Session-ID');
        $session_token = $request->get_header('X-Session-Token');
        
        if ($session_id && $session_token) {
            $validation = self::validate_session($session_id, $session_token);
            if (!is_wp_error($validation)) {
                // Set current user for WordPress
                wp_set_current_user($validation['user_id']);
                return $result;
            }
        }
        
        // Check for API key authentication
        $api_key = $request->get_header('X-API-Key');
        $api_secret = $request->get_header('X-API-Secret');
        
        if ($api_key) {
            $validation = self::validate_api_key($api_key, $api_secret);
            if (!is_wp_error($validation)) {
                // Set current user for WordPress
                wp_set_current_user($validation['user_id']);
                return $result;
            }
        }
        
        // No valid authentication found
        return new WP_Error(
            'unauthorized',
            'Authentication required',
            ['status' => 401]
        );
    }
    
    public static function cleanup_expired_sessions() {
        global $wpdb;
        
        $table = $wpdb->prefix . self::$sessions_table;
        
        // Delete expired sessions
        $deleted = $wpdb->delete(
            $table,
            ['expires_at <' => current_time('mysql')],
            ['%s']
        );
        
        // Delete old inactive sessions (older than 7 days)
        $wpdb->delete(
            $table,
            [
                'is_active' => 0,
                'invalidated_at <' => date('Y-m-d H:i:s', strtotime('-7 days'))
            ],
            ['%d', '%s']
        );
        
        if ($deleted > 0) {
            hexagon_log('Session Cleanup', "Cleaned up {$deleted} expired sessions", 'info');
        }
    }
    
    private static function generate_secure_token($length = 32) {
        if (function_exists('random_bytes')) {
            return bin2hex(random_bytes($length / 2));
        } elseif (function_exists('openssl_random_pseudo_bytes')) {
            return bin2hex(openssl_random_pseudo_bytes($length / 2));
        } else {
            // Fallback for older PHP versions
            return substr(str_shuffle(str_repeat('0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ', $length)), 0, $length);
        }
    }
    
    private static function get_client_ip() {
        $ip_headers = ['HTTP_X_FORWARDED_FOR', 'HTTP_X_REAL_IP', 'HTTP_CLIENT_IP', 'REMOTE_ADDR'];
        
        foreach ($ip_headers as $header) {
            if (isset($_SERVER[$header]) && !empty($_SERVER[$header])) {
                $ip = $_SERVER[$header];
                // Handle comma-separated IPs (from proxies)
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
    
    // AJAX Handlers
    public static function ajax_login() {
        check_ajax_referer('hexagon_nonce', 'nonce');
        
        $username = sanitize_text_field($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';
        $remember_me = isset($_POST['remember_me']) && $_POST['remember_me'] === 'true';
        
        if (empty($username) || empty($password)) {
            wp_send_json_error('Username and password are required');
            return;
        }
        
        $result = self::authenticate_user($username, $password, $remember_me);
        
        if (is_wp_error($result)) {
            wp_send_json_error($result->get_error_message());
        } else {
            wp_send_json_success($result);
        }
    }
    
    public static function ajax_logout() {
        check_ajax_referer('hexagon_nonce', 'nonce');
        
        $session_id = sanitize_text_field($_POST['session_id'] ?? '');
        
        if ($session_id) {
            self::invalidate_session($session_id);
        }
        
        wp_send_json_success('Logged out successfully');
    }
    
    public static function ajax_generate_api_key() {
        check_ajax_referer('hexagon_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
            return;
        }
        
        $name = sanitize_text_field($_POST['name'] ?? '');
        $permissions = array_map('sanitize_text_field', $_POST['permissions'] ?? []);
        
        if (empty($name)) {
            wp_send_json_error('API key name is required');
            return;
        }
        
        $result = self::generate_api_key(get_current_user_id(), $name, $permissions);
        
        if (is_wp_error($result)) {
            wp_send_json_error($result->get_error_message());
        } else {
            wp_send_json_success($result);
        }
    }
    
    public static function ajax_revoke_api_key() {
        check_ajax_referer('hexagon_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
            return;
        }
        
        global $wpdb;
        $table = $wpdb->prefix . self::$api_keys_table;
        
        $api_key = sanitize_text_field($_POST['api_key'] ?? '');
        
        $result = $wpdb->update(
            $table,
            ['is_active' => 0, 'revoked_at' => current_time('mysql')],
            ['api_key' => $api_key, 'user_id' => get_current_user_id()],
            ['%d', '%s'],
            ['%s', '%d']
        );
        
        if ($result === false) {
            wp_send_json_error('Failed to revoke API key');
        } else {
            wp_send_json_success('API key revoked successfully');
        }
    }
    
    public static function ajax_validate_session() {
        $session_id = sanitize_text_field($_POST['session_id'] ?? '');
        $session_token = sanitize_text_field($_POST['session_token'] ?? '');
        
        $result = self::validate_session($session_id, $session_token);
        
        if (is_wp_error($result)) {
            wp_send_json_error($result->get_error_message());
        } else {
            wp_send_json_success($result);
        }
    }
}