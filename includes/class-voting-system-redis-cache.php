<?php

/**
 * Redis cache handler for enhanced performance.
 *
 * @link       https://patelmohip.in
 * @since      1.0.0
 *
 * @package    Voting_System
 * @subpackage Voting_System/includes
 */

class Voting_System_Redis_Cache {

	/**
	 * Redis instance.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      Redis    $redis    Redis instance.
	 */
	private $redis;

	/**
	 * Cache key prefix.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $prefix    Cache key prefix.
	 */
	private $prefix = 'voting_system:';

	/**
	 * Default expiration time.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      int    $default_expiration    Default cache expiration.
	 */
	private $default_expiration = 3600;

	/**
	 * Initialize the Redis cache.
	 *
	 * @since    1.0.0
	 */
	public function __construct() {
		$this->init_redis();
	}

	/**
	 * Initialize Redis connection.
	 *
	 * @since    1.0.0
	 */
	private function init_redis() {
		if ( ! class_exists( 'Redis' ) ) {
			return;
		}

		try {
			$this->redis = new Redis();
			
			// Configure Redis connection
			$host = defined( 'REDIS_HOST' ) ? REDIS_HOST : '127.0.0.1';
			$port = defined( 'REDIS_PORT' ) ? REDIS_PORT : 6379;
			$timeout = defined( 'REDIS_TIMEOUT' ) ? REDIS_TIMEOUT : 1;
			
			$this->redis->connect( $host, $port, $timeout );
			
			// Authenticate if password is set
			if ( defined( 'REDIS_PASSWORD' ) && REDIS_PASSWORD ) {
				$this->redis->auth( REDIS_PASSWORD );
			}
			
			// Select database
			if ( defined( 'REDIS_DATABASE' ) ) {
				$this->redis->select( REDIS_DATABASE );
			}
			
		} catch ( Exception $e ) {
			error_log( 'Voting System Redis Connection Error: ' . $e->getMessage() );
			$this->redis = null;
		}
	}

	/**
	 * Check if Redis is available.
	 *
	 * @since    1.0.0
	 * @return   bool True if Redis is available, false otherwise.
	 */
	public function is_available() {
		return $this->redis !== null && $this->redis->ping() === '+PONG';
	}

	/**
	 * Get cached data.
	 *
	 * @since    1.0.0
	 * @param    string $key Cache key.
	 * @return   mixed Cached data or false if not found.
	 */
	public function get( $key ) {
		if ( ! $this->is_available() ) {
			return false;
		}

		try {
			$full_key = $this->prefix . $key;
			$data = $this->redis->get( $full_key );
			
			if ( false === $data ) {
				return false;
			}

			return maybe_unserialize( $data );
		} catch ( Exception $e ) {
			error_log( 'Voting System Redis Get Error: ' . $e->getMessage() );
			return false;
		}
	}

	/**
	 * Set cached data.
	 *
	 * @since    1.0.0
	 * @param    string $key        Cache key.
	 * @param    mixed  $data       Data to cache.
	 * @param    int    $expiration Expiration time in seconds.
	 * @return   bool True on success, false on failure.
	 */
	public function set( $key, $data, $expiration = null ) {
		if ( ! $this->is_available() ) {
			return false;
		}

		if ( null === $expiration ) {
			$expiration = $this->default_expiration;
		}

		try {
			$full_key = $this->prefix . $key;
			$serialized_data = maybe_serialize( $data );
			
			return $this->redis->setex( $full_key, $expiration, $serialized_data );
		} catch ( Exception $e ) {
			error_log( 'Voting System Redis Set Error: ' . $e->getMessage() );
			return false;
		}
	}

	/**
	 * Delete cached data.
	 *
	 * @since    1.0.0
	 * @param    string $key Cache key.
	 * @return   bool True on success, false on failure.
	 */
	public function delete( $key ) {
		if ( ! $this->is_available() ) {
			return false;
		}

		try {
			$full_key = $this->prefix . $key;
			return $this->redis->del( $full_key ) > 0;
		} catch ( Exception $e ) {
			error_log( 'Voting System Redis Delete Error: ' . $e->getMessage() );
			return false;
		}
	}

