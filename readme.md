# Healthcare Provider Bookmarks

A WordPress plugin that provides magic link bookmarking functionality for healthcare providers with email capture for marketing.

## Features

- 🔗 Magic link authentication (passwordless login)
- 📧 Email capture for marketing campaigns
- 🏥 Healthcare provider specific bookmarking
- 📱 Mobile responsive design
- 🎨 Gutenberg blocks for easy integration
- 📊 Admin dashboard with statistics
- ⚡ AJAX-powered interactions

## Installation

1. Download or clone this repository
2. Upload the `healthcare-bookmarks` folder to `/wp-content/plugins/`
3. Activate the plugin through the 'Plugins' menu in WordPress
4. Configure settings at **Settings → Healthcare Bookmarks**

## Setup Guide

### 1. Configure Plugin Settings
- Go to **Settings → Healthcare Bookmarks**
- Customize email templates for magic links
- Set your "My Bookmarks" page URL

### 2. Create My Bookmarks Page
- Create a new page (e.g., "My Bookmarks")
- Add the shortcode: `[healthcare_bookmarks]`
- Save and note the page URL for settings

### 3. Add Blocks to Your Site

#### Bookmark Button Block
- Edit any healthcare provider post
- Add the "Healthcare Bookmark Button" block
- Only displays on `healthcare_provider` post type

#### Bookmark Counter Block  
- Edit your header template or navigation area
- Add the "Healthcare Bookmark Counter" block
- Shows bookmark count and links to My Bookmarks page

## Usage

### For Visitors (Not Logged In)
1. Click bookmark button on healthcare provider
2. Enter email address
3. Check email for magic link
4. Click magic link to auto-login and bookmark

### For Logged In Users
1. Click bookmark button to instantly bookmark
2. View bookmarks via header counter or My Bookmarks page
3. Remove bookmarks from My Bookmarks page

## File Structure

```
healthcare-bookmarks/
├── healthcare-bookmarks.php    # Main plugin file
├── README.md                   # This file
├── assets/
│   ├── bookmarks.js           # Frontend JavaScript
│   └── bookmarks.css          # Plugin styles
└── blocks/
    ├── bookmark-button.js     # Bookmark button block
    └── bookmark-counter.js    # Counter block
```

## Shortcodes

### `[healthcare_bookmarks]`
Displays a responsive grid of user's bookmarked healthcare providers with thumbnails, excerpts, and action buttons.

**Usage:**
```
[healthcare_bookmarks]
```

## Hooks & Filters

### Actions
- `hb_user_bookmarked` - Fired when user bookmarks a post
- `hb_user_removed_bookmark` - Fired when user removes bookmark
- `hb_magic_link_used` - Fired when magic link is used

### Filters
- `hb_email_subject` - Filter magic link email subject
- `hb_email_message` - Filter magic link email content
- `hb_bookmark_card_html` - Filter bookmark card HTML

## Requirements

- WordPress 5.0+
- PHP 7.4+
- Custom post type: `healthcare_provider`
- Email functionality (wp_mail)

## Configuration Options

### Email Templates
Customize in **Settings → Healthcare Bookmarks**:
- **Subject Line**: Use `[POST_TITLE]` placeholder
- **Email Message**: Use `[POST_TITLE]` and `[MAGIC_LINK]` placeholders

### Magic Link Security
- Links expire after 15 minutes
- One-time use tokens
- Secure token generation

## Database Tables

The plugin creates two custom tables:

### `wp_healthcare_bookmarks`
- `id` - Unique bookmark ID
- `user_id` - WordPress user ID  
- `post_id` - Healthcare provider post ID
- `created_at` - Bookmark timestamp

### `wp_healthcare_emails`
- `id` - Unique email ID
- `email` - Email address
- `created_at` - Collection timestamp

## Admin Features

### Settings Page
- **Location**: Settings → Healthcare Bookmarks
- **Features**: Email template customization, statistics, page URL setting

### Statistics Dashboard
- Total emails collected
- Total bookmarks created
- Export functionality (future enhancement)

## Styling & Customization

### CSS Classes
- `.hb-bookmark-btn` - Bookmark button
- `.hb-counter` - Header counter
- `.hb-bookmarks-grid` - Bookmarks grid container
- `.hb-bookmark-card` - Individual bookmark card

### Responsive Design
- Mobile-first approach
- Breakpoints at 768px
- Touch-friendly interface

## Security Features

- Nonce verification for all AJAX requests
- Email sanitization and validation
- SQL injection prevention
- XSS protection
- Rate limiting on magic link generation

## Troubleshooting

### Magic Links Not Working
1. Check email delivery (wp_mail functionality)
2. Verify 15-minute expiration window
3. Check spam folder
4. Ensure WordPress cron is working

### Blocks Not Appearing
1. Clear cache/refresh block editor
2. Check file permissions
3. Verify JavaScript console for errors

### Bookmarks Not Saving
1. Check user permissions
2. Verify database tables were created
3. Check for plugin conflicts

### Email Collection Issues
1. Verify wp_mail() functionality
2. Check SMTP configuration
3. Test with simple email first

## Support

For support, please check:
1. WordPress error logs
2. Browser console for JavaScript errors
3. Plugin settings configuration

## Changelog

### Version 1.0.0
- Initial release
- Magic link authentication
- Email capture functionality
- Gutenberg blocks
- Admin dashboard
- Mobile responsive design

## License

This plugin is licensed under GPL v2 or later.

## Contributing

1. Fork the repository
2. Create a feature branch
3. Make your changes
4. Test thoroughly
5. Submit a pull request

---

**Note**: This plugin is designed specifically for healthcare provider post types. Ensure your WordPress site has the `healthcare_provider` custom post type configured before use.