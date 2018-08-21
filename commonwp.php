<?php
/**
 * Plugin Name: commonWP
 * Description: Load open source static assets over free, public CDN.
 * Author:      Milan Dinić
 * Author URI:  https://milandinic.com/
 * Version:     1.0.0-beta-1
 * Text Domain: commonwp
 * Domain Path: /languages/
 *
 * @package commonWP
 */

// Load dependencies.
if ( file_exists( __DIR__ . '/vendor/autoload.php' ) ) {
	require __DIR__ . '/vendor/autoload.php';
}

/**
 * Version of commonWP plugin.
 *
 * @since 1.0.0
 * @var string
 */
define( 'COMMONWP_VERSION', '1.0.0-beta-1' );

/*
 * Initialize a plugin.
 *
 * Load class when all plugins are loaded
 * so that other plugins can overwrite it.
 */
add_action( 'plugins_loaded', [ 'dimadin\WP\Plugin\commonWP\Main', 'get_instance' ], 10 );
