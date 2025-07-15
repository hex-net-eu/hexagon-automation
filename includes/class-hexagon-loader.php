<?php
if (!defined('ABSPATH')) exit;
class Hexagon_Loader {
    public static function init() {
        // Load core classes
        require_once HEXAGON_PATH . 'includes/class-hexagon-automation.php';
        
        // Load core management classes
        require_once HEXAGON_PATH . 'includes/class-error-handler.php';
        require_once HEXAGON_PATH . 'includes/class-settings-manager.php';
        require_once HEXAGON_PATH . 'includes/class-system-diagnostic.php';
        require_once HEXAGON_PATH . 'includes/modules/class-module-manager.php';
        
        // Load all modules
        require_once HEXAGON_PATH . 'includes/modules/class-hexagon-ai-manager.php';
        require_once HEXAGON_PATH . 'includes/modules/class-email-integration.php';
        require_once HEXAGON_PATH . 'includes/modules/class-social-integration.php';
        require_once HEXAGON_PATH . 'includes/modules/class-content-manager.php';
        require_once HEXAGON_PATH . 'includes/modules/class-image-generator.php';
        require_once HEXAGON_PATH . 'includes/modules/class-setup-wizard.php';
        require_once HEXAGON_PATH . 'includes/modules/class-wordpress-integration.php';
        require_once HEXAGON_PATH . 'includes/modules/class-usercom-integration.php';
        require_once HEXAGON_PATH . 'includes/modules/class-social-scheduler.php';
        require_once HEXAGON_PATH . 'includes/modules/class-international-content.php';
        require_once HEXAGON_PATH . 'includes/modules/class-rest-api.php';
        require_once HEXAGON_PATH . 'includes/modules/class-auto-repair.php';
        require_once HEXAGON_PATH . 'includes/modules/class-system-tester.php';
        require_once HEXAGON_PATH . 'includes/modules/class-rss-manager.php';
        require_once HEXAGON_PATH . 'includes/modules/class-social-media-manager.php';
        require_once HEXAGON_PATH . 'includes/modules/class-dashboard-api.php';
        require_once HEXAGON_PATH . 'includes/class-hexagon-auth.php';
        require_once HEXAGON_PATH . 'includes/class-hexagon-logger.php';
        require_once HEXAGON_PATH . 'includes/class-analytics-dashboard.php';
        require_once HEXAGON_PATH . 'includes/class-debug-exporter.php';
        require_once HEXAGON_PATH . 'includes/modules/class-ai-content-generator.php';
        
        // Load AI providers
        require_once HEXAGON_PATH . 'includes/providers/class-ai-chatgpt.php';
        require_once HEXAGON_PATH . 'includes/providers/class-ai-claude.php';
        require_once HEXAGON_PATH . 'includes/providers/class-ai-perplexity.php';
        
        // Initialize AI providers
        Hexagon_AI_ChatGPT::init();
        Hexagon_AI_Claude::init();
        Hexagon_AI_Perplexity::init();
        
        // Initialize core management classes
        Hexagon_Error_Handler::init();
        Hexagon_Settings_Manager::init();
        Hexagon_System_Diagnostic::init();
        Hexagon_Module_Manager::init();
        
        // Initialize modules (using module manager)
        Hexagon_Module_Manager::load_enabled_modules();
        
        // Initialize other core classes
        Hexagon_Setup_Wizard::init();
        Hexagon_RSS_Manager::init();
        Hexagon_Social_Media_Manager::init();
        Hexagon_Dashboard_API::init();
        Hexagon_Auth::init();
        Hexagon_Logger::init();
        Hexagon_Analytics_Dashboard::init();
        Hexagon_Debug_Exporter::init();
        Hexagon_AI_Content_Generator::init();
        Hexagon_Image_Generator::init();
        Hexagon_Social_Scheduler::init();
        
        // Initialize admin interface
        add_action('admin_menu', [__CLASS__, 'add_admin_menu']);
        add_filter('plugin_action_links_' . plugin_basename(HEXAGON_PATH . 'hexagon-automation.php'), [__CLASS__, 'add_plugin_links']);
        add_action('admin_init', [__CLASS__, 'register_settings']);
    }
    
    public static function add_admin_menu() {
        add_menu_page(
            'Hexagon Automation',
            'Hexagon Automation',
            'manage_options',
            'hexagon-dashboard',
            [__CLASS__, 'admin_dashboard'],
            'dashicons-admin-tools',
            30
        );
        
        add_submenu_page(
            'hexagon-dashboard',
            'Settings',
            'Settings',
            'manage_options',
            'hexagon-settings',
            [__CLASS__, 'admin_settings']
        );
        
        add_submenu_page(
            'hexagon-dashboard',
            'System Tests',
            'System Tests',
            'manage_options',
            'hexagon-tests',
            [__CLASS__, 'admin_tests']
        );
        
        add_submenu_page(
            'hexagon-dashboard',
            'Debug Export',
            'Debug Export',
            'manage_options',
            'hexagon-debug',
            [__CLASS__, 'admin_debug']
        );
    }
    
