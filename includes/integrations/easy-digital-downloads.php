<?php

namespace Yoohoo\WPZapier;

class EasyDigitalDownloads {

    public function __construct() {
        add_filter( 'wp_zapier_event_hook_filter', array( $this, 'add_hooks' ), 10, 1 );
        add_filter( 'wp_zapier_hydrate_extender', array( $this, 'hydrate_extender' ), 10, 2 );
    }

    function add_hooks( $hooks ) {

        $new_hooks = array(
            'edd_customer_post_create' => array(
                'name' => 'Easy Digital Downloads - Customer Created'
            ),
            'edd_customer_post_update' => array(
                'name' => 'Easy Digital Downloads - Customer Updated'
            ),
            'edd_download_post_create' => array(
                'name' => 'Easy Digital Downloads - Download Created'
            ),
            'edd_complete_purchase' => array(
                'name' => 'Easy Digital Downloads - User Completed a Payment'
            )

        );

        $hooks = array_merge( $hooks, $new_hooks );
        return $hooks;
    }

    function hydrate_extender( $data, $hook ) {

        $tmp_data = array();

        if ( $hook == 'edd_customer_post_create' ) {
            $tmp_data['customer_id'] = $data[0];
            $tmp_data['customer_data'] = $data[1]; 
        }

        if ( $hook == 'edd_customer_post_update' ) {
            $tmp_data['customer_id'] = $data[1];
            $tmp_data['customer_data'] = $data[2];
        }

        if ( $hook == 'edd_download_post_create' ) {
            $tmp_data['download_id'] = $data[0];
            $tmp_data['download_data'] = $data[1];
        }

        if ( $hook == 'edd_complete_purchase' ) {
            $tmp_data['payment_id'] = $data[0];
            $payment_data = edd_get_payment_meta( $data[0] );

            // remove some stuff.
            unset( $payment_data['key'] );
            unset( $payment_data['email'] );
            
            $tmp_data['payment_data'] = $payment_data;
        }



        $data = $tmp_data;
        return $data;
    }

}

add_action( 'wp_zapier_integrations_loaded', function(){
    if ( class_exists( 'Easy_Digital_Downloads' ) ) {
        $ssp = new EasyDigitalDownloads();
    }
});