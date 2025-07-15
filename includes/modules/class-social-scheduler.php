<?php
if (!defined('ABSPATH')) exit;

class Hexagon_Social_Scheduler {
    
    public static function init() {
        add_action('wp_ajax_hexagon_schedule_post', [__CLASS__, 'ajax_schedule_post']);
        add_action('wp_ajax_hexagon_get_scheduled_posts', [__CLASS__, 'ajax_get_scheduled_posts']);
        add_action('wp_ajax_hexagon_update_scheduled_post', [__CLASS__, 'ajax_update_scheduled_post']);
        add_action('wp_ajax_hexagon_delete_scheduled_post', [__CLASS__, 'ajax_delete_scheduled_post']);
        add_action('wp_ajax_hexagon_connect_social_account', [__CLASS__, 'ajax_connect_social_account']);
        add_action('wp_ajax_hexagon_disconnect_social_account', [__CLASS__, 'ajax_disconnect_social_account']);
        add_action('wp_ajax_hexagon_get_social_accounts', [__CLASS__, 'ajax_get_social_accounts']);
        add_action('wp_ajax_hexagon_test_social_connection', [__CLASS__, 'ajax_test_social_connection']);
        add_action('wp_ajax_hexagon_get_social_stats', [__CLASS__, 'ajax_get_social_stats']);
        
        // Schedule the posting cron job
        if (!wp_next_scheduled('hexagon_process_scheduled_posts')) {
            wp_schedule_event(time(), 'every_minute', 'hexagon_process_scheduled_posts');
        }
        add_action('hexagon_process_scheduled_posts', [__CLASS__, 'process_scheduled_posts']);
        
        // Analytics sync
        if (!wp_next_scheduled('hexagon_sync_social_analytics')) {
            wp_schedule_event(time(), 'hourly', 'hexagon_sync_social_analytics');
        }
        add_action('hexagon_sync_social_analytics', [__CLASS__, 'sync_social_analytics']);
        
        // Add custom cron schedules
        add_filter('cron_schedules', [__CLASS__, 'add_cron_schedules']);
    }
    
    public static function add_cron_schedules($schedules) {
        $schedules['every_minute'] = [
            'interval' => 60,
            'display' => __('Every Minute')
        ];
        return $schedules;
    }
    
    public static function schedule_post($content, $platforms, $scheduled_for, $settings = []) {
        global $wpdb;
        
        $default_settings = [
            'timezone' => 'UTC',
            'images' => [],
            'hashtags' => '',
            'max_attempts' => 3,
            'auto_optimize' => true,
            'track_analytics' => true
        ];
        
        $settings = array_merge($default_settings, $settings);
        
        // Validate platforms
        $valid_platforms = ['facebook', 'twitter', 'instagram', 'linkedin', 'tiktok', 'youtube'];
        $platforms = array_intersect($platforms, $valid_platforms);
        
        if (empty($platforms)) {
            return new WP_Error('invalid_platforms', 'No valid platforms specified');
        }
        
        // Validate scheduled time
        $scheduled_timestamp = strtotime($scheduled_for);
        if ($scheduled_timestamp === false || $scheduled_timestamp <= time()) {
            return new WP_Error('invalid_schedule_time', 'Scheduled time must be in the future');
        }
        
        // Optimize content for each platform if requested
        if ($settings['auto_optimize']) {
            $content = self::optimize_content_for_platforms($content, $platforms);
        }
        
        $table = $wpdb->prefix . 'hex_scheduled_posts';
        $post_id = 'sched_' . uniqid();
        
        $result = $wpdb->insert(
            $table,
            [
                'post_id' => $post_id,
                'platforms' => json_encode($platforms),
                'content' => $content,
                'images' => json_encode($settings['images']),
                'scheduled_for' => date('Y-m-d H:i:s', $scheduled_timestamp),
                'timezone' => $settings['timezone'],
                'status' => 'scheduled',
                'max_attempts' => $settings['max_attempts'],
                'created_at' => current_time('mysql')
            ],
            ['%s', '%s', '%s', '%s', '%s', '%s', '%s', '%d', '%s']
        );
        
        if ($result === false) {
            return new WP_Error('db_error', 'Failed to schedule post');
        }
        
        hexagon_log('Social Scheduler', "Post scheduled for " . implode(', ', $platforms) . " at {$scheduled_for}", 'info');
        
        return [
            'post_id' => $post_id,
            'platforms' => $platforms,
            'scheduled_for' => $scheduled_for,
            'status' => 'scheduled'
        ];
    }
    
