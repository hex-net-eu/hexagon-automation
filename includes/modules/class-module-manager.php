<?php
if (!defined('ABSPATH')) exit;

class Hexagon_Module_Manager {
    
    private static $modules = [
        'ai_manager' => [
            'class' => 'Hexagon_AI_Manager',
            'file' => 'class-hexagon-ai-manager.php',
            'name' => 'AI Content Manager',
            'description' => 'AI-powered content generation with ChatGPT, Claude, and Perplexity',
            'dependencies' => [],
            'status' => 'enabled'
        ],
        'email_integration' => [
            'class' => 'Hexagon_Email_Integration',
            'file' => 'class-email-integration.php',
            'name' => 'Email Integration',
            'description' => 'SMTP email automation and notifications',
            'dependencies' => [],
            'status' => 'enabled'
        ],
        'social_integration' => [
            'class' => 'Hexagon_Social_Integration',
            'file' => 'class-social-integration.php',
            'name' => 'Social Media Integration',
            'description' => 'Facebook, Instagram, Twitter, LinkedIn posting automation',
            'dependencies' => [],
            'status' => 'enabled'
        ],
        'image_generator' => [
            'class' => 'Hexagon_Image_Generator',
            'file' => 'class-image-generator.php',
            'name' => 'AI Image Generator',
            'description' => 'DALL-E, Midjourney, Stable Diffusion image generation',
            'dependencies' => ['ai_manager'],
            'status' => 'enabled'
        ],
        'content_manager' => [
            'class' => 'Hexagon_Content_Manager',
            'file' => 'class-content-manager.php',
            'name' => 'Content Manager',
            'description' => 'WordPress post and page automation',
            'dependencies' => ['ai_manager'],
            'status' => 'enabled'
        ],
        'social_scheduler' => [
            'class' => 'Hexagon_Social_Scheduler',
            'file' => 'class-social-scheduler.php',
            'name' => 'Social Media Scheduler',
            'description' => 'Advanced post scheduling and automation',
            'dependencies' => ['social_integration'],
            'status' => 'enabled'
        ],
        'auto_repair' => [
            'class' => 'Hexagon_Auto_Repair',
            'file' => 'class-auto-repair.php',
            'name' => 'Auto Repair System',
            'description' => 'Automatic system health monitoring and repair',
            'dependencies' => [],
            'status' => 'enabled'
        ],
        'system_tester' => [
            'class' => 'Hexagon_System_Tester',
            'file' => 'class-system-tester.php',
            'name' => 'System Tester',
            'description' => 'Comprehensive system testing and diagnostics',
            'dependencies' => [],
            'status' => 'enabled'
        ],
        'rest_api' => [
            'class' => 'Hexagon_Rest_Api',
            'file' => 'class-rest-api.php',
            'name' => 'REST API',
            'description' => 'API endpoints for dashboard and external integrations',
            'dependencies' => [],
            'status' => 'enabled'
        ]
    ];
    
    public static function init() {
        add_action('admin_init', [__CLASS__, 'load_enabled_modules']);
        add_action('wp_ajax_hexagon_toggle_module', [__CLASS__, 'ajax_toggle_module']);
        add_action('wp_ajax_hexagon_test_module', [__CLASS__, 'ajax_test_module']);
    }
    
    public static function get_modules() {
        $stored_modules = get_option('hexagon_modules_config', []);
        
        // Merge with defaults
        foreach (self::$modules as $key => $module) {
            if (isset($stored_modules[$key])) {
                self::$modules[$key]['status'] = $stored_modules[$key]['status'];
            }
        }
        
        return self::$modules;
    }
    
    public static function is_module_enabled($module_key) {
        $modules = self::get_modules();
        return isset($modules[$module_key]) && $modules[$module_key]['status'] === 'enabled';
    }
    
    public static function enable_module($module_key) {
        if (!isset(self::$modules[$module_key])) {
            return new WP_Error('invalid_module', 'Module not found');
        }
        
        $module = self::$modules[$module_key];
        
        // Check dependencies
        foreach ($module['dependencies'] as $dependency) {
            if (!self::is_module_enabled($dependency)) {
                return new WP_Error('dependency_error', "Module requires {$dependency} to be enabled");
            }
        }
        
        // Update status
        $stored_modules = get_option('hexagon_modules_config', []);
        $stored_modules[$module_key]['status'] = 'enabled';
        update_option('hexagon_modules_config', $stored_modules);
        
        // Try to load the module
        $result = self::load_module($module_key);
        if (is_wp_error($result)) {
            // Revert if loading failed
            $stored_modules[$module_key]['status'] = 'disabled';
            update_option('hexagon_modules_config', $stored_modules);
            return $result;
        }
        
        hexagon_log('Module Enabled', "Module {$module_key} enabled successfully", 'info');
        return true;
    }
    
    public static function disable_module($module_key) {
        if (!isset(self::$modules[$module_key])) {
            return new WP_Error('invalid_module', 'Module not found');
        }
        
        // Check if other modules depend on this one
        foreach (self::$modules as $key => $module) {
            if (in_array($module_key, $module['dependencies']) && self::is_module_enabled($key)) {
                return new WP_Error('dependency_error', "Cannot disable {$module_key} - {$key} depends on it");
            }
        }
        
        // Update status
        $stored_modules = get_option('hexagon_modules_config', []);
        $stored_modules[$module_key]['status'] = 'disabled';
        update_option('hexagon_modules_config', $stored_modules);
        
        hexagon_log('Module Disabled', "Module {$module_key} disabled", 'info');
        return true;
    }
    
