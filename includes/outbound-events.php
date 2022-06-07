<?php 

namespace Yoohoo\WPZapier;

class OutboundEvents{
	public function __construct(){		
		add_action('init', array($this, 'registerPostType'));

		add_action('add_meta_boxes', array($this, 'addMetaBoxes'));
		add_action('save_post', array($this, 'saveMetaBoxes'), 10 , 2);
		add_filter('wp_zapier_object_filter', array($this, 'baseObjectFilters'), 10, 3);

		add_filter('manage_outbound_event_posts_columns' , array($this,'addColumns'));
		add_action('manage_outbound_event_posts_custom_column' , array($this,'addColumnData'), 10, 2);

		/* Conditional Flow */
		add_filter('wp_zapier_flow_logic_argument_filter', array($this, 'registerFlowLogicArguments'), 1);

		$this->registerHooks();
	}

	/**
	 * Register the outbound event post type
	 *
	 * @return void
	*/
	public function registerPostType(){
		 $labels = array(
	        'name'                  => __( 'Outbound Events', 'wp-zapier' ),
	        'singular_name'         => __( 'Outbound Event', 'wp-zapier' ),
	        'menu_name'             => __( 'Outbound Events', 'wp-zapier' ),
	        'name_admin_bar'        => __( 'Outbound Event', 'wp-zapier' ),
	        'add_new'               => __( 'Add New', 'wp-zapier' ),
	        'add_new_item'          => __( 'Add New Event', 'wp-zapier' ),
	        'new_item'              => __( 'New Event', 'wp-zapier' ),
	        'edit_item'             => __( 'Edit Event', 'wp-zapier' ),
	        'view_item'             => __( 'View Event', 'wp-zapier' ),
	        'all_items'             => __( 'Outbound Events', 'wp-zapier' ),
	        'search_items'          => __( 'Search Events', 'wp-zapier' ),
	        'not_found'             => __( 'No events found.', 'wp-zapier' ),
	        'not_found_in_trash'    => __( 'No events found in Trash.', 'wp-zapier' ),
	    );
	 
	    $args = array(
	        'labels'             => $labels,
	        'public'             => false,
	        'publicly_queryable' => false,
	        'show_ui'            => true,
	        'show_in_menu'       => 'wp-zapier',
	        'query_var'          => true,
	        'capability_type'    => 'post',
	        'has_archive'        => false,
	        'hierarchical'       => false,
	        'menu_position'      => 99,
	        'supports'           => array('title', 'custom-fields'),
	    );
	 
	    register_post_type( 'outbound_event', $args );
	}

	/**
	 * Add the Trigger column to the post type output
	 * Change the order so that the date column is last
	 *
	 * @param array $columns
	 *
	 * @return array
	*/
	public function addColumns($columns){
		unset($columns['date']);
		$columns['hook'] = __('Trigger', 'wp-zapier');
		$columns['status'] = __('Status', 'wp-zapier');
		
		// Date seems unneeded. Let's just leave it hidden for now
		//$columns['date'] = __('Date');

		return $columns;
	}

	/**
	 * Add the data for the trigger column
	 *
	 * @param string $column
	 * @param int $post_id
	 *
	 * @return void
	*/
	public function addColumnData($column, $post_id) {
		if($column === 'hook'){
			$hooks = $this->getEventHooks();
			$hook = get_post_meta($post_id, '_zapier_action', true);
			if(!empty($hooks[$hook])){
				echo $hooks[$hook]['name'];
			} else {
				echo __('Unknown', 'wp-zapier') . " ({$hook})";
			}
		} else if($column === 'status')	{
			$status = get_post_meta($post_id, '_zapier_status', true);

			if(!empty($status) && $status === 'disabled'){
				echo "<strong>" . __('Disabled', 'wp-zapier') . "</strong>";
			} else {
				$nonce = wp_create_nonce("wpzap_switch_nonce");
				echo '<label class="wpzap-switch"><input data-nonce="' . esc_attr( $nonce ) . '" data-id="' . esc_attr( $post_id ) . '" type="checkbox" value="enabled" ' . checked('enabled', get_post_meta( $post_id, '_zapier_status', true ), false ) . ' /><div class="wpzap-slider wpzap-round"></div></label>';
			}
		}
	}

