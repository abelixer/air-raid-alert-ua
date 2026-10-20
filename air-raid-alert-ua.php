<?php
/**
 * Plugin Name:       Air Raid Alert UA
 * Plugin URI:        https://github.com/abelixer/air-raid-alert-ua
 * Description:       Embed air-raid alert widgets from alerts.na-agency.com using a shortcode, Gutenberg block, or classic widget.
 * Version:           1.0.0
 * Requires at least: 6.0
 * Requires PHP:      7.4
 * Author:            Abelixer
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       air-raid-alert-ua
 * Domain Path:       /languages
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'ARA_VERSION', '1.0.0' );
define( 'ARA_FILE', __FILE__ );
define( 'ARA_DIR', plugin_dir_path( __FILE__ ) );
define( 'ARA_URL', plugin_dir_url( __FILE__ ) );
define( 'ARA_BASENAME', plugin_basename( __FILE__ ) );

require_once ARA_DIR . 'includes/helpers.php';
require_once ARA_DIR . 'includes/class-ara-settings.php';
require_once ARA_DIR . 'includes/class-ara-regions.php';
require_once ARA_DIR . 'includes/class-ara-renderer.php';
require_once ARA_DIR . 'includes/class-ara-assets.php';
require_once ARA_DIR . 'includes/class-ara-shortcode.php';
require_once ARA_DIR . 'includes/class-ara-blocks.php';
require_once ARA_DIR . 'includes/class-ara-widget.php';
require_once ARA_DIR . 'includes/class-ara-admin.php';
require_once ARA_DIR . 'includes/class-ara-plugin.php';

register_activation_hook( __FILE__, array( 'ARA_Plugin', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'ARA_Plugin', 'deactivate' ) );

/**
 * Main plugin accessor.
 *
 * @return ARA_Plugin
 */
function ara_plugin() {
	return ARA_Plugin::instance();
}

ara_plugin();
