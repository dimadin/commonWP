<?php
/**
 * \dimadin\WP\Plugin\commonWP\Expiration class.
 *
 * @package commonWP
 * @since 1.0.0
 */

namespace dimadin\WP\Plugin\commonWP;

use dimadin\WP\Plugin\commonWP\Clean;
use dimadin\WP\Plugin\commonWP\Utils;
use WP_Temporary;

/**
 * Class with methods used when core is upgraded.
 *
 * @since 1.0.0
 */
class Expiration {
	/**
	 * Check if core was recently upgraded.
	 *
	 * When upgrading core, temporary is saved that core was upgraded.
	 * This method checks if such temporary exists.
	 *
	 * @since 1.0.0
	 *
	 * @return bool True if temporary exists, false otherwise.
	 */
	public static function is_recently_upgraded_core() {
		$method = Utils::get_temporaries_method( 'get' );

		if ( WP_Temporary::$method( 'commonwp_recently_upgraded_core' ) ) {
			return true;
		} else {
			return false;
		}
	}

	/**
	 * Set temporary that core was recently upgraded.
	 *
	 * @since 1.0.0
	 */
	public static function set_for_recently_upgraded_core() {
		$method = Utils::get_temporaries_method( 'set' );

		WP_Temporary::$method( 'commonwp_recently_upgraded_core', true, 3 * HOUR_IN_SECONDS );
	}

	/**
	 * Change TTL of inactive core paths if core was recently upgraded.
	 *
	 * Remote files for core paths come from tags on GitHub. That tags come from tags
	 * in SVN repository where development of core happens. During release of the new
	 * core package, tagging of new version happens some time after package is released.
	 * That means it is possible for WordPress installation to be upgraded to the new
	 * version before there are tags thus without remote files to use for paths.
	 *
	 * This method shortens TTL for inactive paths if they are for core files and if
	 * core was recently upgraded, which would force processing of core paths in time
	 * shorter than default one. Also, it schedules deletion of expired paths.
	 *
	 * @since 1.0.0
	 *
	 * @param int     $ttl     TTL of inactive path in seconds.
	 * @param Process $process Current instance of processing class.
	 * @return int $ttl Filtered TTL of inactive path.
	 */
	public static function filter_inactive_path_ttl( $ttl, $process ) {
		// Check if this is from core directories and core was recently upgraded, then lower ttl.
		if ( Utils::is_in_default_dir( $process->origin_path ) && static::is_recently_upgraded_core() ) {
			// Schedule deletion of expired paths.
			Clean::for_recently_upgraded_core();

			/**
			 * Filter TTL of inactive path for recently upgraded core.
			 *
			 * Used to shorten TTL for inactive paths if they are for core files and if
			 * core was recently upgraded, which would force processing of core paths in time
			 * shorter than default one.
			 *
			 * @since 1.0.0
			 *
			 * @param int TTL of inactive path in seconds. Default 900 (fifteen minutes).
			 */
			return apply_filters( 'commonwp_inactive_path_ttl_for_recently_upgraded_core', 15 * MINUTE_IN_SECONDS );
		}

		return $ttl;
	}
}
