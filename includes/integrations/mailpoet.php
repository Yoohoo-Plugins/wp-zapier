<?php

/**
 * Integration for MailPoet.
 * Download Reference: https://wordpress.org/plugins/mailpoet/
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
            'mailpoet_subscriber_created' => array (
                'name' => __( 'MailPoet - Subscriber created', 'wp-zapier' )
            ),
            'mailpoet_subscriber_updated' => array (
                'name' => __( 'MailPoet - Subscriber updated', 'wp-zapier' )
            ),
            'mailpoet_subscriber_deleted' => array (
                'name' => __( 'MailPoet - Subscriber deleted', 'wp-zapier' )
            ),
            'mailpoet_subscription_before_subscribe' => array(
                'name' => __( 'MailPoet - Frontend form submission', 'wp-zapier' )
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

        // Subscriber created
        if ( $hook == 'mailpoet_subscriber_created' ) {
            $sub_id = (int) $data[0];

            $subscriber = $this->get_subscriber( $sub_id );

            $tmp_data = $subscriber;
            $tmp_data = apply_filters( "wp_zapier_{$hook}", $tmp_data, $data );
        }

        // Subscriber updated.
        if ( $hook == 'mailpoet_subscriber_updated' ) {
            $sub_id = (int) $data[0];

            $subscriber = $this->get_subscriber( $sub_id );

            $tmp_data = $subscriber;
            $tmp_data = apply_filters( "wp_zapier_{$hook}", $tmp_data, $data );
        }

        // Subscriber deleted.
        if ( $hook == 'mailpoet_subscriber_deleted' ) {
            $sub_id = (int) $data[0];

            $subscriber = $this->get_subscriber( $sub_id );

            $tmp_data = $subscriber;
            $tmp_data = apply_filters( "wp_zapier_{$hook}", $tmp_data, $data );
        }
        
        if ( is_array( $tmp_data ) && ! empty( $tmp_data ) ) {
            $data = $tmp_data;
        }
        
        return $data;
    }

    /**
     * Register arguments for flow logic.
     * 
     * @param array $arguments
     * 
     * @return array
     * 
     * @since 2.4
     */
    public function register_flow_logic_arguments( $arguments ) {
        $arguments['mailpoet_subscription_before_subscribe'] = array( 'email' => 'Email' );
        $arguments['mailpoet_subscriber_created'] = array( 
            'email' => 'Email',
            'first_name' => 'First Name',
            'last_name' => 'Last Name',
            'subscriber_status' => 'Subscriber Status',
            'created_at' => 'Created At',
            'updated_at' => 'Updated At',
            'confirmed_at' => 'Confirmed At',
            'source' => 'Source',
            'last_subscribed_at' => 'Last Subscribed At',
            'wp_user_id' => 'WP User ID',
            'is_woocommerce_user' => 'Is WooCommerce User'
         );
         $arguments['mailpoet_subscriber_updated'] = array( 
            'email' => 'Email',
            'first_name' => 'First Name',
            'last_name' => 'Last Name',
            'subscriber_status' => 'Subscriber Status',
            'created_at' => 'Created At',
            'updated_at' => 'Updated At',
            'confirmed_at' => 'Confirmed At',
            'source' => 'Source',
            'last_subscribed_at' => 'Last Subscribed At',
            'wp_user_id' => 'WP User ID',
            'is_woocommerce_user' => 'Is WooCommerce User'
         );
         $arguments['mailpoet_subscriber_deleted'] = array( 
            'email' => 'Email',
            'first_name' => 'First Name',
            'last_name' => 'Last Name',
            'subscriber_status' => 'Subscriber Status',
            'created_at' => 'Created At',
            'updated_at' => 'Updated At',
            'confirmed_at' => 'Confirmed At',
            'source' => 'Source',
            'last_subscribed_at' => 'Last Subscribed At',
            'wp_user_id' => 'WP User ID',
            'is_woocommerce_user' => 'Is WooCommerce User'
         );
        return $arguments;
    }

    /**
     * Helper function to get subscriber data from MailPoet.
     * @param int $sub_id
     * 
     * @return array
     * 
     * @since 2.4
     */
    public function get_subscriber( $sub_id ) {
        if ( class_exists(\MailPoet\API\API::class)) {
            $sub_data = array();
            $mailpoet_api = \MailPoet\API\API::MP('v1');

            $subscriber = $mailpoet_api->getSubscriber($sub_id);

            // Get specifici subscriber information here.
            $sub_data['email'] = $subscriber['email'];
            $sub_data['first_name'] = $subscriber['first_name'];
            $sub_data['last_name'] = $subscriber['last_name'];
            $sub_data['subscriber_status'] = $subscriber['status'];
            $sub_data['subscriptions'] = json_encode( $subscriber['subscriptions'] );
            $sub_data['created_at'] = $subscriber['created_at'];
            $sub_data['updated_at'] = $subscriber['updated_at'];
            $sub_data['confirmed_at'] = $subscriber['confirmed_at'];
            $sub_data['source'] = $subscriber['source'];
            $sub_data['last_subscribed_at'] = $subscriber['last_subscribed_at'];
            $sub_data['wp_user_id'] = $subscriber['wp_user_id'];
            $sub_data['is_woocommerce_user'] = $subscriber['is_woocommerce_user'];

            $sub_data = apply_filters( 'wp_zapier_mailpoet_subscriber_data', $sub_data, $subscriber );
            
            return $sub_data;
        }
    }

}

add_action( 'wp_zapier_integrations_loaded', function(){
    if ( function_exists( 'mailpoet_deactivate_plugin' ) ) {
        $lifter_lms = new MailPoet();
    }
});