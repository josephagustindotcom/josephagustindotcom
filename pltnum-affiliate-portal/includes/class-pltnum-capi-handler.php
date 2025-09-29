<?php
/**
 * Facebook Conversions API Handler
 */
class PLTNUM_CAPI_Handler {
    
    public function __construct() {
        $this->init_hooks();
    }
    
    private function init_hooks() {
        // Hook into order completion or other relevant events
        add_action('woocommerce_order_status_completed', array($this, 'send_purchase_event'));
        add_action('wp_ajax_pltnum_test_capi_event', array($this, 'ajax_test_capi_event'));
        add_action('wp_ajax_nopriv_pltnum_test_capi_event', array($this, 'ajax_test_capi_event'));
    }
    
    /**
     * Send purchase event to Facebook CAPI
     */
    public function send_purchase_event($order_id) {
        if (!class_exists('WooCommerce')) {
            return;
        }
        
        $order = wc_get_order($order_id);
        if (!$order) {
            return;
        }
        
        // Get affiliate ID (this would depend on your affiliate system)
        $affiliate_id = $this->get_affiliate_id_for_order($order);
        
        if (!$affiliate_id) {
            return;
        }
        
        $pixel_id = get_user_meta($affiliate_id, 'pltnum_pixel_id', true);
        $capi_token = get_user_meta($affiliate_id, 'pltnum_capi_token', true);
        
        if (!$pixel_id || !$capi_token) {
            PLTNUM_Logger::log_capi_event($affiliate_id, $order_id, 'purchase', 'error', 'missing_credentials', 'Missing pixel ID or CAPI token');
            return;
        }
        
        $event_data = $this->prepare_purchase_event_data($order);
        $this->send_event_to_facebook($affiliate_id, $order_id, $pixel_id, $capi_token, $event_data);
    }
    
    /**
     * AJAX handler for testing CAPI events
     */
    public function ajax_test_capi_event() {
        check_ajax_referer('pltnum_admin_nonce', 'nonce');
        
        $affiliate_id = intval($_POST['affiliate_id']);
        $event_type = sanitize_text_field($_POST['event_type']);
        
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized access');
        }
        
        $pixel_id = get_user_meta($affiliate_id, 'pltnum_pixel_id', true);
        $capi_token = get_user_meta($affiliate_id, 'pltnum_capi_token', true);
        
        if (!$pixel_id || !$capi_token) {
            wp_send_json_error('Missing credentials for this affiliate');
        }
        
        $test_event_data = $this->prepare_test_event_data($event_type);
        $result = $this->send_event_to_facebook($affiliate_id, 'test_' . time(), $pixel_id, $capi_token, $test_event_data);
        
