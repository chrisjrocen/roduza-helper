<?php
/**
 * @package roduza_helper
 */

namespace roduza_helper\Base;

/**
 * The base controller class for the plugin.
 *
 * This class is responsible for setting up the plugin's path, URL, and slug.
 * It can be extended by other classes to provide additional functionality.
 */
class BaseController {

	/**
	 * The path to the plugin directory.
	 *
	 * @var string
	 */
	public $plugin_path;

	/**
	 * The URL to the plugin directory.
	 *
	 * @var string
	 */
	public $plugin_url;

	/**
	 * The slug of the plugin.
	 *
	 * @var string
	 */
	public $plugin_slug;

	/**
	 * The plugin basename.
	 */
	public function __construct() {
		$this->plugin_path = plugin_dir_path( dirname( __DIR__, 1 ) );
		$this->plugin_url  = plugin_dir_url( dirname( __DIR__, 1 ) );
		$this->plugin_slug = plugin_basename( dirname( __DIR__, 2 ) ) . '/roduza-helper.php';
	}
}
