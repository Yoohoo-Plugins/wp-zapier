<?php
/**
 * Plugin Name: When Last Login - Zapier Add-on
 * Description: Integrate into Zapier on user registration and user login.
 * Plugin URI: https://yoohooplugins.com
 * Author: YooHoo Plugins
 * Author URI: https://yoohooplugins.com
 * Version: 1.0
 * License: GPL2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: when-last-login-zapier
 */

defined( 'ABSPATH' ) or exit;

class WhenLastLoginZapier{

	public function __construct(){

		add_filter( 'wll_settings_page_tabs', array( $this, 'wll_zapier_tabs' ) );
		add_filter( 'wll_settings_page_content', array( $this, 'wll_zapier_content' ) );
		add_action( 'admin_head', array( $this, 'wll_zapier_save_settings' ) );
		
		add_action( 'wp_login', array( $this, 'wll_zapier_login' ), 10, 2 );
		add_action( 'user_register', array( $this, 'wll_zapier_register' ), 10, 1 );
		add_action( 'profile_update', array( $this, 'wll_zapier_profile_update' ), 10, 2 );

	}	

	public function wll_zapier_tabs( $tabs ){

		$tabs['zapier-integration'] = array(
			'title' => __('Zapier Integration', 'when-last-login-stats'),
			'icon' => ''
		);

		return $tabs;

	}

	public function wll_zapier_content( $content ){

		$content['zapier-integration'] = plugin_dir_path( __FILE__ ).'when-last-login-zapier-integration-settings.php';

		return $content;

	}

	public function wll_zapier_save_settings(){

		if( isset( $_POST['wll_save_zapier_settings'] ) ){

			$zapier_webhook_login = isset( $_POST['wll_zapier_webhook_login'] ) ? sanitize_text_field( $_POST['wll_zapier_webhook_login'] ) : "";
			$zapier_webhook_register = isset( $_POST['wll_zapier_webhook_register'] ) ? sanitize_text_field( $_POST['wll_zapier_webhook_register'] ) : "";
			$zapier_webhook_update = isset( $_POST['wll_zapier_webhook_update'] ) ? sanitize_text_field( $_POST['wll_zapier_webhook_update'] ) : "";

			$zapier_notify_login = isset( $_POST['wll_zapier_notify_login'] ) ? 1 : 0;
			$zapier_notify_register = isset( $_POST['wll_zapier_notify_register'] ) ? 1 : 0;
			$zapier_notify_update = isset( $_POST['wll_zapier_notify_update'] ) ? 1 : 0;

			$settings = array(
				'webhook_login' => $zapier_webhook_login,
				'webhook_register' => $zapier_webhook_register,
				'webhook_update' => $zapier_webhook_update,
				'notify_login' => $zapier_notify_login,
				'notify_register' => $zapier_notify_register,
				'notify_update' => $zapier_notify_update
			);

			update_option( 'wll_zapier_settings', $settings );

		}

	}

	public function wll_zapier_login( $user_login, $user ){

	    $zapier_array = get_transient( 'when_last_login_zapier_data_'.$user->ID );

		$settings = get_option( 'wll_zapier_settings' ); 
		
		$zapier_notify_login = isset( $settings['notify_login'] ) ? 1 : 0;

		$zapier_webhook = isset( $settings['webhook_login'] ) ? $settings['webhook_login'] : "";

		if( $zapier_webhook != "" && $zapier_notify_login === 1 ){

			if ( false === $zapier_array || $zapier_array == '' ) {

				$user_id = $user->ID;

				$meta = get_user_meta( $user_id );

				$user_data = (array)$user;

				$user_data = apply_filters( 'wll_zapier_user_data_login_filter', $user_data );

				$user_meta = $meta;

				$user_meta = apply_filters( 'wll_zapier_user_meta_login_filter', $user_meta );

				$zapier_array = array();

				$zapier_array['action'] = 'login';

				unset( $user_data['data']->user_pass );

				$zapier_array['user_data'] = $user_data;

				$zapier_array['user_meta'] = $user_meta;		    

			}

			$zapier_request = wp_remote_post( $zapier_webhook, array('body' => $zapier_array ) );

		    if ( ! is_wp_error( $zapier_request ) ) {

		        if ( isset( $zapier_request['body'] ) && strlen( $zapier_request['body'] ) > 0 ) {		        	

				    $zapier_request_body = wp_remote_retrieve_body( $zapier_request );	
	            	
	            	$zapier_transient_timeout = apply_filters( 'wll_zapier_transient_timeout_login', 3600 );

		            set_transient( 'when_last_login_zapier_data_'.$user->ID, $zapier_array, $zapier_transient_timeout );
		        
		        }

		    }

		}	

	}

