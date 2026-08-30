<?php

namespace Sort1API\Components\Models;


class Model {	
	
	protected static function _error_arr($name) {		
		return ["status"=>"err", "err"=>$name];
	}
	
}



?>