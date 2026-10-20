<?php
/**
 * Front-end and admin assets.
 *
 * @package AirRaidAlertUA
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Registers and enqueues plugin assets.
 */
class ARA_Assets {

	/**
	 * Register hooks.
	 *
	 * @return void
	 */
	public static function init() {
		add_action( 'init', array( __CLASS__, 'register_assets' ), 5 );
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'enqueue_front' ), 20 );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue_admin' ) );
	}

	/**
	 * Register asset handles.
	 *
	 * @return void
	 */
	public static function register_assets() {
		wp_register_style(
			'ara-public',
			ARA_URL . 'public/assets/public.css',
			array(),
			ARA_VERSION
		);

		wp_register_script(
			'ara-public',
			ARA_URL . 'public/assets/public.js',
			array(),
			ARA_VERSION,
			true
		);

		wp_register_style(
			'ara-admin',
			ARA_URL . 'admin/assets/admin.css',
			array(),
			ARA_VERSION
		);

		wp_register_script(
			'ara-admin',
			ARA_URL . 'admin/assets/admin.js',
			array(),
			ARA_VERSION,
			true
		);
	}

	/**
	 * Front-end assets.
	 *
	 * @return void
	 */
	public static function enqueue_front() {
		if ( ! ara_is_enabled() ) {
			return;
		}

		wp_enqueue_style( 'ara-public' );
		wp_enqueue_script( 'ara-public' );

		$custom_css = (string) ara_get_setting( 'custom_css', '' );
		if ( '' !== trim( $custom_css ) ) {
			wp_add_inline_style( 'ara-public', $custom_css );
		}
	}

	/**
	 * Admin assets only on plugin screens.
	 *
	 * @param string $hook_suffix Current admin page.
	 * @return void
	 */
	public static function enqueue_admin( $hook_suffix ) {
		if ( false === strpos( (string) $hook_suffix, 'air-raid-alert-ua' ) ) {
			return;
		}

		wp_enqueue_style( 'ara-admin' );
		wp_enqueue_script( 'ara-admin' );
	}
}
