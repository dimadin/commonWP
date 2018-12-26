<?php
/**
 * \dimadin\WP\Plugin\commonWP\Versions class.
 *
 * @package commonWP
 * @since 1.0.0
 */

namespace dimadin\WP\Plugin\commonWP;

use Exception;
use WP_Temporary;
use dimadin\WP\Plugin\commonWP\Utils;

/**
 * Class with methods related to retrieving various versions.
 *
 * @since 1.0.0
 */
class Versions {
	/**
	 * Get Twemoji's version from URL.
	 *
	 * Given URL, try to extract version name using pattern
	 * used in URL for directory for SVG versions of emoji.
	 *
	 * @since 1.0.0
	 *
	 * @throws Exception If Twemoji version has not been found.
	 *
	 * @param string $url URL to be searched for.
	 * @return string Version of Twemoji.
	 */
	public static function get_twemoji_version_from_svg_url( $url ) {
		preg_match( '/emoji\/(.*?[0-9.])\/svg\//', $url, $matches );

		if ( ! $matches || ! array_key_exists( 1, $matches ) ) {
			throw new Exception( 'Could not find Twemoji version.' );
		}

		return $matches[1];
	}

	/**
	 * Get dependency's version from URL.
	 *
	 * Given URL, try to extract version name using pattern
	 * used in URLs for WordPress' dependencies.
	 *
	 * @since 1.0.0
	 *
	 * @param string $url URL to be searched for.
	 * @return string Version of dependency.
	 */
	public static function get_dependency_version_from_url( $url ) {
		parse_str( wp_parse_url( $url, PHP_URL_QUERY ), $args );

		if ( array_key_exists( 'ver', $args ) ) {
			return $args['ver'];
		} else {
			return '';
		}
	}

	/**
	 * Get stable version of WordPress version.
	 *
	 * WordPress version can be in development and have suffix.
	 * This method strips that suffix and always return stable version.
	 *
	 * @since 1.0.0
	 *
	 * @param string $version WordPress version to get stable version for.
	 * @return string $version Version's stable version.
	 */
	public static function get_stable_wp_version( $version ) {
		// Check if this version is already stable.
		if ( false !== strpos( $version, '-' ) ) {
			// Remove additional suffix to get stable version.
			$version = strstr( $version, '-', true );
		}

		return $version;
	}

	/**
	 * Get latest released WordPress version.
	 *
	 * @since 1.0.0
	 *
	 * @throws Exception If there is problem fetching latest releases
	 *                   from WordPress.org API.
	 *
	 * @return string $latest_version Latest released WordPress version.
	 */
	public static function get_latest_wp_version() {
		try {
			$updates = self::get_latest_wp_versions();

			$latest_version = 0;

			foreach ( $updates as $update ) {
				if ( is_array( $update ) && isset( $update['response'], $update['version'] ) && 'upgrade' === $update['response'] && version_compare( $update['version'], $latest_version, '>' ) ) {
					$latest_version = $update['version'];
				}
			}

			if ( 0 !== $latest_version ) {
				return $latest_version;
			} else {
				throw new Exception( 'Latest WordPress version cannot be found.' );
			}
		} catch ( Exception $e ) {
			throw new Exception( $e->getMessage() );
		}
	}

	/**
	 * Get latest released WordPress version in the same branch.
	 *
	 * @since 1.0.0
	 *
	 * @throws Exception If there is problem fetching latest releases
	 *                   from WordPress.org API.
	 *
	 * @param string $version WordPress version to get latest version
	 *                        in branch for.
	 * @return string $latest_version Latest released version in the same
	 *                                WordPress branch.
	 */
	public static function get_latest_wp_version_in_branch( $version ) {
		try {
			$updates = self::get_latest_wp_versions();

			$current_branch = self::get_wp_branch( $version );

			$latest_version = 0;

			foreach ( $updates as $update ) {
				if ( is_array( $update ) && isset( $update['version'] ) && 0 === strpos( $update['version'], $current_branch ) && version_compare( $update['version'], $latest_version, '>' ) ) {
					$latest_version = $update['version'];
				}
			}

			if ( 0 !== $latest_version ) {
				return $latest_version;
			} else {
				throw new Exception( 'Latest WordPress version in the same branch cannot be found.' );
			}
		} catch ( Exception $e ) {
			throw new Exception( $e->getMessage() );
		}
	}

