<?php

if (! defined('ABSPATH')) {
    exit(); // Exit if accessed directly
}

global $wpdb;

echo '<script>
		jQuery("#toplevel_page_morphing-portals-integration").removeClass("wp-not-current-submenu");
		jQuery("#toplevel_page_morphing-portals-integration").addClass("wp-has-current-submenu");
		jQuery("#toplevel_page_morphing-portals-integration .wp-submenu-wrap li:eq(2)").addClass("current");
	  </script>';

// Form validation variables
$hd_mpi_field_errors = '';
$hd_mpi_field_success = '';
$hd_mpi_field_values = array();
$hd_mpi_label = __("Add Placeholder", "morphing-portals-integration");

   // This part handles insertions/updates
	if ($_SERVER["REQUEST_METHOD"] == "POST") {
	    if(empty($_POST['origin_name'])){
			    $hd_mpi_field_errors.= __('Field name is empty!', 'morphing-portals-integration')."<br>";
		}

		if(empty($_POST['origin_tags'])){
		    $hd_mpi_field_errors.= __('Field tags is empty!', 'morphing-portals-integration')."<br>";
		}


     // No errors? Insert/Update data then
	   if($hd_mpi_field_errors == ''){
		   
		 if(wp_verify_nonce( $_REQUEST['_wpnonce'], 'add-origin-hellodev' )){
	
			 // Get data from form
	         $hd_mpi_name = sanitize_text_field($_POST['origin_name']);
	         $hd_mpi_tags = sanitize_text_field($_POST['origin_tags']);
	
	         // Put data into array
	         $hd_mpi_field_values = array('name' => $hd_mpi_name, 'tags' => $hd_mpi_tags);
	
	         // No custom_field_id? Then insert new row
	         if(!isset($_POST['origin_id'])){
	
		        $api_token = get_option('hd_mpi_api_token');
		        $endpoint = MORPHING_PORTALS_WP_API_ORIGIN_ENDPOINT;
			    	$url = MORPHING_PORTALS_WP_API_BASE_URL . $endpoint;
	
		        $origin_obj  = new MP_Origin_API($hd_mpi_field_values);
		        $comunicator = new MP_API_COMM($url, $api_token);
	
		        $origin_id = $comunicator->add($origin_obj->to_create(), 'originID');
	
		        $comunicator->close();
	
		        if($origin_id){
	
			        $hd_mpi_field_values['mp_id'] = $origin_id;
	
		            // Insert row
		            $wpdb->insert( MORPHING_PORTALS_WP_INTEGRATION_DATABASE_ORIGINS, $hd_mpi_field_values);
	
		            // Get row id
		            $hd_mpi_field_lastid = $wpdb->insert_id;
	
		            $hd_mpi_field_values['origin_id'] = $hd_mpi_field_lastid;
	
		            // Print success!
		            $hd_mpi_field_success = __("Added $hd_mpi_name successfully!", 'morphing-portals-integration');
		        }
		        else{
			        $hd_mpi_field_errors.= __('Could not add placeholder due to problem syncing with API!', 'morphing-portals-integration')."<br>";
		        }
	
		   	  }
	
		      // Update existing row
		      else{
	
			      $hd_mpi_field_values['id'] = $_POST['origin_id'];
			      $api_token = get_option('hd_mpi_api_token');
			      $endpoint = MORPHING_PORTALS_WP_API_ORIGIN_ENDPOINT;
	
	  			  $origin_obj = new MP_Origin_API($hd_mpi_field_values);
	  			  $origin_obj->search_mp_id();
	
	  			  $endpoint .= "/" . $origin_obj->get_mp_id();
	  			  $url = MORPHING_PORTALS_WP_API_BASE_URL . $endpoint;
	
	  			  $comunicator = new MP_API_COMM($url, $api_token);
	
	  			  $origin_id = $comunicator->update($origin_obj->to_create(), 'originID');
	  			  $comunicator->close();
	  			  
	  			  if($origin_id){
	
			          $hd_mpi_field_id = sanitize_text_field($_POST['origin_id']);
		
			          $wpdb->update(
				         MORPHING_PORTALS_WP_INTEGRATION_DATABASE_ORIGINS,
				          $hd_mpi_field_values,
			              array( 'id' => $hd_mpi_field_id ),
			              array('%s','%s')
					  );
		
			          // Print success
			          $hd_mpi_field_values['origin_id'] = $hd_mpi_field_id;
			          $hd_mpi_field_success = __("Updated $hd_mpi_name successfully!", 'morphing-portals-integration');
		          }
		          else{
			          $hd_mpi_field_errors.= __('Could not update placeholder due to problem syncing with API!', 'morphing-portals-integration')."<br>";
		          }
		      }
	      }
	      else{
		      $hd_mpi_field_errors.= __('Invalid nounce!', 'morphing-portals-integration')."<br>";
	      }
	    }
	}


// Load data if user loads existing item
if (isset($_GET['origin_id'])){

  $hd_mpi_id = $_GET['origin_id'];

  $hd_mpi_field_values = $wpdb->get_row( "SELECT * FROM ". MORPHING_PORTALS_WP_INTEGRATION_DATABASE_ORIGINS. " WHERE id = $hd_mpi_id", ARRAY_A );
  $hd_mpi_field_values['origin_id'] = $hd_mpi_id;
  $hd_mpi_label = __("Update Placeholder", "morphing-portals-integration");

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
      href="<?php menu_page_url("morphing-portals-integration-origins") ?>"><?php _e("Back", "morphing-portals-integration"); ?></a>
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
					<th scope="row"><label for="origin_name"><?php _e("Name", "morphing-portals-integration"); ?></label></th>
					<td><input type="text" name="origin_name" id="origin_name"
            class="input-add-field" value="<?php if(isset($hd_mpi_field_values['name'])) echo $hd_mpi_field_values['name'] ?>"/></td>
				</tr>
				<tr class="form-field">
					<th scope="row"><label for="origin_tags"><?php _e("Tags", "morphing-portals-integration"); ?></label></th>
					<td><input type="text" name="origin_tags" id="origin_tags"
            class="input-add-field hd_mpi_free_selectize" value="<?php if(isset($hd_mpi_field_values['tags'])) echo $hd_mpi_field_values['tags'] ?>"/></td>
				</tr>
			</tbody>
		</table>
		
	<?php 
   	 	wp_nonce_field( 'add-origin-hellodev' );
    ?>

    <?php if(isset($hd_mpi_field_values['origin_id'])): ?>
      <input type="hidden" name="origin_id" class="button-primary"
			value="<?php echo $hd_mpi_field_values['origin_id']; ?>" />
    <?php endif; ?>

      <input type="submit" name="submit" class="button-primary"
			value="<?php _e("Save placeholder", "morphing-portals-integration"); ?>" />
	</form>
</div>
