<?php
if (!defined('ABSPATH')) exit;

class Hexagon_Social_Media_Manager {
    
    public static function init() {
        add_action('wp_ajax_hexagon_connect_social_platform', [__CLASS__, 'ajax_connect_platform']);
        add_action('wp_ajax_hexagon_disconnect_social_platform', [__CLASS__, 'ajax_disconnect_platform']);
        add_action('wp_ajax_hexagon_post_to_social', [__CLASS__, 'ajax_post_to_social']);
        add_action('wp_ajax_hexagon_test_social_connection', [__CLASS__, 'ajax_test_connection']);
        add_action('wp_ajax_hexagon_get_social_stats', [__CLASS__, 'ajax_get_stats']);
    }
    
    // Facebook Graph API Integration
    public static function connect_facebook($access_token, $page_id = null) {
        // Validate Facebook access token
        $response = wp_remote_get("https://graph.facebook.com/me?access_token={$access_token}");
        
        if (is_wp_error($response)) {
            return new WP_Error('facebook_connection_failed', 'Failed to connect to Facebook: ' . $response->get_error_message());
        }
        
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        
        if (isset($data['error'])) {
            return new WP_Error('facebook_auth_failed', 'Facebook authentication failed: ' . $data['error']['message']);
        }
        
        // Get user's pages if page_id not specified
        if (!$page_id) {
            $pages_response = wp_remote_get("https://graph.facebook.com/me/accounts?access_token={$access_token}");
            if (!is_wp_error($pages_response)) {
                $pages_data = json_decode(wp_remote_retrieve_body($pages_response), true);
                if (isset($pages_data['data']) && !empty($pages_data['data'])) {
                    $page_id = $pages_data['data'][0]['id'];
                    $access_token = $pages_data['data'][0]['access_token']; // Use page access token
                }
            }
        }
        
        $connection_data = [
            'platform' => 'facebook',
            'user_id' => $data['id'],
            'user_name' => $data['name'],
            'access_token' => $access_token,
            'page_id' => $page_id,
            'connected_at' => current_time('mysql'),
            'status' => 'active'
        ];
        
        update_option('hexagon_facebook_connection', $connection_data);
        hexagon_log('Social Media', 'Facebook connected successfully', 'info');
        
        return $connection_data;
    }
    
    public static function post_to_facebook($message, $image_url = null, $link_url = null) {
        $connection = get_option('hexagon_facebook_connection');
        if (!$connection || $connection['status'] !== 'active') {
            return new WP_Error('facebook_not_connected', 'Facebook not connected');
        }
        
        $access_token = $connection['access_token'];
        $page_id = $connection['page_id'] ?: $connection['user_id'];
        
        $post_data = [
            'message' => $message,
            'access_token' => $access_token
        ];
        
        if ($link_url) {
            $post_data['link'] = $link_url;
        }
        
        $endpoint = "https://graph.facebook.com/{$page_id}/feed";
        
        if ($image_url) {
            $post_data['url'] = $image_url;
            $endpoint = "https://graph.facebook.com/{$page_id}/photos";
        }
        
        $response = wp_remote_post($endpoint, [
            'body' => $post_data,
            'timeout' => 30
        ]);
        
        if (is_wp_error($response)) {
            return new WP_Error('facebook_post_failed', 'Failed to post to Facebook: ' . $response->get_error_message());
        }
        
        $body = json_decode(wp_remote_retrieve_body($response), true);
        
        if (isset($body['error'])) {
            return new WP_Error('facebook_api_error', 'Facebook API error: ' . $body['error']['message']);
        }
        
        hexagon_log('Social Media', 'Posted to Facebook successfully', 'info');
        return $body;
    }
    
    // Instagram Basic Display API Integration
    public static function connect_instagram($access_token) {
        // Validate Instagram access token
        $response = wp_remote_get("https://graph.instagram.com/me?fields=id,username&access_token={$access_token}");
        
        if (is_wp_error($response)) {
            return new WP_Error('instagram_connection_failed', 'Failed to connect to Instagram: ' . $response->get_error_message());
        }
        
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        
        if (isset($data['error'])) {
            return new WP_Error('instagram_auth_failed', 'Instagram authentication failed: ' . $data['error']['message']);
        }
        
        $connection_data = [
            'platform' => 'instagram',
            'user_id' => $data['id'],
            'username' => $data['username'],
            'access_token' => $access_token,
            'connected_at' => current_time('mysql'),
            'status' => 'active'
        ];
        
        update_option('hexagon_instagram_connection', $connection_data);
        hexagon_log('Social Media', 'Instagram connected successfully', 'info');
        
        return $connection_data;
    }
    
