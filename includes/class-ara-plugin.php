<?php
/**
 * Plugin bootstrap and lifecycle hooks.
 *
 * @package AirRaidAlertUA
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Main plugin coordinator.
 */
final class ARA_Plugin {

	/**
	 * Singleton instance.
	 *
	 * @var ARA_Plugin|null
	 */
	private static $instance = null;

	/**
	 * Get the singleton.
	 *
	 * @return ARA_Plugin
	 */
	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Wire the plugin modules.
	 */
	private function __construct() {
		add_action( 'init', array( __CLASS__, 'load_textdomain' ) );

		ARA_Assets::init();
		ARA_Shortcode::init();
		ARA_Blocks::init();
		ARA_Widget::init();

		if ( is_admin() ) {
			ARA_Admin::init();
		}

		add_action( 'ara_refresh_regions', array( 'ARA_Regions', 'refresh_cron' ) );
		add_action( 'init', array( __CLASS__, 'maybe_schedule_cron' ), 20 );
		add_action( 'wp_initialize_site', array( __CLASS__, 'initialize_new_site' ), 10, 2 );
	}

	/**
	 * Load translations.
	 *
	 * @return void
	 */
	public static function load_textdomain() {
		$locale     = determine_locale();
		$candidates = array( $locale );

		if ( 'uk_UA' === $locale ) {
			$candidates[] = 'uk';
		}

		foreach ( $candidates as $candidate ) {
			$mofile = ARA_DIR . 'languages/air-raid-alert-ua-' . $candidate . '.mo';

			if ( is_readable( $mofile ) ) {
				load_textdomain( 'air-raid-alert-ua', $mofile, $locale );
				break;
			}
		}

		load_plugin_textdomain(
			'air-raid-alert-ua',
			false,
			dirname( ARA_BASENAME ) . '/languages'
		);
	}

	/**
	 * Activation.
	 *
	 * @param bool $network_wide Whether the plugin was network-activated.
	 * @return void
	 */
	public static function activate( $network_wide = false ) {
		if ( is_multisite() && $network_wide ) {
			self::add_network_defaults();

			$sites = get_sites(
				array(
					'fields' => 'ids',
					'number' => 0,
				)
			);

			foreach ( $sites as $site_id ) {
				switch_to_blog( (int) $site_id );
				self::add_site_defaults();
				self::schedule_cron();
				restore_current_blog();
			}

			return;
		}

		self::add_site_defaults();
		self::schedule_cron();

		if ( is_multisite() ) {
			self::add_network_defaults();
		}
	}

	/**
	 * Deactivation.
	 *
	 * @param bool $network_wide Whether the plugin was network-deactivated.
	 * @return void
	 */
	public static function deactivate( $network_wide = false ) {
		if ( is_multisite() && $network_wide ) {
			$sites = get_sites(
				array(
					'fields' => 'ids',
					'number' => 0,
				)
			);

			foreach ( $sites as $site_id ) {
				switch_to_blog( (int) $site_id );
				wp_clear_scheduled_hook( 'ara_refresh_regions' );
				restore_current_blog();
			}

			return;
		}

		wp_clear_scheduled_hook( 'ara_refresh_regions' );
	}

	/**
	 * Re-schedule the region refresh if the cron event was lost.
	 *
	 * @return void
	 */
	public static function maybe_schedule_cron() {
		self::schedule_cron();
	}

	/**
	 * Prepare a newly created multisite site.
	 *
	 * @param WP_Site $site New site.
	 * @param array   $args Arguments.
	 * @return void
	 */
	public static function initialize_new_site( $site, $args = array() ) {
		if ( ! is_multisite() || ! $site instanceof WP_Site ) {
			return;
		}

		$network_plugins = (array) get_site_option( 'active_sitewide_plugins', array() );
		if ( ! isset( $network_plugins[ ARA_BASENAME ] ) ) {
			return;
		}

		switch_to_blog( (int) $site->blog_id );
		self::add_site_defaults();
		self::schedule_cron();
		restore_current_blog();
	}

	/**
	 * Add per-site defaults without overwriting existing settings.
	 *
	 * @return void
	 */
	private static function add_site_defaults() {
		if ( false === get_option( ARA_Settings::SITE_OPTION, false ) ) {
			$defaults            = ARA_Settings::defaults();
			$defaults['inherit'] = is_multisite() ? 1 : 0;
			add_option( ARA_Settings::SITE_OPTION, $defaults );
		}
	}

	/**
	 * Add network defaults without overwriting existing settings.
	 *
	 * @return void
	 */
	private static function add_network_defaults() {
		if ( false === get_site_option( ARA_Settings::NETWORK_OPTION, false ) ) {
			add_site_option( ARA_Settings::NETWORK_OPTION, ARA_Settings::defaults() );
		}
	}

	/**
	 * Schedule the daily region refresh.
	 *
	 * @return void
	 */
	private static function schedule_cron() {
		if ( ! wp_next_scheduled( 'ara_refresh_regions' ) ) {
			wp_schedule_event( time() + 5 * MINUTE_IN_SECONDS, 'daily', 'ara_refresh_regions' );
		}
	}
}