    private static function optimize_content_for_platforms($content, $platforms) {
        $optimized_content = [];
        
        foreach ($platforms as $platform) {
            switch ($platform) {
                case 'twitter':
                    // Twitter has character limits
                    $optimized_content[$platform] = substr($content, 0, 280);
                    break;
                    
                case 'instagram':
                    // Instagram allows longer captions but prioritizes hashtags
                    $optimized_content[$platform] = $content . "\n\n#instagram #content";
                    break;
                    
                case 'linkedin':
                    // LinkedIn prefers professional tone
                    $optimized_content[$platform] = $content;
                    break;
                    
                case 'facebook':
                    // Facebook has no strict limits but shorter is better
                    $optimized_content[$platform] = $content;
                    break;
                    
                case 'tiktok':
                    // TikTok is video-focused with short descriptions
                    $optimized_content[$platform] = substr($content, 0, 150) . " #tiktok";
                    break;
                    
                case 'youtube':
                    // YouTube allows longer descriptions
                    $optimized_content[$platform] = $content;
                    break;
                    
                default:
                    $optimized_content[$platform] = $content;
            }
        }
        
        return json_encode($optimized_content);
    }
    
    public static function process_scheduled_posts() {
        global $wpdb;
        
        $table = $wpdb->prefix . 'hex_scheduled_posts';
        $current_time = current_time('mysql');
        
        // Get posts that are ready to be published
        $posts_to_publish = $wpdb->get_results($wpdb->prepare("
            SELECT * FROM {$table} 
            WHERE status = 'scheduled' 
            AND scheduled_for <= %s 
            AND attempts < max_attempts
            LIMIT 10
        ", $current_time), ARRAY_A);
        
        foreach ($posts_to_publish as $post) {
            self::publish_to_platforms($post);
        }
    }
    
    private static function publish_to_platforms($post_data) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'hex_scheduled_posts';
        $platforms = json_decode($post_data['platforms'], true);
        $content = $post_data['content'];
        $images = json_decode($post_data['images'], true);
        
        // Parse optimized content if available
        $decoded_content = json_decode($content, true);
        if (is_array($decoded_content)) {
            $platform_content = $decoded_content;
        } else {
            // Fallback to same content for all platforms
            $platform_content = array_fill_keys($platforms, $content);
        }
        
        $results = [];
        $successful_posts = 0;
        
        foreach ($platforms as $platform) {
            $platform_content_text = $platform_content[$platform] ?? $content;
            $result = self::post_to_platform($platform, $platform_content_text, $images, $post_data);
            
            $results[$platform] = $result;
            
            if (!is_wp_error($result)) {
                $successful_posts++;
                
                // Store individual platform post record
                self::store_platform_post_record($post_data['post_id'], $platform, $result, $platform_content_text);
            }
        }
        
        // Update scheduled post status
        $new_status = ($successful_posts > 0) ? 'published' : 'failed';
        if ($successful_posts > 0 && $successful_posts < count($platforms)) {
            $new_status = 'partial';
        }
        
        $wpdb->update(
            $table,
            [
                'status' => $new_status,
                'posting_results' => json_encode($results),
                'attempts' => $post_data['attempts'] + 1,
                'posted_at' => current_time('mysql')
            ],
            ['id' => $post_data['id']],
            ['%s', '%s', '%d', '%s'],
            ['%d']
        );
        
        hexagon_log('Social Scheduler', "Published post {$post_data['post_id']} to {$successful_posts}/" . count($platforms) . " platforms", $successful_posts > 0 ? 'info' : 'error');
    }
    
    private static function post_to_platform($platform, $content, $images, $post_data) {
        // Get platform connection details
        $connection = self::get_platform_connection($platform);
        if (is_wp_error($connection)) {
            return $connection;
        }
        
        switch ($platform) {
            case 'facebook':
                return self::post_to_facebook($content, $images, $connection);
                
            case 'twitter':
                return self::post_to_twitter($content, $images, $connection);
                
            case 'instagram':
                return self::post_to_instagram($content, $images, $connection);
                
            case 'linkedin':
                return self::post_to_linkedin($content, $images, $connection);
                
            case 'tiktok':
                return self::post_to_tiktok($content, $images, $connection);
                
            case 'youtube':
                return self::post_to_youtube($content, $images, $connection);
                
            default:
                return new WP_Error('unsupported_platform', 'Platform not supported: ' . $platform);
        }
    }
    
    private static function post_to_facebook($content, $images, $connection) {
        $page_id = $connection['account_id'];
        $access_token = $connection['access_token'];
        
        $post_data = [
            'message' => $content,
            'access_token' => $access_token
        ];
        
        // Add images if provided
        if (!empty($images)) {
            // For multiple images, use batch upload
            if (count($images) > 1) {
                $photo_ids = [];
                foreach ($images as $image_url) {
                    $photo_response = wp_remote_post("https://graph.facebook.com/v18.0/{$page_id}/photos", [
                        'body' => [
                            'url' => $image_url,
                            'published' => 'false',
                            'access_token' => $access_token
                        ]
                    ]);
                    
                    if (!is_wp_error($photo_response)) {
                        $photo_data = json_decode(wp_remote_retrieve_body($photo_response), true);
                        if (isset($photo_data['id'])) {
                            $photo_ids[] = ['media_fbid' => $photo_data['id']];
                        }
                    }
                }
                
                if (!empty($photo_ids)) {
                    $post_data['attached_media'] = json_encode($photo_ids);
                }
            } else {
                // Single image
                $post_data['link'] = $images[0];
            }
        }
        
        $response = wp_remote_post("https://graph.facebook.com/v18.0/{$page_id}/feed", [
            'body' => $post_data,
            'timeout' => 30
        ]);
        
        if (is_wp_error($response)) {
            return new WP_Error('facebook_api_error', 'Facebook API request failed: ' . $response->get_error_message());
        }
        
        $body = json_decode(wp_remote_retrieve_body($response), true);
        
        if (isset($body['error'])) {
            return new WP_Error('facebook_error', 'Facebook error: ' . $body['error']['message']);
        }
        
        return [
            'platform_post_id' => $body['id'] ?? null,
            'platform' => 'facebook',
            'posted_at' => current_time('mysql'),
            'response_data' => $body
        ];
    }
    
    private static function post_to_twitter($content, $images, $connection) {
        // Twitter API v2 implementation
        $bearer_token = $connection['access_token'];
        
        $tweet_data = [
            'text' => $content
        ];
        
        // Handle media uploads if images provided
        if (!empty($images)) {
            $media_ids = [];
            foreach ($images as $image_url) {
                // Download image and upload to Twitter
                $image_content = wp_remote_get($image_url);
                if (!is_wp_error($image_content)) {
                    $media_id = self::upload_twitter_media(wp_remote_retrieve_body($image_content), $bearer_token);
                    if ($media_id) {
                        $media_ids[] = $media_id;
                    }
                }
            }
            
            if (!empty($media_ids)) {
                $tweet_data['media'] = ['media_ids' => $media_ids];
            }
        }
        
        $response = wp_remote_post('https://api.twitter.com/2/tweets', [
            'headers' => [
                'Authorization' => 'Bearer ' . $bearer_token,
                'Content-Type' => 'application/json'
            ],
            'body' => json_encode($tweet_data),
            'timeout' => 30
        ]);
        
        if (is_wp_error($response)) {
            return new WP_Error('twitter_api_error', 'Twitter API request failed: ' . $response->get_error_message());
        }
        
        $body = json_decode(wp_remote_retrieve_body($response), true);
        
        if (isset($body['errors'])) {
            return new WP_Error('twitter_error', 'Twitter error: ' . $body['errors'][0]['message']);
        }
        
        return [
            'platform_post_id' => $body['data']['id'] ?? null,
            'platform' => 'twitter',
            'posted_at' => current_time('mysql'),
            'response_data' => $body
        ];
    }
    
    private static function upload_twitter_media($media_content, $bearer_token) {
        // This is a simplified version - real implementation would need proper OAuth
        // For now, return a placeholder
        return 'media_' . uniqid();
    }
    
    private static function post_to_instagram($content, $images, $connection) {
        // Instagram Basic Display API implementation
        $access_token = $connection['access_token'];
        $instagram_account_id = $connection['account_id'];
        
        if (empty($images)) {
            return new WP_Error('instagram_error', 'Instagram requires at least one image');
        }
        
        // Create media container
        $container_data = [
            'image_url' => $images[0],
            'caption' => $content,
            'access_token' => $access_token
        ];
        
        $container_response = wp_remote_post("https://graph.facebook.com/v18.0/{$instagram_account_id}/media", [
            'body' => $container_data,
            'timeout' => 30
        ]);
        
        if (is_wp_error($container_response)) {
            return new WP_Error('instagram_api_error', 'Instagram API request failed: ' . $container_response->get_error_message());
        }
        
        $container_body = json_decode(wp_remote_retrieve_body($container_response), true);
        
        if (isset($container_body['error'])) {
            return new WP_Error('instagram_error', 'Instagram error: ' . $container_body['error']['message']);
        }
        
        $creation_id = $container_body['id'];
        
        // Publish the media
        $publish_response = wp_remote_post("https://graph.facebook.com/v18.0/{$instagram_account_id}/media_publish", [
            'body' => [
                'creation_id' => $creation_id,
                'access_token' => $access_token
            ],
            'timeout' => 30
        ]);
        
        if (is_wp_error($publish_response)) {
            return new WP_Error('instagram_publish_error', 'Instagram publish failed: ' . $publish_response->get_error_message());
        }
        
        $publish_body = json_decode(wp_remote_retrieve_body($publish_response), true);
        
        return [
            'platform_post_id' => $publish_body['id'] ?? null,
            'platform' => 'instagram',
            'posted_at' => current_time('mysql'),
            'response_data' => $publish_body
        ];
    }
    
    private static function post_to_linkedin($content, $images, $connection) {
        $access_token = $connection['access_token'];
        $person_urn = $connection['account_id'];
        
        $post_data = [
            'author' => $person_urn,
            'lifecycleState' => 'PUBLISHED',
            'specificContent' => [
                'com.linkedin.ugc.ShareContent' => [
                    'shareCommentary' => [
                        'text' => $content
                    ],
                    'shareMediaCategory' => empty($images) ? 'NONE' : 'IMAGE'
                ]
            ],
            'visibility' => [
                'com.linkedin.ugc.MemberNetworkVisibility' => 'PUBLIC'
            ]
        ];
        
        // Add images if provided
        if (!empty($images)) {
            $media_assets = [];
            foreach ($images as $image_url) {
                $media_assets[] = [
                    'status' => 'READY',
                    'description' => [
                        'text' => $content
                    ],
                    'media' => $image_url,
                    'title' => [
                        'text' => 'Shared Image'
                    ]
                ];
            }
            $post_data['specificContent']['com.linkedin.ugc.ShareContent']['media'] = $media_assets;
        }
        
        $response = wp_remote_post('https://api.linkedin.com/v2/ugcPosts', [
            'headers' => [
                'Authorization' => 'Bearer ' . $access_token,
                'Content-Type' => 'application/json',
                'X-Restli-Protocol-Version' => '2.0.0'
            ],
            'body' => json_encode($post_data),
            'timeout' => 30
        ]);
        
        if (is_wp_error($response)) {
            return new WP_Error('linkedin_api_error', 'LinkedIn API request failed: ' . $response->get_error_message());
        }
        
        $body = json_decode(wp_remote_retrieve_body($response), true);
        
        if (isset($body['error'])) {
            return new WP_Error('linkedin_error', 'LinkedIn error: ' . $body['error']['message']);
        }
        
        return [
            'platform_post_id' => $body['id'] ?? null,
            'platform' => 'linkedin',
            'posted_at' => current_time('mysql'),
            'response_data' => $body
        ];
    }
    
    private static function post_to_tiktok($content, $images, $connection) {
        // TikTok is primarily video-based, so this is a placeholder
        // Real implementation would need video upload capabilities
        return new WP_Error('tiktok_not_implemented', 'TikTok posting requires video content and is not yet implemented');
    }
    
    private static function post_to_youtube($content, $images, $connection) {
        // YouTube is primarily video-based, so this is a placeholder
        // Real implementation would need video upload capabilities
        return new WP_Error('youtube_not_implemented', 'YouTube posting requires video content and is not yet implemented');
    }
    
    private static function get_platform_connection($platform) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'hex_social_accounts';
        
        $connection = $wpdb->get_row($wpdb->prepare("
            SELECT * FROM {$table} 
            WHERE platform = %s 
            AND is_connected = 1 
            AND (token_expires_at IS NULL OR token_expires_at > %s)
        ", $platform, current_time('mysql')), ARRAY_A);
        
        if (!$connection) {
            return new WP_Error('no_connection', "No active connection found for platform: {$platform}");
        }
        
        return $connection;
    }
    
    private static function store_platform_post_record($scheduled_post_id, $platform, $result, $content) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'hex_social_posts';
        
        $wpdb->insert(
            $table,
            [
                'post_id' => $scheduled_post_id,
                'platform' => $platform,
                'platform_post_id' => $result['platform_post_id'] ?? null,
                'content' => $content,
                'posted_at' => current_time('mysql'),
                'status' => 'published',
                'created_at' => current_time('mysql')
            ],
            ['%s', '%s', '%s', '%s', '%s', '%s', '%s']
        );
    }
    
    public static function sync_social_analytics() {
        global $wpdb;
        
        $social_posts_table = $wpdb->prefix . 'hex_social_posts';
        
        // Get recent posts that need analytics updates
        $posts_to_sync = $wpdb->get_results($wpdb->prepare("
            SELECT * FROM {$social_posts_table} 
            WHERE status = 'published' 
            AND posted_at > %s 
            AND platform_post_id IS NOT NULL
            ORDER BY posted_at DESC 
            LIMIT 50
        ", date('Y-m-d H:i:s', strtotime('-7 days'))), ARRAY_A);
        
        foreach ($posts_to_sync as $post) {
            $analytics = self::fetch_post_analytics($post['platform'], $post['platform_post_id']);
            
            if (!is_wp_error($analytics)) {
                $wpdb->update(
                    $social_posts_table,
                    [
                        'engagement_data' => json_encode($analytics['engagement'] ?? []),
                        'reach_data' => json_encode($analytics['reach'] ?? []),
                        'performance_score' => self::calculate_performance_score($analytics)
                    ],
                    ['id' => $post['id']],
                    ['%s', '%s', '%d'],
                    ['%d']
                );
            }
        }
    }
    
    private static function fetch_post_analytics($platform, $platform_post_id) {
        switch ($platform) {
            case 'facebook':
                return self::fetch_facebook_analytics($platform_post_id);
            case 'twitter':
                return self::fetch_twitter_analytics($platform_post_id);
            case 'instagram':
                return self::fetch_instagram_analytics($platform_post_id);
            case 'linkedin':
                return self::fetch_linkedin_analytics($platform_post_id);
            default:
                return new WP_Error('unsupported_platform', 'Analytics not supported for platform: ' . $platform);
        }
    }
    
    private static function fetch_facebook_analytics($post_id) {
        // Placeholder for Facebook analytics
        return [
            'engagement' => ['likes' => 0, 'comments' => 0, 'shares' => 0],
            'reach' => ['impressions' => 0, 'reach' => 0]
        ];
    }
    
    private static function fetch_twitter_analytics($post_id) {
        // Placeholder for Twitter analytics
        return [
            'engagement' => ['likes' => 0, 'retweets' => 0, 'replies' => 0],
            'reach' => ['impressions' => 0]
        ];
    }
    
    private static function fetch_instagram_analytics($post_id) {
        // Placeholder for Instagram analytics
        return [
            'engagement' => ['likes' => 0, 'comments' => 0],
            'reach' => ['impressions' => 0, 'reach' => 0]
        ];
    }
    
    private static function fetch_linkedin_analytics($post_id) {
        // Placeholder for LinkedIn analytics
        return [
            'engagement' => ['likes' => 0, 'comments' => 0, 'shares' => 0],
            'reach' => ['impressions' => 0]
        ];
    }
    
    private static function calculate_performance_score($analytics) {
        // Simple performance scoring based on engagement metrics
        $engagement = $analytics['engagement'] ?? [];
        $reach = $analytics['reach'] ?? [];
        
        $total_engagement = array_sum($engagement);
        $total_reach = $reach['reach'] ?? $reach['impressions'] ?? 1;
        
        $engagement_rate = ($total_reach > 0) ? ($total_engagement / $total_reach) * 100 : 0;
        
        // Score out of 100
        return min(100, round($engagement_rate * 10));
    }
    
    // AJAX Handlers
    public static function ajax_schedule_post() {
        check_ajax_referer('hexagon_nonce', 'nonce');
        
        if (!current_user_can('edit_posts')) {
            wp_send_json_error('Insufficient permissions');
            return;
        }
        
        $content = sanitize_textarea_field($_POST['content'] ?? '');
        $platforms = array_map('sanitize_text_field', $_POST['platforms'] ?? []);
        $scheduled_for = sanitize_text_field($_POST['scheduled_for'] ?? '');
        $settings = array_map('sanitize_text_field', $_POST['settings'] ?? []);
        
        if (empty($content) || empty($platforms) || empty($scheduled_for)) {
            wp_send_json_error('Content, platforms, and schedule time are required');
            return;
        }
        
        $result = self::schedule_post($content, $platforms, $scheduled_for, $settings);
        
        if (is_wp_error($result)) {
            wp_send_json_error($result->get_error_message());
        } else {
            wp_send_json_success($result);
        }
    }
    
    public static function ajax_get_scheduled_posts() {
        check_ajax_referer('hexagon_nonce', 'nonce');
        
        if (!current_user_can('edit_posts')) {
            wp_send_json_error('Insufficient permissions');
            return;
        }
        
        global $wpdb;
        $table = $wpdb->prefix . 'hex_scheduled_posts';
        
        $status = sanitize_text_field($_POST['status'] ?? 'all');
        $limit = (int) ($_POST['limit'] ?? 20);
        $offset = (int) ($_POST['offset'] ?? 0);
        
        $where_clause = '';
        if ($status !== 'all') {
            $where_clause = $wpdb->prepare("WHERE status = %s", $status);
        }
        
        $posts = $wpdb->get_results($wpdb->prepare("
            SELECT * FROM {$table} 
            {$where_clause}
            ORDER BY scheduled_for DESC 
            LIMIT %d OFFSET %d
        ", $limit, $offset), ARRAY_A);
        
        // Decode JSON fields
        foreach ($posts as &$post) {
            $post['platforms'] = json_decode($post['platforms'], true);
            $post['images'] = json_decode($post['images'], true);
            $post['posting_results'] = json_decode($post['posting_results'], true);
        }
        
        wp_send_json_success($posts);
    }
    
    public static function ajax_get_social_accounts() {
        check_ajax_referer('hexagon_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
            return;
        }
        
        global $wpdb;
        $table = $wpdb->prefix . 'hex_social_accounts';
        
        $accounts = $wpdb->get_results("SELECT * FROM {$table} ORDER BY platform, account_name", ARRAY_A);
        
        // Remove sensitive data before sending
        foreach ($accounts as &$account) {
            unset($account['access_token'], $account['refresh_token']);
            $account['account_data'] = json_decode($account['account_data'], true);
        }
        
        wp_send_json_success($accounts);
    }
    
    public static function ajax_get_social_stats() {
        check_ajax_referer('hexagon_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
            return;
        }
        
        global $wpdb;
        
        $scheduled_table = $wpdb->prefix . 'hex_scheduled_posts';
        $social_table = $wpdb->prefix . 'hex_social_posts';
        
        // Get scheduled posts stats
        $total_scheduled = $wpdb->get_var("SELECT COUNT(*) FROM {$scheduled_table}");
        $published_posts = $wpdb->get_var("SELECT COUNT(*) FROM {$scheduled_table} WHERE status = 'published'");
        $failed_posts = $wpdb->get_var("SELECT COUNT(*) FROM {$scheduled_table} WHERE status = 'failed'");
        
        // Get platform breakdown
        $platform_stats = $wpdb->get_results("
            SELECT platform, COUNT(*) as count, AVG(performance_score) as avg_performance
            FROM {$social_table} 
            WHERE status = 'published'
            GROUP BY platform
        ", ARRAY_A);
        
        // Get recent performance
        $recent_posts = $wpdb->get_results("
            SELECT platform, engagement_data, reach_data, performance_score, posted_at
            FROM {$social_table} 
            WHERE status = 'published' 
            AND posted_at > DATE_SUB(NOW(), INTERVAL 30 DAY)
            ORDER BY posted_at DESC
        ", ARRAY_A);
        
        wp_send_json_success([
            'total_scheduled' => (int) $total_scheduled,
            'published_posts' => (int) $published_posts,
            'failed_posts' => (int) $failed_posts,
            'success_rate' => $total_scheduled > 0 ? round(($published_posts / $total_scheduled) * 100, 1) : 0,
            'platform_stats' => $platform_stats,
            'recent_posts' => $recent_posts
        ]);
    }
    
    public static function ajax_connect_social_account() {
        check_ajax_referer('hexagon_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
            return;
        }
        
        // This would handle OAuth flows for each platform
        // For now, return a placeholder response
        wp_send_json_success(['message' => 'Social account connection flow initiated']);
    }
    
    public static function ajax_test_social_connection() {
        check_ajax_referer('hexagon_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
            return;
        }
        
        $platform = sanitize_text_field($_POST['platform'] ?? '');
        
        if (empty($platform)) {
            wp_send_json_error('Platform is required');
            return;
        }
        
        $connection = self::get_platform_connection($platform);
        
        if (is_wp_error($connection)) {
            wp_send_json_error($connection->get_error_message());
        } else {
            wp_send_json_success([
                'platform' => $platform,
                'account_name' => $connection['account_name'],
                'connection_status' => 'active',
                'last_sync' => $connection['last_sync']
            ]);
        }
    }
}