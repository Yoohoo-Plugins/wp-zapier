<?php

// Don't access this file directly to do stuff.
defined( 'ABSPATH' ) or exit;

$zapier_settings = get_option( 'wp_zapier_settings', true );

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

/**
 * Include additional modules here to make things a whole lot easier.
 * Loads modules based on other plugins activated.
 * e.g. Include <<DIR>>/paid-memberships-pro.php
 */

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

	default:
		echo json_encode( __( 'Please choose an action.', 'wp-zapier' ) );
		break;
}

/**
 * Function to create the user within WordPress.
 *
 */
function wpzp_create_user(){

	// Get the params
	$username = isset( $_REQUEST['username'] ) ? sanitize_text_field( $_REQUEST['username'] ) : '';
	$display_name = isset( $_REQUEST['display_name'] ) ? sanitize_text_field( $_REQUEST['display_name'] ) : '';
	$email = isset( $_REQUEST['email'] ) ? sanitize_email( $_REQUEST['email'] ) : '';
	$first_name = isset( $_REQUEST['first_name'] ) ? sanitize_text_field( $_REQUEST['first_name'] ) : '';
	$last_name = isset( $_REQUEST['last_name'] ) ? sanitize_text_field( $_REQUEST['last_name'] ) : '';
	$role = isset( $_REQUEST['role'] ) ? sanitize_text_field( $_REQUEST['role'] ) : 'subscriber';
	$user_pass = isset( $_REQUEST['user_pass'] ) ? $_REQUEST['user_pass'] : wp_generate_password( 20, true, false );
	$user_url = isset( $_REQUEST['user_url'] ) ? esc_url( $_REQUEST['user_url'] ) : '';

	if ( empty( $email ) ) {
		echo json_encode( __( ' An email is required to create a user.', 'wp-zapier' ) );
		exit;
	}

	$dynamic_username = apply_filters( 'wp_zapier_generate_username', true );

	// Generate the username if username is missing and the filter is set to true.
	if ( empty( $username ) && $dynamic_username ) {
		$username = wpzp_generate_username( $first_name, $last_name, $email );
	}

	$userdata = array(
		'user_login' => $username,
		'display_name' => $display_name,
		'user_email' => $email,
		'first_name' => $first_name,
		'last_name' => $last_name,
		'role' => $role,
		'user_pass' => $user_pass,
		'user_url' => $user_url
	);

	$userdata = apply_filters( 'wp_zapier_userdata_before_create', $userdata );

	$user_id = wp_insert_user( $userdata );

	do_action( 'wp_zapier_before_create_user', $user_id );

	if ( ! is_wp_error( $user_id ) ) {
		echo "User created :" . $user_id;

		// try to update usermeta.
		wpzp_update_user_meta( $user_id );

		if ( apply_filters( 'wp_zapier_send_new_user_email', true ) ) {
			wp_new_user_notification( $user_id, null, 'both' );	
		}
		

		do_action( 'wp_zapier_after_create_user', $user_id );

		echo json_encode( array( 'status' => 'success', 'response' => __( 'user created successfully', 'wp-zapier' ), 'user_id' => $user_id ) );
		exit;
	} else {
		$error = $user_id->get_error_message();
		echo json_encode( $error );
		exit;
	}

	exit;
}

/**
 * Function to update existing user.
 */
function wpzp_update_user() {

	$user = wpzp_get_user();

	$create_user = apply_filters( 'wp_zapier_create_user_on_update_webhook', true );

	// If the user doesn't exist create the user.
	if ( empty( $user ) ) {
		if ( $create_user ) {
			wpzp_create_user();
		} else {
			echo json_encode( __( 'User does not exist.', 'wp-zapier' ) );
			exit;
		}
		
		exit;
	}

	// Let's not allow calls to update administrators.
	if ( in_array( 'administrator', $user->roles ) ) {
		echo json_encode( __( 'Update denied: You are not allowed to update administrators accounts.', 'wp-zapier' ) );
		exit;
	}

	// Get all updated information
	$user_id = $user->ID;
	$new_email = isset( $_REQUEST['new_email'] ) ? sanitize_email( $_REQUEST['new_email'] ) : $user->user_email;
	$role = isset( $_REQUEST['role'] ) ? strtolower( sanitize_text_field( $_REQUEST['role'] ) ) : '';
	$first_name = isset( $_REQUEST['first_name'] ) ? sanitize_textarea_field( $_REQUEST['first_name'] ) : $user->first_name;
	$last_name = isset( $_REQUEST['last_name'] ) ? sanitize_textarea_field( $_REQUEST['last_name'] ) : $user->last_name;
	$description = isset( $_REQUEST['description'] ) ? sanitize_textarea_field( $_REQUEST['description'] ) : $user->description;
	$user_url = isset( $_REQUEST['user_url'] ) ? esc_url( $_REQUEST['user_url'] ) : $user->user_url;
	$user_pass = isset( $_REQUEST['user_pass'] ) ? sanitize_text_field( $_REQUEST['user_pass'] ) : '';

	$userdata = array(
		'ID' => $user_id,
		'user_email' => $new_email,
		'first_name' => $first_name,
		'last_name' => $last_name,
		'description' => $description,
		'user_url' => $user_url,
		'user_pass' => $user_pass
	);

	$userdata = apply_filters( 'wp_zapier_userdata_before_update', $userdata );

	if ( ! empty( $role ) ) {
		$u = new WP_User( $user_id );;
		$u->set_role( $role );
	}

	do_action( 'wp_zapier_before_update_user', $user );

	// remove the hook before updating.
	remove_action( 'profile_update', array( 'Yoohoo_WP_Zapier', 'wpzp_zapier_profile_update' ), 10, 2 );

	$user_id = wp_update_user( $userdata  );

	do_action( 'wp_zapier_after_update_user', $user_id );

	add_action( 'profile_update', array( 'Yoohoo_WP_Zapier', 'wpzp_zapier_profile_update' ), 10, 2 );

	if( ! is_wp_error( $user_id ) ) {

		$send_update_email = apply_filters( 'wp_zapier_send_update_email', true );

	 	if( $send_update_email ) {

			$headers = 'From: ' . get_bloginfo( "name" ) . ' <' . get_bloginfo( "admin_email" ) . '>' . "\r\n";

				wp_mail( $new_email, 'Your account has been updated.', 'Hi ' . $user->user_nicename . ',' . "\r\n" . 'Your account at ' . get_bloginfo("name") . '(' . home_url() . ') has been updated.' . "\r\n" . 'Login to your account: ' . wp_login_url(), $headers );
			}
			
			// Update user meta.
			wpzp_update_user_meta( $user_id );
			echo json_encode( array( 'status' => 'success', 'response' => __( 'user updated successfully', 'wp-zapier' ), 'user_id' => $user_id ) );
		exit;
	}else{
		echo json_encode( __( 'Error updating user.', 'wp-zapier' ) );
		exit;
	}

exit;
}

