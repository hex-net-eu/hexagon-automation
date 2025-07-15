<?php
if (!defined('ABSPATH')) exit;

class Hexagon_Analytics_Dashboard {
    
    public static function init() {
        add_action('wp_ajax_hexagon_get_analytics_overview', [__CLASS__, 'ajax_get_analytics_overview']);
        add_action('wp_ajax_hexagon_get_ai_analytics', [__CLASS__, 'ajax_get_ai_analytics']);
        add_action('wp_ajax_hexagon_get_social_analytics', [__CLASS__, 'ajax_get_social_analytics']);
        add_action('wp_ajax_hexagon_get_image_analytics', [__CLASS__, 'ajax_get_image_analytics']);
        add_action('wp_ajax_hexagon_get_error_analytics', [__CLASS__, 'ajax_get_error_analytics']);
        add_action('wp_ajax_hexagon_get_performance_analytics', [__CLASS__, 'ajax_get_performance_analytics']);
        add_action('wp_ajax_hexagon_get_usage_analytics', [__CLASS__, 'ajax_get_usage_analytics']);
        add_action('wp_ajax_hexagon_export_analytics_report', [__CLASS__, 'ajax_export_analytics_report']);
        add_action('wp_ajax_hexagon_get_real_time_stats', [__CLASS__, 'ajax_get_real_time_stats']);
        
        // Schedule analytics aggregation
        if (!wp_next_scheduled('hexagon_aggregate_analytics')) {
            wp_schedule_event(time(), 'hourly', 'hexagon_aggregate_analytics');
        }
        add_action('hexagon_aggregate_analytics', [__CLASS__, 'aggregate_analytics_data']);
    }
    
