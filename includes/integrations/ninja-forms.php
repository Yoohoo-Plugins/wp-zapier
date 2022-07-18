<?php

namespace Yoohoo\WPZapier;

class NinjaForms{

public function __construct() {
    add_filter( 'wp_zapier_event_hook_filter', array($this, 'add_hooks' ), 10, 1);
    add_filter( 'wp_zapier_hydrate_extender', array( $this, 'hydrate_extender' ), 10, 2 );
    add_filter( 'wp_zapier_base_object_extender', array( $this, 'filter_object_data' ), 10, 3 );

    add_filter('wp_zapier_flow_logic_argument_filter', array($this, 'register_flow_logic_arguments'));
}

/**
 * Add Paid Memberships Pro hooks to our Outbound Events.
 */
public function add_hooks( $hooks ) {
    // This is to ensure we're only on the backend, otherwise it interferes with frontend Ninja Forms login.
    $page = isset( $_REQUEST['post_type'] ) ? sanitize_text_field( $_REQUEST['post_type'] ) : false;
    
    if ( $page !== 'outbound_event' ) {
        return $hooks;
    }
    
    $nf_hooks = array();

    $nf_hooks['ninja_forms_submit_data'] = array(
        'name' => __( 'Ninja Forms - After Submission', 'wp-zapier' )
    );


    $hooks = array_merge( $hooks, $nf_hooks );

    return $hooks;
}

/**
 * Function to pre-populate and format data that is needed.
 * Requires to be associative array.
 */
public function hydrate_extender( $data, $hook ) {

    $tmp_data = array();

    if( strpos( $hook, 'ninja_forms_submit_data' ) !== false ) {

        $tmp_data = array();
        
        // error_log(print_r( $data, true ) );

        if( !empty( $data[0] ) ){

            $tmp_data['form_id'] = $data[0]['id'];

            $tmp_data['fields'] = array();

            foreach( $data[0]['fields'] as $field ){
                $tmp_data['fields'][$field['key']] = $field['value'];
            }
        }

        $tmp_data = apply_filters( "wp_zapier_{$hook}", $tmp_data, $data );

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
  
    return apply_filters( 'wp_zapier_ninja_forms_formatted_object_data', $formatted, $data, $hook );

}

public function register_flow_logic_arguments($arguments){
    $arguments['ninja_forms_submit_data'] = array(
        'form_id' => 'Form ID'
    );

    return $arguments;
}

}

// Only instatiate the class if PMPro is active.
add_action( 'wp_zapier_integrations_loaded', function(){
if ( class_exists( 'Ninja_Forms' ) ) {
    $ninja_forms = new NinjaForms();
}
});

