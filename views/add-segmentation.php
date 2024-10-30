<?php

if (! defined('ABSPATH')) {
    exit(); // Exit if accessed directly
}

global $wpdb;

echo '<script>
		jQuery("#toplevel_page_morphing-portals-integration").removeClass("wp-not-current-submenu");
		jQuery("#toplevel_page_morphing-portals-integration").addClass("wp-has-current-submenu");
		jQuery("#toplevel_page_morphing-portals-integration .wp-submenu-wrap li:eq(5)").addClass("current");
	  </script>';

// Form validation variables
$hd_mpi_field_errors = '';
$hd_mpi_field_success = '';
$hd_mpi_field_values = array();
$hd_mpi_label = __("Add Segmentation", "morphing-portals-integration");

   // This part handles insertions/updates
	if ($_SERVER["REQUEST_METHOD"] == "POST") {
	    if(empty($_POST['segmentation_name'])){
			    $hd_mpi_field_errors.= __('Field name is empty!', 'morphing-portals-integration')."<br>";
		}

		if(empty($_POST['segmentation_url'])){
		    $hd_mpi_field_errors.= __('Field url is empty!', 'morphing-portals-integration')."<br>";
		}


     // No errors? Insert/Update data then
	   if($hd_mpi_field_errors == ''){
		   
		 if(wp_verify_nonce( $_REQUEST['_wpnonce'], 'add-segmentation-hellodev' )){

			 // Get data from form
	         $hd_mpi_name = $_POST['segmentation_name'];
	         $hd_mpi_url =  $_POST['segmentation_url'];
	         $hd_mpi_segmentations = $_POST['segmentation_segmentations'];
	
	         // Put data into array
	         $hd_mpi_field_values = array('name' => $hd_mpi_name, 'URL' => $hd_mpi_url, 'user_segmentations' => $hd_mpi_segmentations);
	
	         // No custom_field_id? Then insert new row
	         if(!isset($_POST['segmentation_id'])){
	
		        $api_token = get_option('hd_mpi_api_token');
		        $endpoint = MORPHING_PORTALS_WP_API_SEGMENTATION_ENDPOINT;
				    $url = MORPHING_PORTALS_WP_API_BASE_URL . $endpoint;
	
				    $segmentation_obj  = new MP_Segmentation_API($hd_mpi_field_values);
		        $comunicator = new MP_API_COMM($url, $api_token);
	
		        $segmentation_id = $comunicator->add($segmentation_obj->to_create(), 'segmentationTagID');
	
		        $comunicator->close();
	
		        if($segmentation_id){
	
			        $hd_mpi_field_values['mp_id'] = $segmentation_id;
	
		            // Insert row
		            $wpdb->insert( MORPHING_PORTALS_WP_INTEGRATION_DATABASE_SEGMENTATIONS, $hd_mpi_field_values);
	
		            // Get row id
		            $hd_mpi_field_lastid = $wpdb->insert_id;
	
		            $hd_mpi_field_values['segmentation_id'] = $hd_mpi_field_lastid;
	
		            // Print success!
		            $hd_mpi_field_success = __("Added $hd_mpi_name successfully!", 'morphing-portals-integration');
	
	            }
	
	            else{
			        $hd_mpi_field_errors.= __('Could not add segmentation due to problem syncing with API!', 'morphing-portals-integration')."<br>";
		        }
	
		   	  }
	
		      // Update existing row
		      else{
	
			      $hd_mpi_field_values['id'] = $_POST['segmentation_id'];
			      $api_token = get_option('hd_mpi_api_token');
			      $endpoint = MORPHING_PORTALS_WP_API_SEGMENTATION_ENDPOINT;
	
	  			  $segmentation_obj = new MP_Segmentation_API($hd_mpi_field_values);
	  			  $segmentation_obj->search_mp_id();
	
	  			  $endpoint .= "/" . $segmentation_obj->get_mp_id();
	  			  $url = MORPHING_PORTALS_WP_API_BASE_URL . $endpoint;
	
	  			  $comunicator = new MP_API_COMM($url, $api_token);
	  			  $segmentation_id = $comunicator->update($segmentation_obj->to_create(), 'segmentationTagID');
	  			  $comunicator->close();
	  			  
	  			  if($segmentation_id){
	
			          $hd_mpi_field_id = $_POST['segmentation_id'];
			          $wpdb->update(
				         MORPHING_PORTALS_WP_INTEGRATION_DATABASE_SEGMENTATIONS,
				          $hd_mpi_field_values,
			              array( 'id' => $hd_mpi_field_id ),
			              array('%s','%s', '%s')
					  );
		
			          // Print success
			          $hd_mpi_field_values['segmentation_id'] = $hd_mpi_field_id;
			          $hd_mpi_field_success = __("Updated $hd_mpi_name successfully!", 'morphing-portals-integration');
		          
		          }
		          else{
			          $hd_mpi_field_errors.= __('Could not update segmentation due to problem syncing with API!', 'morphing-portals-integration')."<br>";
		          }
		      }
	      }
	      else{
		      $hd_mpi_field_errors.= __('Invalid nounce!', 'morphing-portals-integration')."<br>";
	      }
	    }
	}


