<?php
if (!defined('ABSPATH')) exit;

class Hexagon_Hexagon_Ai_Manager {
    
    private static $api_endpoints = [
        'chatgpt' => 'https://api.openai.com/v1/chat/completions',
        'claude' => 'https://api.anthropic.com/v1/messages',
        'perplexity' => 'https://api.perplexity.ai/chat/completions'
    ];
    
    public static function init() {
        add_action('wp_ajax_hexagon_ai_generate', [__CLASS__, 'handle_ai_generation']);
        add_action('wp_ajax_nopriv_hexagon_ai_generate', [__CLASS__, 'handle_ai_generation']);
        add_action('wp_ajax_hexagon_ai_test_connection', [__CLASS__, 'test_ai_connection']);
        add_action('init', [__CLASS__, 'schedule_ai_tasks']);
    }
    
    public static function handle_ai_generation() {
        check_ajax_referer('hexagon_ai_nonce', 'nonce');
        
        $provider = sanitize_text_field($_POST['provider']);
        $content_type = sanitize_text_field($_POST['content_type']);
        $prompt = sanitize_textarea_field($_POST['prompt']);
        $language = sanitize_text_field($_POST['language'] ?? 'pl');
        
        if (!in_array($provider, ['chatgpt', 'claude', 'perplexity'])) {
            wp_send_json_error(['message' => 'Nieprawidłowy dostawca AI']);
        }
        
        try {
            $result = self::generate_content($provider, $content_type, $prompt, $language);
            hexagon_log('AI Content Generated', "Provider: $provider, Type: $content_type", 'success');
            wp_send_json_success($result);
        } catch (Exception $e) {
            hexagon_log('AI Generation Error', $e->getMessage(), 'error');
            wp_send_json_error(['message' => $e->getMessage()]);
        }
    }
    
    public static function generate_content($provider, $content_type, $prompt, $language = 'pl') {
        $api_key = hexagon_get_option("hexagon_ai_{$provider}_api_key");
        if (empty($api_key)) {
            throw new Exception("Brak klucza API dla $provider");
        }
        
        $system_prompt = self::get_system_prompt($content_type, $language);
        $full_prompt = $system_prompt . "\n\n" . $prompt;
        
        switch ($provider) {
            case 'chatgpt':
                return self::call_chatgpt($api_key, $full_prompt);
            case 'claude':
                return self::call_claude($api_key, $full_prompt);
            case 'perplexity':
                return self::call_perplexity($api_key, $full_prompt);
            default:
                throw new Exception('Nieobsługiwany dostawca AI');
        }
    }
    
    private static function call_chatgpt($api_key, $prompt) {
        $model = hexagon_get_option('hexagon_ai_chatgpt_model', 'gpt-4');
        $temperature = floatval(hexagon_get_option('hexagon_ai_chatgpt_temperature', 0.7));
        $max_tokens = intval(hexagon_get_option('hexagon_ai_chatgpt_max_tokens', 2000));
        
        $body = [
            'model' => $model,
            'messages' => [
                ['role' => 'user', 'content' => $prompt]
            ],
            'temperature' => $temperature,
            'max_tokens' => $max_tokens
        ];
        
        $response = wp_remote_post(self::$api_endpoints['chatgpt'], [
            'headers' => [
                'Authorization' => 'Bearer ' . $api_key,
                'Content-Type' => 'application/json'
            ],
            'body' => json_encode($body),
            'timeout' => 60
        ]);
        
        if (is_wp_error($response)) {
            throw new Exception('Błąd połączenia z ChatGPT: ' . $response->get_error_message());
        }
        
        $data = json_decode(wp_remote_retrieve_body($response), true);
        
        if (isset($data['error'])) {
            throw new Exception('ChatGPT Error: ' . $data['error']['message']);
        }
        
        return [
            'content' => $data['choices'][0]['message']['content'],
            'usage' => $data['usage'],
            'provider' => 'ChatGPT',
            'model' => $model
        ];
    }
    
    private static function call_claude($api_key, $prompt) {
        $model = hexagon_get_option('hexagon_ai_claude_model', 'claude-3-sonnet-20240229');
        $max_tokens = intval(hexagon_get_option('hexagon_ai_claude_max_tokens', 2000));
        
        $body = [
            'model' => $model,
            'max_tokens' => $max_tokens,
            'messages' => [
                ['role' => 'user', 'content' => $prompt]
            ]
        ];
        
        $response = wp_remote_post(self::$api_endpoints['claude'], [
            'headers' => [
                'x-api-key' => $api_key,
                'Content-Type' => 'application/json',
                'anthropic-version' => '2023-06-01'
            ],
            'body' => json_encode($body),
            'timeout' => 60
        ]);
        
        if (is_wp_error($response)) {
            throw new Exception('Błąd połączenia z Claude: ' . $response->get_error_message());
        }
        
        $data = json_decode(wp_remote_retrieve_body($response), true);
        
        if (isset($data['error'])) {
            throw new Exception('Claude Error: ' . $data['error']['message']);
        }
        
        return [
            'content' => $data['content'][0]['text'],
            'usage' => $data['usage'],
            'provider' => 'Claude',
            'model' => $model
        ];
    }
    
