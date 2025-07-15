<?php
if (!defined('ABSPATH')) exit;

class Hexagon_AI_Claude {
    
    private static $api_endpoint = 'https://api.anthropic.com/v1/messages';
    
    public static function init() {
        // Provider initialized
    }
    
    public static function generate_content($prompt, $model = 'claude-3-sonnet-20240229', $max_tokens = 2000) {
        $api_key = get_option('hexagon_ai_claude_api_key');
        
        if (empty($api_key)) {
            throw new Exception('Claude API key not configured');
        }
        
        $body = [
            'model' => $model,
            'max_tokens' => $max_tokens,
            'messages' => [
                ['role' => 'user', 'content' => $prompt]
            ]
        ];
        
        $response = wp_remote_post(self::$api_endpoint, [
            'headers' => [
                'x-api-key' => $api_key,
                'anthropic-version' => '2023-06-01',
                'Content-Type' => 'application/json'
            ],
            'body' => json_encode($body),
            'timeout' => 60
        ]);
        
        if (is_wp_error($response)) {
            throw new Exception('Claude API request failed: ' . $response->get_error_message());
        }
        
        $data = json_decode(wp_remote_retrieve_body($response), true);
        
        if (isset($data['error'])) {
            throw new Exception('Claude API error: ' . $data['error']['message']);
        }
        
        if (!isset($data['content'][0]['text'])) {
            throw new Exception('Invalid Claude API response');
        }
        
        return [
            'content' => trim($data['content'][0]['text']),
            'usage' => $data['usage'] ?? null,
            'model' => $model,
            'provider' => 'Claude'
        ];
    }
    
    public static function test_connection() {
        try {
            $result = self::generate_content('Test connection. Respond with "OK".', 'claude-3-sonnet-20240229', 10);
            return [
                'success' => true,
                'message' => 'Claude connection successful',
                'response' => $result['content']
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Claude connection failed: ' . $e->getMessage()
            ];
        }
    }
    
    public static function get_available_models() {
        return [
            'claude-3-opus-20240229' => 'Claude 3 Opus (Best)',
            'claude-3-sonnet-20240229' => 'Claude 3 Sonnet (Recommended)',
            'claude-3-haiku-20240307' => 'Claude 3 Haiku (Fastest)'
        ];
    }
}
