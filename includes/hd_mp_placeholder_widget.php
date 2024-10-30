<?php

class hd_mpi_placeholder_widget extends WP_Widget {

function __construct() {
parent::__construct(
// Base ID of your widget
'hd_mpi_placeholder_widget', 

// Widget name will appear in UI
__('Morphing Portals Placeholder', 'morphing-portals-integration'), 

// Widget description
array( 'description' => __( 'Injects banner as widget.', 'morphing-portals-integration' ), ) 
);
}

// Creating widget front-end
// This is where the action happens
public function widget( $args, $instance ) {
	// PART 1: Extracting the arguments + getting the values
    extract($args, EXTR_SKIP);
    $title = empty($instance['title']) ? ' ' : apply_filters('widget_title', $instance['title']);
    $placeholder_id = empty($instance['placeholder']) ? '' : $instance['placeholder'];
    
    // Before widget code, if any
    echo (isset($before_widget)?$before_widget:'');

    // PART 2: The title and the text output
    if (!empty($title))
      echo $before_title . $title . $after_title;;
    if (!empty($placeholder_id))
      echo do_shortcode("[hd_mp_placeholder id='$placeholder_id']");

    // After widget code, if any  
    echo (isset($after_widget)?$after_widget:'');
}
		
// Widget Backend 
public function form( $instance ) {
 	 
 	 // PART 1: Extract the data from the instance variable
     $instance = wp_parse_args( (array) $instance, array( 'title' => '' ) );
     $title = $instance['title'];
     $placeholder = $instance['placeholder'];   
     
     global $wpdb;
	 $placeholders = $wpdb->get_results( "SELECT * FROM " . MORPHING_PORTALS_WP_INTEGRATION_DATABASE_ORIGINS);
	 $placeholder_array = array();
	 foreach($placeholders as $placehol){
		 $placeholders_array[$placehol->name] = $placehol->id;
	 }

     // PART 2-3: Display the fields
     ?>
     <!-- PART 2: Widget Title field START -->
     <p>
      <label for="<?php echo $this->get_field_id('title'); ?>">Title: 
        <input class="widefat" id="<?php echo $this->get_field_id('title'); ?>" 
               name="<?php echo $this->get_field_name('title'); ?>" type="text" 
               value="<?php echo attribute_escape($title); ?>" />
      </label>
      </p>
      <!-- Widget Title field END -->

     <!-- PART 3: Widget City field START -->
     <p>
      	<select type="text" class='widefat' name="<?php echo $this->get_field_name('placeholder'); ?>" id="<?php echo $this->get_field_id('placeholder'); ?>" class="input-add-field">
            <?php foreach($placeholders_array as $k=>$v) {
              if(isset($placeholder)){
                $selected = ($v == $placeholder) ? " selected='selected'" : "";
                echo "<option value='$v'$selected>$k</option>\n";
              }
              else{
                echo "<option value='$v'>$k</option>\n";
              }
            }?>
		</select>
      </label>
     </p>
     <!-- Widget City field END -->
     <?php }
	
public function update($new_instance, $old_instance) {
    $instance = $old_instance;
    $instance['title'] = $new_instance['title'];
    $instance['placeholder'] = $new_instance['placeholder'];
    return $instance;
  }
}