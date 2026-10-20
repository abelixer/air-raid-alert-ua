<?php
/**
 * Settings storage and sanitization.
 *
 * @package AirRaidAlertUA
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Reads effective settings and normalizes user input.
 */
class ARA_Settings {

	/**
	 * Per-site option name.
	 */
	const SITE_OPTION = 'ara_settings';

	/**
	 * Network-wide option name.
	 */
	const NETWORK_OPTION = 'ara_network_settings';

	/**
	 * Supported widget types.
	 *
	 * @return string[]
	 */
	public static function types() {
		return array( 'card', 'line', 'map', 'stats', 'ua' );
	}

	/**
	 * Supported widget sizes.
	 *
	 * @return string[]
	 */
	public static function sizes() {
		return array( 's', 'm', 'l' );
	}

	/**
	 * Supported themes.
	 *
	 * @return string[]
	 */
	public static function themes() {
		return array( 'auto', 'light', 'dark' );
	}

	/**
	 * Default settings.
	 *
	 * @return array
	 */
	public static function defaults() {
		return array(
			'enabled'           => 1,
			'region'            => 4,
			'type'              => 'card',
			'size'              => 'm',
			'theme'             => 'auto',
			'use_custom_colors' => 0,
			'bg'                => 'ffffff',
			'fg'                => '1f2937',
			'accent'            => '2563eb',
			'red'               => 'dc2626',
			'yellow'            => 'f59e0b',
			'custom_css'        => '',
			'fallback_image'    => 0,
			'attribution'       => 1,
			'cache_ttl'         => 24,
			'delete_data'       => 0,
		);
	}

	/**
	 * Raw network settings merged with defaults.
	 *
	 * @return array
	 */
	public static function get_network_settings() {
		$settings = is_multisite() ? get_site_option( self::NETWORK_OPTION, array() ) : array();
		if ( ! is_array( $settings ) ) {
			$settings = array();
		}
		return wp_parse_args( $settings, self::defaults() );
	}

	/**
	 * Raw per-site settings.
	 *
	 * @param int|null $site_id Optional site ID.
	 * @return array
	 */
	public static function get_site_settings( $site_id = null ) {
		if ( $site_id && is_multisite() ) {
			$settings = get_blog_option( (int) $site_id, self::SITE_OPTION, array() );
		} else {
			$settings = get_option( self::SITE_OPTION, array() );
		}

		if ( ! is_array( $settings ) ) {
			$settings = array();
		}

		return $settings;
	}

	/**
	 * Effective settings for a site.
	 *
	 * On multisite a site either inherits the network defaults or overrides them.
	 *
	 * @param int|null $site_id Optional site ID.
	 * @return array
	 */
	public static function get_effective( $site_id = null ) {
		$defaults = self::defaults();

		if ( ! is_multisite() ) {
			return wp_parse_args( self::get_site_settings( $site_id ), $defaults );
		}

		$network = wp_parse_args( self::get_network_settings(), $defaults );
		$site    = self::get_site_settings( $site_id );

		if ( empty( $site ) || ! empty( $site['inherit'] ) ) {
			if ( isset( $site['enabled'] ) ) {
				$network['enabled'] = empty( $site['enabled'] ) ? 0 : 1;
			}

			return $network;
		}

		return wp_parse_args( $site, $network );
	}

	/**
	 * Get one effective setting.
	 *
	 * @param string   $key     Setting key.
	 * @param mixed    $default Fallback value.
	 * @param int|null $site_id Optional site ID.
	 * @return mixed
	 */
	public static function get( $key, $default = null, $site_id = null ) {
		$settings = self::get_effective( $site_id );

		if ( array_key_exists( $key, $settings ) ) {
			return $settings[ $key ];
		}

		return $default;
	}

	/**
	 * Whether the current site inherits network settings.
	 *
	 * @param int|null $site_id Optional site ID.
	 * @return bool
	 */
	public static function inherits_network( $site_id = null ) {
		if ( ! is_multisite() ) {
			return false;
		}

		$settings = self::get_site_settings( $site_id );

		return empty( $settings ) || ! empty( $settings['inherit'] );
	}