        wp_send_json($result);
    }
    
    /**
     * Get affiliate ID for an order (mock implementation)
     */
    private function get_affiliate_id_for_order($order) {
        // This would depend on your affiliate tracking system
        // For demo purposes, we'll use a meta field or the customer ID
        $affiliate_id = $order->get_meta('_pltnum_affiliate_id');
        
        if (!$affiliate_id) {
            $affiliate_id = $order->get_customer_id();
        }
        
        return $affiliate_id;
    }
    
    /**
     * Prepare purchase event data for Facebook CAPI
     */
    private function prepare_purchase_event_data($order) {
        $event_data = array(
            'event_name' => 'Purchase',
            'event_time' => time(),
            'event_id' => 'order_' . $order->get_id(),
            'event_source_url' => get_site_url(),
            'user_data' => array(
                'em' => array(hash('sha256', strtolower($order->get_billing_email()))),
                'ph' => array(hash('sha256', preg_replace('/[^0-9]/', '', $order->get_billing_phone()))),
                'fn' => array(hash('sha256', strtolower($order->get_billing_first_name()))),
                'ln' => array(hash('sha256', strtolower($order->get_billing_last_name()))),
                'ct' => array(hash('sha256', strtolower($order->get_billing_city()))),
                'st' => array(hash('sha256', strtolower($order->get_billing_state()))),
                'zp' => array(hash('sha256', $order->get_billing_postcode())),
                'country' => array(hash('sha256', strtolower($order->get_billing_country())))
            ),
            'custom_data' => array(
                'currency' => $order->get_currency(),
                'value' => floatval($order->get_total()),
                'order_id' => $order->get_id(),
                'content_ids' => $this->get_order_product_ids($order),
                'content_type' => 'product',
                'num_items' => $order->get_item_count()
            )
        );
        
        return $event_data;
    }
    
    /**
     * Prepare test event data
     */
    private function prepare_test_event_data($event_type) {
        $base_data = array(
            'event_time' => time(),
            'event_id' => 'test_' . time() . '_' . rand(1000, 9999),
            'event_source_url' => get_site_url(),
            'user_data' => array(
                'em' => array(hash('sha256', 'test@example.com')),
                'fn' => array(hash('sha256', 'test')),
                'ln' => array(hash('sha256', 'user'))
            )
        );
        
        switch ($event_type) {
            case 'PageView':
                $base_data['event_name'] = 'PageView';
                break;
                
            case 'Purchase':
                $base_data['event_name'] = 'Purchase';
                $base_data['custom_data'] = array(
                    'currency' => 'USD',
                    'value' => 99.99,
                    'content_type' => 'product'
                );
                break;
                
            case 'AddToCart':
                $base_data['event_name'] = 'AddToCart';
                $base_data['custom_data'] = array(
                    'currency' => 'USD',
                    'value' => 29.99,
                    'content_type' => 'product'
                );
                break;
                
            default:
                $base_data['event_name'] = 'PageView';
        }
        
        return $base_data;
    }
    
    /**
     * Get product IDs from order
     */
    private function get_order_product_ids($order) {
        $product_ids = array();
        
        foreach ($order->get_items() as $item) {
            $product_ids[] = $item->get_product_id();
        }
        
        return $product_ids;
    }
    
    /**
     * Send event to Facebook CAPI
     */
    private function send_event_to_facebook($affiliate_id, $order_id, $pixel_id, $capi_token, $event_data) {
        $facebook_api_url = "https://graph.facebook.com/v18.0/{$pixel_id}/events";
        
        $request_data = array(
            'data' => array($event_data),
            'access_token' => $capi_token,
            'test_event_code' => get_option('pltnum_test_event_code', '') // For testing
        );
        
        $args = array(
            'body' => json_encode($request_data),
            'headers' => array(
                'Content-Type' => 'application/json',
                'User-Agent' => 'PLTNUM-CAPI-Plugin/' . PLTNUM_VERSION
            ),
            'timeout' => 30,
            'method' => 'POST'
        );
        
        $response = wp_remote_post($facebook_api_url, $args);
        
        if (is_wp_error($response)) {
            $error_message = $response->get_error_message();
            PLTNUM_Logger::log_capi_event($affiliate_id, $order_id, $event_data['event_name'], 'error', 'network_error', $error_message);
            
            return array(
                'success' => false,
                'message' => 'Network error: ' . $error_message
            );
        }
        
        $response_code = wp_remote_retrieve_response_code($response);
        $response_body = wp_remote_retrieve_body($response);
        $response_data = json_decode($response_body, true);
        
        if ($response_code === 200 && isset($response_data['events_received'])) {
            // Success
            PLTNUM_Logger::log_capi_event($affiliate_id, $order_id, $event_data['event_name'], 'success', '', 'Event sent successfully', $response_body);
            
            return array(
                'success' => true,
                'message' => 'Event sent successfully',
                'events_received' => $response_data['events_received']
            );
        } else {
            // Error
            $error_code = isset($response_data['error']['code']) ? $response_data['error']['code'] : $response_code;
            $error_message = isset($response_data['error']['message']) ? $response_data['error']['message'] : 'Unknown error';
            
            PLTNUM_Logger::log_capi_event($affiliate_id, $order_id, $event_data['event_name'], 'error', $error_code, $error_message, $response_body);
            
            return array(
                'success' => false,
                'message' => "Facebook API error: {$error_message}",
                'error_code' => $error_code
            );
        }
    }
}