<?php
/**
 * Small helper functions used across the plugin.
 *
 * @package AirRaidAlertUA
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'ara_get_setting' ) ) {
	/**
	 * Get an effective setting for the current site.
	 *
	 * @param string $key     Setting key.
	 * @param mixed  $default Fallback value.
	 * @return mixed
	 */
	function ara_get_setting( $key, $default = null ) {
		return ARA_Settings::get( $key, $default );
	}
}

if ( ! function_exists( 'ara_is_enabled' ) ) {
	/**
	 * Whether the widget is enabled for the current site.
	 *
	 * @return bool
	 */
	function ara_is_enabled() {
		return (bool) ara_get_setting( 'enabled' );
	}
}

if ( ! function_exists( 'ara_widget' ) ) {
	/**
	 * Render the alert widget in a theme template.
	 *
	 * @param array $args Optional renderer arguments.
	 * @return string
	 */
	function ara_widget( $args = array() ) {
		return ARA_Renderer::render( $args );
	}
}
