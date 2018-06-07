<?php 
/**
 * Setting header for admin area.
 */
?>
<h1><?php _e( 'Zapier Settings', 'when-last-login-zapier-integration' ); ?></h1>
<hr/>
<style>
	.wll-action-links { margin-top: 20px; }
	.wll-action-links a { text-decoration:none; }
	.wll-action-links a.active { color: black; font-weight:bold; }
</style>
<div class='wll-action-links'>
	<a <?php if ( ! isset( $_REQUEST['receive_data'] ) && ! isset( $_REQUEST['license_settings'] ) ) { echo "class='active'"; } ?> href="<?php echo admin_url( 'options-general.php?page=wllz-zapier-settings' ); ?>"><?php _e( 'Send Data To Zapier', 'when-last-login-zapier-integration' ); ?></a> | <a <?php if ( isset( $_REQUEST['receive_data'] ) ) { echo "class='active'"; } ?> href="<?php echo admin_url( 'options-general.php?page=wllz-zapier-settings&receive_data=true' ); ?>"><?php _e( 'Receive Data From Zapier', 'when-last-login-zapier-integration' ); ?></a> | <a <?php if ( isset( $_REQUEST['license_settings'] ) ) { echo "class='active'"; } ?> href="<?php echo admin_url( 'options-general.php?page=wllz-zapier-settings&license_settings=1' ); ?>"><?php _e( 'License Settings', 'when-last-login-zapier-integration' ); ?></a>
</div>
<br/>
