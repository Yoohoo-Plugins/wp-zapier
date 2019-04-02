<?php 
/**
 * Setting header for admin area.
 */
?>
<div class="wp-zapier-settings-header" style="font-size:25px;margin:2% 0 0 0;display:inline-block;">
	<?php _e( 'WP Zapier - Settings', 'wp-zapier' );?>
	<div class="wp-zapier-settings-header-meta" style="font-size:14px;margin-left:5px;display:inline-block;">
		<?php echo "v" . WPZP_VERSION; ?>
		<span style="margin-left:5px;"><a href="https://yoohooplugins.com/?s=zapier" class="button-primary" style="margin-top:-4px;"><?php _e( 'Documentation', 'wp-zapier' ); ?></a></span>
	</div>
</div>

<div class="wrap"><h2 style="margin:0;font-size:1px;"></h2></div>

<nav class='wp-zapier-action-links nav-tab-wrapper'>
	<a href="<?php echo admin_url( 'options-general.php?page=wp-zapier-settings' ); ?>" class="nav-tab <?php if( ! isset( $_REQUEST['receive_data'] ) && ! isset( $_REQUEST['license_settings'] ) ) { echo "nav-tab-active"; } ?>"><?php _e( 'Send Data To Zapier', 'wp-zapier' ); ?></a>

	<a href="<?php echo admin_url( 'options-general.php?page=wp-zapier-settings&receive_data=true' ); ?>" class="nav-tab <?php if( isset( $_REQUEST['receive_data'] ) && $_REQUEST['receive_data'] == 'true' ) { echo "nav-tab-active"; } ?>"><?php _e( 'Receive Data From Zapier', 'wp-zapier' ); ?></a>

	<a href="<?php echo admin_url( 'options-general.php?page=wp-zapier-settings&license_settings=true' ); ?>" class="nav-tab <?php if( isset( $_REQUEST['license_settings'] ) && $_REQUEST['license_settings'] == 'true' ) { echo "nav-tab-active"; } ?>"><?php _e( 'License Settings', 'wp-zapier' ); ?></a>

	<?php do_action( 'wp_zapier_settings_header_tabs' ); ?>
</nav>
<br/>
