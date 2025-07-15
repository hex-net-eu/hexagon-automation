<?php
if (!defined('ABSPATH')) exit;

class Hexagon_Email_Integration {
    
    private static $smtp_config = null;
    private static $error_count = 0;
    private static $max_retries = 3;
    
    public static function init() {
        add_action('wp_ajax_hexagon_test_email', [__CLASS__, 'test_email_connection']);
        add_action('wp_ajax_hexagon_send_email', [__CLASS__, 'send_email']);
        add_action('hexagon_email_error', [__CLASS__, 'handle_email_error'], 10, 2);
        add_action('hexagon_ai_cleanup', [__CLASS__, 'send_daily_digest']);
        add_action('init', [__CLASS__, 'setup_email_hooks']);
        
        // Auto-repair hooks
        add_action('wp_mail_failed', [__CLASS__, 'auto_repair_email']);
        add_filter('wp_mail_smtp', [__CLASS__, 'auto_configure_smtp']);
    }
    
    public static function setup_email_hooks() {
        // Override wp_mail if custom SMTP is configured
        if (hexagon_get_option('hexagon_email_use_smtp', false)) {
            add_action('phpmailer_init', [__CLASS__, 'configure_smtp']);
        }
        
        // Email logging
        add_action('wp_mail_succeeded', [__CLASS__, 'log_email_success']);
        add_action('wp_mail_failed', [__CLASS__, 'log_email_failure']);
    }
    
    public static function send_email($to, $subject, $message, $headers = [], $attachments = []) {
        $attempt = 0;
        $max_attempts = self::$max_retries;
        
        while ($attempt < $max_attempts) {
            try {
                $result = self::attempt_send_email($to, $subject, $message, $headers, $attachments);
                
                if ($result) {
                    hexagon_log('Email Sent Successfully', "To: $to, Subject: $subject", 'success');
                    self::reset_error_count();
                    return true;
                }
                
                $attempt++;
                if ($attempt < $max_attempts) {
                    hexagon_log('Email Send Failed - Retrying', "Attempt $attempt of $max_attempts", 'warning');
                    sleep(2); // Wait before retry
                    self::auto_repair_email_config();
                }
                
            } catch (Exception $e) {
                hexagon_log('Email Send Exception', $e->getMessage(), 'error');
                $attempt++;
                
                if ($attempt < $max_attempts) {
                    self::auto_repair_email_config();
                    sleep(2);
                }
            }
        }
        
        hexagon_log('Email Send Failed After All Retries', "To: $to, Subject: $subject", 'error');
        self::increment_error_count();
        return false;
    }
    
    private static function attempt_send_email($to, $subject, $message, $headers, $attachments) {
        // Sanitize inputs
        $to = sanitize_email($to);
        $subject = sanitize_text_field($subject);
        
        if (!is_email($to)) {
            throw new Exception('Invalid email address');
        }
        
        // Add default headers
        if (empty($headers)) {
            $headers = [
                'Content-Type: text/html; charset=UTF-8',
                'From: ' . get_option('admin_email')
            ];
        }
        
        return wp_mail($to, $subject, $message, $headers, $attachments);
    }
    
    public static function configure_smtp($phpmailer) {
        $smtp_enabled = hexagon_get_option('hexagon_email_use_smtp', false);
        
        if (!$smtp_enabled) {
            return;
        }
        
        $phpmailer->isSMTP();
        $phpmailer->Host = hexagon_get_option('hexagon_email_smtp_host', 'smtp.gmail.com');
        $phpmailer->SMTPAuth = true;
        $phpmailer->Port = intval(hexagon_get_option('hexagon_email_smtp_port', 587));
        $phpmailer->Username = hexagon_get_option('hexagon_email_smtp_username', '');
        $phpmailer->Password = hexagon_get_option('hexagon_email_smtp_password', '');
        
        $encryption = hexagon_get_option('hexagon_email_smtp_encryption', 'tls');
        if ($encryption === 'ssl') {
            $phpmailer->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        } else {
            $phpmailer->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        }
        
        // Enable debugging in development
        if (defined('WP_DEBUG') && WP_DEBUG) {
            $phpmailer->SMTPDebug = 2;
        }
    }
    