    private static function call_perplexity($api_key, $prompt) {
        $model = hexagon_get_option('hexagon_ai_perplexity_model', 'llama-3.1-sonar-small-128k-online');
        $temperature = floatval(hexagon_get_option('hexagon_ai_perplexity_temperature', 0.2));
        $max_tokens = intval(hexagon_get_option('hexagon_ai_perplexity_max_tokens', 2000));
        
        $body = [
            'model' => $model,
            'messages' => [
                ['role' => 'user', 'content' => $prompt]
            ],
            'temperature' => $temperature,
            'max_tokens' => $max_tokens
        ];
        
        $response = wp_remote_post(self::$api_endpoints['perplexity'], [
            'headers' => [
                'Authorization' => 'Bearer ' . $api_key,
                'Content-Type' => 'application/json'
            ],
            'body' => json_encode($body),
            'timeout' => 60
        ]);
        
        if (is_wp_error($response)) {
            throw new Exception('Błąd połączenia z Perplexity: ' . $response->get_error_message());
        }
        
        $data = json_decode(wp_remote_retrieve_body($response), true);
        
        if (isset($data['error'])) {
            throw new Exception('Perplexity Error: ' . $data['error']['message']);
        }
        
        return [
            'content' => $data['choices'][0]['message']['content'],
            'usage' => $data['usage'],
            'provider' => 'Perplexity',
            'model' => $model
        ];
    }
    
    private static function get_system_prompt($content_type, $language) {
        $prompts = [
            'article' => 'Napisz profesjonalny artykuł na zadany temat. Używaj nagłówków H2, H3, wprowadzenia i podsumowania.',
            'guide' => 'Stwórz praktyczny przewodnik krok po kroku. Używaj list punktowanych i numerowanych.',
            'news' => 'Napisz wiadomość w stylu newsowym. Zacznij od najważniejszych informacji.',
            'review' => 'Napisz szczegółową recenzję produktu lub usługi. Uwzględnij wady i zalety.',
            'tutorial' => 'Stwórz szczegółowy tutorial z instrukcjami krok po kroku.',
            'comparison' => 'Napisz porównanie produktów/usług w formie tabeli lub listy.',
            'case_study' => 'Przedstaw studium przypadku z konkretnymi danymi i wynikami.',
            'interview' => 'Stwórz wywiad z ekspercją pytaniami i odpowiedziami.',
            'opinion' => 'Napisz artykuł opiniotwórczy przedstawiający konkretne stanowisko.',
            'listicle' => 'Stwórz artykuł w formie listy z konkretnymi punktami.',
            'howto' => 'Napisz instrukcję "jak zrobić" z prostymi krokami.',
            'definition' => 'Wyjaśnij pojęcie lub termin w sposób przystępny.',
            'faq' => 'Stwórz listę najczęściej zadawanych pytań z odpowiedziami.',
            'press_release' => 'Napisz komunikat prasowy w formalnym tonie.',
            'blog_post' => 'Stwórz przyjazny post blogowy w luźnym stylu.',
            'product_description' => 'Napisz atrakcyjny opis produktu skupiający się na korzyściach.',
            'social_media' => 'Stwórz krótki, angażujący post na media społecznościowe.',
            'email_marketing' => 'Napisz skuteczny email marketingowy z call-to-action.',
            'landing_page' => 'Stwórz copy na stronę docelową z jasnym CTA.',
            'summary' => 'Napisz zwięzłe podsumowanie głównych punktów tematu.'
        ];
        
        $base_prompt = $prompts[$content_type] ?? $prompts['article'];
        
        if ($language === 'en') {
            $base_prompt = self::translate_prompt_to_english($base_prompt);
        }
        
        return $base_prompt . " Język: $language. Długość: około 800-1200 słów.";
    }
    
    private static function translate_prompt_to_english($polish_prompt) {
        $translations = [
            'Napisz profesjonalny artykuł na zadany temat. Używaj nagłówków H2, H3, wprowadzenia i podsumowania.' => 'Write a professional article on the given topic. Use H2, H3 headings, introduction and summary.',
            'Stwórz praktyczny przewodnik krok po kroku. Używaj list punktowanych i numerowanych.' => 'Create a practical step-by-step guide. Use bullet points and numbered lists.',
        ];
        
        return $translations[$polish_prompt] ?? 'Write comprehensive content on the given topic.';
    }
    
    public static function test_ai_connection() {
        check_ajax_referer('hexagon_ai_nonce', 'nonce');
        
        $provider = sanitize_text_field($_POST['provider']);
        
        try {
            $result = self::generate_content($provider, 'summary', 'Test connection', 'en');
            wp_send_json_success(['message' => 'Połączenie pomyślne', 'provider' => $provider]);
        } catch (Exception $e) {
            wp_send_json_error(['message' => $e->getMessage()]);
        }
    }
    
    public static function schedule_ai_tasks() {
        if (!wp_next_scheduled('hexagon_ai_cleanup')) {
            wp_schedule_event(time(), 'daily', 'hexagon_ai_cleanup');
        }
    }
    
    public static function get_usage_stats($provider = null) {
        $stats = hexagon_get_option('hexagon_ai_usage_stats', []);
        
        if ($provider) {
            return $stats[$provider] ?? ['requests' => 0, 'tokens' => 0, 'cost' => 0];
        }
        
        return $stats;
    }
    
    public static function update_usage_stats($provider, $usage) {
        $stats = self::get_usage_stats();
        
        if (!isset($stats[$provider])) {
            $stats[$provider] = ['requests' => 0, 'tokens' => 0, 'cost' => 0];
        }
        
        $stats[$provider]['requests']++;
        if (isset($usage['total_tokens'])) {
            $stats[$provider]['tokens'] += $usage['total_tokens'];
        }
        
        update_option('hexagon_ai_usage_stats', $stats);
    }
}
