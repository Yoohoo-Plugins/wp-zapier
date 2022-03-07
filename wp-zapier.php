<?php
/**
 * Plugin Name: WP Zapier
 * Description: Automate your WordPress users with over 1000+ apps on Zapier.com
 * Plugin URI: https://yoohooplugins.com
 * Author: Yoohoo Plugins
 * Author URI: https://yoohooplugins.com
 * Version: 2.2
 * License: GPL2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: wp-zapier
 * Domain Path: /languages
 */

defined( 'ABSPATH' ) or exit;

/**
 * Include update class for automatic updates.
 */
if ( ! defined( 'YOOHOO_STORE' ) ) {
	define( 'YOOHOO_STORE', 'https://yoohooplugins.com/edd-sl-api/' );
}

define( 'WPZAP_URL', plugin_dir_url( __FILE__ ) );
define( 'WPZAP_PLUGIN_ID', 453 );
define( 'WPZAP_VERSION', '2.2' );

if ( ! class_exists( 'Yoohoo_Zapier_Update_Checker' ) ) {
	include( dirname( __FILE__ ) . '/includes/updates/zapier-update-checker.php' );
}

if (!class_exists('Yoohoo\WPZapier')) {
	include( dirname( __FILE__ ) . '/includes/yoohoo-wp-zapier.php' );
}

if ( ! class_exists( 'Yoohoo\WPZapier\OutboundEvents' ) ) {
	include( dirname( __FILE__ ) . '/includes/outbound-events.php' );
}

$license_key = trim( get_option( 'yoohoo_zapier_license_key' ) );

// setup the updater
$edd_updater = new Yoohoo_Zapier_Update_Checker( YOOHOO_STORE, __FILE__, array( 
		'version' => WPZAP_VERSION,
		'license' => $license_key,
		'item_id' => WPZAP_PLUGIN_ID,
		'author' => 'Yoohoo Plugins',
		'url' => home_url()
	)
);

$wpZapier = new Yoohoo\WPZapier();
