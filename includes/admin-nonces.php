<?php
/**
 * Hexagon Automation - Admin Nonces
 * Dodaje nonces do JavaScript
 */

if (!defined('ABSPATH')) {
    exit;
}

class Hexagon_Admin_Nonces {
    
    public static function init() {
        add_action('admin_enqueue_scripts', [__CLASS__, 'enqueue_admin_scripts']);
    }
    
    public static function enqueue_admin_scripts($hook) {
        // Tylko na stronach Hexagon
        if (strpos($hook, 'hexagon') === false && $hook !== 'toplevel_page_hexagon-dashboard') {
            return;
        }
        
        // Enqueue nonces script
        wp_enqueue_script(
            'hexagon-nonces',
            HEXAGON_URL . 'assets/js/nonces.js',
            ['jquery'],
            HEXAGON_VERSION,
            true
        );
        
        // Dodaj nonces do JavaScript
        wp_localize_script('hexagon-nonces', 'hexagon_ajax', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonces' => [
                'ai' => wp_create_nonce('hexagon_ai_nonce'),
                'email' => wp_create_nonce('hexagon_email_nonce'),
                'social' => wp_create_nonce('hexagon_social_nonce'),
                'debug' => wp_create_nonce('hexagon_debug_nonce'),
                'test' => wp_create_nonce('hexagon_test_nonce')
            ],
            'debug_mode' => defined('WP_DEBUG') && WP_DEBUG
        ]);
    }
}

add_action('init', ['Hexagon_Admin_Nonces', 'init']);