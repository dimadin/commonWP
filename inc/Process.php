<?php
/**
 * \dimadin\WP\Plugin\commonWP\Process class.
 *
 * @package commonWP
 * @since 1.0.0
 */

namespace dimadin\WP\Plugin\commonWP;

use dimadin\WP\Plugin\commonWP\Singleton;
use dimadin\WP\Plugin\commonWP\NPM;
use dimadin\WP\Plugin\commonWP\Utils;
use dimadin\WP\Plugin\commonWP\Expiration;
use dimadin\WP\Plugin\commonWP\Versions;
use ReflectionClass;
use Exception;
use _WP_Dependency;

/**
 * Class that processes current path to get remote one.
 *
 * @since 1.0.0
 */
class Process {
	use Singleton;

	/**
	 * Type of dependency.
	 *
	 * @since 1.0.0
	 * @var string
	 */
	public $type;

	/**
	 * Handle by which dependency is registered in WordPress.
	 *
	 * @since 1.0.0
	 * @var string
	 */
	public $handle;

	/**
	 * NPM data of dependency.
	 *
	 * @since 1.0.0
	 * @var array
	 */
	public $npm_data;

	/**
	 * Final URL of dependency before rewrite.
	 *
	 * @since 1.0.0
	 * @var string
	 */
	public $src;

	/**
	 * Path relative to the WP root.
	 *
	 * @since 1.0.0
	 * @var string
	 */
	public $origin_path;

	/**
	 * Path relative to cdn.jsdelivr.net root.
	 *
	 * @since 1.0.0
	 * @var string
	 */
	public $remote_path;

	/**
	 * Content of local path.
	 *
	 * @since 1.0.0
	 * @var string
	 */
	public $origin_content;

	/**
	 * Status of the path after processing.
	 *
	 * @since 1.0.0
	 * @var string
	 */
	public $status;

	/**
	 * TTL of path.
	 *
	 * @since 1.0.0
	 * @var int
	 */
	public $ttl;

	/**
	 * Subresource integrity hash of the path.
	 *
	 * @since 1.0.0
	 * @var string
	 */
	public $subresource_integrity;

	/**
	 * Process path.
	 *
	 * Tries different processing methods to see if it is possible
	 * to rewrite.
	 *
	 * @since 1.0.0
	 *
	 * @throws Exception If processing isn't successful.
	 */
	public function execute() {
		$relative_path = Utils::sanitize_relative_path( $this->origin_path );

		$extension = strtolower( pathinfo( wp_parse_url( $relative_path, PHP_URL_PATH ), PATHINFO_EXTENSION ) );

		// Check if path is dynamic file.
		if ( empty( $extension ) || 'php' === $extension ) {
			throw new Exception( 'Path is dynamic file.' );
		}

		try {
			// First process path with NPM.
			$this->npm();
		} catch ( Exception $e ) {
			// Check if this is from core directories.
			if ( Utils::is_in_default_dir( $relative_path ) ) {
				$this->core();
				// Check if this is plugin.
			} elseif ( 0 === strpos( $this->src, plugins_url() ) ) {
				$this->plugin();
				// Check if this is theme.
			} elseif ( 0 === strpos( $this->src, get_theme_root_uri() ) ) {
				$this->theme();
			}
		}

		// If status is not 'active', path was not successfully processed.
		if ( empty( $this->status ) || 'active' !== $this->status ) {
			throw new Exception( 'Path was not successfully processed.' );
		}
	}

