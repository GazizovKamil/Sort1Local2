<?php

namespace Sort1API\Components;

use Sort1API\App;

class Config {
	
	private static $_config = array();	
	private static $_is_initiated = false;
	
	
	
	private function __construct() {
		
	}
	
	private static function _init() {
		
		if (file_exists(App::$ROOT.'config.php'))			
			self::$_config = (include App::$ROOT.'config.php');

		//if (file_exists(App::$ROOT.'config.ini'))			
			//self::$_config = parse_ini_file(App::$ROOT.'config.ini',false,INI_SCANNER_RAW);
	
		//print_r(self::$_config);
		self::$_is_initiated=true;
	}
	
	
	public static function get($name) {
		if (!self::$_is_initiated) self::_init();
		
		if (isset(self::$_config[$name]))
			return self::$_config[$name];
		else
			return null;	
		
		
	}
		
	public static function get_section($name, $trunc=false) {
		if (!self::$_is_initiated) self::_init();
		
		$ret = array();
		
		foreach (self::$_config as $k => $v) {
			if (strpos($k, $name) === 0) {
				$k1 = ($trunc)?substr($k, strlen($name)):$k;
				$ret[$k1] = $v;		
			}
		}
		
		return $ret;
		
		
	}
	
	
	
	
}



?>