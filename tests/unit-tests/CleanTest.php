<?php
/**
 * Tests: \dimadin\WP\Plugin\commonWP\Tests\CleanTest class
 *
 * @package commonWP
 * @subpackage Tests
 * @since 1.0.0
 */

namespace dimadin\WP\Plugin\commonWP\Tests;

use dimadin\WP\Plugin\commonWP\Clean;
use dimadin\WP\Plugin\commonWP\Paths;
use dimadin\WP\Plugin\commonWP\Process;
use dimadin\WP\Plugin\commonWP\Store;
use dimadin\WP\Plugin\commonWP\Queue;
use dimadin\WP\Plugin\commonWP\Utils;
use WP_UnitTestCase;

/**
 * Class with tests for \dimadin\WP\Plugin\commonWP\Clean class.
 *
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 *
 * @since 1.0.0
 */
class CleanTest extends WP_UnitTestCase {
	public static function init_store( $relative_path, $src, $handle ) {
		Store::delete();

		$stored_data = Utils::init_stored_data();

		$stored_data['queue'][ $relative_path ] = [
			'src'    => $src,
			'handle' => $handle,
			'type'   => 'script',
			'ttl'    => time() - HOUR_IN_SECONDS,
		];

		Store::update( $stored_data );
	}

	public function test_clean_all() {
		$relative_path = '/wp-includes/js/admin-bar.js';
		$src           = 'https://example.com' . $relative_path;
		$handle        = 'admin-bar';

		static::init_store( $relative_path, $src, $handle );

		$this->assertArrayHasKey( $relative_path, Store::get()['queue'] );

		Clean::all();

		$this->assertFalse( Store::get() );
	}

	public function test_clean_expired() {
		$relative_path = '/wp-includes/js/admin-bar.js';
		$src           = 'https://example.com' . $relative_path;
		$handle        = 'admin-bar';

		static::init_store( $relative_path, $src, $handle );

		$this->assertArrayHasKey( $relative_path, Store::get()['queue'] );

		Clean::expired();

		$this->assertFalse( array_key_exists( $relative_path, Store::get()['queue'] ) );
	}

	public function test_clean_core_paths() {
		$relative_path = '/wp-includes/js/admin-bar.js';
		$src           = 'https://example.com' . $relative_path;
		$handle        = 'admin-bar';

		static::init_store( $relative_path, $src, $handle );

		$this->assertArrayHasKey( $relative_path, Store::get()['queue'] );

		Clean::core_paths();

		$this->assertFalse( array_key_exists( $relative_path, Store::get()['queue'] ) );
	}

	public function test_clean_plugin_paths() {
		$relative_path = '/wp-content/plugins/akismet/_inc/form.js';
		$src           = 'https://example.com' . $relative_path;
		$handle        = 'akismet-form';

		static::init_store( $relative_path, $src, $handle );

		$this->assertArrayHasKey( $relative_path, Store::get()['queue'] );

		Clean::plugin_paths( [ 'akismet/akismet.php' ] );

		$this->assertFalse( array_key_exists( $relative_path, Store::get()['queue'] ) );
	}

	public function test_clean_theme_paths() {
		$relative_path = '/wp-content/themes/twentyseventeen/style.css';
		$src           = 'https://example.com' . $relative_path;
		$handle        = 'twentyseventeen-style';

		static::init_store( $relative_path, $src, $handle );

		$this->assertArrayHasKey( $relative_path, Store::get()['queue'] );

		Clean::theme_paths( [ 'twentyseventeen' ] );

		$this->assertFalse( array_key_exists( $relative_path, Store::get()['queue'] ) );
	}

	public function test_clean_starting_with() {
		$relative_path = '/wp-includes/js/admin-bar.js';
		$src           = 'https://example.com' . $relative_path;
		$handle        = 'admin-bar';

		static::init_store( $relative_path, $src, $handle );

		$this->assertArrayHasKey( $relative_path, Store::get()['queue'] );

		Clean::starting_with( [ '/wp' ] );

		$this->assertFalse( array_key_exists( $relative_path, Store::get()['queue'] ) );
	}
}
