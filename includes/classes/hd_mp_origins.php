<?php

class MP_Origin_API {
	
	  // Class fields
	  private static $instance;
	  private static $table;
	  private $wp_id = 0;
	  private $mp_id = 0;
	  private $name  = '';
	  private $tags  = array();
	
	/**
	 * Class Constructor
	 *
	 * @param parameters array
	 */
	  public function __construct($item) {	
		global $wpdb;
				
		if(isset($item['id']) && is_numeric($item['id'])){
			$this->wp_id = $item['id'];
		}
		if(isset($item['originID'])){
			$this->mp_id = sanitize_text_field($item['originID']);
		}	
		if(isset($item['name'])){
			$this->name = sanitize_text_field($item['name']);
		}	
		if(isset($item['tags'])){
			if(!is_array($item['tags'])){
				$this->tags = sanitize_text_field($item['tags']);
			}
			else{
				$this->tags = array_map( 'sanitize_text_field', $item['tags'] );
			}
		}
	    self::$instance = $this;
	    self::$table    = MORPHING_PORTALS_WP_INTEGRATION_DATABASE_ORIGINS;
	  }
	  
	  public function checkIfExists(){
		  global $wpdb;
		  $table = self::$table;
		  $id = $this->mp_id;
		  $result = $wpdb->get_row( "SELECT * FROM $table WHERE mp_id = '$id'" );
		  if($result){
			  return true;
		  }
		  else{
			  return false;
		  }
	  }
	  
	  public function addToDB(){
		  global $wpdb;
		  $table = self::$table;
		  $tags  = $this->tags;

		  if(is_array($tags)){
			  $tags = implode(',', $tags);
		  }
		  try{
			  $result = $wpdb->insert( 
				$table, 
				array( 
					'mp_id' => $this->mp_id, 
					'name'  => $this->name,
					'tags'  => $tags
				), 
				array( 
					'%s', 
					'%s',
					'%s'
				) 
			  );
			  
			  return true;
			  
		  } catch (Exception $e){
			  return false;
		  }
	  }
	  
	  public function search_mp_id(){
		  global $wpdb;
		  $table = self::$table;
		  $id    = $this->wp_id;
		  $result = $wpdb->get_row( "SELECT * FROM $table WHERE id = '$id'" );
		  if($result){
			  $this->mp_id = $result->mp_id;
		  }
		  else{
			  $this->mp_id = 0;
		  }
	  }
	  
	  public function get_mp_id(){
		  return $this->mp_id;
	  }
	  
	  // Transform object into array
	  public function to_array(){
	    return array('wp_id'             => (int) $this->wp_id,
	    			 'mp_id'             => $this->mp_id,
	    			 'name'              => $this->name,
	    			 'tags'              => $this->tags
	    );	                 
	  }
	  
	  public function to_create(){
		$tags = $this->tags;
		if(!is_array($tags)){
			$tags = explode(',', $tags);
		}
		
	    return array('name'              => $this->name,
	    			 'tags'              => $tags
	    );	                  
	  }
}