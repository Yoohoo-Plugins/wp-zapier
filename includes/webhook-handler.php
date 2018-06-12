<?php

// Don't access this file directly to do stuff.
defined( 'ABSPATH' ) or exit;

$zapier_settings = get_option( 'wpzp_zapier_settings', true );

$api_key = ! empty( $_REQUEST['api_key'] ) ? sanitize_key( $_REQUEST['api_key'] ) : '';
$action = ! empty( $_REQUEST['action'] ) ? sanitize_text_field( $_REQUEST['action'] ) : '';

// set the output to be JSON.
header( 'Content-Type: application/json' );

// Bail if the API key is not valid.
if ( $api_key != $zapier_settings['api_key'] ) {
	status_header( 403 );
	echo json_encode( __( 'API Key not valid or missing.', 'wp-zapier' ) );
	exit;
}



switch ( $action ) {
	case 'create_user':
		wpzp_create_user();
		break;

	case 'update_user':
		wpzp_update_user();
		break;

	case 'delete_user':
		wpzp_delete_user();
		break;
}

/**
 * Function to create the user within WordPress.
 *
 */
function wpzp_create_user(){

	// Get the params
	$username = isset( $_GET['username'] ) ? sanitize_text_field( $_GET['username'] ) : '';
	$email = isset( $_GET['email'] ) ? sanitize_email( $_GET['email'] ) : '';
	$first_name = isset( $_GET['first_name'] ) ? sanitize_text_field( $_GET['first_name'] ) : '';
	$last_name = isset( $_GET['last_name'] ) ? sanitize_text_field( $_GET['last_name'] ) : '';
	$role = isset( $_GET['role'] ) ? sanitize_text_field( $_GET['role'] ) : 'subscriber';
	$user_pass = wp_generate_password( 20, true, false );

	if ( empty( $email ) ) {
		echo json_encode( __( ' An email is required to create a user.', 'wp-zapier' ) );
		exit;
	}

	if ( empty( $username ) ) {
		$username = wpzp_generate_username( $firstname, $lastname, $email );
	}

	$userdata = array(
		'user_login' => $username,
		'user_email' => $email,
		'first_name' => $first_name,
		'last_name' => $last_name,
		'role' => $role,
		'user_pass' => $user_pass
	);

	$user_id = wp_insert_user( $userdata );

	if ( ! is_wp_error( $user_id ) ) {
		echo "User created :" . $user_id;
		wpzp_update_user_meta( $user_id );
		wp_new_user_notification( $user_id, null, 'both' );
		exit;
	} else {
		echo json_encode( __( 'Error creating user, user already exists.', 'wp-zapier' ) );
		exit;
	}

	exit;
}

/**
 * Function to update existing user.
 */
function wpzp_update_user() {

	$email = isset( $_GET['email'] ) ? sanitize_email( $_GET['email'] ) : '';

	if ( ! empty( $email ) ) {
		$user = get_user_by( 'email', $email );

		// If the user doesn't exist create the user.
		$create_user = apply_filters( 'wp_zapier_create_user_on_update', true );

		if ( empty( $user )  && $create_user ) {
			wpzp_create_user();
			exit;
		}else{
			echo json_encode( __( 'User does not exist.', 'wp-zapier' ) );
			exit;
		}

		// Let's not allow calls to update administrators.
		if ( in_array( 'administrator', $user->roles ) ) {
			echo json_encode( __( 'Update denied: You are not allowed to update administrators accounts.', 'wp-zapier' ) );
			exit;
		}

		// Get all updated information
		$user_id = $user->ID;
		$new_email = isset( $_GET['new_email'] ) ? sanitize_email( $_GET['new_email'] ) : $user->user_email;
		$role = isset( $_GET['role'] ) ? sanitize_text_field( $_GET['role'] ) : '';
		$first_name = isset( $_GET['first_name'] ) ? sanitize_textarea_field( $_GET['first_name'] ) : $user->first_name;
		$last_name = isset( $_GET['last_name'] ) ? sanitize_textarea_field( $_GET['last_name'] ) : $user->last_name;
		$description = isset( $_GET['description'] ) ? sanitize_textarea_field( $_GET['description'] ) : $user->description;


		$userdata = array(
			'ID' => $user_id,
			'user_email' => $new_email,
			'first_name' => $first_name,
			'last_name' => $last_name,
			'description' => $description
		);

		if ( ! empty( $role ) ) {
			$u = new WP_User( $user_id );;
			$u->set_role( $role );
		}


		// remove the hook before updating.

		remove_action( 'profile_update', array( 'Yoohoo_WP_Zapier', 'wpzp_zapier_profile_update' ), 10, 2 );

		$user_id = wp_update_user( $userdata  );

		add_action( 'profile_update', array( 'Yoohoo_WP_Zapier', 'wpzp_zapier_profile_update' ), 10, 2 );


		if( ! is_wp_error( $user_id ) ) {
			$headers = 'From: ' . get_bloginfo( "name" ) . ' <' . get_bloginfo( "admin_email" ) . '>' . "\r\n";

 			wp_mail( $email, 'Your account has been updated.', 'Hi ' . $user->user_nicename . ',' . "\r\n" . 'Your account at ' . get_bloginfo("name") . '(' . home_url() . ') has been updated.' . "\r\n" . 'Login to your account: ' . wp_login_url(), $headers );
 			wpzp_update_user_meta( $user_id );
			echo json_encode( __( 'User updated sucessfully', 'wp-zapier' ) );
			exit;
		}else{
			echo json_encode( __( 'Error updating user.', 'wp-zapier' ) );
			exit;
		}
	}

	exit;
}

