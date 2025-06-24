<?php
/**
 * @package roduza_helper
 */

namespace roduza_helper;

/**
 * The Init class for the plugin.
 *
 * This class is responsible for initializing all the core classes of the plugin.
 * It loops through the classes, instantiates them, and calls the register() method if it exists.
 */
final class Init {

	/**
	 * Store all the classes inside the array.
	 *
	 * @return array Full list of classes.
	 */
	public static function get_services() {
		return array(
			Admin\RoduzaHelper::class,
		);
	}

	/**
	 * Loop through the classes, initialise them, and call the register() method if it exists
	 *
	 * @return void
	 */
	public static function register_services() {

		foreach ( self::get_services() as $class ) {
			$service = self::instantiate( $class );
			if ( method_exists( $service, 'register' ) ) {
				$service->register();
			}
		}
	}

	/**
	 * Instantiate the class.
	 *
	 * @param class $class    The class from the service array to instantiate.
	 *
	 * @return class instance new instance of the class.
	 */
	private static function instantiate( $class ) {
		$service = new $class();
		return $service;
	}
}
