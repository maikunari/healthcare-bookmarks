# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

This is a WordPress plugin called "Healthcare Provider Bookmarks" that implements a magic link bookmarking system for healthcare providers with email capture capabilities. The plugin is designed to work with the `healthcare_provider` custom post type.

## Architecture

### Core Plugin Structure
- **Main Plugin File**: `healthcare-bookmarks.php` - Contains the main `HealthcareBookmarks` class with all core functionality
- **Frontend Assets**: `assets/` directory contains JavaScript and CSS files
- **Gutenberg Blocks**: `blocks/` directory contains block registration JavaScript

### Database Tables
The plugin creates two custom tables:
- `wp_healthcare_bookmarks` - Stores user bookmarks (user_id, post_id, created_at)
- `wp_healthcare_emails` - Stores captured email addresses for marketing

### Key Features Implementation
1. **Magic Link Authentication**: Passwordless login via email tokens (15-minute expiration)
2. **Bookmark Management**: AJAX-powered bookmark toggling for logged-in users
3. **Email Capture**: Collects emails with consent when non-logged-in users bookmark
4. **Security**: Rate limiting, nonce verification, IP tracking, one-time tokens

## Development Commands

Since this is a WordPress plugin, there are no build commands. The plugin follows standard WordPress development practices:

### Plugin Activation/Testing
```bash
# Activate plugin via WP-CLI (if available)
wp plugin activate healthcare-bookmarks

# Deactivate plugin
wp plugin deactivate healthcare-bookmarks

# Check plugin status
wp plugin status healthcare-bookmarks
```

### Database Operations
```bash
# Reset plugin tables (requires deactivation/reactivation)
wp plugin deactivate healthcare-bookmarks && wp plugin activate healthcare-bookmarks
```

## Key Implementation Details

### AJAX Actions
All AJAX endpoints are prefixed and use nonce verification:
- `send_magic_link` - Sends magic link email to user
- `toggle_bookmark` - Add/remove bookmark for logged-in users
- `get_bookmark_count` - Retrieve user's bookmark count
- `send_bookmarks_access_link` - Send access link to existing users
- `export_emails` - Export email list as CSV (admin only)

### User Management
- Creates minimal-permission users automatically via magic links
- Bookmark-only users have `subscriber` role with restricted dashboard access
- Admin bar is hidden for bookmark users via multiple hooks

### Email System
- Uses `wp_mail()` with HTML content type
- Templates customizable via admin settings
- Placeholders: `[POST_TITLE]` and `[MAGIC_LINK]`

### Shortcodes & Blocks
- Shortcode: `[healthcare_bookmarks]` - Displays user's bookmarks grid
- Block: `healthcare-bookmarks/bookmark-button` - Bookmark button for posts
- Block: `healthcare-bookmarks/bookmark-counter` - Shows bookmark count in header

## Important Considerations

1. **Post Type Dependency**: Requires `healthcare_provider` custom post type to be registered
2. **WordPress Version**: Requires WordPress 5.0+ for Gutenberg blocks
3. **jQuery Dependency**: Frontend JavaScript relies on jQuery (loaded by WordPress)
4. **Email Functionality**: Requires working `wp_mail()` configuration
5. **Security**: All user inputs are sanitized, SQL queries use prepared statements
6. **Rate Limiting**: 2-minute cooldown between magic link requests per email