<?php

namespace Yoohoo\WPZapier;

class WooCommerceEvents{
	public function __construct(){
		add_filter('wp_zapier_event_hook_filter', array($this, 'add_hooks'), 10, 1);
		add_filter('wp_zapier_base_object_extender', array($this, 'filter_object_data'), 10, 3);

        add_filter('wp_zapier_flow_logic_argument_filter', array($this, 'register_flow_logic_arguments'));
	}

	public function add_hooks($hooks){
			$wooHooks = array(
				'woocommerce_new_order' => array(
					'name' => __( 'WooCommerce - New Order', 'wp-zapier' )
				),
				'woocommerce_order_status_changed' => array(
					'name' => __( 'WooCommerce - Order Status Changed', 'wp-zapier' )
				),
				'save_post_product' => array(
					'name' => __( 'WooCommerce - Save Product', 'wp-zapier' )
				)
			);
			
			$hooks = array_merge($hooks, $wooHooks);

		return $hooks;
	}

	public function filter_object_data($formatted, $data, $hook){
		if(is_a($data, 'WC_Order')){
		
			$orderData = $data->get_data();
			$formatted['order_id'] = $orderData['id'];
			$formatted['order_key'] = $orderData['order_key'];
			$formatted['customer_id'] = $orderData['customer_id'];

			$formatted['currency'] = $orderData['currency'];
			$formatted['discount_total'] = $orderData['discount_total'];
			$formatted['discount_tax'] = $orderData['discount_tax'];
			$formatted['shipping_total'] = $orderData['shipping_total'];
			$formatted['shipping_tax'] = $orderData['shipping_tax'];
			$formatted['cart_tax'] = $orderData['cart_tax'];
			$formatted['total'] = $orderData['total'];
			$formatted['total_tax'] = $orderData['total_tax'];

			$formatted['billing'] = $orderData['billing'];
			$formatted['shipping'] = $orderData['shipping'];
			$formatted['payment_method'] = $orderData['payment_method'];
			$formatted['status'] = $orderData['status'];


			if ( function_exists( 'dokan_get_sellers_by' ) ) {	
				$vendors = dokan_get_sellers_by($data);
				if(count($vendors) > 0){
					$formatted['vendors'] = array();
					foreach ($vendors as $sellerID => $itemData) {
						$singleVendor = get_user_by('ID', $sellerID);

						$formatted['vendors'][] = array(
							'id' => $sellerID,
							'store_name' => $singleVendor->user_nicename,
							'email' => $singleVendor->user_email,
							'url' => $singleVendor->user_url,
							'display_name' => $singleVendor->display_name
						);
					}
				}
			}

			$tmp_data = apply_filters( "wp_zapier_{$hook}", $formatted, $orderData );
		}

		return $formatted;
	}

	public function register_flow_logic_arguments($arguments){
        $order = array(
			'order_id' => "Order ID",
			'order_key' => "Order Key",
			'customer_id' => "Customer ID",
			'currency' => "Currency",
			'discount_total' => "Discount Total",
			'discount_tax' => "Discount Tax",
			'shipping_total' => "Shipping Total",
			'shipping_tax' => "Shipping Tax",
			'cart_tax' => "Cart Tax",
			'total' => "Total",
			'total_tax' => "Tax",
			'billing' => "Billing",
			'shipping' => "Shipping",
			'payment_method' => "Payment Method",
			'status' => "Status",
		);

        $arguments['woocommerce_new_order'] = $order;
        $arguments['woocommerce_order_status_changed'] = $order;

        $arguments['save_post_product'] = $arguments['wp_zapier_save_post'];

        return $arguments;
    }

} // End of Class

add_action('wp_zapier_integrations_loaded', function(){
	if ( class_exists('WooCommerce') ) {
		$woo = new WooCommerceEvents();
	}
});
