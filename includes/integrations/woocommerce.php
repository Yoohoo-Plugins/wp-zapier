<?php

namespace Yoohoo\WPZapier;

class WooCommerceEvents{
	public function __construct(){
		add_filter('wp_zapier_event_hook_filter', array($this, 'add_hooks'), 10, 1);
		add_filter('wp_zapier_base_object_extender', array($this, 'filter_object_data'), 10, 3);
	}

	public function add_hooks($hooks){
			$wooHooks = array(
				'woocommerce_new_order' => array(
					'name' => 'WooCommerce - New Order'
				),
				'woocommerce_order_status_changed' => array(
					'name' => 'WooCommerce - Order Status Changed'
				),
				'save_post_product' => array(
					'name' => 'WooCommerce - Save Product'
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
		} 

		return $formatted;
	}

} // End of Class

add_action('wp_zapier_integrations_loaded', function(){
	if ( class_exists('WooCommerce') ) {
		$woo = new WooCommerceEvents();
	}
});
