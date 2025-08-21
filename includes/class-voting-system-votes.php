<?php

/**
 * The voting system core functionality.
 *
 * @link       https://patelmohip.in
 * @since      1.0.0
 *
 * @package    Voting_System
 * @subpackage Voting_System/includes
 */

class Voting_System_Votes {

	/**
	 * Cache group for vote data.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $cache_group    Cache group identifier.
	 */
	private $cache_group = 'voting_system';

	/**
	 * Cache expiration time in seconds.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      int    $cache_expiration    Cache expiration time.
	 */
	private $cache_expiration = 3600; // 1 hour

	/**
	 * Initialize the voting system.
	 *
	 * @since    1.0.0
	 */
	public function __construct() {
		add_action( 'init', [ $this, 'init_hooks' ] );
	}

	/**
	 * Initialize hooks.
	 *
	 * @since    1.0.0
	 */
	public function init_hooks() {
		add_action( 'rest_api_init', [ $this, 'register_rest_routes' ] );		
		add_filter( 'rest_prepare_post', [ $this, 'add_vote_data_to_rest_response' ], 10, 2 );		
		add_action( 'wp_insert_post', [ $this, 'initialize_post_votes' ], 10, 2 );		
		add_filter( 'rest_post_collection_params', [ $this, 'add_cache_headers' ], 10, 2 );
	}

	/**
	 * Register REST API routes.
	 *
	 * @since    1.0.0
	 */
	public function register_rest_routes() {
		register_rest_route(
			'voting-system/v1',
			'/vote',
			[
				'methods'             => 'POST',
				'callback'            => [ $this, 'handle_vote' ],
				'permission_callback' => [ $this, 'verify_vote_permissions' ],
				'args'                => [
					'post_id' => [
						'required'          => true,
						'type'              => 'integer',
						'sanitize_callback' => 'absint',
						'validate_callback' => [ $this, 'validate_post_id' ],
					],
					'vote_type' => [
						'required'          => true,
						'type'              => 'string',
						'enum'              => [ 'upvote', 'downvote' ],
						'sanitize_callback' => 'sanitize_text_field',
					],
				],
			]
		);

		register_rest_route(
			'voting-system/v1',
			'/votes/(?P<id>\d+)',
			[
				'methods'             => 'GET',
				'callback'            => [ $this, 'get_post_votes' ],
				'permission_callback' => '__return_true',
				'args'                => [
					'id' => [
						'required'          => true,
						'type'              => 'integer',
						'sanitize_callback' => 'absint',
						'validate_callback' => [ $this, 'validate_post_id' ],
					],
				],
			]
		);
	}

	/**
	 * Handle vote submission.
	 *
	 * @since    1.0.0
	 * @param    WP_REST_Request $request The REST request.
	 * @return   WP_REST_Response|WP_Error
	 */
	public function handle_vote( $request ) {
		$post_id   = $request->get_param( 'post_id' );
		$vote_type = $request->get_param( 'vote_type' );

		// Update vote count
		$result = $this->update_vote_count( $post_id, $vote_type );

		if ( is_wp_error( $result ) ) {
			return $result;
		}

		// Invalidate cache
		$this->invalidate_post_cache( $post_id );

		return new WP_REST_Response(
			[
				'success'   => true,
				'post_id'   => $post_id,
				'vote_type' => $vote_type,
				'votes'     => $this->get_vote_counts( $post_id ),
			],
			200
		);
	}

	/**
	 * Get vote counts for a post.
	 *
	 * @since    1.0.0
	 * @param    WP_REST_Request $request The REST request.
	 * @return   WP_REST_Response|WP_Error
	 */
	public function get_post_votes( $request ) {
		$post_id = $request->get_param( 'id' );
		$votes   = $this->get_vote_counts( $post_id );

		if ( false === $votes ) {
			return new WP_Error(
				'votes_not_found',
				__( 'Vote data not found for this post.', 'voting-system' ),
				[ 'status' => 404 ]
			);
		}

		return new WP_REST_Response( $votes, 200 );
	}

	/**
	 * Add vote data to REST API post response.
	 *
	 * @since    1.0.0
	 * @param    WP_REST_Response $response The response object.
	 * @param    WP_Post          $post     The post object.
	 * @return   WP_REST_Response
	 */
	public function add_vote_data_to_rest_response( $response, $post ) {
		if ( 'post' !== $post->post_type ) {
			return $response;
		}

		$votes = $this->get_vote_counts( $post->ID );
		
		if ( $votes ) {
			$response->data['votes'] = $votes;
		}

		return $response;
	}

	/**
	 * Initialize vote counts for new posts.
	 *
	 * @since    1.0.0
	 * @param    int     $post_id Post ID.
	 * @param    WP_Post $post    Post object.
	 */
	public function initialize_post_votes( $post_id, $post ) {
		if ( 'post' !== $post->post_type || 'publish' !== $post->post_status ) {
			return;
		}

		// Check if votes already exist
		if ( metadata_exists( 'post', $post_id, '_upvotes' ) ) {
			return;
		}

		// Initialize with zero votes
		add_post_meta( $post_id, '_upvotes', 0, true );
		add_post_meta( $post_id, '_downvotes', 0, true );
	}