	/**
	 * Register the primary meta box
	 *
	 * @return void
	*/
	public function addMetaBoxes(){
        add_meta_box(
            'wp_zapier_out_box',
            'Event Settings', 
            array($this, 'renderMetaBox'), 
            'outbound_event',
            'normal'
        );

		add_meta_box(
			'wp_zapie_out_box_condition',
			'Conditional Dispatch',
			array($this, 'renderConditionMetaBox'),
			'outbound_event',
			'normal'
		);

        add_meta_box(
            'wp_zapier_out_box_stats',
            'Event Statistics', 
            array($this, 'renderStatMetaBox'), 
            'outbound_event',
            'side'
        );
	}

	/**
	 * Render the primary meta box
	 *
	 * @return void
	*/
	public function renderMetaBox($post){
		$hooks = $this->getEventHooks();
		
    	$zapier_action = get_post_meta($post->ID, '_zapier_action', true);
    	$zapier_url = get_post_meta($post->ID, '_zapier_url', true);
    	$zapier_status = get_post_meta($post->ID, '_zapier_status', true);

		wp_nonce_field('wp_zapier_event_nonce', 'wp_zapier_event_nonce');
		?>
		<table class='form-table'>
			<tbody>
				<tr>
					<td style="max-width: 150px">
						<label><?php _e('Trigger:', 'wp-zapier'); ?></label>
					</td>
					<td>
						<select name="zapier_action" style="width: 100%">
							<option value='-1'><?php _e('Select a trigger', 'wp-zapier'); ?></option>
							<?php
								if(!empty($hooks)){
									foreach ($hooks as $slug => $data) {
										$checked = ($slug === $zapier_action ? 'selected' : ''); 
										echo "<option value='{$slug}' {$checked}>{$data['name']}</option>";
									}
								}
							?>
						</select>
					</td>
				</tr>

				<tr>
					<td>
						<label><?php _e('Webhook URL:', 'wp-zapier'); ?></label>
					</td>
					<td>
						<input type="text" name="zapier_url" value="<?php echo esc_attr($zapier_url); ?>" style="width: 100%">
					</td>
				</tr>

				<tr>
					<td>
						<label><?php _e('Webhook Status:', 'wp-zapier'); ?></label>
					</td>
					<td>
						<select name="zapier_status" style="width: 100%">
							<option value='enabled' <?php echo ($zapier_status === 'enabled' ? 'selected' : ''); ?>>Enabled</option>
							<option value='disabled' <?php echo ($zapier_status !== 'enabled' ? 'selected' : ''); ?> >Disabled</option>
						</select>
					</td>
				</tr>
			</tbody>
		</table>

		<small><em><?php esc_html_e( "Note: You can add fixed fields using the 'Custom Fields' section.", 'wp-zapier' ); ?></em></small>
		<?php
	}

