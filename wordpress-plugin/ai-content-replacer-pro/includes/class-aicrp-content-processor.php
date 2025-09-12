<?php
/**
 * Content Processor Class for AI Content Replacer Pro
 * Handles content detection, processing, and replacement
 *
 * @package AI_Content_Replacer_Pro
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class AICRP_Content_Processor {
    
    /**
     * Process content with AI
     *
     * @param array $options Processing options
     * @return array Processing results
     */
    public static function process_content($options = array()) {
        $default_options = array(
            'include_pages' => true,
            'include_posts' => true,
            'include_widgets' => false,
            'preserve_images' => true,
            'optimize_seo' => true,
            'backup_original' => true,
            'content_types' => array('page', 'post'),
            'batch_size' => 5
        );

        $options = wp_parse_args($options, $default_options);
        
        // Get business profile for context
        $business_profile = AICRP_Database::get_business_profile();
        if (empty($business_profile)) {
            return array(
                'success' => false,
                'error' => __('Please set up your business profile first.', 'ai-content-replacer-pro')
            );
        }

        // Detect content to process
        $content_items = self::detect_content($options);
        if (empty($content_items)) {
            return array(
                'success' => false,
                'error' => __('No content found to process.', 'ai-content-replacer-pro')
            );
        }

        $results = array(
            'success' => true,
            'processed_count' => 0,
            'failed_count' => 0,
            'skipped_count' => 0,
            'items' => array(),
            'total_tokens' => 0,
            'total_cost' => 0,
            'processing_time' => 0
        );

        $start_time = microtime(true);

        // Process content in batches
        $batches = array_chunk($content_items, $options['batch_size']);
        
        foreach ($batches as $batch) {
            foreach ($batch as $item) {
                $item_result = self::process_content_item($item, $business_profile, $options);
                
                $results['items'][] = $item_result;
                
                if ($item_result['success']) {
                    $results['processed_count']++;
                    $results['total_tokens'] += $item_result['tokens_used'] ?? 0;
                    $results['total_cost'] += $item_result['cost'] ?? 0;
                } elseif ($item_result['skipped']) {
                    $results['skipped_count']++;
                } else {
                    $results['failed_count']++;
                }
            }
            
            // Small delay between batches to avoid overwhelming APIs
            if (count($batches) > 1) {
                sleep(1);
            }
        }

        $end_time = microtime(true);
        $results['processing_time'] = round(($end_time - $start_time) * 1000); // Convert to milliseconds

        return $results;
    }

    /**
     * Detect content to process
     *
     * @param array $options Detection options
     * @return array Content items
     */
    private static function detect_content($options) {
        $content_items = array();
        
        // Build query arguments
        $query_args = array(
            'post_type' => $options['content_types'],
            'post_status' => 'publish',
            'posts_per_page' => -1,
            'meta_query' => array(
                array(
                    'key' => '_aicrp_processed',
                    'compare' => 'NOT EXISTS'
                )
            )
        );

        $posts = get_posts($query_args);
        
        foreach ($posts as $post) {
            $content_items[] = array(
                'id' => $post->ID,
                'type' => $post->post_type,
                'title' => $post->post_title,
                'content' => $post->post_content,
                'excerpt' => $post->post_excerpt,
                'word_count' => str_word_count(strip_tags($post->post_content)),
                'page_builder' => self::detect_page_builder($post->ID),
                'meta_fields' => self::get_processable_meta_fields($post->ID)
            );
        }

        return $content_items;
    }

    /**
     * Process individual content item
     *
     * @param array $item Content item
     * @param array $business_profile Business profile
     * @param array $options Processing options
     * @return array Processing result
     */
    private static function process_content_item($item, $business_profile, $options) {
        $result = array(
            'id' => $item['id'],
            'title' => $item['title'],
            'type' => $item['type'],
            'success' => false,
            'skipped' => false,
            'error' => '',
            'tokens_used' => 0,
            'cost' => 0,
            'changes_made' => array()
        );

        try {
            // Check if content should be skipped
            if (self::should_skip_content($item, $options)) {
                $result['skipped'] = true;
                $result['error'] = __('Content skipped based on criteria', 'ai-content-replacer-pro');
                return $result;
            }

            // Create backup if enabled
            if ($options['backup_original']) {
                self::create_content_backup($item['id'], $item);
            }

            // Process different content parts
            $changes_made = array();
            $total_tokens = 0;
            $total_cost = 0;

            // Process main content
            if (!empty($item['content'])) {
                $content_result = self::process_text_content($item['content'], $business_profile, array(
                    'context' => 'main_content',
                    'post_type' => $item['type'],
                    'page_builder' => $item['page_builder']
                ));

                if ($content_result['success']) {
                    // Update post content
                    wp_update_post(array(
                        'ID' => $item['id'],
                        'post_content' => $content_result['content']
                    ));

                    $changes_made[] = 'main_content';
                    $total_tokens += $content_result['tokens_used'];
                    $total_cost += $content_result['cost'];
                }
            }

            // Process post title if needed
            if (self::should_process_title($item, $options)) {
                $title_result = self::process_text_content($item['title'], $business_profile, array(
                    'context' => 'title',
                    'max_words' => 10
                ));

                if ($title_result['success']) {
                    wp_update_post(array(
                        'ID' => $item['id'],
                        'post_title' => $title_result['content']
                    ));

                    $changes_made[] = 'title';
                    $total_tokens += $title_result['tokens_used'];
                    $total_cost += $title_result['cost'];
                }
            }

            // Process excerpt if exists
            if (!empty($item['excerpt'])) {
                $excerpt_result = self::process_text_content($item['excerpt'], $business_profile, array(
                    'context' => 'excerpt',
                    'max_words' => 50
                ));

                if ($excerpt_result['success']) {
                    wp_update_post(array(
                        'ID' => $item['id'],
                        'post_excerpt' => $excerpt_result['content']
                    ));

                    $changes_made[] = 'excerpt';
                    $total_tokens += $excerpt_result['tokens_used'];
                    $total_cost += $excerpt_result['cost'];
                }
            }

            // Process meta fields (SEO, custom fields)
            if (!empty($item['meta_fields']) && $options['optimize_seo']) {
                $meta_result = self::process_meta_fields($item['id'], $item['meta_fields'], $business_profile);
                if ($meta_result['success']) {
                    $changes_made = array_merge($changes_made, $meta_result['fields_updated']);
                    $total_tokens += $meta_result['tokens_used'];
                    $total_cost += $meta_result['cost'];
                }
            }

            // Mark as processed
            update_post_meta($item['id'], '_aicrp_processed', current_time('mysql'));
            update_post_meta($item['id'], '_aicrp_changes_made', $changes_made);

            $result['success'] = !empty($changes_made);
            $result['changes_made'] = $changes_made;
            $result['tokens_used'] = $total_tokens;
            $result['cost'] = $total_cost;

            if (empty($changes_made)) {
                $result['error'] = __('No changes were needed', 'ai-content-replacer-pro');
            }

        } catch (Exception $e) {
            $result['error'] = $e->getMessage();
            
            // Log error
            AICRP_Security::log_security_event(
                'content_processing_error',
                'medium',
                array(
                    'post_id' => $item['id'],
                    'error' => $e->getMessage()
                )
            );
        }

        return $result;
    }

    /**
     * Process text content with AI
     *
     * @param string $content Original content
     * @param array $business_profile Business profile
     * @param array $context Processing context
     * @return array Processing result
     */
    private static function process_text_content($content, $business_profile, $context = array()) {
        // Clean and validate content
        $content = trim($content);
        if (empty($content)) {
            return array(
                'success' => false,
                'error' => 'Empty content'
            );
        }

        // Check content security
        $security_check = AICRP_Security::scan_content($content);
        if (!$security_check['safe']) {
            return array(
                'success' => false,
                'error' => 'Content failed security check: ' . implode(', ', $security_check['issues'])
            );
        }

        // Build context-specific prompt
        $prompt = self::build_content_prompt($content, $context);

        // Generate new content
        $generation_result = AICRP_API_Manager::generate_content($prompt, $business_profile, $context);

        if (!$generation_result['success']) {
            return array(
                'success' => false,
                'error' => $generation_result['error']
            );
        }

        // Clean and validate generated content
        $new_content = self::clean_generated_content($generation_result['content'], $content, $context);

        // Preserve specific elements if needed
        if (isset($context['page_builder']) && $context['page_builder']) {
            $new_content = self::preserve_page_builder_structure($new_content, $content, $context['page_builder']);
        }

        return array(
            'success' => true,
            'content' => $new_content,
            'tokens_used' => $generation_result['tokens_used'],
            'cost' => $generation_result['cost'],
            'provider_used' => $generation_result['provider_used']
        );
    }

    /**
     * Build content-specific prompt
     *
     * @param string $content Original content
     * @param array $context Processing context
     * @return string Generated prompt
     */
    private static function build_content_prompt($content, $context) {
        $context_type = $context['context'] ?? 'content';
        
        $prompts = array(
            'title' => "Rewrite this page/post title to be more relevant to the business while keeping it engaging and SEO-friendly. Original title: \n\n{$content}",
            'excerpt' => "Rewrite this excerpt to better represent the business and attract the target audience. Keep it concise and compelling. Original excerpt: \n\n{$content}",
            'main_content' => "Rewrite this content to be specifically relevant to the business, its services, and target audience. Maintain the same structure and formatting, but replace generic information with business-specific details. Keep all HTML tags and formatting intact. Original content: \n\n{$content}",
            'meta_description' => "Create an SEO-optimized meta description for this content that includes relevant business keywords and attracts clicks. Keep it under 160 characters. Content: \n\n{$content}"
        );

        return $prompts[$context_type] ?? $prompts['main_content'];
    }

    /**
     * Clean and validate generated content
     *
     * @param string $generated_content Generated content
     * @param string $original_content Original content
     * @param array $context Processing context
     * @return string Cleaned content
     */
    private static function clean_generated_content($generated_content, $original_content, $context) {
        // Remove potential AI disclaimers or meta text
        $generated_content = preg_replace('/^(Here\'s|Here is|I\'ve rewritten|I have rewritten).*?:/i', '', $generated_content);
        $generated_content = trim($generated_content);

        // Preserve HTML structure from original if context is main content
        if (($context['context'] ?? '') === 'main_content') {
            // Extract HTML tags from original
            preg_match_all('/<[^>]+>/', $original_content, $original_tags);
            
            // If original had HTML tags, ensure new content maintains basic structure
            if (!empty($original_tags[0])) {
                // Simple structure preservation - wrap in paragraphs if not already wrapped
                if (!preg_match('/<[^>]+>/', $generated_content)) {
                    $paragraphs = explode("\n\n", $generated_content);
                    $paragraphs = array_map(function($p) {
                        return '<p>' . trim($p) . '</p>';
                    }, array_filter($paragraphs));
                    $generated_content = implode("\n\n", $paragraphs);
                }
            }
        }

        // Final security clean
        $generated_content = AICRP_Security::clean_content($generated_content);

        return $generated_content;
    }

    /**
     * Process meta fields (SEO, custom fields)
     *
     * @param int $post_id Post ID
     * @param array $meta_fields Meta fields to process
     * @param array $business_profile Business profile
     * @return array Processing result
     */
    private static function process_meta_fields($post_id, $meta_fields, $business_profile) {
        $fields_updated = array();
        $total_tokens = 0;
        $total_cost = 0;

        foreach ($meta_fields as $field_key => $field_value) {
            if (empty($field_value)) {
                continue;
            }

            $context = array(
                'context' => 'meta_description',
                'max_words' => 30
            );

            // Handle different SEO plugins
            if (strpos($field_key, 'yoast') !== false || strpos($field_key, 'rank_math') !== false) {
                if (strpos($field_key, 'title') !== false) {
                    $context['context'] = 'title';
                    $context['max_words'] = 10;
                } elseif (strpos($field_key, 'desc') !== false) {
                    $context['context'] = 'meta_description';
                    $context['max_words'] = 25;
                }
            }

            $result = self::process_text_content($field_value, $business_profile, $context);
            
            if ($result['success']) {
                update_post_meta($post_id, $field_key, $result['content']);
                $fields_updated[] = $field_key;
                $total_tokens += $result['tokens_used'];
                $total_cost += $result['cost'];
            }
        }

        return array(
            'success' => !empty($fields_updated),
            'fields_updated' => $fields_updated,
            'tokens_used' => $total_tokens,
            'cost' => $total_cost
        );
    }

    /**
     * Detect page builder used for post
     *
     * @param int $post_id Post ID
     * @return string|false Page builder name or false
     */
    private static function detect_page_builder($post_id) {
        // Check for Elementor
        if (get_post_meta($post_id, '_elementor_edit_mode', true)) {
            return 'elementor';
        }

        // Check for Beaver Builder
        if (get_post_meta($post_id, '_fl_builder_enabled', true)) {
            return 'beaver_builder';
        }

        // Check for Divi
        if (get_post_meta($post_id, '_et_pb_use_builder', true)) {
            return 'divi';
        }

        // Check for Visual Composer
        $content = get_post_field('post_content', $post_id);
        if (strpos($content, '[vc_') !== false) {
            return 'visual_composer';
        }

        // Check for Gutenberg blocks
        if (has_blocks($post_id)) {
            return 'gutenberg';
        }

        return false;
    }

    /**
     * Get processable meta fields for post
     *
     * @param int $post_id Post ID
     * @return array Meta fields
     */
    private static function get_processable_meta_fields($post_id) {
        $meta_fields = array();
        
        // Common SEO plugin fields
        $seo_fields = array(
            '_yoast_wpseo_title',
            '_yoast_wpseo_metadesc',
            'rank_math_title',
            'rank_math_description',
            '_aioseo_title',
            '_aioseo_description'
        );

        foreach ($seo_fields as $field) {
            $value = get_post_meta($post_id, $field, true);
            if (!empty($value)) {
                $meta_fields[$field] = $value;
            }
        }

        return $meta_fields;
    }

    /**
     * Check if content should be skipped
     *
     * @param array $item Content item
     * @param array $options Processing options
     * @return bool Should skip
     */
    private static function should_skip_content($item, $options) {
        // Skip if already processed recently
        $last_processed = get_post_meta($item['id'], '_aicrp_processed', true);
        if ($last_processed && strtotime($last_processed) > (time() - DAY_IN_SECONDS)) {
            return true;
        }

        // Skip if content is too short
        if ($item['word_count'] < 10) {
            return true;
        }

        // Skip if content is too long (for performance)
        if ($item['word_count'] > 2000 && !isset($options['force_long_content'])) {
            return true;
        }

        // Skip password protected posts
        if (post_password_required($item['id'])) {
            return true;
        }

        return false;
    }

    /**
     * Check if post title should be processed
     *
     * @param array $item Content item
     * @param array $options Processing options
     * @return bool Should process title
     */
    private static function should_process_title($item, $options) {
        // Don't process if title is already customized (not generic)
        $generic_titles = array(
            'Hello world!',
            'Sample Page',
            'About',
            'Contact',
            'Home'
        );

        return !in_array($item['title'], $generic_titles);
    }

    /**
     * Create content backup
     *
     * @param int $post_id Post ID
     * @param array $item Content item
     */
    private static function create_content_backup($post_id, $item) {
        $backup_data = array(
            'title' => $item['title'],
            'content' => $item['content'],
            'excerpt' => $item['excerpt'],
            'meta_fields' => $item['meta_fields'],
            'timestamp' => current_time('mysql')
        );

        update_post_meta($post_id, '_aicrp_content_backup', $backup_data);
    }

    /**
     * Preserve page builder structure
     *
     * @param string $new_content New content
     * @param string $original_content Original content
     * @param string $page_builder Page builder type
     * @return string Processed content
     */
    private static function preserve_page_builder_structure($new_content, $original_content, $page_builder) {
        // This is a simplified version - in production, you'd need specific parsers for each page builder
        
        switch ($page_builder) {
            case 'elementor':
                // Preserve Elementor JSON structure
                return $original_content; // For now, skip Elementor processing
                
            case 'gutenberg':
                // Preserve Gutenberg blocks
                if (has_blocks($original_content)) {
                    return $original_content; // For now, skip block processing
                }
                break;
                
            case 'visual_composer':
                // Preserve VC shortcodes
                if (preg_match('/\[vc_/', $original_content)) {
                    return $original_content; // For now, skip VC processing
                }
                break;
        }

        return $new_content;
    }

    /**
     * Restore content from backup
     *
     * @param int $post_id Post ID
     * @return bool Success status
     */
    public static function restore_content_backup($post_id) {
        $backup_data = get_post_meta($post_id, '_aicrp_content_backup', true);
        
        if (empty($backup_data)) {
            return false;
        }

        // Restore post content
        $result = wp_update_post(array(
            'ID' => $post_id,
            'post_title' => $backup_data['title'],
            'post_content' => $backup_data['content'],
            'post_excerpt' => $backup_data['excerpt']
        ));

        if (is_wp_error($result)) {
            return false;
        }

        // Restore meta fields
        if (!empty($backup_data['meta_fields'])) {
            foreach ($backup_data['meta_fields'] as $meta_key => $meta_value) {
                update_post_meta($post_id, $meta_key, $meta_value);
            }
        }

        // Remove processing markers
        delete_post_meta($post_id, '_aicrp_processed');
        delete_post_meta($post_id, '_aicrp_changes_made');

        return true;
    }

    /**
     * Get processing statistics
     *
     * @param int $days Number of days to analyze
     * @return array Statistics
     */
    public static function get_processing_statistics($days = 30) {
        global $wpdb;

        $history_table = $wpdb->prefix . 'aicrp_processing_history';
        
        $stats = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT 
                    COUNT(*) as total_processed,
                    SUM(tokens_used) as total_tokens,
                    SUM(cost) as total_cost,
                    AVG(duration_seconds) as avg_duration,
                    SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as successful_count
                FROM $history_table 
                WHERE created_at >= DATE_SUB(NOW(), INTERVAL %d DAY)
                AND user_id = %d",
                $days,
                get_current_user_id()
            ),
            ARRAY_A
        );

        if (empty($stats)) {
            return array(
                'total_processed' => 0,
                'total_tokens' => 0,
                'total_cost' => 0,
                'avg_duration' => 0,
                'success_rate' => 0
            );
        }

        $stats['success_rate'] = $stats['total_processed'] > 0 ? 
            round(($stats['successful_count'] / $stats['total_processed']) * 100, 2) : 0;

        return $stats;
    }
}