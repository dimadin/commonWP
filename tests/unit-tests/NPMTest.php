<?php
/**
 * Tests: \dimadin\WP\Plugin\commonWP\Tests\NPMTest class
 *
 * @package commonWP
 * @subpackage Tests
 * @since 1.0.0
 */

namespace dimadin\WP\Plugin\commonWP\Tests;

use dimadin\WP\Plugin\commonWP\NPM;
use WP_UnitTestCase;

/**
 * Class with tests for \dimadin\WP\Plugin\commonWP\NPM class.
 *
 * @since 1.0.0
 */
class NPMTest extends WP_UnitTestCase {
	public function test_get_data_for_scripts() {
		$expect = [
			'package'  => 'jquery',
			'file'     => 'dist/jquery',
			'minified' => '.min',
		];
		$this->assertEquals( NPM::get_data( 'jquery-core', 'script' ), $expect );

		$this->expectException( 'Exception' );
		NPM::get_data( 'non-existing', 'script' );
	}

	public function test_get_data_for_custom_script() {
		$expect = [
			'package'  => 'bootstrap',
			'file'     => 'dist/js/bootstrap',
			'minified' => '.min',
		];

		add_filter( 'npm_packages_scripts', function( $scripts ) use ( $expect ) {
			$scripts['bootstrap'] = $expect;

			return $scripts;
		} );
		$this->assertEquals( NPM::get_data( 'bootstrap', 'script' ), $expect );
	}

	public function test_get_data_for_custom_style() {
		$expect = [
			'package'  => 'bootstrap',
			'file'     => 'dist/css/bootstrap',
			'minified' => '.min',
		];

		add_filter( 'npm_packages_styles', function( $scripts ) use ( $expect ) {
			$scripts['bootstrap'] = $expect;

			return $scripts;
		} );
		$this->assertEquals( NPM::get_data( 'bootstrap', 'style' ), $expect );
	}
}
