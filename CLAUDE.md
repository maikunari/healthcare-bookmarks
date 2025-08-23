# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

This is a WordPress plugin called "Healthcare Provider Bookmarks" that implements a magic link bookmarking system for healthcare providers with email capture capabilities and ConvertKit integration for advanced email marketing segmentation. The plugin is designed to work with the `healthcare_provider` custom post type.

## Architecture

### Core Plugin Structure
- **Main Plugin File**: `healthcare-bookmarks.php` - Contains the main `HealthcareBookmarks` class with all core functionality
- **Frontend Assets**: `assets/` directory contains JavaScript and CSS files
- **Gutenberg Blocks**: `blocks/` directory contains block registration JavaScript

### Database Tables
The plugin creates two custom tables:
- `wp_healthcare_bookmarks` - Stores user bookmarks with location and specialty tracking
  - `user_id` - WordPress user ID
  - `post_id` - Healthcare provider post ID
  - `city` - City from the location taxonomy
  - `specialties` - JSON array of specialties from the specialties taxonomy
  - `created_at` - Timestamp
- `wp_healthcare_emails` - Stores captured email addresses with interest tracking
  - `email` - Email address
  - `cities` - JSON array of all cities user has shown interest in
  - `specialties` - JSON array of all specialties user has shown interest in
  - `created_at` - Timestamp

### Key Features Implementation
1. **Magic Link Authentication**: Passwordless login via email tokens (15-minute expiration)
2. **Bookmark Management**: AJAX-powered bookmark toggling for logged-in users
3. **Email Capture**: Collects emails with consent when non-logged-in users bookmark
4. **Location & Specialty Tracking**: Automatically tracks user interests based on bookmarked providers
5. **ConvertKit Integration**: Automatic subscriber management with city and specialty tagging
6. **Security**: Rate limiting, nonce verification, IP tracking, one-time tokens

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

## ConvertKit Integration

### Setup
1. **Get API Credentials**:
   - Log into Kit.com (ConvertKit)
   - Go to Account Settings → Advanced
   - Copy your API Key
   - Get Form ID from your form's settings

2. **Configure in WordPress**:
   - Navigate to Settings → Healthcare Bookmarks
   - Enable ConvertKit integration
   - Enter API Key and Form ID
   - Choose tag formats for cities and specialties

### Tag Management
- **City Tags**: Extracted from the `location` taxonomy (first term)
- **Specialty Tags**: Extracted from the `specialties` taxonomy (all terms)
- **Tag Formats**: 
  - With prefix: "City: San Francisco", "Specialty: Cardiology"
  - Without prefix: "San Francisco", "Cardiology"

### Segmentation Capabilities
- Segment by geographic location (cities)
- Segment by medical interests (specialties)
- Create compound segments (e.g., Cardiology + San Francisco)
- Track multiple interests per user

## Taxonomy Requirements

The plugin expects these taxonomies on the `healthcare_provider` post type:
- **location**: For geographic categorization (cities)
- **specialties**: For medical specialty categorization

## Important Considerations

1. **Post Type Dependency**: Requires `healthcare_provider` custom post type to be registered
2. **Taxonomy Dependencies**: Requires `location` and `specialties` taxonomies
3. **WordPress Version**: Requires WordPress 5.0+ for Gutenberg blocks
4. **jQuery Dependency**: Frontend JavaScript relies on jQuery (loaded by WordPress)
5. **Email Functionality**: Requires working `wp_mail()` configuration
6. **ConvertKit API**: Requires valid API key and form ID for integration
7. **Security**: All user inputs are sanitized, SQL queries use prepared statements
8. **Rate Limiting**: 2-minute cooldown between magic link requests per email
9. **Database Updates**: After updating plugin code, deactivate and reactivate to update database schema