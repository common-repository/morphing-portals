<?php

if (! defined('ABSPATH')) {
    exit(); // Exit if accessed directly
}

global $wpdb;

echo '<script>
		jQuery("#toplevel_page_morphing-portals-integration").removeClass("wp-not-current-submenu");
		jQuery("#toplevel_page_morphing-portals-integration").addClass("wp-has-current-submenu");
		jQuery("#toplevel_page_morphing-portals-integration .wp-submenu-wrap li:eq(4)").addClass("current");
	  </script>';

// Form validation variables
$hd_mpi_field_errors = '';
$hd_mpi_field_success = '';
$hd_mpi_field_values = array();
$hd_mpi_label = __("Add Content", "morphing-portals-integration");

   // This part handles insertions/updates
	if ($_SERVER["REQUEST_METHOD"] == "POST") {
	    if(empty($_POST['module_name'])){
			    $hd_mpi_field_errors.= __('Field name is empty!', 'morphing-portals-integration')."<br>";
		}


     // No errors? Insert/Update data then
	   if($hd_mpi_field_errors == ''){

		 if(wp_verify_nonce( $_REQUEST['_wpnonce'], 'add-module-hellodev' )){

			 // Get data from form
	         $hd_mpi_name          = sanitize_text_field($_POST['module_name']);
	         $hd_mpi_url           = sanitize_text_field($_POST['module_url']);
	         $hd_mpi_goal          = sanitize_text_field($_POST['module_goal']);
	         $hd_mpi_tags          = sanitize_text_field($_POST['module_tags']);
	         $hd_mpi_segmentations = sanitize_text_field($_POST['module_segmentations']);
           $hd_mpi_labels        = sanitize_text_field($_POST['module_labels']);
	         if(isset($_POST['module_html'])){
	         	$hd_mpi_html       = esc_html($_POST['module_html']);
	         }
	         else{
		         $hd_mpi_html = '';
	         }

	         if($hd_mpi_html){
		         $token = bin2hex(openssl_random_pseudo_bytes(5));
		         $hd_mpi_url = home_url() . '/?morphingportals_api=1&id=' . $token;
	         }

	         // Put data into array
	         $hd_mpi_field_values = array('name' => $hd_mpi_name, 'URL' => $hd_mpi_url, 'goal_id' => $hd_mpi_goal, 'tags' => $hd_mpi_tags, 'exclude_segmentations' => $hd_mpi_segmentations, 'banner_html' => $hd_mpi_html, 'labels' => $hd_mpi_labels);

	         // No custom_field_id? Then insert new row
	         if(!isset($_POST['module_id'])){

		        $api_token = get_option('hd_mpi_api_token');
		        $endpoint = MORPHING_PORTALS_WP_API_BANNER_ENDPOINT;
				$url = MORPHING_PORTALS_WP_API_BASE_URL . $endpoint;

				$module_obj = new MP_Module_API($hd_mpi_field_values);
		        $comunicator = new MP_API_COMM($url, $api_token);

		        $module_obj->getMPGoalID();

		        $module_id = $comunicator->addModule($module_obj->to_create());
		        $comunicator->close();

		        if($module_id){

			        $hd_mpi_field_values['mp_id'] = $module_id;

		            // Insert row
		            $wpdb->insert( MORPHING_PORTALS_WP_INTEGRATION_DATABASE_MODULES, $hd_mpi_field_values);

		            // Get row id
		            $hd_mpi_field_lastid = $wpdb->insert_id;

		            $hd_mpi_field_values['module_id'] = $hd_mpi_field_lastid;

		            // Print success!
		            $hd_mpi_field_success = __("Added $hd_mpi_name successfully!", 'morphing-portals-integration');

	            }
	            else{
		            $hd_mpi_field_errors.= __('Could not add content due to problem syncing with API!', 'morphing-portals-integration')."<br>";
	            }

		   	  }

		      // Update existing row
		      else{

			      $hd_mpi_field_values['id'] = $_POST['module_id'];
			      $api_token = get_option('hd_mpi_api_token');
			      $endpoint = MORPHING_PORTALS_WP_API_BANNER_ENDPOINT;

				  $module_obj = new MP_Module_API($hd_mpi_field_values);
				  $module_obj->search_mp_id();
				  $module_obj->getMPGoalID();

				  $endpoint .= "/" . $module_obj->get_mp_id();
				  $url = MORPHING_PORTALS_WP_API_BASE_URL . $endpoint;

				  $comunicator = new MP_API_COMM($url, $api_token);
				  $module_id = $comunicator->updateModule($module_obj->to_create());
				  if($module_id){
					  $comunicator->close();

			          $hd_mpi_field_id = $_POST['module_id'];
			          $wpdb->update(
				         MORPHING_PORTALS_WP_INTEGRATION_DATABASE_MODULES,
				          $hd_mpi_field_values,
			              array( 'id' => $hd_mpi_field_id ),
			              array('%s','%s', '%d', '%s', '%s', '%s', '%s')
					  );

			          // Print success
			          $hd_mpi_field_values['module_id'] = $hd_mpi_field_id;
			          $hd_mpi_field_success = __("Updated $hd_mpi_name successfully!", 'morphing-portals-integration');
		          }
		          else{
			          $hd_mpi_field_errors.= __('Could not update content due to problem syncing with API!', 'morphing-portals-integration')."<br>";
		          }
		      }
	      }
	      else{
		      $hd_mpi_field_errors.= __('Invalid nounce!', 'morphing-portals-integration')."<br>";
	      }
	    }
	}


