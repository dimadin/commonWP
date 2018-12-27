<?php
/**
 * \dimadin\WP\Plugin\commonWP\Main class.
 *
 * @package commonWP
 * @since 1.0.0
 */

namespace dimadin\WP\Plugin\commonWP;

use dimadin\WP\Plugin\commonWP\Singleton;
use WP_CLI;
use WP_Temporary;

/**
 * Class with methods that initialize commonWP.
 *
 * This class hooks other parts of commonWP, and
 * other methods that are important for functioning
 * of commonWP.
 *
 * @since 1.0.0
 */
class Main {
	use Singleton;

	/**
	 * Constructor.
	 *
	 * This method is used to hook everything.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		static::hook();
	}

	/**
	 * Hook everything.
	 *
	 * @since 1.0.0
	 */
	public static function hook() {
		// phpcs:disable PEAR.Functions.FunctionCallSignature.SpaceBeforeCloseBracket, Generic.Functions.FunctionCallArgumentSpacing.TooMuchSpaceAfterComma, WordPress.Arrays.CommaAfterArrayItem.SpaceAfterComma, WordPress.Arrays.ArrayDeclarationSpacing.SpaceBeforeArrayCloser, Generic.Functions.FunctionCallArgumentSpacing.SpaceBeforeComma

		// Rewrite URLs of dependencies to use jsDelivr.
		add_filter( 'script_loader_src',          [ __NAMESPACE__ . '\Rewrite',      'script'                      ], -567, 2  );
		add_filter( 'style_loader_src',           [ __NAMESPACE__ . '\Rewrite',      'style'                       ], -567, 2  );

		// Rewrite URL of directory that holds SVG versions of emoji.
		add_filter( 'emoji_svg_url',              [ __NAMESPACE__ . '\Rewrite',      'emoji_svg_url'               ], -567     );

		// Add subresource integrity attributes to rewritten dependencies.
		add_filter( 'script_loader_tag',          [ __NAMESPACE__ . '\SRI',          'script'                      ], -567, 3  );
		add_filter( 'style_loader_tag',           [ __NAMESPACE__ . '\SRI',          'style'                       ], -567, 3  );

		// Save current page queue and schedule processing of queued despondencies.
		add_action( 'shutdown',                   [ __NAMESPACE__ . '\Queue',        'save'                        ], 1        );
		add_action( 'shutdown',                   [ __NAMESPACE__ . '\Queue',        'schedule_processing'         ], 2        );

		// Remove expired dependencies from settings.
		add_action( 'wp_scheduled_delete',        [ __NAMESPACE__ . '\Clean',        'expired'                     ], 2        );

		// Add suggested privacy policy content.
		add_action( 'admin_init',                 [ __NAMESPACE__ . '\Privacy',      'add_privacy_content'         ], 2        );

		// Add WP-CLI commands.
		add_action( 'cli_init',                   [ __NAMESPACE__ . '\Main',         'init_wp_cli'                 ], 2        );

		// Disable concatenation of dependencies on admin pages.
		add_action( 'init',                       [ __NAMESPACE__ . '\Main',         'disable_concatenation'       ], 2        );

		// Put jQuery in no-conflict mode.
		add_action( 'wp_loaded',                  [ __NAMESPACE__ . '\Main',         'jquery_noconflict'           ], 2        );

		// Add extra headers for better processing of paths.
		add_filter( 'extra_plugin_headers',       [ __NAMESPACE__ . '\Main',         'add_extra_headers'           ], 2        );
		add_filter( 'extra_theme_headers',        [ __NAMESPACE__ . '\Main',         'add_extra_headers'           ], 2        );

		// Remove all dependencies with paths that belong to changed resources.
		add_action( 'upgrader_process_complete',  [ __NAMESPACE__ . '\Clean',        'after_upgrade'               ], 2   , 2  );
		add_action( 'deactivated_plugin',         [ __NAMESPACE__ . '\Clean',        'after_plugin_deactivation'   ], 2   , 2  );
		add_action( 'switch_theme',               [ __NAMESPACE__ . '\Clean',        'after_theme_switch'          ], 2   , 3  );

		// Reduce ttl of inactive dependency when it is from core and core has recently upgraded.
		add_filter( 'commonwp_inactive_path_ttl', [ __NAMESPACE__ . '\Expiration',   'filter_inactive_path_ttl'    ], 2   , 2  );

		// Force deletion of expired paths and maybe reschedule again on scheduled event.
		add_action( 'for_recently_upgraded_core', [ __NAMESPACE__ . '\Clean',        'for_recently_upgraded_core'  ], 2        );

		// Register listener for background processes from Backdrop library.
		add_action( 'admin_init', [ 'dimadin\WP\Library\Backdrop\Main',              'init'                        ], 2        );

		// Remove expired temporaries from database.
		add_action( 'admin_init', [ 'WP_Temporary',                                  'clean'                       ], 2        );

		// Disable Jetpack Site Accelerator for static files (aka Photon CDN and Asset CDN).
		add_filter( 'jetpack_force_disable_site_accelerator',                        '__return_true',                 2        );

		// phpcs:enable
	}

	/**
	 * Disable scripts and styles concatenation.
	 *
	 * It can only be done by using global
	 * variable that holds value, not via filter.
	 *
	 * @since 1.0.0
	 *
	 * @global bool $concatenate_scripts
	 */
	public static function disable_concatenation() {
		$GLOBALS['concatenate_scripts'] = false; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.OverrideProhibited
	}

	/**
	 * Add inline JavaScript code to put jQuery in noConflict mode.
	 *
	 * Copy of jQuery in WordPress uses this at the end of
	 * the file. However, original copy (and one used in
	 * CDN) doesn't have this. By printing we replicate
	 * behaviour as when WordPress copy is used.
	 *
	 * @since 1.0.0
	 */
	public static function jquery_noconflict() {
		wp_add_inline_script( 'jquery-core', 'try{jQuery.noConflict();}catch(e){};' );
	}

	/**
	 * Add extra headers to get_plugins() or wp_get_themes().
	 *
	 * These extra headers allow better processing of paths
	 * that aren't hosted on WordPress.org.
	 *
	 * @since 1.0.0
	 *
	 * @param array $extra_headers Array of file headers in `HeaderKey => Header Value` format.
	 * @return array Array of file headers in `HeaderKey => Header Value` format.
	 */
	public static function add_extra_headers( $extra_headers ) {
		/*
		 * Add support for headers that define paths in GitHub
		 * that are used by GitHub Updater so that they are
		 * available even if GitHub Updater isn't active.
		 */
		$github_headers = [
			'GitHub Plugin URI' => 'GitHub Plugin URI',
			'GitHub Theme URI'  => 'GitHub Theme URI',
		];

		/*
		 * Add support for header that defines plugin or theme
		 * as one not hosted on WordPress.org. Because ticket
		 * #32101 is still not fixed...
		 *
		 * @link https://core.trac.wordpress.org/ticket/32101
		 */
		$private_header = [
			'Private' => 'Private',
		];

		return array_unique( array_merge( $extra_headers, $github_headers, $private_header ) );
	}

	/**
	 * Add WP-CLI commands.
	 *
	 * @since 1.0.0
	 */
	public static function init_wp_cli() {
		WP_CLI::add_command( 'commonwp', __NAMESPACE__ . '\WPCLI' );

		// Add WP_Temporary command.
		WP_Temporary::init_wp_cli();
	}
}
