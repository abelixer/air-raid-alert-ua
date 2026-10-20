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
	 * Bundled Select2 version.
	 */
	const SELECT2_VERSION = '4.1.0-rc.0';

	/**
	 * Register hooks.
	 *
	 * @return void
	 */
	public static function init() {
		add_action( 'init', array( __CLASS__, 'register_assets' ), 5 );
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'enqueue_front' ), 20 );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue_admin' ) );
		add_action( 'customize_controls_enqueue_scripts', array( __CLASS__, 'enqueue_customizer' ) );
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
			'ara-select2',
			ARA_URL . 'admin/assets/vendor/select2/select2.min.css',
			array(),
			self::SELECT2_VERSION
		);

		wp_register_script(
			'ara-select2',
			ARA_URL . 'admin/assets/vendor/select2/select2.min.js',
			array( 'jquery' ),
			self::SELECT2_VERSION,
			true
		);

		wp_register_style(
			'ara-admin',
			ARA_URL . 'admin/assets/admin.css',
			array( 'ara-select2' ),
			ARA_VERSION
		);

		wp_register_script(
			'ara-admin',
			ARA_URL . 'admin/assets/admin.js',
			array( 'jquery', 'ara-select2' ),
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
	 * Admin assets on plugin screens and widget screens.
	 *
	 * @param string $hook_suffix Current admin page.
	 * @return void
	 */
	public static function enqueue_admin( $hook_suffix ) {
		$hook_suffix = (string) $hook_suffix;

		if ( false === strpos( $hook_suffix, 'air-raid-alert-ua' ) && 'widgets.php' !== $hook_suffix ) {
			return;
		}

		self::enqueue_admin_assets();
	}

	/**
	 * Admin assets in the Customizer, where the classic widget form is rendered.
	 *
	 * @return void
	 */
	public static function enqueue_customizer() {
		self::enqueue_admin_assets();
	}

	/**
	 * Enqueue the shared admin assets.
	 *
	 * @return void
	 */
	private static function enqueue_admin_assets() {
		wp_enqueue_style( 'ara-admin' );
		wp_enqueue_script( 'ara-admin' );

		wp_localize_script(
			'ara-admin',
			'araAdminData',
			array(
				'noResults'     => __( 'No results found.', 'air-raid-alert-ua' ),
				'searching'     => __( 'Searching…', 'air-raid-alert-ua' ),
				'inputTooShort' => __( 'Please enter 1 or more characters.', 'air-raid-alert-ua' ),
			)
		);
	}
}
