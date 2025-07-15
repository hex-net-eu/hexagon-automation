<?php
if (!defined('ABSPATH')) exit;

class Hexagon_Social_Integration {
    
    private static $platforms = ['facebook', 'instagram', 'twitter', 'linkedin'];
    
    public static function init() {
        add_action('wp_ajax_hexagon_social_post', [__CLASS__, 'handle_social_post']);
        add_action('wp_ajax_hexagon_social_test', [__CLASS__, 'test_social_connection']);
        add_action('wp_ajax_hexagon_social_schedule', [__CLASS__, 'schedule_post']);
        add_action('hexagon_social_post_scheduled', [__CLASS__, 'process_scheduled_post']);
        add_action('init', [__CLASS__, 'setup_social_hooks']);
    }
    
    public static function setup_social_hooks() {
        // Schedule social media tasks
        if (!wp_next_scheduled('hexagon_social_cleanup')) {
            wp_schedule_event(time(), 'hourly', 'hexagon_social_cleanup');
        }
        
        // Auto-post hooks
        add_action('publish_post', [__CLASS__, 'auto_post_to_social'], 10, 2);
        add_action('hexagon_social_cleanup', [__CLASS__, 'process_scheduled_posts']);
    }
    
    public static function handle_social_post() {
        check_ajax_referer('hexagon_social_nonce', 'nonce');
        
        $platform = sanitize_text_field($_POST['platform']);
        $message = sanitize_textarea_field($_POST['message']);
        $image_url = esc_url_raw($_POST['image_url'] ?? '');
        $link_url = esc_url_raw($_POST['link_url'] ?? '');
        
        if (!in_array($platform, self::$platforms)) {
            wp_send_json_error(['message' => 'Nieprawidłowa platforma']);
        }
        
        try {
            $result = self::post_to_platform($platform, $message, $image_url, $link_url);
            hexagon_log('Social Media Post', "Posted to $platform successfully", 'success');
            wp_send_json_success($result);
        } catch (Exception $e) {
            hexagon_log('Social Media Error', $e->getMessage(), 'error');
            wp_send_json_error(['message' => $e->getMessage()]);
        }
    }
    
    public static function post_to_platform($platform, $message, $image_url = '', $link_url = '') {
        switch ($platform) {
            case 'facebook':
                return self::post_to_facebook($message, $image_url, $link_url);
            case 'instagram':
                return self::post_to_instagram($message, $image_url);
            case 'twitter':
                return self::post_to_twitter($message, $image_url, $link_url);
            case 'linkedin':
                return self::post_to_linkedin($message, $image_url, $link_url);
            default:
                throw new Exception('Nieobsługiwana platforma: ' . $platform);
        }
    }
    
    private static function post_to_facebook($message, $image_url = '', $link_url = '') {
        $access_token = hexagon_get_option('hexagon_social_facebook_token');
        $page_id = hexagon_get_option('hexagon_social_facebook_page_id');
        
        if (empty($access_token) || empty($page_id)) {
            throw new Exception('Brak konfiguracji Facebook - wymagany token i page ID');
        }
        
        $endpoint = "https://graph.facebook.com/v18.0/$page_id/feed";
        
        $data = [
            'message' => $message,
            'access_token' => $access_token
        ];
        
        if (!empty($link_url)) {
            $data['link'] = $link_url;
        }
        
        if (!empty($image_url)) {
            $data['picture'] = $image_url;
        }
        
        $response = wp_remote_post($endpoint, [
            'body' => $data,
            'timeout' => 30
        ]);
        
        if (is_wp_error($response)) {
            throw new Exception('Facebook API Error: ' . $response->get_error_message());
        }
        
        $body = json_decode(wp_remote_retrieve_body($response), true);
        
        if (isset($body['error'])) {
            throw new Exception('Facebook Error: ' . $body['error']['message']);
        }
        
        return [
            'platform' => 'Facebook',
            'post_id' => $body['id'],
            'message' => 'Post published successfully'
        ];
    }
    