	/**
	 * Sanitize settings submitted from the admin form.
	 *
	 * @param array  $input   Raw input.
	 * @param string $context Either "site" or "network".
	 * @return array
	 */
	public static function sanitize( $input, $context = 'site' ) {
		$input    = is_array( $input ) ? $input : array();
		$defaults = self::defaults();
		$output   = $defaults;

		$output['enabled']           = empty( $input['enabled'] ) ? 0 : 1;
		$output['use_custom_colors'] = empty( $input['use_custom_colors'] ) ? 0 : 1;
		$output['fallback_image']    = empty( $input['fallback_image'] ) ? 0 : 1;
		$output['attribution']       = empty( $input['attribution'] ) ? 0 : 1;
		$output['delete_data']       = empty( $input['delete_data'] ) ? 0 : 1;

		$type = isset( $input['type'] ) && is_scalar( $input['type'] ) ? sanitize_key( (string) $input['type'] ) : $defaults['type'];
		if ( ! in_array( $type, self::types(), true ) ) {
			$type = $defaults['type'];
		}
		$output['type'] = $type;

		$size = isset( $input['size'] ) && is_scalar( $input['size'] ) ? sanitize_key( (string) $input['size'] ) : $defaults['size'];
		if ( ! in_array( $size, self::sizes(), true ) ) {
			$size = $defaults['size'];
		}
		$output['size'] = $size;

		$theme = isset( $input['theme'] ) && is_scalar( $input['theme'] ) ? sanitize_key( (string) $input['theme'] ) : $defaults['theme'];
		if ( ! in_array( $theme, self::themes(), true ) ) {
			$theme = $defaults['theme'];
		}
		$output['theme'] = $theme;

		$region = isset( $input['region'] ) && is_scalar( $input['region'] ) ? absint( $input['region'] ) : 0;
		$known  = ARA_Regions::get_region_ids();
		if ( $region && ! empty( $known ) && ! in_array( $region, $known, true ) ) {
			$region = (int) $defaults['region'];
		}
		if ( ! $region ) {
			$region = (int) $defaults['region'];
		}
		$output['region'] = $region;

		$output['bg']     = self::sanitize_hex( isset( $input['bg'] ) ? $input['bg'] : '', $defaults['bg'] );
		$output['fg']     = self::sanitize_hex( isset( $input['fg'] ) ? $input['fg'] : '', $defaults['fg'] );
		$output['accent'] = self::sanitize_hex( isset( $input['accent'] ) ? $input['accent'] : '', $defaults['accent'] );
		$output['red']    = self::sanitize_hex( isset( $input['red'] ) ? $input['red'] : '', $defaults['red'] );
		$output['yellow'] = self::sanitize_hex( isset( $input['yellow'] ) ? $input['yellow'] : '', $defaults['yellow'] );

		$output['cache_ttl'] = isset( $input['cache_ttl'] ) && is_scalar( $input['cache_ttl'] ) ? absint( $input['cache_ttl'] ) : $defaults['cache_ttl'];
		$output['cache_ttl'] = max( 1, min( 168, $output['cache_ttl'] ) );

		$output['custom_css'] = self::sanitize_css( isset( $input['custom_css'] ) ? $input['custom_css'] : '' );

		if ( 'site' === $context ) {
			$output['inherit'] = empty( $input['inherit'] ) ? 0 : 1;
		}

		return $output;
	}

	/**
	 * Normalize a hex color.
	 *
	 * @param string $value    Raw value.
	 * @param string $fallback Fallback color without a hash.
	 * @return string
	 */
	private static function sanitize_hex( $value, $fallback ) {
		if ( ! is_scalar( $value ) ) {
			return $fallback;
		}

		$value = strtolower( ltrim( trim( (string) $value ), '#' ) );

		if ( preg_match( '/^[0-9a-f]{3}$/', $value ) ) {
			$value = $value[0] . $value[0] . $value[1] . $value[1] . $value[2] . $value[2];
		}

		if ( preg_match( '/^[0-9a-f]{6}$/', $value ) ) {
			return $value;
		}

		return $fallback;
	}

	/**
	 * Remove dangerous markup from the custom CSS field.
	 *
	 * @param string $css Raw CSS.
	 * @return string
	 */
	private static function sanitize_css( $css ) {
		if ( ! is_scalar( $css ) ) {
			return '';
		}

		$css = wp_check_invalid_utf8( (string) $css );
		$css = wp_strip_all_tags( $css );
		$css = str_ireplace(
			array( '</style', '<style', '<!--', '-->', 'expression(', 'javascript:', '@import' ),
			'',
			$css
		);

		return trim( $css );
	}
}
