<?php
/**
 * \dimadin\WP\Plugin\commonWP\Utils class.
 *
 * @package commonWP
 * @since 1.0.0
 */

namespace dimadin\WP\Plugin\commonWP;

use Exception;
use ReflectionClass;
use WP_Temporary;

/**
 * Class with various utility methods.
 *
 * @since 1.0.0
 */
class Utils {
	/**
	 * Get initial store data.
	 *
	 * @since 1.0.0
	 *
	 * @return array $data Initial store data.
	 */
	public static function init_stored_data() {
		$data = [
			'db_version' => COMMONWP_VERSION,
			'active'     => [],
			'inactive'   => [],
			'queue'      => [],
		];

		return $data;
	}

	/**
	 * Return instance for dependency type class.
	 *
	 * @since 1.0.0
	 *
	 * @param string $type Type of dependency.
	 * @return \WP_Dependencies \WP_Dependencies instance.
	 */
	public static function get_dependencies_class( $type ) {
		if ( 'style' === $type ) {
			return wp_styles();
		} else {
			return wp_scripts();
		}
	}

	/**
	 * Get name of the first directory in the path.
	 *
	 * @since 1.0.0
	 *
	 * @param string $path Path to get from.
	 * @return string Directory name.
	 */
	public static function get_dir_from_path( $path ) {
		$parts = explode( '/', ltrim( $path, '/' ) );

		return $parts[0];
	}

