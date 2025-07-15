<?php
if (!defined('ABSPATH')) exit;
class Hexagon_Loader {
    public static function init() {
        // Load core classes
        require_once HEXAGON_PATH . 'includes/class-hexagon-automation.php';
        
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
        
        // Load AI providers
        require_once HEXAGON_PATH . 'includes/providers/class-ai-chatgpt.php';
        require_once HEXAGON_PATH . 'includes/providers/class-ai-claude.php';
        require_once HEXAGON_PATH . 'includes/providers/class-ai-perplexity.php';
    }
}
