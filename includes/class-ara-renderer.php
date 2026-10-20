<?php
/**
 * Shared widget renderer.
 *
 * The shortcode, Gutenberg block, classic widget, and template helper all use
 * this class so the embed markup only lives in one place.
 *
 * @package AirRaidAlertUA
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Builds the iframe or image markup.
 */
class ARA_Renderer {

	/**
	 * Widget dimension presets, mirrored from the source builder.
	 *
	 * @return array
	 */
	public static function dimensions_map() {
		return array(
			'line'  => array(
				's' => array( 320, 44 ),
				'm' => array( 480, 44 ),
				'l' => array( '100%', 44 ),
			),
			'card'  => array(
				's' => array( 300, 190 ),
				'm' => array( 360, 200 ),
				'l' => array( '100%', 200 ),
			),
			'stats' => array(
				's' => array( 300, 330 ),
				'm' => array( 360, 330 ),
				'l' => array( '100%', 330 ),
			),
			'map'   => array(
				's' => array( 300, 380 ),
				'm' => array( 400, 480 ),
				'l' => array( '100%', 560 ),
			),
			'ua'    => array(
				's' => array( 320, 290 ),
				'm' => array( 480, 390 ),
				'l' => array( '100%', 540 ),
			),
		);
	}

	/**
	 * Image fallback widths.
	 *
	 * @return array
	 */
	public static function image_widths() {
		return array(
			'line'  => 640,
			'card'  => 420,
			'stats' => 420,
			'map'   => 480,
			'ua'    => 640,
		);
	}

	/**
	 * Render the widget.
	 *
	 * @param array $args Optional overrides.
	 * @return string
	 */
	public static function render( $args = array() ) {
		$settings = ARA_Settings::get_effective();

		if ( empty( $settings['enabled'] ) ) {
			return '';
		}

		$args = self::normalize_args( $args, $settings );

		$type   = $args['type'];
		$size   = $args['size'];
		$theme  = $args['theme'];
		$region = (int) $args['region'];

		if ( 'ua' === $type ) {
			$region = 0;
		} else {
			if ( ! $region ) {
				$region = (int) $settings['region'];
			}

			$known_ids = ARA_Regions::get_region_ids();
			if ( ! empty( $known_ids ) && ! in_array( $region, $known_ids, true ) ) {
				$region = (int) $settings['region'];
			}

			if ( ! $region ) {
				return '';
			}
		}

		$region_data = $region ? ARA_Regions::get_by_id( $region ) : null;
		$label       = $region_data ? $region_data['name'] : __( 'Україна', 'air-raid-alert-ua' );

		$params = array(
			'type' => $type,
			'size' => $size,
		);

		if ( $region ) {
			$params['region'] = $region;
		}

		if ( 'auto' !== $theme ) {
			$params['theme'] = $theme;
		}

		if ( ! empty( $settings['use_custom_colors'] ) ) {
			$params['custom'] = '1';
			$params['bg']     = $settings['bg'];
			$params['fg']     = $settings['fg'];
			$params['accent'] = $settings['accent'];
			$params['red']    = $settings['red'];
			$params['yellow'] = $settings['yellow'];
		}

		$fallback = ! empty( $args['fallback_image'] );
		$endpoint = $fallback ? '/widget-image' : '/widget';
		$url      = add_query_arg( $params, 'https://alerts.na-agency.com' . $endpoint );
		$classes  = array(
			'ara-widget',
			'ara-widget--' . $type,
			'ara-widget--size-' . $size,
		);

		if ( ! empty( $args['class'] ) ) {
			$classes[] = sanitize_html_class( $args['class'] );
		}

		$wrapper = '<div class="' . esc_attr( implode( ' ', array_filter( $classes ) ) ) . '" data-ara-widget="1" data-region="' . (int) $region . '" data-type="' . esc_attr( $type ) . '" data-size="' . esc_attr( $size ) . '">';

		if ( $fallback ) {
			$widths = self::image_widths();
			$width  = isset( $widths[ $type ] ) ? $widths[ $type ] : $widths['card'];

			$wrapper .= '<img src="' . esc_url( $url ) . '" data-ara-image="1" data-ara-src="' . esc_url( $url ) . '" width="' . (int) $width . '" alt="' . esc_attr( self::alt_text( $label ) ) . '" loading="lazy" referrerpolicy="strict-origin-when-cross-origin" style="border:0;max-width:100%;height:auto;border-radius:14px">';
		} else {
			$dimensions = self::get_dimensions( $type, $size );
			$width      = '100%' === $dimensions[0] ? '100%' : (int) $dimensions[0] . 'px';
			$height     = (int) $dimensions[1];

			$wrapper .= '<iframe src="' . esc_url( $url ) . '" width="' . esc_attr( '100%' === $dimensions[0] ? '100%' : $dimensions[0] ) . '" height="' . (int) $height . '" title="' . esc_attr( self::alt_text( $label ) ) . '" loading="lazy" referrerpolicy="strict-origin-when-cross-origin" style="border:0;width:' . esc_attr( $width ) . ';height:' . (int) $height . 'px;max-width:100%;border-radius:14px;color-scheme:normal"></iframe>';
		}

		if ( ! empty( $args['attribution'] ) ) {
			$wrapper .= self::attribution_html( $region_data );
		}

		$wrapper .= '</div>';

		return $wrapper;
	}

