<?php
/**
 * \dimadin\WP\Plugin\commonWP\Clean class.
 *
 * @package commonWP
 * @since 1.0.0
 */

namespace dimadin\WP\Plugin\commonWP;

use dimadin\WP\Plugin\commonWP\Store;
use dimadin\WP\Plugin\commonWP\Utils;
use dimadin\WP\Plugin\commonWP\Expiration;
use WP_Temporary;

/**
 * Class with methods for removing commonWP data.
 *
 * @since 1.0.0
 */
class Clean {
	/**
	 * Delete everything from storage that commonWP uses.
	 *
	 * @since 1.0.0
	 */
	public static function all() {
		static::temporaries();
		Store::delete();
		WP_Temporary::clean();
	}

	/**
	 * Delete paths whose TTLs have passed.
	 *
	 * @since 1.0.0
	 */
	public static function expired() {
		$stored_data = Store::get();

		if ( ! is_array( $stored_data ) ) {
			return;
		}

		// Loop through types of paths.
		foreach ( [ 'active', 'inactive', 'queue' ] as $path_type ) {
			if ( array_key_exists( $path_type, $stored_data ) ) {
				foreach ( $stored_data[ $path_type ] as $origin_path => $path_settings ) {
					// Remove path if expiration time was in past.
					if ( time() > $path_settings['ttl'] ) {
						unset( $stored_data[ $path_type ][ $origin_path ] );
					}
				}
			}
		}

		Store::update( $stored_data );
	}

	/**
	 * Delete all temporaries set by commonWP.
	 *
	 * @since 1.0.0
	 */
	public static function temporaries() {
		$delete_method = Utils::get_temporaries_method( 'delete' );

		$commonwp_temporaries = [
			'commonwp_latest_core_versions',
			'commonwp_latest_plugins_versions',
			'commonwp_latest_themes_versions',
			'commonwp_processing_queue',
			'commonwp_recently_upgraded_core',
		];

		foreach ( $commonwp_temporaries as $commonwp_temporary ) {
			WP_Temporary::$delete_method( $commonwp_temporary );
		}
	}

	/**
	 * Delete paths whose TTLs have passed and schedule new execution if core was recently upgraded.
	 *
	 * @since 1.0.0
	 */
	public static function for_recently_upgraded_core() {
		// Delete expired paths.
		static::expired();

		// Check if core was recently upgraded and if it is, schedule this method's hook.
		if ( Expiration::is_recently_upgraded_core() ) {
			/** This filter is documented in inc/Expiration.php */
			$ttl = apply_filters( 'commonwp_inactive_path_ttl_for_recently_upgraded_core', 15 * MINUTE_IN_SECONDS );

			// This method should occur again when inactive paths for core expire.
			wp_schedule_single_event( time() + $ttl + 1, 'for_recently_upgraded_core' );
		}
	}

	/**
	 * Delete all paths that start with any of passed paths.
	 *
	 * @since 1.0.0
	 *
	 * @param array $starting_paths Array of starting paths.
	 */
	public static function starting_with( $starting_paths ) {
		$stored_data = Store::get();

		if ( ! is_array( $stored_data ) ) {
			return;
		}

		// Loop through types of paths.
		foreach ( [ 'active', 'inactive', 'queue' ] as $path_type ) {
			if ( array_key_exists( $path_type, $stored_data ) ) {
				foreach ( $stored_data[ $path_type ] as $origin_path => $path_settings ) {
					// Loop through all passed starting paths.
					foreach ( (array) $starting_paths as $starting_path ) {
						if ( 0 === strpos( $origin_path, $starting_path ) ) {
							unset( $stored_data[ $path_type ][ $origin_path ] );
							break;
						}
					}
				}
			}
		}

		Store::update( $stored_data );
	}