	/**
	 * Get previous minor version in the same WordPress branch.
	 *
	 * @since 1.0.0
	 *
	 * @throws Exception If previous version is the same,
	 *                   or if isn't final version.
	 *
	 * @param string $version WordPress version to get previous version for.
	 * @return string $previous_version Previous minor version in the same
	 *                                  WordPress branch.
	 */
	public static function get_previous_wp_version_in_branch( $version ) {
		// Get stable version first.
		$version = self::get_stable_wp_version( $version );

		// Check if stable version has three parts.
		$parts = explode( '.', $version );

		if ( 3 === count( $parts ) && is_numeric( $parts[2] ) ) {
			$last_part = $parts[2] - 1;

			// For minor versions equal or greater than 1, use that.
			if ( $last_part > 0 ) {
				$parts[2] = $last_part;
			} elseif ( 0 === $last_part ) {
				// For major versions, don't use third part.
				unset( $parts[2] );
			}
		}

		$previous_version = implode( $parts, '.' );

		if ( $previous_version === $version ) {
			throw new Exception( 'There is no previous minor version in the same WP branch.' );
		}

		return $previous_version;
	}

	/**
	 * Get tag for current WordPress version on GitHub development repository.
	 *
	 * WordPress version does not follow semver. Major versions are
	 * in form X.Y while minor versions are in form X.Y.Z. However,
	 * tags on GitHub development repository use form X.Y.0 for major
	 * versions. This method tries to generate correct version of tag
	 * on GitHub development repository.
	 *
	 * @since 1.0.0
	 *
	 * @param string $version WordPress version to get GitHub tag for.
	 * @return string $version Version's tag on GitHub.
	 */
	public static function get_wp_version_on_github( $version ) {
		// Get stable version first.
		$version = self::get_stable_wp_version( $version );

		// If this is major version, make it look like semver.
		if ( 2 === count( explode( '.', $version ) ) ) {
			$version .= '.0';
		}

		/**
		 * Filter tag for current WordPress version on GitHub.
		 *
		 * @since 1.0.0
		 *
		 * @param string $version Current version's tag on GitHub.
		 */
		return apply_filters( 'commonwp_wp_core_version_on_github', $version );
	}

	/**
	 * Get branch for WordPress version.
	 *
	 * @since 1.0.0
	 *
	 * @param string $version WordPress version to get branch for.
	 * @return string $version Version's branch.
	 */
	public static function get_wp_branch( $version ) {
		return implode( '.', array_slice( preg_split( '/[.-]/', $version ), 0, 2 ) );
	}

	/**
	 * Get latest released plugin version.
	 *
	 * This method fetches WordPress.org or GitHub API to get
	 * latest version available of a plugin. Successful response
	 * is cached for half an hour.
	 *
	 * @since 1.0.0
	 *
	 * @throws Exception If there is problem fetching latest releases
	 *                   from WordPress.org or GitHub API.
	 *
	 * @param array $plugin {
	 *     Array of plugin's data.
	 *
	 *     @type string $file Basename path of the plugin's main file.
	 *     @type array  $data Array of plugin's data. See {@see get_plugins()}.
	 * }
	 * @return string $version Latest released plugin version.
	 */
	public static function get_latest_plugin_version( $plugin ) {
		$get_method = Utils::get_temporaries_method( 'get' );

		$updates = WP_Temporary::$get_method( 'commonwp_latest_plugins_versions' );

		// Check if there are cached updates.
		if ( false === $updates ) {
			$updates = [];
		}

		// Check if plugin has cached latest version.
		if ( array_key_exists( $plugin['file'], $updates ) ) {
			return $updates[ $plugin['file'] ];
		}

		// Retrieve latest version from API.
		try {
			// Check if plugin is hosted on GitHub.
			$repository = array_key_exists( 'GitHub Plugin URI', $plugin['data'] ) ? Utils::sanitize_github_repository_name( $plugin['data']['GitHub Plugin URI'] ) : '';

			if ( $repository ) {
				// Retrieve using GitHub API.
				$version = self::get_latest_version_on_github( $repository );
			} else {
				// Retrieve using WordPress.org API.
				$version = self::get_latest_version_on_wporg( Utils::get_dir_from_path( $plugin['file'] ), 'plugin' );
			}

			$updates[ $plugin['file'] ] = $version;

			// We want 'update' on purpose because it doesn't change expiration.
			$set_method = Utils::get_temporaries_method( 'update' );

			WP_Temporary::$set_method( 'commonwp_latest_plugins_versions', $updates, HOUR_IN_SECONDS / 2 );

			return $version;
		} catch ( Exception $e ) {
			throw new Exception( $e->getMessage() );
		}
	}

