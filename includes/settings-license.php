
<?php

	require_once( 'settings-header.php' );

	yoohoo_activate_or_deactivate_license( $_REQUEST['yoohoo_zapier_license_key'] );

	$license = get_option( 'yoohoo_zapier_license_key' );
	$status  = get_option( 'yoohoo_zapier_license_status' );
	$expires = get_option( 'yoohoo_zapier_license_expires' );

	if ( empty( $license ) ) {
		yoohoo_admin_notice( 'If you are running Zapier Integration on a live site, we recommend an annual support license. <a href="https://yoohooplugins.com/plugins/zapier-integration/" target="_blank" rel="noopener">More Info</a>', 'warning' );
	}

	// get the date and show a notice.
	if ( ! empty( $expires ) ) {
		$expired = yoohoo_is_license_expired( $expires );
		if ( $expired ) {
			yoohoo_admin_notice( 'Your license key has expired. We recommend in renewing your annual support license to continue to get automatic updates and premium support. <a href="https://yoohooplugins.com/plugins/zapier-integration/" target="_blank" rel="noopener">More Info</a>', 'warning' );
			$expires = "Your license key has expired.";
		}
	}
?>
	<div class="wrap">
		<h2><?php _e('Plugin License Options'); ?></h2>
		<form method="post" action="">

			<table class="form-table">
				<tbody>
					<tr valign="top">
						<th scope="row" valign="top">
							<?php _e('License Key'); ?>
						</th>
						<td>
							<input id="yoohoo_zapier_license_key" name="yoohoo_zapier_license_key" type="text" class="regular-text" value="<?php esc_attr_e( $license ); ?>" />
							<label class="description" for="yoohoo_zapier_license_key"><?php _e('Enter your license key.'); ?></label><br/>
						</td>
					</tr>
					<?php 
					if ( false !== $status && $status == 'valid' ) {
					?>
					<tr>
						<th scope="row" valign="top">
							<?php _e( 'License Status' ); ?>
						</th>
						<td>
						<?php	
							if ( ! $expired ) { ?>
									<span style="color:green"><strong><?php esc_html_e( 'Active.', 'wp-zapier' ); ?></strong></span>
								<?php } else { ?>
									<span style="color:red"><strong><?php esc_html_e( 'Expired.', 'wp-zapier' ); ?></strong></span>
								<?php } ?>

								<?php if ( ! $expired ) { esc_html_e( '(' . sprintf( __( 'Expires on %s', 'wp-zapier' ), $expires ) . ')' ); } } ?>
						</td>
					</tr>
				</tbody>
			</table>
			<?php
			wp_nonce_field( 'wp_zapier_save_license' ); // Default Nonce.
			submit_button( __( 'Validate Key', 'wp-zapier' ) ); 
			?>
		</form>

<?php

/**
 * Maybe activate or deactivate the license key.
 *
 * @param string $license_key
 * @return void
 */
function yoohoo_activate_or_deactivate_license( $license_key ) {
	if ( ! empty( $license_key ) ) {
		yoohoo_activate_license( $license_key );
	} else {
		yoohoo_deactivate_license();
	}
}
/**
 * Save the license key and activate it.
 */
function yoohoo_activate_license( $license_key ) {

	if ( isset( $_REQUEST['submit'] ) && check_admin_referer( 'wp_zapier_save_license' ) ) {
		if ( ! empty( $license_key ) ) {
			$license_key = sanitize_text_field( $license_key );
			update_option( 'yoohoo_zapier_license_key', $license_key );

			// data to send in our API request
			$api_params = array(
				'edd_action' => 'activate_license',
				'license'    => $license_key,
				'item_id'    => WPZAP_PLUGIN_ID, // The ID of the item in EDD
				'url'        => home_url()
			);

			// Call the custom API.
			$response = wp_remote_post( YOOHOO_STORE, array( 'timeout' => 15, 'sslverify' => true, 'body' => $api_params ) );

			// make sure the response came back okay
			if ( is_wp_error( $response ) || 200 !== wp_remote_retrieve_response_code( $response ) ) {
				$message =  ( is_wp_error( $response ) && ! empty( $response->get_error_message() ) ) ? $response->get_error_message() : __( 'An error occurred, please try again.' );
				yoohoo_admin_notice( $message, 'error is-dismissible' );
			} else {
				$license_data = json_decode( wp_remote_retrieve_body( $response ) );
				update_option( 'yoohoo_zapier_license_status', $license_data->license );
				update_option( 'yoohoo_zapier_license_expires', $license_data->expires );
				yoohoo_admin_notice( 'License successfully activated.', 'success is-dismissible' );
			}

		}
	} elseif ( isset( $_REQUEST['submit'] ) && ! check_admin_referer( 'wp_zapier_save_license' ) ) {
		yoohoo_admin_notice( 'There was an error activating your license.', 'error is-dismissible' );
	}

}

/**
 * Deactivate the license key.
 *
 * @param string $license The license key to deactivate.
 * @return void
 */
function yoohoo_deactivate_license() {
	if ( isset( $_REQUEST['submit'] ) ) { 
		if ( empty( $_REQUEST['yoohoo_zapier_license_key'] ) ) {
			// Get stored license to deactivate.
			$license_key = get_option( 'yoohoo_zapier_license_key' );

			$api_params = array(
			'edd_action' => 'deactivate_license',
			'license' => $license_key,
			'item_id' => WPZAP_PLUGIN_ID, // the name of our product in EDD
			'url' => home_url()
		);

			// Send the remote request to deactivate the license.
			$response = wp_remote_post( YOOHOO_STORE, array( 'body' => $api_params, 'timeout' => 15, 'sslverify' => true ) );

			// Delete the options if deactivated okay.
			if ( ! is_wp_error( $response ) ) {
				delete_option( 'yoohoo_zapier_license_key' );
				delete_option( 'yoohoo_zapier_license_status' );
				delete_option( 'yoohoo_zapier_license_expires' );

				yoohoo_admin_notice( 'Deactivated license key', 'success is-dismissible' );
			}
			
		}
	}
}

/**
 * Show an admin notice in Yoohoo Plugins dashboard area.
 *
 * @param [type] $message
 * @param [type] $status
 * @return void
 */
function yoohoo_admin_notice( $message, $status ) {
	   ?>
    <div class="notice notice-<?php echo $status; ?>">
        <p><?php echo $message; ?></p>
    </div>
    <?php
}

/**
 * Check if today is after an expiry date and is the license key expired or not.
 *
 * @param date $expiry_date The expiry date to check.
 * @return bool $return Is the license key expired or not.
 */
function yoohoo_is_license_expired( $expiry_date ) {

	$today = date( 'Y-m-d H:i:s' );
 
	if ( $expiry_date < $today ) {
		$r = true;
	} else {
		$r = false;
	}

	return $r;

}