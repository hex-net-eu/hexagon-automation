<?php
/**
 * Hexagon Automation - AJAX Error Fixes
 * Naprawia błędy HTTP 500 w admin-ajax.php
 */

if (!defined('ABSPATH')) {
    exit;
}

// 1. Dodaj try-catch wrapper dla wszystkich AJAX funkcji
class Hexagon_AJAX_Fix {
    
    public static function init() {
        // Override wszystkich problematycznych AJAX handlers
        remove_action('wp_ajax_hexagon_ai_generate', ['Hexagon_AI_Manager', 'handle_ai_generation']);
        remove_action('wp_ajax_nopriv_hexagon_ai_generate', ['Hexagon_AI_Manager', 'handle_ai_generation']);
        remove_action('wp_ajax_hexagon_ai_test_connection', ['Hexagon_AI_Manager', 'test_ai_connection']);
        
        add_action('wp_ajax_hexagon_ai_generate', [__CLASS__, 'safe_handle_ai_generation']);
        add_action('wp_ajax_nopriv_hexagon_ai_generate', [__CLASS__, 'safe_handle_ai_generation']);
        add_action('wp_ajax_hexagon_ai_test_connection', [__CLASS__, 'safe_test_ai_connection']);
        
        // Debug AJAX dla testowania
        add_action('wp_ajax_hexagon_debug_ajax', [__CLASS__, 'debug_ajax']);
    }
    
    public static function safe_handle_ai_generation() {
        try {
            // Sprawdź czy potrzebne funkcje istnieją
            if (!function_exists('hexagon_log')) {
                wp_send_json_error(['message' => 'Plugin nie jest w pełni załadowany']);
                return;
            }
            
            // Sprawdź nonce - ale bez crashowania
            if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'hexagon_ai_nonce')) {
                wp_send_json_error(['message' => 'Błąd bezpieczeństwa - odśwież stronę']);
                return;
            }
            
            // Sprawdź czy klasa istnieje
            if (!class_exists('Hexagon_AI_Manager')) {
                wp_send_json_error(['message' => 'Moduł AI nie jest załadowany']);
                return;
            }
            
            // Wywołaj oryginalną funkcję
            Hexagon_AI_Manager::handle_ai_generation();
            
        } catch (Exception $e) {
            error_log('Hexagon AJAX Error: ' . $e->getMessage());
            wp_send_json_error([
                'message' => 'Błąd generowania treści: ' . $e->getMessage(),
                'debug' => [
                    'error' => $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine()
                ]
            ]);
        } catch (Error $e) {
            error_log('Hexagon AJAX Fatal Error: ' . $e->getMessage());
            wp_send_json_error([
                'message' => 'Krytyczny błąd systemu',
                'debug' => [
                    'error' => $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine()
                ]
            ]);
        }
    }
    
    public static function safe_test_ai_connection() {
        try {
            if (!function_exists('hexagon_log')) {
                wp_send_json_error(['message' => 'Plugin nie jest w pełni załadowany']);
                return;
            }
            
            if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'hexagon_ai_nonce')) {
                wp_send_json_error(['message' => 'Błąd bezpieczeństwa']);
                return;
            }
            
            if (!class_exists('Hexagon_AI_Manager')) {
                wp_send_json_error(['message' => 'Moduł AI nie jest załadowany']);
                return;
            }
            
            Hexagon_AI_Manager::test_ai_connection();
            
        } catch (Exception $e) {
            error_log('Hexagon AI Test Error: ' . $e->getMessage());
            wp_send_json_error(['message' => 'Błąd testowania AI: ' . $e->getMessage()]);
        }
    }
    
    // Debug function - testuje czy AJAX w ogóle działa
    public static function debug_ajax() {
        wp_send_json_success([
            'message' => 'AJAX działa poprawnie!',
            'timestamp' => current_time('mysql'),
            'functions_exist' => [
                'hexagon_log' => function_exists('hexagon_log'),
                'hexagon_get_option' => function_exists('hexagon_get_option'),
                'wp_verify_nonce' => function_exists('wp_verify_nonce')
            ],
            'classes_exist' => [
                'Hexagon_AI_Manager' => class_exists('Hexagon_AI_Manager'),
                'Hexagon_Email_Integration' => class_exists('Hexagon_Email_Integration'),
                'Hexagon_Social_Integration' => class_exists('Hexagon_Social_Integration')
            ],
            'post_data' => $_POST
        ]);
    }
}

// Safe function exists check
if (!function_exists('hexagon_log')) {
    function hexagon_log($action, $context, $level = 'info') {
        error_log("Hexagon Log [$level]: $action - $context");
        return true;
    }
}

if (!function_exists('hexagon_get_option')) {
    function hexagon_get_option($option_name, $default = '') {
        return get_option($option_name, $default);
    }
}

// Inicjalizuj fixes
add_action('init', ['Hexagon_AJAX_Fix', 'init'], 99);