<?php

/**
 * Integration for Sensei LMS Outbound Events.
 * Download Reference: https://wordpress.org/plugins/sensei-lms/ 
 */

namespace Yoohoo\WPZapier;

class TutorLMS {

    public function __construct() {
        add_filter( 'wp_zapier_event_hook_filter', array( $this, 'add_hooks' ), 10, 1 );
        add_filter( 'wp_zapier_hydrate_extender', array( $this, 'hydrate_extender' ), 10, 2 );
    }

    function add_hooks( $hooks ) {

        $new_hooks = array(
            'tutor_after_rating_placed' => array(
                'name' => __( 'Tutor LMS - After Rating Placed', 'wp-zapier' )
            ),
            'tutor_after_add_question' => array(
                'name' => __( 'Tutor LMS - After Question Added', 'wp-zapier' )
            ),
            'tutor_after_answer_to_question' => array(
                'name' => __( 'Tutor LMS - After Answer to Question Added', 'wp-zapier' )
            ),
            'tutor_course_complete_after' => array(
                'name' => __( 'Tutor LMS - Course Completed', 'wp-zapier' )
            ),
            'tutor_lesson_completed_after' => array(
                'name' => __( 'Tutor LMS - Lesson Completed', 'wp-zapier' )
            ),
            'tutor_after_approved_instructor' => array(
                'name' => __( 'Tutor LMS - Approved Instructor', 'wp-zapier' )
            ),
            'tutor_after_blocked_instructor' => array(
                'name' => __( 'Tutor LMS - Blocked Instructor', 'wp-zapier' )
            ),
            'tutor_after_enroll' => array(
                'name' => __( 'Tutor LMS - Enrolled', 'wp-zapier' )
            )
        );

        $hooks = array_merge( $hooks, $new_hooks );

        return $hooks;
    }

    public function hydrate_extender( $data, $hook ) {

        $tmp_data = array();

        if( $hook == 'tutor_after_rating_placed' ) { 
            $tmp_data['data'] = get_comment( $data[0] );
            $tmp_data['date'] = date_i18n( 'd-m-y H:i:s' );
            $tmp_data = apply_filters( "wp_zapier_{$hook}", $tmp_data, $data );
        }
        if( $hook == 'tutor_after_add_question' ) { 
            $tmp_Data['course'] = get_post( $data[0] );
            $tmp_data['data'] = get_comment( $data[1] );
            $tmp_data['date'] = date_i18n( 'd-m-y H:i:s' );
            $tmp_data = apply_filters( "wp_zapier_{$hook}", $tmp_data, $data );
        }
        if( $hook == 'tutor_after_answer_to_question' ) { 
            $tmp_data['data'] = get_comment( $data[0] );
            $tmp_data['date'] = date_i18n( 'd-m-y H:i:s' );
            $tmp_data = apply_filters( "wp_zapier_{$hook}", $tmp_data, $data );
        }
        if( $hook == 'tutor_course_complete_after' ) { 
            $tmp_data['data'] = get_post( $data[0] );
            $tmp_data['date'] = date_i18n( 'd-m-y H:i:s' );
            $tmp_data = apply_filters( "wp_zapier_{$hook}", $tmp_data, $data );
        }
        if( $hook == 'tutor_lesson_completed_after' ) { 
            $tmp_data['data'] = get_post( $data[0] );
            $tmp_data['date'] = date_i18n( 'd-m-y H:i:s' );
            $tmp_data = apply_filters( "wp_zapier_{$hook}", $tmp_data, $data );
        }
        if( $hook == 'tutor_after_approved_instructor' ) { 
            $tmp_data['data'] = get_user_by( 'id', $data[0] );
            $tmp_data['date'] = date_i18n( 'd-m-y H:i:s' );
            $tmp_data = apply_filters( "wp_zapier_{$hook}", $tmp_data, $data );
        }
        if( $hook == 'tutor_after_blocked_instructor' ) { 
            $tmp_data['data'] = get_user_by( 'id', $data[0] );
            $tmp_data['date'] = date_i18n( 'd-m-y H:i:s' );
            $tmp_data = apply_filters( "wp_zapier_{$hook}", $tmp_data, $data );
        }
        if( $hook == 'tutor_after_enroll' ) { 
            $tmp_data['data'] = get_post( $data[0] );
            $tmp_data['is_enrolled'] = get_post( $data[1] );
            $tmp_data['date'] = date_i18n( 'd-m-y H:i:s' );
            $tmp_data = apply_filters( "wp_zapier_{$hook}", $tmp_data, $data );
        }

        if ( is_array( $tmp_data ) && ! empty( $tmp_data ) ) {
            $data = $tmp_data;
        }
        
        return $data;
    }

}

add_action( 'wp_zapier_integrations_loaded', function(){
    if ( defined( 'TUTOR_VERSION' ) ) {
        $tutor_lms = new TutorLMS();
    }
});