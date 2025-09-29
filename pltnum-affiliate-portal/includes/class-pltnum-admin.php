<?php
/**
 * Admin dashboard with diagnostics and monitoring
 */
class PLTNUM_Admin {
    
    public function __construct() {
        $this->init_hooks();
    }
    
    private function init_hooks() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('wp_ajax_pltnum_admin_get_events', array($this, 'ajax_get_events'));
        add_action('wp_ajax_pltnum_admin_filter_events', array($this, 'ajax_filter_events'));
        add_action('admin_notices', array($this, 'show_admin_notices'));
    }
    
    /**
     * Add admin menu pages
     */
    public function add_admin_menu() {
        add_menu_page(
            __('PLTNUM CAPI', 'pltnum-affiliate-portal'),
            __('PLTNUM CAPI', 'pltnum-affiliate-portal'),
            'manage_options',
            'pltnum-capi',
            array($this, 'render_main_page'),
            'dashicons-analytics',
            30
        );
        
        add_submenu_page(
            'pltnum-capi',
            __('Diagnostics Dashboard', 'pltnum-affiliate-portal'),
            __('Diagnostics', 'pltnum-affiliate-portal'),
            'manage_options',
            'pltnum-diagnostics',
            array($this, 'render_diagnostics_page')
        );
        
        add_submenu_page(
            'pltnum-capi',
            __('Affiliate Management', 'pltnum-affiliate-portal'),
            __('Affiliates', 'pltnum-affiliate-portal'),
            'manage_options',
            'pltnum-affiliates',
            array($this, 'render_affiliates_page')
        );
    }
    
    /**
     * Render main admin page
     */
    public function render_main_page() {
        include PLTNUM_PLUGIN_PATH . 'templates/admin-main.php';
    }
    
    /**
     * Render diagnostics dashboard
     */
    public function render_diagnostics_page() {
        $recent_events = $this->get_recent_events();
        $error_summary = $this->get_error_summary();
        $problem_affiliates = $this->get_problem_affiliates();
        
        include PLTNUM_PLUGIN_PATH . 'templates/admin-diagnostics.php';
    }
    
    /**
     * Render affiliates management page
     */
    public function render_affiliates_page() {
        $affiliates = $this->get_affiliates_with_status();
        
        include PLTNUM_PLUGIN_PATH . 'templates/admin-affiliates.php';
    }
    
    /**
     * AJAX handler for getting events
     */
    public function ajax_get_events() {
        check_ajax_referer('pltnum_admin_nonce', 'nonce');
        
        $events = $this->get_recent_events(50);
        wp_send_json_success($events);
    }
    
    /**
     * AJAX handler for filtering events
     */
    public function ajax_filter_events() {
        check_ajax_referer('pltnum_admin_nonce', 'nonce');
        
        $affiliate_id = intval($_POST['affiliate_id']);
        $status = sanitize_text_field($_POST['status']);
        $date_from = sanitize_text_field($_POST['date_from']);
        $date_to = sanitize_text_field($_POST['date_to']);
        
        $events = $this->get_filtered_events($affiliate_id, $status, $date_from, $date_to);
        wp_send_json_success($events);
    }
    
    /**
     * Get recent CAPI events
     */
    private function get_recent_events($limit = 20) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'pltnum_capi_logs';
        
        $results = $wpdb->get_results($wpdb->prepare(
            "SELECT l.*, u.display_name as affiliate_name 
             FROM $table_name l 
             LEFT JOIN {$wpdb->users} u ON l.affiliate_id = u.ID 
             ORDER BY l.timestamp DESC 
             LIMIT %d",
            $limit
        ));
        
        return $results;
    }
    
    /**
     * Get filtered events
     */
    private function get_filtered_events($affiliate_id = 0, $status = '', $date_from = '', $date_to = '') {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'pltnum_capi_logs';
        $where_conditions = array();
        $prepare_values = array();
        
        if ($affiliate_id > 0) {
            $where_conditions[] = 'l.affiliate_id = %d';
            $prepare_values[] = $affiliate_id;
        }
        
        if (!empty($status)) {
            $where_conditions[] = 'l.status = %s';
            $prepare_values[] = $status;
        }
        
        if (!empty($date_from)) {
            $where_conditions[] = 'l.timestamp >= %s';
            $prepare_values[] = $date_from . ' 00:00:00';
        }
        
        if (!empty($date_to)) {
            $where_conditions[] = 'l.timestamp <= %s';
            $prepare_values[] = $date_to . ' 23:59:59';
        }
        
        $where_clause = '';
        if (!empty($where_conditions)) {
            $where_clause = 'WHERE ' . implode(' AND ', $where_conditions);
        }
        
        $sql = "SELECT l.*, u.display_name as affiliate_name 
                FROM $table_name l 
                LEFT JOIN {$wpdb->users} u ON l.affiliate_id = u.ID 
                $where_clause 
                ORDER BY l.timestamp DESC 
                LIMIT 100";
        
        if (!empty($prepare_values)) {
            $results = $wpdb->get_results($wpdb->prepare($sql, $prepare_values));
        } else {
            $results = $wpdb->get_results($sql);
        }
        
        return $results;
    }
    
    /**
     * Get error summary for dashboard
     */
    private function get_error_summary() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'pltnum_capi_logs';
        
        $summary = $wpdb->get_results(
            "SELECT status, COUNT(*) as count 
             FROM $table_name 
             WHERE timestamp >= DATE_SUB(NOW(), INTERVAL 7 DAY) 
             GROUP BY status"
        );
        
        $formatted_summary = array(
            'success' => 0,
            'error' => 0,
            'warning' => 0
        );
        
        foreach ($summary as $row) {
            $formatted_summary[$row->status] = $row->count;
        }
        
        return $formatted_summary;
    }
    
    /**
     * Get affiliates with repeated failures
     */
    private function get_problem_affiliates() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'pltnum_capi_logs';
        
        $problem_affiliates = $wpdb->get_results(
            "SELECT l.affiliate_id, u.display_name, 
                    COUNT(*) as error_count,
                    MAX(l.timestamp) as last_error
             FROM $table_name l
             LEFT JOIN {$wpdb->users} u ON l.affiliate_id = u.ID
             WHERE l.status = 'error' 
               AND l.timestamp >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
             GROUP BY l.affiliate_id
             HAVING error_count >= 5
             ORDER BY error_count DESC"
        );
        
        return $problem_affiliates;
    }
    
    /**
     * Get affiliates with their current status
     */
    private function get_affiliates_with_status() {
        global $wpdb;
        
        $users = $wpdb->get_results(
            "SELECT u.ID, u.display_name, u.user_email,
                    um1.meta_value as pixel_id,
                    um2.meta_value as pixel_verified,
                    um3.meta_value as token_verified
             FROM {$wpdb->users} u
             LEFT JOIN {$wpdb->usermeta} um1 ON u.ID = um1.user_id AND um1.meta_key = 'pltnum_pixel_id'
             LEFT JOIN {$wpdb->usermeta} um2 ON u.ID = um2.user_id AND um2.meta_key = 'pltnum_pixel_verified'
             LEFT JOIN {$wpdb->usermeta} um3 ON u.ID = um3.user_id AND um3.meta_key = 'pltnum_token_verified'
             WHERE u.ID IN (
                 SELECT DISTINCT user_id 
                 FROM {$wpdb->usermeta} 
                 WHERE meta_key = 'pltnum_pixel_id'
             )
             ORDER BY u.display_name"
        );
        
        // Add recent activity data
        foreach ($users as &$user) {
            $user->recent_errors = $this->get_recent_errors_for_user($user->ID);
        }
        
        return $users;
    }
    
    /**
     * Get recent errors for a specific user
     */
    private function get_recent_errors_for_user($user_id) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'pltnum_capi_logs';
        
        $error_count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) 
             FROM $table_name 
             WHERE affiliate_id = %d 
               AND status = 'error' 
               AND timestamp >= DATE_SUB(NOW(), INTERVAL 24 HOUR)",
            $user_id
        ));
        
        return intval($error_count);
    }
    
    /**
     * Show admin notices for critical issues
     */
    public function show_admin_notices() {
        $problem_affiliates = $this->get_problem_affiliates();
        
        if (!empty($problem_affiliates) && count($problem_affiliates) > 0) {
            echo '<div class="notice notice-warning is-dismissible">';
            echo '<p><strong>' . __('PLTNUM CAPI Alert:', 'pltnum-affiliate-portal') . '</strong> ';
            echo sprintf(
                _n(
                    '%d affiliate has repeated CAPI failures in the last 24 hours.',
                    '%d affiliates have repeated CAPI failures in the last 24 hours.',
                    count($problem_affiliates),
                    'pltnum-affiliate-portal'
                ),
                count($problem_affiliates)
            );
            echo ' <a href="' . admin_url('admin.php?page=pltnum-diagnostics') . '">' . __('View diagnostics', 'pltnum-affiliate-portal') . '</a></p>';
            echo '</div>';
        }
    }
}