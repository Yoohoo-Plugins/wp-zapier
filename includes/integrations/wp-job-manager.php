<?php

namespace Yoohoo\WPZapier;

class WP_Job_Manager{

    public function __construct() {
		add_filter( 'wp_zapier_event_hook_filter', array( $this, 'add_hooks' ), 10, 1 );
		add_filter( 'wp_zapier_hydrate_extender', array( $this, 'hydrate_extender' ), 10, 2 );
	}
	
	public function add_hooks( $hooks ) {
		$job_hooks = array(
			'save_post_job_listing' => array(
				'name' => 'WP Job Manager - Job saved/updated'
			),
			'job_manager_create_account_data' => array(
				'name' => 'WP Job Manager - User created'
			),
			'job_manager_job_dashboard_do_action_mark_filled' => array(
				'name' => 'WP Job Manager - Position filled'
			),
			'job_manager_job_dashboard_do_action_mark_not_filled' => array(
				'name' => 'WP Job Manager - Position not filled'
			),
			'job_manager_job_dashboard_do_action_delete' => array(
				'name' => 'WP Job Manager - Position deleted'
			),
			'job_manager_job_submitted_content_publish' => array(
				'name' => 'WP Job Manager - Job submitted'
			)
		);

		$hooks = array_merge( $hooks, $job_hooks );

		return $hooks;
	}

	public function hydrate_extension( $data, $hook ) {

		return $data;
	}

} // End of Class

add_action( 'wp_zapier_integrations_loaded', function(){
	if ( class_exists( 'WP_Job_Manager' ) ) {
		$job = new WP_Job_Manager();
	}
});