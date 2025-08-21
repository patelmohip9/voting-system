<?php

/**
 * The admin-specific functionality of the plugin.
 *
 * @link       https://patelmohip.in
 * @since      1.0.0
 *
 * @package    Voting_System
 * @subpackage Voting_System/admin
 */

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    Voting_System
 * @subpackage Voting_System/admin
 * @author     Patel Mohip <patelmohip9@gmail.com>
 */
class Voting_System_Admin {

	/**
	 * The ID of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $plugin_name    The ID of this plugin.
	 */
	private $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $version    The current version of this plugin.
	 */
	private $version;

	/**
	 * The admin dashboard instance.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      Voting_System_Admin_Dashboard    $dashboard    The admin dashboard.
	 */
	private $dashboard;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @param      string    $plugin_name       The name of this plugin.
	 * @param      string    $version    The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {

		$this->plugin_name = $plugin_name;
		$this->version     = $version;
		
		// Initialize admin dashboard
		$this->dashboard = new Voting_System_Admin_Dashboard();
		
		// Add AJAX handlers
		add_action( 'wp_ajax_reset_post_votes', [ $this, 'ajax_reset_post_votes' ] );
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_scripts' ] );
	}

	/**
	 * Register the stylesheets for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_styles() {
	}

	/**
	 * Register the JavaScript for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts() {
		wp_enqueue_style( 'voting_system_admin_style', plugin_dir_url( __FILE__ ) . 'css/voting-system-admin.css', [], $this->version, 'all' );
		wp_enqueue_script( 'voting_system_admin_script', plugin_dir_url( __FILE__ ) . 'js/voting-system-admin.js', [ 'jquery' ], $this->version, false );

		// Localize script for AJAX
		wp_localize_script(
			$this->plugin_name,
			'voting_system_admin',
			[
				'ajax_url' => admin_url( 'admin-ajax.php' ),
				'nonce'    => wp_create_nonce( 'voting_system_admin_nonce' ),
			]
		);
	}

	/**
	 * AJAX handler to reset post votes.
	 *
	 * @since    1.0.0
	 */
	public function ajax_reset_post_votes() {
		// Verify nonce
		if ( ! wp_verify_nonce( $_POST['nonce'], 'voting_system_admin_nonce' ) ) {
			wp_die( 'Security check failed' );
		}

		// Check user capabilities
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( 'Insufficient permissions' );
		}

		$post_id = isset( $_POST['post_id'] ) ? absint( $_POST['post_id'] ) : 0;

		if ( ! $post_id ) {
			wp_send_json_error( 'Invalid post ID' );
		}

		// Reset votes in database
		global $wpdb;
		$table_name = $wpdb->prefix . 'post_votes';

		$result = $wpdb->update(
			$table_name,
			[
				'upvotes'   => 0,
				'downvotes' => 0,
			],
			[ 'post_id' => $post_id ],
			[ '%d', '%d' ],
			[ '%d' ]
		);

		if ( false === $result ) {
			wp_send_json_error( 'Failed to reset votes' );
		}

		// Clear cache
		wp_cache_delete( "post_votes_{$post_id}", 'voting_system' );

		wp_send_json_success( 'Votes reset successfully' );
	}
}
