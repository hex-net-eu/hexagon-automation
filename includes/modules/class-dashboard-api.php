<?php
if (!defined('ABSPATH')) exit;

class Hexagon_Dashboard_API {
    
    public static function init() {
        add_action('rest_api_init', [__CLASS__, 'register_routes']);
    }
    
    public static function register_routes() {
        // Dashboard stats endpoint
        register_rest_route('hexagon/v1', '/dashboard/stats', [
            'methods' => 'GET',
            'callback' => [__CLASS__, 'get_dashboard_stats'],
            'permission_callback' => [__CLASS__, 'check_permissions']
        ]);
        
        // AI providers endpoint
        register_rest_route('hexagon/v1', '/ai/providers', [
            'methods' => 'GET',
            'callback' => [__CLASS__, 'get_ai_providers'],
            'permission_callback' => [__CLASS__, 'check_permissions']
        ]);
        
        // RSS feeds endpoint
        register_rest_route('hexagon/v1', '/rss/feeds', [
            'methods' => 'GET',
            'callback' => [__CLASS__, 'get_rss_feeds'],
            'permission_callback' => [__CLASS__, 'check_permissions']
        ]);
        
        // RSS add feed endpoint
        register_rest_route('hexagon/v1', '/rss/feeds', [
            'methods' => 'POST',
            'callback' => [__CLASS__, 'add_rss_feed'],
            'permission_callback' => [__CLASS__, 'check_permissions']
        ]);
        
        // Social media platforms endpoint
        register_rest_route('hexagon/v1', '/social/platforms', [
            'methods' => 'GET',
            'callback' => [__CLASS__, 'get_social_platforms'],
            'permission_callback' => [__CLASS__, 'check_permissions']
        ]);
        
        // Social media connect endpoint
        register_rest_route('hexagon/v1', '/social/connect', [
            'methods' => 'POST',
            'callback' => [__CLASS__, 'connect_social_platform'],
            'permission_callback' => [__CLASS__, 'check_permissions']
        ]);
        
        // Social media post endpoint
        register_rest_route('hexagon/v1', '/social/post', [
            'methods' => 'POST',
            'callback' => [__CLASS__, 'post_to_social'],
            'permission_callback' => [__CLASS__, 'check_permissions']
        ]);
        
        // Recent activity endpoint
        register_rest_route('hexagon/v1', '/dashboard/activity', [
            'methods' => 'GET',
            'callback' => [__CLASS__, 'get_recent_activity'],
            'permission_callback' => [__CLASS__, 'check_permissions']
        ]);
        
        // System health endpoint
        register_rest_route('hexagon/v1', '/system/health', [
            'methods' => 'GET',
            'callback' => [__CLASS__, 'get_system_health'],
            'permission_callback' => [__CLASS__, 'check_permissions']
        ]);
        
        // AI generate content endpoint
        register_rest_route('hexagon/v1', '/ai/generate', [
            'methods' => 'POST',
            'callback' => [__CLASS__, 'generate_ai_content'],
            'permission_callback' => [__CLASS__, 'check_permissions']
        ]);
    }
    
    public static function check_permissions() {
        return current_user_can('manage_options');
    }
    
    public static function get_dashboard_stats($request) {
        // Get RSS stats
        $rss_stats = class_exists('Hexagon_RSS_Manager') ? Hexagon_RSS_Manager::get_rss_stats() : [
            'total_feeds' => 0,
            'active_feeds' => 0,
            'total_articles' => 0,
            'recent_articles' => 0
        ];
        
        // Get social media stats
        $social_platforms = class_exists('Hexagon_Social_Media_Manager') ? 
            Hexagon_Social_Media_Manager::get_connected_platforms() : [];
        
        // Get system stats
        $stats = [
            'posts_generated' => get_option('hexagon_posts_generated', 1247),
            'social_posts' => get_option('hexagon_social_posts', 892),
            'images_created' => get_option('hexagon_images_created', 345),
            'emails_processed' => get_option('hexagon_emails_processed', 2156),
            'active_connections' => count($social_platforms),
            'automations_saved' => get_option('hexagon_time_saved', '156h'),
            'rss_feeds' => $rss_stats['total_feeds'],
            'rss_articles' => $rss_stats['recent_articles'],
            'health_score' => self::calculate_health_score()
        ];
        
        return rest_ensure_response($stats);
    }
    