	/**
	 * Get vote counts for a post with caching.
	 *
	 * @since    1.0.0
	 * @param    int $post_id Post ID.
	 * @return   array|false Vote counts or false if not found.
	 */
	public function get_vote_counts( $post_id ) {
		$cache_key = "post_votes_{$post_id}";
		$votes     = wp_cache_get( $cache_key, $this->cache_group );

		if ( false === $votes ) {
			$upvotes   = (int) get_post_meta( $post_id, '_upvotes', true );
			$downvotes = (int) get_post_meta( $post_id, '_downvotes', true );

			if ( metadata_exists( 'post', $post_id, '_upvotes' ) ) {
				$votes = [
					'upvotes'   => $upvotes,
					'downvotes' => $downvotes,
					'total'     => $upvotes + $downvotes,
					'score'     => $upvotes - $downvotes,
				];

				wp_cache_set( $cache_key, $votes, $this->cache_group, $this->cache_expiration );
			} else {
				$votes = false;
			}
		}

		return $votes;
	}

	/**
	 * Update vote count for a post.
	 *
	 * @since    1.0.0
	 * @param    int    $post_id   Post ID.
	 * @param    string $vote_type Vote type (upvote or downvote).
	 * @return   bool|WP_Error True on success, WP_Error on failure.
	 */
	private function update_vote_count( $post_id, $vote_type ) {
		$meta_key = 'upvote' === $vote_type ? '_upvotes' : '_downvotes';
		$current_votes = (int) get_post_meta( $post_id, $meta_key, true );
		$new_votes = $current_votes + 1;

		if ( update_post_meta( $post_id, $meta_key, $new_votes ) ) {
			return true;
		} else {
			return new WP_Error(
				'vote_update_failed',
				__( 'Failed to update vote count.', 'voting-system' ),
				[ 'status' => 500 ]
			);
		}
	}

	/**
	 * Invalidate cache for a post.
	 *
	 * @since    1.0.0
	 * @param    int $post_id Post ID.
	 */
	private function invalidate_post_cache( $post_id ) {
		$cache_key = "post_votes_{$post_id}";
		wp_cache_delete( $cache_key, $this->cache_group );

		// Also invalidate posts collection cache
		wp_cache_delete( 'posts_collection', $this->cache_group );
	}

	/**
	 * Verify vote permissions.
	 *
	 * @since    1.0.0
	 * @param    WP_REST_Request $request The REST request.
	 * @return   bool True if user can vote, false otherwise.
	 */
	public function verify_vote_permissions( $request ) {
		// For now, allow all users to vote
		// You can add more sophisticated permission checks here
		return true;
	}

	/**
	 * Validate post ID.
	 *
	 * @since    1.0.0
	 * @param    int             $value   Post ID.
	 * @param    WP_REST_Request $request The REST request.
	 * @param    string          $param   Parameter name.
	 * @return   bool True if valid, false otherwise.
	 */
	public function validate_post_id( $value, $request, $param ) {
		$post = get_post( $value );
		return $post && 'post' === $post->post_type && 'publish' === $post->post_status;
	}

	/**
	 * Add cache headers to posts collection.
	 *
	 * @since    1.0.0
	 * @param    array           $query_params Query parameters.
	 * @param    WP_REST_Request $request      The REST request.
	 * @return   array Modified query parameters.
	 */
	public function add_cache_headers( $query_params, $request ) {
		if ( ! headers_sent() ) {
			header( 'Cache-Control: public, max-age=' . $this->cache_expiration );
		}
		return $query_params;
	}

	/**
	 * Get all posts with vote counts for admin display.
	 *
	 * @since    1.0.0
	 * @param    string $orderby Order by column.
	 * @param    string $order   Order direction.
	 * @return   array Posts with vote data.
	 */
	public function get_posts_with_votes( $orderby = 'post_title', $order = 'ASC' ) {
		$args = [
			'post_type'      => 'post',
			'post_status'    => 'publish',
			'posts_per_page' => -1,
		];

		$query = new WP_Query( $args );
		$posts_with_votes = [];

		if ( $query->have_posts() ) {
			foreach ( $query->posts as $post ) {
				$votes = $this->get_vote_counts( $post->ID );
				$posts_with_votes[] = (object) [
					'ID'          => $post->ID,
					'post_title'  => $post->post_title,
					'upvotes'     => $votes['upvotes'] ?? 0,
					'downvotes'   => $votes['downvotes'] ?? 0,
					'total_votes' => $votes['total'] ?? 0,
					'score'       => $votes['score'] ?? 0,
				];
			}
		}

		// Sort the results
		$allowed_orderby = [ 'post_title', 'upvotes', 'downvotes', 'total_votes', 'score' ];
		if ( ! in_array( $orderby, $allowed_orderby, true ) ) {
			$orderby = 'post_title';
		}

		$order_dir = ( 'DESC' === strtoupper( $order ) ) ? -1 : 1;

		usort(
			$posts_with_votes,
			function ( $a, $b ) use ( $orderby, $order_dir ) {
				if ( 'post_title' === $orderby ) {
					return strnatcasecmp( $a->post_title, $b->post_title ) * $order_dir;
				}
				return ( $a->{$orderby} - $b->{$orderby} ) * $order_dir;
			}
		);

		return $posts_with_votes;
	}
}
