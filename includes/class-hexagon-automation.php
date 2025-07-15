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
        // Load all modules
        if (class_exists('Hexagon_Hexagon_Ai_Manager')) {
            Hexagon_Hexagon_Ai_Manager::init();
        }
        
        if (class_exists('Hexagon_Email_Integration')) {
            Hexagon_Email_Integration::init();
        }
        
        if (class_exists('Hexagon_Social_Integration')) {
            Hexagon_Social_Integration::init();
        }
        
        if (class_exists('Hexagon_Content_Manager')) {
            Hexagon_Content_Manager::init();
        }
        
        if (class_exists('Hexagon_Image_Generator')) {
            Hexagon_Image_Generator::init();
        }
        
        if (class_exists('Hexagon_Setup_Wizard')) {
            Hexagon_Setup_Wizard::init();
        }
        
        if (class_exists('Hexagon_Wordpress_Integration')) {
            Hexagon_Wordpress_Integration::init();
        }
        
        if (class_exists('Hexagon_Usercom_Integration')) {
            Hexagon_Usercom_Integration::init();
        }
        
        if (class_exists('Hexagon_Social_Scheduler')) {
            Hexagon_Social_Scheduler::init();
        }
        
        if (class_exists('Hexagon_Rest_Api')) {
            Hexagon_Rest_Api::init();
        }
        
        if (class_exists('Hexagon_Auto_Repair')) {
            Hexagon_Auto_Repair::init();
        }
        
        if (class_exists('Hexagon_System_Tester')) {
            Hexagon_System_Tester::init();
        }
        
        if (class_exists('Hexagon_Debug_Exporter')) {
            Hexagon_Debug_Exporter::init();
        }
        
        // Initialize AI providers
        if (class_exists('Hexagon_Ai_Chatgpt')) {
            Hexagon_Ai_Chatgpt::init();
        }
        
        if (class_exists('Hexagon_Ai_Claude')) {
            Hexagon_Ai_Claude::init();
        }
        
        if (class_exists('Hexagon_Ai_Perplexity')) {
            Hexagon_Ai_Perplexity::init();
        }
    }
}
add_action('plugins_loaded', ['Hexagon_Automation','get_instance']);
