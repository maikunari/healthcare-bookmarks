<?php
/**
 * Healthcare Provider Bookmarks Uninstall
 *
 * This file is executed when the plugin is uninstalled.
 * It removes all database tables and options created by the plugin.
 *
 * @package HealthcareBookmarks
 * @since 1.1.0
 */

// Exit if accessed directly or not uninstalling
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

// Get WordPress database object
global $wpdb;

// Define table names
$bookmarks_table = $wpdb->prefix . 'healthcare_bookmarks';
$emails_table = $wpdb->prefix . 'healthcare_emails';

// Drop custom tables
$wpdb->query("DROP TABLE IF EXISTS $bookmarks_table");
$wpdb->query("DROP TABLE IF EXISTS $emails_table");

// Remove plugin options
delete_option('hb_email_subject');
delete_option('hb_email_message');
delete_option('hb_bookmarks_page');
delete_option('hb_convertkit_api_key');
delete_option('hb_convertkit_form_id');
delete_option('hb_convertkit_enabled');
delete_option('hb_convertkit_city_tag_format');
delete_option('hb_convertkit_specialty_tag_format');
delete_option('hb_database_version');

// Remove user meta for bookmark users
$users = get_users(array(
    'meta_key' => 'hb_bookmark_user',
    'meta_value' => true
));

foreach ($users as $user) {
    delete_user_meta($user->ID, 'hb_bookmark_user');
    delete_user_meta($user->ID, 'show_admin_bar_front');
    delete_user_meta($user->ID, 'show_admin_bar_admin');
}

// Clean up transients (magic links and rate limiting)
$wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_hb_magic_%'");
$wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_timeout_hb_magic_%'");
$wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_hb_access_%'");
$wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_timeout_hb_access_%'");
$wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_hb_rate_limit_%'");
$wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_timeout_hb_rate_limit_%'");
$wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_hb_access_rate_limit_%'");
$wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_timeout_hb_access_rate_limit_%'");

// Clear any cached data
wp_cache_flush();