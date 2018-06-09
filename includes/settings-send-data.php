<?php 

	$settings = get_option( 'wll_zapier_settings' ); 

	$zapier_webhook_login = isset( $settings['webhook_login'] ) ? $settings['webhook_login'] : "";
	$zapier_webhook_register = isset( $settings['webhook_register'] ) ? $settings['webhook_register'] : "";
	$zapier_webhook_update = isset( $settings['webhook_update'] ) ? $settings['webhook_update'] : "";

	$zapier_notify_login = isset( $settings['notify_login'] ) ? $settings['notify_login'] : "";
	$zapier_notify_register = isset( $settings['notify_register'] ) ? $settings['notify_register'] : "";
	$zapier_notify_update = isset( $settings['notify_update'] ) ? $settings['notify_update'] : "";

?>

<?php require_once( 'settings-header.php' ); ?>

<div class="wrap">
	<h2><?php _e( 'Send Data To Zapier', 'when-last-login-zapier-integration' ); ?></h2>
	<form action="" method="POST">
		<table class="form-table">
			<tbody>

				<tr>
					<th>
						<label for="wll_zapier_notify_register"><?php _e('Notify on Register', 'when-last-login-zapier-integration'); ?></label>
					</th>
					<td>
						<input type='checkbox' name='wll_zapier_notify_register' value='1' <?php checked( 1, $zapier_notify_register ); ?> />
					</td>
				</tr>

				<tr>
					<th>
						<label for="wll_zapier_webhook_register"><?php _e('Register Webhook URL', 'when-last-login-zapier-integration'); ?></label>
					</th>
					<td>
						<input type='text' name='wll_zapier_webhook_register' size='40' value='<?php echo $zapier_webhook_register; ?>' />
					</td>
				</tr>

				<tr>
					<th>
						<label for="wll_zapier_notify_login"><?php _e('Notify on Login', 'when-last-login-zapier-integration'); ?></label>
					</th>
					<td>
						<input type='checkbox' name='wll_zapier_notify_login' value='1' <?php checked( 1, $zapier_notify_login ); ?> />
					</td>
				</tr>

				<tr>
					<th>
						<label for="wll_zapier_webhook_login"><?php _e('Login Webhook URL', 'when-last-login-zapier-integration'); ?></label>
					</th>
					<td>
						<input type='text' name='wll_zapier_webhook_login' size='40' value='<?php echo $zapier_webhook_login; ?>' />
					</td>
				</tr>

				<tr>
					<th>
						<label for="wll_zapier_notify_update"><?php _e('Notify on Profile Update', 'when-last-login-zapier-integration'); ?></label>
					</th>
					<td>
						<input type='checkbox' name='wll_zapier_notify_update' value='1' <?php checked( 1, $zapier_notify_update ); ?> />
					</td>
				</tr>

				<tr>
					<th>
						<label for="wll_zapier_webhook_update"><?php _e('Profile Update Webhook URL', 'when-last-login-zapier-integration'); ?></label>
					</th>
					<td>
						<input type='text' name='wll_zapier_webhook_update' size='40' value='<?php echo $zapier_webhook_update; ?>' />
					</td>
				</tr>

				<tr>
					<th></th>
					<td>
						<input type="submit" name="wll_save_zapier_settings"  class="button-primary" value="<?php _e('Save Settings', 'when-last-login'); ?>" />
					</td>
				</tr>

			</tbody>
		</table>	     
	</form>
</div>