    public static function post_to_instagram($message, $image_url) {
        $connection = get_option('hexagon_instagram_connection');
        if (!$connection || $connection['status'] !== 'active') {
            return new WP_Error('instagram_not_connected', 'Instagram not connected');
        }
        
        $access_token = $connection['access_token'];
        $user_id = $connection['user_id'];
        
        // Step 1: Create media container
        $container_response = wp_remote_post("https://graph.facebook.com/{$user_id}/media", [
            'body' => [
                'image_url' => $image_url,
                'caption' => $message,
                'access_token' => $access_token
            ],
            'timeout' => 30
        ]);
        
        if (is_wp_error($container_response)) {
            return new WP_Error('instagram_container_failed', 'Failed to create Instagram media container: ' . $container_response->get_error_message());
        }
        
        $container_data = json_decode(wp_remote_retrieve_body($container_response), true);
        
        if (isset($container_data['error'])) {
            return new WP_Error('instagram_container_error', 'Instagram container error: ' . $container_data['error']['message']);
        }
        
        $container_id = $container_data['id'];
        
        // Step 2: Publish media
        $publish_response = wp_remote_post("https://graph.facebook.com/{$user_id}/media_publish", [
            'body' => [
                'creation_id' => $container_id,
                'access_token' => $access_token
            ],
            'timeout' => 30
        ]);
        
        if (is_wp_error($publish_response)) {
            return new WP_Error('instagram_publish_failed', 'Failed to publish to Instagram: ' . $publish_response->get_error_message());
        }
        
        $publish_data = json_decode(wp_remote_retrieve_body($publish_response), true);
        
        if (isset($publish_data['error'])) {
            return new WP_Error('instagram_publish_error', 'Instagram publish error: ' . $publish_data['error']['message']);
        }
        
        hexagon_log('Social Media', 'Posted to Instagram successfully', 'info');
        return $publish_data;
    }
    
    // Twitter API v2 Integration
    public static function connect_twitter($bearer_token, $api_key, $api_secret, $access_token, $access_token_secret) {
        // Verify Twitter credentials
        $response = wp_remote_get('https://api.twitter.com/2/users/me', [
            'headers' => [
                'Authorization' => "Bearer {$bearer_token}",
                'Content-Type' => 'application/json'
            ]
        ]);
        
        if (is_wp_error($response)) {
            return new WP_Error('twitter_connection_failed', 'Failed to connect to Twitter: ' . $response->get_error_message());
        }
        
        $body = json_decode(wp_remote_retrieve_body($response), true);
        
        if (isset($body['errors'])) {
            return new WP_Error('twitter_auth_failed', 'Twitter authentication failed: ' . $body['errors'][0]['message']);
        }
        
        $connection_data = [
            'platform' => 'twitter',
            'user_id' => $body['data']['id'],
            'username' => $body['data']['username'],
            'bearer_token' => $bearer_token,
            'api_key' => $api_key,
            'api_secret' => $api_secret,
            'access_token' => $access_token,
            'access_token_secret' => $access_token_secret,
            'connected_at' => current_time('mysql'),
            'status' => 'active'
        ];
        
        update_option('hexagon_twitter_connection', $connection_data);
        hexagon_log('Social Media', 'Twitter connected successfully', 'info');
        
        return $connection_data;
    }
    
    public static function post_to_twitter($message) {
        $connection = get_option('hexagon_twitter_connection');
        if (!$connection || $connection['status'] !== 'active') {
            return new WP_Error('twitter_not_connected', 'Twitter not connected');
        }
        
        $bearer_token = $connection['bearer_token'];
        
        $response = wp_remote_post('https://api.twitter.com/2/tweets', [
            'headers' => [
                'Authorization' => "Bearer {$bearer_token}",
                'Content-Type' => 'application/json'
            ],
            'body' => json_encode([
                'text' => $message
            ]),
            'timeout' => 30
        ]);
        
        if (is_wp_error($response)) {
            return new WP_Error('twitter_post_failed', 'Failed to post to Twitter: ' . $response->get_error_message());
        }
        
        $body = json_decode(wp_remote_retrieve_body($response), true);
        
        if (isset($body['errors'])) {
            return new WP_Error('twitter_api_error', 'Twitter API error: ' . $body['errors'][0]['message']);
        }
        
        hexagon_log('Social Media', 'Posted to Twitter successfully', 'info');
        return $body;
    }
    