	/**
	 * Process path by testing if it is possible to rewrite using NPM path.
	 *
	 * @since 1.0.0
	 *
	 * @throws Exception If processing isn't successful.
	 */
	public function npm() {
		// Get NPM data for dependency.
		$this->npm_data = NPM::get_data( $this->handle, $this->type );

		$dependencies = Utils::get_dependencies_class( $this->type );

		// Set package and file name.
		$package = $this->npm_data['package'];
		$file    = $this->npm_data['file'];

		// Get handle's registered associated data.
		$dependency = $dependencies->query( $this->handle );

		// Check if handle is registered.
		if ( $dependency instanceof _WP_Dependency ) {
			// Use the same version as one that is registered for handle.
			$version = $dependency->ver;
		} else {
			// Guess version from origin URL.
			$version = Versions::get_dependency_version_from_url( $this->src );
		}

		// Check if item has minified version and set suffix.
		if ( ( ! defined( 'SCRIPT_DEBUG' ) || ! SCRIPT_DEBUG ) && $this->npm_data['minified'] ) {
			$suffix = $this->npm_data['minified'];
		} else {
			$suffix = '';
		}

		$extension = ( 'style' === $this->type ) ? 'css' : 'js';

		$jsdelivr_path = "/npm/{$package}@{$version}/{$file}{$suffix}.{$extension}";
		$jsdelivr_url  = 'https://cdn.jsdelivr.net' . $jsdelivr_path;

		$remote_content = Utils::get_remote_content( $jsdelivr_url );
		$origin_content = $this->get_origin_content();

		$raw_remote_content = $remote_content;

		if ( $remote_content !== $origin_content ) {
			// For some handles, we know that there will be difference.
			if ( ( ! defined( 'SCRIPT_DEBUG' ) || ! SCRIPT_DEBUG ) && 'script' === $this->type ) {
				switch ( $this->handle ) {
					case 'jquery-core':
						$origin_content = rtrim( str_replace( 'jQuery.noConflict();', '', $origin_content ) );
						$remote_content = rtrim( $remote_content );

						break;

					case 'underscore':
						$remote_content = rtrim( str_replace( '//# sourceMappingURL=underscore-min.map', '', $remote_content ) );
						$origin_content = rtrim( $origin_content );

						break;
				}
			}

			// Compare again after applied known differences.
			if ( $remote_content !== $origin_content ) {
				/**
				 * Filter whether to always ask for identical content on NPM.
				 *
				 * Some core dependencies are known to have the same content in NPM package,
				 * but are using either different minification method, or aren't providing
				 * unminified file. This filter allows to skip strict checking for those files.
				 *
				 * Passing a truthy value to the filter will effectively short-circuit comparison.
				 *
				 * @since 1.0.0
				 *
				 * @param bool    $strictly_compare Value to return. Any value other than true
				 *                                  will short-circuit strict comparison.
				 * @param Process $this             Current instance of class.
				 */
				$compare = apply_filters( 'commonwp_npm_compare_with_local', true, $this );

				// If we still want to have identical files, produce error.
				if ( $compare ) {
					throw new Exception( 'File on NPM is not the same as local file.' );
				}
			}
		}

		$this->status      = 'active';
		$this->remote_path = $jsdelivr_path;

		/**
		 * Filter TTL of active path that rewrites to NPM path.
		 *
		 * Note that path might be cached as active for up to 12 hours after
		 * expiration. Garbage collector is scheduled to run twice daily,
		 * though it can be run before.
		 *
		 * @since 1.0.0
		 *
		 * @param int     $ttl  TTL of active path in seconds. Default 604800 (one week).
		 * @param Process $this Current instance of class.
		 */
		$ttl = apply_filters( 'commonwp_npm_path_ttl', WEEK_IN_SECONDS, $this );

		$this->ttl = time() + $ttl;
		$this->add_subresource_integrity( $raw_remote_content );
	}