// Load data if user loads existing item
if (isset($_GET['module_id'])){

  $hd_mpi_id = $_GET['module_id'];

  $hd_mpi_field_values = $wpdb->get_row( "SELECT * FROM ". MORPHING_PORTALS_WP_INTEGRATION_DATABASE_MODULES. " WHERE id = $hd_mpi_id", ARRAY_A );
  $hd_mpi_field_values['module_id'] = $hd_mpi_id;

  $hd_mpi_label = __("Update Content", "morphing-portals-integration");

}

$goals = $wpdb->get_results( "SELECT * FROM ". MORPHING_PORTALS_WP_INTEGRATION_DATABASE_GOALS);

$goal_options = array();

foreach($goals as $goal){
	$goal_options[$goal->name] = $goal->id;
}

$segmentations = $wpdb->get_results( "SELECT * FROM ". MORPHING_PORTALS_WP_INTEGRATION_DATABASE_SEGMENTATIONS);

$segmentation_options = array();

foreach($segmentations as $segmentation){
	$segmentation_options = array_merge($segmentation_options, explode(',', $segmentation->user_segmentations));
}

$segmentation_options = array_unique($segmentation_options);

$segmentation_ready = array();
foreach($segmentation_options as $segm){
	$segmentation_ready[] = array('title' => $segm);
}

$segmentation_json = json_encode($segmentation_ready);

$api_token = get_option('hd_mpi_api_token');
$endpoint = MORPHING_PORTALS_WP_API_TAGS_ENDPOINT;
$url = MORPHING_PORTALS_WP_API_BASE_URL . $endpoint;

$comunicator = new MP_API_COMM($url, $api_token);
$tags_result = $comunicator->get_all();

$tags_array = array();

if(isset($tags_result['tags'])){
	foreach($tags_result['tags'] as $tag_r){
		$tags_array[] = array('title' => $tag_r['name']);
	}
}

$tags_json = json_encode($tags_array);

$endpoint = MORPHING_PORTALS_WP_API_LABELS_ENDPOINT;
$url = MORPHING_PORTALS_WP_API_BASE_URL . $endpoint;

$comunicator = new MP_API_COMM($url, $api_token);
$labels_result = $comunicator->get_all();

$labels_array = array();

foreach($labels_result as $labels_r){
	$labels_array[] = array('title' => $labels_r);
}


$labels_json = json_encode($labels_array);

echo "<script>
	var hd_mpi_module_options=$segmentation_json;
	var hd_mpi_tags_options=$tags_json;
  var hd_mpi_labels_options=$labels_json;
	</script>";
?>

<style>
.input-add-field {
	width: 25em !important;
}

</style>

