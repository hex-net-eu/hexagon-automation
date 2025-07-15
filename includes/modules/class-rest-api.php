<?php
if (!defined('ABSPATH')) exit;

class Hexagon_Rest_Api {
    
    const API_NAMESPACE = 'hexagon/v1';
    
    public static function init() {
        add_action('rest_api_init', [__CLASS__, 'register_routes']);
        add_filter('rest_authentication_errors', [__CLASS__, 'authenticate_request']);
    }
    
    public static function register_routes() {
        // AI endpoints
        register_rest_route(self::API_NAMESPACE, '/ai/generate', [
            'methods' => 'POST',
            'callback' => [__CLASS__, 'handle_ai_generate'],
            'permission_callback' => [__CLASS__, 'check_permissions'],
            'args' => [
                'provider' => ['required' => true, 'type' => 'string'],
                'content_type' => ['required' => true, 'type' => 'string'],
                'prompt' => ['required' => true, 'type' => 'string'],
                'language' => ['default' => 'pl', 'type' => 'string']
            ]
        ]);
        
        register_rest_route(self::API_NAMESPACE, '/ai/test', [
            'methods' => 'POST',
            'callback' => [__CLASS__, 'handle_ai_test'],
            'permission_callback' => [__CLASS__, 'check_permissions'],
            'args' => [
                'provider' => ['required' => true, 'type' => 'string']
            ]
        ]);
        
        register_rest_route(self::API_NAMESPACE, '/ai/stats', [
            'methods' => 'GET',
            'callback' => [__CLASS__, 'get_ai_stats'],
            'permission_callback' => [__CLASS__, 'check_permissions']
        ]);
        
        // Social Media endpoints
        register_rest_route(self::API_NAMESPACE, '/social/post', [
            'methods' => 'POST',
            'callback' => [__CLASS__, 'handle_social_post'],
            'permission_callback' => [__CLASS__, 'check_permissions'],
            'args' => [
                'platform' => ['required' => true, 'type' => 'string'],
                'message' => ['required' => true, 'type' => 'string'],
                'image_url' => ['type' => 'string'],
                'link_url' => ['type' => 'string']
            ]
        ]);
        
        register_rest_route(self::API_NAMESPACE, '/social/schedule', [
            'methods' => 'POST',
            'callback' => [__CLASS__, 'handle_social_schedule'],
            'permission_callback' => [__CLASS__, 'check_permissions'],
            'args' => [
                'platform' => ['required' => true, 'type' => 'string'],
                'message' => ['required' => true, 'type' => 'string'],
                'schedule_time' => ['required' => true, 'type' => 'string'],
                'image_url' => ['type' => 'string'],
                'link_url' => ['type' => 'string']
            ]
        ]);
        
        register_rest_route(self::API_NAMESPACE, '/social/test', [
            'methods' => 'POST',
            'callback' => [__CLASS__, 'handle_social_test'],
            'permission_callback' => [__CLASS__, 'check_permissions'],
            'args' => [
                'platform' => ['required' => true, 'type' => 'string']
            ]
        ]);
        
        register_rest_route(self::API_NAMESPACE, '/social/stats', [
            'methods' => 'GET',
            'callback' => [__CLASS__, 'get_social_stats'],
            'permission_callback' => [__CLASS__, 'check_permissions']
        ]);
        
        // Email endpoints
        register_rest_route(self::API_NAMESPACE, '/email/test', [
            'methods' => 'POST',
            'callback' => [__CLASS__, 'handle_email_test'],
            'permission_callback' => [__CLASS__, 'check_permissions'],
            'args' => [
                'test_email' => ['required' => true, 'type' => 'string']
            ]
        ]);
        
        register_rest_route(self::API_NAMESPACE, '/email/send', [
            'methods' => 'POST',
            'callback' => [__CLASS__, 'handle_email_send'],
            'permission_callback' => [__CLASS__, 'check_permissions'],
            'args' => [
                'to' => ['required' => true, 'type' => 'string'],
                'subject' => ['required' => true, 'type' => 'string'],
                'message' => ['required' => true, 'type' => 'string']
            ]
        ]);
        
        // Settings endpoints
        register_rest_route(self::API_NAMESPACE, '/settings', [
            'methods' => 'GET',
            'callback' => [__CLASS__, 'get_settings'],
            'permission_callback' => [__CLASS__, 'check_permissions']
        ]);
        
        register_rest_route(self::API_NAMESPACE, '/settings', [
            'methods' => 'POST',
            'callback' => [__CLASS__, 'update_settings'],
            'permission_callback' => [__CLASS__, 'check_permissions'],
            'args' => [
                'settings' => ['required' => true, 'type' => 'object']
            ]
        ]);
        
        // Logs endpoints
        register_rest_route(self::API_NAMESPACE, '/logs', [
            'methods' => 'GET',
            'callback' => [__CLASS__, 'get_logs'],
            'permission_callback' => [__CLASS__, 'check_permissions'],
            'args' => [
                'level' => ['type' => 'string'],
                'limit' => ['default' => 50, 'type' => 'integer'],
                'offset' => ['default' => 0, 'type' => 'integer']
            ]
        ]);
        
        register_rest_route(self::API_NAMESPACE, '/logs/clear', [
            'methods' => 'DELETE',
            'callback' => [__CLASS__, 'clear_logs'],
            'permission_callback' => [__CLASS__, 'check_permissions']
        ]);
        
        // System status endpoint
        register_rest_route(self::API_NAMESPACE, '/status', [
            'methods' => 'GET',
            'callback' => [__CLASS__, 'get_system_status'],
            'permission_callback' => [__CLASS__, 'check_permissions']
        ]);
        
        // Dashboard data endpoint
        register_rest_route(self::API_NAMESPACE, '/dashboard', [
            'methods' => 'GET',
            'callback' => [__CLASS__, 'get_dashboard_data'],
            'permission_callback' => [__CLASS__, 'check_permissions']
        ]);
        
        // Authentication endpoint for dashboard
        register_rest_route(self::API_NAMESPACE, '/auth', [
            'methods' => 'POST',
            'callback' => [__CLASS__, 'authenticate_dashboard'],
            'permission_callback' => '__return_true',
            'args' => [
                'username' => ['required' => true, 'type' => 'string'],
                'password' => ['required' => true, 'type' => 'string']
            ]
        ]);
        
        // Image generation endpoints
        register_rest_route(self::API_NAMESPACE, '/image/generate', [
            'methods' => 'POST',
            'callback' => [__CLASS__, 'handle_image_generate'],
            'permission_callback' => [__CLASS__, 'check_permissions'],
            'args' => [
                'provider' => ['required' => true, 'type' => 'string'],
                'prompt' => ['required' => true, 'type' => 'string'],
                'size' => ['default' => '1024x1024', 'type' => 'string'],
                'style' => ['default' => 'photorealistic', 'type' => 'string'],
                'quality' => ['default' => 'standard', 'type' => 'string']
            ]
        ]);
        
        register_rest_route(self::API_NAMESPACE, '/image/gallery', [
            'methods' => 'GET',
            'callback' => [__CLASS__, 'get_image_gallery'],
            'permission_callback' => [__CLASS__, 'check_permissions'],
            'args' => [
                'limit' => ['default' => 20, 'type' => 'integer'],
                'offset' => ['default' => 0, 'type' => 'integer']
            ]
        ]);
        
        register_rest_route(self::API_NAMESPACE, '/image/(?P<id>\d+)', [
            'methods' => 'DELETE',
            'callback' => [__CLASS__, 'delete_generated_image'],
            'permission_callback' => [__CLASS__, 'check_permissions']
        ]);
        
        // Image providers status
        register_rest_route(self::API_NAMESPACE, '/image/providers', [
            'methods' => 'GET',
            'callback' => [__CLASS__, 'get_image_providers_status'],
            'permission_callback' => [__CLASS__, 'check_permissions']
        ]);
    }
    