    private static function post_to_instagram($message, $image_url = '') {
        $access_token = hexagon_get_option('hexagon_social_instagram_token');
        $account_id = hexagon_get_option('hexagon_social_instagram_account_id');
        
        if (empty($access_token) || empty($account_id)) {
            throw new Exception('Brak konfiguracji Instagram - wymagany token i account ID');
        }
        
        if (empty($image_url)) {
            throw new Exception('Instagram wymaga obrazu');
        }
        
        // Step 1: Create media container
        $container_endpoint = "https://graph.facebook.com/v18.0/$account_id/media";
        
        $container_data = [
            'image_url' => $image_url,
            'caption' => $message,
            'access_token' => $access_token
        ];
        
        $container_response = wp_remote_post($container_endpoint, [
            'body' => $container_data,
            'timeout' => 30
        ]);
        
        if (is_wp_error($container_response)) {
            throw new Exception('Instagram Container Error: ' . $container_response->get_error_message());
        }
        
        $container_body = json_decode(wp_remote_retrieve_body($container_response), true);
        
        if (isset($container_body['error'])) {
            throw new Exception('Instagram Container Error: ' . $container_body['error']['message']);
        }
        
        // Step 2: Publish media
        $publish_endpoint = "https://graph.facebook.com/v18.0/$account_id/media_publish";
        
        $publish_data = [
            'creation_id' => $container_body['id'],
            'access_token' => $access_token
        ];
        
        $publish_response = wp_remote_post($publish_endpoint, [
            'body' => $publish_data,
            'timeout' => 30
        ]);
        
        if (is_wp_error($publish_response)) {
            throw new Exception('Instagram Publish Error: ' . $publish_response->get_error_message());
        }
        
        $publish_body = json_decode(wp_remote_retrieve_body($publish_response), true);
        
        if (isset($publish_body['error'])) {
            throw new Exception('Instagram Publish Error: ' . $publish_body['error']['message']);
        }
        
        return [
            'platform' => 'Instagram',
            'post_id' => $publish_body['id'],
            'message' => 'Post published successfully'
        ];
    }
    
    private static function post_to_twitter($message, $image_url = '', $link_url = '') {
        $api_key = hexagon_get_option('hexagon_social_twitter_api_key');
        $api_secret = hexagon_get_option('hexagon_social_twitter_api_secret');
        $access_token = hexagon_get_option('hexagon_social_twitter_access_token');
        $access_secret = hexagon_get_option('hexagon_social_twitter_access_secret');
        
        if (empty($api_key) || empty($api_secret) || empty($access_token) || empty($access_secret)) {
            throw new Exception('Brak konfiguracji Twitter - wymagane wszystkie klucze API');
        }
        
        // Add link to message if provided
        if (!empty($link_url)) {
            $message .= ' ' . $link_url;
        }
        
        // Ensure message is within Twitter's character limit
        if (strlen($message) > 280) {
            $message = substr($message, 0, 277) . '...';
        }
        
        $endpoint = 'https://api.twitter.com/2/tweets';
        
        $data = [
            'text' => $message
        ];
        
        // Handle media upload if image provided
        if (!empty($image_url)) {
            $media_id = self::upload_twitter_media($image_url, $api_key, $api_secret, $access_token, $access_secret);
            if ($media_id) {
                $data['media'] = ['media_ids' => [$media_id]];
            }
        }
        
        $oauth_header = self::generate_twitter_oauth_header($endpoint, 'POST', $api_key, $api_secret, $access_token, $access_secret);
        
        $response = wp_remote_post($endpoint, [
            'headers' => [
                'Authorization' => $oauth_header,
                'Content-Type' => 'application/json'
            ],
            'body' => json_encode($data),
            'timeout' => 30
        ]);
        
        if (is_wp_error($response)) {
            throw new Exception('Twitter API Error: ' . $response->get_error_message());
        }
        
        $body = json_decode(wp_remote_retrieve_body($response), true);
        
        if (isset($body['errors'])) {
            throw new Exception('Twitter Error: ' . $body['errors'][0]['message']);
        }
        
        return [
            'platform' => 'Twitter',
            'post_id' => $body['data']['id'],
            'message' => 'Tweet published successfully'
        ];
    }
    
