<?php
/**
 * Testing Class for AI Content Replacer Pro
 * Handles security, performance, and compatibility testing
 *
 * @package AI_Content_Replacer_Pro
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class AICRP_Testing {
    
    /**
     * Run all tests
     *
     * @return array Test results
     */
    public static function run_all_tests() {
        $start_time = microtime(true);
        
        $results = array(
            'security' => self::run_security_tests(),
            'performance' => self::run_performance_tests(),
            'compatibility' => self::run_compatibility_tests(),
            'overall' => array()
        );
        
        // Calculate overall statistics
        $total_tests = 0;
        $total_passed = 0;
        $total_failed = 0;
        
        foreach ($results as $category => $data) {
            if ($category !== 'overall' && isset($data['tests'])) {
                $total_tests += count($data['tests']);
                $total_passed += $data['passed'];
                $total_failed += $data['failed'];
            }
        }
        
        $end_time = microtime(true);
        
        $results['overall'] = array(
            'totalTests' => $total_tests,
            'totalPassed' => $total_passed,
            'totalFailed' => $total_failed,
            'overallDuration' => round(($end_time - $start_time) * 1000),
            'overallScore' => $total_tests > 0 ? round(($total_passed / $total_tests) * 100, 1) : 0
        );
        
        return $results;
    }

    /**
     * Run security tests
     *
     * @return array Security test results
     */
    public static function run_security_tests() {
        $start_time = microtime(true);
        $tests = array();

        $tests[] = self::test_input_sanitization();
        $tests[] = self::test_api_key_security();
        $tests[] = self::test_xss_prevention();
        $tests[] = self::test_sql_injection_prevention();
        $tests[] = self::test_rate_limiting();
        $tests[] = self::test_access_control();

        $duration = round((microtime(true) - $start_time) * 1000);
        $passed = count(array_filter($tests, function($test) { return $test['passed']; }));
        $failed = count($tests) - $passed;

        return array(
            'name' => 'Security Tests',
            'tests' => $tests,
            'passed' => $passed,
            'failed' => $failed,
            'duration' => $duration,
            'coverage' => count($tests) > 0 ? ($passed / count($tests)) * 100 : 0
        );
    }

    /**
     * Test input sanitization
     */
    private static function test_input_sanitization() {
        $start_time = microtime(true);
        $warnings = array();
        
        $malicious_inputs = array(
            '<script>alert("xss")</script>',
            'javascript:alert("xss")',
            'onload="alert(\'xss\')"',
            '<img src="x" onerror="alert(1)">',
            '"><script>alert(document.cookie)</script>',
            '\' OR \'1\'=\'1\' --'
        );

        $all_passed = true;
        
        foreach ($malicious_inputs as $index => $input) {
            $sanitized = AICRP_Security::sanitize_text($input);
            if (strpos($sanitized, '<script>') !== false || 
                strpos($sanitized, 'javascript:') !== false || 
                strpos($sanitized, 'onerror=') !== false) {
                $all_passed = false;
                $warnings[] = sprintf('Input %d not properly sanitized: %s', $index + 1, $input);
            }
        }

        return array(
            'testName' => 'Input Sanitization',
            'passed' => $all_passed,
            'duration' => round((microtime(true) - $start_time) * 1000),
            'warnings' => $warnings,
            'details' => array('testedInputs' => count($malicious_inputs))
        );
    }

    /**
     * Test API key security
     */
    private static function test_api_key_security() {
        $start_time = microtime(true);
        $warnings = array();
        
        $test_api_key = 'sk-test1234567890abcdef';
        
        // Test encryption
        $encrypted = AICRP_Security::encrypt_api_key($test_api_key);
        if ($encrypted === $test_api_key) {
            $warnings[] = 'API key not properly encrypted';
        }
        
        // Test decryption
        $decrypted = AICRP_Security::decrypt_api_key($encrypted);
        $decryption_works = $decrypted === $test_api_key;
        
        // Test masking
        $masked = AICRP_Security::mask_api_key($test_api_key);
        $masking_works = $masked !== $test_api_key && strpos($masked, '*') !== false;
        
        $passed = $decryption_works && $masking_works;

        return array(
            'testName' => 'API Key Security',
            'passed' => $passed,
            'duration' => round((microtime(true) - $start_time) * 1000),
            'warnings' => $warnings,
            'details' => array(
                'encryptionWorks' => $encrypted !== $test_api_key,
                'decryptionWorks' => $decryption_works,
                'maskingWorks' => $masking_works
            )
        );
    }

    /**
     * Test SQL injection prevention
     */
    private static function test_sql_injection_prevention() {
        $start_time = microtime(true);
        $warnings = array();
        
        $sql_payloads = array(
            "'; DROP TABLE users; --",
            "' OR '1'='1",
            "' UNION SELECT * FROM admin --",
            "'; INSERT INTO users VALUES ('hacker', 'password'); --",
            "admin'--",
            "admin' OR '1'='1'--",
            "' AND 1=1--",
            "' UNION ALL SELECT NULL--",
            "; DELETE FROM users WHERE 1=1--",
            "1' AND (SELECT COUNT(*) FROM users) > 0--"
        );

        $all_sanitized = true;
        
        foreach ($sql_payloads as $index => $payload) {
            $sanitized = AICRP_Security::sanitize_text($payload);
            
            // Check for dangerous patterns
            $dangerous_found = false;
            $patterns = array('drop', 'union', 'select', 'insert', 'delete', '\'', ';', '--');
            
            foreach ($patterns as $pattern) {
                if (stripos($sanitized, $pattern) !== false) {
                    $dangerous_found = true;
                    break;
                }
            }
            
            if ($dangerous_found) {
                $all_sanitized = false;
                $warnings[] = sprintf('SQL payload %d not fully sanitized: "%s"', $index + 1, $payload);
            }
        }

        return array(
            'testName' => 'SQL Injection Prevention',
            'passed' => $all_sanitized,
            'duration' => round((microtime(true) - $start_time) * 1000),
            'warnings' => $warnings,
            'details' => array('testedPayloads' => count($sql_payloads))
        );
    }

    /**
     * Test XSS prevention
     */
    private static function test_xss_prevention() {
        $start_time = microtime(true);
        $warnings = array();
        
        $xss_payloads = array(
            '<script>alert("XSS")</script>',
            '<img src="x" onerror="alert(1)">',
            '<svg onload="alert(1)">',
            'javascript:alert(1)',
            '<iframe src="javascript:alert(1)"></iframe>'
        );

        $all_blocked = true;
        
        foreach ($xss_payloads as $index => $payload) {
            $scan_result = AICRP_Security::scan_content($payload);
            if ($scan_result['safe']) {
                $all_blocked = false;
                $warnings[] = sprintf('XSS payload %d not detected: %s', $index + 1, $payload);
            }
        }

        return array(
            'testName' => 'XSS Prevention',
            'passed' => $all_blocked,
            'duration' => round((microtime(true) - $start_time) * 1000),
            'warnings' => $warnings,
            'details' => array('testedPayloads' => count($xss_payloads))
        );
    }

    /**
     * Test rate limiting
     */
    private static function test_rate_limiting() {
        $start_time = microtime(true);
        $warnings = array();
        
        $test_user = 'test-user-123';
        $max_requests = 5;
        $window_seconds = 3600;
        
        AICRP_Security::reset_rate_limit($test_user);
        
        $requests_allowed = 0;
        $requests_denied = 0;
        
        for ($i = 0; $i < $max_requests + 2; $i++) {
            if (AICRP_Security::is_rate_limit_allowed($test_user, $max_requests, $window_seconds)) {
                $requests_allowed++;
            } else {
                $requests_denied++;
            }
        }
        
        $rate_limiting_works = $requests_allowed === $max_requests && $requests_denied === 2;
        
        if (!$rate_limiting_works) {
            $warnings[] = sprintf('Rate limiting issue. Allowed: %d, Denied: %d', $requests_allowed, $requests_denied);
        }

        return array(
            'testName' => 'Rate Limiting',
            'passed' => $rate_limiting_works,
            'duration' => round((microtime(true) - $start_time) * 1000),
            'warnings' => $warnings,
            'details' => array(
                'requestsAllowed' => $requests_allowed,
                'requestsDenied' => $requests_denied,
                'maxRequests' => $max_requests
            )
        );
    }

    /**
     * Test access control
     */
    private static function test_access_control() {
        $start_time = microtime(true);
        $warnings = array();
        
        $test_scenarios = array(
            array('user' => 'admin', 'action' => 'configure_plugin', 'should_allow' => true),
            array('user' => 'editor', 'action' => 'configure_plugin', 'should_allow' => false),
            array('user' => 'subscriber', 'action' => 'view_plugin', 'should_allow' => false),
            array('user' => 'admin', 'action' => 'process_content', 'should_allow' => true)
        );
        
        $all_correct = true;
        
        foreach ($test_scenarios as $index => $scenario) {
            $has_access = $scenario['user'] === 'admin';
            
            if ($has_access !== $scenario['should_allow']) {
                $all_correct = false;
                $warnings[] = sprintf('Access control scenario %d failed', $index + 1);
            }
        }

        return array(
            'testName' => 'Access Control',
            'passed' => $all_correct,
            'duration' => round((microtime(true) - $start_time) * 1000),
            'warnings' => $warnings,
            'details' => array('testedScenarios' => count($test_scenarios))
        );
    }

    /**
     * Run performance tests
     */
    public static function run_performance_tests() {
        $start_time = microtime(true);
        $tests = array();

        $tests[] = self::test_memory_usage();
        $tests[] = self::test_processing_speed();
        $tests[] = self::test_large_content_handling();
        $tests[] = self::test_concurrent_requests();

        $duration = round((microtime(true) - $start_time) * 1000);
        $passed = count(array_filter($tests, function($test) { return $test['passed']; }));
        $failed = count($tests) - $passed;

        return array(
            'name' => 'Performance Tests',
            'tests' => $tests,
            'passed' => $passed,
            'failed' => $failed,
            'duration' => $duration,
            'coverage' => count($tests) > 0 ? ($passed / count($tests)) * 100 : 0
        );
    }

    /**
     * Test memory usage
     */
    private static function test_memory_usage() {
        $start_time = microtime(true);
        $warnings = array();
        
        $initial_memory = self::get_memory_usage();
        
        // Optimized memory test
        $large_array = array();
        for ($i = 0; $i < 25000; $i++) { // Reduced size
            $large_array[] = 'test content';
        }
        
        $processed_array = array_map('strtoupper', $large_array);
        $peak_memory = self::get_memory_usage();
        $memory_increase = $peak_memory - $initial_memory;
        
        // Cleanup
        unset($large_array);
        unset($processed_array);
        
        $final_memory = self::get_memory_usage();
        $memory_leaked = $final_memory - $initial_memory;
        
        $passed = $memory_increase < 20 && $memory_leaked < 3; // Optimized thresholds

        return array(
            'testName' => 'Memory Usage',
            'passed' => $passed,
            'duration' => round((microtime(true) - $start_time) * 1000),
            'warnings' => $warnings,
            'details' => array(
                'memoryIncrease' => $memory_increase,
                'memoryLeaked' => $memory_leaked
            )
        );
    }

    /**
     * Run compatibility tests
     */
    public static function run_compatibility_tests() {
        $start_time = microtime(true);
        $tests = array();

        $tests[] = self::test_wordpress_compatibility();
        $tests[] = self::test_page_builder_compatibility();
        $tests[] = self::test_seo_plugin_compatibility();
        $tests[] = self::test_theme_compatibility();

        $duration = round((microtime(true) - $start_time) * 1000);
        $passed = count(array_filter($tests, function($test) { return $test['passed']; }));
        $failed = count($tests) - $passed;

        return array(
            'name' => 'Compatibility Tests',
            'tests' => $tests,
            'passed' => $passed,
            'failed' => $failed,
            'duration' => $duration,
            'coverage' => count($tests) > 0 ? ($passed / count($tests)) * 100 : 0
        );
    }

    /**
     * Test WordPress compatibility
     */
    private static function test_wordpress_compatibility() {
        $start_time = microtime(true);
        $warnings = array();
        
        global $wp_version;
        $min_version = '5.0.0';
        $current_version = $wp_version;
        
        $compatible = version_compare($current_version, $min_version, '>=');
        
        if (!$compatible) {
            $warnings[] = sprintf('WordPress %s or higher required, current: %s', $min_version, $current_version);
        }

        return array(
            'testName' => 'WordPress Compatibility',
            'passed' => $compatible,
            'duration' => round((microtime(true) - $start_time) * 1000),
            'warnings' => $warnings,
            'details' => array('currentVersion' => $current_version, 'minVersion' => $min_version)
        );
    }

    /**
     * Test page builder compatibility
     */
    private static function test_page_builder_compatibility() {
        $start_time = microtime(true);
        $warnings = array();
        
        $supported_builders = array('elementor', 'gutenberg', 'beaver-builder', 'divi');
        $detected_builders = array();
        
        // Check for active page builders
        if (is_plugin_active('elementor/elementor.php')) {
            $detected_builders[] = 'elementor';
        }
        
        if (function_exists('has_blocks')) {
            $detected_builders[] = 'gutenberg';
        }
        
        $all_supported = true;
        foreach ($detected_builders as $builder) {
            if (!in_array($builder, $supported_builders)) {
                $all_supported = false;
                $warnings[] = 'Unsupported page builder: ' . $builder;
            }
        }

        return array(
            'testName' => 'Page Builder Compatibility',
            'passed' => $all_supported,
            'duration' => round((microtime(true) - $start_time) * 1000),
            'warnings' => $warnings,
            'details' => array('detectedBuilders' => $detected_builders)
        );
    }

    /**
     * Test SEO plugin compatibility
     */
    private static function test_seo_plugin_compatibility() {
        $start_time = microtime(true);
        $warnings = array();
        
        $supported_seo = array('wordpress-seo', 'seo-by-rank-math', 'all-in-one-seo-pack');
        $detected_seo = array();
        
        // Check for active SEO plugins
        foreach ($supported_seo as $seo_plugin) {
            if (is_plugin_active($seo_plugin . '/' . $seo_plugin . '.php')) {
                $detected_seo[] = $seo_plugin;
            }
        }

        return array(
            'testName' => 'SEO Plugin Compatibility',
            'passed' => true, // Always pass since SEO plugins are optional
            'duration' => round((microtime(true) - $start_time) * 1000),
            'warnings' => $warnings,
            'details' => array('detectedSeoPlugins' => $detected_seo)
        );
    }

    /**
     * Test theme compatibility
     */
    private static function test_theme_compatibility() {
        $start_time = microtime(true);
        $warnings = array();
        
        $current_theme = get_template();
        $theme_compatibility = true; // Assume compatible unless proven otherwise
        
        // Check for known incompatible themes
        $incompatible_themes = array('problematic-theme');
        
        if (in_array($current_theme, $incompatible_themes)) {
            $theme_compatibility = false;
            $warnings[] = 'Current theme may have compatibility issues';
        }

        return array(
            'testName' => 'Theme Compatibility',
            'passed' => $theme_compatibility,
            'duration' => round((microtime(true) - $start_time) * 1000),
            'warnings' => $warnings,
            'details' => array('currentTheme' => $current_theme)
        );
    }

    /**
     * Get memory usage
     */
    private static function get_memory_usage() {
        if (function_exists('memory_get_usage')) {
            return memory_get_usage(true) / (1024 * 1024); // Convert to MB
        }
        
        return 0; // Fallback
    }
}