// Load data if user loads existing item
if (isset($_GET['segmentation_id'])){

  $hd_mpi_id = $_GET['segmentation_id'];

  $hd_mpi_field_values = $wpdb->get_row( "SELECT * FROM ". MORPHING_PORTALS_WP_INTEGRATION_DATABASE_SEGMENTATIONS. " WHERE id = $hd_mpi_id", ARRAY_A );
  $hd_mpi_field_values['segmentation_id'] = $hd_mpi_id;
  
  $hd_mpi_label = __("Update Segmentation", "morphing-portals-integration");

}
?>

<style>
.input-add-field {
	width: 25em !important;
}

</style>

<div class="wrap">

	<h2><?php echo $hd_mpi_label; ?>
    <a class="add-new-h2"
      href="<?php menu_page_url("morphing-portals-integration-user-segmentation") ?>"><?php _e("Back", "morphing-portals-integration"); ?></a>
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
					<th scope="row"><label for="segmentation_name"><?php _e("Name", "morphing-portals-integration"); ?></label></th>
					<td><input type="text" name="segmentation_name" id="segmentation_name"
            class="input-add-field" value="<?php if(isset($hd_mpi_field_values['name'])) echo $hd_mpi_field_values['name'] ?>"/></td>
				</tr>
				<tr class="form-field">
					<th scope="row"><label for="segmentation_url"><?php _e("URL", "morphing-portals-integration"); ?></label></th>
					<td><input type="text" name="segmentation_url" id="segmentation_url"
            class="input-add-field" value="<?php if(isset($hd_mpi_field_values['URL'])) echo $hd_mpi_field_values['URL'] ?>"/></td>
				</tr>
				<tr class="form-field">
					<th scope="row"><label for="segmentation_segmentations"><?php _e("Segmentations", "morphing-portals-integration"); ?></label></th>
					<td><input type="text" name="segmentation_segmentations" id="segmentation_segmentations"
            class="input-add-field hd_mpi_free_selectize" value="<?php if(isset($hd_mpi_field_values['user_segmentations'])) echo $hd_mpi_field_values['user_segmentations'] ?>"/></td>
				</tr>
			</tbody>
		</table>
		
	<?php 
   	 	wp_nonce_field( 'add-segmentation-hellodev' );
    ?>

    <?php if(isset($hd_mpi_field_values['segmentation_id'])): ?>
      <input type="hidden" name="segmentation_id" class="button-primary"
			value="<?php echo $hd_mpi_field_values['segmentation_id']; ?>" />
    <?php endif; ?>

      <input type="submit" name="submit" class="button-primary"
			value="<?php _e("Save segmentation", "morphing-portals-integration"); ?>" />
	</form>
</div>