/**
 * Function to delete users and send a notification.
 */
function wpzp_delete_user() {

	$email = isset( $_GET['email'] ) ? sanitize_email( $_GET['email'] ) : '';

	if ( ! empty( $email ) ) {
		$user = get_user_by( 'email', $email );

		// Let's not allow calls to delete administrators.
		if ( in_array( 'administrator', $user->roles ) ) {
			exit;
		}

		$user_id = $user->ID;

	require_once( ABSPATH . 'wp-admin/includes/user.php' );

	// let's delete the user now and email them.
	 	if ( wp_delete_user( $user_id ) ) {
	 		$headers = 'From: ' . get_bloginfo( "name" ) . ' <' . get_bloginfo( "admin_email" ) . '>' . "\r\n";

 			wp_mail( $email, 'Your account has been deleted.', 'Hi ' . $user->user_nicename . ',' . "\r\n" . 'Your account at ' . get_bloginfo("name") . '(' . home_url() . ') has been deleted.' . "\r\n" . 'Please contact ' . get_bloginfo( 'admin_email' ) . ' if you have any further questions.', $headers );
 			echo json_encode( __( 'User successfully deleted.', 'wp-zapier' ) );
 			exit;
	 	} else {
	 		echo json_encode( __( 'Error deleting user.', 'wp-zapier' ) );
	 		exit;
	 	}
	}
	exit;
}

/**
 * Function to update usermeta.
 */
function wpzp_update_user_meta( $user_id ) {

	if ( empty( $user_id ) ) {
		echo json_encode( __( 'Unable to update user meta, user ID is missing.', 'wp-zapier') );
		return;
	}

	$fields_array = isset( $_GET['usermeta'] ) ? explode( ';', $_GET['usermeta'] ) : '';

	if ( ! empty( $fields_array ) && is_array( $fields_array ) ) {
		
		foreach( $fields_array as $key => $value ) {
			$data[] = explode( ',', $value );
		}

		foreach( $data as $key => $value ) {
			//let's update the user_meta.
			$metakey = sanitize_text_field( $value[0] );
			$meta_value = sanitize_text_field( $value[1] );

			// update user meta here.
			update_user_meta( $user_id, $metakey, $meta_value );
		}

		return;

	} else {
		echo json_encode( __( 'Unable to update user meta.', 'wp-zapier' ) );
		return;
	}
}


/**
 * Function to generate username, thanks to @PMPROPLUGIN
 * www.paidmembershipspro.com - the best WordPress Membership plugin out there.
 */
function wpzp_generate_username( $firstname = '', $lastname = '', $email = '' ) {
	global $wpdb;

	// try first initial + last name, firstname, lastname
	$firstname = preg_replace( '/[^A-Za-z]/', '', $firstname );
	$lastname = preg_replace( '/[^A-Za-z]/', '', $lastname );
	if ( $firstname && $lastname ) {
		$username = substr( $firstname, 0, 1 ) . $lastname;
	} elseif ( $firstname ) {
		$username = $firstname;
	} elseif ( $lastname ) {
		$username = $lastname;
	}

	// is it taken?
	$taken = $wpdb->get_var( "SELECT user_login FROM $wpdb->users WHERE user_login = '" . esc_sql( $username ) . "' LIMIT 1" );

	if ( ! $taken ) {
		return $username;
	}

	// try the beginning of the email address
	$emailparts = explode( '@', $email );
	if ( is_array( $emailparts ) ) {
		$email = preg_replace( '/[^A-Za-z]/', '', $emailparts[0] );
	}

	if ( ! empty( $email ) ) {
		$username = $email;
	}

	// is this taken? if not, add numbers until it works
	$taken = true;
	$count = 0;
	while ( $taken ) {
		// add a # to the end
		if ( $count ) {
			$username = preg_replace( '/[0-9]/', '', $username ) . $count;
		}

		// taken?
		$taken = $wpdb->get_var( "SELECT user_login FROM $wpdb->users WHERE user_login = '" . esc_sql( $username ) . "' LIMIT 1" );

		// increment the number
		$count++;
	}

	// must have a good username now
	return $username;
}