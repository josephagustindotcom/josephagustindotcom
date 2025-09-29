<?php
/**
 * Plugin Name: PLTNUM Affiliate Portal CAPI
 * Plugin URI: https://github.com/josephagustindotcom/josephagustindotcom
 * Description: Enhanced affiliate portal with comprehensive CAPI error feedback and diagnostics for Facebook Conversions API.
 * Version: 1.0.0
 * Author: Joseph Agustin
 * License: GPL v2 or later
 * Text Domain: pltnum-affiliate-portal
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('PLTNUM_PLUGIN_URL', plugin_dir_url(__FILE__));
define('PLTNUM_PLUGIN_PATH', plugin_dir_path(__FILE__));
define('PLTNUM_VERSION', '1.0.0');

// Include required files
require_once PLTNUM_PLUGIN_PATH . 'includes/class-pltnum-core.php';
require_once PLTNUM_PLUGIN_PATH . 'includes/class-pltnum-admin.php';
require_once PLTNUM_PLUGIN_PATH . 'includes/class-pltnum-affiliate-portal.php';
require_once PLTNUM_PLUGIN_PATH . 'includes/class-pltnum-capi-handler.php';
require_once PLTNUM_PLUGIN_PATH . 'includes/class-pltnum-logger.php';

// Initialize the plugin
function pltnum_init() {
    new PLTNUM_Core();
}
add_action('plugins_loaded', 'pltnum_init');

// Activation hook
register_activation_hook(__FILE__, 'pltnum_activate');
function pltnum_activate() {
    // Create necessary database tables and set default options
    pltnum_create_tables();
    add_option('pltnum_version', PLTNUM_VERSION);
}

// Deactivation hook
register_deactivation_hook(__FILE__, 'pltnum_deactivate');
function pltnum_deactivate() {
    // Clean up any temporary data if needed
}

// Create database tables for logging
function pltnum_create_tables() {
    global $wpdb;
    
    $table_name = $wpdb->prefix . 'pltnum_capi_logs';
    
    $charset_collate = $wpdb->get_charset_collate();
    
    $sql = "CREATE TABLE $table_name (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        affiliate_id bigint(20) NOT NULL,
        order_id varchar(100) DEFAULT '',
        event_type varchar(50) NOT NULL,
        status varchar(20) NOT NULL,
        error_code varchar(50) DEFAULT '',
        error_message text DEFAULT '',
        fb_response text DEFAULT '',
        timestamp datetime DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        KEY affiliate_id (affiliate_id),
        KEY status (status),
        KEY timestamp (timestamp)
    ) $charset_collate;";
    
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
}