    private static function post_to_linkedin($message, $image_url = '', $link_url = '') {
        $access_token = hexagon_get_option('hexagon_social_linkedin_token');
        $person_id = hexagon_get_option('hexagon_social_linkedin_person_id');
        
        if (empty($access_token) || empty($person_id)) {
            throw new Exception('Brak konfiguracji LinkedIn - wymagany token i person ID');
        }
        
        $endpoint = 'https://api.linkedin.com/v2/ugcPosts';
        
        $data = [
            'author' => "urn:li:person:$person_id",
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
        
        // Add link if provided
        if (!empty($link_url)) {
            $data['specificContent']['com.linkedin.ugc.ShareContent']['shareMediaCategory'] = 'ARTICLE';
            $data['specificContent']['com.linkedin.ugc.ShareContent']['media'] = [
                [
                    'status' => 'READY',
                    'originalUrl' => $link_url
                ]
            ];
        }
        
        $response = wp_remote_post($endpoint, [
            'headers' => [
                'Authorization' => 'Bearer ' . $access_token,
                'Content-Type' => 'application/json',
                'X-Restli-Protocol-Version' => '2.0.0'
            ],
            'body' => json_encode($data),
            'timeout' => 30
        ]);
        
        if (is_wp_error($response)) {
            throw new Exception('LinkedIn API Error: ' . $response->get_error_message());
        }
        
        $response_code = wp_remote_retrieve_response_code($response);
        if ($response_code !== 201) {
            $body = json_decode(wp_remote_retrieve_body($response), true);
            throw new Exception('LinkedIn Error: ' . ($body['message'] ?? 'Unknown error'));
        }
        
        $headers = wp_remote_retrieve_headers($response);
        $post_url = $headers['location'] ?? '';
        
        return [
            'platform' => 'LinkedIn',
            'post_url' => $post_url,
            'message' => 'Post published successfully'
        ];
    }
    
    public static function schedule_post() {
        check_ajax_referer('hexagon_social_nonce', 'nonce');
        
        $platform = sanitize_text_field($_POST['platform']);
        $message = sanitize_textarea_field($_POST['message']);
        $image_url = esc_url_raw($_POST['image_url'] ?? '');
        $link_url = esc_url_raw($_POST['link_url'] ?? '');
        $schedule_time = sanitize_text_field($_POST['schedule_time']);
        
        $timestamp = strtotime($schedule_time);
        if ($timestamp < time()) {
            wp_send_json_error(['message' => 'Czas harmonogramu musi być w przyszłości']);
        }
        
        $post_data = [
            'platform' => $platform,
            'message' => $message,
            'image_url' => $image_url,
            'link_url' => $link_url,
            'schedule_time' => $schedule_time
        ];
        
        wp_schedule_single_event($timestamp, 'hexagon_social_post_scheduled', [$post_data]);
        
        hexagon_log('Social Media Scheduled', "Scheduled post for $platform at $schedule_time", 'info');
        wp_send_json_success(['message' => 'Post zaplanowany pomyślnie']);
    }
    
    public static function process_scheduled_post($post_data) {
        try {
            $result = self::post_to_platform(
                $post_data['platform'],
                $post_data['message'],
                $post_data['image_url'],
                $post_data['link_url']
            );
            
            hexagon_log('Scheduled Post Published', "Platform: {$post_data['platform']}", 'success');
        } catch (Exception $e) {
            hexagon_log('Scheduled Post Failed', $e->getMessage(), 'error');
            Hexagon_Email_Integration::send_error_alert('Scheduled social media post failed: ' . $e->getMessage());
        }
    }
    
    public static function auto_post_to_social($post_id, $post) {
        $auto_post_enabled = hexagon_get_option('hexagon_social_auto_post', false);
        if (!$auto_post_enabled) {
            return;
        }
        
        $enabled_platforms = hexagon_get_option('hexagon_social_auto_platforms', []);
        if (empty($enabled_platforms)) {
            return;
        }
        
        $message = self::generate_social_message($post);
        $image_url = self::get_post_featured_image($post_id);
        $link_url = get_permalink($post_id);
        
        foreach ($enabled_platforms as $platform) {
            try {
                self::post_to_platform($platform, $message, $image_url, $link_url);
                hexagon_log('Auto Post to Social', "Posted to $platform for post ID $post_id", 'success');
            } catch (Exception $e) {
                hexagon_log('Auto Post Failed', "Platform: $platform, Error: {$e->getMessage()}", 'error');
            }
        }
    }
    
    private static function generate_social_message($post) {
        $template = hexagon_get_option('hexagon_social_post_template', '{title} {excerpt} {link}');
        
        $message = str_replace([
            '{title}',
            '{excerpt}',
            '{author}',
            '{site_name}'
        ], [
            $post->post_title,
            wp_trim_words($post->post_excerpt ?: $post->post_content, 20),
            get_the_author_meta('display_name', $post->post_author),
            get_bloginfo('name')
        ], $template);
        
        return $message;
    }
    
    private static function get_post_featured_image($post_id) {
        $thumbnail_id = get_post_thumbnail_id($post_id);
        if ($thumbnail_id) {
            $image_url = wp_get_attachment_image_src($thumbnail_id, 'large');
            return $image_url ? $image_url[0] : '';
        }
        return '';
    }
    
    public static function test_social_connection() {
        check_ajax_referer('hexagon_social_nonce', 'nonce');
        
        $platform = sanitize_text_field($_POST['platform']);
        
        try {
            $test_message = 'Test post from Hexagon Automation - ' . current_time('mysql');
            $result = self::post_to_platform($platform, $test_message);
            wp_send_json_success(['message' => "Połączenie z $platform pomyślne"]);
        } catch (Exception $e) {
            wp_send_json_error(['message' => $e->getMessage()]);
        }
    }
    
    private static function generate_twitter_oauth_header($url, $method, $api_key, $api_secret, $access_token, $access_secret) {
        $oauth_params = [
            'oauth_consumer_key' => $api_key,
            'oauth_nonce' => wp_generate_password(32, false),
            'oauth_signature_method' => 'HMAC-SHA1',
            'oauth_timestamp' => time(),
            'oauth_token' => $access_token,
            'oauth_version' => '1.0'
        ];
        
        $base_string = $method . '&' . rawurlencode($url) . '&' . rawurlencode(http_build_query($oauth_params));
        $signing_key = rawurlencode($api_secret) . '&' . rawurlencode($access_secret);
        $oauth_params['oauth_signature'] = base64_encode(hash_hmac('sha1', $base_string, $signing_key, true));
        
        $oauth_header = 'OAuth ';
        foreach ($oauth_params as $key => $value) {
            $oauth_header .= $key . '="' . rawurlencode($value) . '", ';
        }
        
        return rtrim($oauth_header, ', ');
    }
    
    private static function upload_twitter_media($image_url, $api_key, $api_secret, $access_token, $access_secret) {
        // Download image
        $image_response = wp_remote_get($image_url);
        if (is_wp_error($image_response)) {
            return false;
        }
        
        $image_data = wp_remote_retrieve_body($image_response);
        $endpoint = 'https://upload.twitter.com/1.1/media/upload.json';
        
        $oauth_header = self::generate_twitter_oauth_header($endpoint, 'POST', $api_key, $api_secret, $access_token, $access_secret);
        
        $response = wp_remote_post($endpoint, [
            'headers' => [
                'Authorization' => $oauth_header
            ],
            'body' => [
                'media' => base64_encode($image_data)
            ],
            'timeout' => 60
        ]);
        
        if (is_wp_error($response)) {
            return false;
        }
        
        $body = json_decode(wp_remote_retrieve_body($response), true);
        return $body['media_id_string'] ?? false;
    }
    
    public static function process_scheduled_posts() {
        // This runs hourly to clean up completed scheduled posts
        hexagon_log('Social Media Cleanup', 'Processed scheduled posts cleanup', 'info');
    }
    
    public static function get_social_stats($platform = null) {
        $stats = hexagon_get_option('hexagon_social_stats', []);
        
        if ($platform) {
            return $stats[$platform] ?? ['posts' => 0, 'engagement' => 0, 'reach' => 0];
        }
        
        return $stats;
    }
}
