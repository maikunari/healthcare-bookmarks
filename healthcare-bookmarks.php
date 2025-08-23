<?php
/**
 * Plugin Name: Healthcare Provider Bookmarks
 * Description: Magic link bookmarking system for healthcare providers with email capture and ConvertKit integration
 * Version: 1.1.0
 * Author: Your Name
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: healthcare-bookmarks
 * Domain Path: /languages
 * Requires at least: 5.0
 * Tested up to: 6.4
 * Requires PHP: 7.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('HB_PLUGIN_VERSION', '1.1.0');
define('HB_PLUGIN_URL', plugin_dir_url(__FILE__));
define('HB_PLUGIN_PATH', plugin_dir_path(__FILE__));
define('HB_PLUGIN_BASENAME', plugin_basename(__FILE__));

class HealthcareBookmarks {
    
    private $table_name;
    private $emails_table;
    
    public function __construct() {
        global $wpdb;
        $this->table_name = $wpdb->prefix . 'healthcare_bookmarks';
        $this->emails_table = $wpdb->prefix . 'healthcare_emails';
        
        add_action('init', array($this, 'init'));
        add_action('plugins_loaded', array($this, 'load_textdomain'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('admin_menu', array($this, 'admin_menu'));
        add_action('wp_ajax_send_magic_link', array($this, 'send_magic_link'));
        add_action('wp_ajax_nopriv_send_magic_link', array($this, 'send_magic_link'));
        add_action('wp_ajax_toggle_bookmark', array($this, 'toggle_bookmark'));
        add_action('wp_ajax_get_bookmark_count', array($this, 'get_bookmark_count'));
        add_action('wp_ajax_nopriv_get_bookmark_count', array($this, 'get_bookmark_count'));
        add_action('wp_ajax_export_emails', array($this, 'export_emails'));
        add_action('wp_ajax_send_bookmarks_access_link', array($this, 'send_bookmarks_access_link'));
        add_action('wp_ajax_nopriv_send_bookmarks_access_link', array($this, 'send_bookmarks_access_link'));
        add_action('wp_ajax_sync_emails_to_convertkit', array($this, 'sync_emails_to_convertkit'));
        add_action('template_redirect', array($this, 'handle_magic_link'));
        add_action('admin_init', array($this, 'block_dashboard_access'));
        add_action('init', array($this, 'hide_admin_bar_for_bookmark_users'));
        add_action('after_setup_theme', array($this, 'remove_admin_bar'));
        
        register_activation_hook(__FILE__, array($this, 'create_tables'));
        add_action('init', array($this, 'register_blocks'));
        add_shortcode('healthcare_bookmarks', array($this, 'bookmarks_shortcode'));
    }
    
    public function init() {
        // Initialize plugin
    }
    
    public function load_textdomain() {
        load_plugin_textdomain(
            'healthcare-bookmarks',
            false,
            dirname(HB_PLUGIN_BASENAME) . '/languages'
        );
    }
    
    public function create_tables() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        // Bookmarks table
        $sql1 = "CREATE TABLE $this->table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) NOT NULL,
            post_id bigint(20) NOT NULL,
            city varchar(100) DEFAULT NULL,
            specialties text DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY user_post (user_id, post_id)
        ) $charset_collate;";
        
        // Emails table
        $sql2 = "CREATE TABLE $this->emails_table (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            email varchar(100) NOT NULL,
            cities text DEFAULT NULL,
            specialties text DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY email (email)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql1);
        dbDelta($sql2);
        
        // Set default options
        add_option('hb_email_subject', 'Click to bookmark [POST_TITLE]');
        add_option('hb_email_message', 'Click the link below to bookmark this healthcare provider:\n\n[MAGIC_LINK]\n\nThis link expires in 15 minutes.');
        add_option('hb_convertkit_api_key', '');
        add_option('hb_convertkit_form_id', '');
        add_option('hb_convertkit_enabled', false);
    }
    
    public function enqueue_scripts() {
        // Use file modification time for cache busting
        $js_version = filemtime(HB_PLUGIN_PATH . 'assets/bookmarks.js');
        $css_version = filemtime(HB_PLUGIN_PATH . 'assets/bookmarks.css');
        
        wp_enqueue_script(
            'healthcare-bookmarks',
            HB_PLUGIN_URL . 'assets/bookmarks.js',
            array('jquery'),
            $js_version,
            true
        );
        
        wp_enqueue_style(
            'healthcare-bookmarks',
            HB_PLUGIN_URL . 'assets/bookmarks.css',
            array(),
            $css_version
        );
        
        wp_localize_script('healthcare-bookmarks', 'hb_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('hb_nonce')
        ));
    }
    
    public function register_blocks() {
        // Bookmark Button Block
        $button_block_path = HB_PLUGIN_PATH . 'blocks/bookmark-button.js';
        wp_register_script(
            'hb-bookmark-button-block',
            HB_PLUGIN_URL . 'blocks/bookmark-button.js',
            array('wp-blocks', 'wp-element', 'wp-editor'),
            file_exists($button_block_path) ? filemtime($button_block_path) : '1.1.0'
        );
        
        register_block_type('healthcare-bookmarks/bookmark-button', array(
            'editor_script' => 'hb-bookmark-button-block',
            'render_callback' => array($this, 'render_bookmark_button')
        ));
        
        // Bookmark Counter Block
        $counter_block_path = HB_PLUGIN_PATH . 'blocks/bookmark-counter.js';
        wp_register_script(
            'hb-bookmark-counter-block',
            HB_PLUGIN_URL . 'blocks/bookmark-counter.js',
            array('wp-blocks', 'wp-element', 'wp-editor'),
            file_exists($counter_block_path) ? filemtime($counter_block_path) : '1.1.0'
        );
        
        register_block_type('healthcare-bookmarks/bookmark-counter', array(
            'editor_script' => 'hb-bookmark-counter-block',
            'render_callback' => array($this, 'render_bookmark_counter')
        ));
    }
    
    public function render_bookmark_button($attributes) {
        global $post;
        
        if (!$post || $post->post_type !== 'healthcare_provider') {
            return '';
        }
        
        $user_id = get_current_user_id();
        $is_bookmarked = false;
        
        if ($user_id) {
            global $wpdb;
            $is_bookmarked = $wpdb->get_var($wpdb->prepare(
                "SELECT id FROM $this->table_name WHERE user_id = %d AND post_id = %d",
                $user_id, $post->ID
            ));
        }
        
        $icon_class = $is_bookmarked ? 'hb-bookmarked' : 'hb-not-bookmarked';
        $button_text = $is_bookmarked ? __('Bookmarked', 'healthcare-bookmarks') : __('Bookmark', 'healthcare-bookmarks');
        
        // Feather bookmark SVG icon
        $bookmark_svg = '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m19 21-7-4-7 4V5a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2v16z"></path></svg>';
        
        return sprintf(
            '<button class="hb-bookmark-btn %s" data-post-id="%d">
                <span class="hb-icon">%s</span>
                <span class="hb-text">%s</span>
            </button>',
            esc_attr($icon_class),
            esc_attr($post->ID),
            wp_kses($bookmark_svg, array(
                'svg' => array('xmlns' => true, 'width' => true, 'height' => true, 'viewBox' => true, 'fill' => true, 'stroke' => true, 'stroke-width' => true, 'stroke-linecap' => true, 'stroke-linejoin' => true),
                'path' => array('d' => true)
            )),
            esc_html($button_text)
        );
    }
    
    public function render_bookmark_counter($attributes) {
        $user_id = get_current_user_id();
        $count = 0;
        
        if ($user_id) {
            global $wpdb;
            $count = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM $this->table_name WHERE user_id = %d",
                $user_id
            ));
        }
        
        $bookmarks_page = get_option('hb_bookmarks_page', '#');
        
        // Feather bookmark SVG icon for counter
        $bookmark_svg = '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m19 21-7-4-7 4V5a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2v16z"></path></svg>';
        
        return sprintf(
            '<a href="%s" class="hb-counter">
                <span class="hb-counter-icon">%s</span>
                <span class="hb-counter-text">' . __('My Bookmarks', 'healthcare-bookmarks') . '</span>
                <span class="hb-counter-number">%d</span>
            </a>',
            esc_url($bookmarks_page),
            wp_kses($bookmark_svg, array(
                'svg' => array('xmlns' => true, 'width' => true, 'height' => true, 'viewBox' => true, 'fill' => true, 'stroke' => true, 'stroke-width' => true, 'stroke-linecap' => true, 'stroke-linejoin' => true),
                'path' => array('d' => true)
            )),
            esc_html($count)
        );
    }
    
    public function send_magic_link() {
        check_ajax_referer('hb_nonce', 'nonce');
        
        $email = sanitize_email($_POST['email']);
        $post_id = intval($_POST['post_id']);
        
        if (!is_email($email) || !$post_id) {
            wp_send_json_error(__('Invalid email or post ID', 'healthcare-bookmarks'));
        }
        
        // Rate limiting: Check if email was sent recently (prevent spam)
        $recent_attempt = get_transient('hb_rate_limit_' . md5($email));
        if ($recent_attempt) {
            wp_send_json_error(__('Please wait a moment before requesting another link', 'healthcare-bookmarks'));
        }
        
        // Set rate limit (1 email per 2 minutes per email address)
        set_transient('hb_rate_limit_' . md5($email), true, 2 * 60);
        
        // Get city and specialties from the post
        $city = $this->get_post_city($post_id);
        $specialties = $this->get_post_specialties($post_id);
        
        // Store email with consent flag
        global $wpdb;
        $wpdb->replace($this->emails_table, array(
            'email' => $email,
            'cities' => $city ? json_encode(array($city)) : null,
            'specialties' => !empty($specialties) ? json_encode($specialties) : null,
            'created_at' => current_time('mysql')
        ));
        
        // Send to ConvertKit if enabled with city and specialty tags
        $cities = $city ? array($city) : array();
        $this->add_to_convertkit($email, $cities, $specialties);
        
        // Generate secure magic link token
        $token = wp_generate_password(32, false);
        $expires = time() + (15 * 60); // 15 minutes
        
        set_transient('hb_magic_' . $token, array(
            'email' => $email,
            'post_id' => $post_id,
            'expires' => $expires,
            'ip' => $this->get_user_ip() // Track IP for security
        ), 15 * 60);
        
        $magic_link = add_query_arg(array(
            'hb_magic' => $token
        ), home_url());
        
        // Get email template
        $subject = get_option('hb_email_subject', 'Click to bookmark [POST_TITLE]');
        $message = get_option('hb_email_message', 'Click the link below to bookmark this healthcare provider:\n\n[MAGIC_LINK]\n\nThis link expires in 15 minutes.');
        
        $post_title = get_the_title($post_id);
        $subject = str_replace('[POST_TITLE]', $post_title, $subject);
        $message = str_replace('[POST_TITLE]', $post_title, $message);
        
        // Create a proper HTML link
        $html_link = '<a href="' . esc_url($magic_link) . '" style="color: #007cba; text-decoration: none; font-weight: bold; padding: 12px 24px; background: #f0f8ff; border: 1px solid #007cba; border-radius: 4px; display: inline-block; margin: 10px 0;">Click Here to Bookmark</a>';
        
        $message = str_replace('[MAGIC_LINK]', $html_link, $message);
        
        // Clean up all possible line break variations thoroughly
        $message = str_replace(['\\n', '\n', '\\\\n'], '<br>', $message);
        $message = str_replace(['\\r', '\r', '\\\\r'], '', $message);
        $message = str_replace(['\\t', '\t'], '&nbsp;&nbsp;&nbsp;&nbsp;', $message);
        
        // Remove any remaining backslashes that might be escaping
        $message = str_replace('\\', '', $message);
        
        // Convert remaining actual line breaks
        $message = nl2br($message);
        
        // Set email headers for HTML
        $headers = array(
            'Content-Type: text/html; charset=UTF-8',
            'From: ' . get_bloginfo('name') . ' <' . get_option('admin_email') . '>'
        );
        
        $sent = wp_mail($email, $subject, $message, $headers);
        
        if ($sent) {
            // Ensure proper JSON response format
            wp_send_json_success(__('Magic link sent! Check your email.', 'healthcare-bookmarks'));
            exit; // Make sure script stops here
        } else {
            wp_send_json_error(__('Failed to send email. Please try again.', 'healthcare-bookmarks'));
            exit; // Make sure script stops here
        }
    }
    
    public function handle_magic_link() {
        // Handle bookmark access links
        if (isset($_GET['hb_access'])) {
            $this->handle_bookmarks_access_link();
            return;
        }
        
        // Handle regular magic links
        if (!isset($_GET['hb_magic'])) {
            return;
        }
        
        $token = sanitize_text_field($_GET['hb_magic']);
        $data = get_transient('hb_magic_' . $token);
        
        if (!$data) {
            wp_die('Invalid or expired magic link.', 'Magic Link Error', array('response' => 403));
        }
        
        // Security: Verify IP matches (optional - can be disabled for mobile users)
        // if ($data['ip'] !== $this->get_user_ip()) {
        //     wp_die('Security check failed.', 'Magic Link Error', array('response' => 403));
        // }
        
        $email = $data['email'];
        $post_id = $data['post_id'];
        
        // Verify post still exists and is healthcare_provider
        $post = get_post($post_id);
        if (!$post || $post->post_type !== 'healthcare_provider') {
            wp_die('Invalid healthcare provider.', 'Magic Link Error', array('response' => 404));
        }
        
        // Check if user exists
        $user = get_user_by('email', $email);
        
        if (!$user) {
            // Create new user with minimal permissions
            $username = sanitize_user(explode('@', $email)[0]);
            $counter = 1;
            $original_username = $username;
            
            while (username_exists($username)) {
                $username = $original_username . $counter;
                $counter++;
            }
            
            $user_id = wp_create_user($username, wp_generate_password(20, true), $email);
            
            if (is_wp_error($user_id)) {
                wp_die('Failed to create user account.', 'Account Error', array('response' => 500));
            }
            
            $user = get_user_by('id', $user_id);
            
            // Set minimal role and permissions
            $user->set_role('subscriber');
            
            // Mark as bookmark-only user and hide admin bar
            add_user_meta($user_id, 'hb_bookmark_user', true);
            add_user_meta($user_id, 'show_admin_bar_front', false);
            update_user_meta($user_id, 'show_admin_bar_admin', false);
        }
        
        // Log in user securely
        wp_clear_auth_cookie();
        wp_set_current_user($user->ID);
        wp_set_auth_cookie($user->ID, false, is_ssl()); // Secure cookie if SSL
        
        // Add bookmark with city and specialties
        $city = $this->get_post_city($post_id);
        $specialties = $this->get_post_specialties($post_id);
        global $wpdb;
        $result = $wpdb->replace($this->table_name, array(
            'user_id' => $user->ID,
            'post_id' => $post_id,
            'city' => $city,
            'specialties' => !empty($specialties) ? json_encode($specialties) : null,
            'created_at' => current_time('mysql')
        ));
        
        // Update user's city and specialty lists
        $this->update_user_cities($email, $city);
        $this->update_user_specialties($email, $specialties);
        
        if ($result === false) {
            wp_die('Failed to save bookmark.', 'Bookmark Error', array('response' => 500));
        }
        
        // Delete used token immediately
        delete_transient('hb_magic_' . $token);
        
        // Redirect to original post with success message
        $redirect_url = add_query_arg('hb_bookmarked', '1', get_permalink($post_id));
        wp_safe_redirect($redirect_url);
        exit;
    }
    
    private function handle_bookmarks_access_link() {
        $token = sanitize_text_field($_GET['hb_access']);
        $data = get_transient('hb_access_' . $token);
        
        if (!$data) {
            wp_die('Invalid or expired access link.', 'Access Link Error', array('response' => 403));
        }
        
        $user_id = $data['user_id'];
        $user = get_user_by('id', $user_id);
        
        if (!$user) {
            wp_die('User not found.', 'Access Link Error', array('response' => 404));
        }
        
        // Log in user securely
        wp_clear_auth_cookie();
        wp_set_current_user($user->ID);
        wp_set_auth_cookie($user->ID, false, is_ssl());
        
        // Delete used token immediately
        delete_transient('hb_access_' . $token);
        
        // Redirect to current page (bookmarks page) without the access token
        wp_safe_redirect(remove_query_arg('hb_access'));
        exit;
    }
    
    public function toggle_bookmark() {
        check_ajax_referer('hb_nonce', 'nonce');
        
        if (!is_user_logged_in()) {
            wp_send_json_error('Not logged in');
        }
        
        $user_id = get_current_user_id();
        $post_id = intval($_POST['post_id']);
        
        global $wpdb;
        
        $exists = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM $this->table_name WHERE user_id = %d AND post_id = %d",
            $user_id, $post_id
        ));
        
        if ($exists) {
            // Remove bookmark
            $wpdb->delete($this->table_name, array(
                'user_id' => $user_id,
                'post_id' => $post_id
            ));
            wp_send_json_success(array('action' => 'removed', 'bookmarked' => false));
        } else {
            // Add bookmark with city and specialties
            $city = $this->get_post_city($post_id);
            $specialties = $this->get_post_specialties($post_id);
            $wpdb->insert($this->table_name, array(
                'user_id' => $user_id,
                'post_id' => $post_id,
                'city' => $city,
                'specialties' => !empty($specialties) ? json_encode($specialties) : null,
                'created_at' => current_time('mysql')
            ));
            
            // Update user's city and specialty lists
            $user = get_user_by('id', $user_id);
            if ($user) {
                $this->update_user_cities($user->user_email, $city);
                $this->update_user_specialties($user->user_email, $specialties);
            }
            
            wp_send_json_success(array('action' => 'added', 'bookmarked' => true));
        }
    }
    
    public function get_bookmark_count() {
        $user_id = get_current_user_id();
        $count = 0;
        
        if ($user_id) {
            global $wpdb;
            $count = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM $this->table_name WHERE user_id = %d",
                $user_id
            ));
        }
        
        wp_send_json_success(array('count' => $count));
    }
    
    public function bookmarks_shortcode($atts) {
        if (!is_user_logged_in()) {
            // Show magic link login form for non-logged-in users
            return $this->render_bookmarks_login_form();
        }
        
        $user_id = get_current_user_id();
        global $wpdb;
        
        $bookmarks = $wpdb->get_results($wpdb->prepare(
            "SELECT post_id FROM $this->table_name WHERE user_id = %d ORDER BY created_at DESC",
            $user_id
        ));
        
        if (empty($bookmarks)) {
            return '<div class="hb-bookmarks-empty"><h3>' . __('No bookmarks yet', 'healthcare-bookmarks') . '</h3><p>' . __('You haven\'t bookmarked any healthcare providers yet.', 'healthcare-bookmarks') . '</p></div>';
        }
        
        $output = '<div class="hb-bookmarks-grid">';
        
        foreach ($bookmarks as $bookmark) {
            $post = get_post($bookmark->post_id);
            if (!$post) continue;
            
            $thumbnail = get_the_post_thumbnail($post->ID, 'medium');
            $title = get_the_title($post->ID);
            $permalink = get_permalink($post->ID);
            $excerpt = wp_trim_words(get_the_excerpt($post->ID), 20);
            
            $output .= sprintf(
                '<div class="hb-bookmark-card">
                    <div class="hb-bookmark-thumbnail">%s</div>
                    <div class="hb-bookmark-content">
                        <h3><a href="%s">%s</a></h3>
                        <p>%s</p>
                        <div class="hb-bookmark-actions">
                            <a href="%s" class="hb-bookmark-link">View Provider</a>
                            <button class="hb-remove-bookmark" data-post-id="%d">Remove</button>
                        </div>
                    </div>
                </div>',
                $thumbnail ?: '<div class="hb-no-image">No Image</div>',
                esc_url($permalink),
                esc_html($title),
                esc_html($excerpt),
                esc_url($permalink),
                esc_attr($post->ID)
            );
        }
        
        $output .= '</div>';
        
        return $output;
    }
    
    private function render_bookmarks_login_form() {
        // Enqueue the login script
        wp_enqueue_script(
            'hb-bookmarks-login',
            HB_PLUGIN_URL . 'assets/bookmarks-login.js',
            array('jquery'),
            filemtime(HB_PLUGIN_PATH . 'assets/bookmarks-login.js'),
            true
        );
        
        // Localize script with secure data
        wp_localize_script('hb-bookmarks-login', 'hb_login_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('hb_bookmarks_access')
        ));
        
        return '
        <div class="hb-bookmarks-login">
            <div class="hb-login-header">
                <h2>' . __('Access Your Bookmarks', 'healthcare-bookmarks') . '</h2>
                <p>' . __('Enter your email to view your saved healthcare providers.', 'healthcare-bookmarks') . '</p>
            </div>
            
            <div class="hb-login-form">
                <input type="email" id="hb-login-email" placeholder="your@email.com" />
                <button id="hb-login-submit" class="hb-login-btn">Send Access Link</button>
                <div class="hb-login-error" style="display: none;"></div>
                <p class="hb-login-note">We\'ll send you a secure link to access your bookmarks instantly.</p>
            </div>
            
            <div class="hb-login-help">
                <h3>' . __('Don\'t have any bookmarks yet?', 'healthcare-bookmarks') . '</h3>
                <p>' . __('Start building your personal healthcare directory by bookmarking providers that interest you.', 'healthcare-bookmarks') . '</p>
                <a href="' . esc_url(home_url()) . '" class="hb-browse-btn">Browse Healthcare Providers →</a>
            </div>
        </div>';
    }
    
    public function admin_menu() {
        add_options_page(
            'Healthcare Bookmarks',
            'Healthcare Bookmarks',
            'manage_options',
            'healthcare-bookmarks',
            array($this, 'admin_page')
        );
        
        add_management_page(
            'Email Subscribers',
            'Email Subscribers',
            'manage_options',
            'healthcare-emails',
            array($this, 'emails_page')
        );
    }
    
    public function admin_page() {
        // Handle form submission with nonce verification
        if (isset($_POST['submit'])) {
            // Verify nonce
            if (!isset($_POST['_wpnonce']) || !wp_verify_nonce($_POST['_wpnonce'], 'hb_settings_nonce')) {
                wp_die(__('Security check failed', 'healthcare-bookmarks'));
            }
            
            update_option('hb_email_subject', sanitize_text_field($_POST['email_subject']));
            update_option('hb_email_message', sanitize_textarea_field($_POST['email_message']));
            update_option('hb_bookmarks_page', esc_url($_POST['bookmarks_page']));
            update_option('hb_convertkit_api_key', sanitize_text_field($_POST['convertkit_api_key']));
            update_option('hb_convertkit_form_id', sanitize_text_field($_POST['convertkit_form_id']));
            update_option('hb_convertkit_enabled', isset($_POST['convertkit_enabled']) ? true : false);
            update_option('hb_convertkit_city_tag_format', sanitize_text_field($_POST['convertkit_city_tag_format']));
            update_option('hb_convertkit_specialty_tag_format', sanitize_text_field($_POST['convertkit_specialty_tag_format']));
            echo '<div class="notice notice-success"><p>' . __('Settings saved!', 'healthcare-bookmarks') . '</p></div>';
        }
        
        $email_subject = get_option('hb_email_subject', 'Click to bookmark [POST_TITLE]');
        $email_message = get_option('hb_email_message', 'Click the link below to bookmark this healthcare provider:\n\n[MAGIC_LINK]\n\nThis link expires in 15 minutes.');
        $bookmarks_page = get_option('hb_bookmarks_page', '');
        $convertkit_api_key = get_option('hb_convertkit_api_key', '');
        $convertkit_form_id = get_option('hb_convertkit_form_id', '');
        $convertkit_enabled = get_option('hb_convertkit_enabled', false);
        $convertkit_city_tag_format = get_option('hb_convertkit_city_tag_format', 'city_prefix');
        $convertkit_specialty_tag_format = get_option('hb_convertkit_specialty_tag_format', 'specialty_prefix');
        
        global $wpdb;
        $email_count = $wpdb->get_var("SELECT COUNT(*) FROM $this->emails_table");
        $bookmark_count = $wpdb->get_var("SELECT COUNT(*) FROM $this->table_name");
        
        ?>
        <div class="wrap">
            <h1>Healthcare Bookmarks Settings</h1>
            
            <div class="hb-stats" style="background: #fff; padding: 20px; margin: 20px 0; border: 1px solid #ccc;">
                <h3>Statistics</h3>
                <p><strong>Total Emails Collected:</strong> <?php echo $email_count; ?></p>
                <p><strong>Total Bookmarks:</strong> <?php echo $bookmark_count; ?></p>
            </div>
            
            <form method="post">
                <?php wp_nonce_field('hb_settings_nonce'); ?>
                <table class="form-table">
                    <tr>
                        <th scope="row">Magic Link Email Subject</th>
                        <td>
                            <input type="text" name="email_subject" value="<?php echo esc_attr($email_subject); ?>" class="regular-text" />
                            <p class="description">Use [POST_TITLE] for the post title</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Magic Link Email Message</th>
                        <td>
                            <textarea name="email_message" rows="5" class="large-text"><?php echo esc_textarea($email_message); ?></textarea>
                            <p class="description">Use [POST_TITLE] for post title and [MAGIC_LINK] for the magic link</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">My Bookmarks Page URL</th>
                        <td>
                            <input type="url" name="bookmarks_page" value="<?php echo esc_attr($bookmarks_page); ?>" class="regular-text" />
                            <p class="description">URL of the page with the [healthcare_bookmarks] shortcode</p>
                        </td>
                    </tr>
                </table>
                
                <h2>ConvertKit Integration</h2>
                <table class="form-table">
                    <tr>
                        <th scope="row">Enable ConvertKit</th>
                        <td>
                            <label>
                                <input type="checkbox" name="convertkit_enabled" <?php checked($convertkit_enabled, true); ?> />
                                Automatically add email addresses to ConvertKit
                            </label>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">ConvertKit API Key</th>
                        <td>
                            <input type="text" name="convertkit_api_key" value="<?php echo esc_attr($convertkit_api_key); ?>" class="regular-text" />
                            <p class="description">Your ConvertKit API Key (found in Account Settings → Advanced)</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">ConvertKit Form ID</th>
                        <td>
                            <input type="text" name="convertkit_form_id" value="<?php echo esc_attr($convertkit_form_id); ?>" class="regular-text" />
                            <p class="description">The Form ID to add subscribers to (found in your form's settings)</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">City Tag Format</th>
                        <td>
                            <select name="convertkit_city_tag_format">
                                <option value="city_prefix" <?php selected($convertkit_city_tag_format, 'city_prefix'); ?>>With prefix (e.g., "City: San Francisco")</option>
                                <option value="city_only" <?php selected($convertkit_city_tag_format, 'city_only'); ?>>City name only (e.g., "San Francisco")</option>
                            </select>
                            <p class="description">How city tags should be formatted in ConvertKit</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Specialty Tag Format</th>
                        <td>
                            <select name="convertkit_specialty_tag_format">
                                <option value="specialty_prefix" <?php selected($convertkit_specialty_tag_format, 'specialty_prefix'); ?>>With prefix (e.g., "Specialty: Cardiology")</option>
                                <option value="specialty_only" <?php selected($convertkit_specialty_tag_format, 'specialty_only'); ?>>Specialty name only (e.g., "Cardiology")</option>
                            </select>
                            <p class="description">How specialty tags should be formatted in ConvertKit</p>
                        </td>
                    </tr>
                </table>
                
                <?php submit_button(); ?>
            </form>
            
            <h3>Usage Instructions</h3>
            <ol>
                <li>Add the "Healthcare Bookmark Button" block to your healthcare provider posts</li>
                <li>Add the "Healthcare Bookmark Counter" block to your header/navigation</li>
                <li>Create a "My Bookmarks" page and add the shortcode: <code>[healthcare_bookmarks]</code></li>
                <li>Set the My Bookmarks page URL in the settings above</li>
            </ol>
        </div>
        <?php
    }
    
    public function emails_page() {
        global $wpdb;
        
        // Handle bulk actions with nonce verification
        if (isset($_POST['action']) && $_POST['action'] === 'delete_selected' && isset($_POST['emails'])) {
            // Verify nonce for bulk actions
            if (!isset($_POST['_wpnonce']) || !wp_verify_nonce($_POST['_wpnonce'], 'bulk_delete_emails')) {
                wp_die(__('Security check failed', 'healthcare-bookmarks'));
            }
            
            $email_ids = array_map('intval', $_POST['emails']);
            
            // Secure deletion - use individual deletes to avoid SQL injection
            $deleted = 0;
            foreach ($email_ids as $id) {
                if ($id > 0) {  // Ensure positive integer
                    $result = $wpdb->delete(
                        $this->emails_table,
                        array('id' => $id),
                        array('%d')
                    );
                    if ($result) $deleted++;
                }
            }
            
            if ($deleted > 0) {
                echo '<div class="notice notice-success"><p>' . esc_html(sprintf('%d email(s) deleted successfully.', $deleted)) . '</p></div>';
            }
        }
        
        // Pagination
        $per_page = 20;
        $current_page = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
        $offset = ($current_page - 1) * $per_page;
        
        $total_emails = $wpdb->get_var("SELECT COUNT(*) FROM $this->emails_table");
        $total_pages = ceil($total_emails / $per_page);
        
        $emails = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $this->emails_table ORDER BY created_at DESC LIMIT %d OFFSET %d",
            $per_page, $offset
        ));
        
        $convertkit_enabled = get_option('hb_convertkit_enabled', false);
        
        ?>
        <div class="wrap">
            <h1>Email Subscribers 
                <a href="#" class="page-title-action" id="export-emails">Export All</a>
                <?php if ($convertkit_enabled): ?>
                <a href="#" class="page-title-action" id="sync-convertkit">Sync to ConvertKit</a>
                <?php endif; ?>
            </h1>
            
            <div class="hb-stats" style="background: #fff; padding: 20px; margin: 20px 0; border: 1px solid #ccc;">
                <h3>Statistics</h3>
                <p><strong>Total Email Subscribers:</strong> <?php echo $total_emails; ?></p>
                <p><strong>Showing:</strong> <?php echo count($emails); ?> of <?php echo $total_emails; ?> emails</p>
            </div>
            
            <?php if (empty($emails)): ?>
                <p>No email subscribers yet.</p>
            <?php else: ?>
                <form method="post">
                    <?php wp_nonce_field('bulk_delete_emails'); ?>
                    <div class="tablenav top">
                        <div class="alignleft actions bulkactions">
                            <select name="action">
                                <option value="-1">Bulk Actions</option>
                                <option value="delete_selected">Delete</option>
                            </select>
                            <input type="submit" class="button action" value="Apply">
                        </div>
                        
                        <?php if ($total_pages > 1): ?>
                        <div class="tablenav-pages">
                            <span class="displaying-num"><?php echo $total_emails; ?> items</span>
                            <?php
                            $page_links = paginate_links(array(
                                'base' => add_query_arg('paged', '%#%'),
                                'format' => '',
                                'prev_text' => '&laquo;',
                                'next_text' => '&raquo;',
                                'total' => $total_pages,
                                'current' => $current_page,
                                'type' => 'plain'
                            ));
                            echo $page_links;
                            ?>
                        </div>
                        <?php endif; ?>
                    </div>
                    
                    <table class="wp-list-table widefat fixed striped">
                        <thead>
                            <tr>
                                <td class="manage-column column-cb check-column">
                                    <input type="checkbox" id="cb-select-all">
                                </td>
                                <th class="manage-column">Email Address</th>
                                <th class="manage-column">Cities</th>
                                <th class="manage-column">Specialties</th>
                                <th class="manage-column">Date Subscribed</th>
                                <th class="manage-column">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($emails as $email): ?>
                            <tr>
                                <th class="check-column">
                                    <input type="checkbox" name="emails[]" value="<?php echo $email->id; ?>">
                                </th>
                                <td><strong><?php echo esc_html($email->email); ?></strong></td>
                                <td>
                                    <?php 
                                    if ($email->cities) {
                                        $cities = json_decode($email->cities, true);
                                        echo esc_html(implode(', ', $cities));
                                    } else {
                                        echo '<em>No cities tracked</em>';
                                    }
                                    ?>
                                </td>
                                <td>
                                    <?php 
                                    if ($email->specialties) {
                                        $specialties = json_decode($email->specialties, true);
                                        echo esc_html(implode(', ', $specialties));
                                    } else {
                                        echo '<em>No specialties tracked</em>';
                                    }
                                    ?>
                                </td>
                                <td><?php echo date('M j, Y g:i A', strtotime($email->created_at)); ?></td>
                                <td>
                                    <a href="mailto:<?php echo esc_attr($email->email); ?>" class="button button-small">Email</a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </form>
            <?php endif; ?>
        </div>
        
        <script>
        jQuery(document).ready(function($) {
            // Select all checkbox functionality
            $('#cb-select-all').on('change', function() {
                $('input[name="emails[]"]').prop('checked', this.checked);
            });
            
            // Export emails functionality
            $('#export-emails').on('click', function(e) {
                e.preventDefault();
                
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'export_emails',
                        nonce: '<?php echo wp_create_nonce('export_emails_nonce'); ?>'
                    },
                    success: function(response) {
                        if (response.success) {
                            // Create download link
                            var blob = new Blob([response.data], { type: 'text/csv' });
                            var url = window.URL.createObjectURL(blob);
                            var a = document.createElement('a');
                            a.href = url;
                            a.download = 'healthcare-email-subscribers-' + new Date().toISOString().split('T')[0] + '.csv';
                            document.body.appendChild(a);
                            a.click();
                            document.body.removeChild(a);
                            window.URL.revokeObjectURL(url);
                        } else {
                            alert('Export failed: ' + response.data);
                        }
                    },
                    error: function() {
                        alert('Export failed. Please try again.');
                    }
                });
            });
            
            // Sync to ConvertKit functionality
            $('#sync-convertkit').on('click', function(e) {
                e.preventDefault();
                
                if (!confirm('This will sync all email addresses to ConvertKit. Continue?')) {
                    return;
                }
                
                var button = $(this);
                button.text('Syncing...').prop('disabled', true);
                
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'sync_emails_to_convertkit',
                        nonce: '<?php echo wp_create_nonce('sync_convertkit_nonce'); ?>'
                    },
                    success: function(response) {
                        if (response.success) {
                            var data = response.data;
                            alert('Sync complete!\n\nTotal: ' + data.total + '\nSuccess: ' + data.success + '\nFailed: ' + data.failed);
                        } else {
                            alert('Sync failed: ' + response.data);
                        }
                        button.text('Sync to ConvertKit').prop('disabled', false);
                    },
                    error: function() {
                        alert('Sync failed. Please try again.');
                        button.text('Sync to ConvertKit').prop('disabled', false);
                    }
                });
            });
        });
        </script>
        <?php
    }
    
    public function export_emails() {
        check_ajax_referer('export_emails_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
        }
        
        global $wpdb;
        $emails = $wpdb->get_results("SELECT email, created_at FROM $this->emails_table ORDER BY created_at DESC");
        
        // Create CSV content
        $csv_content = "Email Address,Date Subscribed\n";
        foreach ($emails as $email) {
            $csv_content .= '"' . str_replace('"', '""', $email->email) . '","' . $email->created_at . "\"\n";
        }
        
        wp_send_json_success($csv_content);
    }
    
    public function send_bookmarks_access_link() {
        check_ajax_referer('hb_bookmarks_access', 'nonce');
        
        $email = sanitize_email($_POST['email']);
        
        if (!is_email($email)) {
            wp_send_json_error('Invalid email address');
        }
        
        // Check if user exists
        $user = get_user_by('email', $email);
        
        if (!$user) {
            wp_send_json_error('No bookmarks found for this email address. Try bookmarking a provider first!');
        }
        
        // Check if user has any bookmarks
        global $wpdb;
        $bookmark_count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $this->table_name WHERE user_id = %d",
            $user->ID
        ));
        
        if ($bookmark_count == 0) {
            wp_send_json_error('No bookmarks found for this email address. Try bookmarking a provider first!');
        }
        
        // Rate limiting
        $recent_attempt = get_transient('hb_access_rate_limit_' . md5($email));
        if ($recent_attempt) {
            wp_send_json_error(__('Please wait a moment before requesting another link', 'healthcare-bookmarks'));
        }
        set_transient('hb_access_rate_limit_' . md5($email), true, 2 * 60);
        
        // Generate magic link token
        $token = wp_generate_password(32, false);
        $expires = time() + (15 * 60); // 15 minutes
        
        set_transient('hb_access_' . $token, array(
            'email' => $email,
            'user_id' => $user->ID,
            'expires' => $expires,
            'ip' => $this->get_user_ip()
        ), 15 * 60);
        
        $bookmarks_page = get_option('hb_bookmarks_page', home_url());
        $magic_link = add_query_arg(array(
            'hb_access' => $token
        ), $bookmarks_page);
        
        // Send email
        $subject = 'Access Your Healthcare Bookmarks';
        $html_link = '<a href="' . esc_url($magic_link) . '" style="color: #007cba; text-decoration: none; font-weight: bold; padding: 12px 24px; background: #f0f8ff; border: 1px solid #007cba; border-radius: 4px; display: inline-block; margin: 10px 0;">View My Bookmarks</a>';
        
        $message = 'Access your saved healthcare providers:<br><br>' . $html_link . '<br><br>This link expires in 15 minutes.';
        
        $headers = array(
            'Content-Type: text/html; charset=UTF-8',
            'From: ' . get_bloginfo('name') . ' <' . get_option('admin_email') . '>'
        );
        
        $sent = wp_mail($email, $subject, $message, $headers);
        
        if ($sent) {
            wp_send_json_success('Access link sent! Check your email.');
        } else {
            wp_send_json_error(__('Failed to send email. Please try again.', 'healthcare-bookmarks'));
        }
    }
    
    // Security Functions
    
    public function block_dashboard_access() {
        // Allow AJAX requests
        if (wp_doing_ajax()) {
            return;
        }
        
        // Allow admin users
        if (current_user_can('edit_posts')) {
            return;
        }
        
        // Block bookmark-only users from dashboard
        if (is_user_logged_in()) {
            $user_id = get_current_user_id();
            $is_bookmark_user = get_user_meta($user_id, 'hb_bookmark_user', true);
            
            if ($is_bookmark_user) {
                wp_safe_redirect(home_url());
                exit;
            }
        }
    }
    
    public function hide_admin_bar_for_bookmark_users() {
        if (is_user_logged_in()) {
            $user_id = get_current_user_id();
            $is_bookmark_user = get_user_meta($user_id, 'hb_bookmark_user', true);
            
            if ($is_bookmark_user) {
                show_admin_bar(false);
                add_filter('show_admin_bar', '__return_false');
            }
        }
    }
    
    public function remove_admin_bar() {
        if (is_user_logged_in()) {
            $user_id = get_current_user_id();
            $is_bookmark_user = get_user_meta($user_id, 'hb_bookmark_user', true);
            
            if ($is_bookmark_user) {
                show_admin_bar(false);
                add_filter('show_admin_bar', '__return_false');
                remove_action('wp_head', '_admin_bar_bump_cb');
            }
        }
    }
    
    private function get_user_ip() {
        // Get real IP address, handling proxies and CDNs
        if (!empty($_SERVER['HTTP_CF_CONNECTING_IP'])) {
            return sanitize_text_field($_SERVER['HTTP_CF_CONNECTING_IP']); // Cloudflare
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ips = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
            return sanitize_text_field(trim($ips[0])); // First IP in chain
        } elseif (!empty($_SERVER['HTTP_X_REAL_IP'])) {
            return sanitize_text_field($_SERVER['HTTP_X_REAL_IP']); // Nginx real IP
        } else {
            return sanitize_text_field($_SERVER['REMOTE_ADDR']); // Standard IP
        }
    }
    
    // Helper Functions
    
    private function get_post_city($post_id) {
        // Get city from the 'location' taxonomy
        $terms = wp_get_post_terms($post_id, 'location', array('fields' => 'names'));
        
        if (!empty($terms) && !is_wp_error($terms)) {
            // Return the first location term as the city
            return sanitize_text_field($terms[0]);
        }
        
        return '';
    }
    
    private function get_post_specialties($post_id) {
        // Get specialties from the 'specialties' taxonomy
        $terms = wp_get_post_terms($post_id, 'specialties', array('fields' => 'names'));
        
        if (!empty($terms) && !is_wp_error($terms)) {
            // Return all specialties as an array
            return array_map('sanitize_text_field', $terms);
        }
        
        return array();
    }
    
    private function update_user_cities($email, $city) {
        if (!$city) return;
        
        global $wpdb;
        $existing = $wpdb->get_row($wpdb->prepare(
            "SELECT cities FROM $this->emails_table WHERE email = %s",
            $email
        ));
        
        if ($existing) {
            $cities = $existing->cities ? json_decode($existing->cities, true) : array();
            if (!in_array($city, $cities)) {
                $cities[] = $city;
                $wpdb->update(
                    $this->emails_table,
                    array('cities' => json_encode($cities)),
                    array('email' => $email)
                );
            }
        }
    }
    
    private function update_user_specialties($email, $specialties) {
        if (empty($specialties)) return;
        
        global $wpdb;
        $existing = $wpdb->get_row($wpdb->prepare(
            "SELECT specialties FROM $this->emails_table WHERE email = %s",
            $email
        ));
        
        if ($existing) {
            $existing_specialties = $existing->specialties ? json_decode($existing->specialties, true) : array();
            
            // Merge new specialties with existing ones
            foreach ($specialties as $specialty) {
                if (!in_array($specialty, $existing_specialties)) {
                    $existing_specialties[] = $specialty;
                }
            }
            
            $wpdb->update(
                $this->emails_table,
                array('specialties' => json_encode($existing_specialties)),
                array('email' => $email)
            );
        }
    }
    
    // ConvertKit Integration Functions
    
    private function add_to_convertkit($email, $cities = array(), $specialties = array()) {
        if (!get_option('hb_convertkit_enabled')) {
            return false;
        }
        
        $api_key = get_option('hb_convertkit_api_key');
        $form_id = get_option('hb_convertkit_form_id');
        
        if (empty($api_key) || empty($form_id)) {
            return false;
        }
        
        // Prepare tags for cities and specialties
        $tags = array();
        
        // Add city tags
        if (!empty($cities)) {
            foreach ($cities as $city) {
                // Create tag like "City: San Francisco" or just city name based on settings
                $tag_format = get_option('hb_convertkit_city_tag_format', 'city_prefix');
                if ($tag_format === 'city_prefix') {
                    $tags[] = 'City: ' . $city;
                } else {
                    $tags[] = $city;
                }
            }
        }
        
        // Add specialty tags
        if (!empty($specialties)) {
            foreach ($specialties as $specialty) {
                // Create tag like "Specialty: Cardiology" or just specialty name based on settings
                $tag_format = get_option('hb_convertkit_specialty_tag_format', 'specialty_prefix');
                if ($tag_format === 'specialty_prefix') {
                    $tags[] = 'Specialty: ' . $specialty;
                } else {
                    $tags[] = $specialty;
                }
            }
        }
        
        $url = 'https://api.convertkit.com/v3/forms/' . $form_id . '/subscribe';
        
        $body_data = array(
            'api_key' => $api_key,
            'email' => $email
        );
        
        // Add tags if we have any
        if (!empty($tags)) {
            $body_data['tags'] = $tags;
        }
        
        $args = array(
            'body' => json_encode($body_data),
            'headers' => array(
                'Content-Type' => 'application/json'
            ),
            'timeout' => 30
        );
        
        $response = wp_remote_post($url, $args);
        
        if (is_wp_error($response)) {
            error_log('ConvertKit API Error: ' . $response->get_error_message());
            return false;
        }
        
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        
        if (isset($data['subscription'])) {
            return true;
        }
        
        error_log('ConvertKit API Response: ' . $body);
        return false;
    }
    
    public function sync_emails_to_convertkit() {
        check_ajax_referer('sync_convertkit_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
        }
        
        if (!get_option('hb_convertkit_enabled')) {
            wp_send_json_error('ConvertKit integration is not enabled');
        }
        
        global $wpdb;
        $emails = $wpdb->get_results("SELECT email, cities, specialties FROM $this->emails_table", ARRAY_A);
        
        $total = count($emails);
        $success = 0;
        $failed = 0;
        
        foreach ($emails as $row) {
            $cities = $row['cities'] ? json_decode($row['cities'], true) : array();
            $specialties = $row['specialties'] ? json_decode($row['specialties'], true) : array();
            if ($this->add_to_convertkit($row['email'], $cities, $specialties)) {
                $success++;
            } else {
                $failed++;
            }
            
            // Add small delay to avoid rate limiting
            usleep(100000); // 0.1 second delay
        }
        
        wp_send_json_success(array(
            'total' => $total,
            'success' => $success,
            'failed' => $failed
        ));
    }
}

// Initialize the plugin
new HealthcareBookmarks();
?>