# WordPress Voting System Plugin

A comprehensive WordPress plugin that adds a voting system to posts with REST API endpoints, caching, and an admin dashboard for statistics.

## Features

### Core Functionality
- **Post Voting System**: Associate upvote and downvote counts with WordPress posts
- **REST API Integration**: Modify standard post endpoints to include vote data
- **Custom API Endpoints**: Dedicated endpoints for voting and retrieving vote data
- **Object Caching**: WordPress native caching.
- **Admin Dashboard**: Comprehensive statistics and management interface

## Installation

1. Copy the `voting-system` folder to your `wp-content/mu-plugins/` directory
2. Click on the Activate.
3. Navigate to WordPress admin → **Post Votes** to view the dashboard



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
- **Responsive Design**: Mobile-friendly interface

### Dashboard Columns:
- **Post Title**: Link to edit post
- **Upvotes**: Number of upvotes (green)
- **Downvotes**: Number of downvotes (red)
- **Total Votes**: Sum of upvotes and downvotes (blue)

## Caching System

### WordPress Object Cache
- **Automatic Caching**: Vote data cached for 1 hour
- **Cache Invalidation**: Automatic cache clearing on vote updates
- **Performance**: Reduces database queries

### Redis Support (optional)
For enhanced performance, configure Redis:

```php
// wp-config.php
define('REDIS_HOST', '127.0.0.1');
define('REDIS_PORT', 6379);
define('REDIS_PASSWORD', 'your_password'); // Optional
define('REDIS_DATABASE', 0); // Optional
define('REDIS_TIMEOUT', 1); // Optional
```


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
