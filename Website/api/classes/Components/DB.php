<?php

namespace Sort1API\Components;


class DB {
	protected static $_instance = NULL;
	private static $current_host = "mysql";
	
	protected function __construct($opt = array()) {
		//self::$_instance = new SafeMySQL($opt);				
	}
	
	private function __clone() { }
	private function __wakeup() { }
	
	
	final public static function getInstance($host="mysql") {
		if (null !== static::$_instance && $host == self::$current_host) {
			return static::$_instance;			
		}
		self::$current_host = $host; 
		//static::$_instance = new static(Config::get_section('mysql-', true));
		static::$_instance = new SafeMySQL(Config::get_section($host.'-', true));
		mysqli_autocommit(static::$_instance->get_conn(),FALSE);
		//mysqli_query(static::$_instance->get_conn(),"SET time_zone = 'Asia/Chita'");
		return static::$_instance; 
	}
	
	
	
	
	
	
	
	
	
}




?>