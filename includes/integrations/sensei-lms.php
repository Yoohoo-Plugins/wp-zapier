<?php

/**
 * Integration for Sensei LMS Outbound Events.
 * Download Reference: https://wordpress.org/plugins/sensei-lms/ 
 */

namespace Yoohoo\WPZapier;

class SenseiLMS {

    public function __construct() {
        add_filter( 'wp_zapier_event_hook_filter', array( $this, 'add_hooks' ), 10, 1 );
        add_filter( 'wp_zapier_hydrate_extender', array( $this, 'hydrate_extender' ), 10, 2 );
    }

    function add_hooks( $hooks ) {
        
        $new_hooks = array(
            'sensei_user_course_start' => array(
                'name' => __( 'Sensei LMS - User Starts a Course', 'wp-zapier' )
            ),
            'sensei_user_course_end' => array(
                'name' => __( 'Sensei LMS - User Completed a Course', 'wp-zapier' )
            ),
            'sensei_user_lesson_start' => array(
                'name' => __( 'Sensei LMS - User Starts a New Lesson', 'wp-zapier' )
            ),
            'sensei_user_lesson_end' => array(
                'name' => __( 'Sensei LMS - User Completed a Lesson', 'wp-zapier' )
            ),
            'sensei_user_quiz_submitted' => array(
                'name' => __( 'Sensei LMS - User Completed a Quiz', 'wp-zapier' )
            )
        );

        $hooks = array_merge( $hooks, $new_hooks );

        return $hooks;
    }

    public function hydrate_extender( $data, $hook ) {

        $tmp_data = array();

        if ( $hook == 'sensei_user_course_start' ) {
            $tmp_data['user'] = get_user_by( 'ID', $data[0] );
            $tmp_data['course_id'] = $data[1];
            $tmp_data['course_title'] = get_the_title( $tmp_data['course_id'] );
            $tmp_data['date'] = date_i18n( 'd-m-Y H:i:s' );
            $tmp_data = apply_filters( "wp_zapier_{$hook}", $tmp_data, $data );
        }
    
        if ( $hook == 'sensei_user_course_end' ) {
            $tmp_data['user'] = get_user_by( 'ID', $data[0] );
            $tmp_data['course_id'] = $data[1];
            $tmp_data['course_title'] = get_the_title( $tmp_data['course_id'] );
            $tmp_data['date'] = date_i18n( 'd-m-Y H:i:s' );
            $tmp_data = apply_filters( "wp_zapier_{$hook}", $tmp_data, $data );
        }

        if ( $hook == 'sensei_user_lesson_start' ) {
            $tmp_data['user'] = get_user_by( 'ID', $data[0] );
            $tmp_data['lesson_id'] = $data[1];
            $tmp_data['lesson_title'] = get_the_title( $data[1] );
            $tmp_data['course_id'] = Sensei()->lesson->get_course_id( $data[1] );
            $tmp_data['course_title'] = get_the_title( $tmp_data['course_id'] );
            $tmp_data['date'] = date_i18n( 'd-m-Y H:i:s' );
            $tmp_data = apply_filters( "wp_zapier_{$hook}", $tmp_data, $data );
        }

        if ( $hook == 'sensei_user_lesson_end' ) {
            $tmp_data['user'] = get_user_by( 'ID', $data[0] );
            $tmp_data['lesson_id'] = $data[1];
            $tmp_data['lesson_title'] = get_the_title( $data[1] );
            $tmp_data['course_id'] = Sensei()->lesson->get_course_id( $data[1] );
            $tmp_data['course_title'] = get_the_title( $tmp_data['course_id'] );
            $tmp_data['date'] = date_i18n( 'd-m-Y H:i:s' );
            $tmp_data = apply_filters( "wp_zapier_{$hook}", $tmp_data, $data );
        }

        if ( $hook == 'sensei_user_quiz_submitted' ) {
            $tmp_data['user'] = get_user_by( 'ID', $data[0] );
            $tmp_data['quiz_id'] = $data[1];
            $tmp_data['grade'] = $data[2];
            $tmp_data['quiz_pass_percentage'] = $data[3];
            $tmp_data['quiz_grade_type'] = $data[4];
            $tmp_data['date'] = date_i18n( 'd-m-Y H:i:s' );
            $tmp_data = apply_filters( "wp_zapier_{$hook}", $tmp_data, $data );
        }    
    
        if ( is_array( $tmp_data ) && ! empty( $tmp_data ) ) {
            $data = $tmp_data;
        }
    
        return $data;
    }

}

add_action( 'wp_zapier_integrations_loaded', function(){
    if ( class_exists( 'Sensei_Main' ) || class_exists( 'Sensei_Compat_Admin' ) ) {
        $sensei_lms = new SenseiLMS();
    }
});