	public function wll_zapier_register( $user_id ){

		$user = get_user_by( 'id', $user_id );

		$zapier_array = get_transient( 'when_last_login_zapier_data_'.$user->ID );

		$settings = get_option( 'wll_zapier_settings' ); 

		$zapier_webhook = isset( $settings['webhook_register'] ) ? $settings['webhook_register'] : "";
		
		$zapier_notify_register = isset( $settings['notify_register'] ) ? 1 : 0;	

		if( $zapier_webhook != "" && $zapier_notify_register === 1 ){

			if ( false === $zapier_array || $zapier_array == '' ) {

				$user_id = $user->ID;

				$meta = get_user_meta( $user_id );

				$user_data = (array)$user;

				$user_data = apply_filters( 'wll_zapier_user_data_login_filter', $user_data );

				$user_meta = $meta;

				$user_meta = apply_filters( 'wll_zapier_user_meta_login_filter', $user_meta );

				$zapier_array = array();

				$zapier_array['action'] = 'register';

				unset( $user_data['data']->user_pass );

				$zapier_array['user_data'] = $user_data;

				$zapier_array['user_meta'] = $user_meta;		    

			}

			$zapier_request = wp_remote_post( $zapier_webhook, array('body' => $zapier_array ) );

		    if ( ! is_wp_error( $zapier_request ) ) {

		        if ( isset( $zapier_request['body'] ) && strlen( $zapier_request['body'] ) > 0 ) {		        	

				    $zapier_request_body = wp_remote_retrieve_body( $zapier_request );	
	            	
	            	$zapier_transient_timeout = apply_filters( 'wll_zapier_transient_timeout_login', 3600 );

		            set_transient( 'when_last_login_zapier_data_'.$user->ID, $zapier_array, $zapier_transient_timeout );
		        
		        }

		    }

		}
		
	}

	public function wll_zapier_profile_update( $user_id, $old_user_data ){

		$user = get_user_by( 'id', $user_id );

		$zapier_array = get_transient( 'when_last_login_zapier_data_'.$user->ID );

		$settings = get_option( 'wll_zapier_settings' ); 

		$zapier_webhook = isset( $settings['webhook_update'] ) ? $settings['webhook_update'] : "";

		$zapier_notify_update = isset( $settings['notify_update'] ) ? 1 : 0;

		if( $zapier_webhook != "" && $zapier_notify_update === 1 ){

			$user_id = $user->ID;

			$meta = get_user_meta( $user_id );

			$user_data = (array)$user;

			$user_data = apply_filters( 'wll_zapier_user_data_login_filter', $user_data );

			$user_meta = $meta;

			$user_meta = apply_filters( 'wll_zapier_user_meta_login_filter', $user_meta );

			$zapier_array = array();

			$zapier_array['action'] = 'profile_updated';

			unset( $user_data['data']->user_pass );

			$zapier_array['user_data'] = $user_data;

			$zapier_array['user_meta'] = $user_meta;		    

			$zapier_request = wp_remote_post( $zapier_webhook, array('body' => $zapier_array ) );

		    if ( ! is_wp_error( $zapier_request ) ) {

		        if ( isset( $zapier_request['body'] ) && strlen( $zapier_request['body'] ) > 0 ) {		        	

				    $zapier_request_body = wp_remote_retrieve_body( $zapier_request );	
	            	
	            	$zapier_transient_timeout = apply_filters( 'wll_zapier_transient_timeout_login', 3600 );

		            set_transient( 'when_last_login_zapier_data_'.$user->ID, $zapier_array, $zapier_transient_timeout );
		        
		        }

		    }

		}

	}

}

new WhenLastLoginZapier();


