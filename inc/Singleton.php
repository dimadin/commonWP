<?php
/**
 * \dimadin\WP\Plugin\commonWP\Singleton trait.
 *
 * @package commonWP
 * @since 1.0.0
 */

namespace dimadin\WP\Plugin\commonWP;

/**
 * Singleton pattern.
 *
 * @since 1.0.0
 *
 * @link http://www.sitepoint.com/using-traits-in-php-5-4/
 */
trait Singleton {
	/**
	 * Instantiate called class.
	 *
	 * @since 1.0.0
	 *
	 * @staticvar bool|object $instance
	 *
	 * @return object $instance Instance of called class.
	 */
	public static function get_instance() {
		static $instance = false;

		if ( false === $instance ) {
			$instance = new static();
		}

		return $instance;
	}
}
