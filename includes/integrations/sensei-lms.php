<?php

namespace Yoohoo\WPZapier;

class SenseiLMS {

    public function __construct() {
        add_filter( 'wp_zapier_event_hook_filter', array( $this, 'add_hooks' ), 10, 1 );
        add_filter( 'wp_zapier_hydrate_extender', array( $this, 'hydrate_extender' ), 10, 2 );
    }

    function add_hooks( $hooks ) {
        
        $new_hooks = array(
            'sensei_user_course_start' => array(
                'name' => 'Sensei LMS - User Starts a Course'
            ),
            'sensei_user_course_end' => array(
                'name' => 'Sensei LMS - User Completed a Course'
            ),
            'sensei_user_lesson_start' => array(
                'name' => 'Sensei LMS - User Starts a New Lesson'
            ),
            'sensei_user_lesson_end' => array(
                'name' => 'Sensei LMS - User Completed a Lesson'
            ),
            'sensei_user_quiz_submitted' => array(
                'name' => 'Sensei LMS - User Completed a Quiz'
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
        }
    
        if ( $hook == 'sensei_user_course_end' ) {
            $tmp_data['user'] = get_user_by( 'ID', $data[0] );
            $tmp_data['course_id'] = $data[1];
            $tmp_data['course_title'] = get_the_title( $tmp_data['course_id'] );
        }

        if ( $hook == 'sensei_user_lesson_start' ) {
            $tmp_data['user'] = get_user_by( 'ID', $data[0] );
            $tmp_data['lesson_id'] = $data[1];
            $tmp_data['lesson_title'] = get_the_title( $data[1] );
            $tmp_data['course_id'] = Sensei()->lesson->get_course_id( $data[1] );
            $tmp_data['course_title'] = get_the_title( $tmp_data['course_id'] );
        }

        if ( $hook == 'sensei_user_lesson_end' ) {
            $tmp_data['user'] = get_user_by( 'ID', $data[0] );
            $tmp_data['lesson_id'] = $data[1];
            $tmp_data['lesson_title'] = get_the_title( $data[1] );
            $tmp_data['course_id'] = Sensei()->lesson->get_course_id( $data[1] );
            $tmp_data['course_title'] = get_the_title( $tmp_data['course_id'] );
            /// $tmp_data['lesson_meta'] = get_post_meta( $tmp_data['lesson_id'] );
        }

        if ( $hook == 'sensei_user_quiz_submitted' ) {
            $tmp_data['user'] = get_user_by( 'ID', $data[0] );
            $tmp_data['quiz_id'] = $data[1];
            $tmp_data['grade'] = $data[2];
            $tmp_data['quiz_pass_percentage'] = $data[3];
            $tmp_data['quiz_grade_type'] = $data[4];
        }
    
    
        if ( is_array( $tmp_data ) && ! empty( $tmp_data ) ) {
            $data = $tmp_data;
        }
    
        return $data;
    }

}

add_action( 'wp_zapier_integrations_loaded', function(){
    if ( class_exists( 'Sensei_Main' ) ) {
        $sensei_lms = new SenseiLMS();
    }
});