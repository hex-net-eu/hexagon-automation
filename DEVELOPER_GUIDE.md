# Hexagon Automation v3.0.0 - Developer Guide

## 🏗️ Architecture Overview

Hexagon Automation follows a modular architecture with clear separation of concerns:

```
hexagon-automation/
├── hexagon-automation.php     # Main plugin file
├── includes/
│   ├── class-hexagon-loader.php       # Module loader
│   ├── class-hexagon-automation.php   # Main class
│   ├── class-hexagon-activation.php   # Installation/activation
│   ├── functions.php                  # Global functions
│   ├── modules/                       # Feature modules
│   │   ├── class-hexagon-ai-manager.php
│   │   ├── class-email-integration.php
│   │   ├── class-social-integration.php
│   │   ├── class-rest-api.php
│   │   ├── class-auto-repair.php
│   │   └── class-system-tester.php
│   └── providers/                     # AI provider classes
│       ├── class-ai-chatgpt.php
│       ├── class-ai-claude.php
│       └── class-ai-perplexity.php
├── dashboard/                         # React dashboard
└── admin/                            # WordPress admin interface
```

## 🔧 Core Components

### Main Plugin Class
```php
class Hexagon_Automation {
    private static $instance;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function init_modules() {
        // Load all modules dynamically
        $modules = [
            'Hexagon_Hexagon_Ai_Manager',
            'Hexagon_Email_Integration',
            'Hexagon_Social_Integration',
            'Hexagon_Rest_Api',
            'Hexagon_Auto_Repair'
        ];
        
        foreach ($modules as $module) {
            if (class_exists($module)) {
                $module::init();
            }
        }
    }
}
```

### Module Structure
Each module follows this pattern:
```php
class Hexagon_Module_Name {
    public static function init() {
        // Hook into WordPress actions/filters
        add_action('wp_ajax_module_action', [__CLASS__, 'handle_action']);
        add_action('init', [__CLASS__, 'setup_hooks']);
    }
    
    public static function handle_action() {
        // AJAX handler with nonce verification
        check_ajax_referer('module_nonce', 'nonce');
        
        try {
            // Module functionality
            $result = self::process_request();
            wp_send_json_success($result);
        } catch (Exception $e) {
            hexagon_log('Module Error', $e->getMessage(), 'error');
            wp_send_json_error(['message' => $e->getMessage()]);
        }
    }
}
```

## 🤖 AI Module Development

### Adding New AI Providers
Create a new provider class in `/includes/providers/`:

```php
class Hexagon_Ai_NewProvider {
    private static $api_endpoint = 'https://api.newprovider.com/v1/';
    
    public static function init() {
        // Register provider
        add_filter('hexagon_ai_providers', [__CLASS__, 'register_provider']);
    }
    
    public static function register_provider($providers) {
        $providers['newprovider'] = [
            'name' => 'New Provider',
            'class' => __CLASS__,
            'models' => ['model-1', 'model-2']
        ];
        return $providers;
    }
    
    public static function generate_content($prompt, $options = []) {
        $api_key = hexagon_get_option('hexagon_ai_newprovider_api_key');
        
        $body = [
            'prompt' => $prompt,
            'model' => $options['model'] ?? 'model-1',
            'max_tokens' => $options['max_tokens'] ?? 1000
        ];
        
        $response = wp_remote_post(self::$api_endpoint . 'generate', [
            'headers' => [
                'Authorization' => 'Bearer ' . $api_key,
                'Content-Type' => 'application/json'
            ],
            'body' => json_encode($body),
            'timeout' => 60
        ]);
        
        if (is_wp_error($response)) {
            throw new Exception('API Error: ' . $response->get_error_message());
        }
        
        $data = json_decode(wp_remote_retrieve_body($response), true);
        
        if (isset($data['error'])) {
            throw new Exception('Provider Error: ' . $data['error']['message']);
        }
        
        return [
            'content' => $data['text'],
            'usage' => $data['usage'] ?? [],
            'provider' => 'New Provider'
        ];
    }
}
```

### Adding Content Types
Extend the AI Manager with new content types:

