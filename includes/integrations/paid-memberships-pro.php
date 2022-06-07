<?php

namespace Yoohoo\WPZapier;

class PaidMembershipsPro{

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

        $pmpro_hooks = array(
            'pmpro_after_change_membership_level' => array(
                'name' => __( 'PMPro - After Change Level', 'wp-zapier' )
            ),
            'pmpro_added_order' => array(
                'name' => __( 'PMPro - Order Added', 'wp-zapier' )
            ),
            'pmpro_updated_order' => array(
                'name' => __( 'PMPro - Updated Order', 'wp-zapier' )
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

    public function register_flow_logic_arguments($arguments){
        $order = array(
			'code' => "Code",
            'user_id' => "User ID",
			'membership_id' => "Membership ID",
			'Email' => "Email",
			'FirstName' => "First Name",
			'LastName' => "Last Name",
			'gateway' => "Gateway",
			'subtotal' => "Sub Total",
			'tax' => "Tax",
			'total' => "Total",
			'status' => "Status",
			'datetime' => "Order Date/Time",
			'ExpirationDate' => "Expiration Date",
			'affiliate_id' => "Affiliate ID",
			'affiliate_subid' => "Affiliate Sub ID",
            'billing.name' => "Name",
            'billing.street' => "Street",
            'billing.city' => "City",
            'billing.state' => "State",
            'billing.zip' => "Zip",
            'billing.country' => "Country",
            'billing.phone' => "Phone",
		);

        $arguments['pmpro_added_order'] = $order;
        $arguments['pmpro_updated_order'] = $order;

        $arguments['pmpro_after_change_membership_level'] = array(
            'membership_level_id' => "Membership Level ID"
        );

        $userCopy = $arguments['profile_update'];
        if(!empty($userCopy)){
            foreach($userCopy as $key => $label){
                $arguments['pmpro_after_change_membership_level']["user.{$key}"] = "{$label}";
            }
        }

        return $arguments;
    }

}

// Only instatiate the class if PMPro is active.
add_action( 'wp_zapier_integrations_loaded', function(){
if ( defined( 'PMPRO_VERSION' ) ) {
    $pmpro = new PaidMembershipsPro();
}
});