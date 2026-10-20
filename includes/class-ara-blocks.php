<?php
/**
 * Gutenberg block registration (no build step).
 *
 * @package AirRaidAlertUA
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Registers the dynamic block and its editor script.
 */
class ARA_Blocks {

	/**
	 * Block name.
	 */
	const NAME = 'ara/air-raid-alert';

	/**
	 * Register hooks.
	 *
	 * @return void
	 */
	public static function init() {
		add_action( 'init', array( __CLASS__, 'register' ), 15 );
		add_action( 'enqueue_block_editor_assets', array( __CLASS__, 'localize' ) );
	}

	/**
	 * Register the script and dynamic block.
	 *
	 * @return void
	 */
	public static function register() {
		if ( ! function_exists( 'register_block_type' ) ) {
			return;
		}

		wp_register_script(
			'ara-block',
			ARA_URL . 'blocks/widget/index.js',
			array(
				'wp-blocks',
				'wp-element',
				'wp-block-editor',
				'wp-components',
				'wp-i18n',
				'wp-server-side-render',
			),
			ARA_VERSION,
			true
		);

		wp_set_script_translations( 'ara-block', 'air-raid-alert-ua', ARA_DIR . 'languages' );

		register_block_type(
			ARA_DIR . 'blocks/widget',
			array(
				'render_callback' => array( __CLASS__, 'render' ),
			)
		);
	}

	/**
	 * Provide editor data only when the block editor is active.
	 *
	 * @return void
	 */
	public static function localize() {
		wp_localize_script(
			'ara-block',
			'araBlockData',
			array(
				'enabled'  => (bool) ara_is_enabled(),
				'regions'  => self::region_options(),
				'types'    => self::type_options(),
				'sizes'    => self::size_options(),
				'themes'   => self::theme_options(),
				'defaults' => ARA_Settings::get_effective(),
			)
		);
	}

	/**
	 * Server-side render callback.
	 *
	 * @param array $attributes Block attributes.
	 * @return string
	 */
	public static function render( $attributes ) {
		return ARA_Renderer::render( is_array( $attributes ) ? $attributes : array() );
	}

	/**
	 * Region choices for the inspector.
	 *
	 * @return array
	 */
	private static function region_options() {
		$options = array(
			array(
				'label' => __( '— Use plugin default —', 'air-raid-alert-ua' ),
				'value' => '0',
			),
		);

		foreach ( ARA_Regions::get_grouped() as $group ) {
			$oblast = $group['oblast'];
			$options[] = array(
				'label' => $oblast['name'],
				'value' => (string) $oblast['id'],
			);

			foreach ( $group['raions'] as $raion ) {
				$options[] = array(
					'label' => '— ' . $raion['raion']['name'],
					'value' => (string) $raion['raion']['id'],
				);

				foreach ( $raion['hromadas'] as $hromada ) {
					$options[] = array(
						'label' => '— — ' . $hromada['name'],
						'value' => (string) $hromada['id'],
					);
				}
			}

			foreach ( $group['hromadas'] as $hromada ) {
				$options[] = array(
					'label' => '— ' . $hromada['name'],
					'value' => (string) $hromada['id'],
				);
			}
		}

		return $options;
	}

	/**
	 * Type choices.
	 *
	 * @return array
	 */
	private static function type_options() {
		return array(
			array( 'label' => __( 'Card', 'air-raid-alert-ua' ), 'value' => 'card' ),
			array( 'label' => __( 'Line', 'air-raid-alert-ua' ), 'value' => 'line' ),
			array( 'label' => __( 'Map', 'air-raid-alert-ua' ), 'value' => 'map' ),
			array( 'label' => __( 'Stats', 'air-raid-alert-ua' ), 'value' => 'stats' ),
			array( 'label' => __( 'Whole Ukraine', 'air-raid-alert-ua' ), 'value' => 'ua' ),
		);
	}

	/**
	 * Size choices.
	 *
	 * @return array
	 */
	private static function size_options() {
		return array(
			array( 'label' => __( 'Small', 'air-raid-alert-ua' ), 'value' => 's' ),
			array( 'label' => __( 'Medium', 'air-raid-alert-ua' ), 'value' => 'm' ),
			array( 'label' => __( 'Large / 100%', 'air-raid-alert-ua' ), 'value' => 'l' ),
		);
	}

	/**
	 * Theme choices.
	 *
	 * @return array
	 */
	private static function theme_options() {
		return array(
			array( 'label' => __( 'Auto (system)', 'air-raid-alert-ua' ), 'value' => 'auto' ),
			array( 'label' => __( 'Light', 'air-raid-alert-ua' ), 'value' => 'light' ),
			array( 'label' => __( 'Dark', 'air-raid-alert-ua' ), 'value' => 'dark' ),
		);
	}
}
