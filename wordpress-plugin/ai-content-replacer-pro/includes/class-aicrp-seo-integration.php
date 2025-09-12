<?php
/**
 * SEO Integration Class
 * Handles integration with popular WordPress SEO plugins
 *
 * @package AI_Content_Replacer_Pro
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class AICRP_SEO_Integration {
    
    /**
     * Supported SEO plugins
     */
    private static $supported_seo_plugins = array(
        'yoast-seo' => 'Yoast SEO',
        'rankmath' => 'Rank Math',
        'all-in-one-seo' => 'All in One SEO',
        'seo-framework' => 'The SEO Framework'
    );

    /**
     * Detect active SEO plugins
     *
     * @return array Active SEO plugins info
     */
    public static function detect_active_seo_plugins() {
        $active_plugins = array();
        $compatibility = array();
        
        // Check Yoast SEO
        if (is_plugin_active('wordpress-seo/wp-seo.php')) {
            $active_plugins[] = 'yoast-seo';
            $compatibility['yoast-seo'] = true;
        }
        
        // Check Rank Math
        if (is_plugin_active('seo-by-rank-math/rank-math.php')) {
            $active_plugins[] = 'rankmath';
            $compatibility['rankmath'] = true;
        }
        
        // Check All in One SEO
        if (is_plugin_active('all-in-one-seo-pack/all_in_one_seo_pack.php')) {
            $active_plugins[] = 'all-in-one-seo';
            $compatibility['all-in-one-seo'] = true;
        }
        
        // Check The SEO Framework
        if (is_plugin_active('autodescription/autodescription.php')) {
            $active_plugins[] = 'seo-framework';
            $compatibility['seo-framework'] = true;
        }

        return array(
            'detected' => $active_plugins,
            'supported' => array_keys(self::$supported_seo_plugins),
            'compatibility' => $compatibility
        );
    }

    /**
     * Extract SEO metadata for a post
     *
     * @param int $post_id Post ID
     * @return array SEO metadata
     */
    public static function extract_seo_data($post_id) {
        $seo_data = array(
            'title' => '',
            'description' => '',
            'keywords' => array(),
            'focus_keyword' => ''
        );

        // Yoast SEO
        if (is_plugin_active('wordpress-seo/wp-seo.php')) {
            $seo_data['title'] = get_post_meta($post_id, '_yoast_wpseo_title', true);
            $seo_data['description'] = get_post_meta($post_id, '_yoast_wpseo_metadesc', true);
            $seo_data['focus_keyword'] = get_post_meta($post_id, '_yoast_wpseo_focuskw', true);
        }
        
        // Rank Math
        if (is_plugin_active('seo-by-rank-math/rank-math.php')) {
            if (empty($seo_data['title'])) {
                $seo_data['title'] = get_post_meta($post_id, 'rank_math_title', true);
            }
            if (empty($seo_data['description'])) {
                $seo_data['description'] = get_post_meta($post_id, 'rank_math_description', true);
            }
            if (empty($seo_data['focus_keyword'])) {
                $seo_data['focus_keyword'] = get_post_meta($post_id, 'rank_math_focus_keyword', true);
            }
        }
        
        // All in One SEO
        if (is_plugin_active('all-in-one-seo-pack/all_in_one_seo_pack.php')) {
            if (empty($seo_data['title'])) {
                $seo_data['title'] = get_post_meta($post_id, '_aioseo_title', true);
            }
            if (empty($seo_data['description'])) {
                $seo_data['description'] = get_post_meta($post_id, '_aioseo_description', true);
            }
        }

        return $seo_data;
    }

    /**
     * Update SEO metadata for a post
     *
     * @param int $post_id Post ID
     * @param array $seo_data SEO data
     * @return bool Success status
     */
    public static function update_seo_data($post_id, $seo_data) {
        $updated = false;

        // Yoast SEO
        if (is_plugin_active('wordpress-seo/wp-seo.php')) {
            if (!empty($seo_data['title'])) {
                update_post_meta($post_id, '_yoast_wpseo_title', sanitize_text_field($seo_data['title']));
                $updated = true;
            }
            if (!empty($seo_data['description'])) {
                update_post_meta($post_id, '_yoast_wpseo_metadesc', sanitize_textarea_field($seo_data['description']));
                $updated = true;
            }
            if (!empty($seo_data['focus_keyword'])) {
                update_post_meta($post_id, '_yoast_wpseo_focuskw', sanitize_text_field($seo_data['focus_keyword']));
                $updated = true;
            }
        }
        
        // Rank Math
        if (is_plugin_active('seo-by-rank-math/rank-math.php')) {
            if (!empty($seo_data['title'])) {
                update_post_meta($post_id, 'rank_math_title', sanitize_text_field($seo_data['title']));
                $updated = true;
            }
            if (!empty($seo_data['description'])) {
                update_post_meta($post_id, 'rank_math_description', sanitize_textarea_field($seo_data['description']));
                $updated = true;
            }
            if (!empty($seo_data['focus_keyword'])) {
                update_post_meta($post_id, 'rank_math_focus_keyword', sanitize_text_field($seo_data['focus_keyword']));
                $updated = true;
            }
        }
        
        // All in One SEO
        if (is_plugin_active('all-in-one-seo-pack/all_in_one_seo_pack.php')) {
            if (!empty($seo_data['title'])) {
                update_post_meta($post_id, '_aioseo_title', sanitize_text_field($seo_data['title']));
                $updated = true;
            }
            if (!empty($seo_data['description'])) {
                update_post_meta($post_id, '_aioseo_description', sanitize_textarea_field($seo_data['description']));
                $updated = true;
            }
        }

        return $updated;
    }

    /**
     * Generate SEO-optimized content
     *
     * @param string $content Original content
     * @param string $focus_keyword Focus keyword
     * @param array $business_profile Business profile
     * @return array Optimization results
     */
    public static function optimize_content_for_seo($content, $focus_keyword, $business_profile) {
        $optimized_content = $content;
        $recommendations = array();
        $seo_score = 100;

        // Keyword density check
        $keyword_count = substr_count(strtolower($content), strtolower($focus_keyword));
        $word_count = str_word_count($content);
        $keyword_density = $word_count > 0 ? ($keyword_count / $word_count) * 100 : 0;

        if ($keyword_density < 0.5) {
            $recommendations[] = __('Consider adding the focus keyword more frequently (target: 0.5-2.5%)', 'ai-content-replacer-pro');
            $seo_score -= 10;
        } elseif ($keyword_density > 3) {
            $recommendations[] = __('Reduce keyword density to avoid over-optimization (target: 0.5-2.5%)', 'ai-content-replacer-pro');
            $seo_score -= 15;
        }

        // Content length check
        if ($word_count < 300) {
            $recommendations[] = __('Content is quite short. Consider adding more detailed information.', 'ai-content-replacer-pro');
            $seo_score -= 20;
        }

        // Business-specific optimization
        if (!empty($business_profile)) {
            // Add business location for local SEO
            if (!empty($business_profile['location']) && 
                stripos($content, $business_profile['location']) === false) {
                $optimized_content .= sprintf(__(' Located in %s.', 'ai-content-replacer-pro'), $business_profile['location']);
                $recommendations[] = __('Added location information for local SEO', 'ai-content-replacer-pro');
            }

            // Check for business keywords
            if (!empty($business_profile['keywords']) && is_array($business_profile['keywords'])) {
                $missing_keywords = array();
                
                foreach ($business_profile['keywords'] as $keyword) {
                    if (stripos($content, $keyword) === false) {
                        $missing_keywords[] = $keyword;
                    }
                }
                
                if (!empty($missing_keywords)) {
                    $top_missing = array_slice($missing_keywords, 0, 3);
                    $recommendations[] = sprintf(
                        __('Consider incorporating these business keywords: %s', 'ai-content-replacer-pro'),
                        implode(', ', $top_missing)
                    );
                }
            }
        }

        return array(
            'optimized_content' => $optimized_content,
            'seo_score' => max(0, $seo_score),
            'recommendations' => $recommendations,
            'keyword_density' => round($keyword_density, 2),
            'word_count' => $word_count
        );
    }

    /**
     * Generate SEO meta title
     *
     * @param string $original_title Original title
     * @param array $business_profile Business profile
     * @param string $focus_keyword Focus keyword
     * @return string Optimized title
     */
    public static function generate_seo_title($original_title, $business_profile, $focus_keyword = '') {
        $business_name = $business_profile['business_name'] ?? '';
        $location = $business_profile['location'] ?? '';
        
        // If title already contains business name, keep it
        if (!empty($business_name) && stripos($original_title, $business_name) !== false) {
            return $original_title;
        }
        
        // Generate new title with business context
        $new_title = $original_title;
        
        if (!empty($focus_keyword) && stripos($original_title, $focus_keyword) === false) {
            $new_title = $focus_keyword . ' - ' . $original_title;
        }
        
        if (!empty($business_name)) {
            $new_title .= ' | ' . $business_name;
        }
        
        if (!empty($location)) {
            $new_title .= ' - ' . $location;
        }
        
        // Ensure title is under 60 characters for SEO
        if (strlen($new_title) > 60) {
            $new_title = substr($new_title, 0, 57) . '...';
        }
        
        return $new_title;
    }

    /**
     * Generate SEO meta description
     *
     * @param string $content Post content
     * @param array $business_profile Business profile
     * @param string $focus_keyword Focus keyword
     * @return string Meta description
     */
    public static function generate_seo_description($content, $business_profile, $focus_keyword = '') {
        // Extract first paragraph or 160 characters
        $content = wp_strip_all_tags($content);
        $content = preg_replace('/\s+/', ' ', $content);
        
        $description = '';
        
        // Try to get first sentence
        $sentences = preg_split('/[.!?]+/', $content);
        if (!empty($sentences[0])) {
            $description = trim($sentences[0]);
        }
        
        // If too short, add more content
        if (strlen($description) < 120 && !empty($sentences[1])) {
            $description .= '. ' . trim($sentences[1]);
        }
        
        // Add business context
        if (!empty($business_profile['business_name'])) {
            $business_name = $business_profile['business_name'];
            if (stripos($description, $business_name) === false) {
                $description = $business_name . ' - ' . $description;
            }
        }
        
        // Add focus keyword if not present
        if (!empty($focus_keyword) && stripos($description, $focus_keyword) === false) {
            $description = $focus_keyword . ': ' . $description;
        }
        
        // Ensure description is under 160 characters
        if (strlen($description) > 160) {
            $description = substr($description, 0, 157) . '...';
        }
        
        return $description;
    }

    /**
     * Analyze content readability
     *
     * @param string $content Content to analyze
     * @return array Readability analysis
     */
    public static function analyze_readability($content) {
        $content = wp_strip_all_tags($content);
        $sentences = preg_split('/[.!?]+/', $content);
        $sentences = array_filter($sentences, function($sentence) {
            return !empty(trim($sentence));
        });
        
        $words = str_word_count($content);
        $sentence_count = count($sentences);
        
        // Calculate average sentence length
        $avg_sentence_length = $sentence_count > 0 ? $words / $sentence_count : 0;
        
        // Simple readability scoring
        $readability_score = 100;
        
        if ($avg_sentence_length > 20) {
            $readability_score -= 10;
        }
        
        if ($avg_sentence_length > 30) {
            $readability_score -= 20;
        }
        
        // Check for complex words (>3 syllables - simplified check)
        $complex_words = 0;
        $word_array = explode(' ', $content);
        
        foreach ($word_array as $word) {
            if (strlen($word) > 8) { // Rough approximation
                $complex_words++;
            }
        }
        
        $complex_word_percentage = $words > 0 ? ($complex_words / $words) * 100 : 0;
        
        if ($complex_word_percentage > 10) {
            $readability_score -= 15;
        }

        $reading_level = 'Unknown';
        if ($readability_score >= 90) {
            $reading_level = 'Very Easy';
        } elseif ($readability_score >= 80) {
            $reading_level = 'Easy';
        } elseif ($readability_score >= 70) {
            $reading_level = 'Fairly Easy';
        } elseif ($readability_score >= 60) {
            $reading_level = 'Standard';
        } elseif ($readability_score >= 50) {
            $reading_level = 'Fairly Difficult';
        } else {
            $reading_level = 'Difficult';
        }

        return array(
            'readability_score' => max(0, $readability_score),
            'reading_level' => $reading_level,
            'word_count' => $words,
            'sentence_count' => $sentence_count,
            'avg_sentence_length' => round($avg_sentence_length, 1),
            'complex_word_percentage' => round($complex_word_percentage, 1)
        );
    }

    /**
     * Generate schema markup for business
     *
     * @param array $business_profile Business profile
     * @return string Schema JSON-LD markup
     */
    public static function generate_business_schema($business_profile) {
        if (empty($business_profile)) {
            return '';
        }

        $schema = array(
            '@context' => 'https://schema.org',
            '@type' => 'Organization',
            'name' => $business_profile['business_name'] ?? '',
            'description' => $business_profile['description'] ?? ''
        );

        // Add contact information
        if (!empty($business_profile['phone']) || !empty($business_profile['email'])) {
            $contact_point = array(
                '@type' => 'ContactPoint',
                'contactType' => 'customer service'
            );
            
            if (!empty($business_profile['phone'])) {
                $contact_point['telephone'] = $business_profile['phone'];
            }
            
            if (!empty($business_profile['email'])) {
                $contact_point['email'] = $business_profile['email'];
            }
            
            $schema['contactPoint'] = $contact_point;
        }

        // Add address if location is provided
        if (!empty($business_profile['location'])) {
            $schema['address'] = array(
                '@type' => 'PostalAddress',
                'addressLocality' => $business_profile['location']
            );
        }

        // Add website URL
        if (!empty($business_profile['website'])) {
            $schema['url'] = $business_profile['website'];
        }

        return '<script type="application/ld+json">' . wp_json_encode($schema, JSON_UNESCAPED_SLASHES) . '</script>';
    }

    /**
     * Optimize content for local SEO
     *
     * @param string $content Original content
     * @param array $business_profile Business profile
     * @return array Optimization results
     */
    public static function optimize_for_local_seo($content, $business_profile) {
        $optimized_content = $content;
        $changes_made = array();

        if (empty($business_profile['location'])) {
            return array(
                'content' => $content,
                'changes_made' => array(),
                'recommendations' => array(__('Add business location for local SEO benefits', 'ai-content-replacer-pro'))
            );
        }

        $location = $business_profile['location'];
        $business_name = $business_profile['business_name'] ?? '';

        // Add location context if missing
        if (stripos($content, $location) === false) {
             $location_phrases = array(
                sprintf(__('Located in %s', 'ai-content-replacer-pro'), $location),
                sprintf(__('Serving %s area', 'ai-content-replacer-pro'), $location),
                sprintf(__('Based in %s', 'ai-content-replacer-pro'), $location)
            );
            
            $random_phrase = $location_phrases[array_rand($location_phrases)];
            $optimized_content .= ' ' . $random_phrase . '.';
            $changes_made[] = 'location_context';
        }

        // Add local business keywords
        $local_keywords = array(
            $location . ' ' . ($business_profile['business_type'] ?? ''),
            ($business_profile['business_type'] ?? '') . ' in ' . $location,
            'local ' . ($business_profile['business_type'] ?? '')
        );

        $recommendations = array();
        foreach ($local_keywords as $keyword) {
            if (!empty($keyword) && stripos($content, $keyword) === false) {
                $recommendations[] = sprintf(__('Consider adding local keyword: "%s"', 'ai-content-replacer-pro'), $keyword);
            }
        }

        return array(
            'content' => $optimized_content,
            'changes_made' => $changes_made,
            'recommendations' => $recommendations
        );
    }

    /**
     * Check SEO plugin compatibility
     *
     * @return array Compatibility status
     */
    public static function check_seo_compatibility() {
        $active_seo = self::detect_active_seo_plugins();
        $issues = array();
        $recommendations = array();

        if (empty($active_seo['detected'])) {
            $recommendations[] = __('Consider installing an SEO plugin like Yoast SEO or Rank Math for better optimization', 'ai-content-replacer-pro');
        }

        // Check for conflicts (multiple SEO plugins active)
        if (count($active_seo['detected']) > 1) {
            $issues[] = __('Multiple SEO plugins detected. This may cause conflicts.', 'ai-content-replacer-pro');
            $recommendations[] = __('Deactivate unnecessary SEO plugins to avoid conflicts', 'ai-content-replacer-pro');
        }

        return array(
            'compatible' => empty($issues),
            'active_plugins' => $active_seo['detected'],
            'issues' => $issues,
            'recommendations' => $recommendations
        );
    }

    /**
     * Get supported SEO plugins
     *
     * @return array Supported SEO plugins
     */
    public static function get_supported_seo_plugins() {
        return self::$supported_seo_plugins;
    }
}