    public static function check_permissions($request) {
        // Check if user is authenticated
        if (!is_user_logged_in()) {
            // Check for API key authentication
            $api_key = $request->get_header('X-API-Key');
            $stored_api_key = hexagon_get_option('hexagon_api_key');
            
            if (empty($api_key) || $api_key !== $stored_api_key) {
                return new WP_Error('rest_forbidden', 'Authentication required', ['status' => 401]);
            }
        }
        
        // Check user capabilities
        if (!current_user_can('manage_options')) {
            return new WP_Error('rest_forbidden', 'Insufficient permissions', ['status' => 403]);
        }
        
        return true;
    }
    
    public static function authenticate_request($result) {
        // Skip if already authenticated
        if (!empty($result)) {
            return $result;
        }
        
        // Check for API key in header
        $api_key = $_SERVER['HTTP_X_API_KEY'] ?? '';
        $stored_api_key = hexagon_get_option('hexagon_api_key');
        
        if (!empty($api_key) && $api_key === $stored_api_key) {
            // Set a dummy user for API authentication
            wp_set_current_user(1); // Admin user
            return true;
        }
        
        return $result;
    }
    
    public static function handle_ai_generate($request) {
        try {
            $params = $request->get_params();
            
            $result = Hexagon_Hexagon_Ai_Manager::generate_content(
                $params['provider'],
                $params['content_type'],
                $params['prompt'],
                $params['language']
            );
            
            // Update usage stats
            if (isset($result['usage'])) {
                Hexagon_Hexagon_Ai_Manager::update_usage_stats($params['provider'], $result['usage']);
            }
            
            return rest_ensure_response($result);
            
        } catch (Exception $e) {
            return new WP_Error('ai_error', $e->getMessage(), ['status' => 500]);
        }
    }
    
