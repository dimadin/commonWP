<?php
/**
 * Plugin Name: commonWP
 * Plugin URI:  https://milandinic.com/wordpress/plugins/commonwp/
 * Description: Offload open source static assets to the free, public CDN.
 * Author:      Milan Dinić
 * Author URI:  https://milandinic.com/
 * Version:     1.1.0
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
define( 'COMMONWP_VERSION', '1.1.0' );

/*
 * Initialize a plugin.
 *
 * Load class when all plugins are loaded
 * so that other plugins can overwrite it.
 */
add_action( 'plugins_loaded', [ 'dimadin\WP\Plugin\commonWP\Main', 'get_instance' ], 10 );
