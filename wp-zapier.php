<?php
/**
 * Plugin Name: WP Zapier
 * Description: Automate your WordPress users with over 1000+ apps on Zapier.com
 * Plugin URI: https://yoohooplugins.com
 * Author: Yoohoo Plugins
 * Author URI: https://yoohooplugins.com
 * Version: 1.2.1
 * License: GPL2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: wp-zapier
 * Domain Path: /languages
 */

defined( 'ABSPATH' ) or exit;


/**
 * Include update class for automatic updates.
 */
define( 'YOOHOO_STORE', 'https://yoohooplugins.com/edd-sl-api/' );
define( 'YH_PLUGIN_ID', 453 );
define( 'WPZP_VERSION', 1.1 );

if ( ! class_exists( 'Yoohoo_Zapier_Update_Checker' ) ) {
	include( dirname( __FILE__ ) . '/includes/updates/zapier-update-checker.php' );
}

$license_key = trim( get_option( 'yoohoo_zapier_license_key' ) );

// setup the updater
$edd_updater = new Yoohoo_Zapier_Update_Checker( YOOHOO_STORE, __FILE__, array( 
		'version' => WPZP_VERSION,
		'license' => $license_key,
		'item_id' => YH_PLUGIN_ID,
		'author' => 'Yoohoo Plugins',
		'url' => home_url()
	)
);

class Yoohoo_WP_Zapier{

