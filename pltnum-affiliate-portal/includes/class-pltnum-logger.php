<?php
/**
 * Enhanced logging system for CAPI events and verification attempts
 */
class PLTNUM_Logger {
    
    public function __construct() {
        // Constructor for any initialization if needed
    }
    
    /**
     * Log CAPI event to database
     */
    public static function log_capi_event($affiliate_id, $order_id, $event_type, $status, $error_code = '', $error_message = '', $fb_response = '') {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'pltnum_capi_logs';
        
        $data = array(
            'affiliate_id' => $affiliate_id,
            'order_id' => $order_id,
            'event_type' => $event_type,
            'status' => $status,
            'error_code' => $error_code,
            'error_message' => $error_message,
            'fb_response' => $fb_response,
            'timestamp' => current_time('mysql')
        );
        
        $wpdb->insert($table_name, $data);
        
        // Also update user meta with recent events (limit to last 10)
        self::update_user_recent_events($affiliate_id, $data);
        
        // Check for repeated failures and trigger notifications if needed
        self::check_repeated_failures($affiliate_id);
    }
    
    /**
     * Log verification attempt (pixel or token verification)
     */
    public static function log_verification_attempt($user_id, $verification_type, $result) {
        $history = get_user_meta($user_id, 'pltnum_verification_history', true);
        
        if (!is_array($history)) {
            $history = array();
        }
        
        $attempt = array(
            'type' => $verification_type,
            'status' => $result['success'] ? 'success' : 'failed',
            'message' => $result['message'],
            'error_type' => isset($result['error_type']) ? $result['error_type'] : '',
            'timestamp' => current_time('mysql'),
            'suggestions' => isset($result['suggestions']) ? $result['suggestions'] : array()
        );
        
        $history[] = $attempt;
        
        // Keep only last 10 attempts
        if (count($history) > 10) {
            $history = array_slice($history, -10);
        }
        
        update_user_meta($user_id, 'pltnum_verification_history', $history);
    }
    
    /**
     * Update user's recent CAPI events in user meta
     */
    private static function update_user_recent_events($user_id, $event_data) {
        $recent_events = get_user_meta($user_id, 'pltnum_recent_events', true);
        
        if (!is_array($recent_events)) {
            $recent_events = array();
        }
        
        $recent_events[] = $event_data;
        
        // Keep only last 10 events
        if (count($recent_events) > 10) {
            $recent_events = array_slice($recent_events, -10);
        }
        
        update_user_meta($user_id, 'pltnum_recent_events', $recent_events);
    }
    
    /**
     * Check for repeated failures and trigger notifications
     */
    private static function check_repeated_failures($affiliate_id) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'pltnum_capi_logs';
        
        // Count errors in the last 24 hours
        $error_count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) 
             FROM $table_name 
             WHERE affiliate_id = %d 
               AND status = 'error' 
               AND timestamp >= DATE_SUB(NOW(), INTERVAL 24 HOUR)",
            $affiliate_id
        ));
        
        $failure_threshold = get_option('pltnum_failure_threshold', 5);
        
        if ($error_count >= $failure_threshold) {
            // Check if we already sent notification recently
            $last_notification = get_user_meta($affiliate_id, 'pltnum_last_failure_notification', true);
            $notification_cooldown = get_option('pltnum_notification_cooldown', 3600); // 1 hour default
            
            if (!$last_notification || (time() - strtotime($last_notification)) > $notification_cooldown) {
                self::send_failure_notification($affiliate_id, $error_count);
                update_user_meta($affiliate_id, 'pltnum_last_failure_notification', current_time('mysql'));
            }
        }
    }
    
    /**
     * Send notification for repeated failures
     */
    private static function send_failure_notification($affiliate_id, $error_count) {
        $user = get_user_by('id', $affiliate_id);
        
        if (!$user) {
            return;
        }
        
        // Email notification to admin
        $admin_email = get_option('admin_email');
        $subject = sprintf(__('[%s] PLTNUM CAPI - Repeated failures detected', 'pltnum-affiliate-portal'), get_bloginfo('name'));
        
        $message = sprintf(
            __("Affiliate %s (%s) has experienced %d CAPI failures in the last 24 hours.\n\nPlease check their configuration in the admin dashboard:\n%s\n\nThis is an automated notification from the PLTNUM CAPI plugin.", 'pltnum-affiliate-portal'),
            $user->display_name,
            $user->user_email,
            $error_count,
            admin_url('admin.php?page=pltnum-diagnostics')
        );
        
        wp_mail($admin_email, $subject, $message);
        
        // Store notification in options for dashboard display
        $notifications = get_option('pltnum_failure_notifications', array());
        $notifications[] = array(
            'affiliate_id' => $affiliate_id,
            'affiliate_name' => $user->display_name,
            'error_count' => $error_count,
            'timestamp' => current_time('mysql'),
            'dismissed' => false
        );
        
        // Keep only last 50 notifications
        if (count($notifications) > 50) {
            $notifications = array_slice($notifications, -50);
        }
        
        update_option('pltnum_failure_notifications', $notifications);
    }
    
    /**
     * Get recent events for a specific affiliate
     */
    public static function get_affiliate_recent_events($affiliate_id, $limit = 10) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'pltnum_capi_logs';
        
        $results = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $table_name 
             WHERE affiliate_id = %d 
             ORDER BY timestamp DESC 
             LIMIT %d",
            $affiliate_id,
            $limit
        ));
        
        return $results;
    }
    
    /**
     * Get events statistics for dashboard
     */
    public static function get_events_statistics($days = 7) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'pltnum_capi_logs';
        
        $stats = $wpdb->get_results($wpdb->prepare(
            "SELECT 
                DATE(timestamp) as date,
                status,
                COUNT(*) as count
             FROM $table_name 
             WHERE timestamp >= DATE_SUB(NOW(), INTERVAL %d DAY)
             GROUP BY DATE(timestamp), status
             ORDER BY date DESC",
            $days
        ));
        
        return $stats;
    }
    
    /**
     * Clean old log entries
     */
    public static function cleanup_old_logs($days = 30) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'pltnum_capi_logs';
        
        $wpdb->query($wpdb->prepare(
            "DELETE FROM $table_name 
             WHERE timestamp < DATE_SUB(NOW(), INTERVAL %d DAY)",
            $days
        ));
    }
    
    /**
     * Get error trends for analytics
     */
    public static function get_error_trends($affiliate_id = 0, $days = 7) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'pltnum_capi_logs';
        $where_clause = '';
        $prepare_values = array($days);
        
        if ($affiliate_id > 0) {
            $where_clause = 'AND affiliate_id = %d';
            $prepare_values[] = $affiliate_id;
        }
        
        $results = $wpdb->get_results($wpdb->prepare(
            "SELECT 
                error_code,
                COUNT(*) as count,
                MAX(timestamp) as last_occurrence
             FROM $table_name 
             WHERE status = 'error' 
               AND timestamp >= DATE_SUB(NOW(), INTERVAL %d DAY)
               $where_clause
             GROUP BY error_code
             ORDER BY count DESC",
            $prepare_values
        ));
        
        return $results;
    }
}