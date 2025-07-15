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
        try {
            // Check minimum requirements
            if (version_compare(PHP_VERSION, '7.4', '<')) {
                wp_die('Hexagon Automation requires PHP 7.4 or higher. Current version: ' . PHP_VERSION);
            }
            
            if (version_compare(get_bloginfo('version'), '5.0', '<')) {
                wp_die('Hexagon Automation requires WordPress 5.0 or higher. Current version: ' . get_bloginfo('version'));
            }
            
            // Deactivate all other hexagon-automation installs
            require_once ABSPATH . 'wp-admin/includes/plugin.php';
            if (defined('HEXAGON_PATH')) {
                $current = plugin_basename( HEXAGON_PATH . 'hexagon-automation.php' );
                $all = get_plugins();
                foreach ( $all as $slug => $data ) {
                    if ( strpos( $slug, 'hexagon-automation' ) === 0 && $slug !== $current ) {
                        if ( is_plugin_active( $slug ) ) {
                            deactivate_plugins( $slug );
                        }
                    }
                }
            }

            global $wpdb;
            
            // Create default options
            add_option( 'hexagon_ai_provider', 'chatgpt' );
            add_option( 'hexagon_wizard_email', [] );
            add_option( 'hexagon_plugin_version', '3.1.0' );

            // Create logs table
            self::create_tables();
            
            // Log successful activation (after table creation)
            hexagon_log('Plugin Activated', 'Hexagon Automation v3.1.0 activated successfully', 'success');
            
        } catch (Exception $e) {
            wp_die('Hexagon Automation activation failed: ' . $e->getMessage());
        }
    }
    
    /**
     * Create database tables (can be called from auto-repair)
     */
    public static function create_tables() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        // Authentication Tables
        
        // API Keys table
        $api_keys_table = $wpdb->prefix . 'hex_api_keys';
        $api_keys_sql = "CREATE TABLE $api_keys_table (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            api_key varchar(100) NOT NULL UNIQUE,
            api_secret varchar(255) NOT NULL,
            user_id bigint(20) NOT NULL,
            name varchar(100) NOT NULL,
            permissions longtext,
            is_active tinyint(1) NOT NULL DEFAULT 1,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            last_used datetime,
            revoked_at datetime,
            usage_count int(11) NOT NULL DEFAULT 0,
            PRIMARY KEY  (id),
            KEY api_key (api_key),
            KEY user_id (user_id),
            KEY is_active (is_active)
        ) $charset_collate;";
        
        // Sessions table
        $sessions_table = $wpdb->prefix . 'hex_sessions';
        $sessions_sql = "CREATE TABLE $sessions_table (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            session_id varchar(100) NOT NULL UNIQUE,
            session_token varchar(255) NOT NULL,
            user_id bigint(20) NOT NULL,
            ip_address varchar(45),
            user_agent varchar(255),
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            expires_at datetime NOT NULL,
            last_activity datetime,
            is_active tinyint(1) NOT NULL DEFAULT 1,
            invalidated_at datetime,
            PRIMARY KEY  (id),
            KEY session_id (session_id),
            KEY user_id (user_id),
            KEY expires_at (expires_at),
            KEY is_active (is_active)
        ) $charset_collate;";
        
        // AI Provider Settings table
        $ai_providers_table = $wpdb->prefix . 'hex_ai_providers';
        $ai_providers_sql = "CREATE TABLE $ai_providers_table (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            provider_name varchar(50) NOT NULL,
            api_key varchar(255),
            model_settings longtext,
            usage_stats longtext,
            rate_limits longtext,
            is_enabled tinyint(1) NOT NULL DEFAULT 0,
            last_tested datetime,
            test_status varchar(20),
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            UNIQUE KEY provider_name (provider_name)
        ) $charset_collate;";
        
        // Generated Images table
        $images_table = $wpdb->prefix . 'hex_generated_images';
        $images_sql = "CREATE TABLE $images_table (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            image_id varchar(100) NOT NULL UNIQUE,
            provider varchar(50) NOT NULL,
            prompt text NOT NULL,
            negative_prompt text,
            style varchar(100),
            size varchar(20),
            model varchar(100),
            file_path varchar(500),
            file_url varchar(500),
            wp_attachment_id bigint(20),
            generation_time float DEFAULT 0,
            cost decimal(10,4) DEFAULT 0,
            metadata longtext,
            status varchar(20) NOT NULL DEFAULT 'pending',
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            completed_at datetime,
            PRIMARY KEY  (id),
            KEY image_id (image_id),
            KEY provider (provider),
            KEY status (status),
            KEY created_at (created_at)
        ) $charset_collate;";
        
        // Social Media Accounts table
        $social_accounts_table = $wpdb->prefix . 'hex_social_accounts';
        $social_accounts_sql = "CREATE TABLE $social_accounts_table (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            platform varchar(50) NOT NULL,
            account_id varchar(255),
            account_name varchar(255),
            access_token text,
            refresh_token text,
            token_expires_at datetime,
            account_data longtext,
            is_connected tinyint(1) NOT NULL DEFAULT 0,
            last_sync datetime,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY platform (platform),
            KEY is_connected (is_connected)
        ) $charset_collate;";
        
        // Scheduled Posts table
        $scheduled_posts_table = $wpdb->prefix . 'hex_scheduled_posts';
        $scheduled_posts_sql = "CREATE TABLE $scheduled_posts_table (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            post_id bigint(20),
            platforms longtext NOT NULL,
            content text,
            images longtext,
            scheduled_for datetime NOT NULL,
            timezone varchar(50) DEFAULT 'UTC',
            status varchar(20) NOT NULL DEFAULT 'scheduled',
            posting_results longtext,
            attempts int(3) NOT NULL DEFAULT 0,
            max_attempts int(3) NOT NULL DEFAULT 3,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            posted_at datetime,
            PRIMARY KEY  (id),
            KEY post_id (post_id),
            KEY scheduled_for (scheduled_for),
            KEY status (status)
        ) $charset_collate;";
        
        // System Settings table
        $settings_table = $wpdb->prefix . 'hex_settings';
        $settings_sql = "CREATE TABLE $settings_table (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            setting_key varchar(100) NOT NULL UNIQUE,
            setting_value longtext,
            setting_type varchar(50) NOT NULL DEFAULT 'string',
            is_encrypted tinyint(1) NOT NULL DEFAULT 0,
            category varchar(50),
            description text,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY setting_key (setting_key),
            KEY category (category)
        ) $charset_collate;";
        
        // System Logs table (enhanced)
        $logs_table = $wpdb->prefix . 'hex_logs';
        $logs_sql = "CREATE TABLE $logs_table (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            log_id varchar(50) NOT NULL UNIQUE,
            level varchar(20) NOT NULL,
            category varchar(50) NOT NULL,
            message text NOT NULL,
            context longtext,
            user_id bigint(20),
            ip_address varchar(45),
            user_agent varchar(255),
            file varchar(255),
            line int(11),
            trace text,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY log_id (log_id),
            KEY level (level),
            KEY category (category),
            KEY created_at (created_at),
            KEY user_id (user_id)
        ) $charset_collate;";
        
        // RSS Sources table
        $rss_sources_table = $wpdb->prefix . 'hex_rss_sources';
        $rss_sources_sql = "CREATE TABLE $rss_sources_table (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            source_id varchar(50) NOT NULL UNIQUE,
            name varchar(255) NOT NULL,
            url varchar(500) NOT NULL,
            category varchar(100),
            fetch_frequency int(11) NOT NULL DEFAULT 3600,
            last_fetch datetime,
            last_article_count int(11) NOT NULL DEFAULT 0,
            total_articles int(11) NOT NULL DEFAULT 0,
            auto_post tinyint(1) NOT NULL DEFAULT 0,
            post_template text,
            is_active tinyint(1) NOT NULL DEFAULT 1,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY source_id (source_id),
            KEY is_active (is_active),
            KEY last_fetch (last_fetch)
        ) $charset_collate;";
        
        // Content Generation table (enhanced)
        $content_table = $wpdb->prefix . 'hex_content_generation';
        $content_sql = "CREATE TABLE $content_table (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            generation_id varchar(50) NOT NULL UNIQUE,
            post_id bigint(20),
            prompt text NOT NULL,
            content_type varchar(50),
            content_length varchar(20),
            language varchar(10) DEFAULT 'en',
            generated_content longtext,
            ai_provider varchar(50),
            model varchar(100),
            tokens_used int(11) DEFAULT 0,
            generation_time float DEFAULT 0,
            cost decimal(10,4) DEFAULT 0,
            quality_score int(3),
            seo_score int(3),
            status varchar(20) NOT NULL DEFAULT 'pending',
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            completed_at datetime,
            PRIMARY KEY  (id),
            KEY generation_id (generation_id),
            KEY post_id (post_id),
            KEY ai_provider (ai_provider),
            KEY status (status)
        ) $charset_collate;";
        
        // Social Media Posts table (enhanced)
        $social_table = $wpdb->prefix . 'hex_social_posts';
        $social_sql = "CREATE TABLE $social_table (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            post_id bigint(20),
            platform varchar(50) NOT NULL,
            platform_post_id varchar(255),
            content text,
            images longtext,
            hashtags text,
            scheduled_for datetime,
            posted_at datetime,
            status varchar(20) NOT NULL DEFAULT 'pending',
            engagement_data longtext,
            reach_data longtext,
            performance_score int(3),
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY post_id (post_id),
            KEY platform (platform),
            KEY status (status),
            KEY scheduled_for (scheduled_for)
        ) $charset_collate;";
        
        // Error Log table (enhanced)
        $error_table = $wpdb->prefix . 'hex_error_log';
        $error_sql = "CREATE TABLE $error_table (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            error_id varchar(50) NOT NULL UNIQUE,
            timestamp datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            type varchar(50) NOT NULL,
            severity varchar(20) NOT NULL,
            message text NOT NULL,
            file varchar(255) NOT NULL,
            line int(11) NOT NULL DEFAULT 0,
            trace text,
            context longtext,
            user_id bigint(20),
            ip_address varchar(45),
            resolved tinyint(1) NOT NULL DEFAULT 0,
            resolved_at datetime,
            occurrence_count int(11) NOT NULL DEFAULT 1,
            PRIMARY KEY  (id),
            KEY error_id (error_id),
            KEY type (type),
            KEY severity (severity),
            KEY timestamp (timestamp),
            KEY resolved (resolved)
        ) $charset_collate;";
        
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        
        // Create all tables
        dbDelta($api_keys_sql);
        dbDelta($sessions_sql);
        dbDelta($ai_providers_sql);
        dbDelta($images_sql);
        dbDelta($social_accounts_sql);
        dbDelta($scheduled_posts_sql);
        dbDelta($settings_sql);
        dbDelta($logs_sql);
        dbDelta($rss_sources_sql);
        dbDelta($content_sql);
        dbDelta($social_sql);
        dbDelta($error_sql);
        
        // Insert default AI providers
        self::insert_default_ai_providers();
        
        // Insert default settings
        self::insert_default_settings();
        
        // Store creation timestamp
        update_option('hexagon_tables_created', current_time('mysql'));
        update_option('hexagon_db_version', '3.1.0');
    }
    
    private static function insert_default_ai_providers() {
        global $wpdb;
        
        $table = $wpdb->prefix . 'hex_ai_providers';
        
        $providers = [
            [
                'provider_name' => 'chatgpt',
                'model_settings' => json_encode([
                    'model' => 'gpt-4',
                    'temperature' => 0.7,
                    'max_tokens' => 2000,
                    'top_p' => 1,
                    'frequency_penalty' => 0,
                    'presence_penalty' => 0
                ]),
                'rate_limits' => json_encode([
                    'requests_per_minute' => 20,
                    'tokens_per_minute' => 40000
                ])
            ],
            [
                'provider_name' => 'claude',
                'model_settings' => json_encode([
                    'model' => 'claude-3-sonnet-20240229',
                    'max_tokens' => 2000,
                    'temperature' => 0.7
                ]),
                'rate_limits' => json_encode([
                    'requests_per_minute' => 15,
                    'tokens_per_minute' => 30000
                ])
            ],
            [
                'provider_name' => 'perplexity',
                'model_settings' => json_encode([
                    'model' => 'llama-3-sonar-large-32k-online',
                    'temperature' => 0.7,
                    'max_tokens' => 2000
                ]),
                'rate_limits' => json_encode([
                    'requests_per_minute' => 10,
                    'tokens_per_minute' => 20000
                ])
            ]
        ];
        
        foreach ($providers as $provider) {
            $wpdb->insert($table, $provider, ['%s', '%s', '%s']);
        }
    }
    
    private static function insert_default_settings() {
        global $wpdb;
        
        $table = $wpdb->prefix . 'hex_settings';
        
        $settings = [
            // General Settings
            ['setting_key' => 'site_name', 'setting_value' => get_bloginfo('name'), 'category' => 'general'],
            ['setting_key' => 'admin_email', 'setting_value' => get_option('admin_email'), 'category' => 'general'],
            ['setting_key' => 'timezone', 'setting_value' => get_option('timezone_string', 'UTC'), 'category' => 'general'],
            ['setting_key' => 'debug_mode', 'setting_value' => '0', 'category' => 'general'],
            ['setting_key' => 'language', 'setting_value' => 'en', 'category' => 'general'],
            
            // Security Settings
            ['setting_key' => 'ip_validation', 'setting_value' => '0', 'category' => 'security'],
            ['setting_key' => 'session_timeout', 'setting_value' => '86400', 'category' => 'security'],
            ['setting_key' => 'max_login_attempts', 'setting_value' => '5', 'category' => 'security'],
            ['setting_key' => 'lockout_duration', 'setting_value' => '1800', 'category' => 'security'],
            
            // Automation Settings
            ['setting_key' => 'auto_publish', 'setting_value' => '0', 'category' => 'automation'],
            ['setting_key' => 'content_review', 'setting_value' => '1', 'category' => 'automation'],
            ['setting_key' => 'social_auto_post', 'setting_value' => '0', 'category' => 'automation'],
            ['setting_key' => 'rss_fetch_frequency', 'setting_value' => '3600', 'category' => 'automation'],
            
            // Notification Settings
            ['setting_key' => 'email_notifications', 'setting_value' => '1', 'category' => 'notifications'],
            ['setting_key' => 'error_notifications', 'setting_value' => '1', 'category' => 'notifications'],
            ['setting_key' => 'weekly_reports', 'setting_value' => '1', 'category' => 'notifications'],
            
            // Performance Settings
            ['setting_key' => 'cache_enabled', 'setting_value' => '1', 'category' => 'performance'],
            ['setting_key' => 'log_retention_days', 'setting_value' => '30', 'category' => 'performance'],
            ['setting_key' => 'max_concurrent_requests', 'setting_value' => '5', 'category' => 'performance']
        ];
        
        foreach ($settings as $setting) {
            $wpdb->insert($table, $setting, ['%s', '%s', '%s']);
        }
    }

    /**
     * Runs on plugin uninstall.
     */
    public static function uninstall() {
        global $wpdb;
        // Delete options
        delete_option( 'hexagon_ai_provider' );
        delete_option( 'hexagon_wizard_email' );

        // Drop tables
        $logs_table = $wpdb->prefix . 'hex_logs';
        $images_table = $wpdb->prefix . 'hex_generated_images';
        $error_table = $wpdb->prefix . 'hex_error_log';
        $wpdb->query( "DROP TABLE IF EXISTS $logs_table" );
        $wpdb->query( "DROP TABLE IF EXISTS $images_table" );
        $wpdb->query( "DROP TABLE IF EXISTS $error_table" );
    }
}
