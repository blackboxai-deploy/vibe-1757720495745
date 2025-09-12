<?php
/**
 * API Manager Class for AI Content Replacer Pro
 * Handles AI provider integrations and API calls
 *
 * @package AI_Content_Replacer_Pro
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class AICRP_API_Manager {
    
    /**
     * Supported AI providers
     */
    private static $supported_providers = array(
        'openai' => array(
            'name' => 'OpenAI',
            'models' => array('gpt-3.5-turbo', 'gpt-4', 'gpt-4-turbo', 'gpt-4o'),
            'endpoint' => 'https://api.openai.com/v1/chat/completions',
            'default_model' => 'gpt-3.5-turbo'
        ),
        'anthropic' => array(
            'name' => 'Anthropic Claude',
            'models' => array('claude-3-haiku-20240307', 'claude-3-sonnet-20240229', 'claude-3-opus-20240229'),
            'endpoint' => 'https://api.anthropic.com/v1/messages',
            'default_model' => 'claude-3-haiku-20240307'
        ),
        'google' => array(
            'name' => 'Google Gemini',
            'models' => array('gemini-pro', 'gemini-pro-vision', 'gemini-1.5-flash'),
            'endpoint' => 'https://generativelanguage.googleapis.com/v1/models/',
            'default_model' => 'gemini-pro'
        ),
        'groq' => array(
            'name' => 'Groq',
            'models' => array('llama3-70b-8192', 'llama3-8b-8192', 'mixtral-8x7b-32768'),
            'endpoint' => 'https://api.groq.com/openai/v1/chat/completions',
            'default_model' => 'llama3-70b-8192'
        )
    );

    /**
     * Save API configuration
     *
     * @param array $config Configuration data
     * @return bool Success status
     */
    public static function save_configuration($config) {
        if (!is_array($config) || empty($config['providers'])) {
            return false;
        }

        $success = true;
        
        foreach ($config['providers'] as $provider_data) {
            $result = AICRP_Database::save_api_provider($provider_data);
            if (!$result) {
                $success = false;
            }
        }

        return $success;
    }

    /**
     * Get available providers
     *
     * @return array Supported providers
     */
    public static function get_supported_providers() {
        return self::$supported_providers;
    }

    /**
     * Test API connection
     *
     * @param string $provider Provider name
     * @param string $api_key API key
     * @param string $model Model name
     * @return array Test results
     */
    public static function test_api_connection($provider, $api_key, $model = '') {
        if (!isset(self::$supported_providers[$provider])) {
            return array(
                'success' => false,
                'message' => __('Unsupported provider', 'ai-content-replacer-pro')
            );
        }

        $provider_config = self::$supported_providers[$provider];
        $test_model = $model ?: $provider_config['default_model'];

        $test_prompt = "Test connection - respond with 'OK' only.";
        
        try {
            $response = self::make_api_call($provider, $api_key, $test_model, $test_prompt);
            
            if ($response['success']) {
                return array(
                    'success' => true,
                    'message' => __('API connection successful', 'ai-content-replacer-pro'),
                    'response_time' => $response['response_time'] ?? 0
                );
            } else {
                return array(
                    'success' => false,
                    'message' => $response['error'] ?? __('Connection failed', 'ai-content-replacer-pro')
                );
            }
        } catch (Exception $e) {
            return array(
                'success' => false,
                'message' => $e->getMessage()
            );
        }
    }

    /**
     * Generate content using AI
     *
     * @param string $prompt Content prompt
     * @param array $business_profile Business profile data
     * @param array $options Processing options
     * @return array Generation results
     */
    public static function generate_content($prompt, $business_profile = array(), $options = array()) {
        // Get available providers in priority order
        $providers = AICRP_Database::get_api_providers();
        $enabled_providers = array_filter($providers, function($provider) {
            return $provider['enabled'] && !empty($provider['api_key']);
        });

        if (empty($enabled_providers)) {
            return array(
                'success' => false,
                'error' => __('No API providers configured', 'ai-content-replacer-pro')
            );
        }

        // Sort by priority
        usort($enabled_providers, function($a, $b) {
            return $a['priority'] - $b['priority'];
        });

        // Build enhanced prompt
        $enhanced_prompt = self::build_enhanced_prompt($prompt, $business_profile, $options);

        // Try each provider until success
        foreach ($enabled_providers as $provider) {
            // Check rate limits
            if ($provider['used_today'] >= $provider['daily_limit']) {
                continue; // Skip provider that hit daily limit
            }

            // Check if rate limit allows request
            $identifier = 'user_' . get_current_user_id() . '_' . $provider['provider_name'];
            if (!AICRP_Security::is_rate_limit_allowed($identifier, 60, 3600)) { // 60 requests per hour
                continue;
            }

            try {
                $start_time = microtime(true);
                
                $response = self::make_api_call(
                    $provider['provider_name'],
                    $provider['api_key'],
                    $provider['model'],
                    $enhanced_prompt
                );
                
                $end_time = microtime(true);
                $duration = round(($end_time - $start_time) * 1000); // Convert to milliseconds

                if ($response['success']) {
                    // Update provider usage
                    $tokens_used = $response['tokens_used'] ?? self::estimate_tokens($enhanced_prompt . $response['content']);
                    AICRP_Database::update_provider_usage($provider['provider_name'], $tokens_used);

                    // Log successful processing
                    AICRP_Database::log_processing_history(array(
                        'processing_type' => 'content_generation',
                        'content_type' => $options['content_type'] ?? 'text',
                        'tokens_used' => $tokens_used,
                        'provider_used' => $provider['provider_name'],
                        'cost' => $tokens_used * $provider['cost_per_token'],
                        'duration_seconds' => round($duration / 1000),
                        'status' => 'completed',
                        'processed_content' => $response['content']
                    ));

                    return array(
                        'success' => true,
                        'content' => $response['content'],
                        'provider_used' => $provider['provider_name'],
                        'tokens_used' => $tokens_used,
                        'cost' => $tokens_used * $provider['cost_per_token'],
                        'duration' => $duration
                    );
                }
            } catch (Exception $e) {
                // Log error and try next provider
                AICRP_Database::log_processing_history(array(
                    'processing_type' => 'content_generation',
                    'content_type' => $options['content_type'] ?? 'text',
                    'provider_used' => $provider['provider_name'],
                    'status' => 'error',
                    'error_message' => $e->getMessage()
                ));
                
                continue;
            }
        }

        return array(
            'success' => false,
            'error' => __('All API providers failed or unavailable', 'ai-content-replacer-pro')
        );
    }

    /**
     * Build enhanced prompt with business context
     *
     * @param string $original_prompt Original prompt
     * @param array $business_profile Business profile
     * @param array $options Options
     * @return string Enhanced prompt
     */
    private static function build_enhanced_prompt($original_prompt, $business_profile, $options) {
        $prompt_parts = array();

        // Add business context
        if (!empty($business_profile)) {
            $prompt_parts[] = "Business Context:";
            $prompt_parts[] = "- Business Name: " . ($business_profile['business_name'] ?? 'Not specified');
            $prompt_parts[] = "- Business Type: " . ($business_profile['business_type'] ?? 'Not specified');
            $prompt_parts[] = "- Description: " . ($business_profile['description'] ?? 'Not specified');
            $prompt_parts[] = "- Target Audience: " . ($business_profile['target_audience'] ?? 'General audience');
            $prompt_parts[] = "- Brand Tone: " . ($business_profile['tone'] ?? 'Professional');
            
            if (!empty($business_profile['location'])) {
                $prompt_parts[] = "- Location: " . $business_profile['location'];
            }
            
            if (!empty($business_profile['keywords']) && is_array($business_profile['keywords'])) {
                $prompt_parts[] = "- Keywords to include: " . implode(', ', array_slice($business_profile['keywords'], 0, 5));
            }
            
            $prompt_parts[] = "";
        }

        // Add content requirements
        $prompt_parts[] = "Content Requirements:";
        $prompt_parts[] = "- Write in a " . ($business_profile['tone'] ?? 'professional') . " tone";
        $prompt_parts[] = "- Keep the same structure and format as the original";
        $prompt_parts[] = "- Make content relevant to the business and target audience";
        $prompt_parts[] = "- Ensure content is engaging and informative";
        $prompt_parts[] = "- Do not include placeholder text or generic examples";
        
        if (!empty($options['max_words'])) {
            $prompt_parts[] = "- Keep content under " . intval($options['max_words']) . " words";
        }
        
        $prompt_parts[] = "";

        // Add original prompt
        $prompt_parts[] = "Task: " . $original_prompt;

        return implode("\n", $prompt_parts);
    }

    /**
     * Make API call to specified provider
     *
     * @param string $provider Provider name
     * @param string $api_key API key
     * @param string $model Model name
     * @param string $prompt Prompt text
     * @return array API response
     */
    private static function make_api_call($provider, $api_key, $model, $prompt) {
        if (!isset(self::$supported_providers[$provider])) {
            throw new Exception('Unsupported provider: ' . $provider);
        }

        $start_time = microtime(true);

        switch ($provider) {
            case 'openai':
                $response = self::call_openai_api($api_key, $model, $prompt);
                break;
            case 'anthropic':
                $response = self::call_anthropic_api($api_key, $model, $prompt);
                break;
            case 'google':
                $response = self::call_google_api($api_key, $model, $prompt);
                break;
            case 'groq':
                $response = self::call_groq_api($api_key, $model, $prompt);
                break;
            default:
                throw new Exception('Provider not implemented: ' . $provider);
        }

        $end_time = microtime(true);
        $response['response_time'] = round(($end_time - $start_time) * 1000);

        return $response;
    }

    /**
     * Call OpenAI API
     *
     * @param string $api_key API key
     * @param string $model Model name
     * @param string $prompt Prompt text
     * @return array API response
     */
    private static function call_openai_api($api_key, $model, $prompt) {
        $endpoint = 'https://api.openai.com/v1/chat/completions';
        
        $headers = array(
            'Authorization: Bearer ' . $api_key,
            'Content-Type: application/json'
        );

        $data = array(
            'model' => $model,
            'messages' => array(
                array(
                    'role' => 'user',
                    'content' => $prompt
                )
            ),
            'max_tokens' => 2000,
            'temperature' => 0.7
        );

        $response = self::make_http_request($endpoint, $headers, $data);
        
        if ($response['success'] && isset($response['data']['choices'][0]['message']['content'])) {
            return array(
                'success' => true,
                'content' => trim($response['data']['choices'][0]['message']['content']),
                'tokens_used' => $response['data']['usage']['total_tokens'] ?? 0
            );
        }

        return array(
            'success' => false,
            'error' => $response['error'] ?? 'Unknown error'
        );
    }

    /**
     * Call Anthropic API
     *
     * @param string $api_key API key
     * @param string $model Model name
     * @param string $prompt Prompt text
     * @return array API response
     */
    private static function call_anthropic_api($api_key, $model, $prompt) {
        $endpoint = 'https://api.anthropic.com/v1/messages';
        
        $headers = array(
            'x-api-key: ' . $api_key,
            'Content-Type: application/json',
            'anthropic-version: 2023-06-01'
        );

        $data = array(
            'model' => $model,
            'max_tokens' => 2000,
            'messages' => array(
                array(
                    'role' => 'user',
                    'content' => $prompt
                )
            )
        );

        $response = self::make_http_request($endpoint, $headers, $data);
        
        if ($response['success'] && isset($response['data']['content'][0]['text'])) {
            return array(
                'success' => true,
                'content' => trim($response['data']['content'][0]['text']),
                'tokens_used' => $response['data']['usage']['input_tokens'] + $response['data']['usage']['output_tokens']
            );
        }

        return array(
            'success' => false,
            'error' => $response['error'] ?? 'Unknown error'
        );
    }

    /**
     * Call Google Gemini API
     *
     * @param string $api_key API key
     * @param string $model Model name
     * @param string $prompt Prompt text
     * @return array API response
     */
    private static function call_google_api($api_key, $model, $prompt) {
        $endpoint = "https://generativelanguage.googleapis.com/v1/models/{$model}:generateContent?key={$api_key}";
        
        $headers = array(
            'Content-Type: application/json'
        );

        $data = array(
            'contents' => array(
                array(
                    'parts' => array(
                        array('text' => $prompt)
                    )
                )
            ),
            'generationConfig' => array(
                'maxOutputTokens' => 2000,
                'temperature' => 0.7
            )
        );

        $response = self::make_http_request($endpoint, $headers, $data);
        
        if ($response['success'] && isset($response['data']['candidates'][0]['content']['parts'][0]['text'])) {
            return array(
                'success' => true,
                'content' => trim($response['data']['candidates'][0]['content']['parts'][0]['text']),
                'tokens_used' => self::estimate_tokens($prompt . $response['data']['candidates'][0]['content']['parts'][0]['text'])
            );
        }

        return array(
            'success' => false,
            'error' => $response['error'] ?? 'Unknown error'
        );
    }

    /**
     * Call Groq API
     *
     * @param string $api_key API key
     * @param string $model Model name
     * @param string $prompt Prompt text
     * @return array API response
     */
    private static function call_groq_api($api_key, $model, $prompt) {
        $endpoint = 'https://api.groq.com/openai/v1/chat/completions';
        
        $headers = array(
            'Authorization: Bearer ' . $api_key,
            'Content-Type: application/json'
        );

        $data = array(
            'model' => $model,
            'messages' => array(
                array(
                    'role' => 'user',
                    'content' => $prompt
                )
            ),
            'max_tokens' => 2000,
            'temperature' => 0.7
        );

        $response = self::make_http_request($endpoint, $headers, $data);
        
        if ($response['success'] && isset($response['data']['choices'][0]['message']['content'])) {
            return array(
                'success' => true,
                'content' => trim($response['data']['choices'][0]['message']['content']),
                'tokens_used' => $response['data']['usage']['total_tokens'] ?? 0
            );
        }

        return array(
            'success' => false,
            'error' => $response['error'] ?? 'Unknown error'
        );
    }

    /**
     * Make HTTP request
     *
     * @param string $url URL
     * @param array $headers Headers
     * @param array $data POST data
     * @param int $timeout Timeout in seconds
     * @return array Response
     */
    private static function make_http_request($url, $headers, $data, $timeout = 30) {
        $args = array(
            'method' => 'POST',
            'headers' => $headers,
            'body' => wp_json_encode($data),
            'timeout' => $timeout,
            'sslverify' => true
        );

        $response = wp_remote_request($url, $args);
        
        if (is_wp_error($response)) {
            return array(
                'success' => false,
                'error' => $response->get_error_message()
            );
        }

        $response_code = wp_remote_retrieve_response_code($response);
        $response_body = wp_remote_retrieve_body($response);

        if ($response_code !== 200) {
            $error_data = json_decode($response_body, true);
            $error_message = 'HTTP ' . $response_code;
            
            if (isset($error_data['error']['message'])) {
                $error_message .= ': ' . $error_data['error']['message'];
            } elseif (isset($error_data['message'])) {
                $error_message .= ': ' . $error_data['message'];
            }

            return array(
                'success' => false,
                'error' => $error_message
            );
        }

        $decoded_response = json_decode($response_body, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            return array(
                'success' => false,
                'error' => 'Invalid JSON response'
            );
        }

        return array(
            'success' => true,
            'data' => $decoded_response
        );
    }

    /**
     * Estimate token count for text
     *
     * @param string $text Text to estimate
     * @return int Estimated token count
     */
    private static function estimate_tokens($text) {
        // Rough estimation: 1 token ≈ 4 characters for English text
        return max(1, intval(strlen($text) / 4));
    }

    /**
     * Get provider statistics
     *
     * @param int $user_id User ID
     * @return array Provider statistics
     */
    public static function get_provider_statistics($user_id = null) {
        if (!$user_id) {
            $user_id = get_current_user_id();
        }

        $providers = AICRP_Database::get_api_providers($user_id);
        $statistics = array();

        foreach ($providers as $provider) {
            $statistics[$provider['provider_name']] = array(
                'name' => self::$supported_providers[$provider['provider_name']]['name'] ?? $provider['provider_name'],
                'enabled' => $provider['enabled'],
                'daily_limit' => $provider['daily_limit'],
                'used_today' => $provider['used_today'],
                'remaining_today' => max(0, $provider['daily_limit'] - $provider['used_today']),
                'cost_per_token' => $provider['cost_per_token'],
                'priority' => $provider['priority'],
                'status' => $provider['status']
            );
        }

        return $statistics;
    }
}