```php
add_filter('hexagon_ai_content_types', function($types) {
    $types['newsletter'] = [
        'name' => 'Newsletter',
        'prompt' => 'Create an engaging newsletter with the following content: {content}',
        'max_tokens' => 1500,
        'temperature' => 0.8
    ];
    return $types;
});
```

## 📧 Email Module Extension

### Custom Email Templates
Add custom email templates:

```php
add_filter('hexagon_email_templates', function($templates) {
    $templates['welcome'] = [
        'subject' => 'Welcome to {site_name}!',
        'body' => '<h1>Welcome!</h1><p>Thank you for joining us.</p>',
        'variables' => ['site_name', 'user_name', 'user_email']
    ];
    return $templates;
});
```

### Email Event Hooks
Hook into email events:

```php
// Before email is sent
add_action('hexagon_before_email_send', function($to, $subject, $message) {
    hexagon_log('Email Sending', "To: $to, Subject: $subject", 'info');
});

// After email is sent
add_action('hexagon_after_email_send', function($result, $to, $subject) {
    if ($result) {
        hexagon_log('Email Sent', "Successfully sent to $to", 'success');
    } else {
        hexagon_log('Email Failed', "Failed to send to $to", 'error');
    }
});
```

## 📱 Social Media Module Extension

### Adding New Platforms
Create a new social platform handler:

```php
class Hexagon_Social_NewPlatform {
    public static function init() {
        add_filter('hexagon_social_platforms', [__CLASS__, 'register_platform']);
    }
    
    public static function register_platform($platforms) {
        $platforms['newplatform'] = [
            'name' => 'New Platform',
            'class' => __CLASS__,
            'auth_type' => 'oauth2',
            'post_types' => ['text', 'image', 'video']
        ];
        return $platforms;
    }
    
    public static function post($message, $options = []) {
        $access_token = hexagon_get_option('hexagon_social_newplatform_token');
        
        $data = [
            'content' => $message,
            'visibility' => 'public'
        ];
        
        if (!empty($options['image_url'])) {
            $data['media'] = ['url' => $options['image_url']];
        }
        
        $response = wp_remote_post('https://api.newplatform.com/posts', [
            'headers' => [
                'Authorization' => 'Bearer ' . $access_token,
                'Content-Type' => 'application/json'
            ],
            'body' => json_encode($data)
        ]);
        
        // Handle response...
        
        return [
            'platform' => 'New Platform',
            'post_id' => $response_data['id'],
            'url' => $response_data['url']
        ];
    }
}
```

## 🔧 Auto-Repair System Extension

### Custom Health Checks
Add custom health monitoring:

```php
add_filter('hexagon_health_checks', function($checks) {
    $checks['custom_service'] = [
        'name' => 'Custom Service Health',
        'callback' => 'check_custom_service_health',
        'frequency' => 'hourly',
        'critical' => true
    ];
    return $checks;
});

function check_custom_service_health() {
    $issues = [];
    
    // Check external service
    $response = wp_remote_get('https://api.yourservice.com/health');
    if (is_wp_error($response)) {
        $issues[] = 'External service unreachable';
    }
    
    // Check database custom tables
    global $wpdb;
    $table_exists = $wpdb->get_var("SHOW TABLES LIKE 'custom_table'");
    if (!$table_exists) {
        $issues[] = 'Custom table missing';
    }
    
    return $issues;
}
```

### Custom Repair Actions
Add automatic repair functionality:

```php
add_action('hexagon_auto_repair_custom_service', function($issues) {
    foreach ($issues as $issue) {
        if (strpos($issue, 'Custom table missing') !== false) {
            // Recreate custom table
            create_custom_table();
            hexagon_log('Auto Repair', 'Recreated custom table', 'success');
        }
    }
});
```

## 🌐 REST API Extension

### Custom Endpoints
Add custom REST API endpoints:

```php
class Custom_Rest_Endpoints {
    public static function init() {
        add_action('rest_api_init', [__CLASS__, 'register_routes']);
    }
    
    public static function register_routes() {
        register_rest_route('hexagon/v1', '/custom/endpoint', [
            'methods' => 'POST',
            'callback' => [__CLASS__, 'handle_custom_request'],
            'permission_callback' => [__CLASS__, 'check_permissions'],
            'args' => [
                'data' => ['required' => true, 'type' => 'string']
            ]
        ]);
    }
    
    public static function handle_custom_request($request) {
        $data = $request->get_param('data');
        
        // Process custom logic
        $result = process_custom_data($data);
        
        return rest_ensure_response([
            'success' => true,
            'data' => $result
        ]);
    }
    
    public static function check_permissions($request) {
        // Use existing permission system
        return Hexagon_Rest_Api::check_permissions($request);
    }
}
```

