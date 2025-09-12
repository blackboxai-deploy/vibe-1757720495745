<?php
/**
 * Main Dashboard Page
 *
 * @package AI_Content_Replacer_Pro
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Get current statistics
$stats = AICRP_Content_Processor::get_processing_statistics(30);
$provider_stats = AICRP_API_Manager::get_provider_statistics();
?>

<div class="wrap">
    <div class="aicrp-header">
        <h1 class="aicrp-title">
            <span class="aicrp-icon">⚡</span>
            <?php _e('AI Content Replacer Pro', 'ai-content-replacer-pro'); ?>
            <span class="aicrp-version">v<?php echo esc_html(AICRP_VERSION); ?></span>
        </h1>
        <p class="aicrp-subtitle">
            <?php _e('Transform your website content instantly with AI-powered replacement technology', 'ai-content-replacer-pro'); ?>
        </p>
    </div>

    <div class="aicrp-dashboard">
        <!-- Quick Stats -->
        <div class="aicrp-stats-grid">
            <div class="aicrp-stat-card">
                <div class="aicrp-stat-icon">📄</div>
                <div class="aicrp-stat-content">
                    <h3><?php echo esc_html($stats['total_processed'] ?? 0); ?></h3>
                    <p><?php _e('Pages Processed', 'ai-content-replacer-pro'); ?></p>
                </div>
            </div>
            
            <div class="aicrp-stat-card">
                <div class="aicrp-stat-icon">🤖</div>
                <div class="aicrp-stat-content">
                    <h3><?php echo esc_html($stats['total_tokens'] ?? 0); ?></h3>
                    <p><?php _e('AI Tokens Used', 'ai-content-replacer-pro'); ?></p>
                </div>
            </div>
            
            <div class="aicrp-stat-card">
                <div class="aicrp-stat-icon">💰</div>
                <div class="aicrp-stat-content">
                    <h3>$<?php echo esc_html(number_format($stats['total_cost'] ?? 0, 2)); ?></h3>
                    <p><?php _e('Total Cost', 'ai-content-replacer-pro'); ?></p>
                </div>
            </div>
            
            <div class="aicrp-stat-card">
                <div class="aicrp-stat-icon">⚡</div>
                <div class="aicrp-stat-content">
                    <h3><?php echo esc_html(number_format($stats['success_rate'] ?? 0, 1)); ?>%</h3>
                    <p><?php _e('Success Rate', 'ai-content-replacer-pro'); ?></p>
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="aicrp-quick-actions">
            <h2><?php _e('Quick Actions', 'ai-content-replacer-pro'); ?></h2>
            
            <div class="aicrp-action-grid">
                <div class="aicrp-action-card">
                    <h3><?php _e('Business Profile', 'ai-content-replacer-pro'); ?></h3>
                    <p><?php _e('Set up your business information for personalized content generation', 'ai-content-replacer-pro'); ?></p>
                    <a href="<?php echo esc_url(admin_url('admin.php?page=aicrp-business-profile')); ?>" class="aicrp-btn aicrp-btn-primary">
                        <?php _e('Configure Profile', 'ai-content-replacer-pro'); ?>
                    </a>
                </div>
                
                <div class="aicrp-action-card">
                    <h3><?php _e('AI Providers', 'ai-content-replacer-pro'); ?></h3>
                    <p><?php _e('Configure multiple AI providers for intelligent content generation', 'ai-content-replacer-pro'); ?></p>
                    <a href="<?php echo esc_url(admin_url('admin.php?page=aicrp-ai-providers')); ?>" class="aicrp-btn aicrp-btn-secondary">
                        <?php _e('Setup APIs', 'ai-content-replacer-pro'); ?>
                    </a>
                </div>
                
                <div class="aicrp-action-card">
                    <h3><?php _e('Process Content', 'ai-content-replacer-pro'); ?></h3>
                    <p><?php _e('Start one-click content replacement for your website pages and posts', 'ai-content-replacer-pro'); ?></p>
                    <a href="<?php echo esc_url(admin_url('admin.php?page=aicrp-content-processing')); ?>" class="aicrp-btn aicrp-btn-success">
                        <?php _e('Start Processing', 'ai-content-replacer-pro'); ?>
                    </a>
                </div>
                
                <div class="aicrp-action-card">
                    <h3><?php _e('Analytics', 'ai-content-replacer-pro'); ?></h3>
                    <p><?php _e('View detailed analytics and usage statistics for your content processing', 'ai-content-replacer-pro'); ?></p>
                    <a href="<?php echo esc_url(admin_url('admin.php?page=aicrp-analytics')); ?>" class="aicrp-btn aicrp-btn-info">
                        <?php _e('View Analytics', 'ai-content-replacer-pro'); ?>
                    </a>
                </div>
            </div>
        </div>

        <!-- Recent Activity -->
        <div class="aicrp-recent-activity">
            <h2><?php _e('Recent Activity', 'ai-content-replacer-pro'); ?></h2>
            
            <?php
            $recent_history = AICRP_Database::get_processing_history(10);
            if (!empty($recent_history)): ?>
                <div class="aicrp-activity-list">
                    <?php foreach ($recent_history as $item): ?>
                        <div class="aicrp-activity-item">
                            <div class="aicrp-activity-icon">
                                <?php echo $item['status'] === 'completed' ? '✅' : ($item['status'] === 'error' ? '❌' : '⏳'); ?>
                            </div>
                            <div class="aicrp-activity-content">
                                <h4><?php echo esc_html($item['processing_type']); ?></h4>
                                <p><?php echo esc_html($item['content_type']); ?> • 
                                   <?php echo esc_html($item['tokens_used']); ?> tokens • 
                                   $<?php echo esc_html(number_format($item['cost'], 4)); ?></p>
                                <span class="aicrp-activity-time"><?php echo esc_html(human_time_diff(strtotime($item['created_at']))); ?> ago</span>
                            </div>
                            <div class="aicrp-activity-status">
                                <span class="aicrp-status aicrp-status-<?php echo esc_attr($item['status']); ?>">
                                    <?php echo esc_html(ucfirst($item['status'])); ?>
                                </span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="aicrp-empty-state">
                    <p><?php _e('No recent activity. Start by configuring your business profile and AI providers.', 'ai-content-replacer-pro'); ?></p>
                </div>
            <?php endif; ?>
        </div>

        <!-- System Status -->
        <div class="aicrp-system-status">
            <h2><?php _e('System Status', 'ai-content-replacer-pro'); ?></h2>
            
            <div class="aicrp-status-grid">
                <?php
                // Check WordPress version
                global $wp_version;
                $wp_compatible = version_compare($wp_version, '5.0', '>=');
                ?>
                <div class="aicrp-status-item">
                    <span class="aicrp-status-icon <?php echo $wp_compatible ? 'success' : 'error'; ?>">
                        <?php echo $wp_compatible ? '✅' : '❌'; ?>
                    </span>
                    <span class="aicrp-status-text">
                        <?php printf(__('WordPress %s', 'ai-content-replacer-pro'), $wp_version); ?>
                        <?php if (!$wp_compatible): ?>
                            <small><?php _e('(5.0+ required)', 'ai-content-replacer-pro'); ?></small>
                        <?php endif; ?>
                    </span>
                </div>

                <?php
                // Check PHP version
                $php_compatible = version_compare(PHP_VERSION, '7.4', '>=');
                ?>
                <div class="aicrp-status-item">
                    <span class="aicrp-status-icon <?php echo $php_compatible ? 'success' : 'error'; ?>">
                        <?php echo $php_compatible ? '✅' : '❌'; ?>
                    </span>
                    <span class="aicrp-status-text">
                        <?php printf(__('PHP %s', 'ai-content-replacer-pro'), PHP_VERSION); ?>
                        <?php if (!$php_compatible): ?>
                            <small><?php _e('(7.4+ required)', 'ai-content-replacer-pro'); ?></small>
                        <?php endif; ?>
                    </span>
                </div>

                <?php
                // Check cURL support
                $curl_available = function_exists('curl_init');
                ?>
                <div class="aicrp-status-item">
                    <span class="aicrp-status-icon <?php echo $curl_available ? 'success' : 'error'; ?>">
                        <?php echo $curl_available ? '✅' : '❌'; ?>
                    </span>
                    <span class="aicrp-status-text">
                        <?php _e('cURL Support', 'ai-content-replacer-pro'); ?>
                        <?php if (!$curl_available): ?>
                            <small><?php _e('(Required for API calls)', 'ai-content-replacer-pro'); ?></small>
                        <?php endif; ?>
                    </span>
                </div>

                <?php
                // Check active AI providers
                $active_providers = array_filter($provider_stats, function($provider) {
                    return $provider['enabled'];
                });
                $providers_configured = !empty($active_providers);
                ?>
                <div class="aicrp-status-item">
                    <span class="aicrp-status-icon <?php echo $providers_configured ? 'success' : 'warning'; ?>">
                        <?php echo $providers_configured ? '✅' : '⚠️'; ?>
                    </span>
                    <span class="aicrp-status-text">
                        <?php printf(__('%d AI Providers Active', 'ai-content-replacer-pro'), count($active_providers)); ?>
                        <?php if (!$providers_configured): ?>
                            <small><?php _e('(Configure AI providers)', 'ai-content-replacer-pro'); ?></small>
                        <?php endif; ?>
                    </span>
                </div>
            </div>
        </div>

        <!-- Getting Started -->
        <?php if (empty($stats['total_processed'])): ?>
        <div class="aicrp-getting-started">
            <h2><?php _e('Getting Started', 'ai-content-replacer-pro'); ?></h2>
            
            <div class="aicrp-steps">
                <div class="aicrp-step">
                    <div class="aicrp-step-number">1</div>
                    <div class="aicrp-step-content">
                        <h3><?php _e('Configure Business Profile', 'ai-content-replacer-pro'); ?></h3>
                        <p><?php _e('Enter your business information to personalize content generation', 'ai-content-replacer-pro'); ?></p>
                        <a href="<?php echo esc_url(admin_url('admin.php?page=aicrp-business-profile')); ?>" class="aicrp-step-link">
                            <?php _e('Setup Profile →', 'ai-content-replacer-pro'); ?>
                        </a>
                    </div>
                </div>
                
                <div class="aicrp-step">
                    <div class="aicrp-step-number">2</div>
                    <div class="aicrp-step-content">
                        <h3><?php _e('Add AI Providers', 'ai-content-replacer-pro'); ?></h3>
                        <p><?php _e('Configure one or more AI providers for content generation', 'ai-content-replacer-pro'); ?></p>
                        <a href="<?php echo esc_url(admin_url('admin.php?page=aicrp-ai-providers')); ?>" class="aicrp-step-link">
                            <?php _e('Configure APIs →', 'ai-content-replacer-pro'); ?>
                        </a>
                    </div>
                </div>
                
                <div class="aicrp-step">
                    <div class="aicrp-step-number">3</div>
                    <div class="aicrp-step-content">
                        <h3><?php _e('Process Content', 'ai-content-replacer-pro'); ?></h3>
                        <p><?php _e('Start one-click content replacement for all your pages and posts', 'ai-content-replacer-pro'); ?></p>
                        <a href="<?php echo esc_url(admin_url('admin.php?page=aicrp-content-processing')); ?>" class="aicrp-step-link">
                            <?php _e('Start Processing →', 'ai-content-replacer-pro'); ?>
                        </a>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Provider Status -->
        <?php if (!empty($provider_stats)): ?>
        <div class="aicrp-provider-status">
            <h2><?php _e('AI Provider Status', 'ai-content-replacer-pro'); ?></h2>
            
            <div class="aicrp-provider-grid">
                <?php foreach ($provider_stats as $provider_id => $provider): ?>
                    <div class="aicrp-provider-card">
                        <div class="aicrp-provider-header">
                            <h3><?php echo esc_html($provider['name']); ?></h3>
                            <span class="aicrp-provider-status <?php echo $provider['enabled'] ? 'active' : 'inactive'; ?>">
                                <?php echo $provider['enabled'] ? __('Active', 'ai-content-replacer-pro') : __('Inactive', 'ai-content-replacer-pro'); ?>
                            </span>
                        </div>
                        
                        <?php if ($provider['enabled']): ?>
                            <div class="aicrp-provider-usage">
                                <div class="aicrp-usage-bar">
                                    <div class="aicrp-usage-fill" style="width: <?php echo esc_attr(($provider['used_today'] / $provider['daily_limit']) * 100); ?>%"></div>
                                </div>
                                <div class="aicrp-usage-text">
                                    <?php printf(
                                        __('%d / %d requests today', 'ai-content-replacer-pro'),
                                        $provider['used_today'],
                                        $provider['daily_limit']
                                    ); ?>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- Security & Performance -->
        <div class="aicrp-security-performance">
            <div class="aicrp-security-card">
                <h2><?php _e('Security Status', 'ai-content-replacer-pro'); ?></h2>
                <div class="aicrp-security-items">
                    <div class="aicrp-security-item">
                        <span class="aicrp-security-icon success">🔒</span>
                        <span><?php _e('API Keys Encrypted', 'ai-content-replacer-pro'); ?></span>
                    </div>
                    <div class="aicrp-security-item">
                        <span class="aicrp-security-icon success">🛡️</span>
                        <span><?php _e('Input Sanitization Active', 'ai-content-replacer-pro'); ?></span>
                    </div>
                    <div class="aicrp-security-item">
                        <span class="aicrp-security-icon success">⚡</span>
                        <span><?php _e('Rate Limiting Enabled', 'ai-content-replacer-pro'); ?></span>
                    </div>
                </div>
                <a href="<?php echo esc_url(admin_url('admin.php?page=aicrp-testing')); ?>" class="aicrp-btn aicrp-btn-outline">
                    <?php _e('Run Security Tests', 'ai-content-replacer-pro'); ?>
                </a>
            </div>
            
            <div class="aicrp-performance-card">
                <h2><?php _e('Performance Metrics', 'ai-content-replacer-pro'); ?></h2>
                <div class="aicrp-performance-items">
                    <div class="aicrp-performance-item">
                        <span class="aicrp-performance-label"><?php _e('Avg Processing Time', 'ai-content-replacer-pro'); ?></span>
                        <span class="aicrp-performance-value"><?php echo esc_html(number_format($stats['avg_duration'] ?? 0, 1)); ?>s</span>
                    </div>
                    <div class="aicrp-performance-item">
                        <span class="aicrp-performance-label"><?php _e('Memory Usage', 'ai-content-replacer-pro'); ?></span>
                        <span class="aicrp-performance-value"><?php echo esc_html(number_format(memory_get_usage(true) / (1024*1024), 1)); ?>MB</span>
                    </div>
                    <div class="aicrp-performance-item">
                        <span class="aicrp-performance-label"><?php _e('Database Size', 'ai-content-replacer-pro'); ?></span>
                        <span class="aicrp-performance-value"><?php echo esc_html(self::get_database_size()); ?>MB</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Help & Support -->
        <div class="aicrp-help-support">
            <h2><?php _e('Help & Support', 'ai-content-replacer-pro'); ?></h2>
            
            <div class="aicrp-help-grid">
                <div class="aicrp-help-item">
                    <h3><?php _e('📚 Documentation', 'ai-content-replacer-pro'); ?></h3>
                    <p><?php _e('Complete guide on how to use all plugin features', 'ai-content-replacer-pro'); ?></p>
                    <a href="#" class="aicrp-help-link"><?php _e('View Docs', 'ai-content-replacer-pro'); ?></a>
                </div>
                
                <div class="aicrp-help-item">
                    <h3><?php _e('🎥 Video Tutorials', 'ai-content-replacer-pro'); ?></h3>
                    <p><?php _e('Step-by-step video guides for setup and usage', 'ai-content-replacer-pro'); ?></p>
                    <a href="#" class="aicrp-help-link"><?php _e('Watch Videos', 'ai-content-replacer-pro'); ?></a>
                </div>
                
                <div class="aicrp-help-item">
                    <h3><?php _e('💬 Community Forum', 'ai-content-replacer-pro'); ?></h3>
                    <p><?php _e('Get help from other users and developers', 'ai-content-replacer-pro'); ?></p>
                    <a href="#" class="aicrp-help-link"><?php _e('Join Forum', 'ai-content-replacer-pro'); ?></a>
                </div>
                
                <div class="aicrp-help-item">
                    <h3><?php _e('🎫 Premium Support', 'ai-content-replacer-pro'); ?></h3>
                    <p><?php _e('Direct support from our development team', 'ai-content-replacer-pro'); ?></p>
                    <a href="#" class="aicrp-help-link"><?php _e('Contact Support', 'ai-content-replacer-pro'); ?></a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
/**
 * Get database size estimate
 */
function get_database_size() {
    global $wpdb;
    
    $tables = array(
        $wpdb->prefix . 'aicrp_business_profiles',
        $wpdb->prefix . 'aicrp_providers',
        $wpdb->prefix . 'aicrp_processing_history',
        $wpdb->prefix . 'aicrp_security_logs',
        $wpdb->prefix . 'aicrp_analytics'
    );
    
    $total_size = 0;
    
    foreach ($tables as $table) {
        $size = $wpdb->get_var("SELECT ROUND(((data_length + index_length) / 1024 / 1024), 2) AS 'DB Size in MB' FROM information_schema.tables WHERE table_schema = '{$wpdb->dbname}' AND table_name = '{$table}'");
        if ($size) {
            $total_size += floatval($size);
        }
    }
    
    return number_format($total_size, 2);
}
?>