<?php
/*
Plugin Name: Morphing Portals WP Integration
Description: This plugin adds a morphing portals integrations to wordpress
Version: 1.2
Author: Morphing Portals
Text Domain: morphing-portals-integration
Author URI: https://www.morphingportals.com
License: GPLv3
*/
define('MORPHING_PORTALS_API', 'api.morphingportals.com');
define('MORPHING_PORTALS_PORTAL', 'portal.morphingportals.com');

if(!class_exists('morphing_portals_integration'))
	{

	class morphing_portals_integration {

	    public function __construct(){
		    add_action('admin_menu', array($this, 'hd_mpi_custom_menu'));

		    // Enqueue here
			add_action( 'admin_enqueue_scripts', array($this, 'hd_mpi_adding_scripts' ));
			add_action( 'wp_enqueue_scripts', array($this, 'hd_mpi_adding_scripts_frontend' ), 1);

			// Sync Ajax
			add_action( 'wp_ajax_hd_mpi_sync_morphing_portals', array($this, 'hd_mpi_sync_morphing_portals_ajax' ));

			// Add placeholder shortcode
			add_shortcode( 'hd_mp_placeholder', array($this, 'hd_mpi_placeholder_shortcode' ));

			// Add placeholder widget
			add_action( 'widgets_init', array($this, 'hd_mpi_load_placeholder_widget') );

			// Add tinymce button
			add_action('admin_head', array($this, 'hd_mpi_tc_placeholder_button'));

			// Add rewrite rule
	        add_action( 'init', array( $this, 'hd_mpi_morphingportals_init_internal' ));
	        add_filter( 'query_vars', array( $this, 'hd_mpi_morphingportals_query_vars'));
	        add_action( 'parse_request', array( $this,'hd_mpi_morphingportals_parse_request' ));

	        add_action('admin_head', array( $this,'hd_mpi_morphingportals_add_url_js' ));
		}

		public function hd_mpi_morphingportals_add_url_js(){
			$url = MORPHING_PORTALS_WP_INTEGRATION_PLUGIN_URL;
			echo "<script>var hd_mpi_url='$url'; </script>";
		}

		// Rewrites rule to change endpoint url
	    public function hd_mpi_morphingportals_init_internal(){
	        add_rewrite_rule( "morphingportals_api", "index.php?morphingportals_api=1", "top" );

	        $version = MORPHING_PORTALS_WP_API_VERSION;
	        $flushed_version = get_option('hd_mpi_rw_flushed_version');
	        if($flushed_version !== $version){
	        	flush_rewrite_rules();
	        	update_option('hd_mpi_rw_flushed_version', $version);
	        }
	    }

	    // Add endpoint
	    public function hd_mpi_morphingportals_query_vars( $query_vars ){
	        $query_vars[] = 'morphingportals_api';
	        return $query_vars;
	    }

	    // Adds file to endpoint
	    public function hd_mpi_morphingportals_parse_request( $wp ){

	        if ( array_key_exists( 'morphingportals_api', $wp->query_vars ) ) {
	            require_once MORPHING_PORTALS_WP_INTEGRATION_PLUGIN_PATH . 'includes/mp_endpoint.php';
	            exit();
	        }

		    return;
		}

		public function hd_mpi_tc_placeholder_button(){
			global $typenow;

		    // check user permissions
		    if (!current_user_can('edit_posts') && !current_user_can('edit_pages')) {
		      return;
		    }
		    // verify the post type
		    if( ! in_array( $typenow, array( 'post', 'page', 'product') ) )
		        return;

		    // check if WYSIWYG is enabled
		    if ( get_user_option('rich_editing') == 'true') {
		        add_filter("mce_external_plugins", array($this, "hd_mpi_add_tinymce_plugin"));
		        add_filter('mce_buttons', array($this, 'hd_mpi_register_my_tc_button'));
		    }
		}

		public function hd_mpi_add_tinymce_plugin($plugin_array) {
		  $plugin_array['mpi_ph_button'] = plugins_url('assets/js/mpi_ph_button.js?token=1', MORPHING_PORTALS_WP_INTEGRATION_PLUGIN_FILE);
		  return $plugin_array;
		}

		public function hd_mpi_register_my_tc_button($buttons) {
		  array_push($buttons, "mpi_ph_button");
		  return $buttons;
		}

		public function hd_mpi_load_placeholder_widget(){
			register_widget( 'hd_mpi_placeholder_widget' );
		}

		public function hd_mpi_placeholder_shortcode( $atts ){
			$atts = shortcode_atts( array(
				'id' => 0
			), $atts );
			if($atts['id']){
				$placeholder_id = $atts['id'];
				global $wpdb;

				$placeholder = $wpdb->get_row( "SELECT * FROM " . MORPHING_PORTALS_WP_INTEGRATION_DATABASE_ORIGINS . " WHERE id = $placeholder_id" );
				if(isset($placeholder->mp_id) && $placeholder->mp_id){
					$mp_id = $placeholder->mp_id;
					$mp_html = "<div id='imp_banner_div_$mp_id' style='visibility: hidden;'></div>";
					return $mp_html;
				}
			}
			return 'Failed to get placeholder.';
		}

		public function hd_mpi_remove_deleted_elements($element_array, $database){
			global $wpdb;
			if(is_array($element_array) && !empty($element_array)){
				$query = "DELETE FROM "  . $database . " WHERE mp_id NOT IN ('" . implode('\',\'', $element_array) . "')";
				$wpdb->query($query);
			}
		}

		public function hd_mpi_sync_morphing_portals_ajax(){

			if ( isset($_REQUEST) ) {
			   	  $api_key = get_option('hd_mpi_api_key');
			   	  $api_token = get_option('hd_mpi_api_token');
			   	  $response_text = 'There was an issue while syncing';
			   	  $result_bool = 'success';
			   	  $added = 0;
			   	  $finished = false;

				  $offset = $_REQUEST['offset'];

				  switch($offset){
					  case 0:
					  	$endpoint = MORPHING_PORTALS_WP_API_GOALS_ENDPOINT;
					    $url = MORPHING_PORTALS_WP_API_BASE_URL . $endpoint;

					    $comunicator = new MP_API_COMM($url, $api_token);
					    $result = $comunicator->get_all();

				        if(isset($result['goals']) && is_array($result['goals'])){
					        $goals = $result['goals'];
					        $goal_array = array();
					        foreach($goals as $goal){
						        $goal_obj = new MP_Goal_API($goal);
						        $goal_exists = $goal_obj->checkIfExists();
						        if(!$goal_exists){
							        if($goal_obj->addToDB()){
								    	$added++;
							        }
							        else{

							        }
						        }
						        $goal_array[] = $goal['goalID'];
					        }
					        if($added>0){
					        	$response_text = "$added new goal(s) synced";
					        }
					        else{
						        $response_text = "No new goals to sync";
					        }

					        $this->hd_mpi_remove_deleted_elements($goal_array, MORPHING_PORTALS_WP_INTEGRATION_DATABASE_GOALS);
				        }
				        else{
					        $result_bool   = 'failure';
					        $response_text = 'There was a problem while syncing goals';
					        $finished = true;
				        }
					  	break;
					  case 1:
							$endpoint = MORPHING_PORTALS_WP_API_ORIGINS_ENDPOINT;
						    $url = MORPHING_PORTALS_WP_API_BASE_URL . $endpoint;

						    $comunicator = new MP_API_COMM($url, $api_token);
						    $result = $comunicator->get_all();

					        if(isset($result['origins']) && is_array($result['origins'])){
						        $origins = $result['origins'];
						        $origins_array = array();
						        foreach($origins as $origin){
							        $origin_obj = new MP_Origin_API($origin);
							        $origin_exists = $origin_obj->checkIfExists();
							        if(!$origin_exists){
								        if($origin_obj->addToDB()){
									    	$added++;
								        }
								        else{

								        }
							        }
							        $origins_array[] = $origin['originID'];
						        }
						        if($added>0){
						        	$response_text = "$added new placeholder(s) synced";
						        }
						        else{
							        $response_text = "No new placeholders to sync";
						        }

						         $this->hd_mpi_remove_deleted_elements($origins_array, MORPHING_PORTALS_WP_INTEGRATION_DATABASE_ORIGINS);
					        }
					        else{
						        $result_bool   = 'failure';
						        $response_text = 'There was a problem while syncing placeholders';
						        $finished = true;
					        }
					  	break;
					  case 3:
						    $endpoint = MORPHING_PORTALS_WP_API_BANNERS_ENDPOINT;
						    $url = MORPHING_PORTALS_WP_API_BASE_URL . $endpoint;

						    $comunicator = new MP_API_COMM($url, $api_token);
						    $result = $comunicator->get_all();

					        if(isset($result['banners']) && is_array($result['banners'])){
						        $modules = $result['banners'];
						        $modules_array = array();
						        foreach($modules as $module){
							        $module_obj = new MP_Module_API($module);
							        $module_exists = $module_obj->checkIfExists();
							        if(!$module_exists){
								        if($module_obj->addToDB()){
									    	$added++;
								        }
								        else{

								        }
							        }
							        $modules_array[] = $module['bannerID'];
						        }
						        if($added>0){
						        	$response_text = "$added new content(s) synced";
						        }
						        else{
							        $response_text = "No new content to sync";
						        }
						        $this->hd_mpi_remove_deleted_elements($modules_array, MORPHING_PORTALS_WP_INTEGRATION_DATABASE_MODULES);
					        }
					        else{
						        $result_bool   = 'failure';
						        $response_text = 'There was a problem while syncing content';
						        $finished = true;
					        }
					  	break;
					  case 2:
						$response_text = '';
						break;
					/*  		$endpoint = MORPHING_PORTALS_WP_API_SEGMENTATIONS_ENDPOINT;
						    $url = MORPHING_PORTALS_WP_API_BASE_URL . $endpoint;

						    $comunicator = new MP_API_COMM($url, $api_token);
						    $result = $comunicator->get_all();

					        if(isset($result['segmentations']) && is_array($result['segmentations'])){
						        $segmentations = $result['segmentations'];
						        $segmentations_array = array();
						        foreach($segmentations as $segmentation){
							        $segmentation_obj = new MP_Segmentation_API($segmentation);
							        $segmentation_exists = $segmentation_obj->checkIfExists();
							        if(!$segmentation_exists){
								        if($segmentation_obj->addToDB()){
									    	$added++;
								        }
								        else{

								        }
							        }
							        $segmentations_array[] = $segmentation['segmentationTagID'];
						        }
						        if($added>0){
						        	$response_text = "$added new segmentation(s) synced";
						        }
						        else{
							        $response_text = "No new segmentations to sync";
						        }
						        $this->hd_mpi_remove_deleted_elements($segmentations_array, MORPHING_PORTALS_WP_INTEGRATION_DATABASE_SEGMENTATIONS);
					        }
					        else{
						        $result_bool   = 'failure';
						        $response_text = 'There was a problem while syncing segmentations';
						        $finished = true;
					        }
					  	break; */
					  	case 4:
						    $endpoint = MORPHING_PORTALS_WP_API_THRESHOLD_ENDPOINT;
						    $url = MORPHING_PORTALS_WP_API_BASE_URL . $endpoint;

						    $comunicator = new MP_API_COMM($url, $api_token);
						    $result = $comunicator->get_all();

					        if(isset($result['value'])){
						        $value = sanitize_text_field($result['value']);
						        $activation_rate = get_option('hd_mpi_activation_rate');

						        if($value != $activation_rate){
							        update_option('hd_mpi_activation_rate', $value);
							        $response_text = 'Activation rate updated';
						        }
						        else{
							        $response_text = 'No change on activation rate';
						        }
					        }
					        else{
						        $result_bool   = 'failure';
						        $response_text = 'There was a problem while syncing activation rate';
						        $finished = true;
					        }
					  	break;
					  	case 5:
						    $endpoint = MORPHING_PORTALS_WP_API_NEWS_ENDPOINT;
						    $url = MORPHING_PORTALS_WP_API_BASE_URL . $endpoint;

						    $comunicator = new MP_API_COMM($url, $api_token);
						    $result = $comunicator->get_all();

					        if(isset($result['news_keywords'])){
						        $value = implode(',' , array_map( 'sanitize_text_field', $result['news_keywords'] ));
						        $new_phrases = get_option('hd_mpi_new_phrases');

						        if($value != $new_phrases){
							        update_option('hd_mpi_new_phrases', $value);
							        $response_text = 'New phrases updated';
						        }
						        else{
							        $response_text = 'No change on new phrases';
						        }
						        $finished = true;
					        }
					        else{
						        $result_bool   = 'failure';
						        $response_text = 'There was a problem while syncing new phrases';
						        $finished = true;
					        }
					  	break;
					  default:
					  	break;
				  }

				  echo json_encode(array('result' => $result_bool, 'finished' => $finished, 'text' => $response_text));
			}
			die();
		}

		public function hd_mpi_adding_scripts(){
			global $typenow;

			//Add selectize and custom js file
			wp_enqueue_script('hellodev-selectize-js', plugins_url('assets/js/selectize.js', MORPHING_PORTALS_WP_INTEGRATION_PLUGIN_FILE), array('jquery'), '1.0', false);
			wp_enqueue_script('hellodev-admin-js', plugins_url('assets/js/admin.js', MORPHING_PORTALS_WP_INTEGRATION_PLUGIN_FILE), array('jquery'), '1.0', false);

			if(isset($_GET['page']) && $_GET['page'] == 'morphing-portals-integration'){

				// Add dashboard js files
				wp_enqueue_script('hellodev-admin-dash-1-js', 'https://ajax.googleapis.com/ajax/libs/angularjs/1.4.9/angular.min.js');
				wp_enqueue_script('hellodev-admin-dash-111-js', 'https://ajax.googleapis.com/ajax/libs/angularjs/1.4.9/angular-animate.min.js');
				wp_enqueue_script('hellodev-admin-dash-2-js', 'https://ajax.googleapis.com/ajax/libs/angularjs/1.4.9/angular-messages.js');
				wp_enqueue_script('hellodev-admin-dash-3-js', 'https://ajax.googleapis.com/ajax/libs/angularjs/1.4.9/angular-cookies.min.js');
				wp_enqueue_script('hellodev-admin-dash-4-js', 'https://ajax.googleapis.com/ajax/libs/angularjs/1.4.9/angular-route.min.js');

				wp_enqueue_script('hellodev-admin-dash-5-js', 'https://cdn.rawgit.com/zenorocha/clipboard.js/master/dist/clipboard.min.js');

				wp_enqueue_script('hellodev-admin-dash-6-js', plugins_url('assets/js/dashboardApp.js', MORPHING_PORTALS_WP_INTEGRATION_PLUGIN_FILE));
				wp_enqueue_script('hellodev-admin-dash-7-js', plugins_url('assets/js/dashboardCtrl.js', MORPHING_PORTALS_WP_INTEGRATION_PLUGIN_FILE));
				wp_enqueue_script('hellodev-admin-dash-8-js', 'https://'.MORPHING_PORTALS_PORTAL.'/current/imp-portal/scripts/directives/go-click-directive.js');
				wp_enqueue_script('hellodev-admin-dash-9-js', 'https://'.MORPHING_PORTALS_PORTAL.'/current/imp-portal/scripts/directives/update-on-enter-directive.js');
				wp_enqueue_script('hellodev-admin-dash-10-js', 'https://'.MORPHING_PORTALS_PORTAL.'/current/imp-portal/scripts/directives/drop-image-zone-directive.js');
				wp_enqueue_script('hellodev-admin-dash-11-js', 'https://'.MORPHING_PORTALS_PORTAL.'/current/imp-portal/scripts/directives/elastic-directive.js');
				wp_enqueue_script('hellodev-admin-dash-35-js', 'https://cdnjs.cloudflare.com/ajax/libs/d3/3.5.16/d3.min.js');

				wp_enqueue_script('hellodev-admin-dash-12-js', 'https://'.MORPHING_PORTALS_PORTAL.'/current/imp-portal/scripts/directives/d3-bars-directive.js');


				wp_enqueue_script('hellodev-admin-dash-16-js', 'https://'.MORPHING_PORTALS_PORTAL.'/current/imp-portal/scripts/directives/d3-bars-pie-directive.js');
				wp_enqueue_script('hellodev-admin-dash-17-js', 'https://'.MORPHING_PORTALS_PORTAL.'/current/imp-portal/scripts/directives/d3-line-tooltip-chart-directive.js');


				wp_enqueue_script('hellodev-admin-dash-19-js', 'https://'.MORPHING_PORTALS_PORTAL.'/current/imp-portal/scripts/services/token-service.js');
				wp_enqueue_script('hellodev-admin-dash-20-js', 'https://'.MORPHING_PORTALS_PORTAL.'/current/imp-portal/scripts/services/http-wp-service.js');
				wp_enqueue_script('hellodev-admin-dash-21-js', 'https://'.MORPHING_PORTALS_PORTAL.'/current/imp-portal/scripts/services/message-service.js');
				wp_enqueue_script('hellodev-admin-dash-22-js', 'https://'.MORPHING_PORTALS_PORTAL.'/current/imp-portal/scripts/services/goal-service.js');
				wp_enqueue_script('hellodev-admin-dash-23-js', 'https://'.MORPHING_PORTALS_PORTAL.'/current/imp-portal/scripts/services/segmentation-service.js');
				wp_enqueue_script('hellodev-admin-dash-24-js', 'https://'.MORPHING_PORTALS_PORTAL.'/current/imp-portal/scripts/services/origin-service.js');
				wp_enqueue_script('hellodev-admin-dash-25-js', 'https://'.MORPHING_PORTALS_PORTAL.'/current/imp-portal/scripts/services/tag-service.js');
				wp_enqueue_script('hellodev-admin-dash-26-js', 'https://'.MORPHING_PORTALS_PORTAL.'/current/imp-portal/scripts/services/banner-service.js');
				wp_enqueue_script('hellodev-admin-dash-27-js', 'https://'.MORPHING_PORTALS_PORTAL.'/current/imp-portal/scripts/services/dashboard-service.js');
				wp_enqueue_script('hellodev-admin-dash-28-js', 'https://'.MORPHING_PORTALS_PORTAL.'/current/imp-portal/scripts/services/setting-service.js');
				wp_enqueue_script('hellodev-admin-dash-29-js', 'https://'.MORPHING_PORTALS_PORTAL.'/current/imp-portal/scripts/services/delete-service.js');
				wp_enqueue_script('hellodev-admin-dash-30-js', 'https://'.MORPHING_PORTALS_PORTAL.'/current/imp-portal/scripts/services/display-service.js');
				wp_enqueue_script('hellodev-admin-dash-31-js', 'https://'.MORPHING_PORTALS_PORTAL.'/current/imp-portal/scripts/services/code-service.js');
				wp_enqueue_script('hellodev-admin-dash-32-js', 'https://'.MORPHING_PORTALS_PORTAL.'/current/imp-portal/scripts/services/image-service.js');
				wp_enqueue_script('hellodev-admin-dash-33-js', 'https://'.MORPHING_PORTALS_PORTAL.'/current/imp-portal/scripts/services/chart-service.js');
				wp_enqueue_script('hellodev-admin-dash-34-js', 'https://'.MORPHING_PORTALS_PORTAL.'/current/imp-portal/scripts/services/abtest-service.js');
				wp_enqueue_script('hellodev-admin-dash-36-js', 'https://'.MORPHING_PORTALS_PORTAL.'/current/imp-portal/scripts/services/statistics-service.js');

				wp_enqueue_script('hellodev-admin-dash-37-js', 'https://'.MORPHING_PORTALS_PORTAL.'/current/imp-portal/scripts/services/utils-service.js');
				wp_enqueue_script('hellodev-admin-dash-38-js', 'https://'.MORPHING_PORTALS_PORTAL.'/current/imp-portal/scripts/objects/precedent-map.js');

				wp_enqueue_script('hellodev-admin-dash-39-js', 'https://'.MORPHING_PORTALS_PORTAL.'/current/imp-portal/scripts/external/ng-tags-input.min.js');
				wp_enqueue_script('hellodev-admin-dash-40-js', 'https://cdnjs.cloudflare.com/ajax/libs/Chart.js/1.0.2/Chart.min.js');
				wp_enqueue_script('hellodev-admin-dash-41-js', 'https://'.MORPHING_PORTALS_PORTAL.'/current/imp-portal/scripts/external/angular-chart.min.js');
				wp_enqueue_script('hellodev-admin-dash-42-js', 'https://'.MORPHING_PORTALS_PORTAL.'/current/imp-portal/scripts/external/ng-clipboard-directive.min.js');

				wp_enqueue_style( 'hellodev-dashboard-css', plugins_url('assets/css/dashboard.css', MORPHING_PORTALS_WP_INTEGRATION_PLUGIN_FILE)  );
			}




			if( in_array( $typenow, array( 'post', 'page', 'product') ) ){
				wp_enqueue_script('hellodev-mpi_ph_button-js', plugins_url('assets/js/mpi_ph_button.js', MORPHING_PORTALS_WP_INTEGRATION_PLUGIN_FILE), array('jquery'), '1.0', false);
			}
			wp_enqueue_style( 'hellodev-selectize-css', plugins_url('assets/css/selectize.css', MORPHING_PORTALS_WP_INTEGRATION_PLUGIN_FILE) );
			wp_enqueue_style( 'hellodev-selectize-css', plugins_url('assets/css/admin.css', MORPHING_PORTALS_WP_INTEGRATION_PLUGIN_FILE) );
			wp_enqueue_style( 'hellodev-fontawesome-css', 'https://maxcdn.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css');

			// Add jQuery UI
			wp_enqueue_script( 'jquery-ui-core' );
		    wp_enqueue_script( 'jquery-ui-widget' );
		    wp_enqueue_script( 'jquery-ui-mouse' );
		    wp_enqueue_script( 'jquery-ui-accordion' );
		    wp_enqueue_script( 'jquery-ui-autocomplete' );
		    wp_enqueue_script( 'jquery-ui-slider' );
		    wp_enqueue_script( 'jquery-ui-tabs' );
		    wp_enqueue_script( 'jquery-ui-sortable' );
		    wp_enqueue_script( 'jquery-ui-draggable' );
		    wp_enqueue_script( 'jquery-ui-droppable' );
		    wp_enqueue_script( 'jquery-ui-datepicker' );
		    wp_enqueue_script( 'jquery-ui-resize' );
		    wp_enqueue_script( 'jquery-ui-dialog' );
		    wp_enqueue_script( 'jquery-ui-button' );
		}

		public function hd_mpi_adding_scripts_frontend(){
			$api_scripts = get_option('hd_mpi_api_scripts');
			$api_scripts = str_replace('"', '\'', $api_scripts);
			if($api_scripts){
				$doc = new DOMDocument();
				$doc->loadHTML($api_scripts);
				$xpath = new DOMXPath($doc);
				$src = $xpath->evaluate("string(//script/@src)");
				wp_enqueue_script('hellodev-mp-script-js', $src);
			}
		}

		public function hd_mpi_custom_menu(){

			global $submenu;

			if (current_user_can("manage_options")) {
				add_menu_page(__('Morphing Portals Integration', "morphing-portals-integration"), __('Morphing Portals', "morphing-portals-integration"), 'read', 'morphing-portals-integration', array(
	                $this,
	                'morphing_portals_dashboard_page'
	            ), 'dashicons-chart-area', '60');


	            // Origins submenu
	            add_submenu_page('morphing-portals-integration', __('Morphing Portals - Placeholders', "morphing-portals-integration"), __("Placeholders", "morphing-portals-integration"), 'read', 'morphing-portals-integration-origins', array(
	                $this,
	                'morphing_portals_origins_page'
	            ));

	            // Add origins page
	            add_submenu_page(null , __('Morphing Portals - Add Placeholder', "morphing-portals-integration"), __("Add Placeholder", "morphing-portals-integration"), 'read', 'morphing-portals-integration-add-origin', array(
	                $this,
	                'morphing_portals_add_origins_page'
	            ));

	            // Goals submenu
	            add_submenu_page('morphing-portals-integration', __('Morphing Portals - Goals', "morphing-portals-integration"), __("Goals", "morphing-portals-integration"), 'read', 'morphing-portals-integration-goals', array(
	                $this,
	                'morphing_portals_goals_page'
	            ));

	            // Add goals page
	            add_submenu_page(null , __('Morphing Portals - Add Goal', "morphing-portals-integration"), __("Add Goal", "morphing-portals-integration"), 'read', 'morphing-portals-integration-add-goal', array(
	                $this,
	                'morphing_portals_add_goals_page'
	            ));

	            // Modules submenu
	            add_submenu_page('morphing-portals-integration', __('Morphing Portals - Contents', "morphing-portals-integration"), __("Contents", "morphing-portals-integration"), 'read', 'morphing-portals-integration-modules', array(
	                $this,
	                'morphing_portals_modules_page'
	            ));

	            // Add modules page
	            add_submenu_page(null , __('Morphing Portals - Add Module', "morphing-portals-integration"), __("Add Module", "morphing-portals-integration"), 'read', 'morphing-portals-integration-add-module', array(
	                $this,
	                'morphing_portals_add_modules_page'
	            ));
			/*
	            // User Segmentation submenu
	            add_submenu_page('morphing-portals-integration', __('Morphing Portals - User Segmentation', "morphing-portals-integration"), __("User Segmentation", "morphing-portals-integration"), 'read', 'morphing-portals-integration-user-segmentation', array(
	                $this,
	                'morphing_portals_segmentation_page'
	            ));

	            // Add segmentations page
	            add_submenu_page(null , __('Morphing Portals - Add Segmentation', "morphing-portals-integration"), __("Add Segmentation", "morphing-portals-integration"), 'read', 'morphing-portals-integration-add-segmentation', array(
	                $this,
	                'morphing_portals_add_segmentations_page'
	            ));
				*/
	            // Settings submenu
	            add_submenu_page('morphing-portals-integration', __('Morphing Portals - Settings', "morphing-portals-integration"), __("Settings", "morphing-portals-integration"), 'read', 'morphing-portals-integration-settings', array(
	                $this,
	                'morphing_portals_settings_page'
	            ));
            }

	        $submenu['morphing-portals-integration'][0][0] = 'Dashboard';
		}

		public function morphing_portals_dashboard_page(){
			include MORPHING_PORTALS_WP_INTEGRATION_PLUGIN_PATH . 'views/dashboard.php';
		}

		public function morphing_portals_origins_page(){
			include MORPHING_PORTALS_WP_INTEGRATION_PLUGIN_PATH . 'views/list-origins.php';
		}

		public function morphing_portals_add_origins_page(){
			include MORPHING_PORTALS_WP_INTEGRATION_PLUGIN_PATH . 'views/add-origin.php';
		}

		public function morphing_portals_goals_page(){
			include MORPHING_PORTALS_WP_INTEGRATION_PLUGIN_PATH . 'views/list-goals.php';
		}

		public function morphing_portals_add_goals_page(){
			include MORPHING_PORTALS_WP_INTEGRATION_PLUGIN_PATH . 'views/add-goal.php';
		}

		public function morphing_portals_modules_page(){
			include MORPHING_PORTALS_WP_INTEGRATION_PLUGIN_PATH . 'views/list-modules.php';
		}

		public function morphing_portals_add_modules_page(){
			include MORPHING_PORTALS_WP_INTEGRATION_PLUGIN_PATH . 'views/add-module.php';
		}

		public function morphing_portals_segmentation_page(){
			include MORPHING_PORTALS_WP_INTEGRATION_PLUGIN_PATH . 'views/list-segmentations.php';
		}

		public function morphing_portals_add_segmentations_page(){
			include MORPHING_PORTALS_WP_INTEGRATION_PLUGIN_PATH . 'views/add-segmentation.php';
		}

		public function morphing_portals_settings_page(){
			include MORPHING_PORTALS_WP_INTEGRATION_PLUGIN_PATH . 'views/settings.php';
		}

		public static function hd_mpi_install() {

			// Get database info
		    global $wpdb;

		    //DB Version
		    $hd_mpi_db_version = MORPHING_PORTALS_WP_API_VERSION;

		    //Instaled DB Version
		    $hd_mpi_db_installed_ver = get_option("hd_mpi_db_version");

		    // Install new Version!
		    if($hd_mpi_db_installed_ver !== $hd_mpi_db_version){

			    update_option('hd_mpi_api_scripts' , '<script src="//'.MORPHING_PORTALS_PORTAL.'/scripts/current/imp.min.js"></script>');

		  		$hd_mpi_charset_collate = $wpdb->get_charset_collate();
		  		$hd_mpi_table_name_origins = MORPHING_PORTALS_WP_INTEGRATION_DATABASE_ORIGINS;

		  			$hd_mpi_create_sql1 = "CREATE TABLE $hd_mpi_table_name_origins (
		  				id mediumint(9) NOT NULL AUTO_INCREMENT,
		  				mp_id varchar(40) NULL,
		  				name TEXT NOT NULL,
		  				tags TEXT NOT NULL,
		  				UNIQUE KEY id (id)
		  			) $hd_mpi_charset_collate;";

		  			// Add table to database
		  			require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
		  			dbDelta( $hd_mpi_create_sql1 );

		  		$hd_mpi_table_name_goals = MORPHING_PORTALS_WP_INTEGRATION_DATABASE_GOALS;

		  			$hd_mpi_create_sql2 = "CREATE TABLE $hd_mpi_table_name_goals (
		  				id mediumint(9) NOT NULL AUTO_INCREMENT,
		  				mp_id varchar(40) NULL,
		  				name TEXT NOT NULL,
		  				URL VARCHAR (255) NOT NULL,
		  				UNIQUE KEY id (id)
		  			) $hd_mpi_charset_collate;";

		  			// Add table to database
		  			require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
		  			dbDelta( $hd_mpi_create_sql2 );

		  		$hd_mpi_table_name_modules = MORPHING_PORTALS_WP_INTEGRATION_DATABASE_MODULES;

		  			$hd_mpi_create_sql3 = "CREATE TABLE $hd_mpi_table_name_modules (
		  				id mediumint(9) NOT NULL AUTO_INCREMENT,
		  				mp_id varchar(40) NULL,
		  				name TEXT NOT NULL,
		  				URL VARCHAR (255) NULL,
		  				goal_id mediumint(9) NULL,
		  				tags TEXT NULL,
		  				exclude_segmentations TEXT NULL,
		  				banner_html TEXT NULL,
							labels TEXT NULL,
		  				UNIQUE KEY id (id)
		  			) $hd_mpi_charset_collate;";

		  			// Add table to database
		  			require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
		  			dbDelta( $hd_mpi_create_sql3 );

		  		$hd_mpi_table_name_segmentations = MORPHING_PORTALS_WP_INTEGRATION_DATABASE_SEGMENTATIONS;

		  			$hd_mpi_create_sql4 = "CREATE TABLE $hd_mpi_table_name_segmentations (
		  				id mediumint(9) NOT NULL AUTO_INCREMENT,
		  				mp_id varchar(40) NULL,
		  				name TEXT NOT NULL,
		  				URL VARCHAR (255) NULL,
		  				user_segmentations TEXT NULL,
		  				UNIQUE KEY id (id)
		  			) $hd_mpi_charset_collate;";

		  			// Add table to database
		  			require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
		  			dbDelta( $hd_mpi_create_sql4 );

		        update_option("hd_mpi_db_version", $hd_mpi_db_version);
		  	}
	  	}
	}
}