	/**
	 * Process path by testing if it is possible to rewrite using core path.
	 *
	 * @since 1.0.0
	 *
	 * @throws Exception If processing isn't successful.
	 */
	public function core() {
		// Always start with currently installed version.
		$version = get_bloginfo( 'version' );

		// Always get latest stable version.
		$latest_core_version = Versions::get_latest_wp_version();

		// Check if there are any updates.
		if ( version_compare( $latest_core_version, $version, '>' ) ) {
			$latest_branch_core_version = Versions::get_latest_wp_version_in_branch( $version );

			// Check if there is new minor version in the same branch.
			if ( ( $latest_branch_core_version != $latest_core_version ) && version_compare( $latest_branch_core_version, $version, '=' ) ) { // phpcs:ignore WordPress.PHP.StrictComparisons.LooseComparison
				/**
				 * Filter whether to rewrite core path with current version when pending major core update.
				 *
				 * When rewriting using core path, version of core will be in remote URL.
				 * This filter is used in cases when there is a new major version of core
				 * in newer branch, you have latest minor version in your branch, and you
				 * don't want to expose your currently installed version.
				 *
				 * Passing a false value will force rewriting with remote path using
				 * latest core version.
				 *
				 * @since 1.0.0
				 *
				 * @param bool    $process_with_major_update Value to return.
				 * @param Process $this                      Current instance of class.
				 */
				$process_with_major_update = apply_filters( 'commonwp_process_core_with_major_update', true, $this );

				if ( false === $process_with_major_update ) {
					$version = $latest_core_version;
				}
			} else {
				/**
				 * Filter whether to rewrite core path with current version when pending minor core update.
				 *
				 * When rewriting using core path, version of core will be in remote URL.
				 * This filter is used in cases when there is a new minor version of core in
				 * the same branch, no matter if there is a new branch, and you don't want
				 * to expose your currently installed version.
				 *
				 * Passing a false value will force rewriting with remote path using
				 * latest core branch version.
				 *
				 * @since 1.0.0
				 *
				 * @param bool    $process_with_minor_update Value to return.
				 * @param Process $this                      Current instance of class.
				 */
				$process_with_minor_update = apply_filters( 'commonwp_process_core_with_minor_update', true, $this );

				if ( false === $process_with_minor_update ) {
					$version = $latest_branch_core_version;
				}
			}
		}

		// Remove version as there is one in path already.
		$file = remove_query_arg( 'ver', Utils::sanitize_relative_path( $this->origin_path ) );

		// Process core file using GitHub.
		try {
			return $this->github( 'wordpress/wordpress', Versions::get_stable_wp_version( $version ), $file );
		} catch ( Exception $e ) {
			// If core was recently upgraded, it is assumed that it uses latest branch version.
			if ( Expiration::is_recently_upgraded_core() ) {
				// Check if latest major version is requested.
				if ( ! isset( $process_with_major_update ) || false !== $process_with_major_update ) {
					try {
						// Try with previous minor version.
						$previous_version = Versions::get_previous_wp_version_in_branch( get_bloginfo( 'version' ) );

						return $this->github( 'wordpress/wordpress', Versions::get_stable_wp_version( $previous_version ), $file );
					} catch ( Exception $e ) { // phpcs:ignore Generic.CodeAnalysis.EmptyStatement
						// Proceed with latest stable version.
					}
				}
			}
		}

		// Finally, try with latest stable version.
		if ( $version != $latest_core_version ) { // phpcs:ignore WordPress.PHP.StrictComparisons.LooseComparison
			try {
				return $this->github( 'wordpress/wordpress', Versions::get_stable_wp_version( $latest_core_version ), $file );
			} catch ( Exception $e ) { // phpcs:ignore Generic.CodeAnalysis.EmptyStatement
				// There isn't any identical remote file.
			}
		}

		throw new Exception( 'Core file is not processed as identical remote file has not been found.' );
	}

