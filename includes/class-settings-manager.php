<?php
if (!defined('ABSPATH')) exit;

class Hexagon_Settings_Manager {
    
    private static $settings_structure = [];
    
    public static function init() {
        add_action('wp_ajax_hexagon_export_settings', [__CLASS__, 'ajax_export_settings']);
        add_action('wp_ajax_hexagon_import_settings', [__CLASS__, 'ajax_import_settings']);
        add_action('wp_ajax_hexagon_reset_settings', [__CLASS__, 'ajax_reset_settings']);
        add_action('wp_ajax_hexagon_backup_settings', [__CLASS__, 'ajax_backup_settings']);
        add_action('wp_ajax_hexagon_restore_settings', [__CLASS__, 'ajax_restore_settings']);
        
        self::define_settings_structure();
    }
    
    private static function define_settings_structure() {
        self::$settings_structure = [
            'ai' => [
                'name' => 'AI Configuration',
                'description' => 'AI provider settings and API keys',
                'options' => [
                    'hexagon_ai_chatgpt_api_key' => ['type' => 'string', 'sensitive' => true, 'default' => ''],
                    'hexagon_ai_chatgpt_model' => ['type' => 'string', 'sensitive' => false, 'default' => 'gpt-4'],
                    'hexagon_ai_chatgpt_temperature' => ['type' => 'float', 'sensitive' => false, 'default' => 0.7],
                    'hexagon_ai_chatgpt_max_tokens' => ['type' => 'integer', 'sensitive' => false, 'default' => 2000],
                    'hexagon_ai_claude_api_key' => ['type' => 'string', 'sensitive' => true, 'default' => ''],
                    'hexagon_ai_claude_model' => ['type' => 'string', 'sensitive' => false, 'default' => 'claude-3-sonnet-20240229'],
                    'hexagon_ai_claude_max_tokens' => ['type' => 'integer', 'sensitive' => false, 'default' => 2000],
                    'hexagon_ai_perplexity_api_key' => ['type' => 'string', 'sensitive' => true, 'default' => ''],
                    'hexagon_ai_perplexity_model' => ['type' => 'string', 'sensitive' => false, 'default' => 'llama-3.1-sonar-small-128k-online'],
                    'hexagon_ai_perplexity_temperature' => ['type' => 'float', 'sensitive' => false, 'default' => 0.2],
                    'hexagon_ai_perplexity_max_tokens' => ['type' => 'integer', 'sensitive' => false, 'default' => 2000]
                ]
            ],
            'email' => [
                'name' => 'Email Configuration',
                'description' => 'SMTP and email automation settings',
                'options' => [
                    'hexagon_email_use_smtp' => ['type' => 'boolean', 'sensitive' => false, 'default' => false],
                    'hexagon_email_smtp_host' => ['type' => 'string', 'sensitive' => false, 'default' => ''],
                    'hexagon_email_smtp_port' => ['type' => 'integer', 'sensitive' => false, 'default' => 587],
                    'hexagon_email_smtp_username' => ['type' => 'string', 'sensitive' => false, 'default' => ''],
                    'hexagon_email_smtp_password' => ['type' => 'string', 'sensitive' => true, 'default' => ''],
                    'hexagon_email_smtp_encryption' => ['type' => 'string', 'sensitive' => false, 'default' => 'tls'],
                    'hexagon_email_daily_digest' => ['type' => 'boolean', 'sensitive' => false, 'default' => false],
                    'hexagon_email_error_alerts' => ['type' => 'boolean', 'sensitive' => false, 'default' => true]
                ]
            ],
            'social' => [
                'name' => 'Social Media Integration',
                'description' => 'Social platform tokens and posting settings',
                'options' => [
                    'hexagon_social_auto_post' => ['type' => 'boolean', 'sensitive' => false, 'default' => false],
                    'hexagon_social_auto_platforms' => ['type' => 'array', 'sensitive' => false, 'default' => []],
                    'hexagon_social_post_template' => ['type' => 'string', 'sensitive' => false, 'default' => '{title} {excerpt}'],
                    'hexagon_social_facebook_token' => ['type' => 'string', 'sensitive' => true, 'default' => ''],
                    'hexagon_social_facebook_page_id' => ['type' => 'string', 'sensitive' => false, 'default' => ''],
                    'hexagon_social_instagram_token' => ['type' => 'string', 'sensitive' => true, 'default' => ''],
                    'hexagon_social_instagram_account_id' => ['type' => 'string', 'sensitive' => false, 'default' => ''],
                    'hexagon_social_twitter_api_key' => ['type' => 'string', 'sensitive' => true, 'default' => ''],
                    'hexagon_social_twitter_api_secret' => ['type' => 'string', 'sensitive' => true, 'default' => ''],
                    'hexagon_social_twitter_access_token' => ['type' => 'string', 'sensitive' => true, 'default' => ''],
                    'hexagon_social_twitter_access_secret' => ['type' => 'string', 'sensitive' => true, 'default' => ''],
                    'hexagon_social_linkedin_token' => ['type' => 'string', 'sensitive' => true, 'default' => ''],
                    'hexagon_social_linkedin_person_id' => ['type' => 'string', 'sensitive' => false, 'default' => '']
                ]
            ],
            'image' => [
                'name' => 'Image Generation',
                'description' => 'AI image generation provider settings',
                'options' => [
                    'hexagon_image_dalle_api_key' => ['type' => 'string', 'sensitive' => true, 'default' => ''],
                    'hexagon_image_dalle_model' => ['type' => 'string', 'sensitive' => false, 'default' => 'dall-e-3'],
                    'hexagon_image_dalle_usage' => ['type' => 'integer', 'sensitive' => false, 'default' => 0],
                    'hexagon_image_dalle_limit' => ['type' => 'integer', 'sensitive' => false, 'default' => 1000],
                    'hexagon_image_midjourney_api_key' => ['type' => 'string', 'sensitive' => true, 'default' => ''],
                    'hexagon_image_midjourney_model' => ['type' => 'string', 'sensitive' => false, 'default' => 'midjourney-v6'],
                    'hexagon_image_midjourney_usage' => ['type' => 'integer', 'sensitive' => false, 'default' => 0],
                    'hexagon_image_midjourney_limit' => ['type' => 'integer', 'sensitive' => false, 'default' => 500],
                    'hexagon_image_stable_api_key' => ['type' => 'string', 'sensitive' => true, 'default' => ''],
                    'hexagon_image_stable_model' => ['type' => 'string', 'sensitive' => false, 'default' => 'stable-diffusion-xl'],
                    'hexagon_image_stable_usage' => ['type' => 'integer', 'sensitive' => false, 'default' => 0],
                    'hexagon_image_stable_limit' => ['type' => 'integer', 'sensitive' => false, 'default' => 0]
                ]
            ],
            'system' => [
                'name' => 'System Configuration',
                'description' => 'General plugin and system settings',
                'options' => [
                    'hexagon_api_key' => ['type' => 'string', 'sensitive' => true, 'default' => ''],
                    'hexagon_debug_mode' => ['type' => 'boolean', 'sensitive' => false, 'default' => false],
                    'hexagon_log_level' => ['type' => 'string', 'sensitive' => false, 'default' => 'info'],
                    'hexagon_auto_repair' => ['type' => 'boolean', 'sensitive' => false, 'default' => true],
                    'hexagon_error_alerts' => ['type' => 'boolean', 'sensitive' => false, 'default' => false],
                    'hexagon_plugin_version' => ['type' => 'string', 'sensitive' => false, 'default' => HEXAGON_VERSION],
                    'hexagon_modules_config' => ['type' => 'array', 'sensitive' => false, 'default' => []]
                ]
            ]
        ];
    }
    