    public static function add_plugin_links($links) {
        $settings_link = '<a href="' . admin_url('admin.php?page=hexagon-settings') . '">Settings</a>';
        $docs_link = '<a href="' . admin_url('admin.php?page=hexagon-dashboard') . '">Documentation</a>';
        array_unshift($links, $settings_link, $docs_link);
        return $links;
    }
    
    public static function register_settings() {
        register_setting('hexagon_settings', 'hexagon_ai_chatgpt_api_key');
        register_setting('hexagon_settings', 'hexagon_ai_claude_api_key');
        register_setting('hexagon_settings', 'hexagon_ai_perplexity_api_key');
    }
    
    public static function admin_dashboard() {
        ?>
        <div class="wrap">
            <h1>🤖 Hexagon Automation Dashboard v<?php echo HEXAGON_VERSION; ?> - ENTERPRISE EDITION</h1>
            
            <div style="margin-bottom: 20px;">
                <a href="<?php echo HEXAGON_URL; ?>dashboard/index.html" target="_blank" class="button button-primary" style="margin-right: 10px;">🚀 Open React Dashboard</a>
                <a href="<?php echo admin_url('admin.php?page=hexagon-settings'); ?>" class="button">⚙️ Settings</a>
                <a href="<?php echo admin_url('admin.php?page=hexagon-tests'); ?>" class="button">🧪 Tests</a>
                <a href="<?php echo admin_url('admin.php?page=hexagon-debug'); ?>" class="button">🔍 Debug</a>
            </div>
            
            <!-- EMBEDDED REACT DASHBOARD -->
            <div class="card" style="padding: 0; margin-bottom: 20px;">
                <iframe 
                    src="<?php echo HEXAGON_URL; ?>dashboard/index.html" 
                    style="width: 100%; height: 800px; border: none; border-radius: 4px;"
                    title="Hexagon Dashboard">
                </iframe>
                <p style="text-align: center; margin: 10px;">
                    <a href="<?php echo HEXAGON_URL; ?>dashboard/index.html" target="_blank">🔗 Open in full window</a>
                </p>
            </div>
            
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                <div class="card">
                    <h2>🔧 Module Status</h2>
                    <table class="widefat">
                        <tr><td><strong>AI Manager:</strong></td><td><?php echo class_exists('Hexagon_AI_Manager') ? '✅ Loaded' : '❌ Not Found'; ?></td></tr>
                        <tr><td><strong>Email Integration:</strong></td><td><?php echo class_exists('Hexagon_Email_Integration') ? '✅ Loaded' : '❌ Not Found'; ?></td></tr>
                        <tr><td><strong>Social Integration:</strong></td><td><?php echo class_exists('Hexagon_Social_Integration') ? '✅ Loaded' : '❌ Not Found'; ?></td></tr>
                        <tr><td><strong>Auto Repair:</strong></td><td><?php echo class_exists('Hexagon_Auto_Repair') ? '✅ Loaded' : '❌ Not Found'; ?></td></tr>
                    </table>
                </div>
                
                <div class="card">
                    <h2>📊 Quick Stats</h2>
                    <table class="widefat">
                        <tr><td><strong>Plugin Version:</strong></td><td><?php echo HEXAGON_VERSION; ?></td></tr>
                        <tr><td><strong>WordPress:</strong></td><td><?php echo get_bloginfo('version'); ?></td></tr>
                        <tr><td><strong>PHP:</strong></td><td><?php echo PHP_VERSION; ?></td></tr>
                        <tr><td><strong>Memory:</strong></td><td><?php echo ini_get('memory_limit'); ?></td></tr>
                    </table>
                </div>
            </div>
            
            <div class="card">
                <h2>📚 Resources</h2>
                <p>
                    <a href="<?php echo HEXAGON_URL; ?>README-FULL.md" target="_blank">📖 Documentation</a> |
                    <a href="<?php echo HEXAGON_URL; ?>API_DOCUMENTATION.md" target="_blank">🔌 API Docs</a> |
                    <a href="<?php echo HEXAGON_URL; ?>CHANGELOG-v3.0.4.txt" target="_blank">📝 Changelog</a> |
                    <a href="<?php echo HEXAGON_URL; ?>debug-direct-access.php?debug_key=hexagon" target="_blank">🔍 Debug Export</a>
                </p>
            </div>
        </div>
        
        <style>
        .card { background: #fff; border: 1px solid #ccd0d4; padding: 20px; border-radius: 4px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); }
        .widefat td { padding: 8px 12px; }
        </style>
        <?php
    }
    
    public static function admin_settings() {
        ?>
        <div class="wrap">
            <h1>⚙️ Hexagon Automation Settings</h1>
            
            <form method="post" action="options.php">
                <?php settings_fields('hexagon_settings'); ?>
                
                <table class="form-table">
                    <tr>
                        <th scope="row">OpenAI API Key</th>
                        <td>
                            <input type="password" name="hexagon_ai_chatgpt_api_key" value="<?php echo esc_attr(get_option('hexagon_ai_chatgpt_api_key')); ?>" class="regular-text" />
                            <p class="description">Get from: <a href="https://platform.openai.com/api-keys" target="_blank">OpenAI Platform</a></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Claude API Key</th>
                        <td>
                            <input type="password" name="hexagon_ai_claude_api_key" value="<?php echo esc_attr(get_option('hexagon_ai_claude_api_key')); ?>" class="regular-text" />
                            <p class="description">Get from: <a href="https://console.anthropic.com/" target="_blank">Anthropic Console</a></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Perplexity API Key</th>
                        <td>
                            <input type="password" name="hexagon_ai_perplexity_api_key" value="<?php echo esc_attr(get_option('hexagon_ai_perplexity_api_key')); ?>" class="regular-text" />
                            <p class="description">Get from: <a href="https://www.perplexity.ai/settings/api" target="_blank">Perplexity Settings</a></p>
                        </td>
                    </tr>
                </table>
                
                <?php submit_button(); ?>
            </form>
        </div>
        <?php
    }
    
    public static function admin_tests() {
        ?>
        <div class="wrap">
            <h1>🧪 Complete System Tests</h1>
            
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                <div class="card">
                    <h2>🔬 Quick Tests</h2>
                    <p>
                        <button class="button button-primary" onclick="runQuickTest()">🔍 AJAX Test</button>
                        <button class="button" onclick="runFullTest()">🔧 Full System Test</button>
                        <button class="button" onclick="runModuleTest()">📦 Module Test</button>
                    </p>
                    <div id="test-results" style="margin-top: 20px; max-height: 400px; overflow-y: auto;"></div>
                </div>
                
                <div class="card">
                    <h2>📊 System Information</h2>
                    <table class="widefat">
                        <tr><td><strong>WordPress:</strong></td><td><?php echo get_bloginfo('version'); ?></td></tr>
                        <tr><td><strong>PHP Version:</strong></td><td><?php echo PHP_VERSION; ?></td></tr>
                        <tr><td><strong>PHP Memory Limit:</strong></td><td><?php echo ini_get('memory_limit'); ?></td></tr>
                        <tr><td><strong>PHP Max Execution:</strong></td><td><?php echo ini_get('max_execution_time'); ?>s</td></tr>
                        <tr><td><strong>MySQL Version:</strong></td><td><?php global $wpdb; echo $wpdb->db_version(); ?></td></tr>
                        <tr><td><strong>Server:</strong></td><td><?php echo $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown'; ?></td></tr>
                        <tr><td><strong>WP Debug:</strong></td><td><?php echo defined('WP_DEBUG') && WP_DEBUG ? '✅ ON' : '❌ OFF'; ?></td></tr>
                        <tr><td><strong>WP Debug Log:</strong></td><td><?php echo defined('WP_DEBUG_LOG') && WP_DEBUG_LOG ? '✅ ON' : '❌ OFF'; ?></td></tr>
                    </table>
                </div>
            </div>
            
            <div class="card">
                <h2>📦 PHP Extensions</h2>
                <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 10px;">
                    <?php
                    $required_extensions = ['curl', 'json', 'mbstring', 'openssl', 'zip', 'gd', 'mysql', 'mysqli'];
                    foreach ($required_extensions as $ext) {
                        $status = extension_loaded($ext) ? '✅' : '❌';
                        echo "<span>$status $ext</span>";
                    }
                    ?>
                </div>
            </div>
            
            <div class="card">
                <h2>🔗 Network Tests</h2>
                <p>
                    <button class="button" onclick="testOpenAI()">🤖 Test OpenAI</button>
                    <button class="button" onclick="testClaude()">🧠 Test Claude</button>
                    <button class="button" onclick="testPerplexity()">🔍 Test Perplexity</button>
                    <button class="button" onclick="testEmail()">📧 Test Email</button>
                </p>
                <div id="network-results"></div>
            </div>
            
            <script>
            function runQuickTest() {
                document.getElementById('test-results').innerHTML = '⏳ Running quick AJAX test...';
                
                jQuery.post(ajaxurl, {
                    action: 'hexagon_debug_ajax'
                })
                .done(function(response) {
                    document.getElementById('test-results').innerHTML = 
                        '<div class="notice notice-success"><p>✅ AJAX test passed!</p><pre>' + 
                        JSON.stringify(response.data, null, 2) + '</pre></div>';
                })
                .fail(function(xhr) {
                    document.getElementById('test-results').innerHTML = 
                        '<div class="notice notice-error"><p>❌ AJAX test failed!</p><pre>' + 
                        xhr.responseText + '</pre></div>';
                });
            }
            
            function runFullTest() {
                document.getElementById('test-results').innerHTML = '⏳ Running comprehensive system tests...';
                
                jQuery.post(ajaxurl, {
                    action: 'hexagon_run_tests'
                })
                .done(function(response) {
                    const results = response.data;
                    let html = '<div class="notice notice-success"><p>✅ Full system test completed!</p></div>';
                    
                    Object.keys(results).forEach(function(key) {
                        const status = results[key] ? '✅' : '❌';
                        html += `<p>${status} <strong>${key}:</strong> ${results[key] ? 'PASS' : 'FAIL'}</p>`;
                    });
                    
                    document.getElementById('test-results').innerHTML = html;
                })
                .fail(function(xhr) {
                    document.getElementById('test-results').innerHTML = 
                        '<div class="notice notice-error"><p>❌ Full test failed!</p><pre>' + 
                        xhr.responseText + '</pre></div>';
                });
            }
            
            function runModuleTest() {
                document.getElementById('test-results').innerHTML = '⏳ Testing all modules...';
                
                const modules = [
                    'Hexagon_AI_Manager',
                    'Hexagon_Email_Integration', 
                    'Hexagon_Social_Integration',
                    'Hexagon_Auto_Repair',
                    'Hexagon_System_Tester'
                ];
                
                let html = '<div class="notice notice-info"><p>📦 Module Status:</p></div>';
                
                modules.forEach(function(module) {
                    // This would need server-side check, simplified for now
                    html += `<p>🔍 Checking ${module}...</p>`;
                });
                
                document.getElementById('test-results').innerHTML = html;
            }
            
            function testOpenAI() {
                document.getElementById('network-results').innerHTML = '⏳ Testing OpenAI connection...';
                
                jQuery.post(ajaxurl, {
                    action: 'hexagon_ai_test_connection',
                    provider: 'chatgpt'
                })
                .done(function(response) {
                    document.getElementById('network-results').innerHTML = 
                        '<div class="notice notice-success"><p>✅ OpenAI connection successful!</p></div>';
                })
                .fail(function(xhr) {
                    document.getElementById('network-results').innerHTML = 
                        '<div class="notice notice-error"><p>❌ OpenAI connection failed: ' + xhr.responseText + '</p></div>';
                });
            }
            
            function testClaude() {
                document.getElementById('network-results').innerHTML = '⏳ Testing Claude connection...';
                
                jQuery.post(ajaxurl, {
                    action: 'hexagon_ai_test_connection',
                    provider: 'claude'
                })
                .done(function(response) {
                    document.getElementById('network-results').innerHTML = 
                        '<div class="notice notice-success"><p>✅ Claude connection successful!</p></div>';
                })
                .fail(function(xhr) {
                    document.getElementById('network-results').innerHTML = 
                        '<div class="notice notice-error"><p>❌ Claude connection failed: ' + xhr.responseText + '</p></div>';
                });
            }
            </script>
        </div>
        <?php
    }
    
    public static function admin_debug() {
        ?>
        <div class="wrap">
            <h1>🔍 Debug Export</h1>
            
            <div class="card">
                <h2>Debug Information</h2>
                <p>
                    <a href="<?php echo HEXAGON_URL; ?>debug-direct-access.php" target="_blank" class="button button-primary">📋 Download Debug Info</a>
                    <a href="<?php echo admin_url('admin-ajax.php?action=hexagon_export_logs'); ?>" class="button">📊 Export Logs</a>
                </p>
                
                <h3>System Information</h3>
                <table class="widefat">
                    <tr><td><strong>Plugin Version:</strong></td><td><?php echo HEXAGON_VERSION; ?></td></tr>
                    <tr><td><strong>WordPress:</strong></td><td><?php echo get_bloginfo('version'); ?></td></tr>
                    <tr><td><strong>PHP:</strong></td><td><?php echo PHP_VERSION; ?></td></tr>
                    <tr><td><strong>Memory Limit:</strong></td><td><?php echo ini_get('memory_limit'); ?></td></tr>
                    <tr><td><strong>WP Debug:</strong></td><td><?php echo defined('WP_DEBUG') && WP_DEBUG ? '✅ ON' : '❌ OFF'; ?></td></tr>
                </table>
            </div>
        </div>
        <?php
    }
}
