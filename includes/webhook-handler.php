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

	echo $username . ' and ' . $email;

	// create the user with generated password

	// if user exists return / maybe email the admin?
}