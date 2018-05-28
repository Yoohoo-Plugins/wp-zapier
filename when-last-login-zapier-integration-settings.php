<?php 

	$settings = get_option( 'wll_zapier_settings' ); 

	$zapier_webhook_login = isset( $settings['webhook_login'] ) ? $settings['webhook_login'] : "";
	$zapier_webhook_register = isset( $settings['webhook_register'] ) ? $settings['webhook_register'] : "";
	$zapier_webhook_update = isset( $settings['webhook_update'] ) ? $settings['webhook_update'] : "";

	$zapier_notify_login = isset( $settings['notify_login'] ) ? $settings['notify_login'] : "";
	$zapier_notify_register = isset( $settings['notify_register'] ) ? $settings['notify_register'] : "";
	$zapier_notify_update = isset( $settings['notify_update'] ) ? $settings['notify_update'] : "";

?>
<tr>
	<td colspan="2"><h2><?php _e('When Last Login - Zapier Integration Settings', 'when-last-login-stats'); ?></h2></td>
</tr>

<tr>
	<th><?php _e('Notify on Register', 'when-last-login-zapier-integration'); ?></th>
	<td><input type='checkbox' name='wll_zapier_notify_register' value='1' <?php checked( 1, $zapier_notify_register ); ?> /></td>
</tr>

<tr>
	<th><?php _e('Register Webhook URL', 'when-last-login-zapier-integration'); ?></th>
	<td><input type='text' style='width: 350px;' name='wll_zapier_webhook_register' value='<?php echo $zapier_webhook_register; ?>' /></td>
</tr>

<tr><td colspan='2'><hr/></td></tr>

<tr>
	<th><?php _e('Notify on Login', 'when-last-login-zapier-integration'); ?></th>
	<td><input type='checkbox' name='wll_zapier_notify_login' value='1' <?php checked( 1, $zapier_notify_login ); ?> /></td>
</tr>

<tr>
	<th><?php _e('Login Webhook URL', 'when-last-login-zapier-integration'); ?></th>
	<td><input type='text' style='width: 350px;' name='wll_zapier_webhook_login' value='<?php echo $zapier_webhook_login; ?>' /></td>
</tr>



<tr><td colspan='2'><hr/></td></tr>

<tr>
	<th><?php _e('Notify on Profile Update', 'when-last-login-zapier-integration'); ?></th>
	<td><input type='checkbox' name='wll_zapier_notify_update' value='1' <?php checked( 1, $zapier_notify_update ); ?> /></td>
</tr>

<tr>
	<th><?php _e('Profile Update Webhook URL', 'when-last-login-zapier-integration'); ?></th>
	<td><input type='text' style='width: 350px;' name='wll_zapier_webhook_update' value='<?php echo $zapier_webhook_update; ?>' /></td>
</tr>

<tr>
    <th><input type="submit" name="wll_save_zapier_settings"  class="button-primary" value="<?php _e('Save Settings', 'when-last-login'); ?>" /></th>
    <td></td>
</tr>