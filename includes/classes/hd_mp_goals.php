<?php

class MP_Goal_API {

	  // Class fields
	  private static $instance;
	  private static $table;
	  private $wp_id = 0;
	  private $mp_id = 0;
	  private $name  = '';
	  private $url   = '';

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
		if(isset($item['goalID'])){
			$this->mp_id = sanitize_text_field($item['goalID']);
		}
		if(isset($item['name'])){
			$this->name = sanitize_text_field($item['name']);
		}
		if(isset($item['URL'])){
			$this->url = $item['URL'];
		}
		if(isset($item['targetUrl'])){
			$this->url = sanitize_text_field($item['targetUrl']);
		}
	    self::$instance = $this;
	    self::$table    = MORPHING_PORTALS_WP_INTEGRATION_DATABASE_GOALS;
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
		  try{
			  $result = $wpdb->insert(
				$table,
				array(
					'mp_id' => $this->mp_id,
					'name'  => $this->name,
					'url'   => $this->url
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
	    			 'url'               => $this->url
	    );
	  }

	  public function to_create(){
	    return array('name'              => $this->name,
	    			 'targetUrl'         => $this->url
	    );
	  }
}
