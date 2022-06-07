<?php

/**
 * Integration for Sensei LMS Outbound Events.
 * Download Reference: https://wordpress.org/plugins/sensei-lms/ 
 */

namespace Yoohoo\WPZapier;

class LearnDash {

    public function __construct() {
        add_filter( 'wp_zapier_event_hook_filter', array( $this, 'add_hooks' ), 10, 1 );
        add_filter( 'wp_zapier_hydrate_extender', array( $this, 'hydrate_extender' ), 10, 2 );

        add_filter('wp_zapier_flow_logic_argument_filter', array($this, 'register_flow_logic_arguments'));

    }

    function add_hooks( $hooks ) {

        $new_hooks = array(
            'learndash_quiz_completed' => array(
                'name' => __( 'LearnDash LMS - Quiz Completed', 'wp-zapier' )
            ),
            'learndash_topic_completed' => array(
                'name' => __( 'LearnDash LMS - Topic Completed', 'wp-zapier' )
            ),
            'learndash_lesson_completed' => array(
                'name' => __( 'LearnDash LMS - Lesson Completed', 'wp-zapier' )
            ),
            'learndash_course_completed' => array(
                'name' => __( 'LearnDash LMS - Course Completed', 'wp-zapier' )
            ),
            'learndash_user_course_access_expired' => array(
                'name' => __( 'LearnDash LMS - Course Access Expired', 'wp-zapier' )
            ),
            'learndash_update_user_activity' => array(
                'name' => __( 'LearnDash LMS - User Activity', 'wp-zapier' )
            ),
            'learndash_mark_incomplete_process' => array(
                'name' => __( 'LearnDash LMS - Marked as Incomplete', 'wp-zapier' )
            ),
        );

        $hooks = array_merge( $hooks, $new_hooks );

        return $hooks;
    }

    public function hydrate_extender( $data, $hook ) {

        $tmp_data = array();

        if( $hook == 'learndash_quiz_completed' ) { 
            $tmp_data['quiz_data'] = $data[0];            
            $tmp_data['date'] = date_i18n( 'd-m-Y H:i:s' );
            $tmp_data = apply_filters( "wp_zapier_{$hook}", $tmp_data, $data );
        }

        if( $hook == 'learndash_topic_completed' ) { 
            $tmp_data['topic_data'] = $data[0];            
            $tmp_data['date'] = date_i18n( 'd-m-Y H:i:s' );
            $tmp_data = apply_filters( "wp_zapier_{$hook}", $tmp_data, $data );
        }

        if( $hook == 'learndash_lesson_completed' ) { 
            $tmp_data['lesson_data'] = $data[0];            
            $tmp_data['date'] = date_i18n( 'd-m-Y H:i:s' );
            $tmp_data = apply_filters( "wp_zapier_{$hook}", $tmp_data, $data );
        }

        if( $hook == 'learndash_course_completed' ) { 
            $tmp_data['course_data'] = $data[0];            
            $tmp_data['date'] = date_i18n( 'd-m-Y H:i:s' );
            $tmp_data = apply_filters( "wp_zapier_{$hook}", $tmp_data, $data );
        }

        if( $hook == 'learndash_user_course_access_expired' ) { 
            $tmp_data['user'] = get_user_by( 'ID', $data[0] );
            $tmp_data['course_id'] = $data[1];
            $tmp_data['course_title'] = get_the_title( $data[1] );
            $tmp_data['date'] = date_i18n( 'd-m-Y H:i:s' );
            $tmp_data = apply_filters( "wp_zapier_{$hook}", $tmp_data, $data );
        }

        if( $hook == 'learndash_update_user_activity' ) { 
            $tmp_data['activity'] = $data[0] ;
            $tmp_data['date'] = date_i18n( 'd-m-Y H:i:s' );
            $tmp_data = apply_filters( "wp_zapier_{$hook}", $tmp_data, $data );
        }

        if( $hook == 'learndash_mark_incomplete_process' ) { 
            $tmp_data['user'] = get_user_by( 'ID', $data[0] );
            $tmp_data['course_id'] = $data[1];
            $tmp_data['course_title'] = get_the_title( $data[1] );
            $tmp_data['lesson_id'] = $data[2];
            $tmp_data['lesson_title'] = get_the_title( $data[2] );
            $tmp_data['date'] = date_i18n( 'd-m-Y H:i:s' );
            $tmp_data = apply_filters( "wp_zapier_{$hook}", $tmp_data, $data );
        }

        if ( is_array( $tmp_data ) && ! empty( $tmp_data ) ) {
            $data = $tmp_data;
        }
        
        return $data;
    }

    public function register_flow_logic_arguments($arguments){

        $course = array(
            'course_id' => 'Course ID',
            'course_title' => 'Course Title',
        );

        $arguments['learndash_quiz_completed'] = array();
        $arguments['learndash_topic_completed'] = array();
        $arguments['learndash_lesson_completed'] = array();
        $arguments['learndash_course_completed'] = array();
        $arguments['learndash_user_course_access_expired'] = $course;
        $arguments['learndash_update_user_activity'] = array();
        $arguments['learndash_mark_incomplete_process'] = $course;

        $arguments['learndash_mark_incomplete_process']['lesson_id'] = "Lesson ID";
        $arguments['learndash_mark_incomplete_process']['lesson_title'] = "Lesson Title";

        $userCopy = $arguments['profile_update'];
        if(!empty($userCopy)){
            foreach($userCopy as $key => $label){
                $arguments['learndash_user_course_access_expired']["user.{$key}"] = "{$label}";
                $arguments['learndash_mark_incomplete_process']["user.{$key}"] = "{$label}";
            }
        }

        $arguments['learndash_quiz_completed']['date'] = "Date/Time";
        $arguments['learndash_topic_completed']['date'] = "Date/Time";
        $arguments['learndash_lesson_completed']['date'] = "Date/Time";
        $arguments['learndash_course_completed']['date'] = "Date/Time";
        $arguments['learndash_user_course_access_expired']['date'] = "Date/Time";
        $arguments['learndash_update_user_activity']['date'] = "Date/Time";
        $arguments['learndash_mark_incomplete_process']['date'] = "Date/Time";

        return $arguments;
    }

}

add_action( 'wp_zapier_integrations_loaded', function(){
    if ( defined( 'LEARNDASH_VERSION' ) ) {
        $learndash_lms = new LearnDash();
    }
});