	/**
	 * Build a widget URL. Useful for admin previews.
	 *
	 * @param array $args Optional overrides. Pass network => true for network defaults.
	 * @return string
	 */
	public static function get_url( $args = array() ) {
		$args     = is_array( $args ) ? $args : array();
		$settings = ! empty( $args['network'] ) ? ARA_Settings::get_network_settings() : ARA_Settings::get_effective();
		$args     = self::normalize_args( $args, $settings );
		$region   = 'ua' === $args['type'] ? 0 : (int) $args['region'];

		$params = array(
			'type' => $args['type'],
			'size' => $args['size'],
		);

		if ( $region ) {
			$params['region'] = $region;
		}

		if ( 'auto' !== $args['theme'] ) {
			$params['theme'] = $args['theme'];
		}

		if ( ! empty( $settings['use_custom_colors'] ) ) {
			$params['custom'] = '1';
			$params['bg']     = $settings['bg'];
			$params['fg']     = $settings['fg'];
			$params['accent'] = $settings['accent'];
			$params['red']    = $settings['red'];
			$params['yellow'] = $settings['yellow'];
		}

		$endpoint = ! empty( $args['fallback_image'] ) ? '/widget-image' : '/widget';

		return add_query_arg( $params, 'https://alerts.na-agency.com' . $endpoint );
	}

	/**
	 * Get [width, height] for a type/size pair.
	 *
	 * @param string $type Widget type.
	 * @param string $size Widget size.
	 * @return array
	 */
	public static function get_dimensions( $type, $size ) {
		$map = self::dimensions_map();
		if ( ! isset( $map[ $type ] ) ) {
			$type = 'card';
		}
		if ( ! isset( $map[ $type ][ $size ] ) ) {
			$size = 'm';
		}

		return $map[ $type ][ $size ];
	}

	/**
	 * Shortcode-friendly defaults.
	 *
	 * @param array $args   Raw arguments.
	 * @param array $settings Effective settings.
	 * @return array
	 */
	private static function normalize_args( $args, $settings ) {
		$args = is_array( $args ) ? $args : array();

		if ( isset( $args['fallbackImage'] ) && ! isset( $args['fallback_image'] ) ) {
			$args['fallback_image'] = $args['fallbackImage'];
		}

		$merged = $settings;

		if ( ! isset( $merged['type'] ) || ! in_array( $merged['type'], ARA_Settings::types(), true ) ) {
			$merged['type'] = 'card';
		}
		if ( ! isset( $merged['size'] ) || ! in_array( $merged['size'], ARA_Settings::sizes(), true ) ) {
			$merged['size'] = 'm';
		}
		if ( ! isset( $merged['theme'] ) || ! in_array( $merged['theme'], ARA_Settings::themes(), true ) ) {
			$merged['theme'] = 'auto';
		}

		if ( isset( $args['region'] ) && is_scalar( $args['region'] ) && '' !== $args['region'] ) {
			$merged['region'] = absint( $args['region'] );
		}

		if ( isset( $args['type'] ) && is_scalar( $args['type'] ) ) {
			$type = sanitize_key( (string) $args['type'] );
			if ( in_array( $type, ARA_Settings::types(), true ) ) {
				$merged['type'] = $type;
			}
		}

		if ( isset( $args['size'] ) && is_scalar( $args['size'] ) ) {
			$size = sanitize_key( (string) $args['size'] );
			if ( in_array( $size, ARA_Settings::sizes(), true ) ) {
				$merged['size'] = $size;
			}
		}

		if ( isset( $args['theme'] ) && is_scalar( $args['theme'] ) ) {
			$theme = sanitize_key( (string) $args['theme'] );
			if ( in_array( $theme, ARA_Settings::themes(), true ) ) {
				$merged['theme'] = $theme;
			}
		}

		if ( isset( $args['fallback_image'] ) ) {
			$merged['fallback_image'] = self::to_bool( $args['fallback_image'] ) ? 1 : 0;
		}

		if ( isset( $args['attribution'] ) ) {
			$merged['attribution'] = self::to_bool( $args['attribution'] ) ? 1 : 0;
		}

		$merged['class'] = isset( $args['class'] ) && is_scalar( $args['class'] ) ? sanitize_html_class( (string) $args['class'] ) : '';

		return $merged;
	}

	/**
	 * Convert shortcode-style booleans.
	 *
	 * @param mixed $value Raw value.
	 * @return bool
	 */
	private static function to_bool( $value ) {
		if ( is_bool( $value ) ) {
			return $value;
		}

		if ( ! is_scalar( $value ) ) {
			return false;
		}

		if ( is_numeric( $value ) ) {
			return (int) $value > 0;
		}

		return in_array( strtolower( (string) $value ), array( '1', 'true', 'yes', 'on' ), true );
	}

	/**
	 * Build the attribution line.
	 *
	 * @param array|null $region_data Region row.
	 * @return string
	 */
	private static function attribution_html( $region_data ) {
		$link = $region_data
			? 'https://alerts.na-agency.com/region/' . (int) $region_data['id'] . '-' . $region_data['slug']
			: 'https://alerts.na-agency.com/regions';

		return '<p class="ara-widget__attribution"><a href="' . esc_url( $link ) . '" target="_blank" rel="noopener nofollow">' . esc_html__( 'Повітряна тривога', 'air-raid-alert-ua' ) . '</a> — <a href="https://alerts.na-agency.com/" target="_blank" rel="noopener nofollow">alerts.na-agency.com</a></p>';
	}

	/**
	 * Accessible title/alt text.
	 *
	 * @param string $label Region label.
	 * @return string
	 */
	private static function alt_text( $label ) {
		return sprintf(
			/* translators: %s: region name */
			__( 'Повітряна тривога — %s', 'air-raid-alert-ua' ),
			$label
		);
	}
}
