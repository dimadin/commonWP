<?php
/**
 * Tests: \dimadin\WP\Plugin\commonWP\Tests\UtilsCustomPathTest class
 *
 * @package commonWP
 * @subpackage Tests
 * @since 1.0.0
 */

namespace dimadin\WP\Plugin\commonWP\Tests;

use dimadin\WP\Plugin\commonWP\Utils;
use WP_UnitTestCase;

/**
 * Class with tests for \dimadin\WP\Plugin\commonWP\Utils class.
 *
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 *
 * @since 1.0.0
 */
class UtilsCustomPathTest extends WP_UnitTestCase {
	public static function filter_content_url( $url ) {
		return str_replace( 'wp-content', 'content', $url );
	}

	public static function wpSetUpBeforeClass() {
		add_filter( 'content_url', [ __NAMESPACE__ . '\UtilsCustomPathTest', 'filter_content_url' ] );
	}

	public static function wpTearDownAfterClass() {
		remove_filter( 'content_url', [ __NAMESPACE__ . '\UtilsCustomPathTest', 'filter_content_url' ] );
	}

	public function test_get_root_url() {
		$this->assertNotEquals( Utils::get_root_url( 'site' ), Utils::get_root_url( 'content' ) );
	}

	public function test_get_relative_path_prefix() {
		$this->assertEquals( Utils::get_relative_path_prefix( 'site' ), '#SITE#' );
		$this->assertEquals( Utils::get_relative_path_prefix( 'content' ), '#CONTENT#' );
	}
}
