<?php

namespace Yoohoo\WPZapier;

class UltimateMember {

    public function __construct() {
        add_filter( 'wp_zapier_event_hook_filter', array( $this, 'add_hooks' ), 10, 1);
        add_filter( 'wp_zapier_hydrate_extender', array( $this, 'hydrate_extender' ), 10, 2 );
        // add_filter( 'wp_zapier_base_object_extender', array( $this, 'filter_object_data' ), 10, 3 );
    }

    /**
     * Add Ultimate Member hooks to our Outbound Events.
     */
    public function add_hooks( $hooks ) {

        $new_hooks = array(
            'um_registration_complete' => array(
                'name' => 'UM - User Registered'
            ),
            'um_delete_user' => array(
                'name' => 'UM - User Deleted'
            ),
            'um_after_user_is_inactive' => array(
                'name' => 'UM - User Deactivated'
            ),
            'um_after_user_is_approved' => array(
                'name' => 'UM - User Approved'
            ),
            'um_when_status_is_set' => array(
                'name' => 'UM - User Status Changed'
            ),
            'um_after_email_confirmation' => array(
                'name' => 'UM - User Email Confirmed'
            ),
            'um_after_user_role_is_updated' => array(
                'name' => 'UM - User Role Updated'
            ),
            'um_after_member_role_upgrade' => array(
                'name' => 'UM - User Role Upgraded'
            )
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

        if ( $hook == 'um_registration_complete' ) {
            $tmp_data['user_id'] = $data[0];

            $registration_fields = $data[1]['submitted'];

            // Let's remove the nonce and passwords.
            unset( $registration_fields['_wpnonce'] );
            unset( $registration_fields['user_password'] );
            unset( $registration_fields['confirm_user_password'] );

            $tmp_data['registration_fields'] = $registration_fields;

        }

        if ( $hook == 'um_delete_user' ) {
            $tmp_data['user'] = get_user_by( 'ID', $data[0] );
        }

        if ( $hook == 'um_after_user_is_inactive' ) {
            $tmp_data['user'] = get_user_by( 'ID', $data[0] );
        }

        if ( $hook == 'um_after_user_is_approved' ) {
            $tmp_data['user'] = get_user_by( 'ID', $data[0] );
        }

        if ( $hook == 'um_when_status_is_set' ) {
            $tmp_data['user'] = get_user_by( 'ID', $data[0] );
        }

        if ( $hook == 'um_after_email_confirmation' ) {
            $tmp_data['user'] = get_user_by( 'ID', $data[0] );
        }

        if ( $hook == 'um_after_user_role_is_updated' ) {
            $tmp_data['user'] = get_user_by( 'ID', $data[0] );
        }

        if ( $hook == 'um_after_member_role_upgrade' ) {
            $tmp_data['user'] = get_user_by( 'ID', $data[2] );
            $tmp_data['old_roles'] = $data[1];
            $tmp_data['new_roles'] = $data[0];
        }

        if ( is_array( $tmp_data ) && ! empty( $tmp_data ) ) {
            $data = $tmp_data;
        }

        return $data;
    }

} //end of class.

// Only instatiate the class if PMPro is active.
add_action( 'wp_zapier_integrations_loaded', function(){
    if ( defined( 'um_plugin' ) ) {
        $ultimate_member = new UltimateMember();
    }
});