	/**
	 * Render the stat meta box
	 *
	 * @return void
	*/
	public function renderStatMetaBox($post){
    	$success_calls = get_post_meta($post->ID, '_zapier_success_calls', true);
    	$fail_calls = get_post_meta($post->ID, '_zapier_fail_calls', true);

    	$success_calls = !empty($success_calls) ? intval($success_calls) : 0;
    	$fail_calls = !empty($fail_calls) ? intval($fail_calls) : 0;

    	$succes_rate_string = "";
    	if($success_calls > 0){
    		$success_perc = $success_calls / ($success_calls + $fail_calls) * 100;
    		$success_perc = intval($success_perc);
    		$succes_rate_string = " ({$success_perc}%)";
    	}

    	$last_response_text = "No recent logs...";
    	$last_reponse_data = get_post_meta($post->ID, '_zapier_last_response', true);
    	if(!empty($last_reponse_data) && is_string($last_reponse_data)){
    		//Let's do some basic processing of the last response
    		if($this->isValidJSON($last_reponse_data)){
    			$last_reponse_data = json_decode($last_reponse_data);
    			$last_response_text = "";
    			foreach ($last_reponse_data as $key => $value) {
    				if(!is_string($value)){
    					$value = json_encode($value);
    				}

    				$last_response_text = "{$key} : {$value} <br>";
    			}
    		} else {
    			//Raw data is fine
    			$last_response_text = $last_reponse_data;
    		}
    	}

		?>
		<table style="width: 100%">
			<tbody>
				<tr>
					<td style="width: 50%">
						<label><strong><?php _e('Success:', 'wp-zapier'); ?></strong></label>
					</td>
					<td>
						<?php echo $success_calls . $succes_rate_string; ?>
					</td>
				</tr>

				<tr>
					<td>
						<label><strong><?php _e('Failed:', 'wp-zapier'); ?></strong></label>
					</td>
					<td>
						<?php echo $fail_calls; ?>
					</td>
				</tr>

				<tr>
					<td>
						&nbsp;
					</td>
					<td>
						&nbsp;
					</td>
				</tr>

				<tr>
					<td>
						<label><strong><?php _e('Last Response:', 'wp-zapier'); ?></strong></label>
					</td>
					<td>
						
					</td>
				</tr>
			</tbody>
		</table>

		<div style="background: #eee;margin: 5px;padding: 7px;border: 1px solid #dcdcdc; border-radius: 3px;">
			<?php echo $last_response_text; ?>
		</div>
		<?php
	}

	/**
	 * Render the condition meta box
	 *
	 * @return void
	*/
	public function renderConditionMetaBox($post){
		wp_enqueue_style( 'wpzp-admin-flow', WPZAP_URL . 'assets/css/flow.css', false, WPZAP_VERSION);
		wp_enqueue_script( 'wpzp-admin-flow', WPZAP_URL . 'assets/js/flow-control.js', array( 'jquery' ), WPZAP_VERSION );
		
		$fieldBuilder = new FlowLogic\FlowFieldBuilder();
		
    	
		$storedConditions = get_post_meta($post->ID, '_zapier_flow_data', true);
		$hasPlacedIf = false;
		if(!empty($storedConditions)){
			foreach($storedConditions as $flowData){
				$argumentA = array(
					'value' => $flowData['argument_a'],
					'static_value' => (!empty($flowData['static_a']) ? $flowData['static_a'] : ''),
				);

				$argumentB = array(
					'value' => $flowData['argument_b'],
					'static_value' => (!empty($flowData['static_b']) ? $flowData['static_b'] : ''),
				);

				$condition = array(
					'value' => $flowData['condition'],
				);
				?>
					<div class="wp-zapier-conditional-flow">
						<button class="wp-zapier-conditional-flow-drop" title="Remove Conditon"></button>
						<code><?php echo (!$hasPlacedIf ? 'IF' : 'AND'); ?></code>
						<?php 
							echo $fieldBuilder->argumentSelect($argumentA); 
							echo $fieldBuilder->conditionSelect($condition); 
							echo $fieldBuilder->argumentSelect($argumentB, "b"); 
						?>
					</div>
				<?php

				$hasPlacedIf = true;
			}
		}
		
		?>
			<div class="wp-zapier-conditional-flow">
				<button class="wp-zapier-conditional-flow-drop" disabled></button>
				<code><?php echo (!$hasPlacedIf ? 'IF' : 'AND'); ?></code>
				<?php 
					echo $fieldBuilder->argumentSelect(); 
					echo $fieldBuilder->conditionSelect(); 
					echo $fieldBuilder->argumentSelect(false, "b"); 
				?>
			</div>
			<br>
			<hr>
			<strong>Hints</strong>
			<ul>
				<li>- <code>Save this event</code> to add additional conditions</li>
				<li>- For static values, use the <code>Enter Value</code> option, and enter a custom value</li>
				<li>- To access custom request data, like specific form fields, use <code>Enter Key</code> option with a custom value (ex: <code>form_data.field_one</code>)</li>
				<li>- Use the <code>User Meta</code> option and enter the meta value to use it as a sample. Request must contain <code>user_id</code>, <code>user</code> or <code>id</code> index to function</li>
				<li>- Use the <code>Post Meta</code> option and enter the meta value to use it as a sample. Request must contain <code>post_id</code>, <code>post</code> or <code>id</code> index to function</li>
			</ul> 
		<?php
	}

