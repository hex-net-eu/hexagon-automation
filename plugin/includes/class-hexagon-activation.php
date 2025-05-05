<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Handles plugin activation and deactivation routines.
 */
class Hexagon_Activation {

    /**
     * Runs on plugin activation.
     */
    public static function activate() {
        // Deactivate all other hexagon-automation installs
        require_once ABSPATH . 'wp-admin/includes/plugin.php';
        $current = plugin_basename( __FILE__ );
        $all = get_plugins();
        foreach ( $all as $slug => $data ) {
            if ( strpos( $slug, 'hexagon-automation' ) === 0 && $slug !== $current ) {
                if ( is_plugin_active( $slug ) ) {
                    deactivate_plugins( $slug );
                }
            }
        }

        global $wpdb;
        // Create default options
        add_option( 'hexagon_ai_provider', 'chatgpt' );
        add_option( 'hexagon_wizard_email', [] );

        // Create logs table
        $table = $wpdb->prefix . 'hex_logs';
        $charset_collate = $wpdb->get_charset_collate();
        $sql = "CREATE TABLE $table (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            context varchar(100) NOT NULL,
            message text NOT NULL,
            created_at datetime NOT NULL,
            PRIMARY KEY  (id)
        ) $charset_collate;";
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta( $sql );
    }

    /**
     * Runs on plugin uninstall.
     */
    public static function uninstall() {
        global $wpdb;
        // Delete options
        delete_option( 'hexagon_ai_provider' );
        delete_option( 'hexagon_wizard_email' );

        // Drop logs table
        $table = $wpdb->prefix . 'hex_logs';
        $wpdb->query( "DROP TABLE IF EXISTS $table" );
    }
}