    public static function load_enabled_modules() {
        $modules = self::get_modules();
        
        foreach ($modules as $key => $module) {
            if ($module['status'] === 'enabled') {
                self::load_module($key);
            }
        }
    }
    
    public static function load_module($module_key) {
        if (!isset(self::$modules[$module_key])) {
            return new WP_Error('invalid_module', 'Module not found');
        }
        
        $module = self::$modules[$module_key];
        $file_path = HEXAGON_PATH . 'includes/modules/' . $module['file'];
        
        if (!file_exists($file_path)) {
            return new WP_Error('file_not_found', "Module file not found: {$module['file']}");
        }
        
        try {
            require_once $file_path;
            
            if (!class_exists($module['class'])) {
                return new WP_Error('class_not_found', "Module class not found: {$module['class']}");
            }
            
            // Initialize module if it has init method
            if (method_exists($module['class'], 'init')) {
                call_user_func([$module['class'], 'init']);
            }
            
            return true;
            
        } catch (Exception $e) {
            hexagon_log('Module Load Error', "Failed to load {$module_key}: " . $e->getMessage(), 'error');
            return new WP_Error('load_error', $e->getMessage());
        }
    }
    
    public static function test_module($module_key) {
        if (!isset(self::$modules[$module_key])) {
            return new WP_Error('invalid_module', 'Module not found');
        }
        
        $module = self::$modules[$module_key];
        
        // Check if module is loaded
        if (!class_exists($module['class'])) {
            return [
                'status' => 'error',
                'message' => 'Module class not loaded'
            ];
        }
        
        // Run module-specific tests
        $tests = [];
        
        switch ($module_key) {
            case 'ai_manager':
                $tests = self::test_ai_manager();
                break;
            case 'email_integration':
                $tests = self::test_email_integration();
                break;
            case 'social_integration':
                $tests = self::test_social_integration();
                break;
            case 'rest_api':
                $tests = self::test_rest_api();
                break;
            default:
                $tests = [
                    'basic' => [
                        'status' => 'success',
                        'message' => 'Module loaded successfully'
                    ]
                ];
        }
        
        return [
            'status' => 'success',
            'tests' => $tests
        ];
    }
    
    private static function test_ai_manager() {
        $tests = [];
        
        // Test API keys
        $providers = ['chatgpt', 'claude', 'perplexity'];
        foreach ($providers as $provider) {
            $api_key = hexagon_get_option("hexagon_ai_{$provider}_api_key");
            $tests["api_key_{$provider}"] = [
                'status' => !empty($api_key) ? 'success' : 'warning',
                'message' => !empty($api_key) ? "{$provider} API key configured" : "{$provider} API key not set"
            ];
        }
        
        return $tests;
    }
    
    private static function test_email_integration() {
        $tests = [];
        
        // Test SMTP configuration
        $use_smtp = hexagon_get_option('hexagon_email_use_smtp', false);
        $smtp_host = hexagon_get_option('hexagon_email_smtp_host', '');
        
        $tests['smtp_config'] = [
            'status' => ($use_smtp && !empty($smtp_host)) ? 'success' : 'warning',
            'message' => ($use_smtp && !empty($smtp_host)) ? 'SMTP configured' : 'SMTP not configured'
        ];
        
        return $tests;
    }
    
    private static function test_social_integration() {
        $tests = [];
        
        $platforms = ['facebook', 'instagram', 'twitter', 'linkedin'];
        foreach ($platforms as $platform) {
            $token = hexagon_get_option("hexagon_social_{$platform}_token");
            $tests["platform_{$platform}"] = [
                'status' => !empty($token) ? 'success' : 'warning',
                'message' => !empty($token) ? "{$platform} connected" : "{$platform} not connected"
            ];
        }
        
        return $tests;
    }
    
    private static function test_rest_api() {
        $tests = [];
        
        // Test if REST API is accessible
        $api_url = rest_url('hexagon/v1/status');
        $response = wp_remote_get($api_url);
        
        $tests['api_accessibility'] = [
            'status' => !is_wp_error($response) ? 'success' : 'error',
            'message' => !is_wp_error($response) ? 'REST API accessible' : 'REST API not accessible'
        ];
        
        return $tests;
    }
    
    public static function ajax_toggle_module() {
        check_ajax_referer('hexagon_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die('Insufficient permissions');
        }
        
        $module_key = sanitize_text_field($_POST['module']);
        $action = sanitize_text_field($_POST['toggle_action']); // 'enable' or 'disable'
        
        if ($action === 'enable') {
            $result = self::enable_module($module_key);
        } else {
            $result = self::disable_module($module_key);
        }
        
        if (is_wp_error($result)) {
            wp_send_json_error($result->get_error_message());
        } else {
            wp_send_json_success("Module {$action}d successfully");
        }
    }
    
    public static function ajax_test_module() {
        check_ajax_referer('hexagon_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die('Insufficient permissions');
        }
        
        $module_key = sanitize_text_field($_POST['module']);
        $result = self::test_module($module_key);
        
        wp_send_json_success($result);
    }
    
    public static function get_module_status_summary() {
        $modules = self::get_modules();
        $enabled = 0;
        $disabled = 0;
        $errors = 0;
        
        foreach ($modules as $module) {
            if ($module['status'] === 'enabled') {
                $enabled++;
            } else {
                $disabled++;
            }
        }
        
        return [
            'total' => count($modules),
            'enabled' => $enabled,
            'disabled' => $disabled,
            'errors' => $errors
        ];
    }
}