	/**
	 * Save date from the primary meta box
	 *
	 * @param int $post_id
	 * @param WP_Post $post
	 *
	 * @return void
	*/
	public function saveMetaBoxes($post_id, $post){
	    if(defined( 'DOING_AUTOSAVE') && DOING_AUTOSAVE) {
	        return;
	    }

	    if(!current_user_can('edit_page', $post_id)) {
            return;
        }

    	if(!empty($_POST['post_type']) && $_POST['post_type'] == 'outbound_event') {
    		if(!isset($_POST['wp_zapier_event_nonce'])){
		        return;
		    }
		    
		    if (!wp_verify_nonce($_POST['wp_zapier_event_nonce'], 'wp_zapier_event_nonce')){
		        return;
		    }

	        if (!empty($_POST['zapier_action']) && !empty($_POST['zapier_url'])) {
	    		$zapier_action = sanitize_text_field( $_POST['zapier_action'] );
	    		$zapier_url = sanitize_text_field( $_POST['zapier_url'] );
	    		$zapier_status = sanitize_text_field( $_POST['zapier_status'] );
	    		
	    		update_post_meta($post_id, '_zapier_action', $zapier_action);
	    		update_post_meta($post_id, '_zapier_url', $zapier_url);
	    		update_post_meta($post_id, '_zapier_status', $zapier_status);
		    }

			if(!empty($_POST['flow_argument_a'])){
				// We have at least one input argument, so it's safe to assume the rest of the data is available
				$flowData = array();
				foreach($_POST['flow_argument_a'] as $flowKey => $argumentA){
					$argumentB = !empty($_POST['flow_argument_b'][$flowKey]) ? $_POST['flow_argument_b'][$flowKey] : false;
					$condition = !empty($_POST['flow_condition'][$flowKey]) ? $_POST['flow_condition'][$flowKey] : false;

					if(!empty($argumentA) && !empty($argumentB) && !empty($condition)){
						$argumentA = sanitize_text_field($argumentA);
						$argumentB = sanitize_text_field($argumentB);
						$condition = sanitize_text_field($condition);

						$flow = array(
							'argument_a' => $argumentA,
							'argument_b' => $argumentB,
							'condition' => $condition,
						);

						if(($argumentA === 'static_value' || $argumentA === 'static_key' || $argumentA === 'user_meta' || $argumentA === 'post_meta') && !empty($_POST['flow_argument_static_a'][$flowKey])){
							$flow['static_a'] = sanitize_text_field($_POST['flow_argument_static_a'][$flowKey]);
						}

						if(($argumentB === 'static_value' || $argumentB === 'static_key' || $argumentB === 'user_meta' || $argumentB === 'post_meta') && !empty($_POST['flow_argument_static_b'][$flowKey])){
							$flow['static_b'] = sanitize_text_field($_POST['flow_argument_static_b'][$flowKey]);
						}

						$flowData[] = $flow;
					}
				}

				update_post_meta($post_id, '_zapier_flow_data', $flowData);
			} else {
				/* No conditional, clear it */
				update_post_meta($post_id, '_zapier_flow_data', array());
			}

    	} else {
	        if(!empty($_POST['post_type'])){
	        	if($_POST['post_type'] === 'page'){
	        		do_action('wp_zapier_save_page', $post_id, $post);
	        	} else if ($_POST['post_type'] === 'post'){
	        		do_action('wp_zapier_save_post', $post_id, $post);
	        	}
	        }
	    }

	    
	}

