<?php
/**
 * Plugin Name: AI Content Replacer Pro
 * Plugin URI: https://wordpress.org/plugins/ai-content-replacer-pro
 * Description: Revolutionary WordPress plugin that transforms your website content instantly using advanced AI technology. One-click content replacement with design preservation.
 * Version: 1.0.0
 * Author: AI Content Replacer Pro Team
 * Author URI: https://aicontentreplacer.pro
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: ai-content-replacer-pro
 * Domain Path: /languages
 * Requires at least: 5.0
 * Tested up to: 6.4
 * Requires PHP: 7.4
 * Network: true
 *
 * @package AI_Content_Replacer_Pro
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('AICRP_VERSION', '1.0.0');
define('AICRP_PLUGIN_FILE', __FILE__);
define('AICRP_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('AICRP_PLUGIN_URL', plugin_dir_url(__FILE__));
define('AICRP_PLUGIN_BASENAME', plugin_basename(__FILE__));

/**
 * Main plugin class
 */
final class AI_Content_Replacer_Pro {
    
    /**
     * Plugin instance
     *
     * @var AI_Content_Replacer_Pro
     */
    private static $instance = null;

    /**
     * Get plugin instance
     *
     * @return AI_Content_Replacer_Pro
     */
    public static function instance() {
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor
     */
    private function __construct() {
        $this->init_hooks();
    }

    /**
     * Initialize WordPress hooks
     */
    private function init_hooks() {
        add_action('init', array($this, 'init'));
        add_action('plugins_loaded', array($this, 'plugins_loaded'));
        
        // Activation and deactivation hooks
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
        
        // Admin hooks
        if (is_admin()) {
            add_action('admin_menu', array($this, 'admin_menu'));
            add_action('admin_enqueue_scripts', array($this, 'admin_scripts'));
            add_action('wp_ajax_aicrp_save_business_profile', array($this, 'ajax_save_business_profile'));
            add_action('wp_ajax_aicrp_save_api_config', array($this, 'ajax_save_api_config'));
            add_action('wp_ajax_aicrp_process_content', array($this, 'ajax_process_content'));
            add_action('wp_ajax_aicrp_run_tests', array($this, 'ajax_run_tests'));
        }
    }

    /**
     * Initialize plugin
     */
    public function init() {
        // Load text domain
        load_plugin_textdomain('ai-content-replacer-pro', false, dirname(plugin_basename(__FILE__)) . '/languages');
        
        // Include required files
        $this->includes();
    }

    /**
     * Plugin loaded
     */
    public function plugins_loaded() {
        // Initialize components
        if (class_exists('AICRP_Security')) {
            AICRP_Security::init();
        }
    }

    /**
     * Include required files
     */
    private function includes() {
        require_once AICRP_PLUGIN_DIR . 'includes/class-aicrp-security.php';
        require_once AICRP_PLUGIN_DIR . 'includes/class-aicrp-database.php';
        require_once AICRP_PLUGIN_DIR . 'includes/class-aicrp-api-manager.php';
        require_once AICRP_PLUGIN_DIR . 'includes/class-aicrp-content-processor.php';
        require_once AICRP_PLUGIN_DIR . 'includes/class-aicrp-testing.php';
        require_once AICRP_PLUGIN_DIR . 'includes/class-aicrp-page-builder-integration.php';
        require_once AICRP_PLUGIN_DIR . 'includes/class-aicrp-seo-integration.php';
    }

    /**
     * Plugin activation
     */
    public function activate() {
        // Create database tables
        if (class_exists('AICRP_Database')) {
            AICRP_Database::create_tables();
        }
        
        // Set default options
        $this->set_default_options();
        
        // Schedule cron jobs
        if (!wp_next_scheduled('aicrp_cleanup_logs')) {
            wp_schedule_event(time(), 'daily', 'aicrp_cleanup_logs');
        }
        
        // Flush rewrite rules
        flush_rewrite_rules();
    }

    /**
     * Plugin deactivation
     */
    public function deactivate() {
        // Clear scheduled events
        wp_clear_scheduled_hook('aicrp_cleanup_logs');
        
        // Flush rewrite rules
        flush_rewrite_rules();
    }

    /**
     * Set default options
     */
    private function set_default_options() {
        $default_options = array(
            'aicrp_version' => AICRP_VERSION,
            'aicrp_db_version' => '1.0.0',
            'aicrp_security_enabled' => true,
            'aicrp_rate_limit_enabled' => true,
            'aicrp_audit_logging_enabled' => true,
            'aicrp_backup_enabled' => true
        );
        
        foreach ($default_options as $option => $value) {
            if (!get_option($option)) {
                update_option($option, $value);
            }
        }
    }

    /**
     * Add admin menu
     */
    public function admin_menu() {
        // Check user capabilities
        if (!current_user_can('manage_options')) {
            return;
        }

        // Main menu
        add_menu_page(
            __('AI Content Replacer Pro', 'ai-content-replacer-pro'),
            __('AI Content Pro', 'ai-content-replacer-pro'),
            'manage_options',
            'ai-content-replacer-pro',
            array($this, 'admin_page'),
            'dashicons-lightbulb',
            30
        );

        // Submenu pages
        add_submenu_page(
            'ai-content-replacer-pro',
            __('Dashboard', 'ai-content-replacer-pro'),
            __('Dashboard', 'ai-content-replacer-pro'),
            'manage_options',
            'ai-content-replacer-pro',
            array($this, 'admin_page')
        );

        add_submenu_page(
            'ai-content-replacer-pro',
            __('Business Profile', 'ai-content-replacer-pro'),
            __('Business Profile', 'ai-content-replacer-pro'),
            'manage_options',
            'aicrp-business-profile',
            array($this, 'business_profile_page')
        );

        add_submenu_page(
            'ai-content-replacer-pro',
            __('AI Providers', 'ai-content-replacer-pro'),
            __('AI Providers', 'ai-content-replacer-pro'),
            'manage_options',
            'aicrp-ai-providers',
            array($this, 'ai_providers_page')
        );

        add_submenu_page(
            'ai-content-replacer-pro',
            __('Content Processing', 'ai-content-replacer-pro'),
            __('Content Processing', 'ai-content-replacer-pro'),
            'manage_options',
            'aicrp-content-processing',
            array($this, 'content_processing_page')
        );

        add_submenu_page(
            'ai-content-replacer-pro',
            __('Analytics', 'ai-content-replacer-pro'),
            __('Analytics', 'ai-content-replacer-pro'),
            'manage_options',
            'aicrp-analytics',
            array($this, 'analytics_page')
        );

        add_submenu_page(
            'ai-content-replacer-pro',
            __('Testing', 'ai-content-replacer-pro'),
            __('Testing', 'ai-content-replacer-pro'),
            'manage_options',
            'aicrp-testing',
            array($this, 'testing_page')
        );
    }

    /**
     * Enqueue admin scripts and styles
     */
    public function admin_scripts($hook) {
        // Only load on plugin pages
        if (strpos($hook, 'ai-content-replacer-pro') === false && strpos($hook, 'aicrp-') === false) {
            return;
        }

        // Enqueue styles
        wp_enqueue_style(
            'aicrp-admin-style',
            AICRP_PLUGIN_URL . 'assets/css/admin.css',
            array(),
            AICRP_VERSION
        );

        // Enqueue scripts
        wp_enqueue_script(
            'aicrp-admin-script',
            AICRP_PLUGIN_URL . 'assets/js/admin.js',
            array('jquery'),
            AICRP_VERSION,
            true
        );

        // Localize script
        wp_localize_script('aicrp-admin-script', 'aicrp_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('aicrp_nonce'),
            'strings' => array(
                'processing' => __('Processing...', 'ai-content-replacer-pro'),
                'success' => __('Success!', 'ai-content-replacer-pro'),
                'error' => __('Error occurred. Please try again.', 'ai-content-replacer-pro'),
                'confirm' => __('Are you sure you want to proceed?', 'ai-content-replacer-pro')
            )
        ));
    }

