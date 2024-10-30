<?php

class MP_Module_API {

	  // Class fields
	  private static $instance;
	  private static $table;
	  private $wp_id = 0;
	  private $mp_id = 0;
	  private $name  = '';
	  private $url   = '';
	  private $wp_goal_id   = '';
	  private $mp_goal_id   = '';
	  private $tags  = array();
	  private $segmentations = array();
		private $labels  = array();

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
		if(isset($item['bannerID'])){
			$this->mp_id = sanitize_text_field($item['bannerID']);
		}
		if(isset($item['name'])){
			$this->name = sanitize_text_field($item['name']);
		}
		if(isset($item['sourceUrl'])){
			$this->url = $item['sourceUrl'];
		}
		if(isset($item['URL'])){
			$this->url = $item['URL'];
		}
		if(isset($item['tags'])){
			if(!is_array($item['tags'])){
				$this->tags = sanitize_text_field($item['tags']);
			}
			else{
				$this->tags = array_map( 'sanitize_text_field', $item['tags'] );
			}
		}

		if(isset($item['labels'])){
			if(!is_array($item['labels'])){
				$this->labels = sanitize_text_field($item['labels']);
			}
			else{
				$this->labels = array_map( 'sanitize_text_field', $item['labels'] );
			}
		}

		if(isset($item['contextTags'])){
			if(!is_array($item['contextTags'])){
				$this->labels = sanitize_text_field($item['contextTags']);
			}
			else{
				$this->labels = array_map( 'sanitize_text_field', $item['contextTags'] );
			}
		}

		if(isset($item['goalID'])){
			$this->mp_goal_id = sanitize_text_field($item['goalID']);
			$this->wp_goal_id = $this->getWPGoalID();
		}

		if(isset($item['goal_id'])){
			$this->wp_goal_id = sanitize_text_field($item['goal_id']);
		}

		if(isset($item['exclude_segmentations'])){
			$segmentations = $item['exclude_segmentations'];
			if(!$segmentations){
				$segmentations = array();
			}
			else if(!is_array($segmentations)){
				$segmentations = explode(',', $segmentations);
			}

			$segmentations = array_map( 'sanitize_text_field', $segmentations );

			$this->segmentations = $segmentations;
		}

		if(isset($item['excludeConditions'])){
			$segmentations = $item['excludeConditions'];
			if(!is_array($segmentations)){
				$segmentations = explode(',', $segmentations);
			}

			$this->segmentations = $segmentations;
		}

	    self::$instance = $this;
	    self::$table    = MORPHING_PORTALS_WP_INTEGRATION_DATABASE_MODULES;
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

	  public function getWPGoalID(){
		  global $wpdb;
		  $table = MORPHING_PORTALS_WP_INTEGRATION_DATABASE_GOALS;
		  $id = $this->mp_goal_id;
		  $result = $wpdb->get_row( "SELECT * FROM $table WHERE mp_id = '$id'" );
		  if($result){
			  return $result->id;
		  }
		  else{
			  return false;
		  }
	  }

	  public function getMPGoalID(){
		  global $wpdb;
		  $table = MORPHING_PORTALS_WP_INTEGRATION_DATABASE_GOALS;
		  $id = $this->wp_goal_id;
		  $result = $wpdb->get_row( "SELECT * FROM $table WHERE id = '$id'" );
		  if($result){
			  $this->mp_goal_id = $result->mp_id;
		  }
		  else{
		  }
	  }

	  public function addToDB(){
		  global $wpdb;
		  $table = self::$table;
		  $tags  = $this->tags;

		  if(is_array($tags)){
			  $tags = implode(',', $tags);
		  }
			$labels  = $this->labels;

		 if(is_array($labels)){
			 $labels = implode(',', $labels);
		 }

		  $segmentations  = $this->segmentations;

		  if(is_array($segmentations)){
			  $segmentations = implode(',', $segmentations);
		  }

		  try{
			  $result = $wpdb->insert(
				$table,
				array(
					'mp_id'     			=> $this->mp_id,
					'name'      			=> $this->name,
					'url'                   => $this->url,
					'goal_id'               => $this->wp_goal_id,
					'tags'                  => $tags,
					'exclude_segmentations' => $segmentations,
					'labels' 								=> $labels
				),
				array(
					'%s',
					'%s',
					'%s',
					'%d',
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
	    			 'url'               => $this->url,
	    			 'wp_goal_id'        => $this->wp_goal_id,
	    			 'mp_goal_id'        => $this->mp_goal_id,
	    			 'tags'              => $this->tags,
						 'labels'              => $this->labels
	    );
	  }

	  public function to_create(){

		$tags = $this->tags;
		if(!is_array($tags)){
			$tags = explode(',', $tags);
		}
		$labels = $this->labels;
		if(!is_array($labels)){
			$labels = explode(',', $labels);
		}

	    return array('name'              => $this->name,
	    			 'sourceUrl'         => $this->url,
	    			 'tags'              => $tags,
	    			 'goalID'            => $this->mp_goal_id,
	    			 'excludeConditions' => $this->segmentations,
						 'contextTags'       => $labels
	    );
	  }
}