if(class_exists('morphing_portals_integration'))
{

	if ( ! defined( 'ABSPATH' ) ) {
	    exit; // Exit if accessed directly
	}

	global $wpdb;

	// Define some constants
	define('MORPHING_PORTALS_WP_INTEGRATION_DATABASE_ORIGINS', $wpdb->prefix . 'mpwpi_origins');
	define('MORPHING_PORTALS_WP_INTEGRATION_DATABASE_GOALS', $wpdb->prefix . 'mpwpi_goals');
	define('MORPHING_PORTALS_WP_INTEGRATION_DATABASE_MODULES', $wpdb->prefix . 'mpwpi_modules');
	define('MORPHING_PORTALS_WP_INTEGRATION_DATABASE_SEGMENTATIONS', $wpdb->prefix . 'mpwpi_segmentations');
	define('MORPHING_PORTALS_WP_INTEGRATION_PLUGIN_PATH', plugin_dir_path( __FILE__ ));
	define('MORPHING_PORTALS_WP_INTEGRATION_PLUGIN_FILE', __FILE__);
	define('MORPHING_PORTALS_WP_INTEGRATION_PLUGIN_URL', plugin_dir_url(__FILE__));

	// Define endpoints
	define('MORPHING_PORTALS_WP_API_BASE_URL', 'https://'.MORPHING_PORTALS_API);
	define('MORPHING_PORTALS_WP_API_GOALS_ENDPOINT', '/api/v1/goals');
	define('MORPHING_PORTALS_WP_API_ORIGINS_ENDPOINT', '/api/v1/origins');
	define('MORPHING_PORTALS_WP_API_BANNERS_ENDPOINT', '/api/v1/banners');
	define('MORPHING_PORTALS_WP_API_SEGMENTATIONS_ENDPOINT', '/api/v1/segmentationTags');
	define('MORPHING_PORTALS_WP_API_THRESHOLD_ENDPOINT', '/api/v1/threshold');
	define('MORPHING_PORTALS_WP_API_TAGS_ENDPOINT', '/api/v1/tags');
	define('MORPHING_PORTALS_WP_API_LABELS_ENDPOINT', '/api/v1/organisation/contextTags');
	define('MORPHING_PORTALS_WP_API_NEWS_ENDPOINT', '/api/v1/organisation/news');

	define('MORPHING_PORTALS_WP_API_GOAL_ENDPOINT', '/api/v1/goal');
	define('MORPHING_PORTALS_WP_API_BANNER_ENDPOINT', '/api/v1/banner');
	define('MORPHING_PORTALS_WP_API_ORIGIN_ENDPOINT', '/api/v1/origin');
	define('MORPHING_PORTALS_WP_API_SEGMENTATION_ENDPOINT', '/api/v1/userSegmentation');

	define('MORPHING_PORTALS_WP_API_VERSION', '1.2');

	require_once MORPHING_PORTALS_WP_INTEGRATION_PLUGIN_PATH . 'includes/goals_list_table.php';
	require_once MORPHING_PORTALS_WP_INTEGRATION_PLUGIN_PATH . 'includes/origins_list_table.php';
	require_once MORPHING_PORTALS_WP_INTEGRATION_PLUGIN_PATH . 'includes/segmentations_list_table.php';
	require_once MORPHING_PORTALS_WP_INTEGRATION_PLUGIN_PATH . 'includes/modules_list_table.php';
	require_once MORPHING_PORTALS_WP_INTEGRATION_PLUGIN_PATH . 'includes/api_comm.php';
	require_once MORPHING_PORTALS_WP_INTEGRATION_PLUGIN_PATH . 'includes/hd_mp_placeholder_widget.php';

	// Add classes
	require_once MORPHING_PORTALS_WP_INTEGRATION_PLUGIN_PATH . 'includes/classes/hd_mp_goals.php';
	require_once MORPHING_PORTALS_WP_INTEGRATION_PLUGIN_PATH . 'includes/classes/hd_mp_origins.php';
	require_once MORPHING_PORTALS_WP_INTEGRATION_PLUGIN_PATH . 'includes/classes/hd_mp_modules.php';
	require_once MORPHING_PORTALS_WP_INTEGRATION_PLUGIN_PATH . 'includes/classes/hd_mp_segmentations.php';

	// Create new object
	$hd_mpi_loader = new morphing_portals_integration();

	// Register activation hook
	register_activation_hook( __FILE__, array('morphing_portals_integration', 'hd_mpi_install'));

}
