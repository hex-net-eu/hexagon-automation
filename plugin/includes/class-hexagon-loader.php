<?php
if (!defined('ABSPATH')) exit;
class Hexagon_Loader {
    public static function init() {
        require_once HEXAGON_PATH . 'includes/class-hexagon-automation.php';
    }
}
