<?php

namespace Yoohoo\WPZapier;

class GravityForms{

public function __construct() {
    add_filter( 'wp_zapier_event_hook_filter', array($this, 'add_hooks' ), 10, 1);
    add_filter( 'wp_zapier_hydrate_extender', array( $this, 'hydrate_extender' ), 10, 2 );
    add_filter( 'wp_zapier_base_object_extender', array( $this, 'filter_object_data' ), 10, 3 );
}

/**
 * Add Paid Memberships Pro hooks to our Outbound Events.
 */
public function add_hooks( $hooks ) {

    $forms   = \RGFormsModel::get_forms( null, 'title' );

    $gf_hooks = array();

    $gf_hooks['gform_after_submission'] = array(
        'name' => __( 'Gravity Forms - ALL Forms - After Submission', 'wp-zapier' )
    );

    if( $forms ){
        foreach( $forms as $form ){
            $gf_hooks['gform_after_submission_'.$form->id] = array( 'name' => sprintf( __( 'Gravity Forms - %s - After Submission', 'wp-zapier' ), $form->title ) );
        }
    }

    $hooks = array_merge( $hooks, $gf_hooks );

    return $hooks;
}

/**
 * Function to pre-populate and format data that is needed.
 * Requires to be associative array.
 */
public function hydrate_extender( $data, $hook ) {

    $tmp_data = array();

    if( strpos( $hook, 'gform_after_submission' ) !== false ) {

        $tmp_data = array();

        $tmp_counters = array();

        if(!empty($data)){
            foreach ( $data[1]['fields'] as $field ) {
                $inputs = $field->get_entry_inputs();
                if ( is_array( $inputs ) ) {
                    foreach ( $inputs as $input ) {
                        $value = rgar( $data[0], (string) $input['id'] );
                        if( !empty( $value ) ){
                            $tmp_data[$input['label']] = $value;
                        }
                    }
                } else {
                    $value = rgar( $data[0], (string) $field->id );
                    if( !empty( $value ) ){
                        $proxyLabel = $field->label;
                        if(!empty($tmp_data[$field->label])){
                            // Mimics multiform style, but not as an array, but a suffixed name
                            if(!isset($tmp_counters[$field->label])){
                                $tmp_counters[$field->label] = 0;
                            }
                            $tmp_counters[$field->label] += 1;

                            $proxyLabel .= "_" . $tmp_counters[$field->label];
                        }
                        $tmp_data[$proxyLabel] = $value;
                    }
                }
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
  
    return apply_filters( 'wp_zapier_gravity_forms_formatted_object_data', $formatted, $data, $hook );

}

}

// Only instatiate the class if PMPro is active.
add_action( 'wp_zapier_integrations_loaded', function(){
if ( class_exists( 'GFForms' ) ) {
    $pmpro = new GravityForms();
}
});