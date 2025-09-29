/* PLTNUM Admin Dashboard JavaScript */

jQuery(document).ready(function($) {
    'use strict';
    
    // Filter form handling
    $('#pltnum-filter-form').on('submit', function(e) {
        e.preventDefault();
        filterEvents();
    });
    
    // Reset filters button
    $('#reset-filters').on('click', function() {
        $('#pltnum-filter-form')[0].reset();
        $('#filter-date-from').val(getDateString(-7));
        $('#filter-date-to').val(getDateString(0));
        filterEvents();
    });
    
    // View response button
    $(document).on('click', '.view-response-btn', function() {
        var response = $(this).data('response');
        showResponseModal(response);
    });
    
    // View details button
    $(document).on('click', '.view-details-btn', function() {
        var affiliateId = $(this).data('affiliate-id');
        showAffiliateDetails(affiliateId);
    });
    
    // Test CAPI button
    $(document).on('click', '.test-capi-btn', function() {
        var affiliateId = $(this).data('affiliate-id');
        showTestCapiModal(affiliateId);
    });
    
    // View errors button
    $(document).on('click', '.view-errors-btn', function() {
        var affiliateId = $(this).data('affiliate-id');
        filterEventsByAffiliate(affiliateId, 'error');
    });
    
    // Test CAPI form submission
    $('#pltnum-test-capi-form').on('submit', function(e) {
        e.preventDefault();
        sendTestEvent();
    });
    
    // Modal close buttons
    $('.pltnum-modal-close').on('click', function() {
        $(this).closest('.pltnum-modal').hide();
    });
    
    // Close modal when clicking outside
    $('.pltnum-modal').on('click', function(e) {
        if (e.target === this) {
            $(this).hide();
        }
    });
    
    // Auto-refresh events every 30 seconds on diagnostics page
    if ($('#pltnum-events-table').length > 0) {
        setInterval(function() {
            refreshEvents();
        }, 30000);
    }
    
    // Helper function to filter events
    function filterEvents() {
        var $loading = $('#pltnum-events-loading');
        var $tbody = $('#pltnum-events-tbody');
        
        $loading.show();
        $tbody.empty();
        
        var formData = {
            action: 'pltnum_admin_filter_events',
            nonce: pltnum_admin_ajax.nonce,
            affiliate_id: $('#filter-affiliate').val(),
            status: $('#filter-status').val(),
            date_from: $('#filter-date-from').val(),
            date_to: $('#filter-date-to').val()
        };
        
        $.ajax({
            url: pltnum_admin_ajax.ajax_url,
            type: 'POST',
            data: formData,
            success: function(response) {
                if (response.success) {
                    displayEvents(response.data);
                } else {
                    $tbody.html('<tr><td colspan="7">Error loading events</td></tr>');
                }
            },
            error: function() {
                $tbody.html('<tr><td colspan="7">Network error occurred</td></tr>');
            },
            complete: function() {
                $loading.hide();
            }
        });
    }
    
    // Helper function to refresh events
    function refreshEvents() {
        $.ajax({
            url: pltnum_admin_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'pltnum_admin_get_events',
                nonce: pltnum_admin_ajax.nonce
            },
            success: function(response) {
                if (response.success) {
                    displayEvents(response.data);
                }
            }
        });
    }
    
    // Helper function to display events in table
    function displayEvents(events) {
        var $tbody = $('#pltnum-events-tbody');
        
        if (events.length === 0) {
            $tbody.html('<tr><td colspan="7" class="pltnum-no-events">No events found for the selected criteria.</td></tr>');
            return;
        }
        
        var html = '';
        $.each(events, function(index, event) {
            html += '<tr class="pltnum-event-row pltnum-status-' + event.status + '">';
            html += '<td>' + formatTimestamp(event.timestamp) + '</td>';
            html += '<td>';
            html += '<strong>' + (event.affiliate_name || 'Unknown') + '</strong><br>';
            html += '<small>ID: ' + event.affiliate_id + '</small>';
            html += '</td>';
            html += '<td><span class="pltnum-event-type">' + event.event_type + '</span></td>';
            html += '<td>' + (event.order_id || '') + '</td>';
            html += '<td><span class="pltnum-status-badge pltnum-status-' + event.status + '">' + 
                    event.status.charAt(0).toUpperCase() + event.status.slice(1) + '</span></td>';
            html += '<td>';
            
            if (event.status === 'error') {
                html += '<div class="pltnum-error-details">';
                if (event.error_code) {
                    html += '<strong>Code:</strong> ' + event.error_code + '<br>';
                }
                if (event.error_message) {
                    html += '<strong>Message:</strong> ' + event.error_message;
                }
                html += '</div>';
            } else {
                html += '<span class="pltnum-no-error">No errors</span>';
            }
            
            html += '</td>';
            html += '<td>';
            html += '<button type="button" class="button button-small view-response-btn" data-response="' + 
                    encodeURIComponent(event.fb_response || '') + '">View Response</button>';
            html += '</td>';
            html += '</tr>';
        });
        
        $tbody.html(html);
    }
    
    // Helper function to show response modal
    function showResponseModal(response) {
        var decodedResponse = decodeURIComponent(response);
        
        if (!decodedResponse || decodedResponse === 'null' || decodedResponse === '') {
            decodedResponse = 'No response data available';
        } else {
            try {
                // Try to format JSON
                var parsed = JSON.parse(decodedResponse);
                decodedResponse = JSON.stringify(parsed, null, 2);
            } catch (e) {
                // Use as-is if not valid JSON
            }
        }
        
        $('#pltnum-response-content').text(decodedResponse);
        $('#pltnum-response-modal').show();
    }
    
    // Helper function to show affiliate details
    function showAffiliateDetails(affiliateId) {
        var $modal = $('#pltnum-affiliate-modal');
        var $content = $('#pltnum-affiliate-details-content');
        
        $content.html('<div class="pltnum-loading">Loading affiliate details...</div>');
        $modal.show();
        
        // For now, we'll show recent events for this affiliate
        $.ajax({
            url: pltnum_admin_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'pltnum_admin_filter_events',
                nonce: pltnum_admin_ajax.nonce,
                affiliate_id: affiliateId,
                status: '',
                date_from: getDateString(-30),
                date_to: getDateString(0)
            },
            success: function(response) {
                if (response.success) {
                    var html = '<h4>Recent Events (Last 30 Days)</h4>';
                    
                    if (response.data.length > 0) {
                        html += '<table class="wp-list-table widefat fixed striped">';
                        html += '<thead><tr>';
                        html += '<th>Timestamp</th>';
                        html += '<th>Event Type</th>';
                        html += '<th>Status</th>';
                        html += '<th>Details</th>';
                        html += '</tr></thead><tbody>';
                        
                        $.each(response.data, function(index, event) {
                            html += '<tr class="pltnum-status-' + event.status + '">';
                            html += '<td>' + formatTimestamp(event.timestamp) + '</td>';
                            html += '<td>' + event.event_type + '</td>';
                            html += '<td><span class="pltnum-status-badge pltnum-status-' + event.status + '">' + 
                                    event.status.charAt(0).toUpperCase() + event.status.slice(1) + '</span></td>';
                            html += '<td>';
                            if (event.status === 'error') {
                                html += (event.error_code ? 'Code: ' + event.error_code + '<br>' : '') +
                                       (event.error_message ? 'Message: ' + event.error_message : '');
                            } else {
                                html += 'Success';
                            }
                            html += '</td>';
                            html += '</tr>';
                        });
                        
                        html += '</tbody></table>';
                    } else {
                        html += '<p>No events found for this affiliate in the last 30 days.</p>';
                    }
                    
                    $content.html(html);
                } else {
                    $content.html('<p>Error loading affiliate details.</p>');
                }
            },
            error: function() {
                $content.html('<p>Network error occurred while loading details.</p>');
            }
        });
    }
    
    // Helper function to show test CAPI modal
    function showTestCapiModal(affiliateId) {
        $('#test-affiliate-id').val(affiliateId);
        $('#pltnum-test-result').hide();
        $('#pltnum-test-capi-form')[0].reset();
        $('#pltnum-test-capi-modal').show();
    }
    
    // Helper function to send test event
    function sendTestEvent() {
        var $form = $('#pltnum-test-capi-form');
        var $result = $('#pltnum-test-result');
        var $content = $('#pltnum-test-result-content');
        var $submitBtn = $form.find('button[type="submit"]');
        
        $submitBtn.prop('disabled', true).text('Sending...');
        $result.hide();
        
        $.ajax({
            url: pltnum_admin_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'pltnum_test_capi_event',
                nonce: pltnum_admin_ajax.nonce,
                affiliate_id: $('#test-affiliate-id').val(),
                event_type: $('#test-event-type').val()
            },
            success: function(response) {
                var resultClass = response.success ? 'success' : 'error';
                var message = response.message || (response.success ? 'Test successful' : 'Test failed');
                
                $content.html('<div class="notice notice-' + resultClass + '"><p>' + message + '</p></div>');
                
                if (response.events_received) {
                    $content.append('<p><strong>Events received by Facebook:</strong> ' + response.events_received + '</p>');
                }
                
                $result.show();
            },
            error: function() {
                $content.html('<div class="notice notice-error"><p>Network error occurred during test.</p></div>');
                $result.show();
            },
            complete: function() {
                $submitBtn.prop('disabled', false).text('Send Test Event');
            }
        });
    }
    
    // Helper function to filter events by affiliate and status
    function filterEventsByAffiliate(affiliateId, status) {
        $('#filter-affiliate').val(affiliateId);
        $('#filter-status').val(status || '');
        $('#filter-date-from').val(getDateString(-7));
        $('#filter-date-to').val(getDateString(0));
        filterEvents();
        
        // Scroll to events table
        $('html, body').animate({
            scrollTop: $('#pltnum-events-table').offset().top - 20
        }, 500);
    }
    
    // Helper function to format timestamp
    function formatTimestamp(timestamp) {
        var date = new Date(timestamp);
        return date.toLocaleString('en-US', {
            month: 'short',
            day: 'numeric',
            year: 'numeric',
            hour: 'numeric',
            minute: '2-digit',
            hour12: true
        });
    }
    
    // Helper function to get date string
    function getDateString(daysOffset) {
        var date = new Date();
        date.setDate(date.getDate() + daysOffset);
        return date.toISOString().split('T')[0];
    }
    
    // Initialize date filters on load
    $('#filter-date-from').val(getDateString(-7));
    $('#filter-date-to').val(getDateString(0));
    
    // Auto-expand tables on mobile
    function makeTablesResponsive() {
        $('.wp-list-table').each(function() {
            if ($(this).width() > $(this).parent().width()) {
                $(this).parent().css('overflow-x', 'auto');
            }
        });
    }
    
    // Check table responsiveness on load and resize
    makeTablesResponsive();
    $(window).on('resize', makeTablesResponsive);
    
    // Add loading indicators to buttons
    $('.button').on('click', function() {
        var $btn = $(this);
        if (!$btn.hasClass('no-loading') && !$btn.prop('disabled')) {
            $btn.addClass('loading');
            setTimeout(function() {
                $btn.removeClass('loading');
            }, 5000); // Remove loading class after 5 seconds as fallback
        }
    });
    
    // Keyboard shortcuts
    $(document).on('keydown', function(e) {
        // ESC to close modals
        if (e.keyCode === 27) {
            $('.pltnum-modal:visible').hide();
        }
        
        // Ctrl+R to refresh events (prevent default browser refresh)
        if (e.ctrlKey && e.keyCode === 82 && $('#pltnum-events-table').length > 0) {
            e.preventDefault();
            refreshEvents();
        }
    });
    
    // Tooltip functionality for truncated text
    $('[data-tooltip]').hover(
        function() {
            var tooltip = $(this).data('tooltip');
            if (tooltip) {
                $(this).append('<div class="pltnum-tooltip">' + tooltip + '</div>');
            }
        },
        function() {
            $('.pltnum-tooltip').remove();
        }
    );
});