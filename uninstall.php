<?php
/**
 * Uninstall routine.
 *
 * @package AirRaidAlertUA
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

/**
 * Clean up one site, respecting its delete_data setting.
 *
 * @return void
 */
function ara_uninstall_site() {
	$settings = get_option( 'ara_settings', array() );

	wp_clear_scheduled_hook( 'ara_refresh_regions' );

	if ( is_array( $settings ) && ! empty( $settings['delete_data'] ) ) {
		delete_option( 'ara_settings' );
		delete_option( 'ara_regions_data_v1' );
		delete_transient( 'ara_regions_cache_v1' );
	}
}

if ( is_multisite() ) {
	$network_settings    = get_site_option( 'ara_network_settings', array() );
	$delete_network_data = is_array( $network_settings ) && ! empty( $network_settings['delete_data'] );
	$sites               = get_sites(
		array(
			'fields' => 'ids',
			'number' => 0,
		)
	);

	foreach ( $sites as $site_id ) {
		switch_to_blog( (int) $site_id );
		$site_settings = get_option( 'ara_settings', array() );
		if ( is_array( $site_settings ) && ! empty( $site_settings['delete_data'] ) ) {
			$delete_network_data = true;
		}
		ara_uninstall_site();
		restore_current_blog();
	}

	if ( is_array( $network_settings ) && ! empty( $network_settings['delete_data'] ) ) {
		delete_site_option( 'ara_network_settings' );
	}

	if ( $delete_network_data ) {
		delete_site_option( 'ara_regions_data_v1' );
		delete_site_transient( 'ara_regions_cache_v1' );
		delete_site_transient( 'ara_regions_refresh_lock' );
	}
} else {
	ara_uninstall_site();
}
