<div class="wrap">
    <h1><?php _e('PLTNUM CAPI - Diagnostics Dashboard', 'pltnum-affiliate-portal'); ?></h1>
    
    <!-- Summary Cards -->
    <div class="pltnum-admin-summary">
        <div class="pltnum-summary-card pltnum-success">
            <div class="pltnum-summary-number"><?php echo esc_html($error_summary['success']); ?></div>
            <div class="pltnum-summary-label"><?php _e('Successful Events (7 days)', 'pltnum-affiliate-portal'); ?></div>
        </div>
        
        <div class="pltnum-summary-card pltnum-warning">
            <div class="pltnum-summary-number"><?php echo esc_html($error_summary['warning']); ?></div>
            <div class="pltnum-summary-label"><?php _e('Warnings (7 days)', 'pltnum-affiliate-portal'); ?></div>
        </div>
        
        <div class="pltnum-summary-card pltnum-error">
            <div class="pltnum-summary-number"><?php echo esc_html($error_summary['error']); ?></div>
            <div class="pltnum-summary-label"><?php _e('Errors (7 days)', 'pltnum-affiliate-portal'); ?></div>
        </div>
        
        <div class="pltnum-summary-card pltnum-problem">
            <div class="pltnum-summary-number"><?php echo count($problem_affiliates); ?></div>
            <div class="pltnum-summary-label"><?php _e('Problem Affiliates (24h)', 'pltnum-affiliate-portal'); ?></div>
        </div>
    </div>
    
    <!-- Problem Affiliates Alert -->
    <?php if (!empty($problem_affiliates)): ?>
    <div class="notice notice-warning">
        <h3><?php _e('Affiliates with Repeated Failures (Last 24 Hours)', 'pltnum-affiliate-portal'); ?></h3>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th><?php _e('Affiliate', 'pltnum-affiliate-portal'); ?></th>
                    <th><?php _e('Error Count', 'pltnum-affiliate-portal'); ?></th>
                    <th><?php _e('Last Error', 'pltnum-affiliate-portal'); ?></th>
                    <th><?php _e('Actions', 'pltnum-affiliate-portal'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($problem_affiliates as $affiliate): ?>
                <tr>
                    <td>
                        <strong><?php echo esc_html($affiliate->display_name); ?></strong>
                        <br><small>ID: <?php echo esc_html($affiliate->affiliate_id); ?></small>
                    </td>
                    <td>
                        <span class="pltnum-error-count"><?php echo esc_html($affiliate->error_count); ?></span>
                    </td>
                    <td>
                        <?php echo esc_html(mysql2date('M j, Y g:i A', $affiliate->last_error)); ?>
                    </td>
                    <td>
                        <a href="<?php echo admin_url('admin.php?page=pltnum-affiliates&affiliate_id=' . $affiliate->affiliate_id); ?>" class="button button-small">
                            <?php _e('View Details', 'pltnum-affiliate-portal'); ?>
                        </a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
    
    <!-- Filters -->
    <div class="pltnum-filters">
        <h3><?php _e('Filter Events', 'pltnum-affiliate-portal'); ?></h3>
        
        <form id="pltnum-filter-form" class="pltnum-filter-form">
            <div class="pltnum-filter-row">
                <div class="pltnum-filter-group">
                    <label for="filter-affiliate"><?php _e('Affiliate:', 'pltnum-affiliate-portal'); ?></label>
                    <select id="filter-affiliate" name="affiliate_id">
                        <option value=""><?php _e('All Affiliates', 'pltnum-affiliate-portal'); ?></option>
                        <?php
                        $affiliates = get_users(array('meta_key' => 'pltnum_pixel_id'));
                        foreach ($affiliates as $affiliate):
                        ?>
                        <option value="<?php echo esc_attr($affiliate->ID); ?>">
                            <?php echo esc_html($affiliate->display_name); ?> (ID: <?php echo esc_html($affiliate->ID); ?>)
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="pltnum-filter-group">
                    <label for="filter-status"><?php _e('Status:', 'pltnum-affiliate-portal'); ?></label>
                    <select id="filter-status" name="status">
                        <option value=""><?php _e('All Statuses', 'pltnum-affiliate-portal'); ?></option>
                        <option value="success"><?php _e('Success', 'pltnum-affiliate-portal'); ?></option>
                        <option value="warning"><?php _e('Warning', 'pltnum-affiliate-portal'); ?></option>
                        <option value="error"><?php _e('Error', 'pltnum-affiliate-portal'); ?></option>
                    </select>
                </div>
                
                <div class="pltnum-filter-group">
                    <label for="filter-date-from"><?php _e('From:', 'pltnum-affiliate-portal'); ?></label>
                    <input type="date" id="filter-date-from" name="date_from" value="<?php echo date('Y-m-d', strtotime('-7 days')); ?>">
                </div>
                
                <div class="pltnum-filter-group">
                    <label for="filter-date-to"><?php _e('To:', 'pltnum-affiliate-portal'); ?></label>
                    <input type="date" id="filter-date-to" name="date_to" value="<?php echo date('Y-m-d'); ?>">
                </div>
                
                <div class="pltnum-filter-group">
                    <button type="submit" class="button button-primary"><?php _e('Filter', 'pltnum-affiliate-portal'); ?></button>
                    <button type="button" id="reset-filters" class="button"><?php _e('Reset', 'pltnum-affiliate-portal'); ?></button>
                </div>
            </div>
        </form>
    </div>
    
    <!-- Events Table -->
    <div class="pltnum-events-container">
        <h3><?php _e('Recent CAPI Events', 'pltnum-affiliate-portal'); ?></h3>
        
        <div id="pltnum-events-loading" class="pltnum-loading" style="display: none;">
            <?php _e('Loading events...', 'pltnum-affiliate-portal'); ?>
        </div>
        
        <table id="pltnum-events-table" class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th><?php _e('Timestamp', 'pltnum-affiliate-portal'); ?></th>
                    <th><?php _e('Affiliate', 'pltnum-affiliate-portal'); ?></th>
                    <th><?php _e('Event Type', 'pltnum-affiliate-portal'); ?></th>
                    <th><?php _e('Order ID', 'pltnum-affiliate-portal'); ?></th>
                    <th><?php _e('Status', 'pltnum-affiliate-portal'); ?></th>
                    <th><?php _e('Error Details', 'pltnum-affiliate-portal'); ?></th>
                    <th><?php _e('Actions', 'pltnum-affiliate-portal'); ?></th>
                </tr>
            </thead>
            <tbody id="pltnum-events-tbody">
                <?php foreach ($recent_events as $event): ?>
                <tr class="pltnum-event-row pltnum-status-<?php echo esc_attr($event->status); ?>">
                    <td>
                        <?php echo esc_html(mysql2date('M j, Y g:i A', $event->timestamp)); ?>
                    </td>
                    <td>
                        <strong><?php echo esc_html($event->affiliate_name ?: 'Unknown'); ?></strong>
                        <br><small>ID: <?php echo esc_html($event->affiliate_id); ?></small>
                    </td>
                    <td>
                        <span class="pltnum-event-type"><?php echo esc_html($event->event_type); ?></span>
                    </td>
                    <td>
                        <?php echo esc_html($event->order_id); ?>
                    </td>
                    <td>
                        <span class="pltnum-status-badge pltnum-status-<?php echo esc_attr($event->status); ?>">
                            <?php echo esc_html(ucfirst($event->status)); ?>
                        </span>
                    </td>
                    <td>
                        <?php if ($event->status === 'error'): ?>
                            <div class="pltnum-error-details">
                                <?php if ($event->error_code): ?>
                                    <strong><?php _e('Code:', 'pltnum-affiliate-portal'); ?></strong> <?php echo esc_html($event->error_code); ?><br>
                                <?php endif; ?>
                                <?php if ($event->error_message): ?>
                                    <strong><?php _e('Message:', 'pltnum-affiliate-portal'); ?></strong> <?php echo esc_html($event->error_message); ?>
                                <?php endif; ?>
                            </div>
                        <?php else: ?>
                            <span class="pltnum-no-error"><?php _e('No errors', 'pltnum-affiliate-portal'); ?></span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <button type="button" class="button button-small view-response-btn" data-response="<?php echo esc_attr($event->fb_response); ?>">
                            <?php _e('View Response', 'pltnum-affiliate-portal'); ?>
                        </button>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        
        <?php if (empty($recent_events)): ?>
        <div class="pltnum-no-events">
            <p><?php _e('No CAPI events found. Events will appear here once affiliates start sending data to Facebook.', 'pltnum-affiliate-portal'); ?></p>
        </div>
        <?php endif; ?>
    </div>
    
    <!-- Response Modal -->
    <div id="pltnum-response-modal" class="pltnum-modal" style="display: none;">
        <div class="pltnum-modal-content">
            <div class="pltnum-modal-header">
                <h3><?php _e('Facebook API Response', 'pltnum-affiliate-portal'); ?></h3>
                <button type="button" class="pltnum-modal-close">&times;</button>
            </div>
            <div class="pltnum-modal-body">
                <pre id="pltnum-response-content"></pre>
            </div>
        </div>
    </div>
</div>