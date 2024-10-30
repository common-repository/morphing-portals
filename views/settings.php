<?php

if (! defined('ABSPATH')) {
    exit(); // Exit if accessed directly
}

$hd_mpi_field_errors = '';
$hd_mpi_field_success = '';

$api_token = get_option('hd_mpi_api_token');
$new_phrases = get_option('hd_mpi_new_phrases');
$activation_rate = get_option('hd_mpi_activation_rate');

if ($_SERVER["REQUEST_METHOD"] == "POST") {

  if(isset($_POST['hd_mpi_new_phrases'])){
	$api_token = get_option('hd_mpi_api_token');
	$endpoint = MORPHING_PORTALS_WP_API_NEWS_ENDPOINT;
	$url = MORPHING_PORTALS_WP_API_BASE_URL . $endpoint;

	if($_POST['hd_mpi_new_phrases']){
		$news_phrases = explode(',', sanitize_text_field($_POST['hd_mpi_new_phrases']));
	}
	else{
		$news_phrases = array();
	}


	$comunicator = new MP_API_COMM($url, $api_token);
	$result = $comunicator->update($news_phrases, '');
    update_option('hd_mpi_new_phrases' ,sanitize_text_field($_POST['hd_mpi_new_phrases']));
    $new_phrases = $_POST['hd_mpi_new_phrases'];
    $hd_mpi_field_success = __("Settings updated", "morphing-portals-integration");
  }

  if(isset($_POST['hd_mpi_activation_rate'])){
	$activation_rate = get_option('hd_mpi_activation_rate');
	if($activation_rate !== $_POST['hd_mpi_activation_rate']){
		$api_token = get_option('hd_mpi_api_token');
		$endpoint = MORPHING_PORTALS_WP_API_THRESHOLD_ENDPOINT;
		$endpoint .= "/" . sanitize_text_field($_POST['hd_mpi_activation_rate']);
		$url = MORPHING_PORTALS_WP_API_BASE_URL . $endpoint;

		$comunicator = new MP_API_COMM($url, $api_token);
		$result = $comunicator->update(array(), '');

	    update_option('hd_mpi_activation_rate' ,sanitize_text_field($_POST['hd_mpi_activation_rate']));
	    $activation_rate = $_POST['hd_mpi_activation_rate'];
	    $hd_mpi_field_success = __("Settings updated", "morphing-portals-integration");
    }
  }

    if(isset($_POST['hd_mpi_api_token'])){
		if($api_token && $_POST['hd_mpi_api_token'] && $api_token !== $_POST['hd_mpi_api_token']){

			global $wpdb;
			$wpdb->query("DELETE FROM " . MORPHING_PORTALS_WP_INTEGRATION_DATABASE_ORIGINS);
			$wpdb->query("DELETE FROM " . MORPHING_PORTALS_WP_INTEGRATION_DATABASE_GOALS);
			$wpdb->query("DELETE FROM " . MORPHING_PORTALS_WP_INTEGRATION_DATABASE_MODULES);
			$wpdb->query("DELETE FROM " . MORPHING_PORTALS_WP_INTEGRATION_DATABASE_SEGMENTATIONS);

			$new_phrases = '';
			$activation_rate = '';
			delete_option('hd_mpi_new_phrases');
			delete_option('hd_mpi_activation_rate');
		}

	    update_option('hd_mpi_api_token' ,sanitize_text_field($_POST['hd_mpi_api_token']));
	    $api_token = $_POST['hd_mpi_api_token'];
	    $hd_mpi_field_success = __("Settings updated", "morphing-portals-integration");
  	}
}

$goal_options = array('Learning Mode' => '0', '5' => '5', '25' => '25', '50' => '50', '75' => '75', '95' => '95', 'Always' => '100');


?>

<style>
.input-add-field {
	width: 20em !important;
}
</style>

<div class="wrap">

	<h2><?php _e("Morphing Portals Settings", "morphing-portals-integration"); ?>
  </h2>

  <?php if( $hd_mpi_field_errors !== ''){ ?>
  	<div class="error">
		<p><?php echo $hd_mpi_field_errors; ?></p>
	</div>
  <?php
}else if($hd_mpi_field_success !== ''){ ?>
  	<div class="updated">
		<p><?php echo $hd_mpi_field_success; ?></p>
	 </div>
  <?php } ?>
  <form method="post" action="" id="hd_mpi_settings_form" name="hd_mpi_settings_form">
	  	<p><a href="<?php echo 'https://'.MORPHING_PORTALS_PORTAL; ?>"> <?php _e('Access here to get an API key and manage your account.', 'morphing-portals-integration'); ?></a></p>
		<table class="form-table">
			<tbody>

				<tr class="form-field">
						<th scope="row"><label for="hd_mpi_api_token"><?php _e("Morphing Portals API Key", "morphing-portals-integration"); ?></label></th>
						<td><input type="input" name="hd_mpi_api_token" id="hd_mpi_api_token"
			    class="input-add-field"
							value="<?php echo $api_token; ?>"/></td>
					</tr>

			    <tr class="form-field">
			      <th scope="row"><label for="hd_mpi_new_phrases"><?php _e("News Phrases", "morphing-portals-integration"); ?></label></th>
			      <td><input type="input" name="hd_mpi_new_phrases" id="hd_mpi_new_phrases"
			    class="input-add-field hd_mpi_free_selectize"
			        value="<?php echo $new_phrases; ?>"/></td>
			    </tr>

			    <tr class="form-field">
			      <th scope="row"><label for="hd_mpi_activation_rate"><?php _e("Activation Rate", "morphing-portals-integration"); ?></label></th>
			      <td><select name="hd_mpi_activation_rate" id = "hd_mpi_activation_rate" class="input-add-field">
            <?php foreach($goal_options as $k=>$v) {
              if(isset($activation_rate)){
                $selected = ($v == $activation_rate) ? " selected='selected'" : "";
                echo "<option value='$v'$selected>$k</option>\n";
              }
              else{
                echo "<option value='$v'>$k</option>\n";
              }
            }?>
						</select></td>
			    </tr>
      		</tbody>
    	</table>
  </br>
  	<input type="hidden" name="hd_mpi_old_api" id="hd_mpi_old_api" value="<?php echo $api_token; ?>">
    <input type="button" name="hd_mpi_button_sync" id="hd_mpi_button_sync" class="button-secundary"
    value="<?php _e("Sync. from Portal", "morphing-portals-integration"); ?>" />
  </br></br>
    <div id='hd_list_of_events'>
  	</div>
  </br>
    <input type="submit" name="submit" class="button-primary" id="hd_mpi_settings_button" name="hd_mpi_settings_button"
    value="<?php _e("Save", "morphing-portals-integration"); ?>" />
  </form>

  </br>

</div>
