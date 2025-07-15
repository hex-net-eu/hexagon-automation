<?php
if (!defined('ABSPATH')) exit;

class Hexagon_AI_ChatGPT {
    
    private static $api_endpoint = 'https://api.openai.com/v1/chat/completions';
    
    public static function init() {
        // Provider initialized
    }
    
    public static function generate_content($prompt, $model = 'gpt-4', $max_tokens = 2000, $temperature = 0.7) {
        $api_key = get_option('hexagon_ai_chatgpt_api_key');
        
        if (empty($api_key)) {
            throw new Exception('OpenAI API key not configured');
        }
        
        $body = [
            'model' => $model,
            'messages' => [
                ['role' => 'user', 'content' => $prompt]
            ],
            'max_tokens' => $max_tokens,
            'temperature' => $temperature
        ];
        
        $response = wp_remote_post(self::$api_endpoint, [
            'headers' => [
                'Authorization' => 'Bearer ' . $api_key,
                'Content-Type' => 'application/json'
            ],
            'body' => json_encode($body),
            'timeout' => 60
        ]);
        
        if (is_wp_error($response)) {
            throw new Exception('OpenAI API request failed: ' . $response->get_error_message());
        }
        
        $data = json_decode(wp_remote_retrieve_body($response), true);
        
        if (isset($data['error'])) {
            throw new Exception('OpenAI API error: ' . $data['error']['message']);
        }
        
        if (!isset($data['choices'][0]['message']['content'])) {
            throw new Exception('Invalid OpenAI API response');
        }
        
        return [
            'content' => trim($data['choices'][0]['message']['content']),
            'usage' => $data['usage'] ?? null,
            'model' => $model,
            'provider' => 'ChatGPT'
        ];
    }
    
    public static function test_connection() {
        try {
            $result = self::generate_content('Test connection. Respond with "OK".', 'gpt-3.5-turbo', 10, 0);
            return [
                'success' => true,
                'message' => 'ChatGPT connection successful',
                'response' => $result['content']
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'ChatGPT connection failed: ' . $e->getMessage()
            ];
        }
    }
    
    public static function get_available_models() {
        return [
            'gpt-4' => 'GPT-4 (Recommended)',
            'gpt-4-turbo' => 'GPT-4 Turbo',
            'gpt-3.5-turbo' => 'GPT-3.5 Turbo (Faster)',
            'gpt-3.5-turbo-16k' => 'GPT-3.5 Turbo 16K'
        ];
    }
}