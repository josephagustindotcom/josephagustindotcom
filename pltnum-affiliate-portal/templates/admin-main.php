<div class="wrap">
    <h1><?php _e('PLTNUM CAPI - Overview', 'pltnum-affiliate-portal'); ?></h1>
    
    <div class="pltnum-admin-overview">
        <div class="pltnum-overview-section">
            <h2><?php _e('Welcome to PLTNUM Affiliate Portal CAPI', 'pltnum-affiliate-portal'); ?></h2>
            <p><?php _e('This plugin provides comprehensive Facebook Conversions API integration with enhanced error feedback and diagnostics for affiliate portals.', 'pltnum-affiliate-portal'); ?></p>
        </div>
        
        <div class="pltnum-quick-stats">
            <h3><?php _e('Quick Statistics', 'pltnum-affiliate-portal'); ?></h3>
            
            <?php
            $total_affiliates = count(get_users(array('meta_key' => 'pltnum_pixel_id')));
            $active_affiliates = count(get_users(array(
                'meta_query' => array(
                    array(
                        'key' => 'pltnum_pixel_verified',
                        'compare' => 'EXISTS'
                    ),
                    array(
                        'key' => 'pltnum_token_verified',
                        'compare' => 'EXISTS'
                    )
                )
            )));
            
            global $wpdb;
            $table_name = $wpdb->prefix . 'pltnum_capi_logs';
            $events_today = $wpdb->get_var(
                "SELECT COUNT(*) FROM $table_name WHERE DATE(timestamp) = CURDATE()"
            );
            ?>
            
            <div class="pltnum-stats-grid">
                <div class="pltnum-stat-item">
                    <div class="pltnum-stat-number"><?php echo esc_html($total_affiliates); ?></div>
                    <div class="pltnum-stat-label"><?php _e('Total Affiliates', 'pltnum-affiliate-portal'); ?></div>
                </div>
                
                <div class="pltnum-stat-item">
                    <div class="pltnum-stat-number"><?php echo esc_html($active_affiliates); ?></div>
                    <div class="pltnum-stat-label"><?php _e('Active Affiliates', 'pltnum-affiliate-portal'); ?></div>
                </div>
                
                <div class="pltnum-stat-item">
                    <div class="pltnum-stat-number"><?php echo esc_html($events_today); ?></div>
                    <div class="pltnum-stat-label"><?php _e('Events Today', 'pltnum-affiliate-portal'); ?></div>
                </div>
            </div>
        </div>
        
        <div class="pltnum-quick-actions">
            <h3><?php _e('Quick Actions', 'pltnum-affiliate-portal'); ?></h3>
            
            <div class="pltnum-actions-grid">
                <div class="pltnum-action-card">
                    <h4><?php _e('View Diagnostics', 'pltnum-affiliate-portal'); ?></h4>
                    <p><?php _e('Monitor CAPI events, errors, and affiliate performance.', 'pltnum-affiliate-portal'); ?></p>
                    <a href="<?php echo admin_url('admin.php?page=pltnum-diagnostics'); ?>" class="button button-primary">
                        <?php _e('Open Diagnostics', 'pltnum-affiliate-portal'); ?>
                    </a>
                </div>
                
                <div class="pltnum-action-card">
                    <h4><?php _e('Manage Affiliates', 'pltnum-affiliate-portal'); ?></h4>
                    <p><?php _e('View and manage affiliate configurations and credentials.', 'pltnum-affiliate-portal'); ?></p>
                    <a href="<?php echo admin_url('admin.php?page=pltnum-affiliates'); ?>" class="button button-primary">
                        <?php _e('Manage Affiliates', 'pltnum-affiliate-portal'); ?>
                    </a>
                </div>
                
                <div class="pltnum-action-card">
                    <h4><?php _e('Plugin Settings', 'pltnum-affiliate-portal'); ?></h4>
                    <p><?php _e('Configure global plugin settings and notifications.', 'pltnum-affiliate-portal'); ?></p>
                    <a href="<?php echo admin_url('admin.php?page=pltnum-settings'); ?>" class="button button-secondary">
                        <?php _e('Settings', 'pltnum-affiliate-portal'); ?>
                    </a>
                </div>
            </div>
        </div>
        
        <div class="pltnum-recent-activity">
            <h3><?php _e('Recent Activity', 'pltnum-affiliate-portal'); ?></h3>
            
            <?php
            $recent_events = $wpdb->get_results(
                "SELECT l.*, u.display_name as affiliate_name 
                 FROM $table_name l 
                 LEFT JOIN {$wpdb->users} u ON l.affiliate_id = u.ID 
                 ORDER BY l.timestamp DESC 
                 LIMIT 5"
            );
            ?>
            
            <?php if (!empty($recent_events)): ?>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th><?php _e('Time', 'pltnum-affiliate-portal'); ?></th>
                        <th><?php _e('Affiliate', 'pltnum-affiliate-portal'); ?></th>
                        <th><?php _e('Event', 'pltnum-affiliate-portal'); ?></th>
                        <th><?php _e('Status', 'pltnum-affiliate-portal'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recent_events as $event): ?>
                    <tr>
                        <td><?php echo esc_html(mysql2date('g:i A', $event->timestamp)); ?></td>
                        <td><?php echo esc_html($event->affiliate_name ?: 'Unknown'); ?></td>
                        <td><?php echo esc_html($event->event_type); ?></td>
                        <td>
                            <span class="pltnum-status-badge pltnum-status-<?php echo esc_attr($event->status); ?>">
                                <?php echo esc_html(ucfirst($event->status)); ?>
                            </span>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            
            <p class="pltnum-view-all">
                <a href="<?php echo admin_url('admin.php?page=pltnum-diagnostics'); ?>">
                    <?php _e('View all events →', 'pltnum-affiliate-portal'); ?>
                </a>
            </p>
            <?php else: ?>
            <p><?php _e('No recent activity. Events will appear here once affiliates start using the CAPI integration.', 'pltnum-affiliate-portal'); ?></p>
            <?php endif; ?>
        </div>
        
        <div class="pltnum-help-section">
            <h3><?php _e('Getting Started', 'pltnum-affiliate-portal'); ?></h3>
            
            <div class="pltnum-help-content">
                <ol>
                    <li>
                        <strong><?php _e('Add the affiliate portal to your site:', 'pltnum-affiliate-portal'); ?></strong>
                        <?php _e('Use the shortcode', 'pltnum-affiliate-portal'); ?> <code>[pltnum_affiliate_portal]</code> <?php _e('on any page where affiliates should configure their CAPI settings.', 'pltnum-affiliate-portal'); ?>
                    </li>
                    <li>
                        <strong><?php _e('Guide your affiliates:', 'pltnum-affiliate-portal'); ?></strong>
                        <?php _e('Direct them to the portal page to enter their Facebook Pixel ID and CAPI token.', 'pltnum-affiliate-portal'); ?>
                    </li>
                    <li>
                        <strong><?php _e('Monitor performance:', 'pltnum-affiliate-portal'); ?></strong>
                        <?php _e('Use the diagnostics dashboard to track CAPI events and identify any issues.', 'pltnum-affiliate-portal'); ?>
                    </li>
                </ol>
                
                <div class="pltnum-help-links">
                    <a href="#" class="button button-secondary"><?php _e('Documentation', 'pltnum-affiliate-portal'); ?></a>
                    <a href="#" class="button button-secondary"><?php _e('Support', 'pltnum-affiliate-portal'); ?></a>
                </div>
            </div>
        </div>
    </div>
</div>