	/**
	 * Get latest released theme version.
	 *
	 * This method fetches WordPress.org or GitHub API to get
	 * latest version available of a theme. Successful response
	 * is cached for half an hour.
	 *
	 * @since 1.0.0
	 *
	 * @throws Exception If there is problem fetching latest releases
	 *                   from WordPress.org or GitHub API.
	 *
	 * @param array $theme {
	 *     Array of theme's data.
	 *
	 *     @type string    $slug Theme's slug.
	 *     @type \WP_Theme $data \WP_Theme instance of the theme.
	 * }
	 * @return string $version Latest released plugin version.
	 */
	public static function get_latest_theme_version( $theme ) {
		$get_method = Utils::get_temporaries_method( 'get' );

		$updates = WP_Temporary::$get_method( 'commonwp_latest_themes_versions' );

		// Check if there are cached updates.
		if ( false === $updates ) {
			$updates = [];
		}

		// Check if theme has cached latest version.
		if ( array_key_exists( $theme['slug'], $updates ) ) {
			return $updates[ $theme['slug'] ];
		}

		// Retrieve latest version from API.
		try {
			// Check if theme is hosted on GitHub.
			$repository = Utils::sanitize_github_repository_name( (string) $theme['data']->get( 'GitHub Theme URI' ) );

			if ( $repository ) {
				// Retrieve using GitHub API.
				$version = self::get_latest_version_on_github( $repository );
			} else {
				// Retrieve using WordPress.org API.
				$version = self::get_latest_version_on_wporg( $theme['slug'], 'theme' );
			}

			$updates[ $theme['slug'] ] = $version;

			// We want 'update' on purpose because it doesn't change expiration.
			$set_method = Utils::get_temporaries_method( 'update' );

			WP_Temporary::$set_method( 'commonwp_latest_themes_versions', $updates, HOUR_IN_SECONDS / 2 );

			return $version;
		} catch ( Exception $e ) {
			throw new Exception( $e->getMessage() );
		}
	}

	/**
	 * Get latest released WordPress versions.
	 *
	 * This method fetches WordPress.org API to get latest versions
	 * available in each WordPress branch. Successful response is
	 * cached for one hour.
	 *
	 * @since 1.0.0
	 *
	 * @throws Exception If there is problem fetching latest releases
	 *                   from WordPress.org API.
	 *
	 * @return array $updates Latest released WordPress version.
	 */
	public static function get_latest_wp_versions() {
		$get_method = Utils::get_temporaries_method( 'get' );

		$updates = WP_Temporary::$get_method( 'commonwp_latest_core_versions' );

		if ( false === $updates ) {
			try {
				$response = json_decode( trim( Utils::get_remote_content( 'https://api.wordpress.org/core/version-check/1.7/' ) ), true );

				if ( ! is_array( $response ) || ! array_key_exists( 'offers', $response ) ) {
					throw new Exception( 'Latest WordPress versions cannot be found.' );
				}

				$updates = $response['offers'];

				$set_method = Utils::get_temporaries_method( 'set' );

				WP_Temporary::$set_method( 'commonwp_latest_core_versions', $updates, HOUR_IN_SECONDS );
			} catch ( Exception $e ) {
				throw new Exception( $e->getMessage() );
			}
		}

		return $updates;
	}

	/**
	 * Get latest released version of GitHub's repository.
	 *
	 * This method fetches GitHub API to get latest
	 * version (tag) available of a repository.
	 *
	 * @since 1.0.0
	 *
	 * @throws Exception If there is problem fetching latest releases
	 *                   from GitHub API.
	 *
	 * @param string $repository GitHub repository in the form of user/repository.
	 * @return string $version Latest released version.
	 */
	public static function get_latest_version_on_github( $repository ) {
		try {
			$response = json_decode( trim( Utils::get_remote_content( 'https://api.github.com/repos/' . $repository . '/tags' ) ), true );

			if ( ! is_array( $response ) || ! array_key_exists( '0', $response ) || ! array_key_exists( 'name', $response[0] ) ) {
				throw new Exception( 'Latest version on GitHub cannot be found.' );
			}

			return $response[0]['name'];
		} catch ( Exception $e ) {
			throw new Exception( $e->getMessage() );
		}
	}

	/**
	 * Get latest released version on WordPress.org.
	 *
	 * This method fetches WordPress.org to get
	 * latest version available of a plugin or theme.
	 *
	 * @since 1.0.0
	 *
	 * @throws Exception If there is problem fetching latest releases
	 *                   from WordPress.org.
	 *
	 * @param string $slug Directory that holds main file.
	 * @param string $type Whether to get version of theme or plugin.
	 * @return string $version Latest released version on WordPress.org.
	 */
	public static function get_latest_version_on_wporg( $slug, $type ) {
		try {
			$response = json_decode( trim( Utils::get_remote_content( "https://api.wordpress.org/{$type}s/info/1.2/?action={$type}_information&request[slug]={$slug}" ) ), true );

			if ( ! is_array( $response ) || ! array_key_exists( 'version', $response ) ) {
				throw new Exception( 'Latest version on WordPress.org cannot be found.' );
			}

			return $response['version'];
		} catch ( Exception $e ) {
			throw new Exception( $e->getMessage() );
		}
	}
}