    public static function handle_ai_test($request) {
        try {
            $provider = $request->get_param('provider');
            
            $result = Hexagon_Hexagon_Ai_Manager::generate_content(
                $provider,
                'summary',
                'Test connection',
                'en'
            );
            
            return rest_ensure_response([
                'success' => true,
                'message' => "Connection to $provider successful",
                'provider' => $provider
            ]);
            
        } catch (Exception $e) {
            return new WP_Error('ai_test_error', $e->getMessage(), ['status' => 500]);
        }
    }
    
    public static function get_ai_stats($request) {
        $stats = Hexagon_Hexagon_Ai_Manager::get_usage_stats();
        return rest_ensure_response($stats);
    }
    
    public static function handle_social_post($request) {
        try {
            $params = $request->get_params();
            
            $result = Hexagon_Social_Integration::post_to_platform(
                $params['platform'],
                $params['message'],
                $params['image_url'] ?? '',
                $params['link_url'] ?? ''
            );
            
            return rest_ensure_response($result);
            
        } catch (Exception $e) {
            return new WP_Error('social_error', $e->getMessage(), ['status' => 500]);
        }
    }
    
    public static function handle_social_schedule($request) {
        try {
            $params = $request->get_params();
            
            $timestamp = strtotime($params['schedule_time']);
            if ($timestamp < time()) {
                return new WP_Error('invalid_time', 'Schedule time must be in the future', ['status' => 400]);
            }
            
            $post_data = [
                'platform' => $params['platform'],
                'message' => $params['message'],
                'image_url' => $params['image_url'] ?? '',
                'link_url' => $params['link_url'] ?? '',
                'schedule_time' => $params['schedule_time']
            ];
            
            wp_schedule_single_event($timestamp, 'hexagon_social_post_scheduled', [$post_data]);
            
            hexagon_log('Social Media Scheduled', "Scheduled post for {$params['platform']} at {$params['schedule_time']}", 'info');
            
            return rest_ensure_response([
                'success' => true,
                'message' => 'Post scheduled successfully',
                'scheduled_time' => $params['schedule_time']
            ]);
            
        } catch (Exception $e) {
            return new WP_Error('schedule_error', $e->getMessage(), ['status' => 500]);
        }
    }
    
