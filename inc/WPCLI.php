<?php
/**
 * \dimadin\WP\Plugin\commonWP\WPCLI class.
 *
 * @package commonWP
 * @since 1.0.0
 */

namespace dimadin\WP\Plugin\commonWP;

use dimadin\WP\Plugin\commonWP\Clean;
use dimadin\WP\Plugin\commonWP\Store;
use dimadin\WP\Plugin\commonWP\Queue;
use dimadin\WP\Plugin\commonWP\Utils;
use WP_CLI_Command;
use WP_CLI;

/**
 * Get or delete data from storage, or process paths.
 *
 * ## EXAMPLES
 *
 *     # Delete all data that commonWP is using.
 *     $ wp commonwp clean all
 *     commonWP data was deleted.
 *
 *     # Delete paths whose TTL has passed.
 *     $ wp commonwp clean expired
 *     Expired paths were deleted.
 *
 *     # Delete paths that are from /wp-admin folder.
 *     $ wp commonwp starting-with /wp-admin
 *     Paths that start with /wp-admin were deleted.
 *
 *     # List paths of all types.
 *     $ wp commonwp paths list
 *
 *     # List paths that can be rewritten.
 *     $ wp commonwp paths list active
 *
 *     # List paths that are not rewritten.
 *     $ wp commonwp paths list inactive
 *
 *     # List paths that should be processed.
 *     $ wp commonwp paths list queue
 *
 *     # Process all waiting paths.
 *     $ wp commonwp queue process
 *     Queue was processed.
 *
 * @since 1.0.0
 */
class WPCLI extends WP_CLI_Command {
	/**
	 * Delete from commonWP store.
	 *
	 * ## OPTIONS
	 *
	 * all                  : Delete everything.
	 * expired              : Delete expired paths.
	 * starting-with <path> : Delete paths starting with <path>
	 *
	 *
	 * ## EXAMPLES
	 *
	 *     # Delete all data that commonWP is using.
	 *     $ wp commonwp clean all
	 *     commonWP data was deleted.
	 *
	 *     # Delete paths whose TTL has passed.
	 *     $ wp commonwp clean expired
	 *     Expired paths were deleted.
	 *
	 *     # Delete paths that are from /wp-admin folder.
	 *     $ wp commonwp starting-with /wp-admin
	 *     Paths that start with /wp-admin were deleted.
	 *
	 * @synopsis <all|expired|starting-with> [<path>]
	 *
	 * @since 1.0.0
	 *
	 * @param array $args Array of positional arguments.
	 * @param array $assoc_args Associative array of associative arguments.
	 */
	public function clean( $args, $assoc_args ) {
		$action = isset( $args[0] ) ? $args[0] : 'prompt';
		if ( ! in_array( $action, array( 'all', 'expired', 'starting-with', 'prompt' ), true ) ) {
			WP_CLI::error( sprintf( '%s is not a valid command.', $args[0] ) );
		}

		switch ( $action ) {
			case 'all':
				Clean::all();
				WP_CLI::success( 'commonWP data was deleted.' );
				break;
			case 'expired':
				Clean::expired();
				WP_CLI::success( 'Expired paths were deleted.' );
				break;
			case 'starting-with':
				if ( isset( $args[1] ) ) {
					Clean::starting_with( $args[1] );
				}
				WP_CLI::success( sprintf( 'Paths that start with %s were deleted.', $args[1] ) );
				break;
			case 'prompt':
				WP_CLI::error( 'Please specify what you want to delete.' );
				break;
		}
	}

