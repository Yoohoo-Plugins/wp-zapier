<?php

namespace Yoohoo\WPZapier;

class ContactForm7{

public function __construct() {
    add_filter( 'wp_zapier_event_hook_filter', array($this, 'add_hooks' ), 10, 1);
    add_filter( 'wp_zapier_hydrate_extender', array( $this, 'hydrate_extender' ), 10, 2 );
    add_filter( 'wp_zapier_base_object_extender', array( $this, 'filter_object_data' ), 10, 3 );
}

/**
 * Add Paid Memberships Pro hooks to our Outbound Events.
 */
public function add_hooks( $hooks ) {

    $cf7_hooks = array();

    $cf7_hooks['wpcf7_before_send_mail'] = array(
        'name' => __( 'Contact Form 7 - ALL Forms - Before Mail Sent', 'wp-zapier' )
    );

    $cf7_hooks['wpcf7_mail_sent'] = array(
        'name' => __( 'Contact Form 7 - ALL Forms - After Mail Sent', 'wp-zapier' )
    );

    $hooks = array_merge( $hooks, $cf7_hooks );

    return $hooks;
}

/**
 * Function to pre-populate and format data that is needed.
 * Requires to be associative array.
 */
public function hydrate_extender( $data, $hook ) {

    $tmp_data = array();

    if( $hook == 'wpcf7_before_send_mail' ) {

        $tmp_data['form_id'] = ( isset( $_POST['_wpcf7'] ) ) ? $_POST['_wpcf7'] : 0;

        if( !empty( $_POST ) ) { 
            foreach( $_POST as $key => $val ) {
                if( strpos( $key, '_wpcf7' ) !== FALSE ) {
                    //don't record this
                } else {
                    $tmp_data[$key] = $val;
                }
            }
        }

        $tmp_data = apply_filters( "wp_zapier_{$hook}", $tmp_data, $data );

    }

    if( $hook == 'wpcf7_mail_sent' ){

        $tmp_data['form_id'] = ( isset( $_POST['_wpcf7'] ) ) ? $_POST['_wpcf7'] : 0;

        if( !empty( $_POST ) ) { 
            foreach( $_POST as $key => $val ) {
                if( strpos( $key, '_wpcf7' ) !== FALSE ) {
                    //don't record this
                } else {
                    $tmp_data[$key] = $val;
                }
            }
        }

        error_log( print_r( $tmp_data, true ) );

    }

    if ( is_array( $tmp_data ) && ! empty( $tmp_data ) ) {
        $data = $tmp_data;
    }

    return $data;
}

/**
 * Function to filter data to only pass necessary data.
 * Take out items that we don't really need.
 */
public function filter_object_data( $formatted, $data, $hook ) {
  
    return apply_filters( 'wp_zapier_gravity_forms_formatted_object_data', $formatted, $data, $hook );

}

}

// Only instatiate the class if PMPro is active.
add_action( 'wp_zapier_integrations_loaded', function(){
if ( class_exists( 'WPCF7_ContactForm' ) ) {
    $cf7 = new ContactForm7();
}
});