    public static function handle_social_test($request) {
        try {
            $platform = $request->get_param('platform');
            $test_message = 'Test post from Hexagon Automation - ' . current_time('mysql');
            
            $result = Hexagon_Social_Integration::post_to_platform($platform, $test_message);
            
            return rest_ensure_response([
                'success' => true,
                'message' => "Connection to $platform successful",
                'platform' => $platform
            ]);
            
        } catch (Exception $e) {
            return new WP_Error('social_test_error', $e->getMessage(), ['status' => 500]);
        }
    }
    
    public static function get_social_stats($request) {
        $stats = Hexagon_Social_Integration::get_social_stats();
        return rest_ensure_response($stats);
    }
    
    public static function handle_email_test($request) {
        try {
            $test_email = $request->get_param('test_email');
            
            $subject = 'Hexagon Automation - REST API Test Email';
            $message = '<h2>REST API Test Successful</h2>';
            $message .= '<p>This email was sent via the Hexagon Automation REST API.</p>';
            $message .= '<p>Time: ' . current_time('mysql') . '</p>';
            
            $result = Hexagon_Email_Integration::send_email($test_email, $subject, $message);
            
            if ($result) {
                return rest_ensure_response(['success' => true, 'message' => 'Test email sent successfully']);
            } else {
                return new WP_Error('email_failed', 'Failed to send test email', ['status' => 500]);
            }
            
        } catch (Exception $e) {
            return new WP_Error('email_error', $e->getMessage(), ['status' => 500]);
        }
    }
    
    public static function handle_email_send($request) {
        try {
            $params = $request->get_params();
            
            $result = Hexagon_Email_Integration::send_email(
                $params['to'],
                $params['subject'],
                $params['message']
            );
            
            if ($result) {
                return rest_ensure_response(['success' => true, 'message' => 'Email sent successfully']);
            } else {
                return new WP_Error('email_failed', 'Failed to send email', ['status' => 500]);
            }
            
        } catch (Exception $e) {
            return new WP_Error('email_error', $e->getMessage(), ['status' => 500]);
        }
    }
    
    public static function get_settings($request) {
        $settings = [
            'ai' => [
                'chatgpt_api_key' => hexagon_get_option('hexagon_ai_chatgpt_api_key', ''),
                'chatgpt_model' => hexagon_get_option('hexagon_ai_chatgpt_model', 'gpt-4'),
                'chatgpt_temperature' => hexagon_get_option('hexagon_ai_chatgpt_temperature', 0.7),
                'chatgpt_max_tokens' => hexagon_get_option('hexagon_ai_chatgpt_max_tokens', 2000),
                'claude_api_key' => hexagon_get_option('hexagon_ai_claude_api_key', ''),
                'claude_model' => hexagon_get_option('hexagon_ai_claude_model', 'claude-3-sonnet-20240229'),
                'claude_max_tokens' => hexagon_get_option('hexagon_ai_claude_max_tokens', 2000),
                'perplexity_api_key' => hexagon_get_option('hexagon_ai_perplexity_api_key', ''),
                'perplexity_model' => hexagon_get_option('hexagon_ai_perplexity_model', 'llama-3.1-sonar-small-128k-online'),
                'perplexity_temperature' => hexagon_get_option('hexagon_ai_perplexity_temperature', 0.2),
                'perplexity_max_tokens' => hexagon_get_option('hexagon_ai_perplexity_max_tokens', 2000)
            ],
            'email' => [
                'use_smtp' => hexagon_get_option('hexagon_email_use_smtp', false),
                'smtp_host' => hexagon_get_option('hexagon_email_smtp_host', ''),
                'smtp_port' => hexagon_get_option('hexagon_email_smtp_port', 587),
                'smtp_username' => hexagon_get_option('hexagon_email_smtp_username', ''),
                'smtp_encryption' => hexagon_get_option('hexagon_email_smtp_encryption', 'tls'),
                'daily_digest' => hexagon_get_option('hexagon_email_daily_digest', false),
                'error_alerts' => hexagon_get_option('hexagon_email_error_alerts', true)
            ],
            'social' => [
                'auto_post' => hexagon_get_option('hexagon_social_auto_post', false),
                'auto_platforms' => hexagon_get_option('hexagon_social_auto_platforms', []),
                'post_template' => hexagon_get_option('hexagon_social_post_template', '{title} {excerpt}'),
                'facebook_token' => hexagon_get_option('hexagon_social_facebook_token', ''),
                'facebook_page_id' => hexagon_get_option('hexagon_social_facebook_page_id', ''),
                'instagram_token' => hexagon_get_option('hexagon_social_instagram_token', ''),
                'instagram_account_id' => hexagon_get_option('hexagon_social_instagram_account_id', ''),
                'twitter_api_key' => hexagon_get_option('hexagon_social_twitter_api_key', ''),
                'linkedin_token' => hexagon_get_option('hexagon_social_linkedin_token', ''),
                'linkedin_person_id' => hexagon_get_option('hexagon_social_linkedin_person_id', '')
            ]
        ];
        
        return rest_ensure_response($settings);
    }
    
