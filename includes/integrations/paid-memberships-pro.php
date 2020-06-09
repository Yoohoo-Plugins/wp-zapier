<?php

namespace Yoohoo\WPZapier;

class PaidMembershipsPro{

public function __construct() {
    add_filter( 'wp_zapier_event_hook_filter', array($this, 'add_hooks' ), 10, 1);
    add_filter( 'wp_zapier_hydrate_extender', array( $this, 'hydrate_extender' ), 10, 2 );
    add_filter( 'wp_zapier_base_object_extender', array( $this, 'filter_object_data' ), 10, 3 );
}

/**
 * Add Paid Memberships Pro hooks to our Outbound Events.
 */
public function add_hooks( $hooks ) {

    $pmpro_hooks = array(
        'pmpro_after_change_membership_level' => array(
            'name' => 'PMPro - After Change Level'
        ),
        'pmpro_added_order' => array(
            'name' => 'PMPro - Order Added'
        ),
        'pmpro_updated_order' => array(
            'name' => 'PMPro - Updated Order'
        )
    );

    $hooks = array_merge( $hooks, $pmpro_hooks );

    return $hooks;
}

/**
 * Function to pre-populate and format data that is needed.
 * Requires to be associative array.
 */
public function hydrate_extender( $data, $hook ) {

    $tmp_data = array();

    if ( $hook == 'pmpro_after_change_membership_level' ) {
        $tmp_data = array();
        $tmp_data['user'] = get_user_by( 'ID', $data[1] );
        $tmp_data['membership_level_id'] = $data[0];
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

    if ( $hook == 'pmpro_added_order' || $hook == 'pmpro_updated_order' ) {
        if ( is_a( $data, 'MemberOrder' ) ) {
            $data_arr = (array) $data;

            unset( $data_arr['Gateway'] );
            unset( $data_arr['sqlQuery'] );
            unset( $data_arr['id'] );
            unset( $data_arr['checkout_id'] );
            unset( $data_arr['session_id'] );

            $formatted = $data_arr;
        }
    }
    
    return apply_filters( 'wp_zapier_pmpro_formatted_object_data', $formatted, $data, $hook );
}

}

// Only instatiate the class if PMPro is active.
add_action( 'wp_zapier_integrations_loaded', function(){
if ( defined( 'PMPRO_VERSION' ) ) {
    $pmpro = new PaidMembershipsPro();
}
});