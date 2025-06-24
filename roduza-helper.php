<?php
/**
 * Plugin Name:     Roduza Helper
 * Plugin URI:      https://www.ocenchris.com
 * Description:     A demo plugin to show how to use block bindings in WordPress.
 * Author:          Ocen Chris
 * Author URI:      https://www.ocenchris.com
 * Text Domain:     roduza-helper
 * Domain Path:     /languages
 * Version:         1.0.0
 *
 * @package roduza_helper
 */

// If this file is called directly, abort!!!
defined( 'ABSPATH' ) || die( 'No Access!' );

// Require once the Composer Autoload.
if ( file_exists( __DIR__ . '/lib/autoload.php' ) ) {
	require_once __DIR__ . '/lib/autoload.php';
}

/**
 *  Runs during plugin activation.
 *
 * @return void
 */
function activate_roduza_helper_plugin() {
	roduza_helper\Base\Activate::activate();
}

register_activation_hook( __FILE__, 'activate_roduza_helper_plugin' );

/**
 *  Runs during plugin deactivation.
 *
 * @return void
 */
function deactivate_roduza_helper_plugin() {
	roduza_helper\Base\Deactivate::deactivate();
}

register_deactivation_hook( __FILE__, 'deactivate_roduza_helper_plugin' );


/**
 * Initialize all the core classes of the plugin.
 */
if ( class_exists( 'roduza_helper\\Init' ) ) {
	roduza_helper\Init::register_services();
}
