<?php
/**
 * \dimadin\WP\Plugin\commonWP\Paths class.
 *
 * @package commonWP
 * @since 1.0.0
 */

namespace dimadin\WP\Plugin\commonWP;

use dimadin\WP\Plugin\commonWP\Store;
use dimadin\WP\Plugin\commonWP\Singleton;

/**
 * Class with methods to get remote paths.
 *
 * @since 1.0.0
 */
class Paths {
	use Singleton;

	/**
	 * Array of active paths and their data.
	 *
	 * Active paths are paths that have replacement data.
	 *
	 * @since 1.0.0
	 * @var array
	 */
	public $active = [];

	/**
	 * Array of inactive paths and their data.
	 *
	 * Inactive paths are paths that don't have replacement.
	 *
	 * @since 1.0.0
	 * @var array
	 */
	public $inactive = [];

	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		// Set raw data to class properties when initializing class.
		$this->setup();
	}

	/**
	 * Force regeneration of class properties with raw data.
	 *
	 * @since 1.0.0
	 */
	public function reset() {
		// Set properties to initial values.
		$this->active   = [];
		$this->inactive = [];

		// Set properties from settings values.
		$this->setup();
	}

	/**
	 * Set raw data to class properties.
	 *
	 * @since 1.0.0
	 */
	public function setup() {
		$stored_data = Store::get();

		if ( ! is_array( $stored_data ) ) {
			return Store::delete();
		}

		// Check if it uses required database version.
		if ( array_key_exists( 'db_version', $stored_data ) && version_compare( $stored_data['db_version'], COMMONWP_VERSION, '!=' ) ) {
			return Store::delete();
		}

		// Setup active paths.
		if ( array_key_exists( 'active', $stored_data ) ) {
			$this->active = $stored_data['active'];
		}

		// Setup inactive paths.
		if ( array_key_exists( 'inactive', $stored_data ) ) {
			foreach ( $stored_data['inactive'] as $origin_path => $path_settings ) {
				array_push( $this->inactive, $origin_path );
			}
		}
	}

	/**
	 * Get replacement path for requested path.
	 *
	 * @since 1.0.0
	 *
	 * @param string $origin_path Path relative from WordPress installation.
	 * @return string|bool Replacement path if it exists, false otherwise.
	 */
	public function get_active( $origin_path ) {
		if ( array_key_exists( $origin_path, $this->active ) ) {
			return $this->active[ $origin_path ]['path'];
		} else {
			return false;
		}
	}

	/**
	 * Check if path is marked as one not having replacement.
	 *
	 * @since 1.0.0
	 *
	 * @param string $origin_path Path relative from WordPress installation.
	 * @return bool True if marked, false otherwise.
	 */
	public function is_inactive( $origin_path ) {
		if ( in_array( $origin_path, $this->inactive, true ) ) {
			return true;
		} else {
			return false;
		}
	}
}