	/**
	 * Process path by testing if it is possible to rewrite using plugin path.
	 *
	 * @since 1.0.0
	 *
	 * @throws Exception If processing isn't successful.
	 */
	public function plugin() {
		// Get path relative to plugins directory.
		$plugins_relative_path = str_replace( plugins_url(), '', $this->src );

		// Get plugin's directory name.
		$plugin_slug = Utils::get_dir_from_path( $plugins_relative_path );

		// Guess plugin from plugin's directory name.
		$plugin = Utils::get_plugin_from_slug( $plugin_slug );

		// Always start with currently installed version.
		$version = $plugin['data']['Version'];

		// Always get latest stable version.
		$latest_version = Versions::get_latest_plugin_version( $plugin );

		// Check if there are any updates.
		if ( version_compare( $latest_version, $version, '>' ) ) {
			/**
			 * Filter whether to rewrite plugin path with pending plugin's update.
			 *
			 * When rewriting using plugin path, version of plugin will be in remote URL.
			 * This filter is used in cases when there is new version of plugin but you
			 * don't want to expose your currently installed version.
			 *
			 * Passing a false value will force rewriting with remote path using
			 * latest plugin version.
			 *
			 * @since 1.0.0
			 *
			 * @param bool    $process_with_update Value to return.
			 * @param Process $this                Current instance of class.
			 * @param array   $plugin {
			 *     Array of plugin's data.
			 *
			 *     @type string $file Basename path of the plugin's main file.
			 *     @type array  $data Array of plugin's data. See {@see get_plugins()}.
			 * }
			 */
			$process_with_update = apply_filters( 'commonwp_process_plugin_with_update', true, $this, $plugin );

			if ( false === $process_with_update ) {
				$version = $latest_version;
			}
		}

		// Remove version as there is one in path already, and remove plugin slug from the start.
		$file = remove_query_arg( 'ver', Utils::str_replace_once( '/' . $plugin_slug, '', $plugins_relative_path ) );

		// Check if plugin is hosted on GitHub.
		$repository = array_key_exists( 'GitHub Plugin URI', $plugin['data'] ) ? Utils::sanitize_github_repository_name( $plugin['data']['GitHub Plugin URI'] ) : '';

		if ( $repository ) {
			try {
				return $this->github( $repository, $version, $file );
			} catch ( Exception $e ) { // phpcs:ignore Generic.CodeAnalysis.EmptyStatement
				// Proceed with standard processing.
			}
		}

		// Check if plugin is specifying that is not hosted on WordPress.org.
		if ( array_key_exists( 'Private', $plugin['data'] ) && 'true' === strtolower( $plugin['data']['Private'] ) ) {
			throw new Exception( 'Plugin file is not processed as plugin is not hosted on WordPress.org.' );
		}

		$jsdelivr_path = "/wp/plugins/{$plugin_slug}/tags/{$version}{$file}";
		$jsdelivr_url  = 'https://cdn.jsdelivr.net' . $jsdelivr_path;

		$remote_content = Utils::get_remote_content( $jsdelivr_url );
		$origin_content = $this->get_origin_content();

		if ( $remote_content !== $origin_content ) {
			throw new Exception( 'Remote plugin file is not the same as local file.' );
		}

		$this->status      = 'active';
		$this->remote_path = $jsdelivr_path;

		/**
		 * Filter TTL of active path that rewrites to plugin path.
		 *
		 * Note that path might be cached as active for up to 12 hours after
		 * expiration. Garbage collector is scheduled to run twice daily,
		 * though it can be run before.
		 *
		 * @since 1.0.0
		 *
		 * @param int     $ttl  TTL of active path in seconds. Default 86400 (one day).
		 * @param Process $this Current instance of class.
		 */
		$ttl = apply_filters( 'commonwp_plugin_path_ttl', DAY_IN_SECONDS, $this );

		$this->ttl = time() + $ttl;
		$this->add_subresource_integrity( $remote_content );
	}

