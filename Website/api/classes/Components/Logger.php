<?php


namespace Sort1API\Components;

use Sort1API\App;
use Sort1API\Components\Request;
use Sort1API\Components\DB;
use Sort1API\Components\Config;

class Logger {
	//private static $LOG_PATH = '/var/log/sort1/';
	
	
	private static function _log($message, $filename_add="") {
		$date = date("Y-m-d H:i:s");
		$req = Request::getInstance();
		
		$path = Config::get("app-log-path");
		
		$pers = $req->state("client_ip");
		$login = $req->login;
		if (!empty($login))
			$pers .= ".".$login;
		
		
		file_put_contents($path."api/pro_".$pers."_".$filename_add.".log", $date." ".$message."\n", FILE_APPEND|LOCK_EX);	
	}
	
	
	
	public static function error($message) {
		self::_log($message, "error");
		//file_put_contents(self::$LOG_PATH."api_v2/error.log", $date." ".$message."\n", FILE_APPEND|LOCK_EX);	
	}
	

	public static function client_error() {
		
		if (!Config::get("app-client_error-log"))
			return;
		
		//$date = date("Y-m-d H:i:s");
		$req = Request::getInstance();
		$db = DB::getInstance();
		$db_stats = $db->getStats();
		
		self::_log((string)$req."\nDatabase statistics:\n".print_r($db_stats,true),"client_error");
		//file_put_contents(self::$LOG_PATH."api_v2/client_error.log", $date." ".(string)$req."\nDatabase statistics:\n".print_r($db_stats,true)."\n", FILE_APPEND|LOCK_EX);
	}

	public static function debug($resp) {
		if (!Config::get("app-debug-all")) 
			return;

		//$date = date("Y-m-d H:i:s");
		$req = Request::getInstance();
		
		$r['status'] = $resp['status'];
		if(isset($resp['err'])) $r['err'] = $resp['err'];
		else $r['err'] = "";
		if (isset($resp['count'])) $r['count'] = $resp['count'];
		
		$db = DB::getInstance();
		$db_stats = $db->getStats();		
		
		self::_log("Request: ".(string)$req."\nResponse(without arr): ".print_r($r,true)."\n".print_r($db_stats,true),"debug");
		// file_put_contents(self::$LOG_PATH."api_v2/debug.log", $date." Request: ".(string)$req."\nResponse(without arr): ".print_r($r,true)."\n".print_r($db_stats,true)."\n", FILE_APPEND|LOCK_EX);
			
	}
	
	public static function log($message, $name = "default") {
		if (!Config::get("app-custom-log"))
			return;	
		
		$date = date("Y-m-d H:i:s");
		$path = Config::get("app-log-path");
		file_put_contents($path."api/pro_".$name.".log", $date." ".$message."\n", FILE_APPEND|LOCK_EX);
		
	}
	
	
}





?>