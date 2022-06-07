<?php

namespace Yoohoo\WPZapier;

class EventsManager{

    public function __construct() {
        add_filter( 'wp_zapier_event_hook_filter', array( $this, 'add_hooks' ), 10, 1 );
        add_filter( 'wp_zapier_hydrate_extender', array( $this, 'hydrate_extender' ), 10, 2 );

        add_filter('wp_zapier_flow_logic_argument_filter', array($this, 'register_flow_logic_arguments'));

    }

    function add_hooks( $hooks ) {

        $new_hooks = array(
            'save_post_event' => array(
                'name' => __( 'Events Manager - Event Created/Updated' )
            )
        );

        $hooks = array_merge( $hooks, $new_hooks );

        return $hooks;

    }

    function hydrate_extender( $data, $hooks ) {
        $tmp_data = array();

        if ( $hooks == 'save_post_event' ) {
            $post = get_post();

            $tmp_data['event'] = ! empty( $post ) ? $post : '';

            $event_meta = get_post_meta( $post->ID );

            $event_meta_array = array();
            foreach( $event_meta as $key => $meta ) {
            
                if ( strpos( $key, '_event' ) !== false ) {
                    $event_meta_array[$key] = $meta[0];
                }
            }
            
            if ( is_array( $event_meta_array ) && ! empty( $event_meta_array ) ) {
                $tmp_data['event_details'] = $event_meta_array;
            }

            $tmp_data = apply_filters( "wp_zapier_{$hook}", $tmp_data, $data );
        }

        if(!empty($tmp_data)){
            $data = $tmp_data;
        }

        return $data;
    }

    public function register_flow_logic_arguments($arguments){
        $arguments['save_post_event'] = array();
    
        $postCopy = $arguments['wp_zapier_save_post'];
        if(!empty($postCopy)){
            foreach($postCopy as $key => $label){
                $arguments['save_post_event']["event.{$key}"] = "{$label}";
            }
        }
        
        return $arguments;
    }

}


add_action( 'wp_zapier_integrations_loaded', function(){
    if ( defined( 'EM_VERSION' ) ) {
        $ssp = new EventsManager();
    }
});