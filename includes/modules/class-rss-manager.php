<?php
if (!defined('ABSPATH')) exit;

class Hexagon_RSS_Manager {
    
    public static function init() {
        add_action('wp_ajax_hexagon_add_rss_feed', [__CLASS__, 'ajax_add_rss_feed']);
        add_action('wp_ajax_hexagon_remove_rss_feed', [__CLASS__, 'ajax_remove_rss_feed']);
        add_action('wp_ajax_hexagon_fetch_rss_feeds', [__CLASS__, 'ajax_fetch_rss_feeds']);
        add_action('wp_ajax_hexagon_test_rss_feed', [__CLASS__, 'ajax_test_rss_feed']);
        
        // Schedule RSS fetching
        add_action('hexagon_fetch_rss_cron', [__CLASS__, 'fetch_all_rss_feeds']);
        
        // Register cron job
        if (!wp_next_scheduled('hexagon_fetch_rss_cron')) {
            wp_schedule_event(time(), 'hourly', 'hexagon_fetch_rss_cron');
        }
    }
    
    public static function add_rss_feed($url, $title = '', $category = '', $auto_post = false) {
        // Validate RSS URL
        $feed_data = self::parse_rss_feed($url);
        if (is_wp_error($feed_data)) {
            return $feed_data;
        }
        
        $rss_feeds = get_option('hexagon_rss_feeds', []);
        
        // Generate unique ID
        $feed_id = md5($url . time());
        
        // Auto-detect title if not provided
        if (empty($title) && isset($feed_data['title'])) {
            $title = $feed_data['title'];
        }
        
        $new_feed = [
            'id' => $feed_id,
            'url' => $url,
            'title' => $title,
            'category' => $category,
            'auto_post' => $auto_post,
            'status' => 'active',
            'last_fetch' => null,
            'last_article_count' => 0,
            'total_articles_processed' => 0,
            'created_at' => current_time('mysql'),
            'updated_at' => current_time('mysql')
        ];
        
        $rss_feeds[$feed_id] = $new_feed;
        update_option('hexagon_rss_feeds', $rss_feeds);
        
        hexagon_log('RSS Feed Added', "Added RSS feed: {$title} ({$url})", 'info');
        
        return $new_feed;
    }
    
    public static function remove_rss_feed($feed_id) {
        $rss_feeds = get_option('hexagon_rss_feeds', []);
        
        if (!isset($rss_feeds[$feed_id])) {
            return new WP_Error('feed_not_found', 'RSS feed not found');
        }
        
        $feed_title = $rss_feeds[$feed_id]['title'];
        unset($rss_feeds[$feed_id]);
        update_option('hexagon_rss_feeds', $rss_feeds);
        
        hexagon_log('RSS Feed Removed', "Removed RSS feed: {$feed_title}", 'info');
        
        return true;
    }
    
    public static function get_rss_feeds() {
        return get_option('hexagon_rss_feeds', []);
    }
    
    public static function parse_rss_feed($url) {
        $response = wp_remote_get($url, [
            'timeout' => 30,
            'headers' => [
                'User-Agent' => 'Hexagon Automation RSS Reader/1.0'
            ]
        ]);
        
        if (is_wp_error($response)) {
            return new WP_Error('fetch_failed', 'Failed to fetch RSS feed: ' . $response->get_error_message());
        }
        
        $body = wp_remote_retrieve_body($response);
        if (empty($body)) {
            return new WP_Error('empty_feed', 'RSS feed is empty');
        }
        
        // Parse XML
        libxml_use_internal_errors(true);
        $xml = simplexml_load_string($body);
        
        if ($xml === false) {
            $errors = libxml_get_errors();
            $error_message = 'XML parsing failed';
            if (!empty($errors)) {
                $error_message .= ': ' . $errors[0]->message;
            }
            return new WP_Error('xml_parse_failed', $error_message);
        }
        
        $feed_data = [
            'title' => '',
            'description' => '',
            'link' => '',
            'items' => []
        ];
        
        // Detect RSS/Atom format
        if (isset($xml->channel)) {
            // RSS 2.0
            $channel = $xml->channel;
            $feed_data['title'] = (string) $channel->title;
            $feed_data['description'] = (string) $channel->description;
            $feed_data['link'] = (string) $channel->link;
            
            foreach ($channel->item as $item) {
                $feed_data['items'][] = [
                    'title' => (string) $item->title,
                    'link' => (string) $item->link,
                    'description' => (string) $item->description,
                    'pubDate' => (string) $item->pubDate,
                    'guid' => (string) $item->guid
                ];
            }
        } elseif (isset($xml->entry)) {
            // Atom
            $feed_data['title'] = (string) $xml->title;
            $feed_data['description'] = (string) $xml->subtitle;
            $feed_data['link'] = (string) $xml->link['href'];
            
            foreach ($xml->entry as $entry) {
                $feed_data['items'][] = [
                    'title' => (string) $entry->title,
                    'link' => (string) $entry->link['href'],
                    'description' => (string) $entry->summary,
                    'pubDate' => (string) $entry->published,
                    'guid' => (string) $entry->id
                ];
            }
        }
        
        return $feed_data;
    }
    
