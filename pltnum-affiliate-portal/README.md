# PLTNUM Affiliate Portal CAPI Plugin

A comprehensive WordPress plugin that enhances affiliate portals with Facebook Conversions API (CAPI) integration, featuring detailed error feedback, diagnostics, and monitoring capabilities.

## Features

### 🎯 Affiliate Portal UI Enhancements
- **User-friendly feedback messages** for Pixel ID and CAPI token verification
- **Real-time validation** with actionable suggestions for error resolution
- **Verification history** showing the last 5 attempts with timestamps and status
- **Responsive design** that works on all devices
- **Privacy-first approach** with secure credential handling

### 📊 Admin Diagnostics Dashboard
- **Comprehensive event monitoring** with filtering by affiliate and status
- **Error tracking** with detailed Facebook API response logging
- **Problem affiliate detection** highlighting repeated failures
- **Real-time statistics** showing success/warning/error counts
- **Export capabilities** for further analysis

### 🔧 Backend Logging Enhancements
- **Detailed error logging** with timestamps, affiliate IDs, and order IDs
- **Facebook API response storage** for debugging purposes
- **User meta storage** of recent events (limited to last 10 for performance)
- **Automatic cleanup** of old log entries

### 🚨 Admin Notifications
- **Dashboard alerts** for affiliates with repeated failures
- **Email notifications** for critical issues (optional)
- **Configurable thresholds** for failure detection
- **Notification cooldown** to prevent spam

## Installation

1. Upload the `pltnum-affiliate-portal` folder to your `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. The plugin will automatically create necessary database tables
4. Configure settings in the WordPress admin under "PLTNUM CAPI"

## Usage

### For Affiliates

Add the affiliate portal to any page using the shortcode:
```
[pltnum_affiliate_portal]
```

Affiliates can then:
1. Enter their Facebook Pixel ID (15-16 digits)
2. Add their CAPI access token
3. Verify both credentials with real-time feedback
4. View their verification history

### For Administrators

Access the admin dashboard under **PLTNUM CAPI** in the WordPress admin:

- **Overview**: Quick statistics and recent activity
- **Diagnostics**: Detailed event monitoring and filtering
- **Affiliates**: Manage affiliate configurations and test CAPI events

## Configuration

### Required Facebook Setup

1. **Facebook Business Manager Account**
2. **Facebook Pixel** created and configured
3. **CAPI Access Token** generated with proper permissions

### Plugin Settings

The plugin includes several configurable options:

- **Failure threshold**: Number of errors before triggering notifications
- **Notification cooldown**: Time between repeated notifications
- **Log retention**: How long to keep detailed logs
- **Test event code**: For Facebook's Event Testing feature

## API Integration

The plugin integrates with Facebook's Conversions API v18.0:

```php
// Example event sending
PLTNUM_Logger::log_capi_event($affiliate_id, $order_id, 'Purchase', 'success');
```

### Supported Events

- **Purchase**: WooCommerce order completion
- **PageView**: Page tracking events
- **AddToCart**: Shopping cart additions
- **Custom Events**: Extensible for additional tracking

## Developer Hooks

The plugin provides several hooks for customization:

```php
// Filter event data before sending
add_filter('pltnum_event_data', 'custom_event_modifier');

// Action after successful verification
add_action('pltnum_verification_success', 'custom_success_handler');

// Filter notification settings
add_filter('pltnum_notification_settings', 'custom_notification_config');
```

## Database Schema

The plugin creates one main table:

```sql
CREATE TABLE wp_pltnum_capi_logs (
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
);
```

## Privacy & Security

- **No sensitive data exposure** in frontend interfaces
- **Secure token storage** using WordPress user meta
- **HTTPS enforcement** for API communications
- **Data retention policies** with automatic cleanup
- **User permission checks** for all admin functions

## Performance Considerations

- **Efficient database queries** with proper indexing
- **Limited user meta storage** (last 10 events per affiliate)
- **Automatic log cleanup** to prevent database bloat
- **AJAX-powered interfaces** for smooth user experience
- **Optimized asset loading** only where needed

## Troubleshooting

### Common Issues

1. **Invalid Pixel ID Format**
   - Ensure 15-16 digits only
   - No spaces or special characters

2. **CAPI Token Errors**
   - Verify token hasn't expired
   - Check permissions in Facebook Business Manager
   - Ensure correct pixel association

3. **Network Connectivity**
   - Verify HTTPS is working
   - Check firewall settings
   - Test Facebook API accessibility

### Debug Mode

Enable WordPress debug mode to see detailed error logs:
```php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
```

## Changelog

### Version 1.0.0
- Initial release
- Affiliate portal with verification system
- Admin diagnostics dashboard
- Comprehensive error logging
- Facebook CAPI integration
- Responsive UI design

## Support

For support and feature requests, please contact the development team or create an issue in the repository.

## License

This plugin is licensed under the GPL v2 or later.

---

**Note**: This plugin requires WordPress 5.0+ and PHP 7.4+. WooCommerce integration requires WooCommerce 3.0+.