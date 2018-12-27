<?php
/**
 * \dimadin\WP\Plugin\commonWP\NPM class.
 *
 * @package commonWP
 * @since 1.0.0
 */

namespace dimadin\WP\Plugin\commonWP;

use Exception;

/**
 * Class with that registers NPM packages..
 *
 * @since 1.0.0
 */
class NPM {
	/**
	 * Define scripts and their data available for replacement.
	 *
	 * @since 1.0.0
	 */
	public static function get_scripts() {
		// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound, WordPress.Arrays.MultipleStatementAlignment.DoubleArrowNotAligned

		/*
		 * This is an array of items in form of:
		 * ```
		 * 'handle' = [
		 *     'package'  => 'package-name',
		 *     'file'     => 'path/to/file/in/package/without/extension',
		 *     'minified' => 'suffix-for-minified-version',
		 * ]
		 * ```
		 * where:
		 *  - 'handle' should be replaced with the name used for handle
		 *    while registering script in WordPress
		 *  - 'package' value is the name of corresponding NPM package
		 *  - 'file' value is the name of path of corresponding file in NPM package
		 *    without extension (.js is automatically added) or suffix
		 *    for minified version of file if such exist (for example,
		 *    for Bootstrap file it would be 'dist/js/bootstrap')
		 *  - 'minified' value is suffix for minified version of file
		 *    if such exist so that it can be added to 'file' value (for
		 *    the same Bootstrap example, it would be '.min'); if minified
		 *    file doesn't exist, this should be an empty value
		 *
		 * Sometimes all four of values are the same as in
		 * WordPress, but some use different names.
		 */
		$scripts = [
			'jquery-core'              => [
				'package'  => 'jquery',
				'file'     => 'dist/jquery',
				'minified' => '.min',
			],
			'jquery-migrate'           => [
				'package'  => 'jquery-migrate',
				'file'     => 'dist/jquery-migrate',
				'minified' => '.min',
			],
			'underscore'               => [
				'package'  => 'underscore',
				'file'     => 'underscore',
				'minified' => '-min',
			],
			'backbone'                 => [
				'package'  => 'backbone',
				'file'     => 'backbone',
				'minified' => '-min',
			],
			'react'                    => [
				'package'  => 'react',
				'file'     => 'umd/react.production.min',
				'minified' => '',
			],
			'react-dom'                => [
				'package'  => 'react-dom',
				'file'     => 'umd/react-dom.production.min',
				'minified' => '',
			],
			'moment'                   => [
				'package'  => 'moment',
				'file'     => 'min/moment.min',
				'minified' => '',
			],
			'lodash'                   => [
				'package'  => 'lodash',
				'file'     => 'lodash',
				'minified' => '.min',
			],
			'wp-polyfill'              => [
				'package'  => '@babel/polyfill',
				'file'     => 'dist/polyfill',
				'minified' => '.min',
			],
			'wp-polyfill-formdata'     => [
				'package'  => 'formdata-polyfill',
				'file'     => 'formdata.min',
				'minified' => '',
			],
			'plupload'                 => [
				'package'  => 'plupload',
				'file'     => 'js/plupload.full.min',
				'minified' => '',
			],
			'mediaelement-core'        => [
				'package'  => 'mediaelement',
				'file'     => 'build/mediaelement-and-player',
				'minified' => '.min',
			],
			'mediaelement-vimeo'       => [
				'package'  => 'mediaelement',
				'file'     => 'build/renderers/vimeo',
				'minified' => '.min',
			],
			'twentysixteen-html5'      => [
				'package'  => 'html5shiv',
				'file'     => 'dist/html5shiv',
				'minified' => '.min',
			],
			'jquery-scrollto'          => [
				'package'  => 'jquery.scrollto',
				'file'     => 'jquery.scrollTo',
				'minified' => '.min',
			],
		];

		/**
		 * Filter scripts and their data available for replacement.
		 *
		 * @since 1.0.0
		 *
		 * @param array $scripts Scripts and their data available for replacement.
		 */
		return apply_filters( 'npm_packages_scripts', $scripts );

		// phpcs:enable
	}

	/**
	 * Define styles and their data available for replacement.
	 *
	 * @since 1.0.0
	 */
	public static function get_styles() {
		// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound, WordPress.Arrays.MultipleStatementAlignment.DoubleArrowNotAligned

		/*
		 * This is an array of items in form of:
		 * ```
		 * 'handle' = [
		 *     'package'  => 'package-name',
		 *     'file'     => 'path/to/file/in/package/without/extension',
		 *     'minified' => 'suffix-for-minified-version',
		 * ]
		 * ```
		 * where:
		 *  - 'handle' should be replaced with the name used for handle
		 *    while registering style in WordPress
		 *  - 'package' value is the name of corresponding NPM package
		 *  - 'file' value is the name of path of corresponding file in NPM package
		 *    without extension (.css is automatically added) or suffix
		 *    for minified version of file if such exist (for example,
		 *    for Bootstrap file it would be 'dist/css/bootstrap')
		 *  - 'minified' value is suffix for minified version of file
		 *    if such exist so that it can be added to 'file' value (for
		 *    the same Bootstrap example, it would be '.min'); if minified
		 *    file doesn't exist, this should be an empty value
		 *
		 * Sometimes all four of values are the same as in
		 * WordPress, but some use different names.
		 */
		$styles = [
			'mediaelement'             => [
				'package'  => 'mediaelement',
				'file'     => 'build/mediaelementplayer-legacy',
				'minified' => '.min',
			],
		];

		/**
		 * Filter styles and their data available for replacement.
		 *
		 * @since 1.0.0
		 *
		 * @param array $styles Styles and their data available for replacement.
		 */
		return apply_filters( 'npm_packages_styles', $styles );

		// phpcs:enable
	}

	/**
	 * Get NPM data for passed registered handle of type.
	 *
	 * @since 1.0.0
	 *
	 * @throws Exception If there is no NPM data for passed values.
	 *
	 * @param string $handle Handle by which dependency is registered in WordPress.
	 * @param string $type   Type of dependency.
	 * @return bool True if NPM data exists.
	 */
	public static function get_data( $handle, $type ) {
		if ( 'style' === $type ) {
			$method = 'get_styles';
		} else {
			$method = 'get_scripts';
		}

		if ( array_key_exists( $handle, static::$method() ) ) {
			return static::$method()[ $handle ];
		} else {
			throw new Exception( 'Dependency handle has no NPM data.' );
		}
	}
}
