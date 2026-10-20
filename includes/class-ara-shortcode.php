<?php
/**
 * Shortcode support.
 *
 * @package AirRaidAlertUA
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Registers [air_raid_alert].
 */
class ARA_Shortcode {

	/**
	 * Shortcode tag.
	 */
	const TAG = 'air_raid_alert';

	/**
	 * Register hooks.
	 *
	 * @return void
	 */
	public static function init() {
		add_shortcode( self::TAG, array( __CLASS__, 'render' ) );
	}

	/**
	 * Render the shortcode.
	 *
	 * @param array  $atts    Shortcode attributes.
	 * @param string $content Enclosed content.
	 * @param string $tag     Shortcode tag.
	 * @return string
	 */
	public static function render( $atts, $content = '', $tag = '' ) {
		$atts = shortcode_atts(
			array(
				'region'      => null,
				'type'        => null,
				'size'        => null,
				'theme'       => null,
				'fallback'    => null,
				'attribution' => null,
				'class'       => '',
			),
			$atts,
			self::TAG
		);

		$args = array();

		if ( null !== $atts['region'] && '' !== $atts['region'] ) {
			$args['region'] = absint( $atts['region'] );
		}
		if ( null !== $atts['type'] && '' !== $atts['type'] ) {
			$args['type'] = sanitize_key( $atts['type'] );
		}
		if ( null !== $atts['size'] && '' !== $atts['size'] ) {
			$args['size'] = sanitize_key( $atts['size'] );
		}
		if ( null !== $atts['theme'] && '' !== $atts['theme'] ) {
			$args['theme'] = sanitize_key( $atts['theme'] );
		}
		if ( null !== $atts['fallback'] && '' !== $atts['fallback'] ) {
			$args['fallback_image'] = $atts['fallback'];
		}
		if ( null !== $atts['attribution'] && '' !== $atts['attribution'] ) {
			$args['attribution'] = $atts['attribution'];
		}
		if ( ! empty( $atts['class'] ) ) {
			$args['class'] = $atts['class'];
		}

		/**
		 * Filter shortcode arguments before rendering.
		 *
		 * @param array  $args Renderer arguments.
		 * @param array  $atts Shortcode attributes.
		 * @param string $tag  Shortcode tag.
		 */
		$args = apply_filters( 'ara_shortcode_args', $args, $atts, $tag );

		return ARA_Renderer::render( $args );
	}
}
