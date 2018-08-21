<?php
/**
 * Tests: \dimadin\WP\Plugin\commonWP\Tests\SRITest class
 *
 * @package commonWP
 * @subpackage Tests
 * @since 1.0.0
 */

namespace dimadin\WP\Plugin\commonWP\Tests;

use dimadin\WP\Plugin\commonWP\Paths;
use dimadin\WP\Plugin\commonWP\SRI;
use WP_UnitTestCase;

/**
 * Class with tests for \dimadin\WP\Plugin\commonWP\SRI class.
 *
 * @since 1.0.0
 */
class SRITest extends WP_UnitTestCase {
	public function test_replacing_for_scripts() {
		$hash = 'sha384-nvAa0+6Qg9clwYCGGPpDQLVpLNn0fRaROjHqs13t4Ggj3Ez50XnGQqc/r8MhnRDZ';
		$src  = 'https://example.com/wp-includes/js/jquery/jquery.js?ver=1.12.4';
		$html = "<script type='text/javascript' src='$src'></script>"; // phpcs:ignore WordPress.WP.EnqueuedResources.NonEnqueuedScript

		SRI::$script['jquery-core'] = $hash;

		$new_html = SRI::dependency( $html, 'jquery-core', $src, 'script' );
		$this->assertEquals( $new_html, "<script type='text/javascript' src='$src' integrity='$hash' crossorigin='anonymous'></script>" ); // phpcs:ignore WordPress.WP.EnqueuedResources.NonEnqueuedScript
	}

	public function test_replacing_for_styles() {
		$hash = 'sha384-/f14xopkdZNw7gKm0CgltqTbBzHRM0yF2qAHZ1ShN0YR1TX7wIYo3mc9lZLLHHea';
		$src  = 'https://cdn.jsdelivr.net/gh/samikeijonen/sanse@1.2.3/style.min.css';
		$html = "<link rel='stylesheet' id='sanse-parent-style-css' href='$src' type='text/css' media='all' />"; // phpcs:ignore WordPress.WP.EnqueuedResources.NonEnqueuedStylesheet

		SRI::$style['sanse-parent-style'] = $hash;

		$new_html = SRI::dependency( $html, 'sanse-parent-style', $src, 'style' );
		$this->assertEquals( $new_html, "<link rel='stylesheet' id='sanse-parent-style-css' href='$src' integrity='$hash' crossorigin='anonymous' type='text/css' media='all' />" ); // phpcs:ignore WordPress.WP.EnqueuedResources.NonEnqueuedStylesheet
	}

	public function test_adding_and_replacing() {
		$paths         = Paths::get_instance();
		$hash          = 'sha384-uQPAXWjjvZGmVjKnobPRQOCEJ0rkCNRXW1GBUsJkjw1w0K7TxLH6Z3zMX7wtx+Lf';
		$relative_path = '/wp-includes/js/jquery/jquery-migrate.min.js?ver=1.4.1';
		$src           = 'https://example.com/wp-includes/js/jquery/jquery-migrate.min.js?ver=1.4.1';
		$handle        = 'jquery-migrate';
		$html          = "<script type='text/javascript' src='$src'></script>"; // phpcs:ignore WordPress.WP.EnqueuedResources.NonEnqueuedScript

		$paths->active[ $relative_path ] = [
			'path' => '/npm/jquery-migrate@1.4.1/dist/jquery-migrate.min.js',
			'ttl'  => time() + WEEK_IN_SECONDS,
			'sri'  => $hash,
		];

		SRI::add( 'script', $handle, $relative_path );

		$this->assertEquals( SRI::dependency( $html, $handle, $src, 'script' ), "<script type='text/javascript' src='$src' integrity='$hash' crossorigin='anonymous'></script>" ); // phpcs:ignore WordPress.WP.EnqueuedResources.NonEnqueuedScript
	}
}
