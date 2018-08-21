<?php
/**
 * Tests: \dimadin\WP\Plugin\commonWP\Tests\RewriteCustomPathTest class
 *
 * @package commonWP
 * @subpackage Tests
 * @since 1.0.0
 */

namespace dimadin\WP\Plugin\commonWP\Tests;

use dimadin\WP\Plugin\commonWP\Rewrite;
use WP_UnitTestCase;

/**
 * Class with tests for \dimadin\WP\Plugin\commonWP\Rewrite class.
 *
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 *
 * @since 1.0.0
 */
class RewriteCustomPathTest extends WP_UnitTestCase {
	public static function filter_content_url( $url ) {
		return str_replace( 'wp-content', 'content', $url );
	}

	public static function wpSetUpBeforeClass() {
		add_filter( 'content_url', [ __NAMESPACE__ . '\RewriteCustomPathTest', 'filter_content_url' ] );
	}

	public static function wpTearDownAfterClass() {
		remove_filter( 'content_url', [ __NAMESPACE__ . '\RewriteCustomPathTest', 'filter_content_url' ] );
	}

	public function test_get_relative_path() {
		$this->assertEquals( '#SITE#/wp-includes/js/admin-bar.js', Rewrite::get_relative_path( site_url( '/wp-includes/js/admin-bar.js' ) ) );
		$this->assertEquals( '#CONTENT#/plugins/akismet/_inc/form.js', Rewrite::get_relative_path( content_url( '/plugins/akismet/_inc/form.js' ) ) );
	}
}
