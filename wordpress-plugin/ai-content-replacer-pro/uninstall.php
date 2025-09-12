<?php
/**
 * Uninstall AI Content Replacer Pro
 * Clean up all plugin data when uninstalled
 *
 * @package AI_Content_Replacer_Pro
 */

// If uninstall not called from WordPress, then exit
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

/**
 * Clean up plugin data
 */
function aicrp_cleanup_plugin_data() {
    global $wpdb;

    // Remove database tables
    $tables = array(
        $wpdb->prefix . 'aicrp_business_profiles',
        $wpdb->prefix . 'aicrp_providers', 
        $wpdb->prefix . 'aicrp_processing_history',
        $wpdb->prefix . 'aicrp_security_logs',
        $wpdb->prefix . 'aicrp_analytics',
        $wpdb->prefix . 'aicrp_rate_limits'
    );

    foreach ($tables as $table) {
        $wpdb->query("DROP TABLE IF EXISTS {$table}");
    }

    // Remove options
    $options = array(
        'aicrp_version',
        'aicrp_db_version',
        'aicrp_business_profile',
        'aicrp_api_providers',
        'aicrp_security_enabled',
        'aicrp_rate_limit_enabled',
        'aicrp_audit_logging_enabled',
        'aicrp_backup_enabled',
        'aicrp_processing_options',
        'aicrp_analytics_settings'
    );

    foreach ($options as $option) {
        delete_option($option);
        delete_site_option($option); // For multisite
    }

    // Remove user meta
    $wpdb->query("DELETE FROM {$wpdb->usermeta} WHERE meta_key LIKE 'aicrp_%'");

    // Remove post meta (processing markers and backups)
    $wpdb->query("DELETE FROM {$wpdb->postmeta} WHERE meta_key LIKE '_aicrp_%'");

    // Clear any scheduled cron jobs
    wp_clear_scheduled_hook('aicrp_cleanup_logs');
    wp_clear_scheduled_hook('aicrp_reset_daily_usage');
    wp_clear_scheduled_hook('aicrp_analytics_aggregation');

    // Remove transients
    $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_aicrp_%'");
    $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_timeout_aicrp_%'");

    // For multisite installations
    if (is_multisite()) {
        $sites = get_sites(array('number' => 0));
        
        foreach ($sites as $site) {
            switch_to_blog($site->blog_id);
            
            // Remove site-specific options and meta
            foreach ($options as $option) {
                delete_option($option);
            }
            
            // Remove site-specific post meta
            $wpdb->query("DELETE FROM {$wpdb->postmeta} WHERE meta_key LIKE '_aicrp_%'");
            
            restore_current_blog();
        }
    }
}

// Execute cleanup
aicrp_cleanup_plugin_data();