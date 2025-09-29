/* PLTNUM Affiliate Portal Frontend JavaScript */

jQuery(document).ready(function($) {
    'use strict';
    
    // Verify Pixel ID button
    $('#verify-pixel-btn').on('click', function() {
        var $btn = $(this);
        var $feedback = $('#pixel-feedback');
        var pixelId = $('#pltnum-pixel-id').val().trim();
        
        if (!pixelId) {
            showFeedback($feedback, 'error', pltnum_ajax.messages.error, [
                'Please enter a Pixel ID before verifying.'
            ]);
            return;
        }
        
        // Validate pixel ID format
        if (!/^\d{15,16}$/.test(pixelId)) {
            showFeedback($feedback, 'error', 'Invalid Pixel ID format', [
                'Pixel ID should be 15-16 digits only',
                'No letters, spaces, or special characters allowed'
            ]);
            return;
        }
        
        $btn.prop('disabled', true).addClass('loading').text(pltnum_ajax.messages.verifying);
        
        $.ajax({
            url: pltnum_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'pltnum_verify_pixel',
                pixel_id: pixelId,
                nonce: pltnum_ajax.nonce
            },
            success: function(response) {
                if (response.success) {
                    showFeedback($feedback, 'success', response.message, response.suggestions);
                    updateVerificationStatus('pixel', true);
                } else {
                    showFeedback($feedback, 'error', response.message, response.suggestions);
                    updateVerificationStatus('pixel', false);
                }
            },
            error: function() {
                showFeedback($feedback, 'error', pltnum_ajax.messages.network_error, [
                    'Please check your internet connection and try again.'
                ]);
            },
            complete: function() {
                $btn.prop('disabled', false).removeClass('loading').text('Verify Pixel ID');
                refreshVerificationHistory();
            }
        });
    });
    
    // Verify CAPI Token button
    $('#verify-token-btn').on('click', function() {
        var $btn = $(this);
        var $feedback = $('#token-feedback');
        var capiToken = $('#pltnum-capi-token').val().trim();
        var pixelId = $('#pltnum-pixel-id').val().trim();
        
        if (!capiToken) {
            showFeedback($feedback, 'error', 'Please enter a CAPI token before verifying.', [
                'CAPI token is required for verification.'
            ]);
            return;
        }
        
        if (!pixelId) {
            showFeedback($feedback, 'error', 'Please enter and verify your Pixel ID first.', [
                'Pixel ID is required to verify CAPI token.'
            ]);
            return;
        }
        
        if (capiToken.length < 50) {
            showFeedback($feedback, 'error', 'CAPI token appears to be too short.', [
                'Valid CAPI tokens are typically much longer',
                'Please ensure you copied the complete token'
            ]);
            return;
        }
        
        $btn.prop('disabled', true).addClass('loading').text(pltnum_ajax.messages.verifying);
        
        $.ajax({
            url: pltnum_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'pltnum_verify_token',
                capi_token: capiToken,
                pixel_id: pixelId,
                nonce: pltnum_ajax.nonce
            },
            success: function(response) {
                if (response.success) {
                    showFeedback($feedback, 'success', response.message, response.suggestions);
                    updateVerificationStatus('token', true);
                } else {
                    showFeedback($feedback, 'error', response.message, response.suggestions);
                    updateVerificationStatus('token', false);
                }
            },
            error: function() {
                showFeedback($feedback, 'error', pltnum_ajax.messages.network_error, [
                    'Please check your internet connection and try again.'
                ]);
            },
            complete: function() {
                $btn.prop('disabled', false).removeClass('loading').text('Verify CAPI Token');
                refreshVerificationHistory();
            }
        });
    });
    
    // Refresh verification history
    $('#refresh-history-btn').on('click', function() {
        refreshVerificationHistory();
    });
    
    // Load verification history on page load
    refreshVerificationHistory();
    
    // Helper function to show feedback messages
    function showFeedback($container, type, message, suggestions) {
        $container.removeClass('success error warning').addClass(type);
        
        var html = '<h4>' + message + '</h4>';
        
        if (suggestions && suggestions.length > 0) {
            html += '<ul>';
            $.each(suggestions, function(index, suggestion) {
                html += '<li>' + suggestion + '</li>';
            });
            html += '</ul>';
        }
        
        $container.html(html).addClass('show').slideDown();
        
        // Auto-hide success messages after 5 seconds
        if (type === 'success') {
            setTimeout(function() {
                $container.slideUp();
            }, 5000);
        }
    }
    
    // Helper function to update verification status display
    function updateVerificationStatus(type, success) {
        var $section = type === 'pixel' ? $('#pixel-section') : $('#token-section');
        var $statusDiv = $section.find('.pltnum-status');
        
        if (success) {
            if ($statusDiv.length === 0) {
                var statusHtml = '<div class="pltnum-status pltnum-status-success">' +
                    '<span class="dashicons dashicons-yes-alt"></span>' +
                    (type === 'pixel' ? 'Pixel ID verified and active' : 'CAPI token verified and active') +
                    '</div>';
                $section.append(statusHtml);
            }
        } else {
            $statusDiv.remove();
        }
    }
    
    // Helper function to refresh verification history
    function refreshVerificationHistory() {
        var $container = $('#verification-history');
        var $btn = $('#refresh-history-btn');
        
        $container.html('<div class="pltnum-loading">Loading history...</div>');
        $btn.prop('disabled', true);
        
        $.ajax({
            url: pltnum_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'pltnum_get_verification_history',
                nonce: pltnum_ajax.nonce
            },
            success: function(response) {
                if (response.success && response.data.length > 0) {
                    var html = '';
                    $.each(response.data, function(index, item) {
                        html += '<div class="pltnum-history-item">';
                        html += '<div class="pltnum-history-details">';
                        html += '<div class="pltnum-history-type">' + formatVerificationType(item.type) + '</div>';
                        html += '<div class="pltnum-history-message">' + item.message + '</div>';
                        html += '<div class="pltnum-history-timestamp">' + formatTimestamp(item.timestamp) + '</div>';
                        html += '</div>';
                        html += '<div class="pltnum-history-status ' + item.status + '">' + item.status + '</div>';
                        html += '</div>';
                    });
                    $container.html(html);
                } else {
                    $container.html('<div class="pltnum-loading">No verification attempts yet.</div>');
                }
            },
            error: function() {
                $container.html('<div class="pltnum-loading">Error loading history.</div>');
            },
            complete: function() {
                $btn.prop('disabled', false);
            }
        });
    }
    
    // Helper function to format verification type
    function formatVerificationType(type) {
        switch (type) {
            case 'pixel_verification':
                return 'Pixel ID Verification';
            case 'token_verification':
                return 'CAPI Token Verification';
            default:
                return type;
        }
    }
    
    // Helper function to format timestamp
    function formatTimestamp(timestamp) {
        var date = new Date(timestamp);
        return date.toLocaleString();
    }
    
    // Auto-expand textarea based on content
    $('#pltnum-capi-token').on('input', function() {
        this.style.height = 'auto';
        this.style.height = (this.scrollHeight) + 'px';
    });
    
    // Input validation and formatting
    $('#pltnum-pixel-id').on('input', function() {
        var value = $(this).val().replace(/\D/g, ''); // Remove non-digits
        $(this).val(value);
        
        // Visual feedback for pixel ID format
        var $feedback = $('#pixel-feedback');
        if (value && !/^\d{15,16}$/.test(value)) {
            if (!$feedback.hasClass('show')) {
                showFeedback($feedback, 'warning', 'Pixel ID format check', [
                    'Pixel ID should be 15-16 digits',
                    'Current length: ' + value.length + ' digits'
                ]);
            }
        } else if ($feedback.hasClass('warning')) {
            $feedback.slideUp();
        }
    });
    
    // Copy to clipboard functionality for help text
    $(document).on('click', '.pltnum-copy-btn', function() {
        var text = $(this).data('copy');
        if (navigator.clipboard) {
            navigator.clipboard.writeText(text).then(function() {
                // Show brief success message
                alert('Copied to clipboard!');
            });
        } else {
            // Fallback for older browsers
            var textArea = document.createElement('textarea');
            textArea.value = text;
            document.body.appendChild(textArea);
            textArea.select();
            document.execCommand('copy');
            document.body.removeChild(textArea);
            alert('Copied to clipboard!');
        }
    });
    
    // Smooth scrolling for section navigation
    $('a[href^="#"]').on('click', function(e) {
        var target = $(this.getAttribute('href'));
        if (target.length) {
            e.preventDefault();
            $('html, body').stop().animate({
                scrollTop: target.offset().top - 20
            }, 500);
        }
    });
    
    // Form validation before submission
    $('form').on('submit', function(e) {
        var isValid = true;
        var firstInvalidField = null;
        
        $(this).find('input[required], textarea[required]').each(function() {
            if (!$(this).val().trim()) {
                isValid = false;
                $(this).addClass('error');
                if (!firstInvalidField) {
                    firstInvalidField = $(this);
                }
            } else {
                $(this).removeClass('error');
            }
        });
        
        if (!isValid) {
            e.preventDefault();
            if (firstInvalidField) {
                firstInvalidField.focus();
            }
        }
    });
});