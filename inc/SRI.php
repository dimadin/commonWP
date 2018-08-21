<?php
/**
 * \dimadin\WP\Plugin\commonWP\SRI class.
 *
 * @package commonWP
 * @since 1.0.0
 */

namespace dimadin\WP\Plugin\commonWP;

use dimadin\WP\Plugin\commonWP\Paths;
use dimadin\WP\Plugin\commonWP\Singleton;

/**
 * Class for adding subresource integrities.
 *
 * @since 1.0.0
 */
class SRI {
	use Singleton;

	/**
	 * Array of subresource integrity hashes for script handles.
	 *
	 * @since 1.0.0
	 * @var array
	 */
	public static $script = [];

	/**
	 * Array of subresource integrity hashes for style handles.
	 *
	 * @since 1.0.0
	 * @var array
	 */
	public static $style = [];

	/**
	 * Append subresource integrity hash for requested handle of dependency type.
	 *
	 * @since 1.0.0
	 *
	 * @param string $type          Type of dependency.
	 * @param string $handle        Handle by which dependency is registered in WordPress.
	 * @param string $relative_path Path relative from WordPress installation.
	 */
	public static function add( $type, $handle, $relative_path ) {
		$paths = Paths::get_instance();

		if ( array_key_exists( 'sri', $paths->active[ $relative_path ] ) ) {
			static::${$type}[ $handle ] = $paths->active[ $relative_path ]['sri'];
		}
	}

	/**
	 * Add subresource integrity attributes to HTML tag for requested script handle.
	 *
	 * @since 1.0.0
	 *
	 * @param string $html HTML code of script tag.
	 * @param string $handle Handle by which dependency is registered in WordPress.
	 * @param string $src  Final URL of dependency.
	 * @return string HTML code of script tag with added subresource integrity attributes.
	 */
	public static function script( $html, $handle, $src ) {
		return static::dependency( $html, $handle, $src, 'script' );
	}

	/**
	 * Add subresource integrity attributes to HTML tag for requested style handle.
	 *
	 * @since 1.0.0
	 *
	 * @param string $html   HTML code of style tag.
	 * @param string $handle Handle by which dependency is registered in WordPress.
	 * @param string $src    Final URL of dependency.
	 * @return string HTML code of style tag with added subresource integrity attributes.
	 */
	public static function style( $html, $handle, $src ) {
		return static::dependency( $html, $handle, $src, 'style' );
	}

	/**
	 * Add subresource integrity attributes to HTML tag for requested handle and dependency type.
	 *
	 * @since 1.0.0
	 *
	 * @param string $html   HTML code of tag.
	 * @param string $handle Handle by which dependency is registered in WordPress.
	 * @param string $src    Final URL of dependency.
	 * @param string $type   Type of dependency.
	 * @return string HTML code of tag with added subresource integrity attributes.
	 */
	public static function dependency( $html, $handle, $src, $type ) {
		if ( array_key_exists( $handle, static::$$type ) ) {
			$hash = static::${$type}[ $handle ];
			$attr = ( 'style' === $type ) ? 'href' : 'src';
			$html = str_replace( "$attr='$src'", "$attr='$src' integrity='$hash' crossorigin='anonymous'", $html );
		}

		return $html;
	}
}
