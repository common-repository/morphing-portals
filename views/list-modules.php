<?php

if (! defined('ABSPATH')) {
    exit(); // Exit if accessed directly
}

?>

<style>
.copyright {
	font-size: 14px;
	padding-top: 50px;
}

.copyright img {
	vertical-align: middle;
	height: 28px;
}

.wp-list-table .column-cb {
	width: 5%;
}

.wp-list-table .column-field_id {
	width: 5%;
}

.wp-list-table .column-field_name {
	width: 10%;
}

</style>

<div class = "wrap">

  <h2><?php _e('Morphing Portals - Contents', 'morphing-portals-integration'); ?>
  <a class="add-new-h2"
    href="<?php menu_page_url("morphing-portals-integration-add-module") ?>"><?php _e("Add Content", "morphing-portals-integration"); ?></a>
  </h2>
  <form id="events-filter" method="get">
  <input type="hidden" name="page"
      value="<?php echo $_REQUEST['page'] ?>" />

<?php
	
  $hd_mpi_list_table = new mpi_modules_list_table();
  $hd_mpi_list_table->prepare_items();
  $hd_mpi_list_table->display();
?>
  </form>
</div>
