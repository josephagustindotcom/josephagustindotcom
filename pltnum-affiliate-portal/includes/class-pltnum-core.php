<?php
/**
 * Core plugin class
 */
class PLTNUM_Core {
    
    public function __construct() {
        $this->init_hooks();
    }
    
    private function init_hooks() {
        add_action('init', array($this, 'init'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('admin_enqueue_scripts', array($this, 'admin_enqueue_scripts'));
    }
    
    public function init() {
        // Initialize components
        new PLTNUM_Admin();
        new PLTNUM_Affiliate_Portal();
        new PLTNUM_CAPI_Handler();
        new PLTNUM_Logger();
        
        // Load text domain for translations
        load_plugin_textdomain('pltnum-affiliate-portal', false, dirname(plugin_basename(__FILE__)) . '/languages');
    }
    
    public function enqueue_scripts() {
        // Frontend scripts
        wp_enqueue_script(
            'pltnum-frontend',
            PLTNUM_PLUGIN_URL . 'assets/js/frontend.js',
            array('jquery'),
            PLTNUM_VERSION,
            true
        );
        
        wp_enqueue_style(
            'pltnum-frontend',
            PLTNUM_PLUGIN_URL . 'assets/css/frontend.css',
            array(),
            PLTNUM_VERSION
        );
        
        // Localize script for AJAX
        wp_localize_script('pltnum-frontend', 'pltnum_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('pltnum_nonce'),
            'messages' => array(
                'verifying' => __('Verifying...', 'pltnum-affiliate-portal'),
                'success' => __('Verification successful!', 'pltnum-affiliate-portal'),
                'error' => __('Verification failed. Please check your credentials.', 'pltnum-affiliate-portal'),
                'network_error' => __('Network error. Please try again later.', 'pltnum-affiliate-portal'),
                'invalid_pixel' => __('Invalid Pixel ID. Please check the format.', 'pltnum-affiliate-portal'),
                'invalid_token' => __('Invalid or expired CAPI token. Please generate a new one.', 'pltnum-affiliate-portal')
            )
        ));
    }
    
    public function admin_enqueue_scripts($hook) {
        // Only load on PLTNUM admin pages
        if (strpos($hook, 'pltnum') === false) {
            return;
        }
        
        wp_enqueue_script(
            'pltnum-admin',
            PLTNUM_PLUGIN_URL . 'assets/js/admin.js',
            array('jquery'),
            PLTNUM_VERSION,
            true
        );
        
        wp_enqueue_style(
            'pltnum-admin',
            PLTNUM_PLUGIN_URL . 'assets/css/admin.css',
            array(),
            PLTNUM_VERSION
        );
        
        wp_localize_script('pltnum-admin', 'pltnum_admin_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('pltnum_admin_nonce')
        ));
    }
}