<?php
if (!defined('ABSPATH')) exit;
require_once HEXAGON_PATH . 'includes/functions.php';
class Hexagon_Automation {
    private static $instance;
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    private function __construct() {
        // Load modules
        $this->init_modules();
    }
    private function init_modules() {
        // Example module loading
        if (class_exists('Hexagon_Email_Integration')) {
            Hexagon_Email_Integration::init();
        }
    }
}
add_action('plugins_loaded', ['Hexagon_Automation','get_instance']);
