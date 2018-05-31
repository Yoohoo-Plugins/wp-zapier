<?php

// Don't access this file directly to do stuff.
defined( 'ABSPATH' ) or exit;

$zapier_settings = get_option( 'wll_zapier_settings', true );

$api_key = ! empty( $_REQUEST['api_key'] ) ? sanitize_key( $_REQUEST['api_key'] ) : '';
$action = ! empty( $_REQUEST['action'] ) ? sanitize_text_field( $_REQUEST['action'] ) : '';

// set the output to be JSON.
header( 'Content-Type: application/json' );

// Bail if the API key is not valid.
if ( $api_key != $zapier_settings['api_key'] ) {
	status_header( 403 );
	echo json_encode( __( 'API Key not valid or missing.', 'when-last-login-zapier-integration' ) );
	exit;
}

switch ( $action ) {
	case 'create_user':
		wllz_create_user();
		break;

	case 'update_user':
		wllz_update_user();
		break;

	case 'delete_user':
		wllz_delete_user();
		break;
}


/**
 * Function to create the user within WordPress.
 *
 */
function wllz_create_user(){

	// Get the params
	$username = isset( $_GET['username'] ) ? sanitize_text_field( $_GET['username'] ) : '';
	$email = isset( $_GET['email'] ) ? sanitize_email( $_GET['email'] ) : '';
	$first_name = isset( $_GET['first_name'] ) ? sanitize_text_field( $_GET['first_name'] ) : '';
	$last_name = isset( $_GET['last_name'] ) ? sanitize_text_field( $_GET['last_name'] ) : '';
	$role = isset( $_GET['role'] ) ? sanitize_text_field( $_GET['role'] ) : 'subscriber';
	$user_pass = wp_generate_password( 20, true, false );

	if ( empty( $email ) || empty( $username ) ) {
		echo json_encode( __( ' A username and email is required.', 'when-last-login-zapier-integration' ) );
		exit;
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
		wp_new_user_notification( $user_id, null, 'both' );
	} else {
		echo json_encode( __( 'Error creating user, user already exists.', 'when-last-login-zapier-integration' ) );
	}

	exit;
}

/**
 * Function to update existing user.
 */
function wllz_update_user() {

	$email = isset( $_GET['email'] ) ? sanitize_email( $_GET['email'] ) : '';

	if ( ! empty( $email ) ) {
		$user = get_user_by( 'email', $email );

		// Bail, if the user does not exist.
		if ( empty( $user ) ) {
			echo json_encode( __( 'User does not exist.', 'when-last-login-zapier-integration' ) );
			exit;
		}

		// Let's not allow calls to update administrators.
		if ( in_array( 'administrator', $user->roles ) ) {
			echo json_encode( __( 'Unable to update this user.', 'when-last-login-zapier-integration' ) );
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

		remove_action( 'profile_update', array( 'WhenLastLoginZapier', 'wllz_zapier_profile_update' ), 10, 2 );

		$user_id = wp_update_user( $userdata  );

		add_action( 'profile_update', array( 'WhenLastLoginZapier', 'wllz_zapier_profile_update' ), 10, 2 );


		if( ! is_wp_error( $user_id ) ) {
			$headers = 'From: ' . get_bloginfo( "name" ) . ' <' . get_bloginfo( "admin_email" ) . '>' . "\r\n";

 			wp_mail( $email, 'Your account has been updated.', 'Hi ' . $user->user_nicename . ',' . "\r\n" . 'Your account at ' . get_bloginfo("name") . '(' . home_url() . ') has been updated.' . "\r\n" . 'Login to your account: ' . wp_login_url(), $headers );
			echo json_encode( __( 'User updated sucessfully', 'when-last-login-zapier-integration' ) );
		}else{
			echo json_encode( __( 'Error updating user.', 'when-last-login-zapier-integration' ) );
			exit;
		}
	}

	exit;
}

/**
 * Function to delete users and send a notification.
 */
function wllz_delete_user() {

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
 			echo json_encode( __( 'User successfully deleted.', 'when-last-login-zapier-integration' ) );
 			exit;
	 	} else {
	 		echo json_encode( __( 'Error deleting user.', 'when-last-login-zapier-integration' ) );
	 		exit;
	 	}
	}
	exit;
}