    // LinkedIn API Integration
    public static function connect_linkedin($access_token) {
        // Validate LinkedIn access token
        $response = wp_remote_get('https://api.linkedin.com/v2/people/~', [
            'headers' => [
                'Authorization' => "Bearer {$access_token}",
                'Content-Type' => 'application/json'
            ]
        ]);
        
        if (is_wp_error($response)) {
            return new WP_Error('linkedin_connection_failed', 'Failed to connect to LinkedIn: ' . $response->get_error_message());
        }
        
        $body = json_decode(wp_remote_retrieve_body($response), true);
        
        if (isset($body['error'])) {
            return new WP_Error('linkedin_auth_failed', 'LinkedIn authentication failed: ' . $body['error']['message']);
        }
        
        $connection_data = [
            'platform' => 'linkedin',
            'user_id' => $body['id'],
            'first_name' => $body['firstName']['localized']['en_US'] ?? '',
            'last_name' => $body['lastName']['localized']['en_US'] ?? '',
            'access_token' => $access_token,
            'connected_at' => current_time('mysql'),
            'status' => 'active'
        ];
        
        update_option('hexagon_linkedin_connection', $connection_data);
        hexagon_log('Social Media', 'LinkedIn connected successfully', 'info');
        
        return $connection_data;
    }
    
    public static function post_to_linkedin($message) {
        $connection = get_option('hexagon_linkedin_connection');
        if (!$connection || $connection['status'] !== 'active') {
            return new WP_Error('linkedin_not_connected', 'LinkedIn not connected');
        }
        
        $access_token = $connection['access_token'];
        $user_id = $connection['user_id'];
        
        $post_data = [
            'author' => "urn:li:person:{$user_id}",
            'lifecycleState' => 'PUBLISHED',
            'specificContent' => [
                'com.linkedin.ugc.ShareContent' => [
                    'shareCommentary' => [
                        'text' => $message
                    ],
                    'shareMediaCategory' => 'NONE'
                ]
            ],
            'visibility' => [
                'com.linkedin.ugc.MemberNetworkVisibility' => 'PUBLIC'
            ]
        ];
        
        $response = wp_remote_post('https://api.linkedin.com/v2/ugcPosts', [
            'headers' => [
                'Authorization' => "Bearer {$access_token}",
                'Content-Type' => 'application/json'
            ],
            'body' => json_encode($post_data),
            'timeout' => 30
        ]);
        
        if (is_wp_error($response)) {
            return new WP_Error('linkedin_post_failed', 'Failed to post to LinkedIn: ' . $response->get_error_message());
        }
        
        $body = json_decode(wp_remote_retrieve_body($response), true);
        
        if (isset($body['error'])) {
            return new WP_Error('linkedin_api_error', 'LinkedIn API error: ' . $body['error']['message']);
        }
        
        hexagon_log('Social Media', 'Posted to LinkedIn successfully', 'info');
        return $body;
    }
    
    // Multi-platform posting
    public static function post_to_multiple_platforms($message, $platforms, $image_url = null, $link_url = null) {
        $results = [];
        
        foreach ($platforms as $platform) {
            switch ($platform) {
                case 'facebook':
                    $results['facebook'] = self::post_to_facebook($message, $image_url, $link_url);
                    break;
                    
                case 'instagram':
                    if ($image_url) {
                        $results['instagram'] = self::post_to_instagram($message, $image_url);
                    } else {
                        $results['instagram'] = new WP_Error('instagram_no_image', 'Instagram requires an image');
                    }
                    break;
                    
                case 'twitter':
                    $results['twitter'] = self::post_to_twitter($message);
                    break;
                    
                case 'linkedin':
                    $results['linkedin'] = self::post_to_linkedin($message);
                    break;
            }
            
            // Add delay between posts
            sleep(1);
        }
        
        return $results;
    }
    
    public static function get_connected_platforms() {
        $platforms = [];
        
        $facebook = get_option('hexagon_facebook_connection');
        if ($facebook && $facebook['status'] === 'active') {
            $platforms['facebook'] = $facebook;
        }
        
        $instagram = get_option('hexagon_instagram_connection');
        if ($instagram && $instagram['status'] === 'active') {
            $platforms['instagram'] = $instagram;
        }
        
        $twitter = get_option('hexagon_twitter_connection');
        if ($twitter && $twitter['status'] === 'active') {
            $platforms['twitter'] = $twitter;
        }
        
        $linkedin = get_option('hexagon_linkedin_connection');
        if ($linkedin && $linkedin['status'] === 'active') {
            $platforms['linkedin'] = $linkedin;
        }
        
        return $platforms;
    }
    
    public static function disconnect_platform($platform) {
        $option_key = "hexagon_{$platform}_connection";
        $connection = get_option($option_key);
        
        if ($connection) {
            $connection['status'] = 'disconnected';
            $connection['disconnected_at'] = current_time('mysql');
            update_option($option_key, $connection);
            
            hexagon_log('Social Media', "Disconnected from {$platform}", 'info');
            return true;
        }
        
        return false;
    }
    