	/**
	 * Get stored paths data.
	 *
	 * ## OPTIONS
	 *
	 * list : List all type of paths.
	 *
	 * list active   : List paths that can be rewritten (active).
	 * list inactive : List paths that are not rewritten (inactive).
	 * list queue    : List paths to process (queued).
	 *
	 *
	 * ## EXAMPLES
	 *
	 *     # List paths of all types.
	 *     $ wp commonwp paths list
	 *
	 *     # List paths that can be rewritten.
	 *     $ wp commonwp paths list active
	 *
	 *     # List paths that are not rewritten.
	 *     $ wp commonwp paths list inactive
	 *
	 *     # List paths that should be processed.
	 *     $ wp commonwp paths list queue
	 *
	 * @synopsis <list> [<active|inactive|queue>]
	 *
	 * @since 1.0.0
	 *
	 * @param array $args Array of positional arguments.
	 * @param array $assoc_args Associative array of associative arguments.
	 */
	public function paths( $args, $assoc_args ) {
		$action = isset( $args[0] ) ? $args[0] : 'prompt';
		if ( 'list' !== $action ) {
			WP_CLI::error( sprintf( '%s is not a valid command.', $action ) );
		}

		$stored_data = Store::get();

		if ( ! is_array( $stored_data ) ) {
			WP_CLI::error( 'There are no stored paths' );
		}

		$types = [ 'active', 'inactive', 'queue' ];

		if ( isset( $assoc_args['type'] ) ) {
			$types = array_intersect( explode( ',', $assoc_args['type'] ), $types );
		}

		// Loop through types of paths.
		foreach ( $types as $path_type ) {
			$items = [];

			switch ( $path_type ) {
				case 'active':
					if ( ! array_key_exists( $path_type, $stored_data ) || empty( $stored_data[ $path_type ] ) ) {
						WP_CLI::line( WP_CLI::colorize( '%CThere are no stored paths that can be rewritten (active).%n' ) );
						break;
					}

					$fields = [
						'Relative Path',
						'Remote Path',
						'Timeout',
					];

					foreach ( $stored_data[ $path_type ] as $origin_path => $path_settings ) {
						$items[] = [
							'Relative Path' => $origin_path,
							'Remote Path'   => $path_settings['path'],
							'Timeout'       => $this->get_human_ttl( $path_settings['ttl'] ),
						];
					}
					WP_CLI::line( WP_CLI::colorize( '%GPaths that can be rewritten (active):%n' ) );

					break;
				case 'inactive':
					if ( ! array_key_exists( $path_type, $stored_data ) || empty( $stored_data[ $path_type ] ) ) {
						WP_CLI::line( WP_CLI::colorize( '%CThere are no stored paths that are not rewritten (inactive).%n' ) );
						break;
					}

					$fields = [
						'Relative Path',
						'Timeout',
					];

					foreach ( $stored_data[ $path_type ] as $origin_path => $path_settings ) {
						$items[] = [
							'Relative Path' => $origin_path,
							'Timeout'       => $this->get_human_ttl( $path_settings['ttl'] ),
						];
					}
					WP_CLI::line( WP_CLI::colorize( '%GPaths that are not rewritten (inactive):%n' ) );

					break;
				case 'queue':
					if ( ! array_key_exists( $path_type, $stored_data ) || empty( $stored_data[ $path_type ] ) ) {
						WP_CLI::line( WP_CLI::colorize( '%CThere are no stored paths to process (queued).%n' ) );
						break;
					}

					$fields = [
						'Relative Path',
						'Handle',
						'Dependency Type',
						'Timeout',
					];

					foreach ( $stored_data[ $path_type ] as $origin_path => $path_settings ) {
						$items[] = [
							'Relative Path'   => $origin_path,
							'Handle'          => $path_settings['handle'],
							'Dependency Type' => $path_settings['type'],
							'Timeout'         => $this->get_human_ttl( $path_settings['ttl'] ),
						];
					}
					WP_CLI::line( WP_CLI::colorize( '%GPaths to process (queued):%n' ) );

					break;
			}

			if ( isset( $fields ) ) {
				WP_CLI\Utils\format_items( 'table', $items, $fields );
				unset( $fields );
			}
		}
	}

	/**
	 * Process queued paths.
	 *
	 * ## OPTIONS
	 *
	 * process : Process queued paths.
	 *
	 *
	 * ## EXAMPLES
	 *
	 *     # Process all waiting paths.
	 *     $ wp commonwp queue process
	 *     Queue was processed.
	 *
	 * @synopsis <process>
	 *
	 * @since 1.0.0
	 *
	 * @param array $args Array of positional arguments.
	 * @param array $assoc_args Associative array of associative arguments.
	 */
	public function queue( $args, $assoc_args ) {
		$action = isset( $args[0] ) ? $args[0] : 'prompt';
		if ( 'process' !== $action ) {
			WP_CLI::error( sprintf( '%s is not a valid command.', $action ) );
		}

		// Process maximum number of paths.
		add_filter( 'commonwp_max_items_in_processing_queue', function( $num ) {
			return PHP_INT_MAX;
		} );

		Queue::process();

		WP_CLI::success( 'Queue was processed.' );
	}

	/**
	 * Format TTL to human readable form.
	 *
	 * @since 1.0.0
	 *
	 * @param int $ttl Timeout in unix epoch time.
	 * @return string
	 */
	protected function get_human_ttl( $ttl ) {
		static $time = null;

		if ( empty( $time ) ) {
			$time = time();
		}

		// If TTL was in the past.
		if ( $time > $ttl ) {
			return human_time_diff( $ttl, $time ) . ' ago';
		} else {
			return 'in ' . human_time_diff( $time, $ttl );
		}
	}
}
