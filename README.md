# WordPress Voting System Plugin

A comprehensive WordPress plugin that adds a voting system to posts with REST API endpoints, caching, and an admin dashboard for statistics.

## Features

### Core Functionality
- **Post Voting System**: Associate upvote and downvote counts with WordPress posts
- **Database Persistence**: Efficient storage of vote data with automatic initialization
- **REST API Integration**: Modify standard post endpoints to include vote data
- **Custom API Endpoints**: Dedicated endpoints for voting and retrieving vote data
- **Object Caching**: WordPress native caching with Redis support (bonus)
- **Admin Dashboard**: Comprehensive statistics and management interface

### Technical Features
- **Security**: Proper nonce verification and user permission checks
- **Performance**: Optimized database queries with caching layers
- **Standards Compliance**: Follows WordPress and PSR coding standards
- **Responsive Design**: Mobile-friendly admin interface and public voting buttons
- **Accessibility**: ARIA labels and keyboard navigation support

## Installation

1. Copy the `voting-system` folder to your `wp-content/mu-plugins/` directory
2. The plugin will automatically activate as a must-use plugin
3. Navigate to WordPress admin → **Post Votes** to view the dashboard

## Database Schema

The plugin creates a `wp_post_votes` table with the following structure:

```sql
CREATE TABLE wp_post_votes (
    id bigint(20) NOT NULL AUTO_INCREMENT,
    post_id bigint(20) NOT NULL,
    upvotes bigint(20) DEFAULT 0,
    downvotes bigint(20) DEFAULT 0,
    created_at datetime DEFAULT CURRENT_TIMESTAMP,
    updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY post_id (post_id),
    KEY post_id_idx (post_id)
);
```

## REST API Endpoints

### 1. Get Posts with Vote Data
```http
GET /wp-json/wp/v2/posts
```

**Response**: Standard posts endpoint with additional `votes` field:
```json
{
  "id": 123,
  "title": "Sample Post",
  "content": "Post content...",
  "votes": {
    "upvotes": 25,
    "downvotes": 3,
    "total": 28,
    "score": 22
  }
}
```

### 2. Submit a Vote
```http
POST /wp-json/voting-system/v1/vote
```

**Headers**:
```
Content-Type: application/x-www-form-urlencoded
X-WP-Nonce: {wp_rest_nonce}
```

**Parameters**:
- `post_id` (integer, required): ID of the post to vote on
- `vote_type` (string, required): Either "upvote" or "downvote"

**Response**:
```json
{
  "success": true,
  "post_id": 123,
  "vote_type": "upvote",
  "votes": {
    "upvotes": 26,
    "downvotes": 3,
    "total": 29,
    "score": 23
  }
}
```

### 3. Get Vote Counts for a Post
```http
GET /wp-json/voting-system/v1/votes/{post_id}
```

**Response**:
```json
{
  "upvotes": 26,
  "downvotes": 3,
  "total": 29,
  "score": 23
}
```

## Admin Dashboard

Access the admin dashboard at **WordPress Admin → Post Votes**.

### Features:
- **Summary Statistics**: Total posts, upvotes, downvotes, and total votes
- **Sortable Table**: Sort by title, upvotes, downvotes, total votes, or score
- **Search Functionality**: Filter posts by title
- **Auto-refresh**: Optional 30-second auto-refresh
- **Export to CSV**: Download vote statistics
- **Reset Votes**: Reset vote counts for individual posts
- **Responsive Design**: Mobile-friendly interface

### Dashboard Columns:
- **Post Title**: Link to edit post
- **Upvotes**: Number of upvotes (green)
- **Downvotes**: Number of downvotes (red)
- **Total Votes**: Sum of upvotes and downvotes (blue)

## Frontend Integration

The plugin automatically adds voting buttons to single post pages.

### Voting Buttons
- **Visual Design**: Thumb up/down icons with vote counts
- **Interactive States**: Hover effects and loading animations
- **Vote Tracking**: Prevents duplicate votes using localStorage
- **Accessibility**: ARIA labels and keyboard navigation
- **Mobile Responsive**: Adapts to mobile screens

