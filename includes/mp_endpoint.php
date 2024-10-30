<?php 
	if(isset($_GET['id'])){
		global $wpdb;
		$id = sanitize_text_field($_GET['id']);
		$url_search = "%&id=$id";
		$banner = $wpdb->get_row( "SELECT * FROM " . MORPHING_PORTALS_WP_INTEGRATION_DATABASE_MODULES . " WHERE URL LIKE '$url_search'" );
		if(isset($banner->banner_html) && $banner->banner_html){
			$result = html_entity_decode($banner->banner_html);
			echo wpautop($result);
		}
		else{
			echo 'Malformed URL';
		}
		die();
	}
	else{
		echo 'Malformed URL';
		die();
	}
	
?>