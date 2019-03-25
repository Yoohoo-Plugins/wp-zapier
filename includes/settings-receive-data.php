<?php 

	$settings = get_option( 'wp_zapier_settings' ); 

	$api_key = $settings['api_key'];

?>
<?php require_once( 'settings-header.php' ); ?>


<div class="wrap">
	<h2><?php _e( 'Receive Data From Zapier', 'wp-zapier' ); ?></h2>
	<table class="form-table">
		<tbody>

			<tr>
				<th>
					<label for="wp_zapier_webhook_url"><?php _e( 'Webhook URL', 'wp-zapier'); ?></label>
				</th>
				<td>
					<input type='text' size='77' name='wp_zapier_webhook_url' value="<?php echo add_query_arg( array( 'wpz_webhook' => '1', 'api_key' => $api_key ), home_url( '/' ) ); ?>" readonly /><br/>
					<small><?php _e( 'Please copy entire URL when adding this to Zapier.', 'wp-zapier' ); ?></small>

				</td>
			</tr>

			<tr>
				<th>
					<label for="wp_zapier_webhook_api_key"><?php _e( 'API Key', 'wp-zapier'); ?></label>
				</th>
				<td>
					<input type='text' name='wp_zapier_webhook_api_key' size='40' value='<?php echo $api_key; ?>' readonly />	
				</td>
			</tr>

			<tr>
				<th colspan="2">
					<h2><?php _e( 'Available Actions', 'wp-zapier' ); ?></h2>
					<p style="font-weight:normal;"><?php _e( 'Below is a list of available actions when sending data from Zapier to WordPress.', 'wp-zapier' ); ?></p>
				</th>
			</tr>

			<tr>
				<th>

					<p><?php _e( 'create_user', 'wp-zapier'); ?></p>
				</th>
				<td>
					<p><strong><?php _e( 'Accepts', 'wp-zapier'); ?>: email*, username, first_name, last_name, role, usermeta**</strong></p>
					<p><?php _e( 'Create a new user and send an email notification to both the user and site administrator.', 'wp-zapier' ); ?></p>
					<p><?php _e( "This will not create the user if the user's email exists inside WordPress and will be skipped.", "wp-zapier" ); ?></p>
				</td>
			</tr>

			<tr>
				<th>
					<p><?php _e( 'update_user', 'wp-zapier'); ?></p>
				</th>
				<td>
					<p><strong><?php _e( 'Accepts', 'wp-zapier'); ?>: email*, new_email, first_name, last_name, role, usermeta**</strong></p>
					<p><?php _e( 'Update existing user data via email.' ); ?></p>
					<p><?php _e( "This will create a new user if the user's email does not exist.", "wp-zapier" ); ?></p>
				</td>
			</tr>

			<tr>
				<th>
					<p><?php _e( 'delete_user', 'wp-zapier'); ?></p>
				</th>
				<td>
					<p><strong><?php _e( 'Accepts', 'wp-zapier'); ?>: email*</strong></p>
					<p><?php _e( 'Delete an existing user from WordPress which will delete all their usermeta.', 'wp-zapier' ); ?></p>
				</td>
			</tr>

		</tbody>
	</table>
</div>

<p><small>* <?php _e( 'Required fields.', 'wp-zapier' ); ?></small></p>
<p><small>** <?php _e( sprintf( 'Please see %s for the layout needed for usermeta fields.', '<a href="https://yoohooplugins.com/documentation/" target="_blank" rel="noopener nofollow">documentation</a>' ), 'wp-zapier' );?></small></p>
