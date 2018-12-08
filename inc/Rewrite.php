<?php
/**
 * \dimadin\WP\Plugin\commonWP\Rewrite class.
 *
 * @package commonWP
 * @since 1.0.0
 */

namespace dimadin\WP\Plugin\commonWP;

use dimadin\WP\Plugin\commonWP\Paths;
use dimadin\WP\Plugin\commonWP\Queue;
use dimadin\WP\Plugin\commonWP\Utils;
use dimadin\WP\Plugin\commonWP\SRI;
use Exception;

/**
 * Class that changes local paths to remote one.
 *
 * @since 1.0.0
 */
class Rewrite {
	/**
	 * Rewrite script dependency URL.
	 *
	 * @since 1.0.0
	 *
	 * @param string $src    Final URL of dependency before rewrite.
	 * @param string $handle Handle by which dependency is registered in WordPress.
	 * @return string Rewritten URL of dependency.
	 */
	public static function script( $src, $handle ) {
		return static::dependency( $src, $handle, 'script' );
	}

	/**
	 * Rewrite style dependency URL.
	 *
	 * @since 1.0.0
	 *
	 * @param string $src    Final URL of dependency before rewrite.
	 * @param string $handle Handle by which dependency is registered in WordPress.
	 * @return string Rewritten URL of dependency.
	 */
	public static function style( $src, $handle ) {
		return static::dependency( $src, $handle, 'style' );
	}

	/**
	 * Rewrite URL of directory that holds SVG versions of emoji.
	 *
	 * @since 1.0.0
	 *
	 * @param string $src    Final URL of directory that holds SVG versions of emoji.
	 * @return string Rewritten URL of directory that holds SVG versions of emoji.
	 */
	public static function emoji_svg_url( $src ) {
		return static::get_src( $src, $src, 'emoji_svg_url', 'emoji_svg_url' );
	}

	/**
	 * Rewrite dependency URL.
	 *
	 * @since 1.0.0
	 *
	 * @param string $src    Final URL of dependency before rewrite.
	 * @param string $handle Handle by which dependency is registered in WordPress.
	 * @param string $type   Type of dependency.
	 * @return string $src Rewritten URL of dependency.
	 */
	public static function dependency( $src, $handle, $type ) {
		// Check that source is set.
		if ( ! $src ) {
			return $src;
		}

		// Check if source is on current site.
		try {
			$relative_path = static::get_relative_path( $src );
		} catch ( Exception $e ) {
			return $src;
		}

		// Get rewritten source.
		$src = static::get_src( $src, $relative_path, $handle, $type );

		return $src;
	}

	/**
	 * Rewrite URL.
	 *
	 * @since 1.0.0
	 *
	 * @param string $src           Final URL of dependency before rewrite.
	 * @param string $relative_path Path relative to the root.
	 * @param string $handle        Handle by which dependency is registered in WordPress.
	 * @param string $type          Type of dependency.
	 * @return string $src Rewritten URL of dependency.
	 */
	public static function get_src( $src, $relative_path, $handle, $type ) {
		/**
		 * Filter whether rewrite should occur for current dependency.
		 *
		 * Passing false will effectively short-circuit rewrite.
		 *
		 * @since 1.0.0
		 *
		 * @param bool   $to_rewrite    Whether to rewrite dependency. Any value other than
		 *                              true will short-circuit rewrite.
		 * @param string $relative_path Path relative to the root.
		 * @param string $src           Final URL of dependency before rewrite.
		 * @param string $handle        Handle by which dependency is registered in WordPress.
		 * @param string $type          Type of dependency.
		 */
		$should_rewrite = apply_filters( 'commonwp_should_rewrite', true, $relative_path, $src, $handle, $type );

		if ( false === $should_rewrite ) {
			return $src;
		}

		/**
		 * Filter to get src outside of store.
		 *
		 * Passing a truthy value to the filter will effectively short-circuit retrieval
		 * of src from store, returning the passed value instead.
		 *
		 * @since 1.0.0
		 *
		 * @param bool   $pre_rewrite   Value to return. Any value other than false
		 *                              will short-circuit the retrieval of the
		 *                              rewritten src.
		 * @param string $relative_path Path relative to the root.
		 * @param string $src           Final URL of dependency before rewrite.
		 * @param string $handle        Handle by which dependency is registered in WordPress.
		 * @param string $type          Type of dependency.
		 */
		$pre = apply_filters( 'commonwp_get_src', false, $relative_path, $src, $handle, $type );

		if ( false !== $pre ) {
			return $pre;
		}

		$paths = Paths::get_instance();

		// Check if it is active.
		$path = $paths->get_active( $relative_path );

		if ( $path ) {
			// Add subresource integrity hash to the current request memory.
			SRI::add( $type, $handle, $relative_path );

			return 'https://cdn.jsdelivr.net' . $path;
		}

		// If not, check if it is inactive.
		if ( ! $paths->is_inactive( $relative_path ) ) {
			// If not inactive, add it to the queue to process.
			Queue::add( $relative_path, $src, $handle, $type );
		}

		return $src;
	}

	/**
	 * Get path relative to the root URL.
	 *
	 * For most sites, path doesn't have custom prefix,
	 * but if site uses non-standard content directory
	 * it adds one.
	 *
	 * @since 1.0.0
	 *
	 * @throws Exception If source is not from current site.
	 *
	 * @param string $src Final URL of dependency before rewrite.
	 * @return string $path Path relative to the root.
	 */
	public static function get_relative_path( $src ) {
		$site_url    = Utils::get_root_url( 'site' );
		$content_url = Utils::get_root_url( 'content' );

		// If these URLs have default structures they are the same.
		if ( $content_url === $site_url ) {
			$path = str_replace( $site_url, '', $src );
			// If requested URL is in content directory.
		} elseif ( 0 === strpos( $src, $content_url ) ) {
			$path = '#CONTENT#' . str_replace( $content_url, '', $src );
			// If requested URL is in core directory.
		} elseif ( 0 === strpos( $src, $site_url ) ) {
			$path = '#SITE#' . str_replace( $site_url, '', $src );
		}

		if ( ! isset( $path ) || $path === $src ) {
			throw new Exception( 'URL is not from this site.' );
		}

		return $path;
	}
}