    public static function export_settings($include_sensitive = false, $categories = null) {
        $export_data = [
            'export_info' => [
                'timestamp' => current_time('mysql'),
                'plugin_version' => HEXAGON_VERSION,
                'wordpress_version' => get_bloginfo('version'),
                'php_version' => PHP_VERSION,
                'site_url' => get_site_url(),
                'include_sensitive' => $include_sensitive
            ],
            'settings' => []
        ];
        
        $categories_to_export = $categories ?: array_keys(self::$settings_structure);
        
        foreach ($categories_to_export as $category) {
            if (!isset(self::$settings_structure[$category])) {
                continue;
            }
            
            $category_data = self::$settings_structure[$category];
            $export_data['settings'][$category] = [
                'name' => $category_data['name'],
                'description' => $category_data['description'],
                'options' => []
            ];
            
            foreach ($category_data['options'] as $option_name => $option_config) {
                // Skip sensitive data if not requested
                if ($option_config['sensitive'] && !$include_sensitive) {
                    $export_data['settings'][$category]['options'][$option_name] = [
                        'value' => '[REDACTED]',
                        'type' => $option_config['type'],
                        'sensitive' => true
                    ];
                    continue;
                }
                
                $value = get_option($option_name, $option_config['default']);
                
                // Convert value based on type
                switch ($option_config['type']) {
                    case 'boolean':
                        $value = (bool) $value;
                        break;
                    case 'integer':
                        $value = (int) $value;
                        break;
                    case 'float':
                        $value = (float) $value;
                        break;
                    case 'array':
                        $value = is_array($value) ? $value : [];
                        break;
                    default:
                        $value = (string) $value;
                }
                
                $export_data['settings'][$category]['options'][$option_name] = [
                    'value' => $value,
                    'type' => $option_config['type'],
                    'sensitive' => $option_config['sensitive']
                ];
            }
        }
        
        return $export_data;
    }
    
