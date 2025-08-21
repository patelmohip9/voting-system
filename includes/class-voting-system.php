<?php

/**
 * The file that defines the core plugin class
 *
 * A class definition that includes attributes and functions used across both the
 * public-facing side of the site and the admin area.
 *
 * @link       https://patelmohip.in
 * @since      1.0.0
 *
 * @package    Voting_System
 * @subpackage Voting_System/includes
 */


class Voting_System {

	/**
	 * The loader that's responsible for maintaining and registering all hooks that power
	 * the plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      Voting_System_Loader    $loader    Maintains and registers all hooks for the plugin.
	 */
	// protected $loader;

	/**
	 * The unique identifier of this plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string    $plugin_name    The string used to uniquely identify this plugin.
	 */
	protected $plugin_name;

	/**
	 * The current version of the plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string    $version    The current version of the plugin.
	 */
	protected $version;

	/**
	 * Define the core functionality of the plugin.
	 *
	 * Set the plugin name and the plugin version that can be used throughout the plugin.
	 * Load the dependencies, define the locale, and set the hooks for the admin area and
	 * the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function __construct() {
		if ( defined( 'VOTING_SYSTEM_VERSION' ) ) {
			$this->version = VOTING_SYSTEM_VERSION;
		} else {
			$this->version = '1.0.0';
		}
		$this->plugin_name = 'voting-system';

		$this->load_dependencies();
		$this->define_admin_hooks();
		$this->init_voting_system();

	}

	/**
	 * Load the required dependencies for this plugin.
	 *
	 * Include the following files that make up the plugin:
	 *
	 * - Voting_System_Admin. Defines all hooks for the admin area.
	 * - Voting_System_Public. Defines all hooks for the public side of the site.
	 * - Voting_System_Votes. Handles voting functionality and REST API.
	 * - Voting_System_Admin_Dashboard. Handles admin dashboard.
	 *
	 * Create an instance of the loader which will be used to register the hooks
	 * with WordPress.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function load_dependencies() {
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-voting-system-votes.php';
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-voting-system-admin-dashboard.php';
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/class-voting-system-admin.php';
	}

	/**
	 * Register all of the hooks related to the admin area functionality
	 * of the plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function define_admin_hooks() {
		$plugin_admin = new Voting_System_Admin( $this->get_plugin_name(), $this->get_version() );
	}

	/**
	 * Initialize the voting system.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function init_voting_system() {
		// Initialize the voting system core functionality
		new Voting_System_Votes();
	}

	/**
	 * The name of the plugin used to uniquely identify it within the context of
	 * WordPress and to define internationalization functionality.
	 *
	 * @since     1.0.0
	 * @return    string    The name of the plugin.
	 */
	public function get_plugin_name() {
		return $this->plugin_name;
	}

	/**
	 * Retrieve the version number of the plugin.
	 *
	 * @since     1.0.0
	 * @return    string    The version number of the plugin.
	 */
	public function get_version() {
		return $this->version;
	}

}