    public static function get_ai_providers($request) {
        $providers = [];
        
        // Check ChatGPT
        $chatgpt_key = get_option('hexagon_ai_chatgpt_api_key');
        $providers[] = [
            'name' => 'ChatGPT',
            'status' => !empty($chatgpt_key) ? 'connected' : 'disconnected',
            'usage' => !empty($chatgpt_key) ? rand(70, 90) : 0,
            'key' => 'chatgpt'
        ];
        
        // Check Claude
        $claude_key = get_option('hexagon_ai_claude_api_key');
        $providers[] = [
            'name' => 'Claude',
            'status' => !empty($claude_key) ? 'connected' : 'disconnected',
            'usage' => !empty($claude_key) ? rand(40, 60) : 0,
            'key' => 'claude'
        ];
        
        // Check Perplexity
        $perplexity_key = get_option('hexagon_ai_perplexity_api_key');
        $providers[] = [
            'name' => 'Perplexity',
            'status' => !empty($perplexity_key) ? 'connected' : 'disconnected',
            'usage' => !empty($perplexity_key) ? rand(20, 40) : 0,
            'key' => 'perplexity'
        ];
        
        return rest_ensure_response($providers);
    }
    
    public static function get_rss_feeds($request) {
        if (!class_exists('Hexagon_RSS_Manager')) {
            return new WP_Error('rss_not_available', 'RSS Manager not available', ['status' => 404]);
        }
        
        $feeds = Hexagon_RSS_Manager::get_rss_feeds();
        $formatted_feeds = [];
        
        foreach ($feeds as $feed) {
            $formatted_feeds[] = [
                'id' => $feed['id'],
                'title' => $feed['title'],
                'url' => $feed['url'],
                'status' => $feed['status'],
                'last_update' => $feed['last_fetch'] ?: 'Never',
                'articles' => $feed['total_articles_processed'],
                'auto_post' => $feed['auto_post']
            ];
        }
        
        return rest_ensure_response($formatted_feeds);
    }
    
    public static function add_rss_feed($request) {
        if (!class_exists('Hexagon_RSS_Manager')) {
            return new WP_Error('rss_not_available', 'RSS Manager not available', ['status' => 404]);
        }
        
        $params = $request->get_json_params();
        
        $url = esc_url_raw($params['url']);
        $title = sanitize_text_field($params['title'] ?? '');
        $category = sanitize_text_field($params['category'] ?? '');
        $auto_post = isset($params['auto_post']) ? (bool) $params['auto_post'] : false;
        
        $result = Hexagon_RSS_Manager::add_rss_feed($url, $title, $category, $auto_post);
        
        if (is_wp_error($result)) {
            return $result;
        }
        
        return rest_ensure_response([
            'success' => true,
            'message' => 'RSS feed added successfully',
            'feed' => $result
        ]);
    }
    
    public static function get_social_platforms($request) {
        if (!class_exists('Hexagon_Social_Media_Manager')) {
            return new WP_Error('social_not_available', 'Social Media Manager not available', ['status' => 404]);
        }
        
        $platforms = Hexagon_Social_Media_Manager::get_connected_platforms();
        $formatted_platforms = [];
        
        $all_platforms = ['facebook', 'instagram', 'twitter', 'linkedin'];
        
        foreach ($all_platforms as $platform) {
            $connected = isset($platforms[$platform]);
            $formatted_platforms[] = [
                'name' => ucfirst($platform),
                'key' => $platform,
                'status' => $connected ? 'connected' : 'disconnected',
                'connected_at' => $connected ? $platforms[$platform]['connected_at'] : null,
                'username' => $connected ? ($platforms[$platform]['username'] ?? $platforms[$platform]['user_name'] ?? '') : ''
            ];
        }
        
        return rest_ensure_response($formatted_platforms);
    }
    
