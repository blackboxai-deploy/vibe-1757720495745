<?php
/**
 * Business Profile Page
 *
 * @package AI_Content_Replacer_Pro
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Handle form submission
if (isset($_POST['aicrp_save_profile']) && wp_verify_nonce($_POST['aicrp_nonce'], 'aicrp_nonce')) {
    $profile_data = AICRP_Security::sanitize_business_profile($_POST);
    
    if (AICRP_Database::save_business_profile($profile_data)) {
        $success_message = __('Business profile saved successfully!', 'ai-content-replacer-pro');
    } else {
        $error_message = __('Failed to save business profile. Please try again.', 'ai-content-replacer-pro');
    }
}

// Get existing profile data
$profile = AICRP_Database::get_business_profile();
if (!$profile) {
    $profile = array(
        'business_name' => '',
        'business_type' => '',
        'description' => '',
        'target_audience' => '',
        'services' => '',
        'location' => '',
        'phone' => '',
        'email' => '',
        'website' => '',
        'tone' => '',
        'keywords' => array(),
        'usp' => ''
    );
}

$business_types = array(
    'restaurant' => __('Restaurant', 'ai-content-replacer-pro'),
    'ecommerce' => __('E-commerce', 'ai-content-replacer-pro'),
    'healthcare' => __('Healthcare', 'ai-content-replacer-pro'),
    'real_estate' => __('Real Estate', 'ai-content-replacer-pro'),
    'technology' => __('Technology', 'ai-content-replacer-pro'),
    'education' => __('Education', 'ai-content-replacer-pro'),
    'legal' => __('Legal Services', 'ai-content-replacer-pro'),
    'fitness' => __('Fitness', 'ai-content-replacer-pro'),
    'beauty' => __('Beauty', 'ai-content-replacer-pro'),
    'consulting' => __('Consulting', 'ai-content-replacer-pro'),
    'manufacturing' => __('Manufacturing', 'ai-content-replacer-pro'),
    'travel' => __('Travel', 'ai-content-replacer-pro'),
    'finance' => __('Finance', 'ai-content-replacer-pro'),
    'nonprofit' => __('Non-Profit', 'ai-content-replacer-pro'),
    'other' => __('Other', 'ai-content-replacer-pro')
);

$tone_options = array(
    'professional' => __('Professional', 'ai-content-replacer-pro'),
    'friendly' => __('Friendly', 'ai-content-replacer-pro'),
    'casual' => __('Casual', 'ai-content-replacer-pro'),
    'authoritative' => __('Authoritative', 'ai-content-replacer-pro'),
    'warm' => __('Warm', 'ai-content-replacer-pro'),
    'modern' => __('Modern', 'ai-content-replacer-pro'),
    'traditional' => __('Traditional', 'ai-content-replacer-pro'),
    'creative' => __('Creative', 'ai-content-replacer-pro'),
    'technical' => __('Technical', 'ai-content-replacer-pro'),
    'conversational' => __('Conversational', 'ai-content-replacer-pro')
);
?>

<div class="wrap">
    <div class="aicrp-header">
        <h1 class="aicrp-title">
            <span class="aicrp-icon">👤</span>
            <?php _e('Business Profile', 'ai-content-replacer-pro'); ?>
        </h1>
        <p class="aicrp-subtitle">
            <?php _e('Configure your business information for personalized AI content generation', 'ai-content-replacer-pro'); ?>
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

    <div class="aicrp-content">
        <form method="post" action="" class="aicrp-form">
            <?php wp_nonce_field('aicrp_nonce', 'aicrp_nonce'); ?>
            
            <div class="aicrp-form-grid">
                <!-- Basic Information -->
                <div class="aicrp-form-section">
                    <h2><?php _e('Basic Information', 'ai-content-replacer-pro'); ?></h2>
                    <p class="aicrp-section-description">
                        <?php _e('Enter your business details to personalize content generation', 'ai-content-replacer-pro'); ?>
                    </p>
                    
                    <div class="aicrp-form-row">
                        <div class="aicrp-form-group">
                            <label for="business_name"><?php _e('Business Name', 'ai-content-replacer-pro'); ?> <span class="required">*</span></label>
                            <input type="text" 
                                   id="business_name" 
                                   name="business_name" 
                                   value="<?php echo esc_attr($profile['business_name']); ?>" 
                                   placeholder="<?php _e('e.g., Digital Marketing Pro', 'ai-content-replacer-pro'); ?>" 
                                   required>
                        </div>
                        
                        <div class="aicrp-form-group">
                            <label for="business_type"><?php _e('Business Type', 'ai-content-replacer-pro'); ?> <span class="required">*</span></label>
                            <select id="business_type" name="business_type" required>
                                <option value=""><?php _e('Select business type', 'ai-content-replacer-pro'); ?></option>
                                <?php foreach ($business_types as $value => $label): ?>
                                    <option value="<?php echo esc_attr($value); ?>" <?php selected($profile['business_type'], $value); ?>>
                                        <?php echo esc_html($label); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="aicrp-form-group">
                        <label for="description"><?php _e('Business Description', 'ai-content-replacer-pro'); ?> <span class="required">*</span></label>
                        <textarea id="description" 
                                  name="description" 
                                  rows="4" 
                                  placeholder="<?php _e('Brief description of your business, what you do, and what makes you unique...', 'ai-content-replacer-pro'); ?>" 
                                  required><?php echo esc_textarea($profile['description']); ?></textarea>
                    </div>

                    <div class="aicrp-form-group">
                        <label for="target_audience"><?php _e('Target Audience', 'ai-content-replacer-pro'); ?></label>
                        <input type="text" 
                               id="target_audience" 
                               name="target_audience" 
                               value="<?php echo esc_attr($profile['target_audience']); ?>" 
                               placeholder="<?php _e('e.g., Small business owners, entrepreneurs, marketing managers', 'ai-content-replacer-pro'); ?>">
                    </div>

                    <div class="aicrp-form-group">
                        <label for="services"><?php _e('Services/Products', 'ai-content-replacer-pro'); ?></label>
                        <textarea id="services" 
                                  name="services" 
                                  rows="3" 
                                  placeholder="<?php _e('List your main services or products...', 'ai-content-replacer-pro'); ?>"><?php echo esc_textarea($profile['services']); ?></textarea>
                    </div>
                </div>

                <!-- Contact Information -->
                <div class="aicrp-form-section">
                    <h2><?php _e('Contact & Brand Details', 'ai-content-replacer-pro'); ?></h2>
                    <p class="aicrp-section-description">
                        <?php _e('Contact information and brand personality settings', 'ai-content-replacer-pro'); ?>
                    </p>
                    
                    <div class="aicrp-form-row">
                        <div class="aicrp-form-group">
                            <label for="location"><?php _e('Location', 'ai-content-replacer-pro'); ?></label>
                            <input type="text" 
                                   id="location" 
                                   name="location" 
                                   value="<?php echo esc_attr($profile['location']); ?>" 
                                   placeholder="<?php _e('e.g., New York, USA', 'ai-content-replacer-pro'); ?>">
                        </div>
                        
                        <div class="aicrp-form-group">
                            <label for="phone"><?php _e('Phone', 'ai-content-replacer-pro'); ?></label>
                            <input type="tel" 
                                   id="phone" 
                                   name="phone" 
                                   value="<?php echo esc_attr($profile['phone']); ?>" 
                                   placeholder="<?php _e('e.g., +1 (555) 123-4567', 'ai-content-replacer-pro'); ?>">
                        </div>
                    </div>

                    <div class="aicrp-form-row">
                        <div class="aicrp-form-group">
                            <label for="email"><?php _e('Email', 'ai-content-replacer-pro'); ?></label>
                            <input type="email" 
                                   id="email" 
                                   name="email" 
                                   value="<?php echo esc_attr($profile['email']); ?>" 
                                   placeholder="<?php _e('e.g., info@yourbusiness.com', 'ai-content-replacer-pro'); ?>">
                        </div>
                        
                        <div class="aicrp-form-group">
                            <label for="website"><?php _e('Website', 'ai-content-replacer-pro'); ?></label>
                            <input type="url" 
                                   id="website" 
                                   name="website" 
                                   value="<?php echo esc_attr($profile['website']); ?>" 
                                   placeholder="<?php _e('e.g., https://yourbusiness.com', 'ai-content-replacer-pro'); ?>">
                        </div>
                    </div>

                    <div class="aicrp-form-group">
                        <label for="tone"><?php _e('Brand Tone', 'ai-content-replacer-pro'); ?></label>
                        <select id="tone" name="tone">
                            <option value=""><?php _e('Select brand tone', 'ai-content-replacer-pro'); ?></option>
                            <?php foreach ($tone_options as $value => $label): ?>
                                <option value="<?php echo esc_attr($value); ?>" <?php selected($profile['tone'], $value); ?>>
                                    <?php echo esc_html($label); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="aicrp-form-group">
                        <label for="keywords"><?php _e('Keywords', 'ai-content-replacer-pro'); ?></label>
                        <div class="aicrp-keywords-input">
                            <input type="text" 
                                   id="keyword_input" 
                                   placeholder="<?php _e('Add keyword and press Enter', 'ai-content-replacer-pro'); ?>">
                            <button type="button" id="add_keyword" class="aicrp-btn aicrp-btn-outline">
                                <?php _e('Add', 'ai-content-replacer-pro'); ?>
                            </button>
                        </div>
                        <div id="keywords_list" class="aicrp-keywords-list">
                            <?php if (!empty($profile['keywords'])): ?>
                                <?php foreach ($profile['keywords'] as $keyword): ?>
                                    <span class="aicrp-keyword-tag">
                                        <?php echo esc_html($keyword); ?>
                                        <input type="hidden" name="keywords[]" value="<?php echo esc_attr($keyword); ?>">
                                        <button type="button" class="aicrp-remove-keyword">×</button>
                                    </span>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="aicrp-form-group">
                        <label for="usp"><?php _e('Unique Selling Proposition', 'ai-content-replacer-pro'); ?></label>
                        <textarea id="usp" 
                                  name="usp" 
                                  rows="3" 
                                  placeholder="<?php _e('What makes your business unique? Why should customers choose you?', 'ai-content-replacer-pro'); ?>"><?php echo esc_textarea($profile['usp']); ?></textarea>
                    </div>
                </div>
            </div>

            <!-- Save Button -->
            <div class="aicrp-form-actions">
                <button type="submit" name="aicrp_save_profile" class="aicrp-btn aicrp-btn-primary aicrp-btn-large">
                    <?php _e('Save Business Profile', 'ai-content-replacer-pro'); ?>
                </button>
                
                <p class="aicrp-form-note">
                    <?php _e('This information will be used to generate personalized content for your website', 'ai-content-replacer-pro'); ?>
                </p>
            </div>
        </form>

        <!-- Profile Preview -->
        <?php if (!empty($profile['business_name'])): ?>
        <div class="aicrp-profile-preview">
            <h2><?php _e('Profile Preview', 'ai-content-replacer-pro'); ?></h2>
            
            <div class="aicrp-preview-card">
                <div class="aicrp-preview-header">
                    <h3><?php echo esc_html($profile['business_name']); ?></h3>
                    <?php if (!empty($profile['business_type'])): ?>
                        <span class="aicrp-business-type"><?php echo esc_html($business_types[$profile['business_type']] ?? $profile['business_type']); ?></span>
                    <?php endif; ?>
                </div>
                
                <?php if (!empty($profile['description'])): ?>
                    <div class="aicrp-preview-description">
                        <p><?php echo esc_html($profile['description']); ?></p>
                    </div>
                <?php endif; ?>
                
                <div class="aicrp-preview-details">
                    <?php if (!empty($profile['location'])): ?>
                        <div class="aicrp-preview-item">
                            <strong><?php _e('Location:', 'ai-content-replacer-pro'); ?></strong>
                            <?php echo esc_html($profile['location']); ?>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($profile['target_audience'])): ?>
                        <div class="aicrp-preview-item">
                            <strong><?php _e('Target Audience:', 'ai-content-replacer-pro'); ?></strong>
                            <?php echo esc_html($profile['target_audience']); ?>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($profile['tone'])): ?>
                        <div class="aicrp-preview-item">
                            <strong><?php _e('Brand Tone:', 'ai-content-replacer-pro'); ?></strong>
                            <?php echo esc_html($tone_options[$profile['tone']] ?? $profile['tone']); ?>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($profile['keywords'])): ?>
                        <div class="aicrp-preview-item">
                            <strong><?php _e('Keywords:', 'ai-content-replacer-pro'); ?></strong>
                            <div class="aicrp-preview-keywords">
                                <?php foreach ($profile['keywords'] as $keyword): ?>
                                    <span class="aicrp-preview-keyword"><?php echo esc_html($keyword); ?></span>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Tips -->
        <div class="aicrp-tips">
            <h2><?php _e('💡 Tips for Better Content Generation', 'ai-content-replacer-pro'); ?></h2>
            
            <div class="aicrp-tips-grid">
                <div class="aicrp-tip">
                    <h3><?php _e('Be Specific', 'ai-content-replacer-pro'); ?></h3>
                    <p><?php _e('The more detailed your business description, the better the AI can understand and generate relevant content.', 'ai-content-replacer-pro'); ?></p>
                </div>
                
                <div class="aicrp-tip">
                    <h3><?php _e('Choose Right Tone', 'ai-content-replacer-pro'); ?></h3>
                    <p><?php _e('Select a tone that matches your brand personality. This will be consistent across all generated content.', 'ai-content-replacer-pro'); ?></p>
                </div>
                
                <div class="aicrp-tip">
                    <h3><?php _e('Add Relevant Keywords', 'ai-content-replacer-pro'); ?></h3>
                    <p><?php _e('Include industry-specific keywords to improve SEO and ensure content relevance.', 'ai-content-replacer-pro'); ?></p>
                </div>
                
                <div class="aicrp-tip">
                    <h3><?php _e('Define Your USP', 'ai-content-replacer-pro'); ?></h3>
                    <p><?php _e('Clearly state what makes you unique. This helps AI create compelling, differentiated content.', 'ai-content-replacer-pro'); ?></p>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    // Keywords management
    $('#add_keyword').on('click', function() {
        var keyword = $('#keyword_input').val().trim();
        if (keyword && keyword.length > 0) {
            addKeyword(keyword);
            $('#keyword_input').val('');
        }
    });

    $('#keyword_input').on('keypress', function(e) {
        if (e.which === 13) { // Enter key
            e.preventDefault();
            $('#add_keyword').click();
        }
    });

    $(document).on('click', '.aicrp-remove-keyword', function() {
        $(this).parent('.aicrp-keyword-tag').remove();
    });

    function addKeyword(keyword) {
        // Check if keyword already exists
        var exists = false;
        $('.aicrp-keyword-tag input').each(function() {
            if ($(this).val() === keyword) {
                exists = true;
                return false;
            }
        });

        if (!exists) {
            var keywordHtml = '<span class="aicrp-keyword-tag">' +
                keyword +
                '<input type="hidden" name="keywords[]" value="' + keyword + '">' +
                '<button type="button" class="aicrp-remove-keyword">×</button>' +
                '</span>';
            $('#keywords_list').append(keywordHtml);
        }
    }

    // Form validation
    $('form.aicrp-form').on('submit', function(e) {
        var businessName = $('#business_name').val().trim();
        var businessType = $('#business_type').val();
        var description = $('#description').val().trim();

        if (!businessName || !businessType || !description) {
            e.preventDefault();
            alert('<?php _e('Please fill in all required fields.', 'ai-content-replacer-pro'); ?>');
            return false;
        }

        if (description.length < 10) {
            e.preventDefault();
            alert('<?php _e('Business description should be at least 10 characters long.', 'ai-content-replacer-pro'); ?>');
            return false;
        }
    });
});
</script>