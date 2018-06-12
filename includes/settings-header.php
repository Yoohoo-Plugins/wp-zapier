<?php 
/**
 * Setting header for admin area.
 */
?>
<h1><?php _e( 'Zapier Settings', 'when-last-login-zapier-integration' ); ?></h1>
<hr/>
<style>
	.wp-zapier-action-links { margin-top: 20px; }
	.wp-zapier-action-links a { text-decoration:none; }
	.wp-zapier-action-links a.active { color: black; font-weight:bold; }
</style>
<div class='wp-zapier-action-links'>
	<a <?php if ( ! isset( $_REQUEST['receive_data'] ) && ! isset( $_REQUEST['license_settings'] ) ) { echo "class='active'"; } ?> href="<?php echo admin_url( 'options-general.php?page=wp-zapier-settings' ); ?>"><?php _e( 'Send Data To Zapier', 'when-last-login-zapier-integration' ); ?></a> | <a <?php if ( isset( $_REQUEST['receive_data'] ) ) { echo "class='active'"; } ?> href="<?php echo admin_url( 'options-general.php?page=wp-zapier-settings&receive_data=true' ); ?>"><?php _e( 'Receive Data From Zapier', 'when-last-login-zapier-integration' ); ?></a> | <a <?php if ( isset( $_REQUEST['license_settings'] ) ) { echo "class='active'"; } ?> href="<?php echo admin_url( 'options-general.php?page=wp-zapier-settings&license_settings=1' ); ?>"><?php _e( 'License Settings', 'when-last-login-zapier-integration' ); ?></a>
</div>
<br/>
