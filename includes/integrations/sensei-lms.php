<?php

namespace Yoohoo\WPZapier;

class SenseiLMS {

    public function __construct() {
        add_filter( 'wp_zapier_event_hook_filter', array( $this, 'getEventHooks' ), 10, 1 );
		add_filter( 'wp_zapier_base_object_extender', array( $this, 'baseObjectFilters' ), 10, 3 );
    }

    function getEventHooks( $hooks ) {
        if ( class_exists( 'Sensei_Main' ) ) {
            $new_hooks = array(
                'sensei_after_completed_user_courses' => array(
                    'name' => 'Sensei - User Course Completed'
                ),
            );

            $hooks = array_merge( $hooks, $new_hooks );

        }
        return $hooks;
    }

    function baseObjectFilters( $formatted, $data, $hook ) {

        // do stuff here. 
        return $formatted;
    }

}

add_action( 'wp_zapier_integrations_loaded', function(){
	$sensei_lms = new SenseiLMS();
});