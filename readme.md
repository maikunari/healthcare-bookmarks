# Healthcare Provider Bookmarks Plugin

A WordPress plugin that enables users to bookmark healthcare providers with magic link authentication and advanced email marketing capabilities through ConvertKit integration.

## Features

### Core Functionality
- 🔗 **Magic Link Authentication**: Passwordless login system via email
- 📑 **Bookmark Management**: Users can save their favorite healthcare providers
- 📧 **Email Capture**: Collect emails with marketing consent
- 📍 **Location Tracking**: Automatically track user interests by city
- 🏥 **Specialty Tracking**: Track medical specialties of interest
- 🚀 **ConvertKit Integration**: Seamlessly sync subscribers with advanced tagging

### Marketing Capabilities
- **Geographic Segmentation**: Track which cities users are interested in
- **Specialty Segmentation**: Track medical specialties (Cardiology, Pediatrics, etc.)
- **Automatic Tagging**: Apply tags in ConvertKit based on user behavior
- **Bulk Sync**: Sync existing email list to ConvertKit with all tags
- **Interest Accumulation**: Build comprehensive user profiles over time

## Installation

1. Upload the plugin folder to `/wp-content/plugins/`
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Configure settings at Settings → Healthcare Bookmarks

## Configuration

### Basic Setup

1. **Create a Bookmarks Page**:
   - Create a new page in WordPress
   - Add the shortcode `[healthcare_bookmarks]`
   - Save the page URL in plugin settings

2. **Add Bookmark Buttons**:
   - Edit your healthcare provider posts/pages
   - Add the "Healthcare Bookmark Button" Gutenberg block
   - The button will automatically appear on provider pages

3. **Add Bookmark Counter** (Optional):
   - Edit your site header/navigation
   - Add the "Healthcare Bookmark Counter" block
   - Links to your bookmarks page and shows count

### ConvertKit Integration Setup

1. **Get Your ConvertKit Credentials**:
   - Log into your Kit.com account
   - Navigate to Account Settings → Advanced
   - Copy your API Key
   - Go to your desired form and copy the Form ID

2. **Configure in WordPress**:
   - Go to Settings → Healthcare Bookmarks
   - Scroll to "ConvertKit Integration" section
   - Check "Enable ConvertKit"
   - Enter your API Key
   - Enter your Form ID
   - Choose tag formats:
     - **Cities**: "City: San Francisco" or "San Francisco"
     - **Specialties**: "Specialty: Cardiology" or "Cardiology"
   - Save settings

3. **Sync Existing Subscribers** (Optional):
   - Go to Tools → Email Subscribers
   - Click "Sync to ConvertKit"
   - Confirm the action
   - Wait for sync to complete

## Usage

### For Site Visitors

1. **Bookmarking Without Account**:
   - Click bookmark button on any healthcare provider
   - Enter email address
   - Check email for magic link (expires in 15 minutes)
   - Click link to confirm bookmark (creates account automatically)

2. **Accessing Bookmarks**:
   - Visit the bookmarks page
   - Enter email address
   - Click magic link in email
   - View and manage saved providers

### For Administrators

1. **View Email Subscribers**:
   - Navigate to Tools → Email Subscribers
   - View all collected emails with their interests:
     - Cities they're interested in
     - Medical specialties they've bookmarked
   - Export as CSV for backup
   - Sync to ConvertKit as needed

2. **Monitor Statistics**:
   - Check Settings → Healthcare Bookmarks for totals
   - View bookmark count and email subscriber count

## ConvertKit Segmentation Examples

With automatic tagging, you can create powerful segments in ConvertKit:

### Geographic Campaigns
- Users interested in "San Francisco" providers
- Multi-city campaigns (users interested in "SF" OR "LA")
- Regional healthcare announcements

### Specialty-Based Campaigns
- Users interested in "Cardiology"
- Multiple specialty interests (e.g., "Pediatrics" AND "Family Medicine")
- Specialty-specific health tips and news

### Compound Segments
- "Pediatrics" providers in "Los Angeles"
- "Cardiology" OR "Internal Medicine" in "San Diego"
- Emergency services in specific cities

## Requirements

- WordPress 5.0 or higher
- PHP 7.0 or higher
- `healthcare_provider` custom post type
- `location` taxonomy (for cities)
- `specialties` taxonomy (for medical specialties)

