<?php
/**
 * Tests: \dimadin\WP\Plugin\commonWP\Tests\RewriteTest class
 *
 * @package commonWP
 * @subpackage Tests
 * @since 1.0.0
 */

namespace dimadin\WP\Plugin\commonWP\Tests;

use dimadin\WP\Plugin\commonWP\Paths;
use dimadin\WP\Plugin\commonWP\Rewrite;
use WP_UnitTestCase;

/**
 * Class with tests for \dimadin\WP\Plugin\commonWP\Rewrite class.
 *
 * @since 1.0.0
 */
class RewriteTest extends WP_UnitTestCase {
	public function test_get_relative_path() {
		$this->assertEquals( '/wp-includes/js/admin-bar.js', Rewrite::get_relative_path( site_url( '/wp-includes/js/admin-bar.js' ) ) );
		$this->assertEquals( '/some/custom/path.file', Rewrite::get_relative_path( site_url( '/some/custom/path.file' ) ) );

		$this->expectException( 'Exception' );
		Rewrite::get_relative_path( 'http://custom.tld/path/to/file' );
	}
}