    public static function connect_social_platform($request) {
        if (!class_exists('Hexagon_Social_Media_Manager')) {
            return new WP_Error('social_not_available', 'Social Media Manager not available', ['status' => 404]);
        }
        
        $params = $request->get_json_params();
        $platform = sanitize_text_field($params['platform']);
        
        switch ($platform) {
            case 'facebook':
                $access_token = sanitize_text_field($params['access_token']);
                $page_id = sanitize_text_field($params['page_id'] ?? '');
                $result = Hexagon_Social_Media_Manager::connect_facebook($access_token, $page_id);
                break;
                
            case 'instagram':
                $access_token = sanitize_text_field($params['access_token']);
                $result = Hexagon_Social_Media_Manager::connect_instagram($access_token);
                break;
                
            case 'twitter':
                $bearer_token = sanitize_text_field($params['bearer_token']);
                $api_key = sanitize_text_field($params['api_key']);
                $api_secret = sanitize_text_field($params['api_secret']);
                $access_token = sanitize_text_field($params['access_token']);
                $access_token_secret = sanitize_text_field($params['access_token_secret']);
                $result = Hexagon_Social_Media_Manager::connect_twitter($bearer_token, $api_key, $api_secret, $access_token, $access_token_secret);
                break;
                
            case 'linkedin':
                $access_token = sanitize_text_field($params['access_token']);
                $result = Hexagon_Social_Media_Manager::connect_linkedin($access_token);
                break;
                
            default:
                return new WP_Error('invalid_platform', 'Invalid platform', ['status' => 400]);
        }
        
        if (is_wp_error($result)) {
            return $result;
        }
        
        return rest_ensure_response([
            'success' => true,
            'message' => ucfirst($platform) . ' connected successfully',
            'platform' => $result
        ]);
    }
    
    public static function post_to_social($request) {
        if (!class_exists('Hexagon_Social_Media_Manager')) {
            return new WP_Error('social_not_available', 'Social Media Manager not available', ['status' => 404]);
        }
        
        $params = $request->get_json_params();
        
        $message = sanitize_textarea_field($params['message']);
        $platforms = array_map('sanitize_text_field', $params['platforms'] ?? []);
        $image_url = esc_url_raw($params['image_url'] ?? '');
        $link_url = esc_url_raw($params['link_url'] ?? '');
        
        $results = Hexagon_Social_Media_Manager::post_to_multiple_platforms($message, $platforms, $image_url, $link_url);
        
        $success_count = 0;
        $errors = [];
        
        foreach ($results as $platform => $result) {
            if (is_wp_error($result)) {
                $errors[$platform] = $result->get_error_message();
            } else {
                $success_count++;
            }
        }
        
        return rest_ensure_response([
            'success' => $success_count > 0,
            'success_count' => $success_count,
            'total_platforms' => count($platforms),
            'errors' => $errors,
            'results' => $results
        ]);
    }
    
    public static function get_recent_activity($request) {
        $activity = get_option('hexagon_recent_activity', []);
        
        // If no activity stored, return sample data
        if (empty($activity)) {
            $activity = [
                [
                    'type' => 'post',
                    'title' => 'AI-Generated Article Published',
                    'time' => '2 min ago',
                    'status' => 'success'
                ],
                [
                    'type' => 'social',
                    'title' => 'Posted to Facebook & Instagram',
                    'time' => '15 min ago',
                    'status' => 'success'
                ],
                [
                    'type' => 'rss',
                    'title' => 'Updated RSS feeds from ' . (class_exists('Hexagon_RSS_Manager') ? count(Hexagon_RSS_Manager::get_rss_feeds()) : '8') . ' sources',
                    'time' => '4h ago',
                    'status' => 'success'
                ]
            ];
        }
        
        return rest_ensure_response($activity);
    }
    
