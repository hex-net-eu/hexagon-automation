<?php
if (!defined('ABSPATH')) exit;

class Hexagon_Setup_Wizard {
    
    private static $wizard_steps = [];
    
    public static function init() {
        add_action('wp_ajax_hexagon_start_wizard', [__CLASS__, 'ajax_start_wizard']);
        add_action('wp_ajax_hexagon_wizard_step', [__CLASS__, 'ajax_wizard_step']);
        add_action('wp_ajax_hexagon_complete_wizard', [__CLASS__, 'ajax_complete_wizard']);
        add_action('wp_ajax_hexagon_skip_wizard', [__CLASS__, 'ajax_skip_wizard']);
        
        self::define_wizard_steps();
    }
    
    private static function define_wizard_steps() {
        self::$wizard_steps = [
            'welcome' => [
                'title' => 'Welcome to Hexagon Automation',
                'description' => 'Let\'s set up your AI-powered automation system',
                'fields' => []
            ],
            'modules' => [
                'title' => 'Enable Modules',
                'description' => 'Choose which modules you want to activate',
                'fields' => [
                    'enable_ai_manager' => [
                        'type' => 'checkbox',
                        'label' => 'AI Content Manager',
                        'description' => 'AI-powered content generation',
                        'default' => true
                    ],
                    'enable_social_integration' => [
                        'type' => 'checkbox',
                        'label' => 'Social Media Integration',
                        'description' => 'Auto-posting to social platforms',
                        'default' => true
                    ],
                    'enable_email_integration' => [
                        'type' => 'checkbox',
                        'label' => 'Email Integration',
                        'description' => 'SMTP and email automation',
                        'default' => false
                    ],
                    'enable_image_generator' => [
                        'type' => 'checkbox',
                        'label' => 'AI Image Generator',
                        'description' => 'Generate images with AI',
                        'default' => false
                    ]
                ]
            ],
            'ai_setup' => [
                'title' => 'AI Configuration',
                'description' => 'Configure your AI providers',
                'fields' => [
                    'ai_primary_provider' => [
                        'type' => 'select',
                        'label' => 'Primary AI Provider',
                        'description' => 'Choose your main AI provider',
                        'options' => [
                            'chatgpt' => 'OpenAI ChatGPT',
                            'claude' => 'Anthropic Claude',
                            'perplexity' => 'Perplexity AI'
                        ],
                        'default' => 'chatgpt'
                    ],
                    'chatgpt_api_key' => [
                        'type' => 'password',
                        'label' => 'ChatGPT API Key',
                        'description' => 'Get from OpenAI Platform',
                        'required' => false
                    ],
                    'claude_api_key' => [
                        'type' => 'password',
                        'label' => 'Claude API Key',
                        'description' => 'Get from Anthropic Console',
                        'required' => false
                    ],
                    'perplexity_api_key' => [
                        'type' => 'password',
                        'label' => 'Perplexity API Key',
                        'description' => 'Get from Perplexity Settings',
                        'required' => false
                    ]
                ]
            ],
            'social_setup' => [
                'title' => 'Social Media Setup',
                'description' => 'Connect your social media accounts',
                'fields' => [
                    'auto_posting' => [
                        'type' => 'checkbox',
                        'label' => 'Enable Auto-posting',
                        'description' => 'Automatically post new content to social media',
                        'default' => false
                    ],
                    'facebook_setup' => [
                        'type' => 'info',
                        'label' => 'Facebook Setup',
                        'description' => 'You can connect Facebook later in the Social Media settings'
                    ],
                    'instagram_setup' => [
                        'type' => 'info',
                        'label' => 'Instagram Setup',
                        'description' => 'You can connect Instagram later in the Social Media settings'
                    ],
                    'twitter_setup' => [
                        'type' => 'info',
                        'label' => 'Twitter Setup',
                        'description' => 'You can connect Twitter later in the Social Media settings'
                    ]
                ]
            ],
            'email_setup' => [
                'title' => 'Email Configuration',
                'description' => 'Set up email notifications and SMTP',
                'fields' => [
                    'enable_notifications' => [
                        'type' => 'checkbox',
                        'label' => 'Enable Email Notifications',
                        'description' => 'Get notified about system events',
                        'default' => true
                    ],
                    'notification_email' => [
                        'type' => 'email',
                        'label' => 'Notification Email',
                        'description' => 'Email address for notifications',
                        'default' => get_option('admin_email')
                    ],
                    'smtp_setup' => [
                        'type' => 'checkbox',
                        'label' => 'Configure SMTP',
                        'description' => 'Use custom SMTP settings for better email delivery',
                        'default' => false
                    ]
                ]
            ],
            'security' => [
                'title' => 'Security Settings',
                'description' => 'Configure security and access settings',
                'fields' => [
                    'generate_api_key' => [
                        'type' => 'checkbox',
                        'label' => 'Generate API Key',
                        'description' => 'Create API key for dashboard access',
                        'default' => true
                    ],
                    'enable_logging' => [
                        'type' => 'checkbox',
                        'label' => 'Enable System Logging',
                        'description' => 'Log system activities for debugging',
                        'default' => true
                    ],
                    'log_level' => [
                        'type' => 'select',
                        'label' => 'Log Level',
                        'description' => 'Choose what to log',
                        'options' => [
                            'error' => 'Errors Only',
                            'warning' => 'Warnings and Errors',
                            'info' => 'All Activities',
                            'debug' => 'Debug Mode'
                        ],
                        'default' => 'info'
                    ]
                ]
            ],
            'complete' => [
                'title' => 'Setup Complete!',
                'description' => 'Your Hexagon Automation is ready to use',
                'fields' => []
            ]
        ];
    }
    