	/**
	 * Process path by testing if it is possible to rewrite using theme path.
	 *
	 * @since 1.0.0
	 *
	 * @throws Exception If processing isn't successful.
	 */
	public function theme() {
		// Get path relative to theme directory.
		$themes_relative_path = str_replace( get_theme_root_uri(), '', $this->src );

		// Get theme directory name.
		$theme_slug = Utils::get_dir_from_path( $themes_relative_path );

		// Remove version as there is one in path already.
		$file = remove_query_arg( 'ver', $themes_relative_path );

		// Guess theme from relative path.
		$theme = Utils::get_theme_from_slug( $theme_slug );

		// Always start with currently installed version.
		$version = $theme['data']->get( 'Version' );

		// Always get latest stable version.
		$latest_version = Versions::get_latest_theme_version( $theme );

		// Check if there are any updates.
		if ( version_compare( $latest_version, $version, '>' ) ) {
			/**
			 * Filter whether to rewrite theme path with pending theme's update.
			 *
			 * When rewriting using theme path, version of theme will be in remote URL.
			 * This filter is used in cases when there is new version of theme but you
			 * don't want to expose your currently installed version.
			 *
			 * Passing a false value will force rewriting with remote path using
			 * latest theme version.
			 *
			 * @since 1.0.0
			 *
			 * @param bool    $process_with_update Value to return.
			 * @param Process $this                Current instance of class.
			 * @param array   $theme {
			 *     Array of theme's data.
			 *
			 *     @type string    $slug Theme's slug.
			 *     @type \WP_Theme $data \WP_Theme instance of the theme.
			 * }
			 */
			$process_with_update = apply_filters( 'commonwp_process_theme_with_update', true, $this, $theme );

			if ( false === $process_with_update ) {
				$version = $latest_version;
			}
		}

		// Remove theme slug from the start.
		$file = Utils::str_replace_once( '/' . $theme_slug, '', $file );

		// Check if theme is hosted on GitHub.
		$repository = $theme['data']->get( 'GitHub Theme URI' );

		if ( $repository ) {
			try {
				return $this->github( Utils::sanitize_github_repository_name( $repository ), $version, $file );
			} catch ( Exception $e ) { // phpcs:ignore Generic.CodeAnalysis.EmptyStatement
				// Proceed with standard processing.
			}
		}

		// Check if theme is specifying that is not hosted on WordPress.org.
		$private = $theme['data']->get( 'Private' );

		if ( 'true' === strtolower( $private ) ) {
			throw new Exception( 'Theme file is not processed as theme is not hosted on WordPress.org.' );
		}

		try {
			$jsdelivr_path = "/wp/themes/{$theme_slug}/{$version}{$file}";
			$jsdelivr_url  = 'https://cdn.jsdelivr.net' . $jsdelivr_path;

			$remote_content = Utils::get_remote_content( $jsdelivr_url );
			$origin_content = $this->get_origin_content();

			if ( $remote_content !== $origin_content ) {
				throw new Exception( 'Remote theme file is not the same as local file.' );
			}

			$this->status      = 'active';
			$this->remote_path = $jsdelivr_path;

			/**
			 * Filter TTL of active path that rewrites to theme path.
			 *
			 * Note that path might be cached as active for up to 12 hours after
			 * expiration. Garbage collector is scheduled to run twice daily,
			 * though it can be run before.
			 *
			 * @since 1.0.0
			 *
			 * @param int     $ttl  TTL of active path in seconds. Default 259200 (three days).
			 * @param Process $this Current instance of class.
			 */
			$ttl = apply_filters( 'commonwp_theme_path_ttl', 3 * DAY_IN_SECONDS, $this );

			$this->ttl = time() + $ttl;
			$this->add_subresource_integrity( $remote_content );
		} catch ( Exception $e ) {
			// If latest version of default theme isn't requested in case of update, proceed with standard core path.
			if ( ( ! isset( $process_with_update ) || false !== $process_with_update ) && array_key_exists( $theme_slug, Utils::get_default_themes( $theme['data'] ) ) ) {
				return $this->core();
			} else {
				throw new Exception( $e->getMessage() );
			}
		}
	}