	/**
	 * Flush all cached data.
	 *
	 * @since    1.0.0
	 * @return   bool True on success, false on failure.
	 */
	public function flush() {
		if ( ! $this->is_available() ) {
			return false;
		}

		try {
			$pattern = $this->prefix . '*';
			$keys = $this->redis->keys( $pattern );
			
			if ( empty( $keys ) ) {
				return true;
			}

			return $this->redis->del( $keys ) > 0;
		} catch ( Exception $e ) {
			error_log( 'Voting System Redis Flush Error: ' . $e->getMessage() );
			return false;
		}
	}

	/**
	 * Increment a cached value.
	 *
	 * @since    1.0.0
	 * @param    string $key Cache key.
	 * @param    int    $amount Amount to increment.
	 * @return   int|false New value or false on failure.
	 */
	public function increment( $key, $amount = 1 ) {
		if ( ! $this->is_available() ) {
			return false;
		}

		try {
			$full_key = $this->prefix . $key;
			return $this->redis->incrBy( $full_key, $amount );
		} catch ( Exception $e ) {
			error_log( 'Voting System Redis Increment Error: ' . $e->getMessage() );
			return false;
		}
	}

	/**
	 * Get cache statistics.
	 *
	 * @since    1.0.0
	 * @return   array Cache statistics.
	 */
	public function get_stats() {
		if ( ! $this->is_available() ) {
			return [];
		}

		try {
			$info = $this->redis->info();
			$pattern = $this->prefix . '*';
			$keys = $this->redis->keys( $pattern );
			
			return [
				'redis_version' => $info['redis_version'] ?? 'Unknown',
				'connected_clients' => $info['connected_clients'] ?? 0,
				'used_memory_human' => $info['used_memory_human'] ?? '0B',
				'total_keys' => count( $keys ),
				'voting_system_keys' => count( $keys ),
			];
		} catch ( Exception $e ) {
			error_log( 'Voting System Redis Stats Error: ' . $e->getMessage() );
			return [];
		}
	}

	/**
	 * Cache vote data with Redis.
	 *
	 * @since    1.0.0
	 * @param    int   $post_id Post ID.
	 * @param    array $votes   Vote data.
	 * @return   bool True on success, false on failure.
	 */
	public function cache_votes( $post_id, $votes ) {
		$key = "post_votes_{$post_id}";
		return $this->set( $key, $votes );
	}

	/**
	 * Get cached vote data.
	 *
	 * @since    1.0.0
	 * @param    int $post_id Post ID.
	 * @return   array|false Vote data or false if not found.
	 */
	public function get_cached_votes( $post_id ) {
		$key = "post_votes_{$post_id}";
		return $this->get( $key );
	}

	/**
	 * Invalidate vote cache for a post.
	 *
	 * @since    1.0.0
	 * @param    int $post_id Post ID.
	 * @return   bool True on success, false on failure.
	 */
	public function invalidate_post_votes( $post_id ) {
		$key = "post_votes_{$post_id}";
		return $this->delete( $key );
	}

	/**
	 * Cache posts collection.
	 *
	 * @since    1.0.0
	 * @param    string $cache_key Cache key.
	 * @param    array  $posts     Posts data.
	 * @return   bool True on success, false on failure.
	 */
	public function cache_posts_collection( $cache_key, $posts ) {
		return $this->set( $cache_key, $posts, 1800 ); // 30 minutes
	}

	/**
	 * Get cached posts collection.
	 *
	 * @since    1.0.0
	 * @param    string $cache_key Cache key.
	 * @return   array|false Posts data or false if not found.
	 */
	public function get_cached_posts_collection( $cache_key ) {
		return $this->get( $cache_key );
	}
}