	/**
	 * Get plugin's data from plugin's directory name.
	 *
	 * @since 1.0.0
	 *
	 * @throws Exception If plugin has not been found.
	 *
	 * @param string $slug Directory that hold plugin's main file.
	 * @return array $plugin {
	 *     Array of plugin's data.
	 *
	 *     @type string $file Basename path of the plugin's main file.
	 *     @type array  $data Array of plugin's data. See {@see get_plugins()}.
	 * }
	 */
	public static function get_plugin_from_slug( $slug ) {
		if ( ! function_exists( 'get_plugins' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		foreach ( get_plugins() as $plugins_path => $plugin_data ) {
			if ( 0 === strpos( $plugins_path, $slug . '/' ) ) {
				$plugin = [
					'file' => $plugins_path,
					'data' => $plugin_data,
				];

				/**
				 * Filter plugin's data.
				 *
				 * @since 1.0.0
				 *
				 * @param array $plugin {
				 *     Array of plugin's data.
				 *
				 *     @type string $file Basename path of the plugin's main file.
				 *     @type array  $data Array of plugin's data. See {@see get_plugins()}.
				 * }
				 * @param string $slug Directory that hold plugin's main file.
				 */
				return apply_filters( 'commonwp_plugin_from_slug', $plugin, $slug );
			}
		}

		throw new Exception( 'Plugin has not been found from provided slug.' );
	}

	/**
	 * Get theme's data from theme's directory name.
	 *
	 * @since 1.0.0
	 *
	 * @throws Exception If theme has not been found.
	 *
	 * @param string $slug Directory that hold theme's main file.
	 * @return array $theme {
	 *     Array of theme's data.
	 *
	 *     @type string    $slug Theme's slug.
	 *     @type \WP_Theme $data \WP_Theme instance of the theme.
	 * }
	 */
	public static function get_theme_from_slug( $slug ) {
		foreach ( wp_get_themes() as $theme_slug => $theme ) {
			if ( $slug === $theme_slug ) {
				$data = [
					'slug' => $theme_slug,
					'data' => $theme,
				];

				/**
				 * Filter theme's data.
				 *
				 * @since 1.0.0
				 *
				 * @param array $theme {
				 *     Array of theme's data.
				 *
				 *     @type string    $slug Theme's slug.
				 *     @type \WP_Theme $data \WP_Theme instance of the theme.
				 * }
				 * @param string $slug Directory that hold theme's main file.
				 */
				return apply_filters( 'commonwp_theme_from_slug', $data, $slug );
			}
		}

		throw new Exception( 'Theme has not been found from provided slug.' );
	}

	/**
	 * Get basename path of the commonWP's main file.
	 *
	 * @since 1.0.0
	 *
	 * @return string Basename path of the commonWP's main file.
	 */
	public static function get_commonwp_plugin_basename() {
		$plugins_path = 'commonwp/commonwp.php';

		/**
		 * Filter basename path of the commonWP's main file.
		 *
		 * @since 1.0.0
		 *
		 * @param string $plugins_path Standard basename path of the commonWP's main file.
		 */
		return apply_filters( 'commonwp_plugin_basename', $plugins_path );
	}

	/**
	 * Get GitHub's repository name from path.
	 *
	 * Given any form of possible paths, generate GitHub repository
	 * in the form of user/repository.
	 *
	 * @since 1.0.0
	 *
	 * @param string $string Path of GitHub's repository.
	 * @return string GitHub repository in the form of user/repository.
	 */
	public static function sanitize_github_repository_name( $string ) {
		return ltrim( untrailingslashit( strtolower( wp_parse_url( $string, PHP_URL_PATH ) ) ), '/' );
	}

	/**
	 * Remove prefix from relative path if prefix exists.
	 *
	 * @since 1.0.0
	 *
	 * @param string $path Relative path that might have prefix.
	 * @return string Path that doesn't have prefix.
	 */
	public static function sanitize_relative_path( $path ) {
		return preg_replace( '/#(SITE|CONTENT)#/', '', $path );
	}

	/**
	 * Get prefix for use in relative path.
	 *
	 * For most sites, this prefix is empty, but if site
	 * uses non-standard content directory it changes based
	 * on requested type.
	 *
	 * @since 1.0.0
	 *
	 * @param string $type Type of path where prefix is prepend.
	 * @return string Prefix for relative path type.
	 */
	public static function get_relative_path_prefix( $type ) {
		$site_url    = static::get_root_url( 'site' );
		$content_url = static::get_root_url( 'content' );

		// If these URLs have default structures they are the same so there is no prefix.
		if ( $content_url === $site_url ) {
			return '';
		} elseif ( 'content' === $type ) {
			return '#CONTENT#';
		} else {
			return '#SITE#';
		}
	}

	/**
	 * Get root URL of WordPress directory or content directory.
	 *
	 * For most sites, this URL is always the same, but if site
	 * uses non-standard content directory this URL is different
	 * based on requested type.
	 *
	 * @since 1.0.0
	 *
	 * @staticvar string $site_url
	 * @staticvar string $content_url
	 *
	 * @param string $type Type of content that requests root URL.
	 * @return string Root URL.
	 */
	public static function get_root_url( $type = 'site' ) {
		// Setting default URLs should happen only once.
		static $site_url = null, $content_url = null;

		if ( empty( $site_url ) ) {
			$site_url = site_url();
		}

		if ( empty( $content_url ) ) {
			$content_url = content_url();
		}

		// If these URLs have default structures root URL is always the same.
		if ( $content_url === $site_url . '/wp-content' ) {
			return $site_url;
		} elseif ( 'content' === $type ) {
			return $content_url;
		} else {
			return $site_url;
		}
	}

	/**
	 * Get array of default WordPress themes.
	 *
	 * Instances of \WP_Theme have protected property $default_themes.
	 * This method uses Reflection to read and return that property.
	 *
	 * @since 1.0.0
	 *
	 * @param \WP_Theme $theme Instance of \WP_Theme.
	 * @return array Array of default WordPress themes.
	 */
	public static function get_default_themes( $theme ) {
		// Get default themes property of WP_Theme instance.
		$default_themes = ( new ReflectionClass( get_class( $theme ) ) )->getProperty( 'default_themes' );

		// Set that property as accessible to be able to read it.
		$default_themes->setAccessible( true );

		// Get property value by passing \WP_Theme instance again.
		return $default_themes->getValue( $theme );
	}

	/**
	 * Get array of default directories.
	 *
	 * @since 1.0.0
	 *
	 * @return array Array of default directories.
	 */
	public static function get_default_dirs() {
		// Always include default admin and include directories.
		$defaults = [ '/wp-admin/', '/wp-includes/' ];

		// Include directories set by dependencies classes if parent directory not already included.
		return array_unique( array_merge( $defaults, (array) wp_scripts()->default_dirs, (array) wp_styles()->default_dirs ) );
	}

	/**
	 * Whether a path starts with one of default directories.
	 *
	 * @since 1.0.0
	 *
	 * @param string $path The path to check relative to root directory.
	 * @return bool True if it is, false if it is not.
	 */
	public static function is_in_default_dir( $path ) {
		foreach ( static::get_default_dirs() as $test ) {
			if ( 0 === strpos( $path, $test ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Replace first occurrence of the search string with the replacement string.
	 *
	 * @since 1.0.0
	 *
	 * @link https://stackoverflow.com/a/2606638
	 *
	 * @param string $search  The value being searched for, otherwise known as the needle.
	 * @param string $replace The replacement value that replaces found $search values.
	 * @param string $subject The string being searched and replaced on,
	 *                        otherwise known as the haystack.
	 * @return string $subject A string with the replaced value.
	 */
	public static function str_replace_once( $search, $replace, $subject ) {
		$pos = strpos( $subject, $search );

		if ( false !== $pos ) {
			return substr_replace( $subject, $replace, $pos, strlen( $search ) );
		}

		return $subject;
	}

	/**
	 * Get name of callback for manipulating store data.
	 *
	 * @since 1.0.0
	 *
	 * @param string $base Type of manipulation.
	 * @return string The callback to be run for manipulating store data.
	 */
	public static function get_store_callback( $base ) {
		$function  = is_multisite() ? $base . '_site' : $base;
		$function .= '_option';

		/**
		 * Filter name of callback for manipulating store data.
		 *
		 * @since 1.0.0
		 *
		 * @param string $function The callback to be run for manipulating store data.
		 * @param string $base     Type of manipulation.
		 */
		return apply_filters( 'commonwp_store_callback', $function, $base );
	}

	/**
	 * Get name of \WP_Temporary method for manipulating temporary data.
	 *
	 * @since 1.0.0
	 *
	 * @param string $base Type of manipulation.
	 * @return string The \WP_Temporary method to be run for manipulating temporary data.
	 */
	public static function get_temporaries_method( $base ) {
		$method = is_multisite() ? $base . '_site' : $base;

		/**
		 * Filter name of \WP_Temporary method for manipulating temporary data.
		 *
		 * @since 1.0.0
		 *
		 * @param string $method The \WP_Temporary method to be run for manipulating temporary data.
		 * @param string $base   Type of manipulation.
		 */
		return apply_filters( 'commonwp_temporaries_method', $method, $base );
	}

	/**
	 * Generate and return subresource integrity hash.
	 *
	 * @since 1.0.0
	 *
	 * @link https://github.com/Elhebert/laravel-sri/blob/03640cb670d3af1908af91c6c87b46a29ca3e37f/src/Sri.php#L22
	 *
	 * @param string $content Content that should be hashed.
	 * @return string Subresource integrity hash.
	 */
	public static function get_subresource_integrity( $content ) {
		return 'sha384-' . base64_encode( hash( 'sha384', $content, true ) );
	}

	/**
	 * Get body of URL.
	 *
	 * @since 1.0.0
	 *
	 * @throws Exception If retrieving content isn't successful.
	 *
	 * @param string $url URL to get content for.
	 * @return string Content of URL.
	 */
	public static function get_remote_content( $url ) {
		$request = wp_safe_remote_get( $url, [
			'timeout'    => MINUTE_IN_SECONDS / 2,
			'user-agent' => 'WordPress/' . get_bloginfo( 'version' ) . ' commonWP/' . COMMONWP_VERSION,
		] );

		if ( is_wp_error( $request ) ) {
			throw new Exception( 'Something wrong happened during request.' );
		}

		if ( 200 != wp_remote_retrieve_response_code( $request ) ) { // phpcs:ignore WordPress.PHP.StrictComparisons.LooseComparison
			throw new Exception( 'Requested remote URL does not exist.' );
		}

		return wp_remote_retrieve_body( $request );
	}
}
