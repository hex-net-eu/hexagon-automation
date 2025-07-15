<?php
/**
 * Plugin Name:     Hexagon Automation
 * Plugin URI:      https://hex-net.eu/hexagon-automation
 * Description:     AI-powered content & social media automation for WordPress.
 * Version:         3.1.1
 * Requires at least: 5.0
 * Requires PHP: 7.4
 * Tested up to:    6.4
 * Author:          Hexagon Technology
 * Author URI:      https://hex-net.eu
 * Text Domain:     hexagon-automation
 * Domain Path:     /languages
 */
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Deactivate older versions on admin_init
add_action( 'admin_init', function() {
    require_once ABSPATH . 'wp-admin/includes/plugin.php';
    $old_slugs = [
        'hexagon-automation-old/hexagon-automation.php',
        'hexagon-automation-v7/hexagon-automation.php',
        'hexagon-automation-new_v6/hexagon-automation.php',
        'hexagon-automation-2-3-0/hexagon-automation.php',
        'hexagon-automation-2-4-0/hexagon-automation.php',
        'hexagon-automation-2-4-1/hexagon-automation.php',
        'hexagon-automation-2-4-2/hexagon-automation.php',
    ];
    foreach ( $old_slugs as $slug ) {
        if ( is_plugin_active( $slug ) ) {
            deactivate_plugins( $slug );
            add_action( 'admin_notices', function() use ( $slug ) {
                $plugin_data = get_plugin_data( WP_PLUGIN_DIR . '/' . $slug );
                printf(
                    '<div class="notice notice-warning is-dismissible"><p>%s: <strong>%s</strong></p></div>',
                    esc_html__( 'Detected and deactivated old Hexagon Automation version', 'hexagon-automation' ),
                    esc_html( $plugin_data['Version'] )
                );
            } );
        }
    }
} );

// Guarded definitions
if ( ! defined( 'HEXAGON_PATH' ) ) {
    define( 'HEXAGON_PATH', plugin_dir_path( __FILE__ ) );
}
if ( ! defined( 'HEXAGON_URL' ) ) {
    define( 'HEXAGON_URL', plugin_dir_url( __FILE__ ) );
}
if ( ! defined( 'HEXAGON_VERSION' ) ) {
    define( 'HEXAGON_VERSION', '3.1.1' );
}

// Load essential files first
require_once HEXAGON_PATH . 'includes/functions.php';
require_once HEXAGON_PATH . 'includes/ajax-fix.php';
require_once HEXAGON_PATH . 'includes/admin-nonces.php';
require_once HEXAGON_PATH . 'includes/class-hexagon-activation.php';
require_once HEXAGON_PATH . 'includes/class-hexagon-loader.php';

register_activation_hook( __FILE__, [ 'Hexagon_Activation', 'activate' ] );
register_deactivation_hook( __FILE__, [ 'Hexagon_Activation', 'deactivate' ] );
register_uninstall_hook(  __FILE__, [ 'Hexagon_Activation', 'uninstall' ] );

add_action( 'plugins_loaded', [ 'Hexagon_Loader', 'init' ] );

// WordPress hooks for functionality
add_action( 'init', 'hexagon_init_functionality' );
add_action( 'wp_enqueue_scripts', 'hexagon_enqueue_frontend_scripts' );
add_action( 'admin_enqueue_scripts', 'hexagon_enqueue_admin_scripts' );

