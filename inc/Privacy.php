<?php
/**
 * \dimadin\WP\Plugin\commonWP\Privacy class.
 *
 * @package commonWP
 * @since 1.0.0
 */

namespace dimadin\WP\Plugin\commonWP;

/**
 * Class for registering suggested privacy content.
 *
 * @since 1.0.0
 */
class Privacy {
	/**
	 * Add suggested privacy policy content for commonWP.
	 *
	 * @since 1.0.0
	 */
	public static function add_privacy_content() {
		if ( ! function_exists( 'wp_add_privacy_policy_content' ) ) {
			return;
		}

		$content = sprintf(
			// translators: %s: URL of jsDelivr CDN privacy page.
			__(
				'To improve performance of pages, some resources might be loaded from jsDelivr CDN.

The jsDelivr CDN privacy policy is <a href="%s" target="_blank">here</a>.',
				'commonwp'
			),
			esc_url( 'https://www.jsdelivr.com/privacy-policy-jsdelivr-net' )
		);

		wp_add_privacy_policy_content(
			__( 'commonWP', 'commonwp' ),
			wp_kses_post( wpautop( $content, false ) )
		);
	}
}
