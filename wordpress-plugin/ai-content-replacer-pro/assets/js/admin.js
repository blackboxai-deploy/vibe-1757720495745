/**
 * Admin JavaScript for AI Content Replacer Pro
 * Handles interactive functionality in WordPress admin
 */

(function($) {
    'use strict';

    // Initialize when document is ready
    $(document).ready(function() {
        initializeAdminFunctions();
    });

    /**
     * Initialize all admin functions
     */
    function initializeAdminFunctions() {
        initializeFormValidation();
        initializeApiTesting();
        initializeContentProcessing();
        initializeAnalytics();
        initializeTesting();
        initializeKeywordManagement();
        initializeProviderManagement();
    }

    /**
     * Form validation
     */
    function initializeFormValidation() {
        // Business profile form validation
        $('#aicrp-business-profile-form').on('submit', function(e) {
            var isValid = true;
            var errors = [];

            // Check required fields
            var requiredFields = {
                'business_name': 'Business Name',
                'business_type': 'Business Type',
                'description': 'Business Description'
            };

            $.each(requiredFields, function(field, label) {
                var value = $('[name="' + field + '"]').val();
                if (!value || value.trim() === '') {
                    errors.push(label + ' is required');
                    isValid = false;
                }
            });

            // Check description length
            var description = $('[name="description"]').val();
            if (description && description.length < 10) {
                errors.push('Business description must be at least 10 characters');
                isValid = false;
            }

            // Check email format
            var email = $('[name="email"]').val();
            if (email && !isValidEmail(email)) {
                errors.push('Please enter a valid email address');
                isValid = false;
            }

            // Check website URL
            var website = $('[name="website"]').val();
            if (website && !isValidURL(website)) {
                errors.push('Please enter a valid website URL');
                isValid = false;
            }

            if (!isValid) {
                e.preventDefault();
                showValidationErrors(errors);
            }
        });

        // API providers form validation
        $('#aicrp-api-providers-form').on('submit', function(e) {
            var isValid = true;
            var errors = [];
            var hasEnabledProvider = false;

            // Check if at least one provider is enabled with valid API key
            $('.aicrp-provider-card').each(function() {
                var $card = $(this);
                var enabled = $card.find('input[name*="[enabled]"]').is(':checked');
                var apiKey = $card.find('input[name*="[api_key]"]').val();

                if (enabled) {
                    hasEnabledProvider = true;
                    if (!apiKey || apiKey.trim() === '') {
                        var providerName = $card.find('h3').text();
                        errors.push(providerName + ' is enabled but API key is missing');
                        isValid = false;
                    }
                }
            });

            if (!hasEnabledProvider) {
                errors.push('Please enable at least one AI provider');
                isValid = false;
            }

            if (!isValid) {
                e.preventDefault();
                showValidationErrors(errors);
            }
        });
    }

    /**
     * API testing functionality
     */
    function initializeApiTesting() {
        $(document).on('click', '.aicrp-test-api', function() {
            var $button = $(this);
            var $card = $button.closest('.aicrp-provider-card');
            var provider = $button.data('provider');
            var apiKey = $card.find('input[name*="[api_key]"]').val();
            var model = $card.find('select[name*="[model]"]').val();

            if (!apiKey || apiKey.trim() === '') {
                showNotification('Please enter an API key first', 'error');
                return;
            }

            // Show loading state
            $button.prop('disabled', true)
                   .text(aicrp_ajax.strings.processing)
                   .addClass('aicrp-loading');

            // Make AJAX request
            $.ajax({
                url: aicrp_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'aicrp_test_api',
                    nonce: aicrp_ajax.nonce,
                    provider: provider,
                    api_key: apiKey,
                    model: model
                },
                timeout: 30000,
                success: function(response) {
                    if (response.success) {
                        showNotification('✅ ' + response.data.message, 'success');
                        $card.find('.aicrp-provider-status').removeClass('inactive error').addClass('active');
                    } else {
                        showNotification('❌ ' + response.data.message, 'error');
                        $card.find('.aicrp-provider-status').removeClass('active').addClass('error');
                    }
                },
                error: function(xhr, status, error) {
                    showNotification('❌ Connection failed: ' + error, 'error');
                    $card.find('.aicrp-provider-status').removeClass('active').addClass('error');
                },
                complete: function() {
                    $button.prop('disabled', false)
                           .text('Test')
                           .removeClass('aicrp-loading');
                }
            });
        });
    }

    /**
     * Content processing functionality
     */
    function initializeContentProcessing() {
        $('#aicrp-start-processing').on('click', function() {
            var $button = $(this);
            
            if (!confirm(aicrp_ajax.strings.confirm)) {
                return;
            }

            // Collect processing options
            var options = {
                include_pages: $('[name="include_pages"]').is(':checked'),
                include_posts: $('[name="include_posts"]').is(':checked'),
                include_widgets: $('[name="include_widgets"]').is(':checked'),
                preserve_images: $('[name="preserve_images"]').is(':checked'),
                optimize_seo: $('[name="optimize_seo"]').is(':checked'),
                backup_original: $('[name="backup_original"]').is(':checked')
            };

            startContentProcessing($button, options);
        });
    }

    /**
     * Start content processing with progress tracking
     */
    function startContentProcessing($button, options) {
        $button.prop('disabled', true).text('Processing...');
        
        // Show progress container
        var $progressContainer = $('#aicrp-processing-progress');
        $progressContainer.show();
        
        // Initialize progress
        updateProgress(0, 'Initializing content processing...');

        $.ajax({
            url: aicrp_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'aicrp_process_content',
                nonce: aicrp_ajax.nonce,
                options: options
            },
            timeout: 300000, // 5 minutes timeout
            success: function(response) {
                if (response.success) {
                    updateProgress(100, 'Content processing completed!');
                    showProcessingResults(response.data);
                } else {
                    showNotification('❌ Processing failed: ' + response.data.message, 'error');
                }
            },
            error: function(xhr, status, error) {
                showNotification('❌ Processing failed: ' + error, 'error');
            },
            complete: function() {
                $button.prop('disabled', false).text('Start Processing');
                setTimeout(function() {
                    $progressContainer.hide();
                }, 3000);
            }
        });
    }

    /**
     * Analytics functionality
     */
    function initializeAnalytics() {
        // Time range filter
        $('#aicrp-time-range').on('change', function() {
            var timeRange = $(this).val();
            loadAnalyticsData(timeRange);
        });

        // Export functionality
        $('#aicrp-export-analytics').on('click', function() {
            exportAnalyticsData();
        });
    }

    /**
     * Testing functionality
     */
    function initializeTesting() {
        $('#aicrp-run-tests').on('click', function() {
            var $button = $(this);
            
            $button.prop('disabled', true).text('Running Tests...');
            
            // Show progress
            $('#aicrp-testing-progress').show();
            updateTestProgress(0, 'Initializing tests...');

            $.ajax({
                url: aicrp_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'aicrp_run_tests',
                    nonce: aicrp_ajax.nonce
                },
                timeout: 120000, // 2 minutes timeout
                success: function(response) {
                    if (response.success) {
                        updateTestProgress(100, 'Tests completed!');
                        showTestResults(response.data);
                    } else {
                        showNotification('❌ Testing failed', 'error');
                    }
                },
                error: function() {
                    showNotification('❌ Testing failed', 'error');
                },
                complete: function() {
                    $button.prop('disabled', false).text('Run All Tests');
                }
            });
        });
    }

    /**
     * Keyword management
     */
    function initializeKeywordManagement() {
        // Add keyword functionality
        $('#add_keyword').on('click', function() {
            var keyword = $('#keyword_input').val().trim();
            if (keyword && keyword.length > 0) {
                addKeyword(keyword);
                $('#keyword_input').val('');
            }
        });

        // Enter key support
        $('#keyword_input').on('keypress', function(e) {
            if (e.which === 13) {
                e.preventDefault();
                $('#add_keyword').click();
            }
        });

        // Remove keyword
        $(document).on('click', '.aicrp-remove-keyword', function() {
            $(this).parent('.aicrp-keyword-tag').remove();
        });
    }

    /**
     * Provider management
     */
    function initializeProviderManagement() {
        // Toggle provider enable/disable
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
    }

    /**
     * Add keyword to list
     */
    function addKeyword(keyword) {
        // Check if keyword already exists
        var exists = false;
        $('.aicrp-keyword-tag input').each(function() {
            if ($(this).val() === keyword) {
                exists = true;
                return false;
            }
        });

        if (!exists && keyword.length <= 50) {
            var keywordHtml = '<span class="aicrp-keyword-tag">' +
                escapeHtml(keyword) +
                '<input type="hidden" name="keywords[]" value="' + escapeHtml(keyword) + '">' +
                '<button type="button" class="aicrp-remove-keyword">×</button>' +
                '</span>';
            $('#keywords_list').append(keywordHtml);
        } else if (exists) {
            showNotification('Keyword already exists', 'warning');
        } else {
            showNotification('Keyword too long (max 50 characters)', 'warning');
        }
    }

    /**
     * Update rotation order display
     */
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
                    '<span class="aicrp-order-name">' + escapeHtml(provider.name) + '</span>' +
                    '<span class="aicrp-order-model">(' + escapeHtml(provider.model) + ')</span>' +
                    '<span class="aicrp-order-status status-active">Active</span>' +
                    '</div>';
            });
        } else {
            orderHtml = '<p class="aicrp-empty-state">No active providers configured</p>';
        }

        $('#rotation_order').html(orderHtml);
    }

    /**
     * Update progress bar
     */
    function updateProgress(percentage, message) {
        $('#aicrp-progress-bar').css('width', percentage + '%');
        $('#aicrp-progress-text').text(message);
        $('#aicrp-progress-percentage').text(Math.round(percentage) + '%');
    }

    /**
     * Update test progress
     */
    function updateTestProgress(percentage, message) {
        $('#aicrp-test-progress-bar').css('width', percentage + '%');
        $('#aicrp-test-progress-text').text(message);
    }

    /**
     * Show processing results
     */
    function showProcessingResults(results) {
        var message = 'Processing completed!\n\n';
        message += 'Processed: ' + results.processed_count + ' items\n';
        message += 'Failed: ' + results.failed_count + ' items\n';
        message += 'Tokens used: ' + results.total_tokens + '\n';
        message += 'Total cost: $' + results.total_cost.toFixed(4);

        showNotification(message, 'success');
    }

    /**
     * Show test results
     */
    function showTestResults(results) {
        var $resultsContainer = $('#aicrp-test-results');
        
        if (!$resultsContainer.length) {
            $resultsContainer = $('<div id="aicrp-test-results" class="aicrp-test-results"></div>');
            $('#aicrp-testing-progress').after($resultsContainer);
        }

        var overallScore = results.overall.overallScore;
        var scoreClass = overallScore >= 90 ? 'excellent' : (overallScore >= 70 ? 'good' : 'needs-improvement');

        var html = '<div class="aicrp-test-summary">' +
            '<h3>Test Results</h3>' +
            '<div class="aicrp-overall-score ' + scoreClass + '">' +
            '<span class="aicrp-score-number">' + overallScore + '%</span>' +
            '<span class="aicrp-score-label">' + getScoreLabel(overallScore) + '</span>' +
            '</div>' +
            '<div class="aicrp-test-stats">' +
            '<span>Total: ' + results.overall.totalTests + '</span> | ' +
            '<span>Passed: ' + results.overall.totalPassed + '</span> | ' +
            '<span>Failed: ' + results.overall.totalFailed + '</span>' +
            '</div>' +
            '</div>';

        $resultsContainer.html(html).show();
    }

    /**
     * Load analytics data
     */
    function loadAnalyticsData(timeRange) {
        $.ajax({
            url: aicrp_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'aicrp_get_analytics',
                nonce: aicrp_ajax.nonce,
                time_range: timeRange
            },
            success: function(response) {
                if (response.success) {
                    updateAnalyticsDisplay(response.data);
                }
            }
        });
    }

    /**
     * Export analytics data
     */
    function exportAnalyticsData() {
        $.ajax({
            url: aicrp_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'aicrp_export_analytics',
                nonce: aicrp_ajax.nonce
            },
            success: function(response) {
                if (response.success) {
                    // Create download link
                    var blob = new Blob([response.data.csv], { type: 'text/csv' });
                    var url = window.URL.createObjectURL(blob);
                    var a = document.createElement('a');
                    a.href = url;
                    a.download = 'ai-content-replacer-analytics.csv';
                    document.body.appendChild(a);
                    a.click();
                    window.URL.revokeObjectURL(url);
                    document.body.removeChild(a);
                }
            }
        });
    }

    /**
     * Update analytics display
     */
    function updateAnalyticsDisplay(data) {
        // Update stats
        $('#total-pages').text(data.total_pages || 0);
        $('#total-tokens').text(data.total_tokens || 0);
        $('#total-cost').text('$' + (data.total_cost || 0).toFixed(2));
        $('#success-rate').text((data.success_rate || 0).toFixed(1) + '%');
    }

    /**
     * Show notification
     */
    function showNotification(message, type) {
        type = type || 'info';
        
        var $notification = $('<div class="aicrp-notification aicrp-notification-' + type + '">' +
            '<span class="aicrp-notification-message">' + escapeHtml(message) + '</span>' +
            '<button type="button" class="aicrp-notification-close">×</button>' +
            '</div>');

        $('body').append($notification);

        // Auto-hide after 5 seconds
        setTimeout(function() {
            $notification.fadeOut(function() {
                $(this).remove();
            });
        }, 5000);

        // Manual close
        $notification.find('.aicrp-notification-close').on('click', function() {
            $notification.fadeOut(function() {
                $(this).remove();
            });
        });
    }

    /**
     * Show validation errors
     */
    function showValidationErrors(errors) {
        var message = 'Please fix the following errors:\n\n' + errors.join('\n');
        showNotification(message, 'error');
    }

    /**
     * Get score label
     */
    function getScoreLabel(score) {
        if (score >= 90) return 'Excellent';
        if (score >= 70) return 'Good';
        return 'Needs Improvement';
    }

    /**
     * Validate email format
     */
    function isValidEmail(email) {
        var emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return emailRegex.test(email);
    }

    /**
     * Validate URL format
     */
    function isValidURL(url) {
        try {
            new URL(url);
            return true;
        } catch (e) {
            return false;
        }
    }

    /**
     * Escape HTML characters
     */
    function escapeHtml(text) {
        var map = {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        };
        return text.replace(/[&<>"']/g, function(m) { return map[m]; });
    }

    /**
     * Auto-save functionality
     */
    function initializeAutoSave() {
        var autoSaveTimeout;
        
        $('.aicrp-form input, .aicrp-form textarea, .aicrp-form select').on('input change', function() {
            clearTimeout(autoSaveTimeout);
            
            autoSaveTimeout = setTimeout(function() {
                // Auto-save draft
                saveFormDraft();
            }, 2000);
        });
    }

    /**
     * Save form draft
     */
    function saveFormDraft() {
        var formData = {};
        
        $('.aicrp-form input, .aicrp-form textarea, .aicrp-form select').each(function() {
            var name = $(this).attr('name');
            var value = $(this).val();
            
            if (name && value) {
                formData[name] = value;
            }
        });

        localStorage.setItem('aicrp_form_draft', JSON.stringify(formData));
    }

    /**
     * Load form draft
     */
    function loadFormDraft() {
        var draft = localStorage.getItem('aicrp_form_draft');
        
        if (draft) {
            try {
                var formData = JSON.parse(draft);
                
                $.each(formData, function(name, value) {
                    var $field = $('.aicrp-form [name="' + name + '"]');
                    if ($field.length) {
                        $field.val(value);
                    }
                });
            } catch (e) {
                console.log('Failed to load form draft');
            }
        }
    }

    /**
     * Clear form draft
     */
    function clearFormDraft() {
        localStorage.removeItem('aicrp_form_draft');
    }

    // Initialize auto-save and load draft
    initializeAutoSave();
    loadFormDraft();

    // Clear draft on successful form submission
    $('.aicrp-form').on('submit', function() {
        clearFormDraft();
    });

})(jQuery);