    public static function auto_repair_email_config() {
        hexagon_log('Auto-Repair Email', 'Attempting to repair email configuration', 'info');
        
        // Check if SMTP is configured
        $smtp_enabled = hexagon_get_option('hexagon_email_use_smtp', false);
        
        if (!$smtp_enabled) {
            // Try to auto-configure common SMTP settings
            $admin_email = get_option('admin_email');
            $domain = wp_parse_url(home_url(), PHP_URL_HOST);
            
            if (strpos($admin_email, '@gmail.com') !== false) {
                self::auto_configure_gmail();
            } elseif (strpos($admin_email, '@outlook.com') !== false || strpos($admin_email, '@hotmail.com') !== false) {
                self::auto_configure_outlook();
            } else {
                self::auto_configure_generic($domain);
            }
        } else {
            // Test current SMTP settings and fix common issues
            self::test_and_fix_smtp();
        }
    }
    
    private static function auto_configure_gmail() {
        update_option('hexagon_email_use_smtp', true);
        update_option('hexagon_email_smtp_host', 'smtp.gmail.com');
        update_option('hexagon_email_smtp_port', 587);
        update_option('hexagon_email_smtp_encryption', 'tls');
        
        hexagon_log('Auto-Repair Email', 'Configured Gmail SMTP settings', 'info');
    }
    
    private static function auto_configure_outlook() {
        update_option('hexagon_email_use_smtp', true);
        update_option('hexagon_email_smtp_host', 'smtp.office365.com');
        update_option('hexagon_email_smtp_port', 587);
        update_option('hexagon_email_smtp_encryption', 'tls');
        
        hexagon_log('Auto-Repair Email', 'Configured Outlook SMTP settings', 'info');
    }
    
    private static function auto_configure_generic($domain) {
        update_option('hexagon_email_use_smtp', true);
        update_option('hexagon_email_smtp_host', "mail.$domain");
        update_option('hexagon_email_smtp_port', 587);
        update_option('hexagon_email_smtp_encryption', 'tls');
        
        hexagon_log('Auto-Repair Email', "Configured generic SMTP settings for $domain", 'info');
    }
    
    private static function test_and_fix_smtp() {
        $host = hexagon_get_option('hexagon_email_smtp_host');
        $port = hexagon_get_option('hexagon_email_smtp_port', 587);
        
        // Test connection
        $connection = @fsockopen($host, $port, $errno, $errstr, 10);
        
        if (!$connection) {
            hexagon_log('SMTP Connection Failed', "Host: $host, Port: $port, Error: $errstr", 'error');
            
            // Try alternative ports
            $alternative_ports = [587, 465, 25, 2525];
            foreach ($alternative_ports as $alt_port) {
                if ($alt_port != $port) {
                    $test_connection = @fsockopen($host, $alt_port, $errno, $errstr, 5);
                    if ($test_connection) {
                        update_option('hexagon_email_smtp_port', $alt_port);
                        fclose($test_connection);
                        hexagon_log('SMTP Port Auto-Fixed', "Changed to port $alt_port", 'success');
                        break;
                    }
                }
            }
        } else {
            fclose($connection);
        }
    }
    
    public static function test_email_connection() {
        check_ajax_referer('hexagon_email_nonce', 'nonce');
        
        $test_email = sanitize_email($_POST['test_email'] ?? get_option('admin_email'));
        
        $subject = 'Hexagon Automation - Test Email';
        $message = '<h2>Test Email Successful</h2>';
        $message .= '<p>This is a test email from Hexagon Automation plugin.</p>';
        $message .= '<p>Time: ' . current_time('mysql') . '</p>';
        $message .= '<p>If you received this email, your email configuration is working correctly.</p>';
        
        $result = self::send_email($test_email, $subject, $message);
        
        if ($result) {
            wp_send_json_success(['message' => 'Test email sent successfully']);
        } else {
            wp_send_json_error(['message' => 'Failed to send test email']);
        }
    }
    
    public static function send_daily_digest() {
        $email_enabled = hexagon_get_option('hexagon_email_daily_digest', false);
        if (!$email_enabled) {
            return;
        }
        
        $admin_email = get_option('admin_email');
        $logs = self::get_recent_logs();
        $ai_stats = Hexagon_Hexagon_Ai_Manager::get_usage_stats();
        
        $subject = 'Hexagon Automation - Daily Digest';
        $message = self::build_digest_email($logs, $ai_stats);
        
        self::send_email($admin_email, $subject, $message);
    }
    
    private static function get_recent_logs() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'hex_logs';
        $yesterday = date('Y-m-d H:i:s', strtotime('-24 hours'));
        