	public function __construct(){

		add_action( 'admin_head', array( $this, 'wpzp_zapier_save_settings' ) );
		add_action( 'admin_menu', array( $this, 'wpzp_submenu_page' ) );
		
		add_action( 'wp_login', array( $this, 'wpzp_zapier_login' ), 10, 2 );
		add_action( 'user_register', array( $this, 'wpzp_zapier_register' ), 10, 1 );
		add_action( 'profile_update', array( $this, 'wpzp_zapier_profile_update' ), 10, 2 );

		add_filter( 'plugin_row_meta', array( $this, 'wpzp_plugin_row_meta' ), 10, 2 );
      	add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), array( $this, 'wpzp_plugin_action_links' ), 10, 2 );

      	add_action( 'admin_init', array( $this, 'wpzp_generate_api_key' ) );
      	add_action( 'admin_notices', array( $this, 'wpzp_admin_notices' ) );

      	// Webhook handler check.
      	add_action( 'init', array( $this, 'wpzp_webhook_handler' ) );

      	include( dirname( __FILE__ ) . '/includes/privacy.php' );

	}

	public function wpzp_webhook_handler() {
		
		load_plugin_textdomain( 'wp-zapier', false, basename( dirname( __FILE__ ) ) . '/languages' );

		if ( isset( $_REQUEST['wpz_webhook'] ) ) {
			require_once( plugin_dir_path( __FILE__ ) . '/includes/webhook-handler.php' );
			exit;
		}
	}	

	public function wpzp_submenu_page(){
		add_submenu_page( 'options-general.php', __( 'Zapier Settings', 'wp-zapier' ), __( 'Zapier Settings', 'wp-zapier' ), 'manage_options', 'wp-zapier-settings', array( $this, 'wpzp_zapier_content' ) );
	}

	public function wpzp_zapier_content(){

		if ( isset( $_REQUEST['receive_data'] )  && 'wp-zapier-settings' == isset( $_REQUEST['page'] ) ) {
			require_once( plugin_dir_path( __FILE__ ) . '/includes/settings-receive-data.php' );
		} elseif ( 'wp-zapier-settings' == isset( $_REQUEST['page'] ) && isset( $_REQUEST['license_settings'] ) ) {
			require_once( plugin_dir_path( __FILE__ ) . '/includes/settings-license.php' );
		} else {
			require_once( plugin_dir_path( __FILE__ ) . '/includes/settings-send-data.php' );
		}
		

	}

	public function wpzp_zapier_save_settings(){

		if ( isset( $_POST['wp_zapier_save_settings'] ) ){

			$zapier_webhook_login = isset( $_POST['wp_zapier_webhook_login'] ) ? sanitize_text_field( $_POST['wp_zapier_webhook_login'] ) : "";
			$zapier_webhook_register = isset( $_POST['wp_zapier_webhook_register'] ) ? sanitize_text_field( $_POST['wp_zapier_webhook_register'] ) : "";
			$zapier_webhook_update = isset( $_POST['wp_zapier_webhook_update'] ) ? sanitize_text_field( $_POST['wp_zapier_webhook_update'] ) : "";

			$zapier_notify_login = isset( $_POST['wp_zapier_notify_login'] ) ? 1 : 0;
			$zapier_notify_register = isset( $_POST['wp_zapier_notify_register'] ) ? 1 : 0;
			$zapier_notify_update = isset( $_POST['wp_zapier_notify_update'] ) ? 1 : 0;

			$settings = array(
				'webhook_login' => $zapier_webhook_login,
				'webhook_register' => $zapier_webhook_register,
				'webhook_update' => $zapier_webhook_update,
				'notify_login' => $zapier_notify_login,
				'notify_register' => $zapier_notify_register,
				'notify_update' => $zapier_notify_update
			);

			if ( update_option( 'wp_zapier_settings', $settings ) ) {
				add_action( 'admin_notices', array( $this, 'wpzp_zapier_admin_notices' ) );
        	}
        }
    }

    public function wpzp_generate_api_key() {

    	if ( isset( $_REQUEST['page'] ) != 'wp-zapier-settings' ) {
    		return;
    	}

    	$settings = get_option( 'wp_zapier_settings' );

    	if ( ! isset( $settings['api_key'] ) || empty( $settings['api_key'] ) ) {
    		$settings['api_key'] = strtolower( wp_generate_password( 32, false ) );
    		update_option( 'wp_zapier_settings', $settings );
    	}  	
    }


    // This is for saving settings notice.
    public function wpzp_zapier_admin_notices() {
    ?>
      <div class="notice notice-success is-dismissible">
        <p><?php _e( 'Settings saved successfully.', 'wp-zapier' ); ?></p>
      </div>
    <?php
    }

    // license nag.
    public function wpzp_admin_notices(){

    	$hide_nag = apply_filters( 'wp_zapier_hide_nag', false );

    	if ( $hide_nag ) {
    		return;
    	}

    	if ( isset( $_REQUEST['page'] ) != 'wp-zapier-settings' ) {
    		return;
    	}

    	$license_key = get_option( 'yoohoo_zapier_license_key' );
    	$status = get_option( 'yoohoo_zapier_license_status' );
    	$expires = get_option( 'yoohoo_zapier_license_expires' );
    	$today = date( "Y-m-d" );
    	$expired = false;

    	if ( ! empty( $expires ) && $today >= $expires ) {
    		$expired = true;
    	}

    	if ( ! $license_key || $status != 'valid' ) {
	    ?>
	      <div class="notice notice-warning">
	        <p><?php _e( 'Warning! License key for WP Zapier is missing or not active. Please activate your license key. We recommend an annual support license.', 'wp-zapier' ); ?> <a href="https://yoohooplugins.com/plugins/zapier-integration" target="_blank" rel="noopener nofollow"><?php _e( 'More Info', 'wp-zapier' ); ?></a></p>
	      </div>
	    <?php	
	    }elseif( $expired ) {
	    	//show if expired.
	    ?>
	    	<div class="notice notice-error">
	        <p><?php _e( 'Your license for WP Zapier has expired.', 'wp-zapier' ); ?> <a href="https://yoohooplugins.com/checkout/purchase-history/" target="_blank" rel="noopener nofollow"><?php _e( 'Renew Now', 'wp-zapier' ); ?></a></p>
	      </div>
	    <?php
	    }
    }
		

	public function wpzp_zapier_login( $user_login, $user ){

	   	$zapier_array = get_transient( 'wp_zapier_data_'.$user->ID );

		$settings = get_option( 'wp_zapier_settings' ); 
		
		$zapier_notify_login = isset( $settings['notify_login'] ) ? 1 : 0;

		$zapier_webhook = isset( $settings['webhook_login'] ) ? esc_url( $settings['webhook_login'] ) : "";

		if( $zapier_webhook != "" && $zapier_notify_login === 1 ){


			if ( false === $zapier_array || $zapier_array == '' ) {

				// Get the user's ID.
				$user_id = $user->ID;

				// The array to store the data when pushing data.
				$zapier_array = array();

				// Add necessary user information to the array.
				$zapier_array['user_id'] = $user_id;
				$zapier_array['user_email'] = $user->user_email;
				$zapier_array['user_nicename'] = $user->user_nicename;
				$zapier_array['user_registered'] = $user->user_registered;
				$zapier_array['user_url'] = $user->user_url;
				$zapier_array['display_name'] = $user->display_name;
				

				// Let's get some default user meta
				$meta = get_user_meta( $user_id );

				$zapier_array['first_name'] = $meta['first_name'][0];
				$zapier_array['last_name'] = $meta['last_name'][0];
				$zapier_array['nickname'] = $meta['nickname'][0];
				$zapier_array['user_description'] = $meta['description'][0];

				// Add the roles to the end.
				$zapier_array['roles'] = $user->roles;

				$zapier_array = apply_filters( 'wpzp_send_data_login_array', $zapier_array, $user );    

				$zapier_request = wp_remote_post( $zapier_webhook, array('body' => $zapier_array ) );

			    if ( ! is_wp_error( $zapier_request ) ) {

			        if ( isset( $zapier_request['body'] ) && strlen( $zapier_request['body'] ) > 0 ) {

					    $zapier_request_body = wp_remote_retrieve_body( $zapier_request );	
		            	
		            	// Set the transient to 1 hour.
		            	$zapier_transient_timeout = apply_filters( 'wp_zapier_transient_timeout_login', 3600 );

			            set_transient( 'wp_zapier_data_'.$user->ID, $zapier_array, $zapier_transient_timeout );
			        
			        }

			    }
			}

		}	

	}

	public function wpzp_zapier_register( $user_id ){

		$user = get_user_by( 'id', $user_id );

		$zapier_array = get_transient( 'wp_zapier_data_'.$user->ID );

		$settings = get_option( 'wp_zapier_settings' ); 

		$zapier_webhook = isset( $settings['webhook_register'] ) ? $settings['webhook_register'] : "";
		
		$zapier_notify_register = isset( $settings['notify_register'] ) ? 1 : 0;	

		if( $zapier_webhook != "" && $zapier_notify_register === 1 ){

			if ( false === $zapier_array || $zapier_array == '' ) {

				// The array to store the data when pushing data.
				$zapier_array = array();

				// Add necessary user information to the array.
				$zapier_array['user_id'] = $user_id;
				$zapier_array['user_email'] = $user->user_email;
				$zapier_array['user_nicename'] = $user->user_nicename;
				$zapier_array['user_registered'] = $user->user_registered;
				$zapier_array['user_url'] = $user->user_url;
				$zapier_array['display_name'] = $user->display_name;
				

				// Let's get some default user meta
				$meta = get_user_meta( $user_id );

				$zapier_array['first_name'] = $meta['first_name'][0];
				$zapier_array['last_name'] = $meta['last_name'][0];
				$zapier_array['nickname'] = $meta['nickname'][0];
				$zapier_array['user_description'] = $meta['description'][0];

				// Add the roles to the end.
				$zapier_array['roles'] = $user->roles;

				$zapier_array = apply_filters( 'wpzp_send_data_register_array', $zapier_array, $user );  

				$zapier_request = wp_remote_post( $zapier_webhook, array('body' => $zapier_array ) );

			    if ( ! is_wp_error( $zapier_request ) ) {

			        if ( isset( $zapier_request['body'] ) && strlen( $zapier_request['body'] ) > 0 ) {		        	
					    $zapier_request_body = wp_remote_retrieve_body( $zapier_request );	
		            	
		            	// Set the transient to 1 hour.
		            	$zapier_transient_timeout = apply_filters( 'wp_zapier_transient_timeout_login', 3600 );

			            set_transient( 'wp_zapier_data_'.$user->ID, $zapier_array, $zapier_transient_timeout );
			        }
				}
			}
		}
	}

	public function wpzp_zapier_profile_update( $user_id, $old_user_data ){

		$user = get_user_by( 'id', $user_id );

		$zapier_array = get_transient( 'wp_zapier_data_'.$user->ID );

		$settings = get_option( 'wp_zapier_settings' ); 

		$zapier_webhook = isset( $settings['webhook_update'] ) ? $settings['webhook_update'] : "";

		$zapier_notify_update = isset( $settings['notify_update'] ) ? 1 : 0;

		if( $zapier_webhook != "" && $zapier_notify_update === 1 ){

			// The array to store the data when pushing data.
			$zapier_array = array();

			// Add necessary user information to the array.
			$zapier_array['user_id'] = $user_id;
			$zapier_array['user_email'] = $user->user_email;
			$zapier_array['user_nicename'] = $user->user_nicename;
			$zapier_array['user_registered'] = $user->user_registered;
			$zapier_array['user_url'] = $user->user_url;
			$zapier_array['display_name'] = $user->display_name;
			

			// Let's get some default user meta
			$meta = get_user_meta( $user_id );

			$zapier_array['first_name'] = $meta['first_name'][0];
			$zapier_array['last_name'] = $meta['last_name'][0];
			$zapier_array['nickname'] = $meta['nickname'][0];
			$zapier_array['user_description'] = $meta['description'][0];

			// Add the roles to the end.
			$zapier_array['roles'] = $user->roles;

			$zapier_array = apply_filters( 'wpzp_send_data_login_array', $zapier_array, $user );		    

			$zapier_request = wp_remote_post( $zapier_webhook, array('body' => $zapier_array ) );

		    if ( ! is_wp_error( $zapier_request ) ) {

		        if ( isset( $zapier_request['body'] ) && strlen( $zapier_request['body'] ) > 0 ) {	

				    $zapier_request_body = wp_remote_retrieve_body( $zapier_request );	
	            	
	            	$zapier_transient_timeout = apply_filters( 'wp_zapier_transient_timeout_login', 3600 );

		            set_transient( 'wp_zapier_data_'.$user->ID, $zapier_array, $zapier_transient_timeout );  
		        }
		    }
		}
	}

	public function wpzp_plugin_action_links( $links ) {
      $new_links = array(
        '<a href="' . admin_url( 'options-general.php?page=wp-zapier-settings' ) . '" title="' . esc_attr( __( 'View Settings', 'wp-zapier' ) ) . '">' . __( 'Settings', 'wp-zapier' ) . '</a>'
      );

      $new_links = apply_filters( 'wpzp_plugin_action_links', $new_links );

      return array_merge( $new_links, $links );
    }

    public function wpzp_plugin_row_meta( $links, $file ) {
      if ( strpos( $file, 'wp-zapier.php' ) !== false ) {
        $new_links = array(
          '<a href="' . admin_url( 'options-general.php?page=wp-zapier-settings' ) . '" title="' . esc_attr( __( 'View Settings', 'wp-zapier' ) ) . '">' . __( 'Settings', 'wp-zapier' ) . '</a>',
          '<a href="' . esc_url( 'https://yoohooplugins.com/?s=zapier' ) . '" title="' . esc_attr( __( 'View Documentation', 'wp-zapier' ) ) . '">' . __( 'Docs', 'wp-zapier' ) . '</a>',
          '<a href="' . esc_url( 'https://yoohooplugins.com/support/' ) . '" title="' . esc_attr( __( 'Visit Customer Support Forum', 'wp-zapier' ) ) . '">' . __( 'Premium Support', 'wp-zapier' ) . '</a>',
        );

        $new_links = apply_filters( 'wpzp_plugin_row_meta', $new_links );
        $links = array_merge( $links, $new_links );
      }
      return $links;
    }
} // end of class

new Yoohoo_WP_Zapier();