	/**
	 * Get all registered event hooks
	 *
	 * @return array
	*/
	public function getEventHooks(){
		$hooks = array(
			'wp_login' => array(
				'name' => 'User Login'
			),
			'user_register' => array(
				'name' => 'User Register'
			),
			'profile_update' => array(
				'name' => 'Update Profile'
			),
			'wp_zapier_save_post' => array(
				'name' => 'Save Post'
			),
			'wp_zapier_save_page' => array(
				'name' => 'Save Page'
			)
		);

		//Hook for extension
		$hooks = apply_filters('wp_zapier_event_hook_filter', $hooks);

		return $hooks;
	}

	/**
	 * Register all hooks
	 *
	 * @return void
	*/
	public function registerHooks(){
		$hooks = $this->getEventHooks();
		foreach ($hooks as $hook => $data) {
			$this->registerHook($hook);
		}
	}

	/**
	 * Base object filters to cycle through common object types and format their data accordingly
	 * This is the best place to add new processors and filters
	 *
	 * @param array $formatted Current formatted array
 	 * @param object $data Object to be tested and added to formatted array
	 * @param string $hook The current hook/trigger running. Purely used for legacy filter support
	 * 
	 * @return array
	*/
	public function baseObjectFilters($formatted, $data, $hook){
		$filterFlag = strtolower(get_class($data));
		if(is_a($data, 'WP_User')){
			$user = get_user_by('id', $data->ID);
			$meta = get_user_meta($user->ID);

			$formatted['user_id'] = $user->ID;
			$formatted['user_email'] = $user->user_email;
			$formatted['user_nicename'] = $user->user_nicename;
			$formatted['user_registered'] = $user->user_registered;
			$formatted['user_url'] = $user->user_url;
			$formatted['display_name'] = $user->display_name;
			$formatted['roles'] = $user->roles;
			$formatted['first_name'] = $meta['first_name'][0];
			$formatted['last_name'] = $meta['last_name'][0];
			$formatted['nickname'] = $meta['nickname'][0];
			$formatted['user_description'] = $meta['description'][0];

			//Legacy filters
			if($hook === 'profile_update'){
				$formatted = apply_filters('wpzp_send_data_profile_update_array', $formatted, $user, $user->ID);
			} else if ($hook === 'wp_login'){
				$formatted = apply_filters( 'wpzp_send_data_login_array', $formatted, $user, $user->ID); 
			}

		} else if(is_a($data, 'WP_Post')){
			$user = get_user_by('id', $data->post_author);

			$formatted['post_id'] = $data->ID;
			$formatted['post_title'] = $data->post_title;
			$formatted['post_content'] = $data->post_content;
			$formatted['post_modified'] = $data->post_modified;
			$formatted['author_id'] = $data->post_author;
			$formatted['author_name'] = $user->display_name;
			$formatted['permalink'] = get_permalink($data->ID);
			$formatted['post_type'] = $data->post_type;
		}

		//Used for core 'is_a' extension
		$formatted = apply_filters("wp_zapier_base_object_extender", $formatted, $data, $hook);

		if(!empty($filterFlag)){
			//Final filter - Should be used if you are trying to filter a specific filter flag
			$formatted = apply_filters("wp_zapier_object_filter.{$filterFlag}", $formatted);
		}

		return $formatted;
	} 

