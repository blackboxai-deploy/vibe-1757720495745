<?php
/**
 * AI Providers Configuration Page
 *
 * @package AI_Content_Replacer_Pro
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Handle form submission
if (isset($_POST['aicrp_save_providers']) && wp_verify_nonce($_POST['aicrp_nonce'], 'aicrp_nonce')) {
    $success_count = 0;
    $error_count = 0;
    
    if (isset($_POST['providers']) && is_array($_POST['providers'])) {
        foreach ($_POST['providers'] as $provider_data) {
            if (AICRP_Database::save_api_provider($provider_data)) {
                $success_count++;
            } else {
                $error_count++;
            }
        }
    }
    
    if ($success_count > 0) {
        $success_message = sprintf(__('%d AI provider(s) saved successfully!', 'ai-content-replacer-pro'), $success_count);
    }
    
    if ($error_count > 0) {
        $error_message = sprintf(__('%d AI provider(s) failed to save.', 'ai-content-replacer-pro'), $error_count);
    }
}

// Handle API testing
if (isset($_POST['test_api']) && wp_verify_nonce($_POST['aicrp_nonce'], 'aicrp_nonce')) {
    $provider = sanitize_text_field($_POST['provider']);
    $api_key = sanitize_text_field($_POST['api_key']);
    $model = sanitize_text_field($_POST['model']);
    
    $test_result = AICRP_API_Manager::test_api_connection($provider, $api_key, $model);
    
    if ($test_result['success']) {
        $test_success = sprintf(__('✅ %s API connection successful!', 'ai-content-replacer-pro'), $provider);
    } else {
        $test_error = sprintf(__('❌ %s API test failed: %s', 'ai-content-replacer-pro'), $provider, $test_result['message']);
    }
}

// Get existing providers
$existing_providers = AICRP_Database::get_api_providers();
$supported_providers = AICRP_API_Manager::get_supported_providers();

// Convert to associative array for easier lookup
$providers_by_name = array();
foreach ($existing_providers as $provider) {
    $providers_by_name[$provider['provider_name']] = $provider;
}
?>

<div class="wrap">
    <div class="aicrp-header">
        <h1 class="aicrp-title">
            <span class="aicrp-icon">🤖</span>
            <?php _e('AI Providers Configuration', 'ai-content-replacer-pro'); ?>
        </h1>
        <p class="aicrp-subtitle">
            <?php _e('Configure multiple AI providers for intelligent content generation with automatic failover', 'ai-content-replacer-pro'); ?>
        </p>
    </div>

    <?php if (isset($success_message)): ?>
        <div class="notice notice-success is-dismissible">
            <p><?php echo esc_html($success_message); ?></p>
        </div>
    <?php endif; ?>

    <?php if (isset($error_message)): ?>
        <div class="notice notice-error is-dismissible">
            <p><?php echo esc_html($error_message); ?></p>
        </div>
    <?php endif; ?>

    <?php if (isset($test_success)): ?>
        <div class="notice notice-success is-dismissible">
            <p><?php echo esc_html($test_success); ?></p>
        </div>
    <?php endif; ?>

    <?php if (isset($test_error)): ?>
        <div class="notice notice-error is-dismissible">
            <p><?php echo esc_html($test_error); ?></p>
        </div>
    <?php endif; ?>

    <div class="aicrp-content">
        <!-- Info Card -->
        <div class="aicrp-info-card">
            <h2><?php _e('Smart AI Provider Rotation', 'ai-content-replacer-pro'); ?></h2>
            <div class="aicrp-info-grid">
                <div class="aicrp-info-item">
                    <span class="aicrp-info-icon">🔄</span>
                    <div>
                        <h3><?php _e('Smart Rotation', 'ai-content-replacer-pro'); ?></h3>
                        <p><?php _e('Automatically switches between providers based on availability and limits', 'ai-content-replacer-pro'); ?></p>
                    </div>
                </div>
                
                <div class="aicrp-info-item">
                    <span class="aicrp-info-icon">💰</span>
                    <div>
                        <h3><?php _e('Cost Optimization', 'ai-content-replacer-pro'); ?></h3>
                        <p><?php _e('Uses most cost-effective models first to minimize expenses', 'ai-content-replacer-pro'); ?></p>
                    </div>
                </div>
                
                <div class="aicrp-info-item">
                    <span class="aicrp-info-icon">📊</span>
                    <div>
                        <h3><?php _e('Token Management', 'ai-content-replacer-pro'); ?></h3>
                        <p><?php _e('Tracks usage across all providers and manages daily limits', 'ai-content-replacer-pro'); ?></p>
                    </div>
                </div>
            </div>
        </div>

        <form method="post" action="" class="aicrp-form">
            <?php wp_nonce_field('aicrp_nonce', 'aicrp_nonce'); ?>
            
            <div class="aicrp-providers-grid">
                <?php foreach ($supported_providers as $provider_id => $provider_info): ?>
                    <?php 
                    $existing_provider = $providers_by_name[$provider_id] ?? array(
                        'provider_name' => $provider_id,
                        'api_key' => '',
                        'model' => $provider_info['default_model'],
                        'priority' => 5,
                        'daily_limit' => 1000,
                        'enabled' => 0,
                        'used_today' => 0,
                        'cost_per_token' => 0.002,
                        'status' => 'inactive'
                    );
                    ?>
                    
                    <div class="aicrp-provider-card <?php echo $existing_provider['enabled'] ? 'enabled' : ''; ?>">
                        <div class="aicrp-provider-header">
                            <h3><?php echo esc_html($provider_info['name']); ?></h3>
                            <div class="aicrp-provider-toggle">
                                <label class="aicrp-switch">
                                    <input type="checkbox" 
                                           name="providers[<?php echo esc_attr($provider_id); ?>][enabled]" 
                                           value="1" 
                                           <?php checked($existing_provider['enabled'], 1); ?>>
                                    <span class="aicrp-slider"></span>
                                </label>
                            </div>
                        </div>

                        <div class="aicrp-provider-content">
                            <input type="hidden" name="providers[<?php echo esc_attr($provider_id); ?>][provider_name]" value="<?php echo esc_attr($provider_id); ?>">
                            
                            <div class="aicrp-form-group">
                                <label><?php _e('API Key', 'ai-content-replacer-pro'); ?></label>
                                <div class="aicrp-api-key-input">
                                    <input type="password" 
                                           name="providers[<?php echo esc_attr($provider_id); ?>][api_key]" 
                                           value="<?php echo esc_attr($existing_provider['api_key']); ?>" 
                                           placeholder="<?php _e('Enter your API key', 'ai-content-replacer-pro'); ?>"
                                           class="aicrp-api-key-field">
                                    <button type="button" class="aicrp-btn aicrp-btn-outline aicrp-test-api" 
                                            data-provider="<?php echo esc_attr($provider_id); ?>">
                                        <?php _e('Test', 'ai-content-replacer-pro'); ?>
                                    </button>
                                </div>
                            </div>

                            <div class="aicrp-form-row">
                                <div class="aicrp-form-group">
                                    <label><?php _e('Model', 'ai-content-replacer-pro'); ?></label>
                                    <select name="providers[<?php echo esc_attr($provider_id); ?>][model]">
                                        <?php foreach ($provider_info['models'] as $model): ?>
                                            <option value="<?php echo esc_attr($model); ?>" <?php selected($existing_provider['model'], $model); ?>>
                                                <?php echo esc_html($model); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                
                                <div class="aicrp-form-group">
                                    <label><?php _e('Priority', 'ai-content-replacer-pro'); ?></label>
                                    <select name="providers[<?php echo esc_attr($provider_id); ?>][priority]">
                                        <?php for ($i = 1; $i <= 5; $i++): ?>
                                            <option value="<?php echo $i; ?>" <?php selected($existing_provider['priority'], $i); ?>>
                                                <?php echo $i; ?> <?php echo $i === 1 ? __('(Highest)', 'ai-content-replacer-pro') : ($i === 5 ? __('(Lowest)', 'ai-content-replacer-pro') : ''); ?>
                                            </option>
                                        <?php endfor; ?>
                                    </select>
                                </div>
                            </div>

                            <div class="aicrp-form-group">
                                <label><?php _e('Daily Limit', 'ai-content-replacer-pro'); ?></label>
                                <input type="number" 
                                       name="providers[<?php echo esc_attr($provider_id); ?>][daily_limit]" 
                                       value="<?php echo esc_attr($existing_provider['daily_limit']); ?>" 
                                       min="1" 
                                       max="10000">
                            </div>

                            <?php if ($existing_provider['enabled']): ?>
                                <div class="aicrp-provider-usage">
                                    <div class="aicrp-usage-header">
                                        <span><?php _e('Today\'s Usage', 'ai-content-replacer-pro'); ?></span>
                                        <span><?php echo esc_html($existing_provider['used_today']); ?> / <?php echo esc_html($existing_provider['daily_limit']); ?></span>
                                    </div>
                                    <div class="aicrp-usage-bar">
                                        <div class="aicrp-usage-fill" style="width: <?php echo esc_attr(($existing_provider['used_today'] / $existing_provider['daily_limit']) * 100); ?>%"></div>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- Rotation Strategy -->
            <div class="aicrp-rotation-strategy">
                <h2><?php _e('Smart Rotation Strategy', 'ai-content-replacer-pro'); ?></h2>
                
                <div class="aicrp-strategy-content">
                    <div class="aicrp-strategy-rules">
                        <h3><?php _e('Failover Rules', 'ai-content-replacer-pro'); ?></h3>
                        <ul class="aicrp-rules-list">
                            <li><span class="aicrp-rule-icon blue">●</span> <?php _e('Start with highest priority provider', 'ai-content-replacer-pro'); ?></li>
                            <li><span class="aicrp-rule-icon green">●</span> <?php _e('Switch when daily limit reached', 'ai-content-replacer-pro'); ?></li>
                            <li><span class="aicrp-rule-icon yellow">●</span> <?php _e('Skip providers with API errors', 'ai-content-replacer-pro'); ?></li>
                            <li><span class="aicrp-rule-icon purple">●</span> <?php _e('Consider cost optimization', 'ai-content-replacer-pro'); ?></li>
                        </ul>
                    </div>

                    <div class="aicrp-rotation-order">
                        <h3><?php _e('Current Rotation Order', 'ai-content-replacer-pro'); ?></h3>
                        <div class="aicrp-order-list" id="rotation_order">
                            <?php
                            $enabled_providers = array_filter($existing_providers, function($p) { return $p['enabled']; });
                            usort($enabled_providers, function($a, $b) { return $a['priority'] - $b['priority']; });
                            ?>
                            
                            <?php if (!empty($enabled_providers)): ?>
                                <?php foreach ($enabled_providers as $index => $provider): ?>
                                    <div class="aicrp-order-item">
                                        <span class="aicrp-order-number"><?php echo $index + 1; ?></span>
                                        <span class="aicrp-order-name"><?php echo esc_html($supported_providers[$provider['provider_name']]['name']); ?></span>
                                        <span class="aicrp-order-model">(<?php echo esc_html($provider['model']); ?>)</span>
                                        <span class="aicrp-order-status status-<?php echo esc_attr($provider['status']); ?>">
                                            <?php echo esc_html(ucfirst($provider['status'])); ?>
                                        </span>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <p class="aicrp-empty-state"><?php _e('No active providers configured', 'ai-content-replacer-pro'); ?></p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Save Configuration -->
            <div class="aicrp-form-actions">
                <button type="submit" name="aicrp_save_providers" class="aicrp-btn aicrp-btn-primary aicrp-btn-large">
                    <?php _e('Save AI Configuration', 'ai-content-replacer-pro'); ?>
                </button>
                
                <p class="aicrp-form-note">
                    <?php _e('Your API keys are encrypted and stored securely', 'ai-content-replacer-pro'); ?>
                </p>
            </div>
        </form>

        <!-- Setup Guide -->
        <div class="aicrp-setup-guide">
            <h2><?php _e('🚀 Quick Setup Guide', 'ai-content-replacer-pro'); ?></h2>
            
            <div class="aicrp-guide-steps">
                <div class="aicrp-guide-step">
                    <div class="aicrp-guide-number">1</div>
                    <div class="aicrp-guide-content">
                        <h3><?php _e('Get API Keys', 'ai-content-replacer-pro'); ?></h3>
                        <p><?php _e('Sign up for one or more AI provider accounts:', 'ai-content-replacer-pro'); ?></p>
                        <ul>
                            <li><a href="https://platform.openai.com/api-keys" target="_blank">OpenAI API Keys</a></li>
                            <li><a href="https://console.anthropic.com/" target="_blank">Anthropic Console</a></li>
                            <li><a href="https://aistudio.google.com/app/apikey" target="_blank">Google AI Studio</a></li>
                            <li><a href="https://console.groq.com/keys" target="_blank">Groq API Keys</a></li>
                        </ul>
                    </div>
                </div>
                
                <div class="aicrp-guide-step">
                    <div class="aicrp-guide-number">2</div>
                    <div class="aicrp-guide-content">
                        <h3><?php _e('Configure Providers', 'ai-content-replacer-pro'); ?></h3>
                        <p><?php _e('Enter your API keys, select models, and set priorities for intelligent rotation.', 'ai-content-replacer-pro'); ?></p>
                    </div>
                </div>
                
                <div class="aicrp-guide-step">
                    <div class="aicrp-guide-number">3</div>
                    <div class="aicrp-guide-content">
                        <h3><?php _e('Test Connections', 'ai-content-replacer-pro'); ?></h3>
                        <p><?php _e('Use the "Test" button to verify each API connection before saving.', 'ai-content-replacer-pro'); ?></p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Provider Comparison -->
        <div class="aicrp-provider-comparison">
            <h2><?php _e('AI Provider Comparison', 'ai-content-replacer-pro'); ?></h2>
            
            <div class="aicrp-comparison-table">
                <table class="aicrp-table">
                    <thead>
                        <tr>
                            <th><?php _e('Provider', 'ai-content-replacer-pro'); ?></th>
                            <th><?php _e('Best For', 'ai-content-replacer-pro'); ?></th>
                            <th><?php _e('Speed', 'ai-content-replacer-pro'); ?></th>
                            <th><?php _e('Quality', 'ai-content-replacer-pro'); ?></th>
                            <th><?php _e('Cost', 'ai-content-replacer-pro'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td><strong>OpenAI GPT-4</strong></td>
                            <td><?php _e('High-quality content, complex tasks', 'ai-content-replacer-pro'); ?></td>
                            <td><span class="aicrp-rating">⭐⭐⭐</span></td>
                            <td><span class="aicrp-rating">⭐⭐⭐⭐⭐</span></td>
                            <td><span class="aicrp-rating">⭐⭐</span></td>
                        </tr>
                        <tr>
                            <td><strong>Anthropic Claude</strong></td>
                            <td><?php _e('Long-form content, analysis', 'ai-content-replacer-pro'); ?></td>
                            <td><span class="aicrp-rating">⭐⭐⭐⭐</span></td>
                            <td><span class="aicrp-rating">⭐⭐⭐⭐⭐</span></td>
                            <td><span class="aicrp-rating">⭐⭐⭐</span></td>
                        </tr>
                        <tr>
                            <td><strong>Google Gemini</strong></td>
                            <td><?php _e('General content, multilingual', 'ai-content-replacer-pro'); ?></td>
                            <td><span class="aicrp-rating">⭐⭐⭐⭐</span></td>
                            <td><span class="aicrp-rating">⭐⭐⭐⭐</span></td>
                            <td><span class="aicrp-rating">⭐⭐⭐⭐</span></td>
                        </tr>
                        <tr>
                            <td><strong>Groq</strong></td>
                            <td><?php _e('Fast processing, high volume', 'ai-content-replacer-pro'); ?></td>
                            <td><span class="aicrp-rating">⭐⭐⭐⭐⭐</span></td>
                            <td><span class="aicrp-rating">⭐⭐⭐⭐</span></td>
                            <td><span class="aicrp-rating">⭐⭐⭐⭐⭐</span></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    // Test API functionality
    $('.aicrp-test-api').on('click', function() {
        var $button = $(this);
        var provider = $button.data('provider');
        var $card = $button.closest('.aicrp-provider-card');
        var apiKey = $card.find('.aicrp-api-key-field').val();
        var model = $card.find('select[name*="[model]"]').val();

        if (!apiKey) {
            alert('<?php _e('Please enter an API key first.', 'ai-content-replacer-pro'); ?>');
            return;
        }

        $button.prop('disabled', true).text('<?php _e('Testing...', 'ai-content-replacer-pro'); ?>');

        $.post(ajaxurl, {
            action: 'aicrp_test_api',
            nonce: '<?php echo wp_create_nonce('aicrp_test_api'); ?>',
            provider: provider,
            api_key: apiKey,
            model: model
        }, function(response) {
            if (response.success) {
                alert('✅ ' + response.data.message);
            } else {
                alert('❌ ' + response.data.message);
            }
        }).always(function() {
            $button.prop('disabled', false).text('<?php _e('Test', 'ai-content-replacer-pro'); ?>');
        });
    });

    // Toggle provider cards
    $('input[name*="[enabled]"]').on('change', function() {
        var $card = $(this).closest('.aicrp-provider-card');
        if ($(this).is(':checked')) {
            $card.addClass('enabled');
        } else {
            $card.removeClass('enabled');
        }
        updateRotationOrder();
    });

    // Update priority
    $('select[name*="[priority]"]').on('change', function() {
        updateRotationOrder();
    });

    function updateRotationOrder() {
        var providers = [];
        
        $('.aicrp-provider-card.enabled').each(function() {
            var $card = $(this);
            var name = $card.find('h3').text();
            var priority = parseInt($card.find('select[name*="[priority]"]').val());
            var model = $card.find('select[name*="[model]"]').val();
            
            providers.push({
                name: name,
                priority: priority,
                model: model
            });
        });

        providers.sort(function(a, b) {
            return a.priority - b.priority;
        });

        var orderHtml = '';
        if (providers.length > 0) {
            providers.forEach(function(provider, index) {
                orderHtml += '<div class="aicrp-order-item">' +
                    '<span class="aicrp-order-number">' + (index + 1) + '</span>' +
                    '<span class="aicrp-order-name">' + provider.name + '</span>' +
                    '<span class="aicrp-order-model">(' + provider.model + ')</span>' +
                    '<span class="aicrp-order-status status-active">Active</span>' +
                    '</div>';
            });
        } else {
            orderHtml = '<p class="aicrp-empty-state"><?php _e('No active providers configured', 'ai-content-replacer-pro'); ?></p>';
        }

        $('#rotation_order').html(orderHtml);
    }
});
</script>