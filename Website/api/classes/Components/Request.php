<?php

namespace Sort1API\Components;

//use Sort1API\App;

class Request {
	
	//Reqeust state data, inc. client IP, method, headers, etc...
	private static $_state = array();
	
	//Request type - same output is needed
	private static $_type = array();
	
	// Unserialized Request body
	private static $_request_arr = array();
	
	
	private static $_instance = NULL;
	
	
	private function __construct() {
		if(isset($_SERVER['HTTP_CLIENT_IP'])){
			$tmp=explode(',',$_SERVER['HTTP_CLIENT_IP']);
			define('__IP__',trim(array_pop($tmp)));
		} elseif(isset($_SERVER['HTTP_X_FORWARDED_FOR'])){
			$tmp=explode(',',$_SERVER['HTTP_X_FORWARDED_FOR']);
			define('__IP__',trim(array_pop($tmp)));
		} else {
			define('__IP__',$_SERVER['REMOTE_ADDR']);
		}
		
		self::$_state = array(
		'method' => $_SERVER['REQUEST_METHOD'],
		'content_type' => ((isset($_SERVER['CONTENT_TYPE']))?$_SERVER['CONTENT_TYPE']:false),
		'raw_input' => '',
		// 'client_ip' => ((isset($_SERVER['REMOTE_ADDR']))?$_SERVER['REMOTE_ADDR']:false),
		'client_ip' => __IP__,
		'client_user_agent' => ((isset($_SERVER['HTTP_USER_AGENT']))?$_SERVER['HTTP_USER_AGENT']:''),
		'request-uri' => $_SERVER['REQUEST_URI'],
		);
		
		$input = file_get_contents('php://input');
		
		self::$_state['raw_input'] = $input;
		
		if ($input != '') {
			
				
			if (extension_loaded('SimpleXML') && (self::$_state['content_type']=='application/xml')) {
				
				self::$_type = 'xml';
				
				if(defined('LIBXML_PARSEHUGE')){
					$tmp=simplexml_load_string($input,'\SimpleXMLElement',LIBXML_NOERROR | LIBXML_NOWARNING | LIBXML_ERR_NONE | LIBXML_PARSEHUGE);
				} else {
					$tmp=simplexml_load_string($input,'\SimpleXMLElement',LIBXML_NOERROR | LIBXML_NOWARNING | LIBXML_ERR_NONE);
				}	
				
				if ($tmp) {
					self::$_request_arr = json_decode(json_encode($tmp),true);
				}
				
			} elseif (self::$_state['content_type'] == 'application/json') {				
				self::$_request_arr = json_decode($input,true);				
				self::$_type = 'json';
			} 
			else {
				//по-умолчанию считаем, что json:
				self::$_request_arr = json_decode($input,true);
				self::$_type = 'json';
			}
		}
		elseif(preg_match('/multipart\/form-data/',self::$_state['content_type'])){
				self::$_request_arr['action'] = $_POST['action'];
				self::$_type = 'multipart';
		}
		
		//print_r(self::$_state);
				
		
	}
	
	private function __clone() { }
	private function __wakeup() { }
	
	
	public static function getInstance() {
		if (null === self::$_instance) {
			self::$_instance = new self;			
		}	
		
		return static::$_instance;	
		
	}
		
	
	public function __get($name) {
		if (isset(self::$_request_arr[$name])) {
			return self::$_request_arr[$name];
		} else {
			return null;			
		}
		
	}
	
	public function __isset($name) 
	{
    	    return isset(self::$_request_arr[$name]);
	}
		
	public function state($name) {
		if (isset(self::$_state[$name])) {
			return self::$_state[$name];
		} else {
			return null;			
		}
		
	}
	
	public function get_type() {
		return self::$_type;		
	}
	
	public function __toString() {
		$a = print_r([
						"state" => self::$_state,
						"type" => self::$_type,
						"request" => self::$_request_arr,
					],true);
		return $a;
	}
	
	
}




?>