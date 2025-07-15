<?php
if (!defined('ABSPATH')) exit;

class Hexagon_AI_Content_Generator {
    
    public static function init() {
        add_action('wp_ajax_hexagon_generate_content', [__CLASS__, 'ajax_generate_content']);
        add_action('wp_ajax_hexagon_test_ai_provider', [__CLASS__, 'ajax_test_provider']);
        add_action('wp_ajax_hexagon_save_ai_settings', [__CLASS__, 'ajax_save_settings']);
        add_action('wp_ajax_hexagon_get_ai_stats', [__CLASS__, 'ajax_get_stats']);
        add_action('wp_ajax_hexagon_update_provider_config', [__CLASS__, 'ajax_update_provider_config']);
        
        // Background content generation
        add_action('hexagon_generate_content_background', [__CLASS__, 'process_background_generation'], 10, 1);
        
        // Content quality analysis
        add_action('hexagon_analyze_content_quality', [__CLASS__, 'analyze_content_quality'], 10, 1);
    }
    
    public static function generate_content($prompt, $settings = []) {
        // Default settings
        $default_settings = [
            'provider' => 'chatgpt',
            'content_type' => 'article',
            'content_length' => 'standard',
            'language' => 'en',
            'temperature' => 0.7,
            'max_tokens' => 2000,
            'style' => 'professional',
            'seo_optimized' => true,
            'fact_check' => false,
            'include_images' => false
        ];
        
        $settings = array_merge($default_settings, $settings);
        
        // Create generation record
        $generation_id = self::create_generation_record($prompt, $settings);
        
        if (is_wp_error($generation_id)) {
            return $generation_id;
        }
        
        try {
            // Select provider based on settings
            $provider = self::get_provider($settings['provider']);
            
            if (is_wp_error($provider)) {
                self::update_generation_status($generation_id, 'failed', $provider->get_error_message());
                return $provider;
            }
            
            // Build enhanced prompt
            $enhanced_prompt = self::build_enhanced_prompt($prompt, $settings);
            
            // Generate content with selected provider
            $start_time = microtime(true);
            $content_result = self::call_ai_provider($provider, $enhanced_prompt, $settings);
            $generation_time = microtime(true) - $start_time;
            
            if (is_wp_error($content_result)) {
                self::update_generation_status($generation_id, 'failed', $content_result->get_error_message());
                return $content_result;
            }
            
            // Process and enhance the generated content
            $processed_content = self::process_generated_content($content_result['content'], $settings);
            
            // Calculate quality scores
            $quality_score = self::calculate_quality_score($processed_content);
            $seo_score = self::calculate_seo_score($processed_content, $prompt);
            
            // Update generation record with results
            self::update_generation_record($generation_id, [
                'generated_content' => $processed_content,
                'tokens_used' => $content_result['tokens_used'] ?? 0,
                'generation_time' => $generation_time,
                'cost' => self::calculate_cost($settings['provider'], $content_result['tokens_used'] ?? 0),
                'quality_score' => $quality_score,
                'seo_score' => $seo_score,
                'status' => 'completed'
            ]);
            
            // Update provider usage statistics
            self::update_provider_stats($settings['provider'], $content_result['tokens_used'] ?? 0, $generation_time);
            
            // Perform fact-checking if enabled
            if ($settings['fact_check']) {
                self::schedule_fact_check($generation_id);
            }
            
            hexagon_log('AI Content Generation', "Successfully generated content using {$settings['provider']}", 'info');
            
            return [
                'generation_id' => $generation_id,
                'content' => $processed_content,
                'tokens_used' => $content_result['tokens_used'] ?? 0,
                'generation_time' => $generation_time,
                'quality_score' => $quality_score,
                'seo_score' => $seo_score,
                'provider' => $settings['provider'],
                'cost' => self::calculate_cost($settings['provider'], $content_result['tokens_used'] ?? 0)
            ];
            
        } catch (Exception $e) {
            self::update_generation_status($generation_id, 'failed', $e->getMessage());
            hexagon_log('AI Content Generation', "Generation failed: " . $e->getMessage(), 'error');
            return new WP_Error('generation_failed', $e->getMessage());
        }
    }
    
