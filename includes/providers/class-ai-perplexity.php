<?php
if (!defined('ABSPATH')) exit;

class Hexagon_AI_Perplexity {
    
    private static $api_endpoint = 'https://api.perplexity.ai/chat/completions';
    
    public static function init() {
        // Provider initialized
    }
    
    public static function generate_content($prompt, $model = 'llama-3.1-sonar-small-128k-online', $max_tokens = 2000, $temperature = 0.7) {
        $api_key = get_option('hexagon_ai_perplexity_api_key');
        
        if (empty($api_key)) {
            throw new Exception('Perplexity API key not configured');
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
            throw new Exception('Perplexity API request failed: ' . $response->get_error_message());
        }
        
        $data = json_decode(wp_remote_retrieve_body($response), true);
        
        if (isset($data['error'])) {
            throw new Exception('Perplexity API error: ' . $data['error']['message']);
        }
        
        if (!isset($data['choices'][0]['message']['content'])) {
            throw new Exception('Invalid Perplexity API response');
        }
        
        return [
            'content' => trim($data['choices'][0]['message']['content']),
            'usage' => $data['usage'] ?? null,
            'model' => $model,
            'provider' => 'Perplexity'
        ];
    }
    
    public static function test_connection() {
        try {
            $result = self::generate_content('Test connection. Respond with "OK".', 'llama-3.1-sonar-small-128k-online', 10, 0);
            return [
                'success' => true,
                'message' => 'Perplexity connection successful',
                'response' => $result['content']
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Perplexity connection failed: ' . $e->getMessage()
            ];
        }
    }
    
    public static function get_available_models() {
        return [
            'llama-3.1-sonar-large-128k-online' => 'Llama 3.1 Sonar Large (Online)',
            'llama-3.1-sonar-small-128k-online' => 'Llama 3.1 Sonar Small (Online)',
            'llama-3.1-sonar-huge-128k-online' => 'Llama 3.1 Sonar Huge (Online)'
        ];
    }
}