<div class="wrap">

	<h2><?php echo $hd_mpi_label; ?>
    <a class="add-new-h2"
      href="<?php menu_page_url("morphing-portals-integration-modules") ?>"><?php _e("Back", "morphing-portals-integration"); ?></a>
  </h2>
	<?php if($hd_mpi_field_errors !== ''){ ?>
  	<div class="error">
		<p><?php echo $hd_mpi_field_errors; ?></p>
	</div>
  <?php
}else if($hd_mpi_field_success !== ''){ ?>
  	<div class="updated">
		<p><?php echo $hd_mpi_field_success; ?></p>
	</div>
  <?php } ?>
  <form method="post" action="">
		<table class="form-table">
			<tbody>
				<tr class="form-field">
					<th scope="row"><label for="module_name"><?php _e("Name", "morphing-portals-integration"); ?></label></th>
					<td><input type="text" name="module_name" id="module_name"
            class="input-add-field" value="<?php if(isset($hd_mpi_field_values['name'])) echo $hd_mpi_field_values['name'] ?>"/></td>
				</tr>
				<tr class="form-field">
					<th scope="row"><label for="module_url"><?php _e("Content URL", "morphing-portals-integration"); ?></label></th>
					<td><input type="text" name="module_url" id="module_url"
            class="input-add-field" value="<?php if(isset($hd_mpi_field_values['URL'])) echo $hd_mpi_field_values['URL'] ?>"/></td>
				</tr>
				<tr class="form-field">
					<th scope="row"><label for="module_html"><?php _e("Module Content", "morphing-portals-integration"); ?></label></th>
					<td><!--<textarea name="module_html" id="module_html"
            class="input-add-field" rows="4" cols="50"/><?php if(isset($hd_mpi_field_values['banner_html'])) echo base64_decode($hd_mpi_field_values['banner_html']) ?></textarea><label>-->
            <?php
	            $banner_html = '';
	            if(isset($hd_mpi_field_values['banner_html'])){
	            $banner_html = html_entity_decode($hd_mpi_field_values['banner_html']);
	            $banner_html = str_replace('\\', '', $banner_html);
            }
            ?>
            <?php wp_editor( $banner_html, 'module_html', $settings = array() ); ?>
            <?php _e("Insert HTML here if no URL is available."); ?>
            </td>
				<tr class="form-field">
					<th scope="row"><label for="module_goal"><?php _e("Goal", "morphing-portals-integration"); ?></label></th>
					<td><select name="module_goal" id = "module_goal" class="input-add-field">
            <?php foreach($goal_options as $k=>$v) {
              if(isset($hd_mpi_field_values['goal_id'])){
                $selected = ($v == $hd_mpi_field_values['goal_id']) ? " selected='selected'" : "";
                echo "<option value='$v'$selected>$k</option>\n";
              }
              else{
                echo "<option value='$v'>$k</option>\n";
              }
            }?>
						</select></td>
				</tr>
				<tr class="form-field">
					<th scope="row"><label for="module_tags"><?php _e("Placeholder Tags", "morphing-portals-integration"); ?></label></th>
					<td><input type="text" name="module_tags" id="module_tags"
            class="input-add-field hd_mpi_tags_selectize" value="<?php if(isset($hd_mpi_field_values['tags'])) echo $hd_mpi_field_values['tags'] ?>"/></td>
				</tr>
		<!--		<tr class="form-field">
					<th scope="row"><label for="module_segmentations"><?php //_e("Exclude Segmentations", "morphing-portals-integration"); ?></label></th>
					<td><input type="text" name="module_segmentations" id="module_segmentations"
            class="input-add-field hd_mpi_module_selectize" value="<?php //if(isset($hd_mpi_field_values['exclude_segmentations'])) echo $hd_mpi_field_values['exclude_segmentations'] ?>"/></td>
				</tr>
      -->
				<tr class="form-field">
    					<th scope="row"><label for="module_labels"><?php _e("Labels", "morphing-portals-integration"); ?></label></th>
    					<td><input type="text" name="module_labels" id="module_labels"
                class="input-add-field hd_mpi_labels_selectize" value="<?php if(isset($hd_mpi_field_values['labels'])) echo $hd_mpi_field_values['labels'] ?>"/></td>
    				</tr>
			</tbody>
		</table>

	<?php
   	 	wp_nonce_field( 'add-module-hellodev' );
    ?>

    <?php if(isset($hd_mpi_field_values['module_id'])): ?>
      <input type="hidden" name="module_id" class="button-primary"
			value="<?php echo $hd_mpi_field_values['module_id']; ?>" />
    <?php endif; ?>

      <input type="submit" name="submit" class="button-primary"
			value="<?php _e("Save content", "morphing-portals-integration"); ?>" />
	</form>
</div>
