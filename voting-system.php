<?php

/**
 *
 * @wordpress-plugin
 * Plugin Name:       Voting system
 * Plugin URI:        https://patelmohip.in
 * Description:       voting system
 * Version:           1.0.0
 * Author:            Patel Mohip
 * Author URI:        https://patelmohip.in/
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       voting-system
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Currently plugin version.
 * Start at version 1.0.0 and use SemVer - https://semver.org
 * Rename this for your plugin and update it as you release new versions.
 */
define( 'VOTING_SYSTEM_VERSION', '1.0.0' );

/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-voting-system-activator.php
 */
function activate_voting_system() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-voting-system-activator.php';
	Voting_System_Activator::activate();
}

register_activation_hook( __FILE__, 'activate_voting_system' );

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require plugin_dir_path( __FILE__ ) . 'includes/class-voting-system.php';

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.0
 */
function run_voting_system() {

	$plugin = new Voting_System();
	// $plugin->run();

}
run_voting_system();