function hexagon_init_functionality() {
    // Initialize WordPress-specific functionality
    load_plugin_textdomain( 'hexagon-automation', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
    
    // Add WordPress integration hooks
    add_action( 'save_post', 'hexagon_handle_post_save', 10, 2 );
    add_action( 'wp_trash_post', 'hexagon_handle_post_trash' );
    add_action( 'wp_insert_comment', 'hexagon_handle_new_comment', 10, 2 );
    
    // Add shortcodes
    add_shortcode( 'hexagon_ai_content', 'hexagon_ai_content_shortcode' );
    add_shortcode( 'hexagon_generated_image', 'hexagon_generated_image_shortcode' );
}

function hexagon_enqueue_frontend_scripts() {
    if ( get_option( 'hexagon_enable_frontend', false ) ) {
        wp_enqueue_script( 'hexagon-frontend', HEXAGON_URL . 'assets/js/frontend.js', ['jquery'], HEXAGON_VERSION, true );
        wp_enqueue_style( 'hexagon-frontend', HEXAGON_URL . 'assets/css/frontend.css', [], HEXAGON_VERSION );
        
        wp_localize_script( 'hexagon-frontend', 'hexagon_ajax', [
            'ajax_url' => admin_url( 'admin-ajax.php' ),
            'nonce' => wp_create_nonce( 'hexagon_nonce' )
        ]);
    }
}

function hexagon_enqueue_admin_scripts( $hook ) {
    if ( strpos( $hook, 'hexagon' ) !== false ) {
        wp_enqueue_script( 'hexagon-admin', HEXAGON_URL . 'assets/js/admin.js', ['jquery'], HEXAGON_VERSION, true );
        wp_enqueue_style( 'hexagon-admin', HEXAGON_URL . 'assets/css/admin.css', [], HEXAGON_VERSION );
        
        wp_localize_script( 'hexagon-admin', 'hexagon_admin', [
            'ajax_url' => admin_url( 'admin-ajax.php' ),
            'nonce' => wp_create_nonce( 'hexagon_nonce' ),
            'dashboard_url' => HEXAGON_URL . 'dashboard/index.html'
        ]);
    }
}

function hexagon_handle_post_save( $post_id, $post ) {
    if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return;
    if ( ! current_user_can( 'edit_post', $post_id ) ) return;
    
    // Auto-generate social media posts if enabled
    if ( get_option( 'hexagon_auto_social_on_publish', false ) && $post->post_status === 'publish' ) {
        if ( class_exists( 'Hexagon_Social_Scheduler' ) ) {
            $content = get_the_title( $post_id ) . "\n\n" . wp_trim_words( $post->post_content, 30 );
            $platforms = get_option( 'hexagon_auto_social_platforms', [] );
            
            if ( ! empty( $platforms ) ) {
                Hexagon_Social_Scheduler::schedule_post( 
                    $content, 
                    $platforms, 
                    current_time( 'mysql' ),
                    [ 'auto_generated' => true, 'post_id' => $post_id ]
                );
            }
        }
    }
    
    hexagon_log( 'WordPress Integration', "Post {$post_id} saved: {$post->post_title}", 'info' );
}

function hexagon_handle_post_trash( $post_id ) {
    hexagon_log( 'WordPress Integration', "Post {$post_id} moved to trash", 'info' );
}

function hexagon_handle_new_comment( $comment_id, $comment ) {
    if ( get_option( 'hexagon_moderate_comments', false ) ) {
        // AI-powered comment moderation could be implemented here
        hexagon_log( 'WordPress Integration', "New comment {$comment_id} on post {$comment->comment_post_ID}", 'info' );
    }
}

function hexagon_ai_content_shortcode( $atts ) {
    $atts = shortcode_atts( [
        'type' => 'article',
        'prompt' => '',
        'provider' => 'chatgpt',
        'length' => 'standard'
    ], $atts );
    
    if ( empty( $atts['prompt'] ) ) {
        return '<p>Error: No prompt provided for AI content generation.</p>';
    }
    
    if ( class_exists( 'Hexagon_AI_Content_Generator' ) ) {
        $result = Hexagon_AI_Content_Generator::generate_content( $atts['prompt'], $atts );
        
        if ( ! is_wp_error( $result ) ) {
            return '<div class="hexagon-ai-content">' . $result['content'] . '</div>';
        } else {
            return '<p>Error generating content: ' . $result->get_error_message() . '</p>';
        }
    }
    
    return '<p>Hexagon AI Content Generator not available.</p>';
}

function hexagon_generated_image_shortcode( $atts ) {
    $atts = shortcode_atts( [
        'prompt' => '',
        'provider' => 'dalle3',
        'size' => '1024x1024',
        'style' => 'realistic'
    ], $atts );
    
    if ( empty( $atts['prompt'] ) ) {
        return '<p>Error: No prompt provided for image generation.</p>';
    }
    
    if ( class_exists( 'Hexagon_Image_Generator' ) ) {
        $result = Hexagon_Image_Generator::generate_image( $atts['prompt'], $atts );
        
        if ( ! is_wp_error( $result ) && ! empty( $result['images'] ) ) {
            $image_url = $result['images'][0]['file_url'];
            return '<div class="hexagon-generated-image"><img src="' . esc_url( $image_url ) . '" alt="' . esc_attr( $atts['prompt'] ) . '" /></div>';
        } else {
            return '<p>Error generating image: ' . ( is_wp_error( $result ) ? $result->get_error_message() : 'Unknown error' ) . '</p>';
        }
    }
    
    return '<p>Hexagon Image Generator not available.</p>';
}
