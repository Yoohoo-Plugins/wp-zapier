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
		# code...
		break;

	case 'delete_user':
		# code...
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

	if( ! is_wp_error( $user_id ) ) {
		echo "User created :" . $user_id;
		wp_new_user_notification( $user_id, null, 'both' );
	}else{
		echo json_encode( __( 'Error creating user, user already exists.', 'when-last-login-zapier-integration' ) );

	}

	exit;

}