	/**
	 * Delete all paths that start with any of paths of all upgraded.
	 *
	 * If core is upgraded, also set temporary that core was recently upgraded.
	 *
	 * @since 1.0.0
	 *
	 * @param \WP_Upgrader $upgrader  \WP_Upgrader instance. In other contexts, $upgrader, might be a
	 *                                \Theme_Upgrader, \Plugin_Upgrader, \Core_Upgrade, or \Language_Pack_Upgrader instance.
	 * @param array        $hook_extra {
	 *     Array of bulk item update data. See {@see 'upgrader_process_complete'} for all params.
	 *
	 *     @type string $type    Type of update process. Accepts 'plugin', 'theme', 'translation', or 'core'.
	 *     @type array  $plugins Array of the basename paths of the plugins' main files.
	 *     @type array  $themes  The theme slugs.
	 * }
	 */
	public static function after_upgrade( $upgrader, $hook_extra ) {
		// Delete paths using specific method based on type.
		switch ( $hook_extra['type'] ) {
			case 'core':
				Expiration::set_for_recently_upgraded_core();
				static::core_paths();
				break;
			case 'plugin':
				static::plugin_paths( $hook_extra['plugins'] );
				break;
			case 'theme':
				static::theme_paths( $hook_extra['themes'] );
				break;
		}
	}

	/**
	 * Delete all paths that start with any of paths of used by core.
	 *
	 * @since 1.0.0
	 */
	public static function core_paths() {
		$dirs   = Utils::get_default_dirs();
		$prefix = Utils::get_relative_path_prefix( 'site' );

		// Prepend prefix if there is one.
		if ( $prefix ) {
			$dirs = array_map( function( $dir ) use ( $prefix ) {
				return $prefix . $dir;
			}, $dirs );
		}

		static::starting_with( $dirs );
	}

	/**
	 * Delete all paths that start with any of paths of any passed plugin.
	 *
	 * @since 1.0.0
	 *
	 * @param array $plugins Array of the basename paths of the plugins' main files.
	 */
	public static function plugin_paths( $plugins ) {
		$path_to_plugins_dir_relative_from_wp = str_replace( Utils::get_root_url( 'content' ), '', plugins_url() );
		$prefix                               = Utils::get_relative_path_prefix( 'content' );
		$starting_paths                       = [];

		foreach ( (array) $plugins as $plugin_basename_path ) {
			$starting_paths[] = $prefix . $path_to_plugins_dir_relative_from_wp . '/' . Utils::get_dir_from_path( $plugin_basename_path ) . '/';
		}

		static::starting_with( $starting_paths );
	}

	/**
	 * Delete all paths that start with any of paths of any passed theme.
	 *
	 * @since 1.0.0
	 *
	 * @param array $themes Array of theme slugs.
	 */
	public static function theme_paths( $themes ) {
		$path_to_themes_dir_relative_from_wp = str_replace( Utils::get_root_url( 'content' ), '', get_theme_root_uri() );
		$prefix                              = Utils::get_relative_path_prefix( 'content' );
		$starting_paths                      = [];

		foreach ( (array) $themes as $theme_slug ) {
			$starting_paths[] = $prefix . $path_to_themes_dir_relative_from_wp . '/' . $theme_slug . '/';
		}

		static::starting_with( $starting_paths );
	}

	/**
	 * Delete all paths that start with path of plugin after that plugin is deactivated.
	 *
	 * @since 1.0.0
	 *
	 * @param string $plugin               Basename path of the plugin's main file.
	 *                                     (Path to the main plugin file from plugins directory).
	 * @param bool   $network_deactivating In multisite, whether the plugin is deactivated for all sites
	 *                                     in the network or just the current site, or false for single sites.
	 */
	public static function after_plugin_deactivation( $plugin, $network_deactivating ) {
		// If this plugin is deactivated, delete everything.
		if ( Utils::get_commonwp_plugin_basename() === $plugin ) {
			static::all();
			return;
		}

		// Don't delete on multisite when deactivated for single site.
		if ( is_multisite() && ! $network_deactivating ) {
			return;
		}

		static::plugin_paths( $plugin );
	}

	/**
	 * Delete theme paths after it is switched from that theme.
	 *
	 * @since 1.0.0
	 *
	 * @param string    $new_name  Name of the new theme.
	 * @param \WP_Theme $new_theme \WP_Theme instance of the new theme.
	 * @param \WP_Theme $old_theme \WP_Theme instance of the old theme.
	 */
	public static function after_theme_switch( $new_name, $new_theme, $old_theme ) {
		// Don't delete on multisite.
		if ( is_multisite() ) {
			return;
		}

		static::theme_paths( array_unique( [ $old_theme->get_stylesheet(), $old_theme->get_template() ] ) );
	}
}
