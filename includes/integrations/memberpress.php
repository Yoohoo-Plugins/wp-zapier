<?php

namespace Yoohoo\WPZapier;

class MemberPress {

    public function __construct() {
        add_filter( 'wp_zapier_event_hook_filter', array( $this, 'add_hooks' ), 10, 1);
        add_filter( 'wp_zapier_hydrate_extender', array( $this, 'hydrate_extender' ), 10, 2 );
    }

    /**
     * Add MemberPress hooks to our Outbound Events.
     */
    public function add_hooks( $hooks ) {

        $new_hooks = array(
            'mepr-event-member-added' => array(
                'name' => __( 'MemberPress - Member Added', 'wp-zapier' )
            ),
            'mepr-event-member-signup-completed' => array(
                'name' => __( 'MemberPress - Initial Signup (First Time Member)', 'wp-zapier' )
            ),
            'mepr-event-subscription-created' => array(
                'name' => __( 'MemberPress - Subscription Created', 'wp-zapier' )
            ),
            'mepr-event-transaction-completed' => array(
                'name' => __( 'MemberPress - Transaction Completed', 'wp-zapier' )
            ),
        );

        $hooks = array_merge( $hooks, $new_hooks );

        return $hooks;
    }

    /**
     * Function to pre-populate and format data that is needed.
     * Requires to be associative array.
     */
    public function hydrate_extender( $data, $hook ) {

        $tmp_data = array();

        if ( $hook == 'mepr-event-member-added' ) {
            $member_data = $data[0]->get_data();
            $membership_data = json_decode( $data[0]->args );
            $tmp_data['member_info'] = (array) $member_data->rec;
            $tmp_data = apply_filters( "wp_zapier_{$hook}", $tmp_data, $data );
        }

        if ( $hook == 'mepr-event-member-signup-completed' ) {
            $member_data = $data[0]->get_data();
            $membership_data = json_decode( $data[0]->args );
            $tmp_data['member_info'] = (array) $member_data->rec;
            $tmp_data['membership_info'] = (array) $membership_data;
            $tmp_data = apply_filters( "wp_zapier_{$hook}", $tmp_data, $data );
        }

        if ( $hook == 'mepr-event-subscription-created' ) {
            $membership_data = $data[0]->get_data();
            $member_data = get_user_by( 'ID', $membership_data->user_id );
            $tmp_data['member_info'] = (array) $member_data->data;
            unset( $tmp_data['member_info']['user_pass'] ); //remove pass.
            $tmp_data['membership_info'] = (array) $membership_data->rec;
            $tmp_data = apply_filters( "wp_zapier_{$hook}", $tmp_data, $data );
        }

        if ( $hook == 'mepr-event-transaction-completed' ) {
            $membership_data = $data[0]->get_data();
            $member_data = get_user_by( 'ID', $membership_data->user_id );
            $tmp_data['member_info'] = (array) $member_data->data;
            unset( $tmp_data['member_info']['user_pass'] ); //remove pass.
            $tmp_data['membership_info'] = (array) $membership_data->rec;
            $tmp_data = apply_filters( "wp_zapier_{$hook}", $tmp_data, $data );
        }

        if ( is_array( $tmp_data ) && ! empty( $tmp_data ) ) {
            $data = $tmp_data;
        }

        return $data;
    }

} //end of class.

// Only instatiate the class if MemberPress is active.
add_action( 'wp_zapier_integrations_loaded', function(){
    if ( defined( 'MEPR_PLUGIN_SLUG' ) ) {
        $ultimate_member = new MemberPress();
    }
});