## Database Tables

The plugin creates two custom tables:

### wp_healthcare_bookmarks
- `id` - Unique bookmark ID
- `user_id` - WordPress user ID
- `post_id` - Healthcare provider post ID
- `city` - City from location taxonomy
- `specialties` - JSON array of specialties
- `created_at` - Timestamp

### wp_healthcare_emails
- `id` - Unique record ID
- `email` - Email address
- `cities` - JSON array of all cities of interest
- `specialties` - JSON array of all specialties of interest
- `created_at` - Timestamp

## File Structure

```
healthcare-bookmarks/
├── healthcare-bookmarks.php    # Main plugin file
├── README.md                   # This file
├── CLAUDE.md                   # Developer documentation
├── assets/
│   ├── bookmarks.js           # Frontend JavaScript
│   └── bookmarks.css          # Plugin styles
└── blocks/
    ├── bookmark-button.js     # Bookmark button block
    └── bookmark-counter.js    # Counter block
```

## AJAX Endpoints

All endpoints use nonce verification for security:

- `send_magic_link` - Send magic link to user
- `toggle_bookmark` - Add/remove bookmark
- `get_bookmark_count` - Get user's bookmark count
- `send_bookmarks_access_link` - Send access link for bookmarks page
- `export_emails` - Export email list as CSV (admin only)
- `sync_emails_to_convertkit` - Sync all emails to ConvertKit

## Shortcodes

### `[healthcare_bookmarks]`
Displays a responsive grid of user's bookmarked healthcare providers with:
- Provider thumbnails
- Title and excerpt
- View provider link
- Remove bookmark option

## Gutenberg Blocks

### Healthcare Bookmark Button
- **Name**: `healthcare-bookmarks/bookmark-button`
- **Usage**: Add to healthcare provider posts
- **Features**: Dynamic state, AJAX powered

### Healthcare Bookmark Counter
- **Name**: `healthcare-bookmarks/bookmark-counter`
- **Usage**: Add to header/navigation
- **Features**: Live count updates, links to bookmarks page

## Security Features

- **Rate Limiting**: 2-minute cooldown between magic link requests
- **Nonce Verification**: All AJAX requests verified
- **Input Sanitization**: All user inputs sanitized
- **SQL Injection Prevention**: Prepared statements used
- **One-Time Tokens**: Magic links expire after single use
- **Time Expiration**: Magic links expire after 15 minutes

## Troubleshooting

### Emails Not Sending
- Verify WordPress email configuration
- Check spam folder
- Consider using SMTP plugin
- Test with Tools → Site Health

### ConvertKit Sync Issues
- Verify API key is correct
- Check Form ID exists
- Ensure ConvertKit account is active
- Check WordPress error logs
- Verify tag limits haven't been exceeded

### Database Update Required
After updating the plugin:
1. Deactivate the plugin
2. Reactivate the plugin
3. This will update the database schema

### Cities/Specialties Not Tracking
- Verify taxonomies exist and are assigned to posts
- Check that taxonomy slugs are exactly 'location' and 'specialties'
- Ensure terms are assigned to healthcare providers

## Hooks and Filters

### Actions
- `hb_bookmark_added` - Fired when a bookmark is added
- `hb_bookmark_removed` - Fired when a bookmark is removed
- `hb_email_captured` - Fired when an email is captured
- `hb_convertkit_synced` - Fired after ConvertKit sync

### Filters
- `hb_magic_link_expiry` - Modify magic link expiration (default: 15 minutes)
- `hb_rate_limit_duration` - Modify rate limiting (default: 2 minutes)
- `hb_convertkit_tags` - Filter tags before sending to ConvertKit

## Support

For issues or questions:
1. Check WordPress error logs
2. Review plugin settings
3. Verify taxonomy configuration
4. Test email functionality

## Changelog

### Version 1.1.0
- Added ConvertKit integration
- Added city tracking from location taxonomy
- Added specialty tracking from specialties taxonomy
- Enhanced email subscriber management
- Added bulk sync to ConvertKit

### Version 1.0.0
- Initial release
- Magic link authentication
- Bookmark management
- Email capture
- Admin dashboard

## License

This plugin is proprietary software. All rights reserved.

---

**Note**: This plugin requires the `healthcare_provider` custom post type with `location` and `specialties` taxonomies properly configured.