    public static function import_settings($settings_data, $overwrite_existing = false, $skip_sensitive = false) {
        if (!is_array($settings_data) || !isset($settings_data['settings'])) {
            return new WP_Error('invalid_format', 'Invalid settings format');
        }
        
        $imported_count = 0;
        $skipped_count = 0;
        $errors = [];
        
        foreach ($settings_data['settings'] as $category => $category_data) {
            if (!isset(self::$settings_structure[$category])) {
                $errors[] = "Unknown category: {$category}";
                continue;
            }
            
            if (!isset($category_data['options'])) {
                $errors[] = "No options in category: {$category}";
                continue;
            }
            
            foreach ($category_data['options'] as $option_name => $option_data) {
                // Check if option exists in our structure
                if (!isset(self::$settings_structure[$category]['options'][$option_name])) {
                    $errors[] = "Unknown option: {$option_name}";
                    continue;
                }
                
                $option_config = self::$settings_structure[$category]['options'][$option_name];
                
                // Skip sensitive data if requested
                if ($skip_sensitive && $option_config['sensitive']) {
                    $skipped_count++;
                    continue;
                }
                
                // Skip if value is redacted
                if (isset($option_data['value']) && $option_data['value'] === '[REDACTED]') {
                    $skipped_count++;
                    continue;
                }
                
                // Check if option already exists and overwrite setting
                $current_value = get_option($option_name);
                if (!empty($current_value) && !$overwrite_existing) {
                    $skipped_count++;
                    continue;
                }
                
                // Validate and convert value
                $value = self::validate_option_value($option_data['value'], $option_config);
                if ($value === false) {
                    $errors[] = "Invalid value for option: {$option_name}";
                    continue;
                }
                
                // Update the option
                update_option($option_name, $value);
                $imported_count++;
            }
        }
        
        // Log the import
        hexagon_log('Settings Import', "Imported {$imported_count} settings, skipped {$skipped_count}", 'info');
        
        return [
            'success' => true,
            'imported' => $imported_count,
            'skipped' => $skipped_count,
            'errors' => $errors
        ];
    }
    
