jQuery(document).ready(function($) {
    // Handle bookmark button clicks
    $(document).on('click', '.hb-bookmark-btn', function(e) {
        e.preventDefault();
        
        var button = $(this);
        var postId = button.data('post-id');
        
        if (!button.hasClass('hb-logged-in')) {
            // Show email modal instead of prompt
            var postTitle = $('h1').first().text() || 'this healthcare provider';
            showEmailModal(postTitle, function(email) {
                button.prop('disabled', true).find('.hb-text').text('Sending...');
                
                $.ajax({
                    url: hb_ajax.ajax_url,
                    type: 'POST',
                    data: {
                        action: 'send_magic_link',
                        email: email,
                        post_id: postId,
                        nonce: hb_ajax.nonce
                    },
                    success: function(response) {
                        if (response.success) {
                            showSuccessMessage(response.data);
                        } else {
                            showErrorMessage('Error: ' + response.data);
                        }
                        button.prop('disabled', false).find('.hb-text').text('Bookmark');
                    },
                    error: function() {
                        showErrorMessage('Error sending magic link. Please try again.');
                        button.prop('disabled', false).find('.hb-text').text('Bookmark');
                    }
                });
            });
        } else {
            // User is logged in, toggle bookmark
            $.ajax({
                url: hb_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'toggle_bookmark',
                    post_id: postId,
                    nonce: hb_ajax.nonce
                },
                success: function(response) {
                    if (response.success) {
                        if (response.data.bookmarked) {
                            button.removeClass('hb-not-bookmarked').addClass('hb-bookmarked');
                            button.find('.hb-text').text('Bookmarked');
                        } else {
                            button.removeClass('hb-bookmarked').addClass('hb-not-bookmarked');
                            button.find('.hb-text').text('Bookmark');
                        }
                        updateBookmarkCounter();
                    }
                },
                error: function() {
                    alert('Error updating bookmark. Please try again.');
                }
            });
        }
    });
    
    // Handle remove bookmark on My Bookmarks page
    $(document).on('click', '.hb-remove-bookmark', function(e) {
        e.preventDefault();
        
        var button = $(this);
        var postId = button.data('post-id');
        var card = button.closest('.hb-bookmark-card');
        var providerName = card.find('h3 a').text() || 'this healthcare provider';
        
        showRemoveModal(providerName, function() {
            button.prop('disabled', true).text('Removing...');
            
            $.ajax({
                url: hb_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'toggle_bookmark',
                    post_id: postId,
                    nonce: hb_ajax.nonce
                },
                success: function(response) {
                    if (response.success) {
                        card.fadeOut(300, function() {
                            $(this).remove();
                            // Check if no bookmarks left
                            if ($('.hb-bookmark-card').length === 0) {
                                $('.hb-bookmarks-grid').html('<div class="hb-bookmarks-empty"><h3>No bookmarks yet</h3><p>You haven\'t bookmarked any healthcare providers yet.</p></div>');
                            }
                        });
                        updateBookmarkCounter();
                    } else {
                        button.prop('disabled', false).text('Remove');
                        showErrorMessage('Error removing bookmark. Please try again.');
                    }
                },
                error: function() {
                    button.prop('disabled', false).text('Remove');
                    showErrorMessage('Error removing bookmark. Please try again.');
                }
            });
        });
    });
    
    // Create elegant email input modal
    function showEmailModal(providerName, onSubmit) {
        // Remove existing modal if any
        $('.hb-modal').remove();
        
        var modal = $(`
            <div class="hb-modal">
                <div class="hb-modal-backdrop"></div>
                <div class="hb-modal-content">
                    <div class="hb-modal-header">
                        <h3>Bookmark Healthcare Provider</h3>
                        <button class="hb-modal-close">&times;</button>
                    </div>
                    <div class="hb-modal-body">
                        <p>Enter your email to bookmark <strong>${providerName}</strong>:</p>
                        <input type="email" class="hb-email-input" placeholder="your@email.com" autocomplete="email" />
                        <div class="hb-email-error" style="display: none;"></div>
                        <div class="hb-consent-wrapper">
                            <label class="hb-consent-label">
                                <input type="checkbox" class="hb-consent-checkbox" checked />
                                <span class="hb-consent-text">I agree to receive occasional healthcare updates and newsletters. You can unsubscribe anytime.</span>
                            </label>
                        </div>
                        <p class="hb-email-note">We'll send you a secure link to complete the bookmark.</p>
                    </div>
                    <div class="hb-modal-footer">
                        <button class="hb-modal-cancel">Cancel</button>
                        <button class="hb-modal-confirm">Send Magic Link</button>
                    </div>
                </div>
            </div>
        `);
        
        $('body').append(modal);
        
        // Show modal with animation
        setTimeout(function() {
            modal.addClass('hb-modal-show');
            modal.find('.hb-email-input').focus();
        }, 10);
        
        // Handle modal actions
        modal.find('.hb-modal-confirm').on('click', function() {
            var email = modal.find('.hb-email-input').val().trim();
            var emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            var consentChecked = modal.find('.hb-consent-checkbox').is(':checked');
            
            if (!email) {
                showEmailError('Please enter your email address.');
                return;
            }
            
            if (!emailRegex.test(email)) {
                showEmailError('Please enter a valid email address.');
                return;
            }
            
            if (!consentChecked) {
                showEmailError('Please agree to receive updates to continue.');
                return;
            }
            
            hideModal();
            onSubmit(email);
        });
        
        modal.find('.hb-modal-cancel, .hb-modal-close, .hb-modal-backdrop').on('click', function() {
            hideModal();
        });
        
        // Submit on Enter key
        modal.find('.hb-email-input').on('keypress', function(e) {
            if (e.which === 13) {
                modal.find('.hb-modal-confirm').click();
            }
        });
        
        // Close on escape key
        $(document).on('keydown.hbEmailModal', function(e) {
            if (e.keyCode === 27) {
                hideModal();
            }
        });
        
        function showEmailError(message) {
            modal.find('.hb-email-error').text(message).show();
            modal.find('.hb-email-input').addClass('error');
        }
        
        function hideModal() {
            modal.removeClass('hb-modal-show');
            setTimeout(function() {
                modal.remove();
                $(document).off('keydown.hbEmailModal');
            }, 300);
        }
    }
    
    // Create elegant remove confirmation modal
    function showRemoveModal(providerName, onConfirm) {
        // Remove existing modal if any
        $('.hb-modal').remove();
        
        var modal = $(`
            <div class="hb-modal">
                <div class="hb-modal-backdrop"></div>
                <div class="hb-modal-content">
                    <div class="hb-modal-header">
                        <h3>Remove Bookmark</h3>
                        <button class="hb-modal-close">&times;</button>
                    </div>
                    <div class="hb-modal-body">
                        <p>Are you sure you want to remove <strong>${providerName}</strong> from your bookmarks?</p>
                    </div>
                    <div class="hb-modal-footer">
                        <button class="hb-modal-cancel">Cancel</button>
                        <button class="hb-modal-confirm">Remove Bookmark</button>
                    </div>
                </div>
            </div>
        `);
        
        $('body').append(modal);
        
        // Show modal with animation
        setTimeout(function() {
            modal.addClass('hb-modal-show');
        }, 10);
        
        // Handle modal actions
        modal.find('.hb-modal-confirm').on('click', function() {
            hideModal();
            onConfirm();
        });
        
        modal.find('.hb-modal-cancel, .hb-modal-close, .hb-modal-backdrop').on('click', function() {
            hideModal();
        });
        
        // Close on escape key
        $(document).on('keydown.hbModal', function(e) {
            if (e.keyCode === 27) {
                hideModal();
            }
        });
        
        function hideModal() {
            modal.removeClass('hb-modal-show');
            setTimeout(function() {
                modal.remove();
                $(document).off('keydown.hbModal');
            }, 300);
        }
    }
    
    // Show success messages elegantly
    function showSuccessMessage(message) {
        var successDiv = $(`
            <div class="hb-success-message">
                <span>${message}</span>
                <button class="hb-success-close">&times;</button>
            </div>
        `);
        
        $('body').append(successDiv);
        
        setTimeout(function() {
            successDiv.addClass('hb-success-show');
        }, 10);
        
        // Auto-hide after 5 seconds
        setTimeout(function() {
            hideSuccess();
        }, 5000);
        
        successDiv.find('.hb-success-close').on('click', hideSuccess);
        
        function hideSuccess() {
            successDiv.removeClass('hb-success-show');
            setTimeout(function() {
                successDiv.remove();
            }, 300);
        }
    }
    
    // Show error messages elegantly
    function showErrorMessage(message) {
        var errorDiv = $(`
            <div class="hb-error-message">
                <span>${message}</span>
                <button class="hb-error-close">&times;</button>
            </div>
        `);
        
        $('body').append(errorDiv);
        
        setTimeout(function() {
            errorDiv.addClass('hb-error-show');
        }, 10);
        
        // Auto-hide after 5 seconds
        setTimeout(function() {
            hideError();
        }, 5000);
        
        errorDiv.find('.hb-error-close').on('click', hideError);
        
        function hideError() {
            errorDiv.removeClass('hb-error-show');
            setTimeout(function() {
                errorDiv.remove();
            }, 300);
        }
    }
    
    // Update bookmark counter
    function updateBookmarkCounter() {
        $.ajax({
            url: hb_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'get_bookmark_count',
                nonce: hb_ajax.nonce
            },
            success: function(response) {
                if (response.success) {
                    $('.hb-counter-number').text(response.data.count);
                }
            }
        });
    }
    
    // Mark logged in bookmark buttons and update text
    if ($('body').hasClass('logged-in')) {
        $('.hb-bookmark-btn').addClass('hb-logged-in');
        
        // Update button text for bookmarked items
        $('.hb-bookmark-btn.hb-bookmarked .hb-text').text('Bookmarked');
    }
    
    // Initial counter update on page load
    updateBookmarkCounter();
});