<?php
/**
 * Page Builder Integration Class
 * Handles integration with popular WordPress page builders
 *
 * @package AI_Content_Replacer_Pro
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class AICRP_Page_Builder_Integration {
    
    /**
     * Supported page builders
     */
    private static $supported_builders = array(
        'elementor' => 'Elementor',
        'gutenberg' => 'Gutenberg',
        'beaver_builder' => 'Beaver Builder',
        'divi' => 'Divi Builder',
        'visual_composer' => 'WPBakery Page Builder',
        'oxygen' => 'Oxygen',
        'bricks' => 'Bricks',
        'cornerstone' => 'Cornerstone'
    );

    /**
     * Detect active page builders
     *
     * @return array Active page builders
     */
    public static function detect_active_builders() {
        $active_builders = array();
        
        // Check Elementor
        if (is_plugin_active('elementor/elementor.php')) {
            $active_builders[] = 'elementor';
        }
        
        // Check Beaver Builder
        if (is_plugin_active('bb-plugin/fl-builder.php')) {
            $active_builders[] = 'beaver_builder';
        }
        
        // Check Divi
        if (function_exists('et_setup_theme')) {
            $active_builders[] = 'divi';
        }
        
        // Check WPBakery
        if (is_plugin_active('js_composer/js_composer.php')) {
            $active_builders[] = 'visual_composer';
        }
        
        // Check Oxygen
        if (is_plugin_active('oxygen/functions.php')) {
            $active_builders[] = 'oxygen';
        }
        
        // Check Bricks
        if (is_plugin_active('bricks/bricks.php')) {
            $active_builders[] = 'bricks';
        }
        
        // Gutenberg is always available in WordPress 5.0+
        if (function_exists('has_blocks')) {
            $active_builders[] = 'gutenberg';
        }

        return array(
            'detected' => $active_builders,
            'supported' => array_keys(self::$supported_builders),
            'compatibility' => self::check_builder_compatibility($active_builders)
        );
    }

    /**
     * Check builder compatibility
     *
     * @param array $active_builders Active builders
     * @return array Compatibility status
     */
    private static function check_builder_compatibility($active_builders) {
        $compatibility = array();
        
        foreach ($active_builders as $builder) {
            $compatibility[$builder] = isset(self::$supported_builders[$builder]);
        }
        
        return $compatibility;
    }

    /**
     * Detect page builder for specific post
     *
     * @param int $post_id Post ID
     * @return string|false Page builder name or false
     */
    public static function detect_post_page_builder($post_id) {
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

        // Check for Oxygen
        if (get_post_meta($post_id, 'ct_builder_shortcodes', true)) {
            return 'oxygen';
        }

        // Check for Bricks
        if (get_post_meta($post_id, '_bricks_editor_mode', true)) {
            return 'bricks';
        }

        // Check for Gutenberg blocks
        if (has_blocks($post_id)) {
            return 'gutenberg';
        }

        return false;
    }

    /**
     * Parse page builder content
     *
     * @param string $content Content to parse
     * @param string $builder_type Builder type
     * @return array Parsed content data
     */
    public static function parse_builder_content($content, $builder_type) {
        switch ($builder_type) {
            case 'elementor':
                return self::parse_elementor_content($content);
            case 'gutenberg':
                return self::parse_gutenberg_content($content);
            case 'beaver_builder':
                return self::parse_beaver_builder_content($content);
            case 'divi':
                return self::parse_divi_content($content);
            case 'visual_composer':
                return self::parse_visual_composer_content($content);
            default:
                return self::parse_generic_content($content);
        }
    }

    /**
     * Parse Elementor content
     *
     * @param string $content Elementor content
     * @return array Parsed content
     */
    private static function parse_elementor_content($content) {
        $text_content = array();
        $preserved_structure = array();
        $replacement_map = array();
        
        try {
            $elementor_data = json_decode($content, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                self::extract_elementor_text($elementor_data, $text_content, $replacement_map);
                $preserved_structure = $elementor_data;
            }
        } catch (Exception $e) {
            // Fallback to generic parsing
            return self::parse_generic_content($content);
        }
        
        return array(
            'text_content' => $text_content,
            'preserved_structure' => $preserved_structure,
            'replacement_map' => $replacement_map
        );
    }

    /**
     * Extract text from Elementor JSON structure
     *
     * @param array $data Elementor data
     * @param array $text_content Text content array (by reference)
     * @param array $replacement_map Replacement map (by reference)
     */
    private static function extract_elementor_text($data, &$text_content, &$replacement_map) {
        if (is_array($data)) {
            foreach ($data as $key => &$item) {
                if (is_array($item)) {
                    self::extract_elementor_text($item, $text_content, $replacement_map);
                } elseif (is_string($item) && in_array($key, array('title', 'content', 'text', 'description', 'caption'))) {
                    if (!empty($item) && strlen($item) > 5) {
                        $placeholder = '__PLACEHOLDER_' . count($text_content) . '__';
                        $text_content[] = $item;
                        $replacement_map[$placeholder] = $item;
                        $item = $placeholder;
                    }
                }
            }
        }
    }

    /**
     * Parse Gutenberg content
     *
     * @param string $content Gutenberg content
     * @return array Parsed content
     */
    private static function parse_gutenberg_content($content) {
        $text_content = array();
        $replacement_map = array();
        $preserved_content = $content;
        
        // Extract text from Gutenberg blocks
        $block_pattern = '/<!-- wp:([a-z]+\/)?([a-z-]+)(\s+({.*?}))? -->(.*?)<!-- \/wp:\1?\2 -->/';
        
        preg_match_all($block_pattern, $content, $matches, PREG_SET_ORDER);
        
        foreach ($matches as $match) {
            $block_content = isset($match[5]) ? $match[5] : '';
            if (!empty($block_content)) {
                $text_only = strip_tags($block_content);
                $text_only = trim($text_only);
                
                if (!empty($text_only) && strlen($text_only) > 5) {
                    $placeholder = '__PLACEHOLDER_' . count($text_content) . '__';
                    $text_content[] = $text_only;
                    $replacement_map[$placeholder] = $text_only;
                    $preserved_content = str_replace($text_only, $placeholder, $preserved_content);
                }
            }
        }
        
        return array(
            'text_content' => $text_content,
            'preserved_structure' => array(
                'original_content' => $content,
                'processed_content' => $preserved_content
            ),
            'replacement_map' => $replacement_map
        );
    }

    /**
     * Parse Beaver Builder content
     *
     * @param string $content Beaver Builder content
     * @return array Parsed content
     */
    private static function parse_beaver_builder_content($content) {
        // Beaver Builder uses serialized PHP data
        $text_content = array();
        $replacement_map = array();
        
        try {
            $builder_data = maybe_unserialize($content);
            if (is_array($builder_data)) {
                self::extract_beaver_builder_text($builder_data, $text_content, $replacement_map);
            }
        } catch (Exception $e) {
            return self::parse_generic_content($content);
        }
        
        return array(
            'text_content' => $text_content,
            'preserved_structure' => $builder_data,
            'replacement_map' => $replacement_map
        );
    }

    /**
     * Extract text from Beaver Builder structure
     *
     * @param array $data Builder data
     * @param array $text_content Text content (by reference)
     * @param array $replacement_map Replacement map (by reference)
     */
    private static function extract_beaver_builder_text($data, &$text_content, &$replacement_map) {
        if (is_array($data)) {
            foreach ($data as $key => &$item) {
                if (is_array($item)) {
                    self::extract_beaver_builder_text($item, $text_content, $replacement_map);
                } elseif (is_string($item) && in_array($key, array('text', 'title', 'content', 'heading'))) {
                    if (!empty($item) && strlen($item) > 5) {
                        $placeholder = '__PLACEHOLDER_' . count($text_content) . '__';
                        $text_content[] = $item;
                        $replacement_map[$placeholder] = $item;
                        $item = $placeholder;
                    }
                }
            }
        }
    }

    /**
     * Parse Divi content
     *
     * @param string $content Divi content
     * @return array Parsed content
     */
    private static function parse_divi_content($content) {
        $text_content = array();
        $replacement_map = array();
        
        // Extract text from Divi shortcodes
        $shortcode_pattern = '/\[et_pb_[^\]]+\](.*?)\[\/et_pb_[^\]]+\]/s';
        
        preg_match_all($shortcode_pattern, $content, $matches);
        
        foreach ($matches[1] as $match) {
            $text_only = strip_tags($match);
            $text_only = trim($text_only);
            
            if (!empty($text_only) && strlen($text_only) > 5) {
                $placeholder = '__PLACEHOLDER_' . count($text_content) . '__';
                $text_content[] = $text_only;
                $replacement_map[$placeholder] = $text_only;
            }
        }
        
        return array(
            'text_content' => $text_content,
            'preserved_structure' => array('original_content' => $content),
            'replacement_map' => $replacement_map
        );
    }

    /**
     * Parse Visual Composer content
     *
     * @param string $content VC content
     * @return array Parsed content
     */
    private static function parse_visual_composer_content($content) {
        $text_content = array();
        $replacement_map = array();
        
        // Extract text from VC shortcodes
        $shortcode_pattern = '/\[vc_[^\]]+\](.*?)\[\/vc_[^\]]+\]/s';
        
        preg_match_all($shortcode_pattern, $content, $matches);
        
        foreach ($matches[1] as $match) {
            $text_only = strip_tags($match);
            $text_only = trim($text_only);
            
            if (!empty($text_only) && strlen($text_only) > 5) {
                $placeholder = '__PLACEHOLDER_' . count($text_content) . '__';
                $text_content[] = $text_only;
                $replacement_map[$placeholder] = $text_only;
            }
        }
        
        return array(
            'text_content' => $text_content,
            'preserved_structure' => array('original_content' => $content),
            'replacement_map' => $replacement_map
        );
    }

    /**
     * Parse generic content
     *
     * @param string $content Generic content
     * @return array Parsed content
     */
    private static function parse_generic_content($content) {
        $text_content = array();
        $replacement_map = array();
        
        // Extract text nodes from HTML
        $dom = new DOMDocument();
        
        // Suppress errors for malformed HTML
        libxml_use_internal_errors(true);
        
        if ($dom->loadHTML('<?xml encoding="UTF-8">' . $content)) {
            $xpath = new DOMXPath($dom);
            $text_nodes = $xpath->query('//text()[normalize-space()]');
            
            foreach ($text_nodes as $node) {
                $text = trim($node->nodeValue);
                if (!empty($text) && strlen($text) > 5) {
                    $placeholder = '__PLACEHOLDER_' . count($text_content) . '__';
                    $text_content[] = $text;
                    $replacement_map[$placeholder] = $text;
                }
            }
        }
        
        // Restore error handling
        libxml_clear_errors();
        libxml_use_internal_errors(false);
        
        return array(
            'text_content' => $text_content,
            'preserved_structure' => array('original_content' => $content),
            'replacement_map' => $replacement_map
        );
    }

    /**
     * Rebuild content with replaced text
     *
     * @param array $preserved_structure Preserved structure
     * @param array $replacement_map Replacement map
     * @param array $new_text_content New text content
     * @param string $builder_type Builder type
     * @return string Rebuilt content
     */
    public static function rebuild_content($preserved_structure, $replacement_map, $new_text_content, $builder_type) {
        switch ($builder_type) {
            case 'elementor':
                return self::rebuild_elementor_content($preserved_structure, $replacement_map, $new_text_content);
            case 'gutenberg':
                return self::rebuild_gutenberg_content($preserved_structure, $replacement_map, $new_text_content);
            case 'beaver_builder':
                return self::rebuild_beaver_builder_content($preserved_structure, $replacement_map, $new_text_content);
            default:
                return self::rebuild_generic_content($preserved_structure, $replacement_map, $new_text_content);
        }
    }

    /**
     * Rebuild Elementor content
     *
     * @param array $structure Preserved structure
     * @param array $replacement_map Replacement map
     * @param array $new_text_content New text content
     * @return string Rebuilt content
     */
    private static function rebuild_elementor_content($structure, $replacement_map, $new_text_content) {
        $index = 0;
        self::replace_elementor_placeholders($structure, $new_text_content, $index);
        return wp_json_encode($structure);
    }

    /**
     * Replace placeholders in Elementor structure
     *
     * @param array $data Data structure (by reference)
     * @param array $new_text_content New text content
     * @param int $index Current index (by reference)
     */
    private static function replace_elementor_placeholders(&$data, $new_text_content, &$index) {
        if (is_array($data)) {
            foreach ($data as $key => &$item) {
                if (is_array($item)) {
                    self::replace_elementor_placeholders($item, $new_text_content, $index);
                } elseif (is_string($item) && strpos($item, '__PLACEHOLDER_') === 0) {
                    if ($index < count($new_text_content)) {
                        $item = $new_text_content[$index];
                        $index++;
                    }
                }
            }
        }
    }

    /**
     * Rebuild Gutenberg content
     *
     * @param array $structure Preserved structure
     * @param array $replacement_map Replacement map
     * @param array $new_text_content New text content
     * @return string Rebuilt content
     */
    private static function rebuild_gutenberg_content($structure, $replacement_map, $new_text_content) {
        $rebuilt_content = $structure['processed_content'] ?? $structure['original_content'];
        
        $index = 0;
        foreach ($replacement_map as $placeholder => $original_text) {
            if ($index < count($new_text_content)) {
                $rebuilt_content = str_replace($placeholder, $new_text_content[$index], $rebuilt_content);
                $index++;
            }
        }
        
        return $rebuilt_content;
    }

    /**
     * Rebuild Beaver Builder content
     *
     * @param array $structure Preserved structure
     * @param array $replacement_map Replacement map
     * @param array $new_text_content New text content
     * @return string Rebuilt content
     */
    private static function rebuild_beaver_builder_content($structure, $replacement_map, $new_text_content) {
        $index = 0;
        self::replace_elementor_placeholders($structure, $new_text_content, $index);
        return maybe_serialize($structure);
    }

    /**
     * Rebuild generic content
     *
     * @param array $structure Preserved structure
     * @param array $replacement_map Replacement map
     * @param array $new_text_content New text content
     * @return string Rebuilt content
     */
    private static function rebuild_generic_content($structure, $replacement_map, $new_text_content) {
        $rebuilt_content = $structure['original_content'];
        
        $index = 0;
        foreach ($replacement_map as $placeholder => $original_text) {
            if ($index < count($new_text_content)) {
                $rebuilt_content = str_replace($original_text, $new_text_content[$index], $rebuilt_content);
                $index++;
            }
        }
        
        return $rebuilt_content;
    }

    /**
     * Check if post uses page builder
     *
     * @param int $post_id Post ID
     * @return bool Uses page builder
     */
    public static function post_uses_page_builder($post_id) {
        return self::detect_post_page_builder($post_id) !== false;
    }

    /**
     * Get supported page builders list
     *
     * @return array Supported builders
     */
    public static function get_supported_builders() {
        return self::$supported_builders;
    }
}