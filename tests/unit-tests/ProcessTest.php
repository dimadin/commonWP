<?php
/**
 * Tests: \dimadin\WP\Plugin\commonWP\Tests\ProcessTest class
 *
 * @package commonWP
 * @subpackage Tests
 * @since 1.0.0
 */

namespace dimadin\WP\Plugin\commonWP\Tests;

use dimadin\WP\Plugin\commonWP\Paths;
use dimadin\WP\Plugin\commonWP\Process;
use dimadin\WP\Plugin\commonWP\Rewrite;
use dimadin\WP\Plugin\commonWP\Store;
use dimadin\WP\Plugin\commonWP\Queue;
use dimadin\WP\Plugin\commonWP\Versions;
use WP_UnitTestCase;

/**
 * Class with tests for \dimadin\WP\Plugin\commonWP\Process class.
 *
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 *
 * @since 1.0.0
 */
class ProcessTest extends WP_UnitTestCase {
	public static $src;
	public static $relative_path;

	public static function filter_content_url( $pre, $args, $url ) {
		if ( static::$src !== $url ) {
			return $pre;
		}

		return [
			'body'     => file_get_contents( ABSPATH . WPINC . '/js/comment-reply.min.js' ),
			'response' => [
				'code' => 200,
			],
		];
	}

	public static function wpSetUpBeforeClass() {
		static::$relative_path = '/wp-includes/js/comment-reply.min.js?ver=' . $GLOBALS['wp_version'];
		static::$src           = site_url( static::$relative_path );

		Store::delete();

		add_filter( 'pre_http_request', [ __NAMESPACE__ . '\ProcessTest', 'filter_content_url' ], 10, 3 );
	}

	public static function wpTearDownAfterClass() {
		remove_filter( 'pre_http_request', [ __NAMESPACE__ . '\ProcessTest', 'filter_content_url' ], 10, 3 );
	}

	public function test_process() {
		Rewrite::script( static::$src, 'comment-reply' );

		Queue::save();
		Queue::process();

		$paths = Paths::get_instance();
		$paths->reset();
		$paths->setup();

		$path = $paths->get_active( static::$relative_path );
		$this->assertEquals( '/gh/wordpress/wordpress@' . Versions::get_wp_version_on_github( $GLOBALS['wp_version'] ) . '/wp-includes/js/comment-reply.min.js', $path );
	}
}
