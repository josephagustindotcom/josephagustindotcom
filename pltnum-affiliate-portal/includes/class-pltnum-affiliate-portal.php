<?php
/**
 * Affiliate Portal class with enhanced UI and feedback
 */
class PLTNUM_Affiliate_Portal {
    
    public function __construct() {
        $this->init_hooks();
    }
    
    private function init_hooks() {
        add_action('wp_ajax_pltnum_verify_pixel', array($this, 'ajax_verify_pixel'));
        add_action('wp_ajax_pltnum_verify_token', array($this, 'ajax_verify_token'));
        add_action('wp_ajax_pltnum_get_verification_history', array($this, 'ajax_get_verification_history'));
        add_shortcode('pltnum_affiliate_portal', array($this, 'render_affiliate_portal'));
    }
    
    /**
     * Render the affiliate portal shortcode
     */
    public function render_affiliate_portal($atts) {
        if (!is_user_logged_in()) {
            return '<p>' . __('Please log in to access the affiliate portal.', 'pltnum-affiliate-portal') . '</p>';
        }
        
        $user_id = get_current_user_id();
        $pixel_id = get_user_meta($user_id, 'pltnum_pixel_id', true);
        $capi_token = get_user_meta($user_id, 'pltnum_capi_token', true);
        
        ob_start();
        include PLTNUM_PLUGIN_PATH . 'templates/affiliate-portal.php';
        return ob_get_clean();
    }
    
    /**
     * AJAX handler for pixel ID verification
     */
    public function ajax_verify_pixel() {
        check_ajax_referer('pltnum_nonce', 'nonce');
        
        $pixel_id = sanitize_text_field($_POST['pixel_id']);
        $user_id = get_current_user_id();
        
        if (!$user_id) {
            wp_die('Unauthorized access');
        }
        
        $result = $this->verify_pixel_id($pixel_id, $user_id);
        
        // Log the verification attempt
        PLTNUM_Logger::log_verification_attempt($user_id, 'pixel_verification', $result);
        
        wp_send_json($result);
    }
    
    /**
     * AJAX handler for CAPI token verification
     */
    public function ajax_verify_token() {
        check_ajax_referer('pltnum_nonce', 'nonce');
        
        $capi_token = sanitize_text_field($_POST['capi_token']);
        $pixel_id = sanitize_text_field($_POST['pixel_id']);
        $user_id = get_current_user_id();
        
        if (!$user_id) {
            wp_die('Unauthorized access');
        }
        
        $result = $this->verify_capi_token($capi_token, $pixel_id, $user_id);
        
        // Log the verification attempt
        PLTNUM_Logger::log_verification_attempt($user_id, 'token_verification', $result);
        
        wp_send_json($result);
    }
    
    /**
     * AJAX handler for getting verification history
     */
    public function ajax_get_verification_history() {
        check_ajax_referer('pltnum_nonce', 'nonce');
        
        $user_id = get_current_user_id();
        
        if (!$user_id) {
            wp_die('Unauthorized access');
        }
        
        $history = $this->get_verification_history($user_id);
        wp_send_json_success($history);
    }
    
    /**
     * Verify pixel ID with Facebook API
     */
    private function verify_pixel_id($pixel_id, $user_id) {
        // Validate pixel ID format
        if (!preg_match('/^\d{15,16}$/', $pixel_id)) {
            return array(
                'success' => false,
                'message' => __('Invalid Pixel ID format. It should be 15-16 digits.', 'pltnum-affiliate-portal'),
                'error_type' => 'invalid_format',
                'suggestions' => array(
                    __('Check that you copied the full Pixel ID from Facebook Business Manager', 'pltnum-affiliate-portal'),
                    __('Pixel ID should be 15-16 digits only, no letters or special characters', 'pltnum-affiliate-portal')
                )
            );
        }
        
        // Mock Facebook API call for pixel verification
        $api_response = $this->mock_facebook_api_call('pixel', $pixel_id);
        
        if ($api_response['success']) {
            update_user_meta($user_id, 'pltnum_pixel_id', $pixel_id);
            update_user_meta($user_id, 'pltnum_pixel_verified', current_time('mysql'));
            
            return array(
                'success' => true,
                'message' => __('Pixel ID verified successfully!', 'pltnum-affiliate-portal'),
                'suggestions' => array(
                    __('Your Pixel ID is now active and ready for CAPI integration', 'pltnum-affiliate-portal')
                )
            );
        } else {
            return array(
                'success' => false,
                'message' => $api_response['message'],
                'error_type' => $api_response['error_type'],
                'suggestions' => $api_response['suggestions']
            );
        }
    }
    
