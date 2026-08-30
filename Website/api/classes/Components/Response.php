<?php

namespace Sort1API\Components;

use Sort1API\Components\Logger;

class Response {
	
	private $_type = "json";
	private $_code = 200;
	
	private $_output = array();
	
	public function __construct() {
		
	}
	
	
	public function set_type($type) {
		
		if (in_array($type, ['json','xml']))
			$this->_type = $type;
		
	}
	
	public function set_content($content) {
		if (is_array($content))
			$this->_output = $content;		
	}
	
	public function set_http_code($code) {
		if (intval($code) != 0)
			$this->_code = intval($code);		
	}
	
	
	public function options_output() {
	    header("Access-Control-Allow-Method: POST");
	    header("Access-Control-Allow-Headers: *");
	    header("Access-Control-Allow-Origin: *");
	}

	public function output() {
		$out = "";
		
		$method = '_'.$this->_type.'_it';
		if (method_exists($this, $method)) {			
			$out = $this->$method();
		}
				
		if (strtolower($this->_output['status']) <> "ok")
			Logger::client_error();

		if (strtolower($this->_output['status']) == "ok"){
		    $db=DB::getInstance();
			mysqli_commit($db->get_conn());
			//Logger::log("commit\n","mysql_commit");
		}
		
		Logger::debug($this->_output);		
		
		http_response_code($this->_code);
		header("Content-Type: application/".$this->_type);
		
		echo $out;
	}
		
	
	private function _xml_it() {
		// array to xml conversion:
		$return_xml=new \SimpleXMLElement("<root/>");
		$this->_array_to_xml($this->_output, $return_xml);
				
		return $return_xml->asXML();
	}
	
	private function _json_it() {
		$json = json_encode($this->_output);
		
		return $json;
	}	
	
	private function _array_to_xml($array, &$xml_user_info) {
	    foreach($array as $key => $value) {
	        if(is_array($value)) {
	            if(!is_numeric($key)){
	                $subnode = $xml_user_info->addChild("$key");
	                $this->_array_to_xml($value, $subnode);
	            }else{
	                $subnode = $xml_user_info->addChild("item");
	                $this->_array_to_xml($value, $subnode);
	            }
	        }else {
	            $xml_user_info->addChild("$key",htmlspecialchars("$value"));
	        }
	    }
	}
	
}


?>