## 🧪 Testing Framework

### Writing Custom Tests
Add tests to the system tester:

```php
add_filter('hexagon_system_tests', function($tests) {
    $tests['custom'] = [
        'name' => 'Custom Module Tests',
        'callback' => 'run_custom_tests'
    ];
    return $tests;
});

function run_custom_tests() {
    $results = [];
    
    // Test custom functionality
    $results[] = [
        'name' => 'Custom Service Connection',
        'status' => check_custom_service() ? 'pass' : 'fail',
        'message' => 'Custom service connectivity check'
    ];
    
    return $results;
}
```

### Unit Testing
For PHPUnit integration:

```php
class Test_Hexagon_Custom extends WP_UnitTestCase {
    
    public function setUp() {
        parent::setUp();
        // Setup test environment
        activate_plugin('hexagon-automation/hexagon-automation.php');
    }
    
    public function test_custom_functionality() {
        $result = custom_function('test_input');
        $this->assertEquals('expected_output', $result);
    }
    
    public function test_ai_integration() {
        // Mock AI provider response
        add_filter('pre_http_request', function($preempt, $args, $url) {
            if (strpos($url, 'api.openai.com') !== false) {
                return [
                    'body' => json_encode([
                        'choices' => [
                            ['message' => ['content' => 'Test response']]
                        ]
                    ]),
                    'response' => ['code' => 200]
                ];
            }
            return $preempt;
        }, 10, 3);
        
        $result = Hexagon_Hexagon_Ai_Manager::generate_content('chatgpt', 'article', 'test prompt');
        $this->assertArrayHasKey('content', $result);
    }
}
```

## 🔍 Debugging and Logging

### Custom Logging
Use the built-in logging system:

```php
// Different log levels
hexagon_log('Debug Info', 'Detailed debug information', 'info');
hexagon_log('Warning', 'Something might be wrong', 'warning');
hexagon_log('Error', 'Something failed', 'error');
hexagon_log('Success', 'Operation completed', 'success');

// With context data
hexagon_log('API Call', 'Called external API', 'info', [
    'url' => $api_url,
    'response_time' => $response_time,
    'status_code' => $status_code
]);
```

### Debug Mode
Enable debug mode for development:

```php
// In wp-config.php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
define('HEXAGON_DEBUG', true);

// Check debug mode in code
if (defined('HEXAGON_DEBUG') && HEXAGON_DEBUG) {
    error_log('Debug information: ' . print_r($data, true));
}
```

## 🔐 Security Best Practices

### Input Validation
Always validate and sanitize input:

```php
public static function handle_request() {
    // Verify nonce
    check_ajax_referer('hexagon_action_nonce', 'nonce');
    
    // Check capabilities
    if (!current_user_can('manage_options')) {
        wp_send_json_error(['message' => 'Insufficient permissions']);
    }
    
    // Sanitize input
    $user_input = sanitize_text_field($_POST['user_input']);
    $email = sanitize_email($_POST['email']);
    $url = esc_url_raw($_POST['url']);
    
    // Validate input
    if (!is_email($email)) {
        wp_send_json_error(['message' => 'Invalid email address']);
    }
}
```

### SQL Queries
Use WordPress prepared statements:

```php
global $wpdb;

// Correct way
$results = $wpdb->get_results($wpdb->prepare(
    "SELECT * FROM {$wpdb->prefix}hex_logs WHERE level = %s AND created_at > %s",
    $level,
    $date
));

// Wrong way - vulnerable to SQL injection
$results = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}hex_logs WHERE level = '$level'");
```

### API Key Storage
Store sensitive data securely:

```php
// Store encrypted
update_option('hexagon_ai_api_key', wp_hash($api_key));

// Retrieve and validate
$stored_key = get_option('hexagon_ai_api_key');
if (wp_hash($provided_key) === $stored_key) {
    // Key is valid
}
```

