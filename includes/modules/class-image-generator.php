<?php
if (!defined('ABSPATH')) exit;

class Hexagon_Image_Generator {
    
    public static function init() {
        add_action('wp_ajax_hexagon_generate_image', [__CLASS__, 'ajax_generate_image']);
        add_action('wp_ajax_hexagon_test_image_provider', [__CLASS__, 'ajax_test_provider']);
        add_action('wp_ajax_hexagon_save_image_settings', [__CLASS__, 'ajax_save_settings']);
        add_action('wp_ajax_hexagon_get_image_stats', [__CLASS__, 'ajax_get_stats']);
        add_action('wp_ajax_hexagon_delete_generated_image', [__CLASS__, 'ajax_delete_image']);
        add_action('wp_ajax_hexagon_download_image', [__CLASS__, 'ajax_download_image']);
        
        // Background image generation
        add_action('hexagon_generate_image_background', [__CLASS__, 'process_background_generation'], 10, 1);
        
        // Cleanup old images
        if (!wp_next_scheduled('hexagon_cleanup_images')) {
            wp_schedule_event(time(), 'daily', 'hexagon_cleanup_images');
        }
        add_action('hexagon_cleanup_images', [__CLASS__, 'cleanup_old_images']);
    }
    
    public static function generate_image($prompt, $settings = []) {
        // Default settings
        $default_settings = [
            'provider' => 'dalle3',
            'size' => '1024x1024',
            'style' => 'realistic',
            'quality' => 'standard',
            'model' => 'dall-e-3',
            'negative_prompt' => '',
            'num_images' => 1,
            'seed' => null,
            'steps' => 20,
            'guidance_scale' => 7.5,
            'save_to_media_library' => true
        ];
        
        $settings = array_merge($default_settings, $settings);
        
        // Create generation record
        $image_id = self::create_image_record($prompt, $settings);
        
        if (is_wp_error($image_id)) {
            return $image_id;
        }
        
        try {
            // Generate image with selected provider
            $start_time = microtime(true);
            $generation_result = self::call_image_provider($settings['provider'], $prompt, $settings);
            $generation_time = microtime(true) - $start_time;
            
            if (is_wp_error($generation_result)) {
                self::update_image_status($image_id, 'failed', $generation_result->get_error_message());
                return $generation_result;
            }
            
            // Process generated images
            $processed_images = [];
            foreach ($generation_result['images'] as $image_data) {
                $processed_image = self::process_generated_image($image_data, $settings, $image_id);
                if (!is_wp_error($processed_image)) {
                    $processed_images[] = $processed_image;
                }
            }
            
            if (empty($processed_images)) {
                self::update_image_status($image_id, 'failed', 'Failed to process any generated images');
                return new WP_Error('processing_failed', 'Failed to process generated images');
            }
            
            // Update generation record with results
            self::update_image_record($image_id, [
                'file_path' => $processed_images[0]['file_path'],
                'file_url' => $processed_images[0]['file_url'],
                'wp_attachment_id' => $processed_images[0]['attachment_id'] ?? null,
                'generation_time' => $generation_time,
                'cost' => self::calculate_cost($settings['provider'], $settings),
                'metadata' => json_encode([
                    'revised_prompt' => $generation_result['revised_prompt'] ?? null,
                    'total_images' => count($processed_images),
                    'provider_metadata' => $generation_result['metadata'] ?? null
                ]),
                'status' => 'completed'
            ]);
            
            hexagon_log('Image Generation', "Successfully generated image using {$settings['provider']}", 'info');
            
            return [
                'image_id' => $image_id,
                'images' => $processed_images,
                'generation_time' => $generation_time,
                'cost' => self::calculate_cost($settings['provider'], $settings),
                'provider' => $settings['provider'],
                'revised_prompt' => $generation_result['revised_prompt'] ?? null
            ];
            
        } catch (Exception $e) {
            self::update_image_status($image_id, 'failed', $e->getMessage());
            hexagon_log('Image Generation', "Generation failed: " . $e->getMessage(), 'error');
            return new WP_Error('generation_failed', $e->getMessage());
        }
    }
    
    private static function create_image_record($prompt, $settings) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'hex_generated_images';
        $image_id = 'img_' . uniqid();
        
        $result = $wpdb->insert(
            $table,
            [
                'image_id' => $image_id,
                'provider' => $settings['provider'],
                'prompt' => $prompt,
                'negative_prompt' => $settings['negative_prompt'] ?? '',
                'style' => $settings['style'],
                'size' => $settings['size'],
                'model' => $settings['model'],
                'status' => 'processing'
            ],
            ['%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s']
        );
        