	/**
	 * Process URL of directory that holds SVG versions of emoji by testing its NPM path.
	 *
	 * @since 1.0.0
	 *
	 * @throws Exception If processing isn't successful.
	 */
	public function emoji_svg_url() {
		// Find version of Twemoji.
		$ver = Versions::get_twemoji_version_from_svg_url( $this->src );

		$jsdelivr_path     = "/npm/twemoji@{$ver}/2/svg/";
		$twemoji_test_file = '1f1f7-1f1f8.svg';

		// Test if Twemoji exists.
		$twemoji_test_url = "https://cdn.jsdelivr.net{$jsdelivr_path}{$twemoji_test_file}";
		Utils::get_remote_content( $twemoji_test_url );

		$this->status      = 'active';
		$this->remote_path = $jsdelivr_path;

		/**
		 * Filter TTL of active path that rewrites to emoji SVG directory to NPM path.
		 *
		 * Note that path might be cached as active for up to 12 hours after
		 * expiration. Garbage collector is scheduled to run twice daily,
		 * though it can be run before.
		 *
		 * @since 1.0.0
		 *
		 * @param int     $ttl  TTL of active path in seconds. Default 604800 (one week).
		 * @param Process $this Current instance of class.
		 */
		$ttl = apply_filters( 'commonwp_emoji_npm_path_ttl', WEEK_IN_SECONDS, $this );

		$this->ttl = time() + $ttl;
	}

	/**
	 * Process path by testing if it is possible to rewrite using GitHub path.
	 *
	 * @since 1.0.0
	 *
	 * @throws Exception If processing isn't successful.
	 *
	 * @param string $repository GitHub repository in the form of user/repository.
	 * @param string $version    Tag on GitHub.
	 * @param string $file       Path of corresponding file inside repository.
	 */
	public function github( $repository, $version, $file ) {
		$jsdelivr_path = "/gh/{$repository}@{$version}{$file}";
		$jsdelivr_url  = 'https://cdn.jsdelivr.net' . $jsdelivr_path;

		$remote_content = Utils::get_remote_content( $jsdelivr_url );
		$origin_content = $this->get_origin_content();

		if ( $remote_content !== $origin_content ) {
			throw new Exception( 'Remote file is not the same as local file.' );
		}

		$this->status      = 'active';
		$this->remote_path = $jsdelivr_path;

		/**
		 * Filter TTL of active path that rewrites to GitHub path.
		 *
		 * Note that path might be cached as active for up to 12 hours after
		 * expiration. Garbage collector is scheduled to run twice daily,
		 * though it can be run before.
		 *
		 * @since 1.0.0
		 *
		 * @param int     $ttl  TTL of active path in seconds. Default 172800 (two days).
		 * @param Process $this Current instance of class.
		 */
		$ttl = apply_filters( 'commonwp_github_path_ttl', 2 * DAY_IN_SECONDS, $this );

		$this->ttl = time() + $ttl;
		$this->add_subresource_integrity( $remote_content );
	}

	/**
	 * Add subresource integrity hash to instance's property.
	 *
	 * @since 1.0.0
	 *
	 * @param string $remote_content Content that should be hashed.
	 */
	public function add_subresource_integrity( $remote_content ) {
		/**
		 * Filter whether to hash content for current path.
		 *
		 * Passing a false value to the filter will effectively short-circuit hashing.
		 *
		 * @since 1.0.0
		 *
		 * @param bool    $to_hash Value to return. Any value other than false
		 *                         will short-circuit hashing.
		 * @param Process $this    Current instance of class.
		 */
		$process = apply_filters( 'commonwp_add_subresource_integrity', true, $this );

		if ( ! $process ) {
			return;
		}

		$this->subresource_integrity = Utils::get_subresource_integrity( $remote_content );
	}

	/**
	 * Get content of local path.
	 *
	 * @since 1.0.0
	 *
	 * @throws Exception If retrieving content isn't successful.
	 *
	 * @return string Content of local path.
	 */
	public function get_origin_content() {
		if ( ! empty( $this->origin_content ) ) {
			return $this->origin_content;
		}

		$this->origin_content = Utils::get_remote_content( $this->src );

		return $this->origin_content;
	}
}