    public static function update_settings($request) {
        try {
            $settings = $request->get_param('settings');
            
            foreach ($settings as $category => $options) {
                foreach ($options as $key => $value) {
                    $option_name = "hexagon_{$category}_{$key}";
                    update_option($option_name, $value);
                }
            }
            
            hexagon_log('Settings Updated', 'Settings updated via REST API', 'info');
            
            return rest_ensure_response([
                'success' => true,
                'message' => 'Settings updated successfully'
            ]);
            
        } catch (Exception $e) {
            return new WP_Error('settings_error', $e->getMessage(), ['status' => 500]);
        }
    }
    
    public static function get_logs($request) {
        global $wpdb;
        
        $level = $request->get_param('level');
        $limit = $request->get_param('limit');
        $offset = $request->get_param('offset');
        
        $table_name = $wpdb->prefix . 'hex_logs';
        
        $where_clause = '';
        $params = [];
        
        if ($level) {
            $where_clause = 'WHERE level = %s';
            $params[] = $level;
        }
        
        $query = "SELECT * FROM $table_name $where_clause ORDER BY created_at DESC LIMIT %d OFFSET %d";
        $params[] = $limit;
        $params[] = $offset;
        
        $logs = $wpdb->get_results($wpdb->prepare($query, $params));
        
        // Get total count
        $count_query = "SELECT COUNT(*) FROM $table_name $where_clause";
        $total = $wpdb->get_var($level ? $wpdb->prepare($count_query, $level) : $count_query);
        
        return rest_ensure_response([
            'logs' => $logs,
            'total' => intval($total),
            'limit' => $limit,
            'offset' => $offset
        ]);
    }
    
    public static function clear_logs($request) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'hex_logs';
        $result = $wpdb->query("TRUNCATE TABLE $table_name");
        
