<?php
if ( !defined( 'WP_UNINSTALL_PLUGIN' ) ) {
    exit();
}

delete_option( 'wp_zapier_settings' );
delete_option( 'yoohoo_zapier_license_key' );
delete_option( 'yoohoo_zapier_license_status' );
delete_option( 'yoohoo_zapier_license_expires' );