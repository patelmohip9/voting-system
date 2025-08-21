<?php

/**
 * Fired during plugin activation
 *
 * @link       https://patelmohip.in
 * @since      1.0.0
 *
 * @package    Voting_System
 * @subpackage Voting_System/includes
 */

/**
 * Fired during plugin activation.
 *
 * This class defines all code necessary to run during the plugin's activation.
 *
 * @since      1.0.0
 * @package    Voting_System
 * @subpackage Voting_System/includes
 * @author     Patel Mohip <patelmohip9@gmail.com>
 */
class Voting_System_Activator {

	/**
	 * Initialize vote counts for existing posts.
	 *
	 * @since    1.0.0
	 */
	public static function activate() {
		// Initialize vote counts for existing posts
		self::initialize_existing_posts();

		// Set plugin version
		add_option( 'voting_system_version', '1.0.0' );
	}

	/**
	 * Initialize vote counts for existing posts.
	 *
	 * @since    1.0.0
	 */
	private static function initialize_existing_posts() {
		$posts = get_posts(
			array(
				'post_type'      => 'post',
				'post_status'    => 'publish',
				'posts_per_page' => -1,
				'fields'         => 'ids',
			)
		);

		foreach ( $posts as $post_id ) {
			add_post_meta( $post_id, '_upvotes', 0, true );
			add_post_meta( $post_id, '_downvotes', 0, true );
		}
	}

}