    public static function start_wizard() {
        $wizard_state = [
            'current_step' => 'welcome',
            'completed_steps' => [],
            'step_data' => [],
            'started_at' => current_time('mysql'),
            'completed' => false
        ];
        
        update_option('hexagon_wizard_state', $wizard_state);
        
        return $wizard_state;
    }
    
    public static function get_wizard_state() {
        return get_option('hexagon_wizard_state', null);
    }
    
    public static function is_wizard_completed() {
        $state = self::get_wizard_state();
        return $state && isset($state['completed']) && $state['completed'];
    }
    
    public static function process_step($step_key, $data) {
        $wizard_state = self::get_wizard_state();
        if (!$wizard_state) {
            return new WP_Error('no_wizard', 'Wizard not started');
        }
        
        if (!isset(self::$wizard_steps[$step_key])) {
            return new WP_Error('invalid_step', 'Invalid wizard step');
        }
        
        $step_config = self::$wizard_steps[$step_key];
        $processed_data = [];
        $errors = [];
        
        // Validate and process step data
        foreach ($step_config['fields'] as $field_key => $field_config) {
            $value = isset($data[$field_key]) ? $data[$field_key] : null;
            
            // Check required fields
            if (isset($field_config['required']) && $field_config['required'] && empty($value)) {
                $errors[] = "Field '{$field_config['label']}' is required";
                continue;
            }
            
            // Process based on field type
            switch ($field_config['type']) {
                case 'checkbox':
                    $processed_data[$field_key] = (bool) $value;
                    break;
                case 'email':
                    if (!empty($value) && !is_email($value)) {
                        $errors[] = "Invalid email format for '{$field_config['label']}'";
                    } else {
                        $processed_data[$field_key] = sanitize_email($value);
                    }
                    break;
                case 'password':
                    $processed_data[$field_key] = $value; // Don't sanitize passwords
                    break;
                default:
                    $processed_data[$field_key] = sanitize_text_field($value);
            }
        }
        
        if (!empty($errors)) {
            return new WP_Error('validation_failed', 'Validation failed', $errors);
        }
        
        // Apply step settings
        $apply_result = self::apply_step_settings($step_key, $processed_data);
        if (is_wp_error($apply_result)) {
            return $apply_result;
        }
        
        // Update wizard state
        $wizard_state['step_data'][$step_key] = $processed_data;
        $wizard_state['completed_steps'][] = $step_key;
        
        // Move to next step
        $step_keys = array_keys(self::$wizard_steps);
        $current_index = array_search($step_key, $step_keys);
        if ($current_index !== false && isset($step_keys[$current_index + 1])) {
            $wizard_state['current_step'] = $step_keys[$current_index + 1];
        } else {
            $wizard_state['current_step'] = 'complete';
        }
        
        update_option('hexagon_wizard_state', $wizard_state);
        
        return $wizard_state;
    }
    
    private static function apply_step_settings($step_key, $data) {
        switch ($step_key) {
            case 'modules':
                return self::apply_module_settings($data);
            case 'ai_setup':
                return self::apply_ai_settings($data);
            case 'social_setup':
                return self::apply_social_settings($data);
            case 'email_setup':
                return self::apply_email_settings($data);
            case 'security':
                return self::apply_security_settings($data);
            default:
                return true;
        }
    }
    
    private static function apply_module_settings($data) {
        $modules_config = [];
        
        $module_mapping = [
            'enable_ai_manager' => 'ai_manager',
            'enable_social_integration' => 'social_integration',
            'enable_email_integration' => 'email_integration',
            'enable_image_generator' => 'image_generator'
        ];
        
        foreach ($module_mapping as $field_key => $module_key) {
            $modules_config[$module_key] = [
                'status' => isset($data[$field_key]) && $data[$field_key] ? 'enabled' : 'disabled'
            ];
        }
        
        update_option('hexagon_modules_config', $modules_config);
        
        return true;
    }
    