	/**
	 * Filter the raw data passed into the action as parameters. Each value is tested to be a specific type.
	 * Stack exhange:
	 * - Objects will be passed to the object filter to be hydrated
	 * - Arrays will be added as long as keys are string values
	 * - String keys will be added by default, usually these will be numeric, so this acts as a failsafe
	 * - If the key is numeric it is added to the fallback array
	 *
	 * If we have a formatted array, this becomes the new output data to be sent in the trigger
	 * If we do not, we will make use of the fallback array instead as this will be the 'next best' choice
	 *
	 * @param array $data Current parameters from the hook, mixed values accepted here
	 * @param string $hook Current hook, used to pass on to the object filter
	 *
	 * @return array
	*/
	private function dataFilter($data, $hook){
		if(!empty($data)){
			$formatted = array();
			$fallback = array();
			foreach ($data as $i => $val) {
				if(is_object($val)){
					$formatted = apply_filters('wp_zapier_object_filter', $formatted, $val, $hook);
				} else if (is_array($val)){
					foreach ($val as $vKey => $vData) {
						if(is_string($vKey)){
							$formatted[$vKey] = $vData;
						}
					}
				} else if(is_string($i)){
					$formatted[$i] = $val;
				} else {
					if(is_numeric($i)){
						// This is used in case we find no sub_arrays or objects which can be parsed
						$fallback[] = $val;
					} 
				}
			}

			if(!empty($formatted)){
				return $formatted;
			} else {
				return $fallback;
			}
		}
		return $data;
	}

	/**
	 * Simple hydrate method which runs before the main data filter
	 * This was added to allow for 'preloading' data for hooks which may not have enough data to send to a webhook
	 * 
	 * Known preload hooks: 
	 * - user_register
	 *
	 * It is important not to filter too much here and leave it in the hands of the object filter
	 *
	 * @param array $data
	 * @param array $hook
	 *
	 * @return array
	*/
	private function hydrate($data, $hook){
		switch ($hook) {
			case 'user_register':
				if(!empty($data[0])){
					//Let's assume this is the user ID
					$user = get_user_by('id', $data[0]);
					$data[] = $user;
					if ( is_array( $_POST ) ) {
						$register_data = array();
						foreach( $_POST as $key => $posted_value ) {
							if ( strpos( $key, 'password' ) != true ) {
								$register_data[$key] = sanitize_text_field( $posted_value );
							}
						}

						unset( $register_data['_wpnonce'] );
						unset( $register_data['_wp_http_referer'] );
						$data['registration_fields'] = $register_data;
					}
					do_action( 'wp_zapier_user_register_data', $data );
				}
				break;
		}

		$data = apply_filters('wp_zapier_hydrate_extender', $data, $hook);

		return $data;
	}

	/**
	 * Register a hook, this generates a dynamic action which allows us to get all params, filter them and trigger the event chain
	 *
	 * @param string $hook
	 *
	 * @return void 
	*/
	private function registerHook($hook){
		add_action($hook, function(){
			$hook = current_filter();
			$data = func_get_args();
			$data = $this->hydrate($data, $hook);
			$data = $this->dataFilter($data, $hook);
			$this->triggerChain($hook, $data);
		}, 99, 99);
	}

	/**
	 * Trigger all events linked to a specific hook 
	 * 
	 * @param string $hook
	 * @param array $data
	 *
	 * @return void
	*/
	private function triggerChain($hook, $data){
		$events = get_posts(
			array(
				'numberposts' => -1,
				'post_type' => 'outbound_event',
				'post_status' => 'publish',
				'meta_key' => '_zapier_action',
				'meta_value' => $hook
			)
		);

		if(!empty($events)){
			foreach ($events as $event) {
    			$url = get_post_meta($event->ID, '_zapier_url', true);
    			$status = get_post_meta($event->ID, '_zapier_status', true);
    			if(!empty($url)){
    				if(!empty($status) && $status === 'disabled'){
    					//This one is disabled, skip
    					continue;
    				}

					$conditions = get_post_meta($event->ID, '_zapier_flow_data', true);
					$canRun = true;
					if(!empty($conditions)){
						/* This event has flow conditions, let's initialize and evaluate the conditions */
						$flow = new FlowLogic\Flow($conditions, $data);
						if(!$flow->execute()){
							$canRun = false;
						}
					}

					if($canRun){
    					$this->trigger($url, $data, $event);
					}
    			}
			}
		}
	}

