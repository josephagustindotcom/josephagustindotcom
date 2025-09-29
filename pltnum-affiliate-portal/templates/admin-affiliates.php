<div class="wrap">
    <h1><?php _e('PLTNUM CAPI - Affiliate Management', 'pltnum-affiliate-portal'); ?></h1>
    
    <div class="pltnum-affiliates-overview">
        <p><?php _e('Monitor and manage affiliate CAPI configurations, credentials, and performance.', 'pltnum-affiliate-portal'); ?></p>
    </div>
    
    <!-- Affiliates Table -->
    <div class="pltnum-affiliates-container">
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th><?php _e('Affiliate', 'pltnum-affiliate-portal'); ?></th>
                    <th><?php _e('Pixel ID', 'pltnum-affiliate-portal'); ?></th>
                    <th><?php _e('Verification Status', 'pltnum-affiliate-portal'); ?></th>
                    <th><?php _e('Recent Errors (24h)', 'pltnum-affiliate-portal'); ?></th>
                    <th><?php _e('Last Activity', 'pltnum-affiliate-portal'); ?></th>
                    <th><?php _e('Actions', 'pltnum-affiliate-portal'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($affiliates as $affiliate): ?>
                <tr class="pltnum-affiliate-row">
                    <td>
                        <div class="pltnum-affiliate-info">
                            <strong><?php echo esc_html($affiliate->display_name); ?></strong>
                            <br>
                            <small><?php echo esc_html($affiliate->user_email); ?></small>
                            <br>
                            <small><?php _e('ID:', 'pltnum-affiliate-portal'); ?> <?php echo esc_html($affiliate->ID); ?></small>
                        </div>
                    </td>
                    <td>
                        <?php if ($affiliate->pixel_id): ?>
                            <code><?php echo esc_html($affiliate->pixel_id); ?></code>
                        <?php else: ?>
                            <span class="pltnum-not-set"><?php _e('Not set', 'pltnum-affiliate-portal'); ?></span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <div class="pltnum-verification-status">
                            <?php 
                            $pixel_verified = !empty($affiliate->pixel_verified);
                            $token_verified = !empty($affiliate->token_verified);
                            ?>
                            
                            <div class="pltnum-verification-item">
                                <span class="pltnum-verification-label"><?php _e('Pixel:', 'pltnum-affiliate-portal'); ?></span>
                                <span class="pltnum-status-indicator <?php echo $pixel_verified ? 'verified' : 'not-verified'; ?>">
                                    <?php echo $pixel_verified ? __('Verified', 'pltnum-affiliate-portal') : __('Not Verified', 'pltnum-affiliate-portal'); ?>
                                </span>
                                <?php if ($pixel_verified): ?>
                                    <small class="pltnum-verification-date">
                                        <?php echo esc_html(mysql2date('M j, Y', $affiliate->pixel_verified)); ?>
                                    </small>
                                <?php endif; ?>
                            </div>
                            
                            <div class="pltnum-verification-item">
                                <span class="pltnum-verification-label"><?php _e('Token:', 'pltnum-affiliate-portal'); ?></span>
                                <span class="pltnum-status-indicator <?php echo $token_verified ? 'verified' : 'not-verified'; ?>">
                                    <?php echo $token_verified ? __('Verified', 'pltnum-affiliate-portal') : __('Not Verified', 'pltnum-affiliate-portal'); ?>
                                </span>
                                <?php if ($token_verified): ?>
                                    <small class="pltnum-verification-date">
                                        <?php echo esc_html(mysql2date('M j, Y', $affiliate->token_verified)); ?>
                                    </small>
                                <?php endif; ?>
                            </div>
                        </div>
                    </td>
                    <td>
                        <?php if ($affiliate->recent_errors > 0): ?>
                            <span class="pltnum-error-count pltnum-has-errors">
                                <?php echo esc_html($affiliate->recent_errors); ?>
                            </span>
                        <?php else: ?>
                            <span class="pltnum-error-count pltnum-no-errors">0</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php
                        global $wpdb;
                        $table_name = $wpdb->prefix . 'pltnum_capi_logs';
                        $last_activity = $wpdb->get_var($wpdb->prepare(
                            "SELECT MAX(timestamp) FROM $table_name WHERE affiliate_id = %d",
                            $affiliate->ID
                        ));
                        ?>
                        
                        <?php if ($last_activity): ?>
                            <?php echo esc_html(mysql2date('M j, Y g:i A', $last_activity)); ?>
                        <?php else: ?>
                            <span class="pltnum-no-activity"><?php _e('No activity', 'pltnum-affiliate-portal'); ?></span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <div class="pltnum-affiliate-actions">
                            <button type="button" class="button button-small view-details-btn" data-affiliate-id="<?php echo esc_attr($affiliate->ID); ?>">
                                <?php _e('View Details', 'pltnum-affiliate-portal'); ?>
                            </button>
                            
                            <button type="button" class="button button-small test-capi-btn" data-affiliate-id="<?php echo esc_attr($affiliate->ID); ?>" <?php echo (!$pixel_verified || !$token_verified) ? 'disabled' : ''; ?>>
                                <?php _e('Test CAPI', 'pltnum-affiliate-portal'); ?>
                            </button>
                            
                            <?php if ($affiliate->recent_errors > 0): ?>
                            <button type="button" class="button button-small view-errors-btn" data-affiliate-id="<?php echo esc_attr($affiliate->ID); ?>">
                                <?php _e('View Errors', 'pltnum-affiliate-portal'); ?>
                            </button>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        
        <?php if (empty($affiliates)): ?>
        <div class="pltnum-no-affiliates">
            <p><?php _e('No affiliates have configured CAPI settings yet.', 'pltnum-affiliate-portal'); ?></p>
            <p><?php _e('Share the affiliate portal page with your affiliates so they can configure their Facebook Pixel ID and CAPI token.', 'pltnum-affiliate-portal'); ?></p>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Affiliate Details Modal -->
<div id="pltnum-affiliate-modal" class="pltnum-modal" style="display: none;">
    <div class="pltnum-modal-content pltnum-modal-large">
        <div class="pltnum-modal-header">
            <h3><?php _e('Affiliate Details', 'pltnum-affiliate-portal'); ?></h3>
            <button type="button" class="pltnum-modal-close">&times;</button>
        </div>
        <div class="pltnum-modal-body">
            <div id="pltnum-affiliate-details-content">
                <!-- Content will be loaded via AJAX -->
            </div>
        </div>
    </div>
</div>

<!-- Test CAPI Modal -->
<div id="pltnum-test-capi-modal" class="pltnum-modal" style="display: none;">
    <div class="pltnum-modal-content">
        <div class="pltnum-modal-header">
            <h3><?php _e('Test CAPI Event', 'pltnum-affiliate-portal'); ?></h3>
            <button type="button" class="pltnum-modal-close">&times;</button>
        </div>
        <div class="pltnum-modal-body">
            <form id="pltnum-test-capi-form">
                <input type="hidden" id="test-affiliate-id" name="affiliate_id" value="">
                
                <div class="pltnum-form-group">
                    <label for="test-event-type"><?php _e('Event Type:', 'pltnum-affiliate-portal'); ?></label>
                    <select id="test-event-type" name="event_type">
                        <option value="PageView"><?php _e('PageView', 'pltnum-affiliate-portal'); ?></option>
                        <option value="Purchase"><?php _e('Purchase', 'pltnum-affiliate-portal'); ?></option>
                        <option value="AddToCart"><?php _e('AddToCart', 'pltnum-affiliate-portal'); ?></option>
                    </select>
                </div>
                
                <div class="pltnum-form-actions">
                    <button type="submit" class="button button-primary"><?php _e('Send Test Event', 'pltnum-affiliate-portal'); ?></button>
                    <button type="button" class="button pltnum-modal-close"><?php _e('Cancel', 'pltnum-affiliate-portal'); ?></button>
                </div>
            </form>
            
            <div id="pltnum-test-result" style="display: none;">
                <h4><?php _e('Test Result:', 'pltnum-affiliate-portal'); ?></h4>
                <div id="pltnum-test-result-content"></div>
            </div>
        </div>
    </div>
</div>