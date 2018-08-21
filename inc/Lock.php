<?php
/**
 * \dimadin\WP\Plugin\commonWP\Lock class.
 *
 * @package commonWP
 * @since 1.0.0
 */

namespace dimadin\WP\Plugin\commonWP;

use dimadin\WP\Plugin\commonWP\Utils;
use WP_Temporary;

/**
 * Class with methods triggered during active processing.
 *
 * @since 1.0.0
 */
class Lock {
	/**
	 * Delete temporary that processing queue is executing.
	 *
	 * @since 1.0.0
	 */
	public static function remove() {
		$method = Utils::get_temporaries_method( 'delete' );

		WP_Temporary::$method( 'commonwp_processing_queue' );
	}

	/**
	 * Check if processing queue is currently executing or it did execute on current request.
	 *
	 * @since 1.0.0
	 *
	 * @return bool True if it's executing or executed, otherwise false.
	 */
	public static function is() {
		return ( static::is_on_request() || static::is_globally() );
	}

	/**
	 * Check if processing queue is currently executing.
	 *
	 * It does so by checking if temporary is saved.
	 *
	 * @since 1.0.0
	 *
	 * @return bool True if it's executing, false if it doesn't.
	 */
	public static function is_globally() {
		$method = Utils::get_temporaries_method( 'get' );

		if ( WP_Temporary::$method( 'commonwp_processing_queue' ) ) {
			return true;
		} else {
			return false;
		}
	}

	/**
	 * Check if processing queue executed on current request.
	 *
	 * @since 1.0.0
	 *
	 * @return bool True if did executed, false if it didn't.
	 */
	public static function is_on_request() {
		if ( ( defined( 'COMMONWP_PROCESSING_QUEUE' ) && COMMONWP_PROCESSING_QUEUE ) ) {
			return true;
		} else {
			return false;
		}
	}

	/**
	 * Save that processing queue is executing.
	 *
	 * It both defines constant for current request and
	 * and saves temporary for other requests.
	 *
	 * @since 1.0.0
	 */
	public static function set() {
		if ( ! defined( 'COMMONWP_PROCESSING_QUEUE' ) ) {
			define( 'COMMONWP_PROCESSING_QUEUE', true );
		}

		$method = Utils::get_temporaries_method( 'set' );

		WP_Temporary::$method( 'commonwp_processing_queue', true, 5 * MINUTE_IN_SECONDS );
	}
}