	/**
	 * Trigger individual event
	 * Also adds any fixed data stored as custom fields
	 *
	 * @param string $url 
	 * @param array $data
	 * @param WP_Post $event
	 *
	 * @return void
	*/
	private function trigger($url, $data, $event){
		global $current_user;
		$customFields = get_post_meta($event->ID);
		if(!empty($customFields)){
			foreach ($customFields as $key => $value) {
				if(mb_substr($key, 0, 1) !== '_'){
					$data[$key] = is_array($value) ? $value[0] : $value;
				}

				// Custom user meta. Use {{um_metakey}} to get the value.
				if ( strpos( $key, '{{um_' ) !== false ) {
					$user_id_value = is_array( $value ) ? intval( $value[0] ) : intval( $value );
					$user_id = ! empty( $user_id_value ) ? intval( $user_id_value) : $current_user->ID;
					unset( $data[$key] );
					$key = str_replace( array('{{um_', '}}'), array('', '' ), $key ); //formatted key
					$data[$key] = get_user_meta( $user_id, $key, true );
				}	
			}
		}

		$success = $this->send($url, $data);
		$this->logStatus($success, $event->ID);
	}

	/**
	 * Send the data to the URL
	 *
	 * @param string $url
	 * @param array $data
	 *
	 * @return bool
	*/
	private function send($url, $data){
		$url = esc_url($url);
		$request = wp_remote_post($url, array('body' => $data));
		if (!is_wp_error($request)){
	        if (!empty($request['body']) && strlen($request['body']) > 0){		        	
			    $this->lastResponse = wp_remote_retrieve_body($request);
			    return true;
	        }
		} else {
			$this->lastResponse = $request->get_error_message();
		}
		return false;
	}

	/**
	 * Log the status of a request for basic metric reports
	 *
	 * @param bool $success
	 * @param int $event_id
	 *
	 * @return void
	*/
	private function logStatus($success, $event_id){
		if($success){
			$success_calls = get_post_meta($event_id, '_zapier_success_calls', true);
			$success_calls = !empty($success_calls) ? intval($success_calls) : 0;
			update_post_meta($event_id, '_zapier_success_calls', $success_calls + 1);
		} else {
    		$fail_calls = get_post_meta($event_id, '_zapier_fail_calls', true);
    		$fail_calls = !empty($fail_calls) ? intval($fail_calls) : 0;
			update_post_meta($event_id, '_zapier_fail_calls', $fail_calls + 1);
		}

		if(!empty($this->lastResponse)){
			update_post_meta($event_id, '_zapier_last_response', $this->lastResponse);
		}
	}

	/**
	 * Check for valid JSON
	 *
	 * @param string $data
	 *
	 * @return bool
	*/
	private function isValidJSON($data){
		if(!empty($data)){
			$tmp = @json_decode($data);
			return (json_last_error() === JSON_ERROR_NONE);
		}
		return false;
	}

	/**
	 * Hook into the condtional flow logic filter and register the relevant fields for each trigger type
	 * 
	 * @param array $arguments
	 * 
	 * @return array
	 */
	public function registerFlowLogicArguments($arguments){
		$user = array(
			'user_id' => "ID",
			'user_email' => "Email",
			'user_registered' => "Registered Date/Time",
			'user_url' => "URL",
			'display_name' => "Display Name",
			'first_name' => "First Name",
			'last_name' => "Last Name",
			'nickname' => "Nickname",
			'user_description' => "Description"
		);

		$arguments['wp_login'] = $user;
		$arguments['user_register'] = $user;
		$arguments['profile_update'] = $user;

		$post = array(
			'post_id' => "ID",
			'post_title' => "Title",
			'post_content' => "Content",
			'post_modified' => "Modified Date/Time",
			'author_id' => "Author ID",
			'author_name' => "Author Name",
			'permalink' => "Permalink",
			'post_type' => "Post Type"
		);

		$arguments['wp_zapier_save_post'] = $post;
		$arguments['wp_zapier_save_page'] = $post;
		
		return $arguments;
	}
}