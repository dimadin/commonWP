<?php
/**
 * \dimadin\WP\Plugin\commonWP\Queue class.
 *
 * @package commonWP
 * @since 1.0.0
 */

namespace dimadin\WP\Plugin\commonWP;

use dimadin\WP\Plugin\commonWP\Store;
use dimadin\WP\Plugin\commonWP\Utils;
use dimadin\WP\Plugin\commonWP\Lock;
use dimadin\WP\Plugin\commonWP\Process;
use dimadin\WP\Library\Backdrop\Task;
use Exception;

/**
 * Class that registers and triggers processing of paths.
 *
 * @since 1.0.0
 */
class Queue {
	/**
	 * Array of paths in current request that should be processed.
	 *
	 * @since 1.0.0
	 * @var array
	 */
	public static $queue = [];

	/**
	 * Add path to array of paths that should be processed.
	 *
	 * @since 1.0.0
	 *
	 * @param string $relative_path Path relative from WordPress installation.
	 * @param string $src           URL of dependency.
	 * @param string $handle        Handle by which dependency is registered in WordPress.
	 * @param string $type          Type of dependency.
	 */
	public static function add( $relative_path, $src, $handle, $type ) {
		// Check if in current queue.
		if ( array_key_exists( $relative_path, static::$queue ) ) {
			return;
		}

		static::$queue[ $relative_path ] = [
			'src'    => $src,
			'handle' => $handle,
			'type'   => $type,
			'ttl'    => time() + HOUR_IN_SECONDS,
		];
	}

	/**
	 * Save array of paths in current request to process to store.
	 *
	 * @since 1.0.0
	 */
	public static function save() {
		if ( ! static::$queue ) {
			return;
		}

		$stored_data = Store::get();

		if ( ! is_array( $stored_data ) ) {
			$stored_data = Utils::init_stored_data();
		}

		if ( ! array_key_exists( 'queue', $stored_data ) ) {
			$stored_data['queue'] = [];
		}

		// Check if there are new paths.
		if ( ! array_diff_key( static::$queue, $stored_data['queue'] ) ) {
			return;
		}

		$stored_data['queue'] = array_merge( $stored_data['queue'], static::$queue );

		Store::update( $stored_data );
	}

	/**
	 * Make Backdrop task if there are paths to process in store.
	 *
	 * @since 1.0.0
	 */
	public static function schedule_processing() {
		$stored_data = Store::get();

		if ( ! is_array( $stored_data ) || ! array_key_exists( 'queue', $stored_data ) || empty( $stored_data['queue'] ) ) {
			return;
		}

		if ( Lock::is() ) {
			return;
		}

		$task = new Task( [ __NAMESPACE__ . '\Queue', 'process' ] );
		$task->schedule();
	}

	/**
	 * Process paths that are stored for processing.
	 *
	 * @since 1.0.0
	 */
	public static function process() {
		// Check if processing is done or has been done on current request.
		if ( Lock::is() ) {
			return;
		}

		// Get raw store data.
		$stored_data = $raw_stored_data = Store::get(); // phpcs:ignore Squiz.PHP.DisallowMultipleAssignments

		/**
		 * Filter raw store data before processing.
		 *
		 * @since 1.0.0
		 *
		 * @param array $stored_data Raw store data.
		 */
		$stored_data = apply_filters( 'commonwp_pre_process_queue_stored_data', $stored_data );

		// Proceed only if there are paths that should be processed.
		if ( ! is_array( $stored_data ) || ! array_key_exists( 'queue', $stored_data ) || empty( $stored_data['queue'] ) ) {
			// If there was change during filtering, save changes.
			if ( $stored_data !== $raw_stored_data ) {
				Store::update( $stored_data );
			}

			return;
		}

		// Save that processing queue is executing.
		Lock::set();

		$i = 0;

		/**
		 * Filter maximum number of processes that should occur in current processing.
		 *
		 * @since 1.0.0
		 *
		 * @param int $num Maximum number of processes that should occur in current processing.
		 *                 Default 10.
		 */
		$max = apply_filters( 'commonwp_max_items_in_processing_queue', 10 );

		foreach ( $stored_data['queue'] as $origin_path => $path_settings ) {
			// Only allowed number of processed paths per request.
			if ( $i >= $max ) {
				break;
			}

			// Only if path's TTL hasn't passed.
			if ( time() > $path_settings['ttl'] ) {
				unset( $stored_data['queue'][ $origin_path ] );
				continue;
			}

			$process = new Process();

			// Set class properties.
			$process->origin_path = $origin_path;
			$process->src         = $path_settings['src'];
			$process->handle      = $path_settings['handle'];
			$process->type        = $path_settings['type'];

			try {
				// Do different processing based on type of dependency.
				switch ( $path_settings['type'] ) {
					case 'emoji_svg_url':
						$process->emoji_svg_url();

						break;
					default:
						$process->execute();

						break;
				}

				$stored_data['active'][ $origin_path ] = [
					'path' => $process->remote_path,
					'ttl'  => $process->ttl,
				];

				// If there is subresource integrity, save it.
				if ( ! empty( $process->subresource_integrity ) ) {
					$stored_data['active'][ $origin_path ]['sri'] = $process->subresource_integrity;
				}
			} catch ( Exception $e ) {
				/**
				 * Filter TTL of inactive path.
				 *
				 * Note that path might be cached as inactive for up to 12 hours after
				 * expiration. Garbage collector is scheduled to run twice daily,
				 * though it can be run before.
				 *
				 * @since 1.0.0
				 *
				 * @param int     $ttl     TTL of inactive path in seconds. Default 86400 (one day).
				 * @param Process $process Current instance of processing class.
				 */
				$ttl = apply_filters( 'commonwp_inactive_path_ttl', DAY_IN_SECONDS, $process );

				$stored_data['inactive'][ $origin_path ] = [
					'ttl' => time() + $ttl,
				];
			}

			unset( $stored_data['queue'][ $origin_path ] );
			$i++;
		}

		// Delete temporary that processing queue is executing.
		Lock::remove();

		// Save new store data.
		Store::update( $stored_data );
	}
}