    public static function fetch_rss_feed($feed_id) {
        $rss_feeds = get_option('hexagon_rss_feeds', []);
        
        if (!isset($rss_feeds[$feed_id])) {
            return new WP_Error('feed_not_found', 'RSS feed not found');
        }
        
        $feed = $rss_feeds[$feed_id];
        $feed_data = self::parse_rss_feed($feed['url']);
        
        if (is_wp_error($feed_data)) {
            // Mark feed as inactive if failed
            $rss_feeds[$feed_id]['status'] = 'error';
            $rss_feeds[$feed_id]['last_error'] = $feed_data->get_error_message();
            $rss_feeds[$feed_id]['updated_at'] = current_time('mysql');
            update_option('hexagon_rss_feeds', $rss_feeds);
            
            return $feed_data;
        }
        
        $new_articles = [];
        $existing_articles = get_option('hexagon_rss_articles_' . $feed_id, []);
        
        foreach ($feed_data['items'] as $item) {
            $article_guid = $item['guid'] ?: md5($item['link']);
            
            // Skip if article already exists
            if (isset($existing_articles[$article_guid])) {
                continue;
            }
            
            $article = [
                'guid' => $article_guid,
                'title' => $item['title'],
                'link' => $item['link'],
                'description' => $item['description'],
                'pubDate' => $item['pubDate'],
                'feed_id' => $feed_id,
                'feed_title' => $feed['title'],
                'processed' => false,
                'created_at' => current_time('mysql')
            ];
            
            $existing_articles[$article_guid] = $article;
            $new_articles[] = $article;
        }
        
        // Update articles
        update_option('hexagon_rss_articles_' . $feed_id, $existing_articles);
        
        // Update feed stats
        $rss_feeds[$feed_id]['status'] = 'active';
        $rss_feeds[$feed_id]['last_fetch'] = current_time('mysql');
        $rss_feeds[$feed_id]['last_article_count'] = count($new_articles);
        $rss_feeds[$feed_id]['total_articles_processed'] += count($new_articles);
        $rss_feeds[$feed_id]['updated_at'] = current_time('mysql');
        unset($rss_feeds[$feed_id]['last_error']);
        
        update_option('hexagon_rss_feeds', $rss_feeds);
        
        // Auto-post if enabled
        if ($feed['auto_post'] && !empty($new_articles)) {
            self::auto_post_articles($new_articles);
        }
        
        hexagon_log('RSS Feed Fetched', "Fetched " . count($new_articles) . " new articles from {$feed['title']}", 'info');
        
        return [
            'new_articles' => count($new_articles),
            'total_articles' => count($existing_articles),
            'feed_data' => $feed_data
        ];
    }
    
    public static function fetch_all_rss_feeds() {
        $rss_feeds = get_option('hexagon_rss_feeds', []);
        $total_new_articles = 0;
        
        foreach ($rss_feeds as $feed_id => $feed) {
            if ($feed['status'] === 'active') {
                $result = self::fetch_rss_feed($feed_id);
                if (!is_wp_error($result)) {
                    $total_new_articles += $result['new_articles'];
                }
                
                // Add delay to prevent overwhelming servers
                sleep(2);
            }
        }
        
        hexagon_log('RSS Batch Fetch', "Fetched {$total_new_articles} new articles from all feeds", 'info');
        
        return $total_new_articles;
    }
    
    private static function auto_post_articles($articles) {
        if (!class_exists('Hexagon_AI_Manager')) {
            return;
        }
        
        foreach ($articles as $article) {
            try {
                // Generate WordPress post from RSS article
                $ai_prompt = "Create a WordPress blog post based on this RSS article:\n\n";
                $ai_prompt .= "Title: {$article['title']}\n";
                $ai_prompt .= "Description: {$article['description']}\n";
                $ai_prompt .= "Source: {$article['link']}\n\n";
                $ai_prompt .= "Please create an engaging blog post that summarizes the key points and adds value for readers. Include proper attribution to the source.";
                
                $ai_result = Hexagon_AI_Manager::generate_content('chatgpt', 'article', $ai_prompt);
                
                if (!is_wp_error($ai_result)) {
                    // Create WordPress post
                    $post_data = [
                        'post_title' => $article['title'],
                        'post_content' => $ai_result['content'],
                        'post_status' => 'draft', // Save as draft for review
                        'post_type' => 'post',
                        'meta_input' => [
                            'hexagon_source_feed' => $article['feed_id'],
                            'hexagon_source_url' => $article['link'],
                            'hexagon_rss_guid' => $article['guid']
                        ]
                    ];
                    
                    $post_id = wp_insert_post($post_data);
                    
                    if ($post_id && !is_wp_error($post_id)) {
                        hexagon_log('RSS Auto-Post', "Created post from RSS article: {$article['title']}", 'info');
                        
                        // Mark article as processed
                        $existing_articles = get_option('hexagon_rss_articles_' . $article['feed_id'], []);
                        $existing_articles[$article['guid']]['processed'] = true;
                        $existing_articles[$article['guid']]['post_id'] = $post_id;
                        update_option('hexagon_rss_articles_' . $article['feed_id'], $existing_articles);
                    }
                }
            } catch (Exception $e) {
                hexagon_log('RSS Auto-Post Error', "Failed to create post from RSS: " . $e->getMessage(), 'error');
            }
        }
    }
    