    // AJAX Handlers
    public static function ajax_connect_platform() {
        check_ajax_referer('hexagon_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die('Insufficient permissions');
        }
        
        $platform = sanitize_text_field($_POST['platform']);
        
        switch ($platform) {
            case 'facebook':
                $access_token = sanitize_text_field($_POST['access_token']);
                $page_id = sanitize_text_field($_POST['page_id'] ?? '');
                $result = self::connect_facebook($access_token, $page_id);
                break;
                
            case 'instagram':
                $access_token = sanitize_text_field($_POST['access_token']);
                $result = self::connect_instagram($access_token);
                break;
                
            case 'twitter':
                $bearer_token = sanitize_text_field($_POST['bearer_token']);
                $api_key = sanitize_text_field($_POST['api_key']);
                $api_secret = sanitize_text_field($_POST['api_secret']);
                $access_token = sanitize_text_field($_POST['access_token']);
                $access_token_secret = sanitize_text_field($_POST['access_token_secret']);
                $result = self::connect_twitter($bearer_token, $api_key, $api_secret, $access_token, $access_token_secret);
                break;
                
            case 'linkedin':
                $access_token = sanitize_text_field($_POST['access_token']);
                $result = self::connect_linkedin($access_token);
                break;
                
            default:
                wp_send_json_error('Invalid platform');
                return;
        }
        
        if (is_wp_error($result)) {
            wp_send_json_error($result->get_error_message());
        } else {
            wp_send_json_success($result);
        }
    }
    
    public static function ajax_disconnect_platform() {
        check_ajax_referer('hexagon_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die('Insufficient permissions');
        }
        
        $platform = sanitize_text_field($_POST['platform']);
        $result = self::disconnect_platform($platform);
        
        if ($result) {
            wp_send_json_success('Platform disconnected successfully');
        } else {
            wp_send_json_error('Failed to disconnect platform');
        }
    }
    
    public static function ajax_post_to_social() {
        check_ajax_referer('hexagon_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die('Insufficient permissions');
        }
        
        $message = sanitize_textarea_field($_POST['message']);
        $platforms = array_map('sanitize_text_field', $_POST['platforms'] ?? []);
        $image_url = esc_url_raw($_POST['image_url'] ?? '');
        $link_url = esc_url_raw($_POST['link_url'] ?? '');
        
        $results = self::post_to_multiple_platforms($message, $platforms, $image_url, $link_url);
        
        $success_count = 0;
        $errors = [];
        
        foreach ($results as $platform => $result) {
            if (is_wp_error($result)) {
                $errors[$platform] = $result->get_error_message();
            } else {
                $success_count++;
            }
        }
        
        wp_send_json_success([
            'success_count' => $success_count,
            'total_platforms' => count($platforms),
            'errors' => $errors,
            'results' => $results
        ]);
    }
    
    public static function ajax_test_connection() {
        check_ajax_referer('hexagon_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die('Insufficient permissions');
        }
        
        $platform = sanitize_text_field($_POST['platform']);
        $connection = get_option("hexagon_{$platform}_connection");
        
        if (!$connection || $connection['status'] !== 'active') {
            wp_send_json_error('Platform not connected');
            return;
        }
        
        // Test connection by making a simple API call
        $test_result = false;
        
        switch ($platform) {
            case 'facebook':
                $response = wp_remote_get("https://graph.facebook.com/me?access_token={$connection['access_token']}");
                $test_result = !is_wp_error($response) && wp_remote_retrieve_response_code($response) === 200;
                break;
                
            case 'instagram':
                $response = wp_remote_get("https://graph.instagram.com/me?fields=id&access_token={$connection['access_token']}");
                $test_result = !is_wp_error($response) && wp_remote_retrieve_response_code($response) === 200;
                break;
                
            case 'twitter':
                $response = wp_remote_get('https://api.twitter.com/2/users/me', [
                    'headers' => ['Authorization' => "Bearer {$connection['bearer_token']}"]
                ]);
                $test_result = !is_wp_error($response) && wp_remote_retrieve_response_code($response) === 200;
                break;
                
            case 'linkedin':
                $response = wp_remote_get('https://api.linkedin.com/v2/people/~', [
                    'headers' => ['Authorization' => "Bearer {$connection['access_token']}"]
                ]);
                $test_result = !is_wp_error($response) && wp_remote_retrieve_response_code($response) === 200;
                break;
        }
        
        if ($test_result) {
            wp_send_json_success('Connection test successful');
        } else {
            wp_send_json_error('Connection test failed');
        }
    }
    
    public static function ajax_get_stats() {
        check_ajax_referer('hexagon_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die('Insufficient permissions');
        }
        
        $platforms = self::get_connected_platforms();
        
        $stats = [
            'connected_platforms' => count($platforms),
            'platforms' => $platforms,
            'recent_posts' => get_option('hexagon_social_recent_posts', [])
        ];
        
        wp_send_json_success($stats);
    }
}