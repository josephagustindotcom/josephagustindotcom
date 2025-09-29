<div class="pltnum-affiliate-portal">
    <h2><?php _e('PLTNUM Affiliate Portal - CAPI Configuration', 'pltnum-affiliate-portal'); ?></h2>
    
    <div class="pltnum-portal-sections">
        <!-- Pixel ID Section -->
        <div class="pltnum-section" id="pixel-section">
            <h3><?php _e('Facebook Pixel Configuration', 'pltnum-affiliate-portal'); ?></h3>
            
            <div class="pltnum-form-group">
                <label for="pltnum-pixel-id"><?php _e('Facebook Pixel ID:', 'pltnum-affiliate-portal'); ?></label>
                <input type="text" id="pltnum-pixel-id" name="pixel_id" value="<?php echo esc_attr($pixel_id); ?>" placeholder="<?php _e('Enter your 15-16 digit Pixel ID', 'pltnum-affiliate-portal'); ?>">
                <button type="button" id="verify-pixel-btn" class="pltnum-btn pltnum-btn-primary">
                    <?php _e('Verify Pixel ID', 'pltnum-affiliate-portal'); ?>
                </button>
            </div>
            
            <div id="pixel-feedback" class="pltnum-feedback"></div>
            
            <?php if (!empty($pixel_id)): ?>
                <div class="pltnum-status pltnum-status-success">
                    <span class="dashicons dashicons-yes-alt"></span>
                    <?php _e('Pixel ID verified and active', 'pltnum-affiliate-portal'); ?>
                </div>
            <?php endif; ?>
        </div>
        
        <!-- CAPI Token Section -->
        <div class="pltnum-section" id="token-section">
            <h3><?php _e('CAPI Token Configuration', 'pltnum-affiliate-portal'); ?></h3>
            
            <div class="pltnum-form-group">
                <label for="pltnum-capi-token"><?php _e('CAPI Access Token:', 'pltnum-affiliate-portal'); ?></label>
                <textarea id="pltnum-capi-token" name="capi_token" placeholder="<?php _e('Enter your CAPI access token', 'pltnum-affiliate-portal'); ?>"><?php echo esc_textarea($capi_token); ?></textarea>
                <button type="button" id="verify-token-btn" class="pltnum-btn pltnum-btn-primary">
                    <?php _e('Verify CAPI Token', 'pltnum-affiliate-portal'); ?>
                </button>
            </div>
            
            <div id="token-feedback" class="pltnum-feedback"></div>
            
            <?php if (!empty($capi_token)): ?>
                <div class="pltnum-status pltnum-status-success">
                    <span class="dashicons dashicons-yes-alt"></span>
                    <?php _e('CAPI token verified and active', 'pltnum-affiliate-portal'); ?>
                </div>
            <?php endif; ?>
        </div>
        
        <!-- Verification History Section -->
        <div class="pltnum-section" id="history-section">
            <h3><?php _e('Recent Verification Attempts', 'pltnum-affiliate-portal'); ?></h3>
            
            <div id="verification-history" class="pltnum-history-container">
                <div class="pltnum-loading"><?php _e('Loading history...', 'pltnum-affiliate-portal'); ?></div>
            </div>
            
            <button type="button" id="refresh-history-btn" class="pltnum-btn pltnum-btn-secondary">
                <?php _e('Refresh History', 'pltnum-affiliate-portal'); ?>
            </button>
        </div>
        
        <!-- Help Section -->
        <div class="pltnum-section pltnum-help-section">
            <h3><?php _e('Need Help?', 'pltnum-affiliate-portal'); ?></h3>
            
            <div class="pltnum-help-content">
                <div class="pltnum-help-item">
                    <h4><?php _e('How to find your Pixel ID:', 'pltnum-affiliate-portal'); ?></h4>
                    <ol>
                        <li><?php _e('Go to Facebook Business Manager', 'pltnum-affiliate-portal'); ?></li>
                        <li><?php _e('Navigate to Events Manager', 'pltnum-affiliate-portal'); ?></li>
                        <li><?php _e('Select your pixel and copy the ID (15-16 digits)', 'pltnum-affiliate-portal'); ?></li>
                    </ol>
                </div>
                
                <div class="pltnum-help-item">
                    <h4><?php _e('How to generate a CAPI token:', 'pltnum-affiliate-portal'); ?></h4>
                    <ol>
                        <li><?php _e('Go to Facebook Business Manager', 'pltnum-affiliate-portal'); ?></li>
                        <li><?php _e('Navigate to Events Manager > Settings', 'pltnum-affiliate-portal'); ?></li>
                        <li><?php _e('Click "Generate Access Token" in the Conversions API section', 'pltnum-affiliate-portal'); ?></li>
                        <li><?php _e('Copy the full token string (starts with "EAA")', 'pltnum-affiliate-portal'); ?></li>
                    </ol>
                </div>
                
                <div class="pltnum-help-item">
                    <h4><?php _e('Common Error Solutions:', 'pltnum-affiliate-portal'); ?></h4>
                    <ul>
                        <li><strong><?php _e('Invalid Pixel ID:', 'pltnum-affiliate-portal'); ?></strong> <?php _e('Ensure the ID is exactly 15-16 digits with no spaces or special characters', 'pltnum-affiliate-portal'); ?></li>
                        <li><strong><?php _e('Token Expired:', 'pltnum-affiliate-portal'); ?></strong> <?php _e('Generate a new token in Facebook Business Manager', 'pltnum-affiliate-portal'); ?></li>
                        <li><strong><?php _e('Access Denied:', 'pltnum-affiliate-portal'); ?></strong> <?php _e('Ensure you have proper permissions for the pixel and ad account', 'pltnum-affiliate-portal'); ?></li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>