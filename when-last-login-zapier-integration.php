<?php
/**
 * Plugin Name: When Last Login - Zapier Add-on
 * Description: Send and Receive data to and from your WordPress site.
 * Plugin URI: https://yoohooplugins.com
 * Author: Yoohoo Plugins
 * Author URI: https://yoohooplugins.com
 * Version: 1.1
 * License: GPL2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: when-last-login-zapier-integration
 */

defined( 'ABSPATH' ) or exit;


/**
 * Include update class for automatic updates.
 */
define( 'YOOHOO_STORE', 'https://yoohooplugins.com' );
define( 'YH_PLUGIN_ID', 453 );
define( 'WLLZ_VERSION', 1.0 );

if ( ! class_exists( 'Yoohoo_Zapier_Update_Checker' ) ) {
	include( dirname( __FILE__ ) . '/includes/updates/zapier-update-checker.php' );
}

$license_key = trim( get_option( 'wllz_license_key' ) );

// setup the updater
$edd_updater = new Yoohoo_Zapier_Update_Checker( YOOHOO_STORE, __FILE__, array( 
		'version' => WLLZ_VERSION,
		'license' => $license_key,
		'item_id' => YH_PLUGIN_ID,
		'author' => 'Yoohoo Plugins',
		'url' => home_url()
	)
);



class WhenLastLoginZapier{

	public function __construct(){

		add_action( 'admin_head', array( $this, 'wllz_zapier_save_settings' ) );
		add_action( 'admin_menu', array( $this, 'wllz_submenu_page' ) );
		
		add_action( 'wp_login', array( $this, 'wllz_zapier_login' ), 10, 2 );
		add_action( 'user_register', array( $this, 'wllz_zapier_register' ), 10, 1 );
		add_action( 'profile_update', array( $this, 'wllz_zapier_profile_update' ), 10, 2 );

		add_filter( 'plugin_row_meta', array( $this, 'wllz_plugin_row_meta' ), 10, 2 );
      	add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), array( $this, 'wllz_plugin_action_links' ), 10, 2 );

      	add_action( 'admin_init', array( $this, 'wllz_generate_api_key' ) );

      	// Webhook handler check.
      	add_action( 'init', array( $this, 'wllz_webhook_handler' ) );

	}

	public function wllz_webhook_handler() {
		if ( isset( $_REQUEST['wpz_webhook'] ) ) {
			require_once( plugin_dir_path( __FILE__ ) . '/includes/webhook-handler.php' );
			exit;
		}
	}	

	public function wllz_submenu_page(){
		add_submenu_page( 'options-general.php', __( 'Zapier Settings', 'when-last-login-zapier-integration-zapier-integration' ), __( 'Zapier Settings', 'when-last-login-zapier-integration' ), 'manage_options', 'wllz-zapier-settings', array( $this, 'wllz_zapier_content' ) );
	}

	public function wllz_zapier_content(){

		if ( isset( $_REQUEST['receive_data'] )  && 'wllz-zapier-settings' == isset( $_REQUEST['page'] ) ) {
			require_once( plugin_dir_path( __FILE__ ) . '/includes/settings-receive-data.php' );
		} elseif ( 'wllz-zapier-settings' == isset( $_REQUEST['page'] ) && isset( $_REQUEST['license_settings'] ) ) {
			require_once( plugin_dir_path( __FILE__ ) . '/includes/settings-license.php' );
		} else {
			require_once( plugin_dir_path( __FILE__ ) . '/includes/settings-send-data.php' );
		}
		

	}

	public function wllz_zapier_save_settings(){

		if ( isset( $_POST['wll_save_zapier_settings'] ) ){

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

			if ( update_option( 'wll_zapier_settings', $settings ) ) {
				add_action( 'admin_notices', array( $this, 'wllz_zapier_admin_notices' ) );
        	}
        }
    }

    public function wllz_generate_api_key() {

    	if ( isset( $_REQUEST['page'] ) != 'wllz-zapier-settings' ) {
    		return;
    	}

    	$settings = get_option( 'wll_zapier_settings' );

    	if ( ! isset( $settings['api_key'] ) || empty( $settings['api_key'] ) ) {
    		$settings['api_key'] = strtolower( wp_generate_password( 32, false ) );
    		update_option( 'wll_zapier_settings', $settings );
    	}  	
    }

    public function wllz_zapier_admin_notices() {
    ?>
      <div class="notice notice-success is-dismissible">
        <p><?php _e( 'Settings saved successfully.', 'when-last-login-zapier-integration-zapier-integration' ); ?></p>
      </div>
    <?php
    }
		

	public function wllz_zapier_login( $user_login, $user ){

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

	public function wllz_zapier_register( $user_id ){

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

	public function wllz_zapier_profile_update( $user_id, $old_user_data ){

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



	public function wllz_plugin_action_links( $links ) {
      $new_links = array(
        '<a href="' . admin_url( 'options-general.php?page=wllz-zapier-settings' ) . '" title="' . esc_attr( __( 'View Settings', 'when-last-login-zapier-integration' ) ) . '">' . __( 'Settings', 'when-last-login-zapier-integration' ) . '</a>'
      );

      $new_links = apply_filters( 'wllz_plugin_action_links', $new_links );

      return array_merge( $new_links, $links );
    }

    public function wllz_plugin_row_meta( $links, $file ) {
      if ( strpos( $file, 'when-last-login-zapier-integration-zapier-integration.php' ) !== false ) {
        $new_links = array(
          '<a href="' . admin_url( 'options-general.php?page=wllz-zapier-settings' ) . '" title="' . esc_attr( __( 'View Settings', 'when-last-login-zapier-integration' ) ) . '">' . __( 'Settings', 'when-last-login-zapier-integration' ) . '</a>',
          '<a href="' . esc_url( 'https://yoohooplugins.com/?s=zapier' ) . '" title="' . esc_attr( __( 'View Documentation', 'when-last-login-zapier-integration' ) ) . '">' . __( 'Docs', 'when-last-login-zapier-integration' ) . '</a>',
          '<a href="' . esc_url( 'https://yoohooplugins.com/support/' ) . '" title="' . esc_attr( __( 'Visit Customer Support Forum', 'when-last-login-zapier-integration' ) ) . '">' . __( 'Premium Support', 'when-last-login-zapier-integration' ) . '</a>',
        );

        $new_links = apply_filters( 'wllz_plugin_row_meta', $new_links );
        $links = array_merge( $links, $new_links );
      }
      return $links;
    }

} // end of class

new WhenLastLoginZapier();