### Customization
You can customize the appearance by modifying:
- `public/css/voting-system-public.css` - Styling
- `public/js/voting-system-public.js` - Behavior

## Caching System

### WordPress Object Cache
- **Automatic Caching**: Vote data cached for 1 hour
- **Cache Invalidation**: Automatic cache clearing on vote updates
- **Performance**: Reduces database queries

### Redis Support (Bonus)
For enhanced performance, configure Redis:

```php
// wp-config.php
define('REDIS_HOST', '127.0.0.1');
define('REDIS_PORT', 6379);
define('REDIS_PASSWORD', 'your_password'); // Optional
define('REDIS_DATABASE', 0); // Optional
define('REDIS_TIMEOUT', 1); // Optional
```

## Security Features

- **Nonce Verification**: All AJAX requests verified
- **User Permissions**: Admin functions require proper capabilities
- **Data Sanitization**: All inputs sanitized and validated
- **SQL Injection Prevention**: Prepared statements used
- **XSS Protection**: Output escaped properly

## File Structure

```
voting-system/
├── voting-system.php              # Main plugin file
├── README.md                      # This documentation
├── admin/                         # Admin functionality
│   ├── class-voting-system-admin.php
│   ├── css/voting-system-admin.css
│   └── js/voting-system-admin.js
├── public/                        # Public functionality
│   ├── class-voting-system-public.php
│   ├── css/voting-system-public.css
│   └── js/voting-system-public.js
└── includes/                      # Core functionality
    ├── class-voting-system.php
    ├── class-voting-system-activator.php
    ├── class-voting-system-deactivator.php
    ├── class-voting-system-i18n.php
    ├── class-voting-system-loader.php
    ├── class-voting-system-votes.php
    ├── class-voting-system-admin-dashboard.php
    └── class-voting-system-redis-cache.php
```

## Development

### Coding Standards
- Follows WordPress Coding Standards
- PSR-4 autoloading compatible
- Extensive inline documentation
- Proper error handling

### Testing
Test the plugin functionality:

1. **Create a test post** and verify voting buttons appear
2. **Vote on posts** via frontend buttons
3. **Check admin dashboard** for updated statistics
4. **Test REST API** endpoints with tools like Postman
5. **Verify caching** by checking database query counts

### Extending the Plugin

#### Add Custom Post Type Support
```php
// In your theme's functions.php
add_filter('voting_system_supported_post_types', function($post_types) {
    $post_types[] = 'your_custom_post_type';
    return $post_types;
});
```

#### Custom Vote Validation
```php
// Add custom validation logic
add_filter('voting_system_can_vote', function($can_vote, $post_id, $user_id) {
    // Your custom logic here
    return $can_vote;
}, 10, 3);
```

## Troubleshooting

### Common Issues

1. **Voting buttons not appearing**
   - Check if it's a single post page
   - Verify post type is 'post'
   - Check theme compatibility

2. **Database errors**
   - Verify database table was created
   - Check WordPress database permissions
   - Review error logs

3. **Caching issues**
   - Clear object cache
   - Verify Redis connection (if using)
   - Check cache expiration settings

4. **JavaScript errors**
   - Check browser console for errors
   - Verify jQuery is loaded
   - Check nonce generation

### Debug Mode
Enable WordPress debug mode to see detailed error information:

```php
// wp-config.php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
define('WP_DEBUG_DISPLAY', false);
```

## Performance Considerations

- **Database Optimization**: Indexed post_id column for fast lookups
- **Caching Strategy**: Aggressive caching with smart invalidation
- **Lazy Loading**: Vote data loaded only when needed
- **Optimized Queries**: Minimal database impact
- **CDN Ready**: Static assets can be cached by CDN

## License

This plugin is licensed under the GPL v2 or later.

## Support

For support and feature requests, please refer to the plugin documentation or contact the development team.

## Changelog

### Version 1.0.0
- Initial release
- Core voting functionality
- REST API endpoints
- Admin dashboard
- Caching system
- Redis support
- Security implementation
- Performance optimizations