        if ($result === false) {
            return new WP_Error('db_error', 'Failed to create image generation record');
        }
        
        return $image_id;
    }
    
    private static function call_image_provider($provider, $prompt, $settings) {
        switch ($provider) {
            case 'dalle3':
                return self::call_dalle3($prompt, $settings);
            case 'midjourney':
                return self::call_midjourney($prompt, $settings);
            case 'stable_diffusion':
                return self::call_stable_diffusion($prompt, $settings);
            default:
                return new WP_Error('unsupported_provider', 'Unsupported image provider: ' . $provider);
        }
    }
    
    private static function call_dalle3($prompt, $settings) {
        $api_key = self::get_provider_api_key('dalle3');
        if (is_wp_error($api_key)) {
            return $api_key;
        }
        
        // DALL-E 3 API request
        $request_data = [
            'model' => 'dall-e-3',
            'prompt' => $prompt,
            'n' => min($settings['num_images'], 1), // DALL-E 3 only supports 1 image at a time
            'size' => $settings['size'],
            'quality' => $settings['quality'],
            'style' => $settings['style'] === 'realistic' ? 'natural' : 'vivid'
        ];
        
        $response = wp_remote_post('https://api.openai.com/v1/images/generations', [
            'headers' => [
                'Authorization' => 'Bearer ' . $api_key,
                'Content-Type' => 'application/json'
            ],
            'body' => json_encode($request_data),
            'timeout' => 120
        ]);
        
        if (is_wp_error($response)) {
            return new WP_Error('api_request_failed', 'DALL-E 3 API request failed: ' . $response->get_error_message());
        }
        
        $body = json_decode(wp_remote_retrieve_body($response), true);
        
        if (isset($body['error'])) {
            return new WP_Error('dalle3_error', 'DALL-E 3 API error: ' . $body['error']['message']);
        }
        
        if (!isset($body['data']) || empty($body['data'])) {
            return new WP_Error('invalid_response', 'Invalid response from DALL-E 3 API');
        }
        
        $images = [];
        foreach ($body['data'] as $image_data) {
            $images[] = [
                'url' => $image_data['url'],
                'revised_prompt' => $image_data['revised_prompt'] ?? null
            ];
        }
        
        return [
            'images' => $images,
            'revised_prompt' => $body['data'][0]['revised_prompt'] ?? null,
            'metadata' => [
                'model' => 'dall-e-3',
                'quality' => $settings['quality'],
                'style' => $request_data['style']
            ]
        ];
    }
    
    private static function call_midjourney($prompt, $settings) {
        // Note: Midjourney doesn't have an official API yet
        // This is a placeholder for when they release their API
        // For now, we'll simulate the response structure
        
        return new WP_Error('provider_unavailable', 'Midjourney API is not yet available. Please use DALL-E 3 or Stable Diffusion.');
        
        // Future implementation would look like this:
        /*
        $api_key = self::get_provider_api_key('midjourney');
        if (is_wp_error($api_key)) {
            return $api_key;
        }
        
        // When Midjourney API becomes available:
        $request_data = [
            'prompt' => $prompt,
            'aspect_ratio' => self::convert_size_to_aspect_ratio($settings['size']),
            'version' => '6.0',
            'quality' => $settings['quality'],
            'stylize' => $settings['style']
        ];
        
        // API call implementation...
        */
    }
    
    private static function call_stable_diffusion($prompt, $settings) {
        $api_key = self::get_provider_api_key('stable_diffusion');
        if (is_wp_error($api_key)) {
            return $api_key;
        }
        
        // Stability AI API request
        $request_data = [
            'text_prompts' => [
                [
                    'text' => $prompt,
                    'weight' => 1
                ]
            ],
            'cfg_scale' => $settings['guidance_scale'],
            'height' => (int) explode('x', $settings['size'])[1],
            'width' => (int) explode('x', $settings['size'])[0],
            'samples' => min($settings['num_images'], 10),
            'steps' => $settings['steps']
        ];
        
        if (!empty($settings['negative_prompt'])) {
            $request_data['text_prompts'][] = [
                'text' => $settings['negative_prompt'],
                'weight' => -1
            ];
        }
        
        if ($settings['seed']) {
            $request_data['seed'] = (int) $settings['seed'];
        }
        
        $response = wp_remote_post('https://api.stability.ai/v1/generation/stable-diffusion-xl-1024-v1-0/text-to-image', [
            'headers' => [
                'Authorization' => 'Bearer ' . $api_key,
                'Content-Type' => 'application/json',
                'Accept' => 'application/json'
            ],
            'body' => json_encode($request_data),
            'timeout' => 120
        ]);
        
        if (is_wp_error($response)) {
            return new WP_Error('api_request_failed', 'Stable Diffusion API request failed: ' . $response->get_error_message());
        }
        
        $body = json_decode(wp_remote_retrieve_body($response), true);
        
        if (isset($body['message'])) {
            return new WP_Error('stable_diffusion_error', 'Stable Diffusion API error: ' . $body['message']);
        }
        
        if (!isset($body['artifacts']) || empty($body['artifacts'])) {
            return new WP_Error('invalid_response', 'Invalid response from Stable Diffusion API');
        }
        
        $images = [];
        foreach ($body['artifacts'] as $artifact) {
            if ($artifact['finishReason'] === 'SUCCESS' && $artifact['base64']) {
                $images[] = [
                    'base64' => $artifact['base64'],
                    'seed' => $artifact['seed'] ?? null
                ];
            }
        }
        
        return [
            'images' => $images,
            'metadata' => [
                'model' => 'stable-diffusion-xl-1024-v1-0',
                'cfg_scale' => $settings['guidance_scale'],
                'steps' => $settings['steps']
            ]
        ];
    }
    
    private static function process_generated_image($image_data, $settings, $image_id) {
        try {
            // Download/decode image
            if (isset($image_data['url'])) {
                // Download from URL (DALL-E 3)
                $image_content = wp_remote_get($image_data['url'], ['timeout' => 60]);
                if (is_wp_error($image_content)) {
                    return new WP_Error('download_failed', 'Failed to download image: ' . $image_content->get_error_message());
                }
                $image_binary = wp_remote_retrieve_body($image_content);
            } elseif (isset($image_data['base64'])) {
                // Decode base64 (Stable Diffusion)
                $image_binary = base64_decode($image_data['base64']);
            } else {
                return new WP_Error('invalid_image_data', 'No valid image data provided');
            }
            
            if (empty($image_binary)) {
                return new WP_Error('empty_image', 'Downloaded image is empty');
            }
            
            // Generate filename
            $upload_dir = wp_upload_dir();
            $filename = 'hexagon-generated-' . $image_id . '-' . uniqid() . '.png';
            $file_path = $upload_dir['path'] . '/' . $filename;
            $file_url = $upload_dir['url'] . '/' . $filename;
            
            // Save image file
            $saved = file_put_contents($file_path, $image_binary);
            if ($saved === false) {
                return new WP_Error('save_failed', 'Failed to save image file');
            }
            
            $result = [
                'file_path' => $file_path,
                'file_url' => $file_url,
                'filename' => $filename
            ];
            
            // Add to WordPress media library if requested
            if ($settings['save_to_media_library']) {
                $attachment_id = self::add_to_media_library($file_path, $filename, $settings);
                if (!is_wp_error($attachment_id)) {
                    $result['attachment_id'] = $attachment_id;
                    $result['media_url'] = wp_get_attachment_url($attachment_id);
                }
            }
            
            return $result;
            
        } catch (Exception $e) {
            return new WP_Error('processing_error', 'Image processing failed: ' . $e->getMessage());
        }
    }
    
    private static function add_to_media_library($file_path, $filename, $settings) {
        $wp_filetype = wp_check_filetype($filename, null);
        
        $attachment = [
            'post_mime_type' => $wp_filetype['type'],
            'post_title' => 'Generated Image: ' . sanitize_title($settings['provider']),
            'post_content' => 'AI Generated Image - Prompt: ' . substr($settings['prompt'] ?? '', 0, 100),
            'post_status' => 'inherit'
        ];
        
        $attachment_id = wp_insert_attachment($attachment, $file_path);
        
        if (is_wp_error($attachment_id)) {
            return $attachment_id;
        }
        
        // Generate metadata
        require_once ABSPATH . 'wp-admin/includes/image.php';
        $attachment_data = wp_generate_attachment_metadata($attachment_id, $file_path);
        wp_update_attachment_metadata($attachment_id, $attachment_data);
        
        // Add custom meta
        update_post_meta($attachment_id, '_hexagon_generated', true);
        update_post_meta($attachment_id, '_hexagon_provider', $settings['provider']);
        update_post_meta($attachment_id, '_hexagon_prompt', $settings['prompt'] ?? '');
        
        return $attachment_id;
    }
    
    private static function calculate_cost($provider, $settings) {
        // Cost calculations based on provider pricing
        $costs = [
            'dalle3' => [
                '1024x1024' => 0.040,
                '1024x1792' => 0.080,
                '1792x1024' => 0.080
            ],
            'stable_diffusion' => [
                'base_cost' => 0.002,  // per step
                'multiplier' => 1
            ],
            'midjourney' => [
                'base_cost' => 0.008  // estimated
            ]
        ];
        
        switch ($provider) {
            case 'dalle3':
                return $costs['dalle3'][$settings['size']] ?? 0.040;
                
            case 'stable_diffusion':
                $steps = $settings['steps'] ?? 20;
                $images = $settings['num_images'] ?? 1;
                return $costs['stable_diffusion']['base_cost'] * $steps * $images;
                
            case 'midjourney':
                return $costs['midjourney']['base_cost'];
                
            default:
                return 0;
        }
    }
    
    private static function get_provider_api_key($provider) {
        $key_map = [
            'dalle3' => 'hexagon_ai_chatgpt_api_key',
            'stable_diffusion' => 'hexagon_ai_stability_api_key',
            'midjourney' => 'hexagon_ai_midjourney_api_key'
        ];
        
        $option_key = $key_map[$provider] ?? null;
        if (!$option_key) {
            return new WP_Error('invalid_provider', 'Invalid image provider: ' . $provider);
        }
        
        $api_key = get_option($option_key);
        if (empty($api_key)) {
            return new WP_Error('missing_api_key', 'API key not configured for provider: ' . $provider);
        }
        
        return $api_key;
    }
    
    private static function update_image_record($image_id, $data) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'hex_generated_images';
        
        if (isset($data['status'])) {
            $data['completed_at'] = current_time('mysql');
        }
        
        $wpdb->update(
            $table,
            $data,
            ['image_id' => $image_id],
            null,
            ['%s']
        );
    }
    
    private static function update_image_status($image_id, $status, $error_message = null) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'hex_generated_images';
        
        $data = [
            'status' => $status,
            'completed_at' => current_time('mysql')
        ];
        
        if ($error_message) {
            $data['metadata'] = json_encode(['error' => $error_message]);
        }
        
        $wpdb->update(
            $table,
            $data,
            ['image_id' => $image_id],
            ['%s', '%s', '%s'],
            ['%s']
        );
    }
    
    public static function cleanup_old_images() {
        global $wpdb;
        
        $table = $wpdb->prefix . 'hex_generated_images';
        
        // Delete images older than 30 days
        $old_images = $wpdb->get_results($wpdb->prepare("
            SELECT image_id, file_path, wp_attachment_id 
            FROM {$table} 
            WHERE created_at < %s
        ", date('Y-m-d H:i:s', strtotime('-30 days'))), ARRAY_A);
        
        foreach ($old_images as $image) {
            // Delete file
            if ($image['file_path'] && file_exists($image['file_path'])) {
                unlink($image['file_path']);
            }
            
            // Delete from media library
            if ($image['wp_attachment_id']) {
                wp_delete_attachment($image['wp_attachment_id'], true);
            }
            
            // Delete from database
            $wpdb->delete($table, ['image_id' => $image['image_id']], ['%s']);
        }
        
        if (!empty($old_images)) {
            hexagon_log('Image Cleanup', 'Cleaned up ' . count($old_images) . ' old generated images', 'info');
        }
    }
    
    // AJAX Handlers
    public static function ajax_generate_image() {
        check_ajax_referer('hexagon_nonce', 'nonce');
        
        if (!current_user_can('edit_posts')) {
            wp_send_json_error('Insufficient permissions');
            return;
        }
        
        $prompt = sanitize_textarea_field($_POST['prompt'] ?? '');
        $settings = array_map('sanitize_text_field', $_POST['settings'] ?? []);
        
        if (empty($prompt)) {
            wp_send_json_error('Prompt is required');
            return;
        }
        
        $result = self::generate_image($prompt, $settings);
        
        if (is_wp_error($result)) {
            wp_send_json_error($result->get_error_message());
        } else {
            wp_send_json_success($result);
        }
    }
    
    public static function ajax_test_provider() {
        check_ajax_referer('hexagon_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
            return;
        }
        
        $provider = sanitize_text_field($_POST['provider'] ?? '');
        
        if (empty($provider)) {
            wp_send_json_error('Provider name is required');
            return;
        }
        
        // Test with a simple prompt
        $test_prompt = "A simple red apple on a white background";
        $test_settings = [
            'provider' => $provider,
            'size' => '1024x1024',
            'num_images' => 1,
            'save_to_media_library' => false
        ];
        
        $result = self::generate_image($test_prompt, $test_settings);
        
        if (is_wp_error($result)) {
            wp_send_json_error($result->get_error_message());
        } else {
            wp_send_json_success([
                'message' => 'Image provider test successful',
                'test_image' => $result['images'][0] ?? null,
                'generation_time' => $result['generation_time'],
                'cost' => $result['cost']
            ]);
        }
    }
    
    public static function ajax_save_settings() {
        check_ajax_referer('hexagon_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
            return;
        }
        
        $provider = sanitize_text_field($_POST['provider'] ?? '');
        $api_key = sanitize_text_field($_POST['api_key'] ?? '');
        
        if (empty($provider)) {
            wp_send_json_error('Provider name is required');
            return;
        }
        
        $key_map = [
            'dalle3' => 'hexagon_ai_chatgpt_api_key',
            'stable_diffusion' => 'hexagon_ai_stability_api_key',
            'midjourney' => 'hexagon_ai_midjourney_api_key'
        ];
        
        $option_key = $key_map[$provider] ?? null;
        if (!$option_key) {
            wp_send_json_error('Invalid provider');
            return;
        }
        
        update_option($option_key, $api_key);
        wp_send_json_success('Settings saved successfully');
    }
    
    public static function ajax_get_stats() {
        check_ajax_referer('hexagon_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
            return;
        }
        
        global $wpdb;
        
        $table = $wpdb->prefix . 'hex_generated_images';
        
        // Get generation stats
        $total_images = $wpdb->get_var("SELECT COUNT(*) FROM {$table}");
        $successful_images = $wpdb->get_var("SELECT COUNT(*) FROM {$table} WHERE status = 'completed'");
        $total_cost = $wpdb->get_var("SELECT SUM(cost) FROM {$table} WHERE status = 'completed'");
        $avg_generation_time = $wpdb->get_var("SELECT AVG(generation_time) FROM {$table} WHERE status = 'completed' AND generation_time > 0");
        
        // Provider breakdown
        $provider_stats = $wpdb->get_results("
            SELECT provider, COUNT(*) as count, SUM(cost) as total_cost, AVG(generation_time) as avg_time
            FROM {$table} 
            WHERE status = 'completed'
            GROUP BY provider
        ", ARRAY_A);
        
        // Recent generations
        $recent_images = $wpdb->get_results("
            SELECT image_id, prompt, provider, size, cost, generation_time, created_at, status 
            FROM {$table} 
            ORDER BY created_at DESC 
            LIMIT 10
        ", ARRAY_A);
        
        wp_send_json_success([
            'total_images' => (int) $total_images,
            'successful_images' => (int) $successful_images,
            'total_cost' => round($total_cost, 4),
            'average_generation_time' => round($avg_generation_time, 2),
            'provider_stats' => $provider_stats,
            'recent_images' => $recent_images
        ]);
    }
    
    public static function ajax_delete_image() {
        check_ajax_referer('hexagon_nonce', 'nonce');
        
        if (!current_user_can('edit_posts')) {
            wp_send_json_error('Insufficient permissions');
            return;
        }
        
        global $wpdb;
        $table = $wpdb->prefix . 'hex_generated_images';
        
        $image_id = sanitize_text_field($_POST['image_id'] ?? '');
        
        // Get image data
        $image = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$table} WHERE image_id = %s",
            $image_id
        ), ARRAY_A);
        
        if (!$image) {
            wp_send_json_error('Image not found');
            return;
        }
        
        // Delete file
        if ($image['file_path'] && file_exists($image['file_path'])) {
            unlink($image['file_path']);
        }
        
        // Delete from media library
        if ($image['wp_attachment_id']) {
            wp_delete_attachment($image['wp_attachment_id'], true);
        }
        
        // Delete from database
        $result = $wpdb->delete($table, ['image_id' => $image_id], ['%s']);
        
        if ($result === false) {
            wp_send_json_error('Failed to delete image');
        } else {
            wp_send_json_success('Image deleted successfully');
        }
    }
    
    public static function ajax_download_image() {
        check_ajax_referer('hexagon_nonce', 'nonce');
        
        if (!current_user_can('edit_posts')) {
            wp_send_json_error('Insufficient permissions');
            return;
        }
        
        global $wpdb;
        $table = $wpdb->prefix . 'hex_generated_images';
        
        $image_id = sanitize_text_field($_POST['image_id'] ?? '');
        
        // Get image data
        $image = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$table} WHERE image_id = %s",
            $image_id
        ), ARRAY_A);
        
        if (!$image || !file_exists($image['file_path'])) {
            wp_send_json_error('Image not found');
            return;
        }
        
        wp_send_json_success([
            'download_url' => $image['file_url'],
            'filename' => basename($image['file_path'])
        ]);
    }
}