        return $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $table_name WHERE created_at >= %s ORDER BY created_at DESC LIMIT 50",
            $yesterday
        ));
    }
    
    private static function build_digest_email($logs, $ai_stats) {
        $error_count = 0;
        $success_count = 0;
        $warning_count = 0;
        
        foreach ($logs as $log) {
            switch ($log->level) {
                case 'error':
                    $error_count++;
                    break;
                case 'success':
                    $success_count++;
                    break;
                case 'warning':
                    $warning_count++;
                    break;
            }
        }
        
        $message = '<html><body>';
        $message .= '<h2>Hexagon Automation - Daily Summary</h2>';
        $message .= '<h3>System Status</h3>';
        $message .= '<ul>';
        $message .= "<li>✅ Successful operations: $success_count</li>";
        $message .= "<li>⚠️ Warnings: $warning_count</li>";
        $message .= "<li>❌ Errors: $error_count</li>";
        $message .= '</ul>';
        
        if (!empty($ai_stats)) {
            $message .= '<h3>AI Usage Statistics</h3>';
            $message .= '<table border="1" cellpadding="5">';
            $message .= '<tr><th>Provider</th><th>Requests</th><th>Tokens</th></tr>';
            foreach ($ai_stats as $provider => $stats) {
                $message .= "<tr><td>$provider</td><td>{$stats['requests']}</td><td>{$stats['tokens']}</td></tr>";
            }
            $message .= '</table>';
        }
        
        if ($error_count > 0) {
            $message .= '<h3>Recent Errors</h3>';
            $message .= '<ul>';
            foreach ($logs as $log) {
                if ($log->level === 'error') {
                    $message .= "<li><strong>{$log->action}</strong>: {$log->context} ({$log->created_at})</li>";
                }
            }
            $message .= '</ul>';
        }
        
        $message .= '<p><small>Generated by Hexagon Automation Plugin</small></p>';
        $message .= '</body></html>';
        
        return $message;
    }
    
    public static function log_email_success($mail_data) {
        hexagon_log('Email Sent', 'Email delivered successfully', 'success');
    }
    
    public static function log_email_failure($wp_error) {
        $error_message = $wp_error->get_error_message();
        hexagon_log('Email Failed', $error_message, 'error');
        
        // Trigger auto-repair after multiple failures
        self::increment_error_count();
        if (self::$error_count >= 3) {
            self::auto_repair_email_config();
            self::reset_error_count();
        }
    }
    
    private static function increment_error_count() {
        self::$error_count++;
        update_option('hexagon_email_error_count', self::$error_count);
    }
    
    private static function reset_error_count() {
        self::$error_count = 0;
        update_option('hexagon_email_error_count', 0);
    }
    
    public static function send_error_alert($error_message, $context = '') {
        $alert_enabled = hexagon_get_option('hexagon_email_error_alerts', true);
        if (!$alert_enabled) {
            return;
        }
        
        $admin_email = get_option('admin_email');
        $subject = 'Hexagon Automation - Error Alert';
        
        $message = '<html><body>';
        $message .= '<h2>🚨 Error Alert</h2>';
        $message .= "<p><strong>Error:</strong> $error_message</p>";
        if ($context) {
            $message .= "<p><strong>Context:</strong> $context</p>";
        }
        $message .= '<p><strong>Time:</strong> ' . current_time('mysql') . '</p>';
        $message .= '<p><strong>Site:</strong> ' . home_url() . '</p>';
        $message .= '<p><small>This is an automated alert from Hexagon Automation Plugin</small></p>';
        $message .= '</body></html>';
        
        self::send_email($admin_email, $subject, $message);
    }
    
    public static function auto_repair_email($wp_error) {
        $error_message = $wp_error->get_error_message();
        
        // Common email issues and auto-fixes
        if (strpos($error_message, 'SMTP connect() failed') !== false) {
            self::auto_repair_email_config();
        } elseif (strpos($error_message, 'authentication failed') !== false) {
            hexagon_log('Email Auth Failed', 'SMTP authentication failed - check credentials', 'error');
            self::send_error_alert('SMTP authentication failed. Please check your email credentials.');
        } elseif (strpos($error_message, 'Connection timed out') !== false) {
            // Try alternative port
            self::test_and_fix_smtp();
        }
        
        return $wp_error;
    }
    
    public static function auto_configure_smtp($phpmailer) {
        // If no SMTP is configured, try to auto-configure
        if (!hexagon_get_option('hexagon_email_use_smtp', false)) {
            self::auto_repair_email_config();
        }
        
        return $phpmailer;
    }
}
