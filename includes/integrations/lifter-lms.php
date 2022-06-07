<?php

/**
 * Integration for Sensei LMS Outbound Events.
 * Download Reference: https://wordpress.org/plugins/sensei-lms/ 
 */

namespace Yoohoo\WPZapier;

class LifterLMS {

    public function __construct() {
        add_filter( 'wp_zapier_event_hook_filter', array( $this, 'add_hooks' ), 10, 1 );
        add_filter( 'wp_zapier_hydrate_extender', array( $this, 'hydrate_extender' ), 10, 2 );

        add_filter('wp_zapier_flow_logic_argument_filter', array($this, 'register_flow_logic_arguments'));
    }

    function add_hooks( $hooks ) {

        $new_hooks = array(
            'llms_user_enrolled_in_course' => array(
                'name' => __( 'Lifter LMS - User Enrolls In a Course', 'wp-zapier' )
            ),
            'llms_user_added_to_membership_level' => array(
                'name' => __( 'Lifter LMS - User Added to Membership Level', 'wp-zapier' )
            ),
            'llms_user_removed_from_course' => array(
                'name' => __( 'Lifter LMS - User Removed from Course', 'wp-zapier' )
            ),
            'llms_user_removed_from_membership_level' => array(
                'name' => __( 'Lifter LMS - User Removed from Membership Level', 'wp-zapier' )
            ),
            'lifterlms_lesson_completed' => array(
                'name' => __( 'Lifter LMS - User Completed a Lesson', 'wp-zapier' )
            )
        );

        $hooks = array_merge( $hooks, $new_hooks );

        return $hooks;
    }

    public function hydrate_extender( $data, $hook ) {

        $tmp_data = array();
       
        if( $hook == 'llms_user_enrolled_in_course' ) { 
            $tmp_data['user'] = get_user_by( 'ID', $data[0] );
            $tmp_data['course_id'] = $data[1];
            $tmp_data['course_title'] = get_the_title( $data[1] );
            $tmp_data['date'] = date_i18n( 'd-m-Y H:i:s' );
            $tmp_data = apply_filters( "wp_zapier_{$hook}", $tmp_data, $data );
        }
        if( $hook == 'llms_user_added_to_membership_level' ) { 
           $tmp_data['user'] = get_user_by( 'ID', $data[0] );
            $tmp_data['course_id'] = $data[1];
            $tmp_data['course_title'] = get_the_title( $data[1] );
            $tmp_data['date'] = date_i18n( 'd-m-Y H:i:s' );
            $tmp_data = apply_filters( "wp_zapier_{$hook}", $tmp_data, $data );
        }
        if( $hook == 'llms_user_removed_from_course' ) { 
            $tmp_data['user'] = get_user_by( 'ID', $data[0] );
            $tmp_data['course_id'] = $data[1];
            $tmp_data['course_title'] = get_the_title( $data[1] );
            $tmp_data['date'] = date_i18n( 'd-m-Y H:i:s' );
            $tmp_data = apply_filters( "wp_zapier_{$hook}", $tmp_data, $data );
        }
        if( $hook == 'llms_user_removed_from_membership_level' ) { 
            $tmp_data['user'] = get_user_by( 'ID', $data[0] );
            $tmp_data['course_id'] = $data[1];
            $tmp_data['course_title'] = get_the_title( $data[1] );
            $tmp_data['date'] = date_i18n( 'd-m-Y H:i:s' );
            $tmp_data = apply_filters( "wp_zapier_{$hook}", $tmp_data, $data );
        }
        if( $hook == 'lifterlms_lesson_completed' ) {
            $tmp_data['user'] = get_user_by( 'ID', $data[0] );
            $tmp_data['lesson_id'] = $data[1];
            $tmp_data['lesson_title'] = get_the_title( $data[1] );
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
            'course_id' => "Course ID",
            'course_title' => "Course Title",
        );


        $arguments['llms_user_enrolled_in_course'] = $course;
        $arguments['llms_user_added_to_membership_level'] = $course;
        $arguments['llms_user_removed_from_course'] = $course;
        $arguments['llms_user_removed_from_membership_level'] = $course;

        $arguments['lifterlms_lesson_completed'] = array(
            'lesson_id' => "Lesson ID",
            'lesson_title' => "Lesson Title"
        );


        $userCopy = $arguments['profile_update'];
        if(!empty($userCopy)){
            foreach($userCopy as $key => $label){
                $arguments['llms_user_enrolled_in_course']["user.{$key}"] = "{$label}";
                $arguments['llms_user_added_to_membership_level']["user.{$key}"] = "{$label}";
                $arguments['llms_user_removed_from_course']["user.{$key}"] = "{$label}";
                $arguments['llms_user_removed_from_membership_level']["user.{$key}"] = "{$label}";
                $arguments['lifterlms_lesson_completed']["user.{$key}"] = "{$label}";
            }
        }

        $arguments['llms_user_enrolled_in_course']['date'] = "Date/Time";
        $arguments['llms_user_added_to_membership_level']['date'] = "Date/Time";
        $arguments['llms_user_removed_from_course']['date'] = "Date/Time";
        $arguments['llms_user_removed_from_membership_level']['date'] = "Date/Time";
        $arguments['lifterlms_lesson_completed']['date'] = "Date/Time";

        return $arguments;
    }

}

add_action( 'wp_zapier_integrations_loaded', function(){
    if ( class_exists( 'LifterLMS' ) ) {
        $lifter_lms = new LifterLMS();
    }
});