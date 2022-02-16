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

		add_filter( 'pre_set_site_transient_update_plugins', array( $this, 'wpzp_show_thumbnail_on_update'), 10, 1 );
		add_filter( 'plugin_row_meta', array( $this, 'wpzp_plugin_row_meta' ), 10, 2 );
      	add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), array( $this, 'wpzp_plugin_action_links' ), 10, 2 );
      	
      	add_action( 'admin_init', array( $this, 'wpzp_generate_api_key' ) );
      	add_action( 'admin_notices', array( $this, 'wpzp_admin_notices' ) );
      	add_action( 'admin_enqueue_scripts', array( $this, 'wpzp_admin_scripts' ) );
      	add_action( 'init', array( $this, 'init' ) );
		
		add_action( 'wp_ajax_wpzp_admin_switch_ajax', array($this, 'wpzp_admin_switch_ajax' ) );

		// -- Deprecated and replaced by the improved CPT based system --
		//add_action( 'admin_menu', array( $this, 'wpzp_submenu_page' ) );
		//add_action( 'wp_login', array( $this, 'wpzp_zapier_login' ), 10, 2 );
		//add_action( 'user_register', array( $this, 'wpzp_zapier_register' ), 10, 1 );
		//add_action( 'profile_update', array( $this, 'wpzp_zapier_profile_update' ), 10, 2 );

		add_action( 'wp_dashboard_setup', array( $this, 'dashboard_stats' ) );

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
		add_menu_page( __( 'WP Zapier Settings', 'wp-zapier' ), __( 'WP Zapier', 'wp-zapier' ), 'manage_options', 'wp-zapier', array( $this, 'wpzp_receive_data' ), WPZAP_URL . 'assets/img/wp-zapier-dashicon.png', 99);
		$this->wpzp_submenu_page();
	}

	public function wpzp_submenu_page(){
		add_submenu_page( 'wp-zapier', __( 'Receive Data', 'wp-zapier' ), __( 'Receive Data', 'wp-zapier' ), 'manage_options', 'wp-zapier-settings', array( $this, 'wpzp_receive_data' ) );

		add_submenu_page( 'wp-zapier', __( 'License Settings', 'wp-zapier' ), __( 'License Settings', 'wp-zapier' ), 'manage_options', 'wp-zapier-license', array( $this, 'wpzp_zapier_license' ) );
	}

	public function wpzp_receive_data(){
		require_once( plugin_dir_path( __FILE__ ) . '/settings-receive-data.php' );
	}

	public function wpzp_zapier_license() {
		require_once( plugin_dir_path( __FILE__ ) . '/settings-license.php' );
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
	 * Enqueue scripts and stylesheets for admins here.
	 */
	public function wpzp_admin_scripts() {
		$screen = get_current_screen();
	
		if ( ( isset( $_REQUEST['post_type'] ) && $_REQUEST['post_type'] == 'outbound_event' ) || $screen->id == 'dashboard' ) {
			wp_enqueue_style( 'wpzp-admin', WPZAP_URL . 'assets/css/admin.css', array(), WPZAP_VERSION );
			wp_enqueue_script( 'wpzp-admin', WPZAP_URL . 'assets/js/admin.js', array('jquery'), WPZAP_VERSION );
		}
	}

	/** AJAX to update post meta on button click */
	public function wpzp_admin_switch_ajax() {

		// verify nonce first
		if ( !wp_verify_nonce( $_REQUEST['nonce'], "wpzap_switch_nonce")) {
			exit( "Not today! Nonce not verified." );
		 }  
		
		$id = intval( $_REQUEST['id'] );

		// Bail if ID is empty
		if ( empty( $id ) ) {
			return;
		}

		$value = get_post_meta( $id, '_zapier_status', true );
		if ( $value == 'enabled' ) {
			delete_post_meta( $id, '_zapier_status' );
		} else {
			update_post_meta( $id, '_zapier_status', 'enabled' );
		}

		exit;
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

	public function wpzp_show_thumbnail_on_update( $transient ) {

		if ( is_object( $transient ) && 
			isset( $transient->response ) && 
			is_array( $transient->response ) ) {
			$basename = plugin_basename( __FILE__ );
		 
			if ( ! isset( $transient->response[$basename] ) ) {
			  return $transient;
			}
		 
			$transient->response[$basename]->icons = [ 'default' => plugins_url( 'assets/img/wp-zapier-thumbnail.png', __FILE__ ) ];
		}
		 
		  return $transient;
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

    public function dashboard_stats() { 	
	    wp_add_dashboard_widget( 'wp_zapier_dashboard', __('WP Zapier Dashboard', 'wp-zapier'), array( $this, 'dashboard_status_content' ) );

    }

    public function dashboard_status_content(){

    	?>

    	<div class='wpz_dashboard_container'>
    		<div class='wpz_dashboard_item wpz_1 wpz_events'>
    			<h2><?php _e( 'Get Started', 'wp-zapier' ); ?></h2>
    			<div>
	    			<a href='<?php echo admin_url( 'edit.php?post_type=outbound_event' ); ?>'><?php _e('View Outbound Events', 'wp-zapier' ); ?></a>
	    			<a href='<?php echo admin_url( 'post-new.php?post_type=outbound_event' ); ?>'><?php _e('New Outbound Event', 'wp-zapier' ); ?></a>
	    			<a href='<?php echo admin_url( 'admin.php?page=wp-zapier-settings' ); ?>'><?php _e('Receive Inbound Events', 'wp-zapier' ); ?></a>
	    		</div>
    		</div>
    		<div class='wpz_dashboard_item wpz_2'>
    			<h2><?php _e( 'Time Saved', 'wp-zapier' ); ?></h2>
    			<span><?php echo $this->calculate_time_saved().' '.__('Hours', 'wp-zapier'); ?></span>
    		</div>
    		<div class='wpz_dashboard_item wpz_2'>
    			<h2><?php _e( 'License Status', 'wp-zapier' ); ?></h2>
    			<?php 
    				$status = get_option( 'yoohoo_zapier_license_status' );
					if( $status !== 'valid' || empty( $status ) ) { 
						echo '<span style="color: red;">'.__('Invalid', 'wp-zapier').'</span>';
					} else {
						echo '<span style="color: green;">'.__('Valid', 'wp-zapier').'</span>';
					}
				?>
    		</div>
    		<div class='wpz_dashboard_item wpz_1'>
    			<h2><?php _e( 'Recent Blog Posts from YooHoo Plugins', 'wp-zapier' ); ?></h2>
    			<ul><?php echo $this->get_news( 'https://yoohooplugins.com/wp-json/wp/v2/posts' ); ?></ul>
    		</div>    		
		</div>
    	<?php

    }

    function get_news( $url ) { 
    		
    	$data = get_transient( 'wpzapier_news' );

    	if( $data == FALSE ) { 
    		$data = wp_remote_get( $url );
    		$data = wp_remote_retrieve_body( $data );
    		set_transient( 'wpzapier_news', $data, 7 * DAY_IN_SECONDS  );
    	}
    	
    	if( !empty( $data ) ) { 
    		$data = json_decode( $data );
    		foreach( $data as $d ) {     			
    			if( !empty( $d->title->rendered ) ) { 
    				echo "<li><a href='".$d->link."?utm_source=plugin&utm_medium=wp-zapier&utm_campaign=widget' target='_BLANK'>".$d->title->rendered."</a></a>";
    			}
    		}
    	}

    }

    function calculate_time_saved() { 
		//_zapier_success_calls
    	$args = array(
    		'post_type' => 'outbound_event',
    		'posts_per_page' => -1,
    	);

    	$the_query = new \WP_Query( $args );
    	$total_calls = 0;
    	if( $the_query->have_posts() ){
    		while( $the_query->have_posts() ){
    			$the_query->the_post();
    			$success_calls = (int)get_post_meta( get_the_ID(), '_zapier_fail_calls', true );	
    			$total_calls = $total_calls + $success_calls;		
    		}
    	}

    	$total_seconds = $total_calls * 30; //Assuming each task takes 30 seconds

    	return round( $total_seconds / 3600, 2 ); //Convert to Hours    

	}
} // End of Class