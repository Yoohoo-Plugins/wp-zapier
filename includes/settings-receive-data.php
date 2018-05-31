<?php 

	$settings = get_option( 'wll_zapier_settings' ); 

	$api_key = $settings['api_key'];

?>
<?php require_once( 'settings-header.php' ); ?>

<h2><?php _e( 'Receive Data From Zapier', 'when-last-login-zapier-integration' ); ?></h2>

	<table class="form-table">
		<tbody>

			<tr>
				<th>
					<label for="wll_zapier_webhook_url"><?php _e( 'Webhook URL', 'when-last-login-zapier-integration'); ?></label>
				</th>
				<td>
					<input type='text' size='77' name='wll_zapier_webhook_url' value="<?php echo add_query_arg( array( 'wpz_webhook' => '1', 'api_key' => $api_key ), home_url( '/' ) ); ?>" readonly />
				</td>
			</tr>

			<tr>
				<th>
					<label for="wll_zapier_webhook_api_key"><?php _e( 'API Key', 'when-last-login-zapier-integration'); ?></label>
				</th>
				<td>
					<input type='text' name='wll_zapier_webhook_api_key' size='40' value='<?php echo $api_key; ?>' readonly />
				</td>
			</tr>

			<tr>
				<th colspan="2">
					<h2><?php _e( 'Available Actions', 'when-last-login-zapier-integration' ); ?></h2>
					<p style="font-weight:normal;"><?php _e( 'Below is a list of available actions when sending data from Zapier to WordPress.', 'when-last-login-zapier-integration' ); ?></p>
				</th>
			</tr>

			<tr>
				<th>
					<p><?php _e( 'create_user', 'when-last-login-zapier-integration'); ?></p>
				</th>
				<td>
					<p><strong>Required data: username, useremail</strong></p>
					<p><?php _e( 'Create a new user and send an email notification about this.', 'when-last-login-zapier-integration' ); ?></p>
					<p><?php _e( "This will not create the user if the user's email exists inside WordPress and be skipped.", "when-last-login-zapier-integration" ); ?></p>
				</td>
			</tr>

			<tr>
				<th>
					<p><?php _e( 'update_user', 'when-last-login-zapier-integration'); ?></p>
				</th>
				<td>
					<p><strong>Required data: username, useremail, first_name (optional)</strong></p>
					<p><?php _e( 'Update an existing user via email.' ); ?></p>
					<p><?php _e( "This will create the user if the user's email does not exist.", "when-last-login-zapier-integration" ); ?></p>
				</td>
			</tr>

			<tr>
				<th>
					<p><?php _e( 'delete_user', 'when-last-login-zapier-integration'); ?></p>
				</th>
				<td>
					<p><strong>Required data: username, useremail, first_name (optional)</strong></p>
					<p><?php _e( 'Delete an existing user from WordPress.' ); ?></p>
				</td>
			</tr>

		</tbody>
	</table>	     