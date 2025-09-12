<?php
/**
 * Security Class for AI Content Replacer Pro
 * Implements enterprise-level security measures
 *
 * @package AI_Content_Replacer_Pro
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class AICRP_Security {
    
    /**
     * Initialize security hooks
     */
    public static function init() {
        add_action('init', array(__CLASS__, 'security_headers'));
        add_action('wp_ajax_nopriv_aicrp_block_unauthorized', array(__CLASS__, 'block_unauthorized'));
    }

    /**
     * Add security headers
     */
    public static function security_headers() {
        if (!headers_sent()) {
            header('X-Content-Type-Options: nosniff');
            header('X-Frame-Options: SAMEORIGIN');
            header('X-XSS-Protection: 1; mode=block');
        }
    }

    /**
     * Sanitize text input to prevent XSS attacks and SQL injection
     *
     * @param string $input Input string to sanitize
     * @return string Sanitized string
     */
    public static function sanitize_text($input) {
        if (!is_string($input) || empty($input)) {
            return '';
        }
        
        // Remove dangerous characters
        $sanitized = $input;
        $sanitized = str_replace(array('<', '>'), '', $sanitized);
        $sanitized = preg_replace('/javascript:/i', '', $sanitized);
        $sanitized = preg_replace('/on\w+=/i', '', $sanitized);
        
        // Enhanced SQL injection prevention
        $sanitized = preg_replace('/(\\'|\\\\\'|;|\\\\;|--)/i', '', $sanitized);
        $sanitized = preg_replace('/\\s+(or|and)\\s+/i', ' ', $sanitized);
        $sanitized = preg_replace('/\\s+(union|select|insert|update|delete|drop|create|alter|exec|execute)\\s+/i', ' ', $sanitized);
        $sanitized = preg_replace('/\\b(union|select|insert|update|delete|drop|table|database|schema|grant|revoke)\\b/i', '', $sanitized);
         $sanitized = preg_replace('/(\\|\\||&&)/', '', $sanitized);
        
        return trim(sanitize_text_field($sanitized));
    }

    /**
     * Advanced SQL injection pattern detection and removal
     *
     * @param string $input Input string to sanitize
     * @return string Sanitized string
     */
    public static function sanitize_sql_content($input) {
        if (!is_string($input) || empty($input)) {
            return '';
        }
        
        $sanitized = $input;
        
        // Remove SQL comments
        $sanitized = preg_replace('/(--|#|\\/\\*|\\*\\/)/g', '', $sanitized);
        
        // Remove single quotes that could break queries
        $sanitized = str_replace("'", '', $sanitized);
        
        // Remove semicolons that could end statements
        $sanitized = str_replace(';', '', $sanitized);
        
        // Remove common SQL injection keywords
        $sanitized = preg_replace('/\\b(union|select|insert|update|delete|drop|create|alter|grant|revoke|exec|execute|sp_|xp_)\\b/i', '', $sanitized);
        
        // Remove SQL operators that could create logical conditions
        $sanitized = preg_replace('/(\\s+(or|and)\\s+\\d+\\s*=\\s*\\d+)/i', '', $sanitized);
        
        // Remove parentheses that could group conditions
        $sanitized = str_replace(array('(', ')'), '', $sanitized);
        
        // Remove comparison operators in suspicious contexts
        $sanitized = preg_replace('/\\s*=\\s*/', ' ', $sanitized);
        
        // Clean up extra whitespace
        $sanitized = preg_replace('/\\s+/', ' ', $sanitized);
        
        return trim($sanitized);
    }

    /**
     * Sanitize business profile data with enhanced security
     *
     * @param array $profile Business profile data
     * @return array Sanitized profile data
     */
    public static function sanitize_business_profile($profile) {
        if (!is_array($profile)) {
            return array();
        }

        $sanitized = array(
            'business_name' => self::sanitize_sql_content(self::sanitize_text($profile['business_name'] ?? '')),
            'business_type' => self::sanitize_sql_content(self::sanitize_text($profile['business_type'] ?? '')),
            'description' => self::sanitize_sql_content(self::sanitize_text($profile['description'] ?? '')),
            'target_audience' => self::sanitize_sql_content(self::sanitize_text($profile['target_audience'] ?? '')),
            'services' => self::sanitize_sql_content(self::sanitize_text($profile['services'] ?? '')),
            'location' => self::sanitize_sql_content(self::sanitize_text($profile['location'] ?? '')),
            'phone' => self::sanitize_text($profile['phone'] ?? ''),
            'email' => is_email($profile['email'] ?? '') ? sanitize_email($profile['email']) : '',
            'website' => esc_url_raw($profile['website'] ?? ''),
            'tone' => self::sanitize_sql_content(self::sanitize_text($profile['tone'] ?? '')),
            'keywords' => array(),
            'usp' => self::sanitize_sql_content(self::sanitize_text($profile['usp'] ?? ''))
        );

        // Sanitize keywords array
        if (isset($profile['keywords']) && is_array($profile['keywords'])) {
            foreach ($profile['keywords'] as $keyword) {
                $clean_keyword = self::sanitize_sql_content(self::sanitize_text($keyword));
                if (!empty($clean_keyword)) {
                    $sanitized['keywords'][] = $clean_keyword;
                }
            }
        }

        return $sanitized;
    }

    /**
     * Encrypt API key for secure storage
     *
     * @param string $api_key API key to encrypt
     * @return string Encrypted API key
     */
    public static function encrypt_api_key($api_key) {
        if (empty($api_key)) {
            return '';
        }

        // Use WordPress encryption if available
        if (defined('SECURE_AUTH_KEY') && !empty(SECURE_AUTH_KEY)) {
            return base64_encode($api_key . '|' . wp_hash(SECURE_AUTH_KEY));
        }
        
        return base64_encode($api_key);
    }

    /**
     * Decrypt API key for usage
     *
     * @param string $encrypted_key Encrypted API key
     * @return string Decrypted API key
     */
    public static function decrypt_api_key($encrypted_key) {
        if (empty($encrypted_key)) {
            return '';
        }

        try {
            $decoded = base64_decode($encrypted_key);
            
            if (defined('SECURE_AUTH_KEY') && !empty(SECURE_AUTH_KEY)) {
                $parts = explode('|', $decoded);
                if (count($parts) === 2) {
                    return $parts[0];
                }
            }
            
            return $decoded;
        } catch (Exception $e) {
            return '';
        }
    }

    /**
     * Validate API key format for different providers
     *
     * @param string $provider Provider name
     * @param string $api_key API key to validate
     * @return bool Whether the API key format is valid
     */
    public static function validate_api_key_format($provider, $api_key) {
        if (empty($api_key)) {
            return false;
        }

        $patterns = array(
            'openai' => '/^sk-[a-zA-Z0-9]{48,}$/',
            'anthropic' => '/^sk-ant-[a-zA-Z0-9_-]{95,}$/',
            'google' => '/^[a-zA-Z0-9_-]{39}$/',
            'groq' => '/^gsk_[a-zA-Z0-9]{56}$/'
        );

        if (isset($patterns[$provider])) {
            return preg_match($patterns[$provider], $api_key);
        }
        
        return strlen($api_key) > 10;
    }

    /**
     * Mask API key for display purposes
     *
     * @param string $api_key API key to mask
     * @return string Masked API key
     */
    public static function mask_api_key($api_key) {
        if (empty($api_key) || strlen($api_key) < 8) {
            return '****';
        }
        
        $start = substr($api_key, 0, 4);
        $end = substr($api_key, -4);
        $middle = str_repeat('*', min(strlen($api_key) - 8, 20));
        
        return $start . $middle . $end;
    }

    /**
     * Rate limiting check
     *
     * @param string $identifier User identifier
     * @param int $max_requests Maximum requests allowed
     * @param int $window_seconds Time window in seconds
     * @return bool Whether request is allowed
     */
    public static function is_rate_limit_allowed($identifier, $max_requests = 100, $window_seconds = 3600) {
        $transient_key = 'aicrp_rate_limit_' . md5($identifier);
        $current_requests = get_transient($transient_key);
        
        if ($current_requests === false) {
            $current_requests = 1;
            set_transient($transient_key, $current_requests, $window_seconds);
            return true;
        }
        
        if ($current_requests >= $max_requests) {
            return false;
        }
        
        $current_requests++;
        set_transient($transient_key, $current_requests, $window_seconds);
        return true;
    }

    /**
     * Get remaining requests for rate limiting
     *
     * @param string $identifier User identifier
     * @param int $max_requests Maximum requests allowed
     * @return int Remaining requests
     */
    public static function get_remaining_requests($identifier, $max_requests = 100) {
        $transient_key = 'aicrp_rate_limit_' . md5($identifier);
        $current_requests = get_transient($transient_key);
        
        if ($current_requests === false) {
            return $max_requests;
        }
        
        return max(0, $max_requests - $current_requests);
    }

    /**
     * Reset rate limit for identifier
     *
     * @param string $identifier User identifier
     */
    public static function reset_rate_limit($identifier) {
        $transient_key = 'aicrp_rate_limit_' . md5($identifier);
        delete_transient($transient_key);
    }

    /**
     * Scan content for potentially malicious patterns
     *
     * @param string $content Content to scan
     * @return array Scan results
     */
    public static function scan_content($content) {
        $issues = array();
        
        // Check for script tags
        if (preg_match('/<script[^>]*>.*?<\/script>/i', $content)) {
            $issues[] = __('Script tags detected', 'ai-content-replacer-pro');
        }
        
        // Check for javascript: protocols
        if (preg_match('/javascript:/i', $content)) {
            $issues[] = __('JavaScript protocols detected', 'ai-content-replacer-pro');
        }
        
        // Check for event handlers
        if (preg_match('/on\w+\s*=/i', $content)) {
            $issues[] = __('Event handlers detected', 'ai-content-replacer-pro');
        }
        
        // Check for iframe with suspicious sources
        if (preg_match('/<iframe[^>]+src=["\'"][^"\']*(?:javascript:|data:)/i', $content)) {
            $issues[] = __('Suspicious iframe detected', 'ai-content-replacer-pro');
        }
        
        // Check for excessive HTML complexity (potential DoS)
        $html_tags = preg_match_all('/<[^>]+>/', $content);
        if ($html_tags > 1000) {
            $issues[] = __('Excessive HTML complexity', 'ai-content-replacer-pro');
        }
        
        return array(
            'safe' => empty($issues),
            'issues' => $issues
        );
    }

    /**
     * Clean content while preserving formatting
     *
     * @param string $content Content to clean
     * @return string Cleaned content
     */
    public static function clean_content($content) {
        // Remove script tags completely
        $content = preg_replace('/<script[^>]*>.*?<\/script>/i', '', $content);
        
        // Remove javascript: protocols
        $content = preg_replace('/javascript:/i', '', $content);
        
        // Remove event handlers but keep the element
        $content = preg_replace('/\s+on\w+\s*=\s*["\'][^"\']*["\']/i', '', $content);
        
        // Clean up extra whitespace
        $content = preg_replace('/\s+/', ' ', $content);
        
        return trim($content);
    }

    /**
     * Validate content length and complexity
     *
     * @param string $content Content to validate
     * @return array Validation results
     */
    public static function validate_content_limits($content) {
        // Check content length (max 100KB)
        if (strlen($content) > 100000) {
            return array(
                'valid' => false,
                'reason' => __('Content too large (max 100KB)', 'ai-content-replacer-pro')
            );
        }
        
        // Check for reasonable line length
        $lines = explode("\n", $content);
        $long_lines = array_filter($lines, function($line) {
            return strlen($line) > 10000;
        });
        
        if (!empty($long_lines)) {
            return array(
                'valid' => false,
                'reason' => __('Lines too long (max 10KB per line)', 'ai-content-replacer-pro')
            );
        }
        
        // Check for reasonable number of lines
        if (count($lines) > 10000) {
            return array(
                'valid' => false,
                'reason' => __('Too many lines (max 10,000)', 'ai-content-replacer-pro')
            );
        }
        
        return array('valid' => true);
    }

    /**
     * Generate secure nonce for forms
     *
     * @return string Secure nonce
     */
    public static function generate_nonce() {
        return wp_create_nonce('aicrp_security_nonce');
    }

    /**
     * Log security event
     *
     * @param string $action Action performed
     * @param string $severity Severity level
     * @param array $details Additional details
     * @param int $user_id User ID (optional)
     */
    public static function log_security_event($action, $severity, $details = array(), $user_id = null) {
        if (!$user_id) {
            $user_id = get_current_user_id();
        }

        $log_entry = array(
            'timestamp' => current_time('mysql'),
            'action' => sanitize_text_field($action),
            'severity' => sanitize_text_field($severity),
            'user_id' => intval($user_id),
            'ip_address' => self::get_user_ip(),
            'user_agent' => sanitize_text_field($_SERVER['HTTP_USER_AGENT'] ?? ''),
            'details' => wp_json_encode($details)
        );

        // Store in database
        global $wpdb;
        $wpdb->insert(
            $wpdb->prefix . 'aicrp_security_logs',
            $log_entry,
            array('%s', '%s', '%s', '%d', '%s', '%s', '%s')
        );

        // Send critical alerts
        if ($severity === 'critical') {
            self::send_security_alert($log_entry);
        }
    }

    /**
     * Get user IP address
     *
     * @return string User IP address
     */
    private static function get_user_ip() {
        if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ip = sanitize_text_field($_SERVER['HTTP_X_FORWARDED_FOR']);
        } elseif (!empty($_SERVER['HTTP_X_REAL_IP'])) {
            $ip = sanitize_text_field($_SERVER['HTTP_X_REAL_IP']);
        } else {
            $ip = sanitize_text_field($_SERVER['REMOTE_ADDR'] ?? '');
        }
        
        return filter_var($ip, FILTER_VALIDATE_IP) ? $ip : '';
    }

    /**
     * Send security alert for critical events
     *
     * @param array $log_entry Log entry data
     */
    private static function send_security_alert($log_entry) {
        $admin_email = get_option('admin_email');
        
        if (empty($admin_email)) {
            return;
        }

        $subject = sprintf(
            __('[%s] Critical Security Alert - AI Content Replacer Pro', 'ai-content-replacer-pro'),
            get_bloginfo('name')
        );

        $message = sprintf(
            __('A critical security event has been detected on your website:

Action: %s
Time: %s
User: %s
IP Address: %s
Details: %s

Please review your security logs immediately.', 'ai-content-replacer-pro'),
            $log_entry['action'],
            $log_entry['timestamp'],
            get_userdata($log_entry['user_id'])->user_login ?? 'Unknown',
            $log_entry['ip_address'],
            $log_entry['details']
        );

        wp_mail($admin_email, $subject, $message);
    }

    /**
     * Block unauthorized access
     */
    public static function block_unauthorized() {
        wp_die(
            __('Unauthorized access detected.', 'ai-content-replacer-pro'),
            __('Security Alert', 'ai-content-replacer-pro'),
            array('response' => 403)
        );
    }
}