/**
 * Function to delete users and send a notification.
 */
function wpzp_delete_user() {

	$user = wpzp_get_user();

	// Let's not allow calls to delete administrators.
	if ( in_array( 'administrator', $user->roles ) ) {
		exit;
	}

	$user_id = $user->ID;

	require_once( ABSPATH . 'wp-admin/includes/user.php' );

	do_action( 'wp_zapier_before_delete_user', $user );

		// let's delete the user now and email them.
	 if ( wp_delete_user( $user_id ) ) {

	 	do_action( 'wp_zapier_after_delete_user' );

	 	$send_delete_email = apply_filters( 'wp_zapier_send_delete_email', true );

	 	if( $send_delete_email ) {

		 	$headers = 'From: ' . get_bloginfo( "name" ) . ' <' . get_bloginfo( "admin_email" ) . '>' . "\r\n";

	 		wp_mail( $email, 'Your account has been deleted.', 'Hi ' . $user->user_nicename . ',' . "\r\n" . 'Your account at ' . get_bloginfo("name") . '(' . home_url() . ') has been deleted.' . "\r\n" . 'Please contact ' . get_bloginfo( 'admin_email' ) . ' if you have any further questions.', $headers );

 		}
 			echo json_encode( __( 'User successfully deleted.', 'wp-zapier' ) );
 			exit;
	 	} else {
	 		echo json_encode( __( 'Error deleting user.', 'wp-zapier' ) );
	 		exit;
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

	$fields_array = isset( $_REQUEST['usermeta'] ) ? explode( ';', $_REQUEST['usermeta'] ) : '';

	if ( ! empty( $fields_array ) && is_array( $fields_array ) ) {
		
		foreach( $fields_array as $key => $value ) {
			$data[] = explode( ',', $value );
		}

		foreach( $data as $key => $value ) {
			//let's update the user_meta.
			$metakey = isset( $value[0]) ? sanitize_text_field( $value[0] ) : '';
			$meta_value = isset( $value[1] ) ? sanitize_text_field( $value[1] ) : '';

			// update user meta here.
			update_user_meta( $user_id, $metakey, $meta_value );
		}

		return;

	} else {
		echo json_encode( __( 'User meta fields not passed through to WordPress. User meta not updated. ', 'wp-zapier' ) );
		return;
	}
}


/**
 * Function to generate username, thanks to @PMPROPLUGIN
 * www.paidmembershipspro.com - the best WordPress Membership plugin out there.
 */
function wpzp_generate_username( $first_name = '', $last_name = '', $email = '' ) {
	global $wpdb;

	// try first initial + last name, firstname, lastname
	$first_name = preg_replace( '/[^A-Za-z]/', '', $first_name );
	$last_name = preg_replace( '/[^A-Za-z]/', '', $last_name );
	$username = '';
	if ( $first_name && $last_name ) {
		$username = substr( $first_name, 0, 1 ) . $last_name;
	} elseif ( $first_name ) {
		$username = $first_name;
	} elseif ( $last_name ) {
		$username = $last_name;
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

	$username = apply_filters( 'wp_zapier_generate_username_filter', sanitize_user( $username ) );


	// must have a good username now
	return $username;
}

/**
 * Function to get the user object from ID, email or username.
 * @return user object The WP user object.
 * @since 1.3
 */
function wpzp_get_user() {

	$email = isset( $_REQUEST['email'] ) ? sanitize_email( $_REQUEST['email'] ) : '';
	$username = isset( $_REQUEST['username'] ) ? sanitize_user( $_REQUEST['username'] ) : '';
	$user_id = isset( $_REQUEST['user_id'] ) ? intval( $_REQUEST['user_id'] ) : '';

	if ( ! empty( $email ) ) {
		$user = get_user_by( 'email', $email );
	} elseif( ! empty( $username ) && empty( $email ) ) {
		$user = get_user_by( 'login', $username );
	} elseif( ! empty( $user_id ) && empty( $email ) && empty( $username ) ) {
		$user = get_user_by( 'ID', $user_id );
	} else {
		echo json_encode( __('Unable to get user, please pass in email, user_id or username.', 'wp-zapier' ) );
		exit;
	}

	return $user;

}