    public static function get_rss_articles($feed_id = null, $limit = 50) {
        if ($feed_id) {
            $articles = get_option('hexagon_rss_articles_' . $feed_id, []);
            return array_slice($articles, 0, $limit);
        }
        
        // Get articles from all feeds
        $all_articles = [];
        $rss_feeds = get_option('hexagon_rss_feeds', []);
        
        foreach ($rss_feeds as $feed_id => $feed) {
            $articles = get_option('hexagon_rss_articles_' . $feed_id, []);
            $all_articles = array_merge($all_articles, array_values($articles));
        }
        
        // Sort by creation date
        usort($all_articles, function($a, $b) {
            return strtotime($b['created_at']) - strtotime($a['created_at']);
        });
        
        return array_slice($all_articles, 0, $limit);
    }
    
    public static function get_rss_stats() {
        $rss_feeds = get_option('hexagon_rss_feeds', []);
        $active_feeds = 0;
        $total_articles = 0;
        $recent_articles = 0;
        
        $yesterday = date('Y-m-d H:i:s', strtotime('-24 hours'));
        
        foreach ($rss_feeds as $feed) {
            if ($feed['status'] === 'active') {
                $active_feeds++;
            }
            $total_articles += $feed['total_articles_processed'];
            
            // Count recent articles
            $articles = get_option('hexagon_rss_articles_' . $feed['id'], []);
            foreach ($articles as $article) {
                if ($article['created_at'] >= $yesterday) {
                    $recent_articles++;
                }
            }
        }
        
        return [
            'total_feeds' => count($rss_feeds),
            'active_feeds' => $active_feeds,
            'total_articles' => $total_articles,
            'recent_articles' => $recent_articles
        ];
    }
    
    // AJAX Handlers
    public static function ajax_add_rss_feed() {
        check_ajax_referer('hexagon_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die('Insufficient permissions');
        }
        
        $url = esc_url_raw($_POST['url']);
        $title = sanitize_text_field($_POST['title'] ?? '');
        $category = sanitize_text_field($_POST['category'] ?? '');
        $auto_post = isset($_POST['auto_post']) && $_POST['auto_post'] === 'true';
        
        $result = self::add_rss_feed($url, $title, $category, $auto_post);
        
        if (is_wp_error($result)) {
            wp_send_json_error($result->get_error_message());
        } else {
            wp_send_json_success($result);
        }
    }
    
    public static function ajax_remove_rss_feed() {
        check_ajax_referer('hexagon_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die('Insufficient permissions');
        }
        
        $feed_id = sanitize_text_field($_POST['feed_id']);
        $result = self::remove_rss_feed($feed_id);
        
        if (is_wp_error($result)) {
            wp_send_json_error($result->get_error_message());
        } else {
            wp_send_json_success('RSS feed removed successfully');
        }
    }
    
    public static function ajax_fetch_rss_feeds() {
        check_ajax_referer('hexagon_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die('Insufficient permissions');
        }
        
        $feed_id = sanitize_text_field($_POST['feed_id'] ?? '');
        
        if ($feed_id) {
            $result = self::fetch_rss_feed($feed_id);
        } else {
            $result = self::fetch_all_rss_feeds();
        }
        
        if (is_wp_error($result)) {
            wp_send_json_error($result->get_error_message());
        } else {
            wp_send_json_success($result);
        }
    }
    
    public static function ajax_test_rss_feed() {
        check_ajax_referer('hexagon_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die('Insufficient permissions');
        }
        
        $url = esc_url_raw($_POST['url']);
        $feed_data = self::parse_rss_feed($url);
        
        if (is_wp_error($feed_data)) {
            wp_send_json_error($feed_data->get_error_message());
        } else {
            wp_send_json_success([
                'title' => $feed_data['title'],
                'description' => $feed_data['description'],
                'item_count' => count($feed_data['items']),
                'latest_items' => array_slice($feed_data['items'], 0, 5)
            ]);
        }
    }
}