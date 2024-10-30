<?php

class MP_API_COMM {

	  // Class fields
	  private static $instance;
	  private $url = 0;
	  private $curl = null;
	  private $token = '';

	/**
	 * Class Constructor
	 *
	 * @param parameters array
	 */
	  public function __construct($url, $token) {
		$this->url      = $url;
		$this->token    = $token;
		$this->curl     = curl_init();
	    self::$instance = $this;
	  }

	  public function get_all(){

        curl_setopt_array($this->curl, array(
            CURLOPT_RETURNTRANSFER => 1,
            CURLOPT_URL => $this->url,
            CURLOPT_USERAGENT => 'Codular Sample cURL Request'
        ));

        curl_setopt($this->curl,CURLOPT_HTTPHEADER,array('Authorization: Bearer ' . $this->token));

        $result = json_decode(curl_exec($this->curl), TRUE);

        return $result;
	  }

	  public function add($fields, $field_type){

        curl_setopt_array($this->curl, array(
            CURLOPT_RETURNTRANSFER => 1,
            CURLOPT_URL => $this->url,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($fields)
        ));

        curl_setopt($this->curl,CURLOPT_HTTPHEADER,array('Authorization: Bearer ' . $this->token, "Content-Type: application/json"));

        $result = json_decode(curl_exec($this->curl), TRUE);

        if($result && isset($result[$field_type])){
	        return $result[$field_type];
        }
        else{
        	return false;
        }
	  }

	  public function addModule($fields){

        curl_setopt_array($this->curl, array(
            CURLOPT_RETURNTRANSFER => 1,
            CURLOPT_URL => $this->url,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => array("uploaded_file" => "undefined", "body" => json_encode($fields))
        ));

        //curl_setopt($this->curl, CURLOPT_HTTPHEADER, array('Content-Type: multipart/form-data'));
        curl_setopt($this->curl,CURLOPT_HTTPHEADER,array('Authorization: Bearer ' . $this->token, 'Content-Type: multipart/form-data'));

        $result = json_decode(curl_exec($this->curl), TRUE);

        $info = curl_getinfo($this->curl);
        $field_type = 'bannerID';

		if($result && isset($result[$field_type])){
	        return $result[$field_type];
        }
		else{
        	return false;
        }
	  }

	  public function updateModule($fields){

			curl_setopt_array($this->curl, array(
					CURLOPT_RETURNTRANSFER => 1,
					CURLOPT_URL => $this->url,
					CURLOPT_POST => true,
	 				CURLOPT_CUSTOMREQUEST =>"PUT",
					CURLOPT_POSTFIELDS => array("uploaded_file" => "undefined", "body" => json_encode($fields))
			));

			//curl_setopt($this->curl, CURLOPT_HTTPHEADER, array('Content-Type: multipart/form-data'));
			curl_setopt($this->curl,CURLOPT_HTTPHEADER,array('Authorization: Bearer ' . $this->token, 'Content-Type: multipart/form-data'));

			$result = json_decode(curl_exec($this->curl), TRUE);

			$info = curl_getinfo($this->curl);

			if(isset($info['http_code']) &&  $info['http_code'] == 204){
				return true;
			}
			else{
	        	return false;
	        }
	  }

	  public function update($fields, $field_type){

		$data_json = json_encode($fields);

		curl_setopt($this->curl, CURLOPT_URL, $this->url);
		curl_setopt($this->curl, CURLOPT_CUSTOMREQUEST, 'PUT');
		curl_setopt($this->curl, CURLOPT_POSTFIELDS,$data_json);
		curl_setopt($this->curl, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($this->curl, CURLOPT_HTTPHEADER,array('Authorization: Bearer ' . $this->token, 'Content-Type: application/json','Content-Length: ' . strlen($data_json)));

        $result = json_decode(curl_exec($this->curl), TRUE);

        $info = curl_getinfo($this->curl);

		if(isset($info['http_code']) &&  $info['http_code'] == 204){
			return true;
		}
		else{
			return false;
		}

	  }

	  public function deleteEntry($field_type){

		curl_setopt($this->curl, CURLOPT_URL, $this->url);
		curl_setopt($this->curl, CURLOPT_CUSTOMREQUEST, 'DELETE');
		curl_setopt($this->curl, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($this->curl, CURLOPT_HTTPHEADER,array('Authorization: Bearer ' . $this->token));

        $result = json_decode(curl_exec($this->curl), TRUE);

		$info = curl_getinfo($this->curl);

		if(isset($info['http_code']) &&  $info['http_code'] == 204){
			return true;
		}
		else{
			return false;
		}
	  }

	  public function close(){
		  curl_close($this->curl);
	  }
}