    /**
     * Main admin page
     */
    public function admin_page() {
        include AICRP_PLUGIN_DIR . 'admin/dashboard.php';
    }

    /**
     * Business profile page
     */
    public function business_profile_page() {
        include AICRP_PLUGIN_DIR . 'admin/business-profile.php';
    }

    /**
     * AI providers page
     */
    public function ai_providers_page() {
        include AICRP_PLUGIN_DIR . 'admin/ai-providers.php';
    }

    /**
     * Content processing page
     */
    public function content_processing_page() {
        include AICRP_PLUGIN_DIR . 'admin/content-processing.php';
    }

    /**
     * Analytics page
     */
    public function analytics_page() {
        include AICRP_PLUGIN_DIR . 'admin/analytics.php';
    }

    /**
     * Testing page
     */
    public function testing_page() {
        include AICRP_PLUGIN_DIR . 'admin/testing.php';
    }

    /**
     * AJAX: Save business profile
     */
    public function ajax_save_business_profile() {
        // Security check
        if (!wp_verify_nonce($_POST['nonce'], 'aicrp_nonce') || !current_user_can('manage_options')) {
            wp_die(__('Security check failed.', 'ai-content-replacer-pro'));
        }

        // Sanitize input
        $profile_data = AICRP_Security::sanitize_business_profile($_POST['profile']);
        
        // Save to database
        $result = update_option('aicrp_business_profile', $profile_data);
        
        if ($result) {
            wp_send_json_success(array(
                'message' => __('Business profile saved successfully!', 'ai-content-replacer-pro')
            ));
        } else {
            wp_send_json_error(array(
                'message' => __('Failed to save business profile.', 'ai-content-replacer-pro')
            ));
        }
    }