## 📦 Distribution and Updates

### Plugin Packaging
Create distribution package:

```bash
#!/bin/bash
# build-plugin.sh

# Clean previous builds
rm -rf build/
mkdir build/

# Copy plugin files
cp -r includes/ build/
cp -r admin/ build/
cp -r dashboard/dist/ build/dashboard/
cp *.php *.md *.txt build/

# Remove development files
find build/ -name "*.dev.js" -delete
find build/ -name "node_modules" -type d -exec rm -rf {} +
find build/ -name ".git*" -delete

# Create ZIP
cd build/
zip -r ../hexagon-automation-v3.0.0.zip .
cd ..

echo "Plugin package created: hexagon-automation-v3.0.0.zip"
```

### Version Management
Update version across files:

```php
// Version update script
function update_plugin_version($new_version) {
    $files = [
        'hexagon-automation.php' => '/Version:\s+(.+)/',
        'includes/functions.php' => '/HEXAGON_VERSION.*?[\'"](.+?)[\'"]/',
        'package.json' => '/"version":\s*"(.+?)"/'
    ];
    
    foreach ($files as $file => $pattern) {
        $content = file_get_contents($file);
        $content = preg_replace($pattern, 
            str_replace('(.+)', $new_version, $pattern), 
            $content
        );
        file_put_contents($file, $content);
    }
}
```

## 🔧 Performance Optimization

### Caching Strategy
Implement intelligent caching:

```php
function get_cached_ai_response($prompt, $provider) {
    $cache_key = 'hexagon_ai_' . md5($prompt . $provider);
    
    // Try to get from cache
    $cached = wp_cache_get($cache_key, 'hexagon_ai');
    if ($cached !== false) {
        return $cached;
    }
    
    // Generate new response
    $response = generate_ai_content($prompt, $provider);
    
    // Cache for 1 hour
    wp_cache_set($cache_key, $response, 'hexagon_ai', HOUR_IN_SECONDS);
    
    return $response;
}
```

### Database Optimization
Optimize queries and indexes:

```sql
-- Add indexes for better performance
ALTER TABLE wp_hex_logs ADD INDEX idx_level_created (level, created_at);
ALTER TABLE wp_hex_logs ADD INDEX idx_action (action);

-- Cleanup old data
DELETE FROM wp_hex_logs WHERE created_at < DATE_SUB(NOW(), INTERVAL 30 DAY);
```

### Memory Management
Monitor and optimize memory usage:

```php
function monitor_memory_usage($checkpoint = '') {
    $current = memory_get_usage(true);
    $peak = memory_get_peak_usage(true);
    
    hexagon_log('Memory Usage', 
        "Checkpoint: $checkpoint, Current: " . size_format($current) . 
        ", Peak: " . size_format($peak), 
        'info'
    );
    
    // Alert if memory usage is high
    $limit = wp_convert_hr_to_bytes(ini_get('memory_limit'));
    if ($current > ($limit * 0.8)) {
        hexagon_log('Memory Warning', 
            'Memory usage above 80% of limit', 
            'warning'
        );
    }
}
```

## 📞 Support and Contributing

### Issue Reporting
Template for bug reports:

```markdown
## Bug Report

**Plugin Version:** 3.0.0
**WordPress Version:** 6.4.2
**PHP Version:** 8.0.30

**Description:**
Brief description of the issue

**Steps to Reproduce:**
1. Go to...
2. Click on...
3. See error...

**Expected Behavior:**
What should happen

**Actual Behavior:**
What actually happens

**System Test Results:**
```
Paste results from Hexagon Automation → System Tests
```

**Error Logs:**
```
Paste relevant error logs
```
```

### Development Setup
Set up development environment:

```bash
# Clone repository
git clone https://github.com/hexagon-technology/hexagon-automation.git
cd hexagon-automation

# Install dependencies
cd dashboard
npm install

# Build dashboard
npm run build

# Run tests
cd ../tests
phpunit

# Start development server
cd ../
wp server --port=8080
```

---

**🚀 Ready to Extend Hexagon Automation?**

This guide provides the foundation for extending the plugin. The modular architecture makes it easy to add new features while maintaining code quality and security standards.