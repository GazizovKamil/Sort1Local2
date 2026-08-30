<?php

namespace Sort1API\Components\Controllers;


class BaseController {
	
	public static function action_exists($name) {
		if (method_exists(static::class, "action_".$name))
			return true;
			
		return false;
	}	
	
	
	protected static function _error($msg) {
		return ["status"=>"err","err"=> $msg];		
	}
	
	
	
	
}



?>