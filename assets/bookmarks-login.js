jQuery(document).ready(function($) {
    // Handle login form submission on bookmarks page
    $("#hb-login-submit").on("click", function() {
        var email = $("#hb-login-email").val().trim();
        var emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        var button = $(this);
        
        if (!email) {
            showLoginError("Please enter your email address.");
            return;
        }
        
        if (!emailRegex.test(email)) {
            showLoginError("Please enter a valid email address.");
            return;
        }
        
        button.prop("disabled", true).text("Sending...");
        
        $.ajax({
            url: hb_login_ajax.ajax_url,
            type: "POST",
            data: {
                action: "send_bookmarks_access_link",
                email: email,
                nonce: hb_login_ajax.nonce
            },
            success: function(response) {
                if (response.success) {
                    $(".hb-login-form").html('<div class="hb-login-success"><h3>✅ Access Link Sent!</h3><p>Check your email and click the link to view your bookmarks.</p></div>');
                } else {
                    showLoginError(response.data);
                    button.prop("disabled", false).text("Send Access Link");
                }
            },
            error: function() {
                showLoginError("Something went wrong. Please try again.");
                button.prop("disabled", false).text("Send Access Link");
            }
        });
    });
    
    $("#hb-login-email").on("keypress", function(e) {
        if (e.which === 13) {
            $("#hb-login-submit").click();
        }
    });
    
    function showLoginError(message) {
        $(".hb-login-error").text(message).show();
        $("#hb-login-email").addClass("error");
    }
});