    private static function validate_option_value($value, $config) {
        switch ($config['type']) {
            case 'boolean':
                return filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) !== null;
            case 'integer':
                return filter_var($value, FILTER_VALIDATE_INT) !== false;
            case 'float':
                return filter_var($value, FILTER_VALIDATE_FLOAT) !== false;
            case 'array':
                return is_array($value);
            case 'string':
                return is_string($value) || is_numeric($value);
            default:
                return false;
        }
    }
    
    public static function reset_settings($categories = null) {
        $categories_to_reset = $categories ?: array_keys(self::$settings_structure);
        $reset_count = 0;
        
        foreach ($categories_to_reset as $category) {
            if (!isset(self::$settings_structure[$category])) {
                continue;
            }
            
            foreach (self::$settings_structure[$category]['options'] as $option_name => $option_config) {
                delete_option($option_name);
                $reset_count++;
            }
        }
        
        hexagon_log('Settings Reset', "Reset {$reset_count} settings", 'info');
        
        return $reset_count;
    }
    
    public static function backup_settings($backup_name = null) {
        if (!$backup_name) {
            $backup_name = 'auto_backup_' . date('Y_m_d_H_i_s');
        }
        
        $backup_data = self::export_settings(true); // Include sensitive data in backups
        $backup_data['backup_info'] = [
            'name' => $backup_name,
            'created_at' => current_time('mysql'),
            'created_by' => get_current_user_id()
        ];
        
        $backups = get_option('hexagon_settings_backups', []);
        $backups[$backup_name] = $backup_data;
        
        // Keep only last 10 backups
        if (count($backups) > 10) {
            $backups = array_slice($backups, -10, null, true);
        }
        
        update_option('hexagon_settings_backups', $backups);
        
        hexagon_log('Settings Backup', "Created backup: {$backup_name}", 'info');
        
        return $backup_name;
    }
    
    public static function restore_settings($backup_name) {
        $backups = get_option('hexagon_settings_backups', []);
        
        if (!isset($backups[$backup_name])) {
            return new WP_Error('backup_not_found', 'Backup not found');
        }
        
        $backup_data = $backups[$backup_name];
        
        // Create current backup before restoring
        self::backup_settings('before_restore_' . date('Y_m_d_H_i_s'));
        
        $result = self::import_settings($backup_data, true, false);
        
        if ($result['success']) {
            hexagon_log('Settings Restore', "Restored from backup: {$backup_name}", 'info');
        }
        
        return $result;
    }
    
    public static function get_backups() {
        $backups = get_option('hexagon_settings_backups', []);
        $backup_list = [];
        
        foreach ($backups as $name => $data) {
            $backup_list[$name] = [
                'name' => $name,
                'created_at' => $data['backup_info']['created_at'],
                'created_by' => $data['backup_info']['created_by'],
                'plugin_version' => $data['export_info']['plugin_version'],
                'size' => strlen(json_encode($data))
            ];
        }
        
        return $backup_list;
    }
    
    public static function delete_backup($backup_name) {
        $backups = get_option('hexagon_settings_backups', []);
        
        if (isset($backups[$backup_name])) {
            unset($backups[$backup_name]);
            update_option('hexagon_settings_backups', $backups);
            
            hexagon_log('Settings Backup', "Deleted backup: {$backup_name}", 'info');
            return true;
        }
        
        return false;
    }
    
    // AJAX Handlers
    public static function ajax_export_settings() {
        check_ajax_referer('hexagon_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die('Insufficient permissions');
        }
        
        $include_sensitive = isset($_POST['include_sensitive']) && $_POST['include_sensitive'] === 'true';
        $categories = isset($_POST['categories']) ? array_map('sanitize_text_field', $_POST['categories']) : null;
        $format = sanitize_text_field($_POST['format'] ?? 'json');
        
        $settings_data = self::export_settings($include_sensitive, $categories);
        
        switch ($format) {
            case 'json':
                $output = json_encode($settings_data, JSON_PRETTY_PRINT);
                $filename = 'hexagon-settings-' . date('Y-m-d-H-i-s') . '.json';
                break;
            case 'php':
                $output = "<?php\n// Hexagon Automation Settings Export\n// Generated: " . date('Y-m-d H:i:s') . "\n\nreturn " . var_export($settings_data, true) . ";\n";
                $filename = 'hexagon-settings-' . date('Y-m-d-H-i-s') . '.php';
                break;
            default:
                wp_send_json_error('Invalid format');
                return;
        }
        
        wp_send_json_success([
            'data' => $output,
            'filename' => $filename,
            'size' => strlen($output)
        ]);
    }
    
    public static function ajax_import_settings() {
        check_ajax_referer('hexagon_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die('Insufficient permissions');
        }
        
        $settings_json = stripslashes($_POST['settings_data']);
        $overwrite_existing = isset($_POST['overwrite_existing']) && $_POST['overwrite_existing'] === 'true';
        $skip_sensitive = isset($_POST['skip_sensitive']) && $_POST['skip_sensitive'] === 'true';
        
        $settings_data = json_decode($settings_json, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            wp_send_json_error('Invalid JSON format');
            return;
        }
        
        $result = self::import_settings($settings_data, $overwrite_existing, $skip_sensitive);
        
        if (is_wp_error($result)) {
            wp_send_json_error($result->get_error_message());
        } else {
            wp_send_json_success($result);
        }
    }
    
    public static function ajax_reset_settings() {
        check_ajax_referer('hexagon_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die('Insufficient permissions');
        }
        
        $categories = isset($_POST['categories']) ? array_map('sanitize_text_field', $_POST['categories']) : null;
        
        // Create backup before reset
        self::backup_settings('before_reset_' . date('Y_m_d_H_i_s'));
        
        $reset_count = self::reset_settings($categories);
        
        wp_send_json_success(['reset_count' => $reset_count]);
    }
    
    public static function ajax_backup_settings() {
        check_ajax_referer('hexagon_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die('Insufficient permissions');
        }
        
        $backup_name = sanitize_text_field($_POST['backup_name'] ?? '');
        $backup_name = self::backup_settings($backup_name ?: null);
        
        wp_send_json_success(['backup_name' => $backup_name]);
    }
    
    public static function ajax_restore_settings() {
        check_ajax_referer('hexagon_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die('Insufficient permissions');
        }
        
        $backup_name = sanitize_text_field($_POST['backup_name']);
        $result = self::restore_settings($backup_name);
        
        if (is_wp_error($result)) {
            wp_send_json_error($result->get_error_message());
        } else {
            wp_send_json_success($result);
        }
    }
}