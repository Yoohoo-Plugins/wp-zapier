<?php
/**
 * Primary plugin class
 * Moved to a standalone file for better formatting
 *
 * Moved since: 2020-05-06
*/
namespace Yoohoo;

class WPZapier{

	public function __construct(){
		add_action( 'admin_menu', array( $this, 'wpzp_menu_holder' ) );
		add_action( 'admin_head', array( $this, 'wpzp_zapier_save_settings' ) );

		add_filter( 'plugin_row_meta', array( $this, 'wpzp_plugin_row_meta' ), 10, 2 );
      	add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), array( $this, 'wpzp_plugin_action_links' ), 10, 2 );
      	
      	add_action( 'admin_init', array( $this, 'wpzp_generate_api_key' ) );
      	add_action( 'admin_notices', array( $this, 'wpzp_admin_notices' ) );
      	
      	add_action( 'init', array( $this, 'init' ) );
		
		// -- Deprecated and replaced by the improved CPT based system --
		//add_action( 'admin_menu', array( $this, 'wpzp_submenu_page' ) );
		//add_action( 'wp_login', array( $this, 'wpzp_zapier_login' ), 10, 2 );
		//add_action( 'user_register', array( $this, 'wpzp_zapier_register' ), 10, 1 );
		//add_action( 'profile_update', array( $this, 'wpzp_zapier_profile_update' ), 10, 2 );

      	// Webhook handler check.
      	include( dirname( __FILE__ ) . '/privacy.php' );

      	$this->loadIntegrations();
      	
      	$this->outboundEvents = new WPZapier\OutboundEvents();
	}

	public function init() {
		load_plugin_textdomain( 'wp-zapier', false, basename( dirname( __FILE__ ) ) . '/languages' );

		$shouldMigrate = $this->shouldMigrateToPostTypes();
      	if($shouldMigrate){
      		$this->migrateToPostTypes();
      	}


		if ( isset( $_REQUEST['wpz_webhook'] ) ) {
			require_once( plugin_dir_path( __FILE__ ) . '/webhook-handler.php' );
			exit;
		}

	}

	/**
	 * Autoloader for integrations (Does not init any classes)
	*/
	public function loadIntegrations(){
		$integrations = scandir(__DIR__ . '/integrations');
		foreach ($integrations as $module) {
			if(strpos($module, '.php')){
				require_once(__DIR__ . '/integrations/' . $module);
			}
		}

		do_action('wp_zapier_integrations_loaded');
	}

	/**
	 * Check if we have migrated the legacy system to the CPT system
	*/
	public function shouldMigrateToPostTypes(){
		$migrationComplete = get_option('wpzp_migration_complete');
		if(!empty($migrationComplete)){
			return false;
		}
		return true;
	}	

	/**
	 * Migrate the legacy system to the CPT system
	*/
	public function migrateToPostTypes(){
		$settings = get_option( 'wp_zapier_settings' ); 
		$migrateLogin = isset($settings['notify_login']) ? true : false;
		$migrateReg = isset($settings['notify_register']) ? true : false;
		$migrateProfile = isset($settings['notify_update']) ? true : false;

		$loginUrl = isset($settings['webhook_login']) ? esc_url($settings['webhook_login']) : "";
		$regUrl = isset($settings['webhook_register']) ? esc_url($settings['webhook_register']) : "";
		$updateUrl = isset($settings['webhook_update']) ? esc_url($settings['webhook_update']) : "";

		if($migrateLogin && !empty($loginUrl)){
			$loginPost = array(
			  'post_title'	=> 'Migrated: Login',
			  'post_type'  	=> 'outbound_event',
			  'post_status' => 'publish',
			);
			 
			// Insert the post into the database
			$id = wp_insert_post($loginPost);
			update_post_meta($id, '_zapier_action', 'wp_login');
	    	update_post_meta($id, '_zapier_url', $loginUrl);
		}

		if($migrateReg && !empty($regUrl)){
			$regPost = array(
			  'post_title'	=> 'Migrated: Register',
			  'post_type'  	=> 'outbound_event',
			  'post_status' => 'publish',
			);
			 
			// Insert the post into the database
			$id = wp_insert_post($regPost);
			update_post_meta($id, '_zapier_action', 'user_register');
	    	update_post_meta($id, '_zapier_url', $regUrl);
		}

		if($migrateProfile && !empty($updateUrl)){
			$profilePost = array(
			  'post_title'	=> 'Migrated: Profile Update',
			  'post_type'  	=> 'outbound_event',
			  'post_status' => 'publish',
			);
			 
			// Insert the post into the database
			$id = wp_insert_post($profilePost);
			update_post_meta($id, '_zapier_action', 'profile_update');
	    	update_post_meta($id, '_zapier_url', $updateUrl);
		}

		update_option('wpzp_migration_complete', 'complete');
	}

	public function wpzp_menu_holder(){
		add_menu_page( __( 'WP Zapier Settings', 'wp-zapier' ), __( 'WP Zapier', 'wp-zapier' ), 'manage_options', 'wp-zapier', array( $this, 'wpzp_zapier_content' ), 'dashicons-migrate', 99);
		$this->wpzp_submenu_page();
	}

	public function wpzp_submenu_page(){
		add_submenu_page( 'wp-zapier', __( 'Settings', 'wp-zapier' ), __( 'Settings', 'wp-zapier' ), 'manage_options', 'wp-zapier-settings', array( $this, 'wpzp_zapier_content' ) );
	}

	public function wpzp_zapier_content(){
		if (isset($_REQUEST['license_settings']) && 'wp-zapier-settings' == isset( $_REQUEST['page'])){
			require_once( plugin_dir_path( __FILE__ ) . '/settings-license.php' );
		} else {
			require_once( plugin_dir_path( __FILE__ ) . '/settings-receive-data.php' );
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
		if ( isset( $_REQUEST['wpz_new_api_key'] ) && check_admin_referer( 'wpz_new_api_key', '_wpz_new_api_key' ) ) {
			$settings['api_key'] = '';
		}

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

    	if ( ! isset( $_REQUEST['page'] ) ) {
    		return;
    	}

    	if ( $_REQUEST['page'] != 'wp-zapier-settings' ) {
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
		

    /**
     * Deprecated: Migrated automatically to custom post type system
     * Code left in place to prevent destructive code workflow
     *
     * Deprecated Since Version: 1.5
     * Deprecated Since Date: 2020-05-06
    */ 
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
				$zapier_array = apply_filters( 'wpzp_send_data_login_array', $zapier_array, $user, $user_id );    
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

	/**
     * Deprecated: Migrated automatically to custom post type system
     * Code left in place to prevent destructive code workflow
     *
     * Deprecated Since Version: 1.5
     * Deprecated Since Date: 2020-05-06
    */ 
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
				$zapier_array = apply_filters( 'wpzp_send_data_register_array', $zapier_array, $user, $user_id );  
				$zapier_request = wp_remote_post( $zapier_webhook, array('body' => $zapier_array ) );
			    if ( ! is_wp_error( $zapier_request ) ) {
			        if ( isset( $zapier_request['body'] ) && strlen( $zapier_request['body'] ) > 0 ) {		        	
					    $zapier_request_body = wp_remote_retrieve_body( $zapier_request );	
		            	// Set the transient to 1 hour.
		            	$zapier_transient_timeout = apply_filters( 'wp_zapier_transient_timeout_register', 3600 );
			            set_transient( 'wp_zapier_data_'.$user->ID, $zapier_array, $zapier_transient_timeout );
			        }
				}
			}
		}
	}

	/**
     * Deprecated: Migrated automatically to custom post type system
     * Code left in place to prevent destructive code workflow
     *
     * Deprecated Since Version: 1.5
     * Deprecated Since Date: 2020-05-06
    */ 
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
			$zapier_array = apply_filters( 'wpzp_send_data_profile_update_array', $zapier_array, $user, $user_id );		    
			$zapier_request = wp_remote_post( $zapier_webhook, array('body' => $zapier_array ) );
		    if ( ! is_wp_error( $zapier_request ) ) {
		        if ( isset( $zapier_request['body'] ) && strlen( $zapier_request['body'] ) > 0 ) {	
				    $zapier_request_body = wp_remote_retrieve_body( $zapier_request );	
	            	$zapier_transient_timeout = apply_filters( 'wp_zapier_transient_timeout_profile_update', 3600 );
		            set_transient( 'wp_zapier_data_'.$user->ID, $zapier_array, $zapier_transient_timeout );  
		        }
		    }
		}
	}

	public function wpzp_plugin_action_links( $links ) {
      $new_links = array(
        '<a href="' . admin_url( 'admin.php?page=wp-zapier-settings' ) . '" title="' . esc_attr( __( 'View Settings', 'wp-zapier' ) ) . '">' . __( 'Settings', 'wp-zapier' ) . '</a>'
      );

      $new_links = apply_filters( 'wpzp_plugin_action_links', $new_links );

      return array_merge( $new_links, $links );
    }

    public function wpzp_plugin_row_meta( $links, $file ) {
      if ( strpos( $file, 'wp-zapier.php' ) !== false ) {
        $new_links = array(
          '<a href="' . admin_url( 'admin.php?page=wp-zapier-settings' ) . '" title="' . esc_attr( __( 'View Settings', 'wp-zapier' ) ) . '">' . __( 'Settings', 'wp-zapier' ) . '</a>',
          '<a href="' . esc_url( 'https://yoohooplugins.com/?s=zapier' ) . '" title="' . esc_attr( __( 'View Documentation', 'wp-zapier' ) ) . '">' . __( 'Docs', 'wp-zapier' ) . '</a>',
          '<a href="' . esc_url( 'https://yoohooplugins.com/support/' ) . '" title="' . esc_attr( __( 'Visit Customer Support Forum', 'wp-zapier' ) ) . '">' . __( 'Premium Support', 'wp-zapier' ) . '</a>',
        );

        $new_links = apply_filters( 'wpzp_plugin_row_meta', $new_links );
        $links = array_merge( $links, $new_links );
      }
      return $links;
    }
} 