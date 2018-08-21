<?php
/**
 * Uninstall procedure for commonWP.
 *
 * @package commonWP
 * @subpackage Uninstall
 * @since 1.0.0
 */

// Exit if accessed directly or not on uninstall.
if ( ! defined( 'ABSPATH' ) || ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

/*
 * Remove options that could store data.
 *
 * @since 1.0.0
 */
delete_option( 'commonwp_data' );
delete_site_option( 'commonwp_data' );

/*
 * Try to load dependencies if WP_Temporary isn't loaded.
 */
if ( ! class_exists( 'WP_Temporary' ) && file_exists( __DIR__ . '/vendor/autoload.php' ) ) {
	require __DIR__ . '/vendor/autoload.php';
}

/*
 * If WP_Temporary is loaded, clean temporaries used by commonWP.
 *
 * @since 1.0.0
 */
if ( class_exists( 'WP_Temporary' ) ) {
	$commonwp_temporaries = [
		'commonwp_latest_core_versions',
		'commonwp_latest_plugins_versions',
		'commonwp_latest_themes_versions',
		'commonwp_processing_queue',
		'commonwp_recently_upgraded_core',
	];

	foreach ( $commonwp_temporaries as $commonwp_temporary ) {
		WP_Temporary::delete( $commonwp_temporary );
		WP_Temporary::delete_site( $commonwp_temporary );
	}

	WP_Temporary::clean();
}