    /**
     * Verify CAPI token with Facebook API
     */
    private function verify_capi_token($capi_token, $pixel_id, $user_id) {
        // Validate token format
        if (empty($capi_token) || strlen($capi_token) < 50) {
            return array(
                'success' => false,
                'message' => __('Invalid CAPI token format.', 'pltnum-affiliate-portal'),
                'error_type' => 'invalid_format',
                'suggestions' => array(
                    __('Generate a new CAPI token in Facebook Business Manager', 'pltnum-affiliate-portal'),
                    __('Ensure you copy the complete token string', 'pltnum-affiliate-portal')
                )
            );
        }
        
        // Mock Facebook API call for token verification
        $api_response = $this->mock_facebook_api_call('token', $capi_token, $pixel_id);
        
        if ($api_response['success']) {
            update_user_meta($user_id, 'pltnum_capi_token', $capi_token);
            update_user_meta($user_id, 'pltnum_token_verified', current_time('mysql'));
            
            return array(
                'success' => true,
                'message' => __('CAPI token verified successfully!', 'pltnum-affiliate-portal'),
                'suggestions' => array(
                    __('Your CAPI integration is now active and ready to send events', 'pltnum-affiliate-portal')
                )
            );
        } else {
            return array(
                'success' => false,
                'message' => $api_response['message'],
                'error_type' => $api_response['error_type'],
                'suggestions' => $api_response['suggestions']
            );
        }
    }
    
    /**
     * Mock Facebook API call for demonstration
     */
    private function mock_facebook_api_call($type, $credential, $pixel_id = null) {
        // Simulate different response scenarios
        $scenarios = array('success', 'invalid_credential', 'expired_token', 'network_error', 'api_error');
        $scenario = $scenarios[array_rand($scenarios)];
        
        // For demo purposes, let's make it more likely to succeed
        if (rand(1, 10) <= 7) {
            $scenario = 'success';
        }
        
        switch ($scenario) {
            case 'success':
                return array(
                    'success' => true,
                    'message' => __('Verification successful', 'pltnum-affiliate-portal')
                );
                
            case 'invalid_credential':
                return array(
                    'success' => false,
                    'message' => $type === 'pixel' 
                        ? __('Pixel ID not found or access denied', 'pltnum-affiliate-portal')
                        : __('Invalid CAPI token', 'pltnum-affiliate-portal'),
                    'error_type' => 'invalid_credential',
                    'suggestions' => $type === 'pixel' 
                        ? array(
                            __('Verify the Pixel ID in Facebook Business Manager', 'pltnum-affiliate-portal'),
                            __('Ensure you have access to this pixel', 'pltnum-affiliate-portal')
                        )
                        : array(
                            __('Generate a new CAPI token in Facebook Business Manager', 'pltnum-affiliate-portal'),
                            __('Ensure the token has proper permissions', 'pltnum-affiliate-portal')
                        )
                );
                
            case 'expired_token':
                return array(
                    'success' => false,
                    'message' => __('CAPI token has expired', 'pltnum-affiliate-portal'),
                    'error_type' => 'expired_token',
                    'suggestions' => array(
                        __('Generate a new CAPI token in Facebook Business Manager', 'pltnum-affiliate-portal'),
                        __('Set up automatic token rotation for future use', 'pltnum-affiliate-portal')
                    )
                );
                
            case 'network_error':
                return array(
                    'success' => false,
                    'message' => __('Network error occurred while verifying credentials', 'pltnum-affiliate-portal'),
                    'error_type' => 'network_error',
                    'suggestions' => array(
                        __('Check your internet connection and try again', 'pltnum-affiliate-portal'),
                        __('If the problem persists, contact support', 'pltnum-affiliate-portal')
                    )
                );
                
            default:
                return array(
                    'success' => false,
                    'message' => __('Facebook API error occurred', 'pltnum-affiliate-portal'),
                    'error_type' => 'api_error',
                    'suggestions' => array(
                        __('Facebook API may be temporarily unavailable', 'pltnum-affiliate-portal'),
                        __('Try again in a few minutes', 'pltnum-affiliate-portal')
                    )
                );
        }
    }
    
    /**
     * Get verification history for the user
     */
    private function get_verification_history($user_id) {
        $history = get_user_meta($user_id, 'pltnum_verification_history', true);
        
        if (!is_array($history)) {
            $history = array();
        }
        
        // Return last 5 attempts
        return array_slice($history, -5);
    }
}