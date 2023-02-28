<?php

/**
 * Integration for Sensei LMS Outbound Events.
 * Download Reference: https://wordpress.org/plugins/sensei-lms/ 
 */

namespace Yoohoo\WPZapier;

class MailPoet {

    public function __construct() {
        add_filter( 'wp_zapier_event_hook_filter', array( $this, 'add_hooks' ), 10, 1 );
        add_filter( 'wp_zapier_hydrate_extender', array( $this, 'hydrate_extender' ), 10, 2 );

        add_filter( 'wp_zapier_flow_logic_argument_filter', array( $this, 'register_flow_logic_arguments' ) );
    }

    function add_hooks( $hooks ) {

        $new_hooks = array(
            'mailpoet_subscription_before_subscribe' => array(
                'name' => __( '[BETA] MailPoet - User Subscribed (via Form)', 'wp-zapier' )
            )
        );

        $hooks = array_merge( $hooks, $new_hooks );

        return $hooks;
    }

    public function hydrate_extender( $data, $hook ) {

        $tmp_data = array();
       
        if ( $hook == 'mailpoet_subscription_before_subscribe' ) { 
            $tmp_data['email'] = sanitize_email( $data[0] );
            $tmp_data = apply_filters( "wp_zapier_{$hook}", $tmp_data, $data );
        }
        
        if ( is_array( $tmp_data ) && ! empty( $tmp_data ) ) {
            $data = $tmp_data;
        }
        
        return $data;
    }

    //To update.
    public function register_flow_logic_arguments( $arguments ) {
        $arguments['mailpoet_subscription_before_subscribe'] = array( 'email' => 'Email' );
        return $arguments;
    }

}

add_action( 'wp_zapier_integrations_loaded', function(){
    if ( function_exists( 'mailpoet_deactivate_plugin' ) ) {
        $lifter_lms = new MailPoet();
    }
});