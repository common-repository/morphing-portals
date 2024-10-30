<?php 
	$api_token = get_option('hd_mpi_api_token');
	if($api_token){
		echo '<script>
				jQuery("body").attr("ng-app", "ImpPortalAngularApp");			
				var mp_api_token = "'.$api_token.'";
			  </script>';
		
		echo '<main ng-view=""></main>';
	}
	