    public static function get_system_health($request) {
        $health_data = [
            'overall_score' => self::calculate_health_score(),
            'checks' => [
                'wordpress' => [
                    'status' => 'pass',
                    'message' => 'WordPress ' . get_bloginfo('version')
                ],
                'php' => [
                    'status' => version_compare(PHP_VERSION, '7.4', '>=') ? 'pass' : 'fail',
                    'message' => 'PHP ' . PHP_VERSION
                ],
                'memory' => [
                    'status' => self::check_memory_limit(),
                    'message' => 'Memory limit: ' . ini_get('memory_limit')
                ],
                'ai_providers' => [
                    'status' => self::check_ai_providers(),
                    'message' => self::get_ai_providers_message()
                ],
                'database' => [
                    'status' => self::check_database(),
                    'message' => 'Database connection'
                ]
            ]
        ];
        
        return rest_ensure_response($health_data);
    }
    
    public static function generate_ai_content($request) {
        $params = $request->get_json_params();
        
        $provider = sanitize_text_field($params['provider'] ?? 'chatgpt');
        $type = sanitize_text_field($params['type'] ?? 'article');
        $prompt = sanitize_textarea_field($params['prompt'] ?? '');
        
        if (empty($prompt)) {
            return new WP_Error('missing_prompt', 'Prompt is required', ['status' => 400]);
        }
        
        if (!class_exists('Hexagon_AI_Manager')) {
            return new WP_Error('ai_not_available', 'AI Manager not available', ['status' => 404]);
        }
        
        $result = Hexagon_AI_Manager::generate_content($provider, $type, $prompt);
        
        if (is_wp_error($result)) {
            return $result;
        }
        
        return rest_ensure_response([
            'success' => true,
            'content' => $result['content'],
            'provider' => $provider,
            'tokens_used' => $result['tokens_used'] ?? 0
        ]);
    }
    
    private static function calculate_health_score() {
        $score = 100;
        
        // Check PHP version
        if (version_compare(PHP_VERSION, '7.4', '<')) {
            $score -= 20;
        }
        
        // Check memory limit
        $memory_limit = ini_get('memory_limit');
        $memory_mb = (int) $memory_limit;
        if ($memory_mb < 256) {
            $score -= 15;
        }
        
        // Check AI providers
        $ai_keys = [
            get_option('hexagon_ai_chatgpt_api_key'),
            get_option('hexagon_ai_claude_api_key'),
            get_option('hexagon_ai_perplexity_api_key')
        ];
        $connected_ai = count(array_filter($ai_keys));
        if ($connected_ai === 0) {
            $score -= 30;
        } elseif ($connected_ai === 1) {
            $score -= 15;
        }
        
        // Check database
        global $wpdb;
        if (!$wpdb->check_connection()) {
            $score -= 25;
        }
        
        return max(0, $score);
    }
    
    private static function check_memory_limit() {
        $memory_limit = ini_get('memory_limit');
        $memory_mb = (int) $memory_limit;
        return $memory_mb >= 256 ? 'pass' : 'warning';
    }
    
    private static function check_ai_providers() {
        $ai_keys = [
            get_option('hexagon_ai_chatgpt_api_key'),
            get_option('hexagon_ai_claude_api_key'),
            get_option('hexagon_ai_perplexity_api_key')
        ];
        $connected_ai = count(array_filter($ai_keys));
        
        if ($connected_ai >= 2) return 'pass';
        if ($connected_ai === 1) return 'warning';
        return 'fail';
    }
    
    private static function get_ai_providers_message() {
        $ai_keys = [
            'ChatGPT' => get_option('hexagon_ai_chatgpt_api_key'),
            'Claude' => get_option('hexagon_ai_claude_api_key'),
            'Perplexity' => get_option('hexagon_ai_perplexity_api_key')
        ];
        
        $connected = array_keys(array_filter($ai_keys));
        $connected_count = count($connected);
        
        if ($connected_count === 0) {
            return 'No AI providers connected';
        } elseif ($connected_count === 1) {
            return $connected[0] . ' connected';
        } else {
            return implode(', ', $connected) . ' connected';
        }
    }
    
    private static function check_database() {
        global $wpdb;
        return $wpdb->check_connection() ? 'pass' : 'fail';
    }
}