<?php
/**
 * Admin settings pages and form handlers.
 *
 * @package AirRaidAlertUA
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Registers the site and network settings pages.
 */
class ARA_Admin {

	/**
	 * Site settings page slug.
	 */
	const SITE_SLUG = 'air-raid-alert-ua';

	/**
	 * Network settings page slug.
	 */
	const NETWORK_SLUG = 'air-raid-alert-ua-network';

	/**
	 * Register hooks.
	 *
	 * @return void
	 */
	public static function init() {
		add_action( 'admin_menu', array( __CLASS__, 'menu' ) );
		add_action( 'network_admin_menu', array( __CLASS__, 'network_menu' ) );
		add_action( 'admin_post_ara_save_settings', array( __CLASS__, 'save_site' ) );
		add_action( 'admin_post_ara_save_network', array( __CLASS__, 'save_network' ) );
		add_action( 'admin_post_ara_refresh_regions', array( __CLASS__, 'refresh_regions' ) );
		add_filter( 'plugin_action_links_' . ARA_BASENAME, array( __CLASS__, 'plugin_links' ) );
	}

	/**
	 * Register admin menus.
	 *
	 * @return void
	 */
	public static function menu() {
		add_options_page(
			__( 'Air Raid Alert UA', 'air-raid-alert-ua' ),
			__( 'Air Raid Alert UA', 'air-raid-alert-ua' ),
			'manage_options',
			self::SITE_SLUG,
			array( __CLASS__, 'render_site_page' )
		);
	}

	/**
	 * Register the network settings page.
	 *
	 * @return void
	 */
	public static function network_menu() {
		add_submenu_page(
			'settings.php',
			__( 'Air Raid Alert UA', 'air-raid-alert-ua' ),
			__( 'Air Raid Alert UA', 'air-raid-alert-ua' ),
			'manage_network_options',
			self::NETWORK_SLUG,
			array( __CLASS__, 'render_network_page' )
		);
	}

	/**
	 * Site settings page.
	 *
	 * @return void
	 */
	public static function render_site_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to access this page.', 'air-raid-alert-ua' ) );
		}

		self::render_page( 'site' );
	}

	/**
	 * Network settings page.
	 *
	 * @return void
	 */
	public static function render_network_page() {
		if ( ! current_user_can( 'manage_network_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to access this page.', 'air-raid-alert-ua' ) );
		}

		self::render_page( 'network' );
	}

	/**
	 * Render a settings page.
	 *
	 * @param string $context "site" or "network".
	 * @return void
	 */
	private static function render_page( $context ) {
		$is_network = 'network' === $context;

		if ( $is_network ) {
			$settings = ARA_Settings::get_network_settings();
			$inherit  = false;
		} else {
			$settings = ARA_Settings::get_effective();
			$inherit  = ARA_Settings::inherits_network();

			if ( is_multisite() ) {
				$site_settings = ARA_Settings::get_site_settings();
				$settings['delete_data'] = isset( $site_settings['delete_data'] ) ? (int) $site_settings['delete_data'] : 0;
			}
		}

		$notice       = self::get_notice();
		$last_refresh = ARA_Regions::get_last_refresh_info();
		$form_action  = admin_url( 'admin-post.php' );
		$refresh_url = add_query_arg(
			array(
				'action'  => 'ara_refresh_regions',
				'context' => $context,
			),
			$form_action
		);
		$refresh_url = wp_nonce_url( $refresh_url, 'ara_refresh_regions' );

		include ARA_DIR . 'admin/views/settings-page.php';
	}

	/**
	 * Save site settings.
	 *
	 * @return void
	 */
	public static function save_site() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to save these settings.', 'air-raid-alert-ua' ) );
		}

		check_admin_referer( 'ara_save_settings' );

		$input    = isset( $_POST['ara_settings'] ) ? wp_unslash( $_POST['ara_settings'] ) : array();
		$settings = ARA_Settings::sanitize( $input, 'site' );

		update_option( ARA_Settings::SITE_OPTION, $settings );

		self::set_notice( 'success', __( 'Settings saved.', 'air-raid-alert-ua' ) );
		wp_safe_redirect( admin_url( 'options-general.php?page=' . self::SITE_SLUG ) );
		exit;
	}

	/**
	 * Save network settings.
	 *
	 * @return void
	 */
	public static function save_network() {
		if ( ! current_user_can( 'manage_network_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to save these settings.', 'air-raid-alert-ua' ) );
		}

		check_admin_referer( 'ara_save_network' );

		$input    = isset( $_POST['ara_settings'] ) ? wp_unslash( $_POST['ara_settings'] ) : array();
		$settings = ARA_Settings::sanitize( $input, 'network' );

		update_site_option( ARA_Settings::NETWORK_OPTION, $settings );

		self::set_notice( 'success', __( 'Network defaults saved.', 'air-raid-alert-ua' ) );
		wp_safe_redirect( network_admin_url( 'settings.php?page=' . self::NETWORK_SLUG ) );
		exit;
	}

	/**
	 * Refresh the region list.
	 *
	 * @return void
	 */
	public static function refresh_regions() {
		if ( ! current_user_can( 'manage_options' ) && ! current_user_can( 'manage_network_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to refresh the region list.', 'air-raid-alert-ua' ) );
		}

		check_admin_referer( 'ara_refresh_regions' );

		$context_raw = isset( $_GET['context'] ) && is_scalar( $_GET['context'] ) ? wp_unslash( $_GET['context'] ) : '';
		$context     = 'network' === sanitize_key( (string) $context_raw ) ? 'network' : 'site';
		$result  = ARA_Regions::refresh();

		self::set_notice( $result['success'] ? 'success' : 'warning', $result['message'] );

		$url = 'network' === $context && current_user_can( 'manage_network_options' )
			? network_admin_url( 'settings.php?page=' . self::NETWORK_SLUG )
			: admin_url( 'options-general.php?page=' . self::SITE_SLUG );

		wp_safe_redirect( $url );
		exit;
	}

	/**
	 * Add a settings link on the Plugins screen.
	 *
	 * @param array $links Existing links.
	 * @return array
	 */
	public static function plugin_links( $links ) {
		if ( current_user_can( 'manage_options' ) ) {
			$links[] = '<a href="' . esc_url( admin_url( 'options-general.php?page=' . self::SITE_SLUG ) ) . '">' . esc_html__( 'Settings', 'air-raid-alert-ua' ) . '</a>';
		}

		return $links;
	}

	/**
	 * Store an admin notice for the current user.
	 *
	 * @param string $type    success|warning|error|info.
	 * @param string $message Message.
	 * @return void
	 */
	private static function set_notice( $type, $message ) {
		set_transient(
			'ara_admin_notice_' . get_current_user_id(),
			array(
				'type'    => $type,
				'message' => $message,
			),
			MINUTE_IN_SECONDS
		);
	}

	/**
	 * Read and delete the current user's admin notice.
	 *
	 * @return array|null
	 */
	private static function get_notice() {
		$key    = 'ara_admin_notice_' . get_current_user_id();
		$notice = get_transient( $key );

		if ( ! is_array( $notice ) ) {
			return null;
		}

		delete_transient( $key );

		return $notice;
	}
}
