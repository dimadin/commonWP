<?php
/**
 * \dimadin\WP\Plugin\commonWP\Clean class.
 *
 * @package commonWP
 * @since 1.0.0
 */

namespace dimadin\WP\Plugin\commonWP;

use dimadin\WP\Plugin\commonWP\Lock;
use dimadin\WP\Plugin\commonWP\Utils;

/**
 * Class for working with storage for data.
 *
 * @since 1.0.0
 */
class Store {
	/**
	 * Delete stored data.
	 *
	 * @since 1.0.0
	 */
	public static function delete() {
		$func = Utils::get_store_callback( 'delete' );

		$func( 'commonwp_data' );
	}

	/**
	 * Get stored data.
	 *
	 * @since 1.0.0
	 */
	public static function get() {
		$func = Utils::get_store_callback( 'get' );

		return $func( 'commonwp_data' );
	}

	/**
	 * Update stored data.
	 *
	 * @since 1.0.0
	 *
	 * @param array $settings New data to store.
	 */
	public static function update( $settings ) {
		if ( ! Lock::is_globally() ) {
			$func = Utils::get_store_callback( 'update' );

			$func( 'commonwp_data', $settings );
		}
	}
}