        if ($result !== false) {
            hexagon_log('Logs Cleared', 'All logs cleared via REST API', 'info');
            return rest_ensure_response(['success' => true, 'message' => 'Logs cleared successfully']);
        } else {
            return new WP_Error('clear_logs_error', 'Failed to clear logs', ['status' => 500]);
        }
    }
    
    public static function get_system_status($request) {
        $status = [
            'wordpress_version' => get_bloginfo('version'),
            'php_version' => PHP_VERSION,
            'plugin_version' => HEXAGON_AUTOMATION_VERSION ?? '3.0.0',
            'memory_limit' => ini_get('memory_limit'),
            'max_execution_time' => ini_get('max_execution_time'),
            'upload_max_filesize' => ini_get('upload_max_filesize'),
            'post_max_size' => ini_get('post_max_size'),
            'database_version' => $GLOBALS['wpdb']->db_version(),
            'active_plugins' => count(get_option('active_plugins', [])),
            'theme' => get_option('template'),
            'multisite' => is_multisite(),
            'site_url' => site_url(),
            'home_url' => home_url(),
            'admin_email' => get_option('admin_email'),
            'timezone' => get_option('timezone_string') ?: 'UTC',
            'date_format' => get_option('date_format'),
            'time_format' => get_option('time_format'),
            'start_of_week' => get_option('start_of_week'),
            'language' => get_locale()
        ];
        
        // Check module status
        $status['modules'] = [
            'ai_manager' => class_exists('Hexagon_Hexagon_Ai_Manager'),
            'email_integration' => class_exists('Hexagon_Email_Integration'),
            'social_integration' => class_exists('Hexagon_Social_Integration'),
            'rest_api' => class_exists('Hexagon_Rest_Api')
        ];
        
        // Check API connectivity
        $status['api_status'] = [
            'chatgpt' => !empty(hexagon_get_option('hexagon_ai_chatgpt_api_key')),
            'claude' => !empty(hexagon_get_option('hexagon_ai_claude_api_key')),
            'perplexity' => !empty(hexagon_get_option('hexagon_ai_perplexity_api_key')),
            'facebook' => !empty(hexagon_get_option('hexagon_social_facebook_token')),
            'instagram' => !empty(hexagon_get_option('hexagon_social_instagram_token')),
            'twitter' => !empty(hexagon_get_option('hexagon_social_twitter_api_key')),
            'linkedin' => !empty(hexagon_get_option('hexagon_social_linkedin_token'))
        ];
        
        return rest_ensure_response($status);
    }
    
    public static function get_dashboard_data($request) {
        $ai_stats = Hexagon_Hexagon_Ai_Manager::get_usage_stats();
        $social_stats = Hexagon_Social_Integration::get_social_stats();
        
        // Recent logs
        global $wpdb;
        $table_name = $wpdb->prefix . 'hex_logs';
        $recent_logs = $wpdb->get_results(
            "SELECT * FROM $table_name ORDER BY created_at DESC LIMIT 10"
        );
        
        // Error count last 24h
        $yesterday = date('Y-m-d H:i:s', strtotime('-24 hours'));
        $error_count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table_name WHERE level = 'error' AND created_at >= %s",
            $yesterday
        ));
        
        $dashboard_data = [
            'ai_stats' => $ai_stats,
            'social_stats' => $social_stats,
            'recent_logs' => $recent_logs,
            'error_count_24h' => intval($error_count),
            'system_health' => [
                'uptime' => $this->get_uptime(),
                'memory_usage' => $this->get_memory_usage(),
                'database_size' => $this->get_database_size()
            ]
        ];
        
        return rest_ensure_response($dashboard_data);
    }
    
    public static function authenticate_dashboard($request) {
        $username = $request->get_param('username');
        $password = $request->get_param('password');
        
        $user = wp_authenticate($username, $password);
        
        if (is_wp_error($user)) {
            return new WP_Error('auth_failed', 'Invalid credentials', ['status' => 401]);
        }
        
        if (!user_can($user, 'manage_options')) {
            return new WP_Error('insufficient_permissions', 'User does not have sufficient permissions', ['status' => 403]);
        }
        
        // Generate API key for dashboard
        $api_key = wp_generate_password(32, false);
        update_option('hexagon_api_key', $api_key);
        
        return rest_ensure_response([
            'success' => true,
            'api_key' => $api_key,
            'user' => [
                'id' => $user->ID,
                'username' => $user->user_login,
                'email' => $user->user_email,
                'display_name' => $user->display_name
            ]
        ]);
    }
    
    public static function handle_image_generate($request) {
        try {
            $params = $request->get_params();
            
            // For now, simulate image generation (implement actual providers later)
            $image_data = [
                'id' => time(),
                'prompt' => $params['prompt'],
                'provider' => $params['provider'],
                'size' => $params['size'],
                'style' => $params['style'],
                'quality' => $params['quality'],
                'url' => 'https://picsum.photos/1024/1024', // Placeholder
                'created_at' => current_time('mysql'),
                'status' => 'completed'
            ];
            
            // Store in database
            global $wpdb;
            $table_name = $wpdb->prefix . 'hex_generated_images';
            $wpdb->insert($table_name, $image_data);
            
            hexagon_log('Image Generated', "Generated image with prompt: {$params['prompt']}", 'info');
            
            return rest_ensure_response([
                'success' => true,
                'image' => $image_data,
                'message' => 'Image generated successfully'
            ]);
            
        } catch (Exception $e) {
            return new WP_Error('image_generation_error', $e->getMessage(), ['status' => 500]);
        }
    }
    
    public static function get_image_gallery($request) {
        global $wpdb;
        
        $limit = $request->get_param('limit');
        $offset = $request->get_param('offset');
        
        $table_name = $wpdb->prefix . 'hex_generated_images';
        
        $images = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $table_name ORDER BY created_at DESC LIMIT %d OFFSET %d",
            $limit, $offset
        ));
        
        $total = $wpdb->get_var("SELECT COUNT(*) FROM $table_name");
        
        return rest_ensure_response([
            'images' => $images,
            'total' => intval($total),
            'limit' => $limit,
            'offset' => $offset
        ]);
    }
    
    public static function delete_generated_image($request) {
        global $wpdb;
        
        $image_id = $request->get_param('id');
        $table_name = $wpdb->prefix . 'hex_generated_images';
        
        $result = $wpdb->delete($table_name, ['id' => $image_id]);
        
        if ($result) {
            return rest_ensure_response([
                'success' => true,
                'message' => 'Image deleted successfully'
            ]);
        } else {
            return new WP_Error('delete_failed', 'Failed to delete image', ['status' => 500]);
        }
    }
    
    public static function get_image_providers_status($request) {
        $providers = [
            'dalle' => [
                'enabled' => !empty(hexagon_get_option('hexagon_image_dalle_api_key')),
                'status' => !empty(hexagon_get_option('hexagon_image_dalle_api_key')) ? 'connected' : 'disconnected',
                'model' => hexagon_get_option('hexagon_image_dalle_model', 'dall-e-3'),
                'usage' => hexagon_get_option('hexagon_image_dalle_usage', 0),
                'monthly_limit' => hexagon_get_option('hexagon_image_dalle_limit', 1000)
            ],
            'midjourney' => [
                'enabled' => !empty(hexagon_get_option('hexagon_image_midjourney_api_key')),
                'status' => !empty(hexagon_get_option('hexagon_image_midjourney_api_key')) ? 'connected' : 'disconnected',
                'model' => hexagon_get_option('hexagon_image_midjourney_model', 'midjourney-v6'),
                'usage' => hexagon_get_option('hexagon_image_midjourney_usage', 0),
                'monthly_limit' => hexagon_get_option('hexagon_image_midjourney_limit', 500)
            ],
            'stable' => [
                'enabled' => !empty(hexagon_get_option('hexagon_image_stable_api_key')),
                'status' => !empty(hexagon_get_option('hexagon_image_stable_api_key')) ? 'connected' : 'disconnected',
                'model' => hexagon_get_option('hexagon_image_stable_model', 'stable-diffusion-xl'),
                'usage' => hexagon_get_option('hexagon_image_stable_usage', 0),
                'monthly_limit' => hexagon_get_option('hexagon_image_stable_limit', 0)
            ]
        ];
        
        return rest_ensure_response($providers);
    }
    
    private static function get_uptime() {
        $uptime_file = WP_CONTENT_DIR . '/hexagon_uptime.txt';
        if (file_exists($uptime_file)) {
            $start_time = intval(file_get_contents($uptime_file));
            return time() - $start_time;
        }
        return 0;
    }
    
    private static function get_memory_usage() {
        return [
            'used' => memory_get_usage(true),
            'peak' => memory_get_peak_usage(true),
            'limit' => wp_convert_hr_to_bytes(ini_get('memory_limit'))
        ];
    }
    
    private static function get_database_size() {
        global $wpdb;
        
        $size = $wpdb->get_var("
            SELECT ROUND(SUM(data_length + index_length) / 1024 / 1024, 1) AS 'DB Size in MB' 
            FROM information_schema.tables 
            WHERE table_schema='{$wpdb->dbname}'
        ");
        
        return floatval($size);
    }
}