    /**
     * AJAX: Save API configuration
     */
    public function ajax_save_api_config() {
        // Security check
        if (!wp_verify_nonce($_POST['nonce'], 'aicrp_nonce') || !current_user_can('manage_options')) {
            wp_die(__('Security check failed.', 'ai-content-replacer-pro'));
        }

        // Process API configuration
        if (class_exists('AICRP_API_Manager')) {
            $result = AICRP_API_Manager::save_configuration($_POST['config']);
            
            if ($result) {
                wp_send_json_success(array(
                    'message' => __('API configuration saved successfully!', 'ai-content-replacer-pro')
                ));
            } else {
                wp_send_json_error(array(
                    'message' => __('Failed to save API configuration.', 'ai-content-replacer-pro')
                ));
            }
        }
    }

    /**
     * AJAX: Process content
     */
    public function ajax_process_content() {
        // Security check
        if (!wp_verify_nonce($_POST['nonce'], 'aicrp_nonce') || !current_user_can('manage_options')) {
            wp_die(__('Security check failed.', 'ai-content-replacer-pro'));
        }

        // Process content
        if (class_exists('AICRP_Content_Processor')) {
            $result = AICRP_Content_Processor::process_content($_POST['options']);
            wp_send_json($result);
        }
    }

    /**
     * AJAX: Run tests
     */
    public function ajax_run_tests() {
        // Security check
        if (!wp_verify_nonce($_POST['nonce'], 'aicrp_nonce') || !current_user_can('manage_options')) {
            wp_die(__('Security check failed.', 'ai-content-replacer-pro'));
        }

        // Run tests
        if (class_exists('AICRP_Testing')) {
            $results = AICRP_Testing::run_all_tests();
            wp_send_json_success($results);
        }
    }
}

/**
 * Initialize the plugin
 */
function aicrp_init() {
    return AI_Content_Replacer_Pro::instance();
}

// Initialize plugin
aicrp_init();

/**
 * Uninstall hook
 */
register_uninstall_hook(__FILE__, 'aicrp_uninstall');

function aicrp_uninstall() {
    // Remove all plugin data
    if (class_exists('AICRP_Database')) {
        AICRP_Database::drop_tables();
    }
    
    // Delete options
    delete_option('aicrp_business_profile');
    delete_option('aicrp_api_providers');
    delete_option('aicrp_version');
    delete_option('aicrp_db_version');
    delete_option('aicrp_security_enabled');
    delete_option('aicrp_rate_limit_enabled');
    delete_option('aicrp_audit_logging_enabled');
    delete_option('aicrp_backup_enabled');
    
    // Clear cron jobs
    wp_clear_scheduled_hook('aicrp_cleanup_logs');
}