    private static function create_generation_record($prompt, $settings) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'hex_content_generation';
        $generation_id = 'gen_' . uniqid();
        
        $result = $wpdb->insert(
            $table,
            [
                'generation_id' => $generation_id,
                'prompt' => $prompt,
                'content_type' => $settings['content_type'],
                'content_length' => $settings['content_length'],
                'language' => $settings['language'],
                'ai_provider' => $settings['provider'],
                'model' => self::get_model_for_provider($settings['provider']),
                'status' => 'processing'
            ],
            ['%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s']
        );
        
        if ($result === false) {
            return new WP_Error('db_error', 'Failed to create generation record');
        }
        
        return $generation_id;
    }
    
    private static function build_enhanced_prompt($prompt, $settings) {
        $enhanced_prompt = '';
        
        // Add role and context based on content type
        $content_type_prompts = [
            'article' => "You are a professional content writer creating a comprehensive article.",
            'guide' => "You are an expert guide writer creating a step-by-step guide.",
            'howto' => "You are a technical writer creating a clear how-to tutorial.",
            'review' => "You are a balanced reviewer providing honest analysis.",
            'news' => "You are a journalist writing breaking news.",
            'blog' => "You are a blogger writing engaging content.",
            'social' => "You are a social media manager creating engaging posts.",
            'email' => "You are an email marketing specialist creating compelling emails.",
            'ad' => "You are an advertising copywriter creating persuasive ads.",
            'product' => "You are a product description writer highlighting benefits."
        ];
        
        if (isset($content_type_prompts[$settings['content_type']])) {
            $enhanced_prompt .= $content_type_prompts[$settings['content_type']] . "\n\n";
        }
        
        // Add length specifications
        $length_specs = [
            'short' => "Write a concise piece (400-500 words).",
            'standard' => "Write a standard-length piece (800-1000 words).",
            'long' => "Write a comprehensive piece (1500-2000 words).",
            'extra_long' => "Write an in-depth piece (2500+ words)."
        ];
        
        if (isset($length_specs[$settings['content_length']])) {
            $enhanced_prompt .= $length_specs[$settings['content_length']] . "\n";
        }
        
        // Add SEO requirements
        if ($settings['seo_optimized']) {
            $enhanced_prompt .= "Make the content SEO-optimized with proper headings, keywords, and structure.\n";
        }
        
        // Add language specification
        if ($settings['language'] !== 'en') {
            $language_names = [
                'es' => 'Spanish',
                'fr' => 'French',
                'de' => 'German',
                'it' => 'Italian',
                'pt' => 'Portuguese',
                'pl' => 'Polish',
                'nl' => 'Dutch',
                'ru' => 'Russian',
                'ja' => 'Japanese',
                'ko' => 'Korean',
                'zh' => 'Chinese'
            ];
            
            $language_name = $language_names[$settings['language']] ?? $settings['language'];
            $enhanced_prompt .= "Write the content in {$language_name}.\n";
        }
        
        // Add style specifications
        $style_specs = [
            'professional' => "Use a professional, authoritative tone.",
            'casual' => "Use a casual, conversational tone.",
            'formal' => "Use a formal, academic tone.",
            'friendly' => "Use a friendly, approachable tone.",
            'persuasive' => "Use a persuasive, compelling tone.",
            'informative' => "Use an informative, educational tone."
        ];
        
        if (isset($style_specs[$settings['style']])) {
            $enhanced_prompt .= $style_specs[$settings['style']] . "\n";
        }
        
        $enhanced_prompt .= "\nUser Request: " . $prompt;
        
        return $enhanced_prompt;
    }
    
    private static function call_ai_provider($provider, $prompt, $settings) {
        $provider_name = $provider['provider_name'];
        $model_settings = json_decode($provider['model_settings'], true);
        
        switch ($provider_name) {
            case 'chatgpt':
                return self::call_openai($provider, $prompt, $settings, $model_settings);
            case 'claude':
                return self::call_anthropic($provider, $prompt, $settings, $model_settings);
            case 'perplexity':
                return self::call_perplexity($provider, $prompt, $settings, $model_settings);
            default:
                return new WP_Error('unsupported_provider', 'Unsupported AI provider: ' . $provider_name);
        }
    }
    
    private static function call_openai($provider, $prompt, $settings, $model_settings) {
        $api_key = $provider['api_key'];
        if (empty($api_key)) {
            return new WP_Error('missing_api_key', 'OpenAI API key not configured');
        }
        
        $request_data = [
            'model' => $model_settings['model'] ?? 'gpt-4',
            'messages' => [
                ['role' => 'user', 'content' => $prompt]
            ],
            'temperature' => $settings['temperature'] ?? $model_settings['temperature'] ?? 0.7,
            'max_tokens' => $settings['max_tokens'] ?? $model_settings['max_tokens'] ?? 2000,
            'top_p' => $model_settings['top_p'] ?? 1,
            'frequency_penalty' => $model_settings['frequency_penalty'] ?? 0,
            'presence_penalty' => $model_settings['presence_penalty'] ?? 0
        ];
        
        $response = wp_remote_post('https://api.openai.com/v1/chat/completions', [
            'headers' => [
                'Authorization' => 'Bearer ' . $api_key,
                'Content-Type' => 'application/json'
            ],
            'body' => json_encode($request_data),
            'timeout' => 60
        ]);
        
        if (is_wp_error($response)) {
            return new WP_Error('api_request_failed', 'OpenAI API request failed: ' . $response->get_error_message());
        }
        
        $body = json_decode(wp_remote_retrieve_body($response), true);
        
        if (isset($body['error'])) {
            return new WP_Error('openai_error', 'OpenAI API error: ' . $body['error']['message']);
        }
        
        if (!isset($body['choices'][0]['message']['content'])) {
            return new WP_Error('invalid_response', 'Invalid response from OpenAI API');
        }
        
        return [
            'content' => trim($body['choices'][0]['message']['content']),
            'tokens_used' => $body['usage']['total_tokens'] ?? 0,
            'prompt_tokens' => $body['usage']['prompt_tokens'] ?? 0,
            'completion_tokens' => $body['usage']['completion_tokens'] ?? 0
        ];
    }
    
    private static function call_anthropic($provider, $prompt, $settings, $model_settings) {
        $api_key = $provider['api_key'];
        if (empty($api_key)) {
            return new WP_Error('missing_api_key', 'Claude API key not configured');
        }
        
        $request_data = [
            'model' => $model_settings['model'] ?? 'claude-3-sonnet-20240229',
            'max_tokens' => $settings['max_tokens'] ?? $model_settings['max_tokens'] ?? 2000,
            'temperature' => $settings['temperature'] ?? $model_settings['temperature'] ?? 0.7,
            'messages' => [
                ['role' => 'user', 'content' => $prompt]
            ]
        ];
        
        $response = wp_remote_post('https://api.anthropic.com/v1/messages', [
            'headers' => [
                'x-api-key' => $api_key,
                'Content-Type' => 'application/json',
                'anthropic-version' => '2023-06-01'
            ],
            'body' => json_encode($request_data),
            'timeout' => 60
        ]);
        
        if (is_wp_error($response)) {
            return new WP_Error('api_request_failed', 'Claude API request failed: ' . $response->get_error_message());
        }
        
        $body = json_decode(wp_remote_retrieve_body($response), true);
        
        if (isset($body['error'])) {
            return new WP_Error('claude_error', 'Claude API error: ' . $body['error']['message']);
        }
        
        if (!isset($body['content'][0]['text'])) {
            return new WP_Error('invalid_response', 'Invalid response from Claude API');
        }
        
        return [
            'content' => trim($body['content'][0]['text']),
            'tokens_used' => $body['usage']['input_tokens'] + $body['usage']['output_tokens'],
            'prompt_tokens' => $body['usage']['input_tokens'],
            'completion_tokens' => $body['usage']['output_tokens']
        ];
    }
    
    private static function call_perplexity($provider, $prompt, $settings, $model_settings) {
        $api_key = $provider['api_key'];
        if (empty($api_key)) {
            return new WP_Error('missing_api_key', 'Perplexity API key not configured');
        }
        
        $request_data = [
            'model' => $model_settings['model'] ?? 'llama-3-sonar-large-32k-online',
            'messages' => [
                ['role' => 'user', 'content' => $prompt]
            ],
            'temperature' => $settings['temperature'] ?? $model_settings['temperature'] ?? 0.7,
            'max_tokens' => $settings['max_tokens'] ?? $model_settings['max_tokens'] ?? 2000
        ];
        
        $response = wp_remote_post('https://api.perplexity.ai/chat/completions', [
            'headers' => [
                'Authorization' => 'Bearer ' . $api_key,
                'Content-Type' => 'application/json'
            ],
            'body' => json_encode($request_data),
            'timeout' => 60
        ]);
        
        if (is_wp_error($response)) {
            return new WP_Error('api_request_failed', 'Perplexity API request failed: ' . $response->get_error_message());
        }
        
        $body = json_decode(wp_remote_retrieve_body($response), true);
        
        if (isset($body['error'])) {
            return new WP_Error('perplexity_error', 'Perplexity API error: ' . $body['error']['message']);
        }
        
        if (!isset($body['choices'][0]['message']['content'])) {
            return new WP_Error('invalid_response', 'Invalid response from Perplexity API');
        }
        
        return [
            'content' => trim($body['choices'][0]['message']['content']),
            'tokens_used' => $body['usage']['total_tokens'] ?? 0,
            'prompt_tokens' => $body['usage']['prompt_tokens'] ?? 0,
            'completion_tokens' => $body['usage']['completion_tokens'] ?? 0
        ];
    }
    
    private static function process_generated_content($content, $settings) {
        // Clean and format content
        $content = trim($content);
        
        // Remove any unwanted formatting
        $content = preg_replace('/\n{3,}/', "\n\n", $content);
        
        // Add proper paragraph breaks for WordPress
        $content = wpautop($content);
        
        // If SEO optimized, ensure proper heading structure
        if ($settings['seo_optimized']) {
            $content = self::optimize_heading_structure($content);
        }
        
        // Add meta descriptions and keywords if requested
        if ($settings['content_type'] === 'article' && $settings['seo_optimized']) {
            $content = self::add_seo_metadata($content);
        }
        
        return $content;
    }
    
    private static function optimize_heading_structure($content) {
        // Ensure we have proper H2, H3 hierarchy
        $content = preg_replace('/^# (.+)$/m', '<h2>$1</h2>', $content);
        $content = preg_replace('/^## (.+)$/m', '<h3>$1</h3>', $content);
        $content = preg_replace('/^### (.+)$/m', '<h4>$1</h4>', $content);
        
        return $content;
    }
    
    private static function add_seo_metadata($content) {
        // Extract first meaningful sentence for meta description
        $sentences = preg_split('/[.!?]+/', strip_tags($content));
        $meta_description = '';
        
        foreach ($sentences as $sentence) {
            $sentence = trim($sentence);
            if (strlen($sentence) > 20) {
                $meta_description = substr($sentence, 0, 155) . '...';
                break;
            }
        }
        
        // This would be added as post meta in the calling function
        return $content;
    }
    
    private static function calculate_quality_score($content) {
        $score = 0;
        
        // Length check (optimal 800-2000 words)
        $word_count = str_word_count(strip_tags($content));
        if ($word_count >= 800 && $word_count <= 2000) {
            $score += 25;
        } elseif ($word_count >= 500) {
            $score += 15;
        }
        
        // Readability check
        $sentences = preg_split('/[.!?]+/', strip_tags($content));
        $avg_sentence_length = $word_count / max(count($sentences), 1);
        if ($avg_sentence_length >= 15 && $avg_sentence_length <= 25) {
            $score += 20;
        }
        
        // Structure check (headings)
        if (preg_match_all('/<h[2-4]>/', $content) >= 2) {
            $score += 20;
        }
        
        // Paragraph structure
        $paragraphs = explode('</p>', $content);
        if (count($paragraphs) >= 3) {
            $score += 15;
        }
        
        // Content uniqueness (basic check)
        $score += 20; // Assume AI content is unique
        
        return min($score, 100);
    }
    
    private static function calculate_seo_score($content, $prompt) {
        $score = 0;
        $content_text = strip_tags($content);
        
        // Extract keywords from prompt
        $keywords = self::extract_keywords($prompt);
        
        // Keyword density check
        foreach ($keywords as $keyword) {
            $keyword_count = substr_count(strtolower($content_text), strtolower($keyword));
            $total_words = str_word_count($content_text);
            $density = ($keyword_count / $total_words) * 100;
            
            if ($density >= 0.5 && $density <= 3) {
                $score += 10;
            }
        }
        
        // Meta title length (assuming first heading is title)
        if (preg_match('/<h[1-2]>(.+?)<\/h[1-2]>/', $content, $matches)) {
            $title_length = strlen(strip_tags($matches[1]));
            if ($title_length >= 30 && $title_length <= 60) {
                $score += 20;
            }
        }
        
        // Internal structure
        if (preg_match_all('/<h[2-4]>/', $content) >= 2) {
            $score += 20;
        }
        
        // Content length for SEO
        $word_count = str_word_count($content_text);
        if ($word_count >= 800) {
            $score += 30;
        } elseif ($word_count >= 500) {
            $score += 20;
        }
        
        // Image optimization potential
        if (strpos($content, '<img') !== false || strpos($content, 'image') !== false) {
            $score += 10;
        }
        
        return min($score, 100);
    }
    
    private static function extract_keywords($prompt) {
        // Simple keyword extraction
        $words = str_word_count(strtolower($prompt), 1);
        $stop_words = ['the', 'a', 'an', 'and', 'or', 'but', 'in', 'on', 'at', 'to', 'for', 'of', 'with', 'by', 'how', 'what', 'where', 'when', 'why', 'write', 'create', 'generate'];
        
        $keywords = array_filter($words, function($word) use ($stop_words) {
            return strlen($word) > 3 && !in_array($word, $stop_words);
        });
        
        return array_slice(array_values($keywords), 0, 5);
    }
    
    private static function calculate_cost($provider, $tokens_used) {
        // Cost per 1K tokens (approximate)
        $costs = [
            'chatgpt' => 0.03, // GPT-4 pricing
            'claude' => 0.015, // Claude 3 Sonnet pricing
            'perplexity' => 0.02 // Perplexity pricing
        ];
        
        $cost_per_1k = $costs[$provider] ?? 0.02;
        return ($tokens_used / 1000) * $cost_per_1k;
    }
    
    private static function get_provider($provider_name) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'hex_ai_providers';
        $provider = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$table} WHERE provider_name = %s AND is_enabled = 1",
            $provider_name
        ), ARRAY_A);
        
        if (!$provider) {
            return new WP_Error('provider_not_found', 'AI provider not found or not enabled: ' . $provider_name);
        }
        
        if (empty($provider['api_key'])) {
            return new WP_Error('missing_api_key', 'API key not configured for provider: ' . $provider_name);
        }
        
        return $provider;
    }
    
    private static function get_model_for_provider($provider_name) {
        $models = [
            'chatgpt' => 'gpt-4',
            'claude' => 'claude-3-sonnet-20240229',
            'perplexity' => 'llama-3-sonar-large-32k-online'
        ];
        
        return $models[$provider_name] ?? $provider_name;
    }
    
    private static function update_generation_record($generation_id, $data) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'hex_content_generation';
        
        if (isset($data['status'])) {
            $data['completed_at'] = current_time('mysql');
        }
        
        $wpdb->update(
            $table,
            $data,
            ['generation_id' => $generation_id],
            null,
            ['%s']
        );
    }
    
    private static function update_generation_status($generation_id, $status, $error_message = null) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'hex_content_generation';
        
        $data = [
            'status' => $status,
            'completed_at' => current_time('mysql')
        ];
        
        if ($error_message) {
            $data['generated_content'] = 'Error: ' . $error_message;
        }
        
        $wpdb->update(
            $table,
            $data,
            ['generation_id' => $generation_id],
            ['%s', '%s', '%s'],
            ['%s']
        );
    }
    
    private static function update_provider_stats($provider_name, $tokens_used, $generation_time) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'hex_ai_providers';
        
        // Get current stats
        $current_stats = $wpdb->get_var($wpdb->prepare(
            "SELECT usage_stats FROM {$table} WHERE provider_name = %s",
            $provider_name
        ));
        
        $stats = json_decode($current_stats, true) ?: [];
        
        // Update stats
        $stats['total_requests'] = ($stats['total_requests'] ?? 0) + 1;
        $stats['total_tokens'] = ($stats['total_tokens'] ?? 0) + $tokens_used;
        $stats['total_time'] = ($stats['total_time'] ?? 0) + $generation_time;
        $stats['last_request'] = current_time('mysql');
        
        // Monthly stats
        $month_key = date('Y-m');
        $stats['monthly'][$month_key]['requests'] = ($stats['monthly'][$month_key]['requests'] ?? 0) + 1;
        $stats['monthly'][$month_key]['tokens'] = ($stats['monthly'][$month_key]['tokens'] ?? 0) + $tokens_used;
        
        $wpdb->update(
            $table,
            ['usage_stats' => json_encode($stats)],
            ['provider_name' => $provider_name],
            ['%s'],
            ['%s']
        );
    }
    
    // AJAX Handlers
    public static function ajax_generate_content() {
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
        
        $result = self::generate_content($prompt, $settings);
        
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
        
        $provider_name = sanitize_text_field($_POST['provider'] ?? '');
        
        if (empty($provider_name)) {
            wp_send_json_error('Provider name is required');
            return;
        }
        
        $provider = self::get_provider($provider_name);
        
        if (is_wp_error($provider)) {
            wp_send_json_error($provider->get_error_message());
            return;
        }
        
        // Test with a simple prompt
        $test_prompt = "Write a single sentence about artificial intelligence.";
        $test_settings = [
            'provider' => $provider_name,
            'max_tokens' => 50,
            'temperature' => 0.7
        ];
        
        $result = self::call_ai_provider($provider, $test_prompt, $test_settings, json_decode($provider['model_settings'], true));
        
        if (is_wp_error($result)) {
            // Update provider test status
            global $wpdb;
            $table = $wpdb->prefix . 'hex_ai_providers';
            $wpdb->update(
                $table,
                [
                    'last_tested' => current_time('mysql'),
                    'test_status' => 'failed'
                ],
                ['provider_name' => $provider_name],
                ['%s', '%s'],
                ['%s']
            );
            
            wp_send_json_error($result->get_error_message());
        } else {
            // Update provider test status
            global $wpdb;
            $table = $wpdb->prefix . 'hex_ai_providers';
            $wpdb->update(
                $table,
                [
                    'last_tested' => current_time('mysql'),
                    'test_status' => 'success'
                ],
                ['provider_name' => $provider_name],
                ['%s', '%s'],
                ['%s']
            );
            
            wp_send_json_success([
                'message' => 'Provider test successful',
                'test_response' => $result['content'],
                'tokens_used' => $result['tokens_used']
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
        $model_settings = $_POST['model_settings'] ?? [];
        
        if (empty($provider)) {
            wp_send_json_error('Provider name is required');
            return;
        }
        
        global $wpdb;
        $table = $wpdb->prefix . 'hex_ai_providers';
        
        // Sanitize model settings
        $sanitized_settings = [];
        foreach ($model_settings as $key => $value) {
            $key = sanitize_text_field($key);
            if (is_numeric($value)) {
                $sanitized_settings[$key] = floatval($value);
            } else {
                $sanitized_settings[$key] = sanitize_text_field($value);
            }
        }
        
        $result = $wpdb->update(
            $table,
            [
                'api_key' => $api_key,
                'model_settings' => json_encode($sanitized_settings),
                'is_enabled' => !empty($api_key) ? 1 : 0
            ],
            ['provider_name' => $provider],
            ['%s', '%s', '%d'],
            ['%s']
        );
        
        if ($result === false) {
            wp_send_json_error('Failed to save settings');
        } else {
            wp_send_json_success('Settings saved successfully');
        }
    }
    
    public static function ajax_get_stats() {
        check_ajax_referer('hexagon_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
            return;
        }
        
        global $wpdb;
        
        // Get provider stats
        $providers_table = $wpdb->prefix . 'hex_ai_providers';
        $providers = $wpdb->get_results("SELECT provider_name, usage_stats, test_status, last_tested FROM {$providers_table}", ARRAY_A);
        
        // Get generation stats
        $content_table = $wpdb->prefix . 'hex_content_generation';
        $total_generations = $wpdb->get_var("SELECT COUNT(*) FROM {$content_table}");
        $successful_generations = $wpdb->get_var("SELECT COUNT(*) FROM {$content_table} WHERE status = 'completed'");
        $total_tokens = $wpdb->get_var("SELECT SUM(tokens_used) FROM {$content_table} WHERE status = 'completed'");
        $avg_quality = $wpdb->get_var("SELECT AVG(quality_score) FROM {$content_table} WHERE status = 'completed' AND quality_score > 0");
        
        // Recent generations
        $recent_generations = $wpdb->get_results("
            SELECT generation_id, prompt, ai_provider, tokens_used, quality_score, created_at, status 
            FROM {$content_table} 
            ORDER BY created_at DESC 
            LIMIT 10
        ", ARRAY_A);
        
        wp_send_json_success([
            'providers' => $providers,
            'total_generations' => (int) $total_generations,
            'successful_generations' => (int) $successful_generations,
            'total_tokens' => (int) $total_tokens,
            'average_quality' => round($avg_quality, 1),
            'recent_generations' => $recent_generations
        ]);
    }
    
    public static function ajax_update_provider_config() {
        check_ajax_referer('hexagon_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
            return;
        }
        
        $provider = sanitize_text_field($_POST['provider'] ?? '');
        $config = $_POST['config'] ?? [];
        
        if (empty($provider)) {
            wp_send_json_error('Provider name is required');
            return;
        }
        
        global $wpdb;
        $table = $wpdb->prefix . 'hex_ai_providers';
        
        $update_data = [];
        
        if (isset($config['enabled'])) {
            $update_data['is_enabled'] = $config['enabled'] ? 1 : 0;
        }
        
        if (isset($config['api_key'])) {
            $update_data['api_key'] = sanitize_text_field($config['api_key']);
        }
        
        if (isset($config['model_settings'])) {
            $update_data['model_settings'] = json_encode($config['model_settings']);
        }
        
        if (isset($config['rate_limits'])) {
            $update_data['rate_limits'] = json_encode($config['rate_limits']);
        }
        
        if (empty($update_data)) {
            wp_send_json_error('No valid configuration provided');
            return;
        }
        
        $result = $wpdb->update(
            $table,
            $update_data,
            ['provider_name' => $provider],
            null,
            ['%s']
        );
        
        if ($result === false) {
            wp_send_json_error('Failed to update provider configuration');
        } else {
            wp_send_json_success('Provider configuration updated successfully');
        }
    }
}