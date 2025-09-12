<?php
/**
 * Database Class for AI Content Replacer Pro
 * Handles all database operations
 *
 * @package AI_Content_Replacer_Pro
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class AICRP_Database {
    
    /**
     * Database version
     */
    const DB_VERSION = '1.0.0';

    /**
     * Create plugin database tables
     */
    public static function create_tables() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();

        // Business profiles table
        $profiles_table = $wpdb->prefix . 'aicrp_business_profiles';
        $profiles_sql = "CREATE TABLE $profiles_table (
            id int(11) NOT NULL AUTO_INCREMENT,
            user_id int(11) NOT NULL,
            business_name varchar(255) NOT NULL,
            business_type varchar(100),
            description text,
            target_audience text,
            services text,
            location varchar(255),
            phone varchar(50),
            email varchar(100),
            website varchar(255),
            tone varchar(50),
            keywords text,
            usp text,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY user_id (user_id)
        ) $charset_collate;";

        // AI providers table
        $providers_table = $wpdb->prefix . 'aicrp_providers';
        $providers_sql = "CREATE TABLE $providers_table (
            id int(11) NOT NULL AUTO_INCREMENT,
            user_id int(11) NOT NULL,
            provider_name varchar(50) NOT NULL,
            api_key_encrypted text,
            model varchar(100),
            priority int(2) DEFAULT 5,
            daily_limit int(10) DEFAULT 1000,
            used_today int(10) DEFAULT 0,
            cost_per_token decimal(10,6) DEFAULT 0.002,
            enabled tinyint(1) DEFAULT 1,
            status varchar(20) DEFAULT 'inactive',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY user_id (user_id),
            KEY provider_name (provider_name)
        ) $charset_collate;";

        // Processing history table
        $history_table = $wpdb->prefix . 'aicrp_processing_history';
        $history_sql = "CREATE TABLE $history_table (
            id int(11) NOT NULL AUTO_INCREMENT,
            user_id int(11) NOT NULL,
            post_id int(11),
            processing_type varchar(50),
            content_type varchar(20),
            tokens_used int(10) DEFAULT 0,
            provider_used varchar(50),
            cost decimal(10,4) DEFAULT 0.0000,
            duration_seconds int(5) DEFAULT 0,
            status varchar(20) DEFAULT 'pending',
            error_message text,
            backup_content longtext,
            processed_content longtext,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY user_id (user_id),
            KEY post_id (post_id),
            KEY status (status)
        ) $charset_collate;";

        // Security logs table
        $security_table = $wpdb->prefix . 'aicrp_security_logs';
        $security_sql = "CREATE TABLE $security_table (
            id int(11) NOT NULL AUTO_INCREMENT,
            timestamp datetime DEFAULT CURRENT_TIMESTAMP,
            action varchar(100) NOT NULL,
            severity varchar(20) NOT NULL,
            user_id int(11),
            ip_address varchar(45),
            user_agent text,
            details text,
            PRIMARY KEY (id),
            KEY timestamp (timestamp),
            KEY severity (severity),
            KEY user_id (user_id)
        ) $charset_collate;";

        // Usage analytics table
        $analytics_table = $wpdb->prefix . 'aicrp_analytics';
        $analytics_sql = "CREATE TABLE $analytics_table (
            id int(11) NOT NULL AUTO_INCREMENT,
            user_id int(11) NOT NULL,
            date date NOT NULL,
            pages_processed int(5) DEFAULT 0,
            words_replaced int(8) DEFAULT 0,
            tokens_used int(8) DEFAULT 0,
            cost decimal(10,4) DEFAULT 0.0000,
            processing_time int(8) DEFAULT 0,
            success_rate decimal(5,2) DEFAULT 100.00,
            provider_stats text,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY user_date (user_id, date),
            KEY date (date)
        ) $charset_collate;";

        // API rate limiting table
        $rate_limit_table = $wpdb->prefix . 'aicrp_rate_limits';
        $rate_limit_sql = "CREATE TABLE $rate_limit_table (
            id int(11) NOT NULL AUTO_INCREMENT,
            identifier varchar(100) NOT NULL,
            requests int(5) DEFAULT 1,
            window_start datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY identifier (identifier),
            KEY window_start (window_start)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        
        dbDelta($profiles_sql);
        dbDelta($providers_sql);
        dbDelta($history_sql);
        dbDelta($security_sql);
        dbDelta($analytics_sql);
        dbDelta($rate_limit_sql);

        // Update database version
        update_option('aicrp_db_version', self::DB_VERSION);
        
        // Create default entries
        self::create_default_data();
    }

    /**
     * Create default data
     */
    private static function create_default_data() {
        global $wpdb;
        
        $current_user_id = get_current_user_id();
        if (!$current_user_id) {
            $current_user_id = 1; // Fallback to admin user
        }

        // Insert default AI providers
        $providers_table = $wpdb->prefix . 'aicrp_providers';
        $default_providers = array(
            array(
                'user_id' => $current_user_id,
                'provider_name' => 'openai',
                'model' => 'gpt-3.5-turbo',
                'priority' => 1,
                'daily_limit' => 1000,
                'cost_per_token' => 0.002,
                'enabled' => 0,
                'status' => 'inactive'
            ),
            array(
                'user_id' => $current_user_id,
                'provider_name' => 'anthropic',
                'model' => 'claude-3-haiku-20240307',
                'priority' => 2,
                'daily_limit' => 500,
                'cost_per_token' => 0.0008,
                'enabled' => 0,
                'status' => 'inactive'
            ),
            array(
                'user_id' => $current_user_id,
                'provider_name' => 'google',
                'model' => 'gemini-pro',
                'priority' => 3,
                'daily_limit' => 800,
                'cost_per_token' => 0.001,
                'enabled' => 0,
                'status' => 'inactive'
            ),
            array(
                'user_id' => $current_user_id,
                'provider_name' => 'groq',
                'model' => 'llama3-70b-8192',
                'priority' => 4,
                'daily_limit' => 2000,
                'cost_per_token' => 0.0005,
                'enabled' => 0,
                'status' => 'inactive'
            )
        );

        foreach ($default_providers as $provider) {
            $wpdb->insert($providers_table, $provider);
        }
    }

    /**
     * Drop plugin database tables
     */
    public static function drop_tables() {
        global $wpdb;
        
        $tables = array(
            $wpdb->prefix . 'aicrp_business_profiles',
            $wpdb->prefix . 'aicrp_providers',
            $wpdb->prefix . 'aicrp_processing_history',
            $wpdb->prefix . 'aicrp_security_logs',
            $wpdb->prefix . 'aicrp_analytics',
            $wpdb->prefix . 'aicrp_rate_limits'
        );

        foreach ($tables as $table) {
            $wpdb->query("DROP TABLE IF EXISTS $table");
        }

        delete_option('aicrp_db_version');
    }

    /**
     * Save business profile
     *
     * @param array $profile_data Profile data
     * @param int $user_id User ID
     * @return bool Success status
     */
    public static function save_business_profile($profile_data, $user_id = null) {
        global $wpdb;
        
        if (!$user_id) {
            $user_id = get_current_user_id();
        }

        $table = $wpdb->prefix . 'aicrp_business_profiles';
        
        $data = array(
            'user_id' => $user_id,
            'business_name' => sanitize_text_field($profile_data['business_name'] ?? ''),
            'business_type' => sanitize_text_field($profile_data['business_type'] ?? ''),
            'description' => sanitize_textarea_field($profile_data['description'] ?? ''),
            'target_audience' => sanitize_textarea_field($profile_data['target_audience'] ?? ''),
            'services' => sanitize_textarea_field($profile_data['services'] ?? ''),
            'location' => sanitize_text_field($profile_data['location'] ?? ''),
            'phone' => sanitize_text_field($profile_data['phone'] ?? ''),
            'email' => sanitize_email($profile_data['email'] ?? ''),
            'website' => esc_url_raw($profile_data['website'] ?? ''),
            'tone' => sanitize_text_field($profile_data['tone'] ?? ''),
            'keywords' => wp_json_encode($profile_data['keywords'] ?? array()),
            'usp' => sanitize_textarea_field($profile_data['usp'] ?? '')
        );

        // Check if profile exists
        $existing_profile = $wpdb->get_var(
            $wpdb->prepare("SELECT id FROM $table WHERE user_id = %d", $user_id)
        );

        if ($existing_profile) {
            // Update existing profile
            $result = $wpdb->update(
                $table,
                $data,
                array('user_id' => $user_id),
                array('%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s'),
                array('%d')
            );
        } else {
            // Insert new profile
            $result = $wpdb->insert($table, $data);
        }

        return $result !== false;
    }

    /**
     * Get business profile
     *
     * @param int $user_id User ID
     * @return array|null Profile data
     */
    public static function get_business_profile($user_id = null) {
        global $wpdb;
        
        if (!$user_id) {
            $user_id = get_current_user_id();
        }

        $table = $wpdb->prefix . 'aicrp_business_profiles';
        
        $profile = $wpdb->get_row(
            $wpdb->prepare("SELECT * FROM $table WHERE user_id = %d", $user_id),
            ARRAY_A
        );

        if ($profile && !empty($profile['keywords'])) {
            $profile['keywords'] = json_decode($profile['keywords'], true) ?: array();
        }

        return $profile;
    }

    /**
     * Save API provider configuration
     *
     * @param array $provider_data Provider data
     * @param int $user_id User ID
     * @return bool Success status
     */
    public static function save_api_provider($provider_data, $user_id = null) {
        global $wpdb;
        
        if (!$user_id) {
            $user_id = get_current_user_id();
        }

        $table = $wpdb->prefix . 'aicrp_providers';
        
        $data = array(
            'user_id' => $user_id,
            'provider_name' => sanitize_text_field($provider_data['provider_name']),
            'api_key_encrypted' => AICRP_Security::encrypt_api_key($provider_data['api_key'] ?? ''),
            'model' => sanitize_text_field($provider_data['model'] ?? ''),
            'priority' => intval($provider_data['priority'] ?? 5),
            'daily_limit' => intval($provider_data['daily_limit'] ?? 1000),
            'cost_per_token' => floatval($provider_data['cost_per_token'] ?? 0.002),
            'enabled' => intval($provider_data['enabled'] ?? 0),
            'status' => sanitize_text_field($provider_data['status'] ?? 'inactive')
        );

        // Check if provider exists
        $existing_provider = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT id FROM $table WHERE user_id = %d AND provider_name = %s",
                $user_id,
                $data['provider_name']
            )
        );

        if ($existing_provider) {
            // Update existing provider
            $result = $wpdb->update(
                $table,
                $data,
                array('id' => $existing_provider)
            );
        } else {
            // Insert new provider
            $result = $wpdb->insert($table, $data);
        }

        return $result !== false;
    }

    /**
     * Get API providers
     *
     * @param int $user_id User ID
     * @return array Providers data
     */
    public static function get_api_providers($user_id = null) {
        global $wpdb;
        
        if (!$user_id) {
            $user_id = get_current_user_id();
        }

        $table = $wpdb->prefix . 'aicrp_providers';
        
        $providers = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM $table WHERE user_id = %d ORDER BY priority ASC",
                $user_id
            ),
            ARRAY_A
        );

        // Decrypt API keys for enabled providers
        foreach ($providers as &$provider) {
            if ($provider['enabled']) {
                $provider['api_key'] = AICRP_Security::decrypt_api_key($provider['api_key_encrypted']);
                $provider['api_key_masked'] = AICRP_Security::mask_api_key($provider['api_key']);
            }
            unset($provider['api_key_encrypted']); // Remove encrypted key from output
        }

        return $providers;
    }

    /**
     * Log processing history
     *
     * @param array $history_data History data
     * @return int|false Insert ID or false on failure
     */
    public static function log_processing_history($history_data) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'aicrp_processing_history';
        
        $data = array(
            'user_id' => get_current_user_id(),
            'post_id' => intval($history_data['post_id'] ?? 0),
            'processing_type' => sanitize_text_field($history_data['processing_type'] ?? ''),
            'content_type' => sanitize_text_field($history_data['content_type'] ?? ''),
            'tokens_used' => intval($history_data['tokens_used'] ?? 0),
            'provider_used' => sanitize_text_field($history_data['provider_used'] ?? ''),
            'cost' => floatval($history_data['cost'] ?? 0),
            'duration_seconds' => intval($history_data['duration_seconds'] ?? 0),
            'status' => sanitize_text_field($history_data['status'] ?? 'pending'),
            'error_message' => sanitize_textarea_field($history_data['error_message'] ?? ''),
            'backup_content' => wp_kses_post($history_data['backup_content'] ?? ''),
            'processed_content' => wp_kses_post($history_data['processed_content'] ?? '')
        );

        $result = $wpdb->insert($table, $data);
        
        if ($result) {
            return $wpdb->insert_id;
        }
        
        return false;
    }

    /**
     * Get processing history
     *
     * @param int $limit Number of records to retrieve
     * @param int $user_id User ID
     * @return array Processing history
     */
    public static function get_processing_history($limit = 50, $user_id = null) {
        global $wpdb;
        
        if (!$user_id) {
            $user_id = get_current_user_id();
        }

        $table = $wpdb->prefix . 'aicrp_processing_history';
        
        $history = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM $table WHERE user_id = %d ORDER BY created_at DESC LIMIT %d",
                $user_id,
                $limit
            ),
            ARRAY_A
        );

        return $history ?: array();
    }

    /**
     * Get analytics data
     *
     * @param int $days Number of days to retrieve
     * @param int $user_id User ID
     * @return array Analytics data
     */
    public static function get_analytics_data($days = 30, $user_id = null) {
        global $wpdb;
        
        if (!$user_id) {
            $user_id = get_current_user_id();
        }

        $analytics_table = $wpdb->prefix . 'aicrp_analytics';
        $history_table = $wpdb->prefix . 'aicrp_processing_history';
        
        // Get aggregated analytics
        $analytics = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT 
                    DATE(created_at) as date,
                    COUNT(*) as pages_processed,
                    SUM(tokens_used) as tokens_used,
                    SUM(cost) as cost,
                    SUM(duration_seconds) as processing_time,
                    AVG(CASE WHEN status = 'completed' THEN 100 ELSE 0 END) as success_rate
                FROM $history_table 
                WHERE user_id = %d 
                AND created_at >= DATE_SUB(NOW(), INTERVAL %d DAY)
                GROUP BY DATE(created_at)
                ORDER BY date DESC",
                $user_id,
                $days
            ),
            ARRAY_A
        );

        return $analytics ?: array();
    }

    /**
     * Update provider usage
     *
     * @param string $provider_name Provider name
     * @param int $tokens_used Tokens used
     * @param int $user_id User ID
     */
    public static function update_provider_usage($provider_name, $tokens_used, $user_id = null) {
        global $wpdb;
        
        if (!$user_id) {
            $user_id = get_current_user_id();
        }

        $table = $wpdb->prefix . 'aicrp_providers';
        
        $wpdb->query(
            $wpdb->prepare(
                "UPDATE $table 
                SET used_today = used_today + %d 
                WHERE user_id = %d AND provider_name = %s",
                $tokens_used,
                $user_id,
                $provider_name
            )
        );
    }

    /**
     * Reset daily usage counters
     */
    public static function reset_daily_usage() {
        global $wpdb;
        
        $table = $wpdb->prefix . 'aicrp_providers';
        
        $wpdb->query("UPDATE $table SET used_today = 0");
    }

    /**
     * Clean old logs
     *
     * @param int $days Days to keep
     */
    public static function cleanup_old_logs($days = 90) {
        global $wpdb;
        
        $security_table = $wpdb->prefix . 'aicrp_security_logs';
        $history_table = $wpdb->prefix . 'aicrp_processing_history';
        
        // Clean security logs
        $wpdb->query(
            $wpdb->prepare(
                "DELETE FROM $security_table WHERE timestamp < DATE_SUB(NOW(), INTERVAL %d DAY)",
                $days
            )
        );
        
        // Clean processing history (keep only essential data)
        $wpdb->query(
            $wpdb->prepare(
                "DELETE FROM $history_table WHERE created_at < DATE_SUB(NOW(), INTERVAL %d DAY) AND status != 'error'",
                $days
            )
        );
    }
}