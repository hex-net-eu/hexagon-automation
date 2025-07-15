<?php
/**
 * Plugin Name:     Hexagon Automation
 * Plugin URI:      https://hex-net.eu/hexagon-automation
 * Description:     AI-powered content & social media automation for WordPress.
 * Version:         3.0.0
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
    define( 'HEXAGON_VERSION', '3.0.0' );
}

// Load core and activation
require_once HEXAGON_PATH . 'includes/class-hexagon-loader.php';
require_once HEXAGON_PATH . 'includes/class-hexagon-activation.php';

register_activation_hook( __FILE__, [ 'Hexagon_Activation', 'activate' ] );
register_uninstall_hook(  __FILE__, [ 'Hexagon_Activation', 'uninstall' ] );

add_action( 'plugins_loaded', [ 'Hexagon_Loader', 'init' ] );
