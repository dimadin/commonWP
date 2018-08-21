<?php
/**
 * Tests: \dimadin\WP\Plugin\commonWP\Tests\VersionsTest class
 *
 * @package commonWP
 * @subpackage Tests
 * @since 1.0.0
 */

namespace dimadin\WP\Plugin\commonWP\Tests;

use dimadin\WP\Plugin\commonWP\Versions;
use WP_UnitTestCase;

/**
 * Class with tests for \dimadin\WP\Plugin\commonWP\Versions class.
 *
 * @since 1.0.0
 */
class VersionsTest extends WP_UnitTestCase {
	public function test_get_twemoji_version_from_svg_url() {
		$this->assertEquals( Versions::get_twemoji_version_from_svg_url( 'https://s.w.org/images/core/emoji/2.4/svg/' ), '2.4' );
		$this->assertEquals( Versions::get_twemoji_version_from_svg_url( 'https://s.w.org/images/core/emoji/2.4.1/svg/' ), '2.4.1' );
		$this->assertEquals( Versions::get_twemoji_version_from_svg_url( 'http://some.tld/emoji/1/svg/' ), '1' );

		if ( ! method_exists( 'WP_UnitTestCase', 'expectException' ) ) {
			$this->markTestSkipped( 'Skipped because older version PHPUnit is used.' );
		}

		$this->expectException( 'Exception' );
		Versions::get_twemoji_version_from_svg_url( 'https://s.w.org/images/core/emoji/svg/' );
	}

	public function test_get_dependency_version_from_url() {
		$this->assertEquals( Versions::get_dependency_version_from_url( 'https://example.com/wp-includes/js/jquery/jquery.js?ver=1.12.4' ), '1.12.4' );
		$this->assertEquals( Versions::get_dependency_version_from_url( '//example.com/wp-includes/js/jquery/jquery.js?ver=1.12.4' ), '1.12.4' );
		$this->assertEquals( Versions::get_dependency_version_from_url( 'example.com/wp-includes/js/jquery/jquery.js?ver=1.12.4' ), '1.12.4' );
		$this->assertEquals( Versions::get_dependency_version_from_url( 'example.com/wp-includes/js/jquery/jquery.js?ver=1.12.4' ), '1.12.4' );
		$this->assertEquals( Versions::get_dependency_version_from_url( 'jquery.js?ver=1.12.4' ), '1.12.4' );
		$this->assertEmpty( Versions::get_dependency_version_from_url( 'https://example.com/wp-includes/js/jquery/jquery.js?v=1.12.4' ) );
	}

	public function test_get_wp_version_on_github() {
		$this->assertEquals( Versions::get_wp_version_on_github( '4.9' ), '4.9.0' );
		$this->assertEquals( Versions::get_wp_version_on_github( '4.9.1' ), '4.9.1' );
		$this->assertEquals( Versions::get_wp_version_on_github( '4.9.2-alpha-1' ), '4.9.2' );
	}

	public function test_get_previous_wp_version_in_branch() {
		$this->assertEquals( Versions::get_previous_wp_version_in_branch( '4.9.1' ), '4.9' );
		$this->assertEquals( Versions::get_previous_wp_version_in_branch( '4.9.2' ), '4.9.1' );
		$this->assertEquals( Versions::get_previous_wp_version_in_branch( '4.9.3-src' ), '4.9.2' );
		$this->assertEquals( Versions::get_previous_wp_version_in_branch( '4.9.4-alpha-1' ), '4.9.3' );

		if ( ! method_exists( 'WP_UnitTestCase', 'expectException' ) ) {
			$this->markTestSkipped( 'Skipped because older version PHPUnit is used.' );
		}

		$this->expectException( 'Exception' );
		Versions::get_previous_wp_version_in_branch( '4.9' );
	}

	public function test_get_wp_branch() {
		$this->assertEquals( Versions::get_wp_branch( '4.9.1' ), '4.9' );
		$this->assertEquals( Versions::get_wp_branch( '4.9' ), '4.9' );
		$this->assertEquals( Versions::get_wp_branch( '4.9.2-src' ), '4.9' );
		$this->assertEquals( Versions::get_wp_branch( '4.9.3-alpha-1' ), '4.9' );
	}

	public function test_get_stable_wp_version() {
		$this->assertEquals( Versions::get_stable_wp_version( '4.9.1' ), '4.9.1' );
		$this->assertEquals( Versions::get_stable_wp_version( '4.9' ), '4.9' );
		$this->assertEquals( Versions::get_stable_wp_version( '4.9.2-src' ), '4.9.2' );
		$this->assertEquals( Versions::get_stable_wp_version( '4.9.3-alpha-1' ), '4.9.3' );
	}
}