    public static function get_analytics_overview($date_range = '30_days') {
        global $wpdb;
        
        $date_filter = self::get_date_filter($date_range);
        
        // Content generation stats
        $content_table = $wpdb->prefix . 'hex_content_generation';
        $content_stats = $wpdb->get_row($wpdb->prepare("
            SELECT 
                COUNT(*) as total_generations,
                SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as successful_generations,
                AVG(CASE WHEN status = 'completed' THEN tokens_used ELSE 0 END) as avg_tokens,
                SUM(CASE WHEN status = 'completed' THEN cost ELSE 0 END) as total_cost,
                AVG(CASE WHEN status = 'completed' THEN quality_score ELSE 0 END) as avg_quality,
                AVG(CASE WHEN status = 'completed' THEN seo_score ELSE 0 END) as avg_seo_score
            FROM {$content_table} 
            WHERE created_at >= %s
        ", $date_filter), ARRAY_A);
        
        // Image generation stats
        $image_table = $wpdb->prefix . 'hex_generated_images';
        $image_stats = $wpdb->get_row($wpdb->prepare("
            SELECT 
                COUNT(*) as total_images,
                SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as successful_images,
                SUM(CASE WHEN status = 'completed' THEN cost ELSE 0 END) as total_cost,
                AVG(CASE WHEN status = 'completed' THEN generation_time ELSE 0 END) as avg_generation_time
            FROM {$image_table} 
            WHERE created_at >= %s
        ", $date_filter), ARRAY_A);
        
        // Social media stats
        $social_table = $wpdb->prefix . 'hex_social_posts';
        $social_stats = $wpdb->get_row($wpdb->prepare("
            SELECT 
                COUNT(*) as total_posts,
                SUM(CASE WHEN status = 'published' THEN 1 ELSE 0 END) as published_posts,
                AVG(performance_score) as avg_performance,
                COUNT(DISTINCT platform) as active_platforms
            FROM {$social_table} 
            WHERE created_at >= %s
        ", $date_filter), ARRAY_A);
        
        // Error stats
        $error_table = $wpdb->prefix . 'hex_error_log';
        $error_stats = $wpdb->get_row($wpdb->prepare("
            SELECT 
                COUNT(*) as total_errors,
                SUM(CASE WHEN resolved = 0 THEN 1 ELSE 0 END) as unresolved_errors,
                SUM(occurrence_count) as total_occurrences
            FROM {$error_table} 
            WHERE timestamp >= %s
        ", $date_filter), ARRAY_A);
        
        // System performance
        $logs_table = $wpdb->prefix . 'hex_logs';
        $performance_stats = $wpdb->get_row($wpdb->prepare("
            SELECT 
                AVG(JSON_EXTRACT(context, '$.execution_time')) as avg_execution_time,
                AVG(JSON_EXTRACT(context, '$.memory_usage')) as avg_memory_usage,
                AVG(JSON_EXTRACT(context, '$.database_queries')) as avg_database_queries,
                COUNT(*) as performance_samples
            FROM {$logs_table} 
            WHERE category = 'Performance' 
            AND created_at >= %s
        ", $date_filter), ARRAY_A);
        
        return [
            'date_range' => $date_range,
            'period_start' => $date_filter,
            'period_end' => current_time('mysql'),
            'content_generation' => $content_stats,
            'image_generation' => $image_stats,
            'social_media' => $social_stats,
            'errors' => $error_stats,
            'performance' => $performance_stats,
            'total_cost' => ($content_stats['total_cost'] ?? 0) + ($image_stats['total_cost'] ?? 0),
            'success_rate' => self::calculate_overall_success_rate($content_stats, $image_stats, $social_stats)
        ];
    }
    
    public static function get_ai_analytics($date_range = '30_days', $provider = 'all') {
        global $wpdb;
        
        $date_filter = self::get_date_filter($date_range);
        $content_table = $wpdb->prefix . 'hex_content_generation';
        
        $where_clause = "created_at >= %s";
        $params = [$date_filter];
        
        if ($provider !== 'all') {
            $where_clause .= " AND ai_provider = %s";
            $params[] = $provider;
        }
        
        // Provider breakdown
        $provider_stats = $wpdb->get_results($wpdb->prepare("
            SELECT 
                ai_provider,
                COUNT(*) as total_requests,
                SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as successful_requests,
                AVG(CASE WHEN status = 'completed' THEN tokens_used ELSE 0 END) as avg_tokens,
                SUM(CASE WHEN status = 'completed' THEN tokens_used ELSE 0 END) as total_tokens,
                SUM(CASE WHEN status = 'completed' THEN cost ELSE 0 END) as total_cost,
                AVG(CASE WHEN status = 'completed' THEN generation_time ELSE 0 END) as avg_generation_time,
                AVG(CASE WHEN status = 'completed' THEN quality_score ELSE 0 END) as avg_quality_score,
                AVG(CASE WHEN status = 'completed' THEN seo_score ELSE 0 END) as avg_seo_score
            FROM {$content_table} 
            WHERE {$where_clause}
            GROUP BY ai_provider
            ORDER BY total_requests DESC
        ", ...$params), ARRAY_A);
        
        // Daily usage trends
        $daily_trends = $wpdb->get_results($wpdb->prepare("
            SELECT 
                DATE(created_at) as date,
                COUNT(*) as requests,
                SUM(CASE WHEN status = 'completed' THEN tokens_used ELSE 0 END) as tokens_used,
                SUM(CASE WHEN status = 'completed' THEN cost ELSE 0 END) as cost
            FROM {$content_table} 
            WHERE {$where_clause}
            GROUP BY DATE(created_at)
            ORDER BY date DESC
            LIMIT 30
        ", ...$params), ARRAY_A);
        
        // Content type breakdown
        $content_type_stats = $wpdb->get_results($wpdb->prepare("
            SELECT 
                content_type,
                COUNT(*) as count,
                AVG(CASE WHEN status = 'completed' THEN quality_score ELSE 0 END) as avg_quality,
                AVG(CASE WHEN status = 'completed' THEN seo_score ELSE 0 END) as avg_seo_score
            FROM {$content_table} 
            WHERE {$where_clause}
            GROUP BY content_type
            ORDER BY count DESC
        ", ...$params), ARRAY_A);
        
        // Language breakdown
        $language_stats = $wpdb->get_results($wpdb->prepare("
            SELECT 
                language,
                COUNT(*) as count,
                AVG(CASE WHEN status = 'completed' THEN quality_score ELSE 0 END) as avg_quality
            FROM {$content_table} 
            WHERE {$where_clause}
            GROUP BY language
            ORDER BY count DESC
        ", ...$params), ARRAY_A);
        
        return [
            'provider_stats' => $provider_stats,
            'daily_trends' => array_reverse($daily_trends),
            'content_types' => $content_type_stats,
            'languages' => $language_stats,
            'date_range' => $date_range
        ];
    }
    
    public static function get_social_analytics($date_range = '30_days', $platform = 'all') {
        global $wpdb;
        
        $date_filter = self::get_date_filter($date_range);
        $social_table = $wpdb->prefix . 'hex_social_posts';
        $scheduled_table = $wpdb->prefix . 'hex_scheduled_posts';
        
        $where_clause = "created_at >= %s";
        $params = [$date_filter];
        
        if ($platform !== 'all') {
            $where_clause .= " AND platform = %s";
            $params[] = $platform;
        }
        
        // Platform breakdown
        $platform_stats = $wpdb->get_results($wpdb->prepare("
            SELECT 
                platform,
                COUNT(*) as total_posts,
                SUM(CASE WHEN status = 'published' THEN 1 ELSE 0 END) as published_posts,
                AVG(performance_score) as avg_performance,
                SUM(JSON_EXTRACT(engagement_data, '$.likes')) as total_likes,
                SUM(JSON_EXTRACT(engagement_data, '$.comments')) as total_comments,
                SUM(JSON_EXTRACT(engagement_data, '$.shares')) as total_shares,
                SUM(JSON_EXTRACT(reach_data, '$.impressions')) as total_impressions,
                SUM(JSON_EXTRACT(reach_data, '$.reach')) as total_reach
            FROM {$social_table} 
            WHERE {$where_clause}
            GROUP BY platform
            ORDER BY total_posts DESC
        ", ...$params), ARRAY_A);
        
        // Daily posting trends
        $daily_trends = $wpdb->get_results($wpdb->prepare("
            SELECT 
                DATE(posted_at) as date,
                COUNT(*) as posts_published,
                AVG(performance_score) as avg_performance
            FROM {$social_table} 
            WHERE {$where_clause} AND status = 'published'
            GROUP BY DATE(posted_at)
            ORDER BY date DESC
            LIMIT 30
        ", ...$params), ARRAY_A);
        
        // Engagement analysis
        $engagement_stats = $wpdb->get_results($wpdb->prepare("
            SELECT 
                platform,
                AVG(JSON_EXTRACT(engagement_data, '$.likes') / NULLIF(JSON_EXTRACT(reach_data, '$.reach'), 0)) as avg_like_rate,
                AVG(JSON_EXTRACT(engagement_data, '$.comments') / NULLIF(JSON_EXTRACT(reach_data, '$.reach'), 0)) as avg_comment_rate,
                AVG(JSON_EXTRACT(engagement_data, '$.shares') / NULLIF(JSON_EXTRACT(reach_data, '$.reach'), 0)) as avg_share_rate
            FROM {$social_table} 
            WHERE {$where_clause} 
            AND status = 'published'
            AND JSON_EXTRACT(reach_data, '$.reach') > 0
            GROUP BY platform
        ", ...$params), ARRAY_A);
        
        // Scheduling success rate
        $scheduling_stats = $wpdb->get_row($wpdb->prepare("
            SELECT 
                COUNT(*) as total_scheduled,
                SUM(CASE WHEN status = 'published' THEN 1 ELSE 0 END) as successful_posts,
                SUM(CASE WHEN status = 'failed' THEN 1 ELSE 0 END) as failed_posts,
                SUM(CASE WHEN status = 'partial' THEN 1 ELSE 0 END) as partial_posts
            FROM {$scheduled_table} 
            WHERE created_at >= %s
        ", $date_filter), ARRAY_A);
        
        return [
            'platform_stats' => $platform_stats,
            'daily_trends' => array_reverse($daily_trends),
            'engagement_stats' => $engagement_stats,
            'scheduling_stats' => $scheduling_stats,
            'date_range' => $date_range
        ];
    }
    
    public static function get_image_analytics($date_range = '30_days', $provider = 'all') {
        global $wpdb;
        
        $date_filter = self::get_date_filter($date_range);
        $image_table = $wpdb->prefix . 'hex_generated_images';
        
        $where_clause = "created_at >= %s";
        $params = [$date_filter];
        
        if ($provider !== 'all') {
            $where_clause .= " AND provider = %s";
            $params[] = $provider;
        }
        
        // Provider breakdown
        $provider_stats = $wpdb->get_results($wpdb->prepare("
            SELECT 
                provider,
                COUNT(*) as total_requests,
                SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as successful_generations,
                AVG(CASE WHEN status = 'completed' THEN generation_time ELSE 0 END) as avg_generation_time,
                SUM(CASE WHEN status = 'completed' THEN cost ELSE 0 END) as total_cost,
                AVG(CASE WHEN status = 'completed' THEN cost ELSE 0 END) as avg_cost_per_image
            FROM {$image_table} 
            WHERE {$where_clause}
            GROUP BY provider
            ORDER BY total_requests DESC
        ", ...$params), ARRAY_A);
        
        // Daily generation trends
        $daily_trends = $wpdb->get_results($wpdb->prepare("
            SELECT 
                DATE(created_at) as date,
                COUNT(*) as requests,
                SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as successful,
                SUM(CASE WHEN status = 'completed' THEN cost ELSE 0 END) as daily_cost
            FROM {$image_table} 
            WHERE {$where_clause}
            GROUP BY DATE(created_at)
            ORDER BY date DESC
            LIMIT 30
        ", ...$params), ARRAY_A);
        
        // Size and style analysis
        $style_stats = $wpdb->get_results($wpdb->prepare("
            SELECT 
                style,
                size,
                COUNT(*) as count,
                AVG(CASE WHEN status = 'completed' THEN generation_time ELSE 0 END) as avg_time,
                AVG(CASE WHEN status = 'completed' THEN cost ELSE 0 END) as avg_cost
            FROM {$image_table} 
            WHERE {$where_clause}
            GROUP BY style, size
            ORDER BY count DESC
            LIMIT 20
        ", ...$params), ARRAY_A);
        
        return [
            'provider_stats' => $provider_stats,
            'daily_trends' => array_reverse($daily_trends),
            'style_stats' => $style_stats,
            'date_range' => $date_range
        ];
    }
    
    public static function get_error_analytics($date_range = '30_days') {
        global $wpdb;
        
        $date_filter = self::get_date_filter($date_range);
        $error_table = $wpdb->prefix . 'hex_error_log';
        $logs_table = $wpdb->prefix . 'hex_logs';
        
        // Error type breakdown
        $error_types = $wpdb->get_results($wpdb->prepare("
            SELECT 
                type,
                severity,
                COUNT(*) as count,
                SUM(occurrence_count) as total_occurrences,
                AVG(occurrence_count) as avg_occurrences_per_error,
                SUM(CASE WHEN resolved = 1 THEN 1 ELSE 0 END) as resolved_count
            FROM {$error_table} 
            WHERE timestamp >= %s
            GROUP BY type, severity
            ORDER BY total_occurrences DESC
        ", $date_filter), ARRAY_A);
        
        // Daily error trends
        $daily_trends = $wpdb->get_results($wpdb->prepare("
            SELECT 
                DATE(timestamp) as date,
                COUNT(*) as new_errors,
                SUM(occurrence_count) as total_occurrences,
                COUNT(CASE WHEN severity = 'critical' THEN 1 END) as critical_errors,
                COUNT(CASE WHEN severity = 'error' THEN 1 END) as regular_errors
            FROM {$error_table} 
            WHERE timestamp >= %s
            GROUP BY DATE(timestamp)
            ORDER BY date DESC
            LIMIT 30
        ", $date_filter), ARRAY_A);
        
        // Log level distribution
        $log_levels = $wpdb->get_results($wpdb->prepare("
            SELECT 
                level,
                COUNT(*) as count
            FROM {$logs_table} 
            WHERE created_at >= %s
            GROUP BY level
            ORDER BY count DESC
        ", $date_filter), ARRAY_A);
        
        // Most common errors
        $common_errors = $wpdb->get_results($wpdb->prepare("
            SELECT 
                message,
                type,
                severity,
                file,
                line,
                COUNT(*) as occurrences,
                MAX(timestamp) as last_occurrence,
                SUM(CASE WHEN resolved = 1 THEN 1 ELSE 0 END) as resolved_instances
            FROM {$error_table} 
            WHERE timestamp >= %s
            GROUP BY message, file, line
            ORDER BY occurrences DESC
            LIMIT 20
        ", $date_filter), ARRAY_A);
        
        return [
            'error_types' => $error_types,
            'daily_trends' => array_reverse($daily_trends),
            'log_levels' => $log_levels,
            'common_errors' => $common_errors,
            'date_range' => $date_range
        ];
    }
    
    public static function get_performance_analytics($date_range = '30_days') {
        global $wpdb;
        
        $date_filter = self::get_date_filter($date_range);
        $logs_table = $wpdb->prefix . 'hex_logs';
        
        // Performance metrics
        $performance_stats = $wpdb->get_row($wpdb->prepare("
            SELECT 
                COUNT(*) as total_samples,
                AVG(JSON_EXTRACT(context, '$.execution_time')) as avg_execution_time,
                MAX(JSON_EXTRACT(context, '$.execution_time')) as max_execution_time,
                MIN(JSON_EXTRACT(context, '$.execution_time')) as min_execution_time,
                AVG(JSON_EXTRACT(context, '$.memory_usage')) as avg_memory_usage,
                MAX(JSON_EXTRACT(context, '$.memory_usage')) as max_memory_usage,
                AVG(JSON_EXTRACT(context, '$.database_queries')) as avg_database_queries,
                MAX(JSON_EXTRACT(context, '$.database_queries')) as max_database_queries,
                AVG(JSON_EXTRACT(context, '$.peak_memory')) as avg_peak_memory
            FROM {$logs_table} 
            WHERE category = 'Performance' 
            AND created_at >= %s
        ", $date_filter), ARRAY_A);
        
        // Daily performance trends
        $daily_performance = $wpdb->get_results($wpdb->prepare("
            SELECT 
                DATE(created_at) as date,
                COUNT(*) as samples,
                AVG(JSON_EXTRACT(context, '$.execution_time')) as avg_execution_time,
                AVG(JSON_EXTRACT(context, '$.memory_usage')) as avg_memory_usage,
                COUNT(CASE WHEN JSON_EXTRACT(context, '$.execution_time') > 2 THEN 1 END) as slow_requests
            FROM {$logs_table} 
            WHERE category = 'Performance' 
            AND created_at >= %s
            GROUP BY DATE(created_at)
            ORDER BY date DESC
            LIMIT 30
        ", $date_filter), ARRAY_A);
        
        // Page-specific performance
        $page_performance = $wpdb->get_results($wpdb->prepare("
            SELECT 
                JSON_EXTRACT(context, '$.page_url') as page_url,
                JSON_EXTRACT(context, '$.is_admin') as is_admin,
                COUNT(*) as samples,
                AVG(JSON_EXTRACT(context, '$.execution_time')) as avg_execution_time,
                AVG(JSON_EXTRACT(context, '$.memory_usage')) as avg_memory_usage,
                AVG(JSON_EXTRACT(context, '$.database_queries')) as avg_queries
            FROM {$logs_table} 
            WHERE category = 'Performance' 
            AND created_at >= %s
            AND JSON_EXTRACT(context, '$.page_url') IS NOT NULL
            GROUP BY JSON_EXTRACT(context, '$.page_url'), JSON_EXTRACT(context, '$.is_admin')
            HAVING samples >= 5
            ORDER BY avg_execution_time DESC
            LIMIT 20
        ", $date_filter), ARRAY_A);
        
        return [
            'performance_stats' => $performance_stats,
            'daily_performance' => array_reverse($daily_performance),
            'page_performance' => $page_performance,
            'date_range' => $date_range
        ];
    }
    
    public static function get_usage_analytics($date_range = '30_days') {
        global $wpdb;
        
        $date_filter = self::get_date_filter($date_range);
        $logs_table = $wpdb->prefix . 'hex_logs';
        
        // Feature usage
        $feature_usage = $wpdb->get_results($wpdb->prepare("
            SELECT 
                category,
                COUNT(*) as usage_count,
                COUNT(DISTINCT user_id) as unique_users,
                COUNT(DISTINCT DATE(created_at)) as active_days
            FROM {$logs_table} 
            WHERE created_at >= %s
            AND user_id > 0
            GROUP BY category
            ORDER BY usage_count DESC
        ", $date_filter), ARRAY_A);
        
        // User activity
        $user_activity = $wpdb->get_results($wpdb->prepare("
            SELECT 
                user_id,
                COUNT(*) as total_actions,
                COUNT(DISTINCT category) as features_used,
                COUNT(DISTINCT DATE(created_at)) as active_days,
                MAX(created_at) as last_activity
            FROM {$logs_table} 
            WHERE created_at >= %s
            AND user_id > 0
            GROUP BY user_id
            ORDER BY total_actions DESC
            LIMIT 20
        ", $date_filter), ARRAY_A);
        
        // Daily activity patterns
        $daily_activity = $wpdb->get_results($wpdb->prepare("
            SELECT 
                DATE(created_at) as date,
                COUNT(*) as total_actions,
                COUNT(DISTINCT user_id) as active_users,
                COUNT(DISTINCT category) as features_used
            FROM {$logs_table} 
            WHERE created_at >= %s
            AND user_id > 0
            GROUP BY DATE(created_at)
            ORDER BY date DESC
            LIMIT 30
        ", $date_filter), ARRAY_A);
        
        // Hourly patterns
        $hourly_patterns = $wpdb->get_results($wpdb->prepare("
            SELECT 
                HOUR(created_at) as hour,
                COUNT(*) as actions,
                COUNT(DISTINCT user_id) as users
            FROM {$logs_table} 
            WHERE created_at >= %s
            AND user_id > 0
            GROUP BY HOUR(created_at)
            ORDER BY hour
        ", $date_filter), ARRAY_A);
        
        return [
            'feature_usage' => $feature_usage,
            'user_activity' => $user_activity,
            'daily_activity' => array_reverse($daily_activity),
            'hourly_patterns' => $hourly_patterns,
            'date_range' => $date_range
        ];
    }
    
    public static function aggregate_analytics_data() {
        // This method runs hourly to pre-calculate analytics data for better performance
        global $wpdb;
        
        $analytics_table = $wpdb->prefix . 'hex_analytics_summary';
        
        // Check if analytics summary table exists, create if not
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '{$analytics_table}'");
        if (!$table_exists) {
            self::create_analytics_summary_table();
        }
        
        $current_hour = date('Y-m-d H:00:00');
        
        // Aggregate data for the current hour
        $summary_data = [
            'period_start' => $current_hour,
            'period_end' => date('Y-m-d H:59:59'),
            'ai_requests' => self::count_records('hex_content_generation', $current_hour),
            'image_requests' => self::count_records('hex_generated_images', $current_hour),
            'social_posts' => self::count_records('hex_social_posts', $current_hour),
            'errors' => self::count_records('hex_error_log', $current_hour, 'timestamp'),
            'performance_samples' => self::count_performance_samples($current_hour),
            'total_cost' => self::calculate_hourly_costs($current_hour)
        ];
        
        // Insert or update the summary
        $wpdb->replace(
            $analytics_table,
            array_merge(['summary_date' => $current_hour], $summary_data),
            ['%s', '%s', '%s', '%d', '%d', '%d', '%d', '%d', '%f']
        );
        
        hexagon_log('Analytics', 'Hourly analytics data aggregated', 'debug');
    }
    
    private static function create_analytics_summary_table() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'hex_analytics_summary';
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE $table_name (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            summary_date datetime NOT NULL,
            period_start datetime NOT NULL,
            period_end datetime NOT NULL,
            ai_requests int(11) NOT NULL DEFAULT 0,
            image_requests int(11) NOT NULL DEFAULT 0,
            social_posts int(11) NOT NULL DEFAULT 0,
            errors int(11) NOT NULL DEFAULT 0,
            performance_samples int(11) NOT NULL DEFAULT 0,
            total_cost decimal(10,4) NOT NULL DEFAULT 0,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY summary_date (summary_date),
            KEY period_start (period_start)
        ) $charset_collate;";
        
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta($sql);
    }
    
    private static function count_records($table, $hour, $date_field = 'created_at') {
        global $wpdb;
        
        $full_table = $wpdb->prefix . $table;
        $end_hour = date('Y-m-d H:59:59', strtotime($hour));
        
        return (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$full_table} WHERE {$date_field} >= %s AND {$date_field} <= %s",
            $hour,
            $end_hour
        ));
    }
    
    private static function count_performance_samples($hour) {
        global $wpdb;
        
        $logs_table = $wpdb->prefix . 'hex_logs';
        $end_hour = date('Y-m-d H:59:59', strtotime($hour));
        
        return (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$logs_table} WHERE category = 'Performance' AND created_at >= %s AND created_at <= %s",
            $hour,
            $end_hour
        ));
    }
    
    private static function calculate_hourly_costs($hour) {
        global $wpdb;
        
        $end_hour = date('Y-m-d H:59:59', strtotime($hour));
        
        $content_cost = $wpdb->get_var($wpdb->prepare(
            "SELECT SUM(cost) FROM {$wpdb->prefix}hex_content_generation WHERE created_at >= %s AND created_at <= %s AND status = 'completed'",
            $hour,
            $end_hour
        ));
        
        $image_cost = $wpdb->get_var($wpdb->prepare(
            "SELECT SUM(cost) FROM {$wpdb->prefix}hex_generated_images WHERE created_at >= %s AND created_at <= %s AND status = 'completed'",
            $hour,
            $end_hour
        ));
        
        return floatval($content_cost) + floatval($image_cost);
    }
    
    private static function get_date_filter($date_range) {
        switch ($date_range) {
            case '24_hours':
                return date('Y-m-d H:i:s', strtotime('-24 hours'));
            case '7_days':
                return date('Y-m-d H:i:s', strtotime('-7 days'));
            case '30_days':
                return date('Y-m-d H:i:s', strtotime('-30 days'));
            case '90_days':
                return date('Y-m-d H:i:s', strtotime('-90 days'));
            case '1_year':
                return date('Y-m-d H:i:s', strtotime('-1 year'));
            default:
                return date('Y-m-d H:i:s', strtotime('-30 days'));
        }
    }
    
    private static function calculate_overall_success_rate($content_stats, $image_stats, $social_stats) {
        $total_requests = ($content_stats['total_generations'] ?? 0) + 
                         ($image_stats['total_images'] ?? 0) + 
                         ($social_stats['total_posts'] ?? 0);
        
        $total_successful = ($content_stats['successful_generations'] ?? 0) + 
                           ($image_stats['successful_images'] ?? 0) + 
                           ($social_stats['published_posts'] ?? 0);
        
        return $total_requests > 0 ? round(($total_successful / $total_requests) * 100, 2) : 0;
    }
    
    // AJAX Handlers
    public static function ajax_get_analytics_overview() {
        check_ajax_referer('hexagon_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
            return;
        }
        
        $date_range = sanitize_text_field($_POST['date_range'] ?? '30_days');
        $data = self::get_analytics_overview($date_range);
        
        wp_send_json_success($data);
    }
    
    public static function ajax_get_ai_analytics() {
        check_ajax_referer('hexagon_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
            return;
        }
        
        $date_range = sanitize_text_field($_POST['date_range'] ?? '30_days');
        $provider = sanitize_text_field($_POST['provider'] ?? 'all');
        $data = self::get_ai_analytics($date_range, $provider);
        
        wp_send_json_success($data);
    }
    
    public static function ajax_get_social_analytics() {
        check_ajax_referer('hexagon_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
            return;
        }
        
        $date_range = sanitize_text_field($_POST['date_range'] ?? '30_days');
        $platform = sanitize_text_field($_POST['platform'] ?? 'all');
        $data = self::get_social_analytics($date_range, $platform);
        
        wp_send_json_success($data);
    }
    
    public static function ajax_get_image_analytics() {
        check_ajax_referer('hexagon_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
            return;
        }
        
        $date_range = sanitize_text_field($_POST['date_range'] ?? '30_days');
        $provider = sanitize_text_field($_POST['provider'] ?? 'all');
        $data = self::get_image_analytics($date_range, $provider);
        
        wp_send_json_success($data);
    }
    
    public static function ajax_get_error_analytics() {
        check_ajax_referer('hexagon_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
            return;
        }
        
        $date_range = sanitize_text_field($_POST['date_range'] ?? '30_days');
        $data = self::get_error_analytics($date_range);
        
        wp_send_json_success($data);
    }
    
    public static function ajax_get_performance_analytics() {
        check_ajax_referer('hexagon_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
            return;
        }
        
        $date_range = sanitize_text_field($_POST['date_range'] ?? '30_days');
        $data = self::get_performance_analytics($date_range);
        
        wp_send_json_success($data);
    }
    
    public static function ajax_get_usage_analytics() {
        check_ajax_referer('hexagon_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
            return;
        }
        
        $date_range = sanitize_text_field($_POST['date_range'] ?? '30_days');
        $data = self::get_usage_analytics($date_range);
        
        wp_send_json_success($data);
    }
    
    public static function ajax_export_analytics_report() {
        check_ajax_referer('hexagon_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
            return;
        }
        
        $date_range = sanitize_text_field($_POST['date_range'] ?? '30_days');
        $format = sanitize_text_field($_POST['format'] ?? 'json');
        $sections = array_map('sanitize_text_field', $_POST['sections'] ?? ['overview']);
        
        $report_data = [
            'report_info' => [
                'generated_at' => current_time('mysql'),
                'date_range' => $date_range,
                'plugin_version' => HEXAGON_VERSION,
                'site_url' => get_site_url()
            ]
        ];
        
        // Add requested sections
        if (in_array('overview', $sections)) {
            $report_data['overview'] = self::get_analytics_overview($date_range);
        }
        if (in_array('ai', $sections)) {
            $report_data['ai_analytics'] = self::get_ai_analytics($date_range);
        }
        if (in_array('social', $sections)) {
            $report_data['social_analytics'] = self::get_social_analytics($date_range);
        }
        if (in_array('images', $sections)) {
            $report_data['image_analytics'] = self::get_image_analytics($date_range);
        }
        if (in_array('errors', $sections)) {
            $report_data['error_analytics'] = self::get_error_analytics($date_range);
        }
        if (in_array('performance', $sections)) {
            $report_data['performance_analytics'] = self::get_performance_analytics($date_range);
        }
        if (in_array('usage', $sections)) {
            $report_data['usage_analytics'] = self::get_usage_analytics($date_range);
        }
        
        $filename = 'hexagon-analytics-report-' . $date_range . '-' . date('Y-m-d-H-i-s');
        
        switch ($format) {
            case 'json':
                $output = json_encode($report_data, JSON_PRETTY_PRINT);
                $filename .= '.json';
                break;
            case 'csv':
                $output = self::convert_to_csv($report_data);
                $filename .= '.csv';
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
    
    public static function ajax_get_real_time_stats() {
        check_ajax_referer('hexagon_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
            return;
        }
        
        global $wpdb;
        
        // Get stats for the last hour
        $last_hour = date('Y-m-d H:i:s', strtotime('-1 hour'));
        
        $real_time_stats = [
            'timestamp' => current_time('mysql'),
            'last_hour' => [
                'ai_requests' => $wpdb->get_var($wpdb->prepare(
                    "SELECT COUNT(*) FROM {$wpdb->prefix}hex_content_generation WHERE created_at >= %s",
                    $last_hour
                )),
                'image_generations' => $wpdb->get_var($wpdb->prepare(
                    "SELECT COUNT(*) FROM {$wpdb->prefix}hex_generated_images WHERE created_at >= %s",
                    $last_hour
                )),
                'social_posts' => $wpdb->get_var($wpdb->prepare(
                    "SELECT COUNT(*) FROM {$wpdb->prefix}hex_social_posts WHERE created_at >= %s",
                    $last_hour
                )),
                'errors' => $wpdb->get_var($wpdb->prepare(
                    "SELECT COUNT(*) FROM {$wpdb->prefix}hex_error_log WHERE timestamp >= %s",
                    $last_hour
                ))
            ],
            'system_status' => [
                'memory_usage' => memory_get_usage(true),
                'peak_memory' => memory_get_peak_usage(true),
                'database_queries' => get_num_queries(),
                'debug_mode' => get_option('hexagon_debug_mode', false)
            ]
        ];
        
        wp_send_json_success($real_time_stats);
    }
    
    private static function convert_to_csv($data) {
        // Simple CSV conversion for overview data
        $csv = "Metric,Value\n";
        
        if (isset($data['overview'])) {
            $overview = $data['overview'];
            $csv .= "Total Content Generations," . ($overview['content_generation']['total_generations'] ?? 0) . "\n";
            $csv .= "Successful Content Generations," . ($overview['content_generation']['successful_generations'] ?? 0) . "\n";
            $csv .= "Total Image Generations," . ($overview['image_generation']['total_images'] ?? 0) . "\n";
            $csv .= "Successful Image Generations," . ($overview['image_generation']['successful_images'] ?? 0) . "\n";
            $csv .= "Total Social Posts," . ($overview['social_media']['total_posts'] ?? 0) . "\n";
            $csv .= "Published Social Posts," . ($overview['social_media']['published_posts'] ?? 0) . "\n";
            $csv .= "Total Errors," . ($overview['errors']['total_errors'] ?? 0) . "\n";
            $csv .= "Total Cost," . ($overview['total_cost'] ?? 0) . "\n";
            $csv .= "Success Rate," . ($overview['success_rate'] ?? 0) . "%\n";
        }
        
        return $csv;
    }
}