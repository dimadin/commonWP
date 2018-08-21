<?php
/**
 * Tests: \dimadin\WP\Plugin\commonWP\Tests\UtilsTest class
 *
 * @package commonWP
 * @subpackage Tests
 * @since 1.0.0
 */

namespace dimadin\WP\Plugin\commonWP\Tests;

use dimadin\WP\Plugin\commonWP\Utils;
use dimadin\WP\Plugin\commonWP\Versions;
use WP_Theme;
use WP_UnitTestCase;

/**
 * Class with tests for \dimadin\WP\Plugin\commonWP\Utils class.
 *
 * @since 1.0.0
 */
class UtilsTest extends WP_UnitTestCase {
	/**
	 * Test that hooking spawner listener works.
	 *
	 * @since 1.0.0
	 */
	public function test_get_dir_from_path() {
		$this->assertEquals( Utils::get_dir_from_path( '/first/dir' ), 'first' );
		$this->assertEquals( Utils::get_dir_from_path( 'second/dir' ), 'second' );
		$this->assertEquals( Utils::get_dir_from_path( '/third/dir/' ), 'third' );
		$this->assertEquals( Utils::get_dir_from_path( '/fourth/dir/has.file' ), 'fourth' );
	}

	public function test_get_plugin_from_slug() {
		$plugin = Utils::get_plugin_from_slug( 'akismet' );
		$this->assertEquals( $plugin['file'], 'akismet/akismet.php' );

		$this->expectException( 'Exception' );
		Utils::get_plugin_from_slug( 'non-existing' );
	}

	public function test_get_theme_from_slug() {
		$theme = Utils::get_theme_from_slug( 'twentyseventeen' );
		$this->assertEquals( $theme['data']->get( 'Name' ), 'Twenty Seventeen' );

		$this->expectException( 'Exception' );
		Utils::get_theme_from_slug( 'non-existing' );
	}

	public function test_sanitize_github_repository_name() {
		$this->assertEquals( Utils::sanitize_github_repository_name( 'https://github.com/dimadin/commonwp/' ), 'dimadin/commonwp' );
		$this->assertEquals( Utils::sanitize_github_repository_name( 'https://github.com/dimadin/commonwp' ), 'dimadin/commonwp' );
		$this->assertEquals( Utils::sanitize_github_repository_name( '/dimadin/commonwp' ), 'dimadin/commonwp' );
		$this->assertEquals( Utils::sanitize_github_repository_name( '/dimadin/commonwp/' ), 'dimadin/commonwp' );
		$this->assertEquals( Utils::sanitize_github_repository_name( 'dimadin/commonwp' ), 'dimadin/commonwp' );
	}

	public function test_sanitize_relative_path() {
		$this->assertEquals( Utils::sanitize_relative_path( '/wp-includes/js/jquery/jquery.js?ver=1.12.4' ), '/wp-includes/js/jquery/jquery.js?ver=1.12.4' );
		$this->assertEquals( Utils::sanitize_relative_path( '#SITE#/wp-includes/js/jquery/jquery.js?ver=1.12.4' ), '/wp-includes/js/jquery/jquery.js?ver=1.12.4' );
		$this->assertEquals( Utils::sanitize_relative_path( '#CONTENT#/wp-includes/js/jquery/jquery.js?ver=1.12.4' ), '/wp-includes/js/jquery/jquery.js?ver=1.12.4' );
	}

	public function test_get_relative_path_prefix() {
		$this->assertEmpty( Utils::get_relative_path_prefix( 'site' ) );
		$this->assertEmpty( Utils::get_relative_path_prefix( 'content' ) );
	}

	public function test_get_root_url() {
		$this->assertEquals( Utils::get_root_url( 'site' ), Utils::get_root_url( 'content' ) );
	}

	public function test_get_default_themes() {
		$themes = Utils::get_default_themes( WP_Theme::get_core_default_theme() );

		$this->assertArrayHasKey( 'classic', $themes );
		$this->assertArrayHasKey( 'default', $themes );
		$this->assertArrayHasKey( 'twentyten', $themes );
		$this->assertArrayHasKey( 'twentysixteen', $themes );
	}

	public function test_is_in_default_dir() {
		$this->assertTrue( Utils::is_in_default_dir( '/wp-includes/js/jquery/jquery.js?ver=1.12.4' ) );
		$this->assertTrue( Utils::is_in_default_dir( '/wp-admin/css/login.min.css' ) );
		$this->assertFalse( Utils::is_in_default_dir( '/wp-content/themes/twentyfifteen/style.css' ) );
	}

	public function test_str_replace_once() {
		$this->assertEquals( Utils::str_replace_once( 'a', 'b', 'aabbcc' ), 'babbcc' );
		$this->assertEquals( Utils::str_replace_once( 'c', 'd', 'ddeeff' ), 'ddeeff' );
	}
}