    private static function apply_ai_settings($data) {
        if (!empty($data['chatgpt_api_key'])) {
            update_option('hexagon_ai_chatgpt_api_key', $data['chatgpt_api_key']);
        }
        if (!empty($data['claude_api_key'])) {
            update_option('hexagon_ai_claude_api_key', $data['claude_api_key']);
        }
        if (!empty($data['perplexity_api_key'])) {
            update_option('hexagon_ai_perplexity_api_key', $data['perplexity_api_key']);
        }
        
        if (!empty($data['ai_primary_provider'])) {
            update_option('hexagon_ai_primary_provider', $data['ai_primary_provider']);
        }
        
        return true;
    }
    
    private static function apply_social_settings($data) {
        if (isset($data['auto_posting'])) {
            update_option('hexagon_social_auto_post', $data['auto_posting']);
        }
        
        return true;
    }
    
    private static function apply_email_settings($data) {
        if (isset($data['enable_notifications'])) {
            update_option('hexagon_email_error_alerts', $data['enable_notifications']);
        }
        
        if (!empty($data['notification_email'])) {
            update_option('hexagon_notification_email', $data['notification_email']);
        }
        
        if (isset($data['smtp_setup'])) {
            update_option('hexagon_email_use_smtp', $data['smtp_setup']);
        }
        
        return true;
    }
    
    private static function apply_security_settings($data) {
        if (isset($data['generate_api_key']) && $data['generate_api_key']) {
            $api_key = wp_generate_password(32, false);
            update_option('hexagon_api_key', $api_key);
        }
        
        if (isset($data['enable_logging'])) {
            update_option('hexagon_enable_logging', $data['enable_logging']);
        }
        
        if (!empty($data['log_level'])) {
            update_option('hexagon_log_level', $data['log_level']);
        }
        
        return true;
    }
    
    public static function complete_wizard() {
        $wizard_state = self::get_wizard_state();
        if (!$wizard_state) {
            return new WP_Error('no_wizard', 'Wizard not started');
        }
        
        $wizard_state['completed'] = true;
        $wizard_state['completed_at'] = current_time('mysql');
        $wizard_state['current_step'] = 'complete';
        
        update_option('hexagon_wizard_state', $wizard_state);
        update_option('hexagon_wizard_completed', true);
        
        // Run initial system check
        if (class_exists('Hexagon_System_Diagnostic')) {
            Hexagon_System_Diagnostic::run_all_tests();
        }
        
        // Create initial backup
        if (class_exists('Hexagon_Settings_Manager')) {
            Hexagon_Settings_Manager::backup_settings('initial_setup');
        }
        
        hexagon_log('Setup Wizard', 'Setup wizard completed successfully', 'info');
        
        return $wizard_state;
    }
    
    public static function skip_wizard() {
        $wizard_state = [
            'current_step' => 'complete',
            'completed_steps' => [],
            'step_data' => [],
            'completed' => true,
            'skipped' => true,
            'skipped_at' => current_time('mysql')
        ];
        
        update_option('hexagon_wizard_state', $wizard_state);
        update_option('hexagon_wizard_completed', true);
        
        hexagon_log('Setup Wizard', 'Setup wizard skipped', 'info');
        
        return $wizard_state;
    }
    
    public static function reset_wizard() {
        delete_option('hexagon_wizard_state');
        delete_option('hexagon_wizard_completed');
        
        hexagon_log('Setup Wizard', 'Setup wizard reset', 'info');
        
        return true;
    }
    
    public static function get_wizard_data() {
        $wizard_state = self::get_wizard_state();
        
        return [
            'steps' => self::$wizard_steps,
            'state' => $wizard_state,
            'is_completed' => self::is_wizard_completed()
        ];
    }
    
    // AJAX Handlers
    public static function ajax_start_wizard() {
        check_ajax_referer('hexagon_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die('Insufficient permissions');
        }
        
        $wizard_state = self::start_wizard();
        
        wp_send_json_success([
            'state' => $wizard_state,
            'steps' => self::$wizard_steps
        ]);
    }
    
    public static function ajax_wizard_step() {
        check_ajax_referer('hexagon_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die('Insufficient permissions');
        }
        
        $step_key = sanitize_text_field($_POST['step']);
        $step_data = isset($_POST['data']) ? $_POST['data'] : [];
        
        // Sanitize step data
        $sanitized_data = [];
        foreach ($step_data as $key => $value) {
            $sanitized_data[sanitize_key($key)] = $value;
        }
        
        $result = self::process_step($step_key, $sanitized_data);
        
        if (is_wp_error($result)) {
            wp_send_json_error([
                'message' => $result->get_error_message(),
                'errors' => $result->get_error_data()
            ]);
        } else {
            wp_send_json_success($result);
        }
    }
    
    public static function ajax_complete_wizard() {
        check_ajax_referer('hexagon_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die('Insufficient permissions');
        }
        
        $result = self::complete_wizard();
        
        if (is_wp_error($result)) {
            wp_send_json_error($result->get_error_message());
        } else {
            wp_send_json_success($result);
        }
    }
    
    public static function ajax_skip_wizard() {
        check_ajax_referer('hexagon_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die('Insufficient permissions');
        }
        
        $result = self::skip_wizard();
        
        wp_send_json_success($result);
    }
}
