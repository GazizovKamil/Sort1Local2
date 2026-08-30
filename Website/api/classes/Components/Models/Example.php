<?php

namespace Sort1API\Components\Models;

use Sort1API\Components\DB;

/**
* 	Model Class to work with Orders instatnces (orders table)
*   Class can use inner Database (DB::getInstance())
*   or
* 	external api to receive data (using HTTPRequest class or extened)
* 
*/


class ExampleModel extends Model {
	
	public static function get_examples($params) {
		
		//Receive filter arr
		$filter = (isset($params['filter']))?$params['filter']:array();
		
		// Applying filters:
		
		if (!is_null($filter['offset']))
			$offset = "OFFSET ".(int)$filter['offset'];
		else 
			$offset = "";
			
			
		$db = DB::getInstance();
		
		$filters = "";
		
		if (isset($filter['client']))
			$filters .= $db->parse(" AND client_id=?i",$filter['client']);
		
		
		$sql = "SELECT * FROM example WHERE 1 ".$filters.$offset;
		
		$db= DB::getInstance();
		
		//If errors may return error array:
		if (!isset($filters['is_auth']))
			return self::_error_arr("Доступ к запрашиваемой операции запрещен");
		
		
		$ret = $db->getAll($sql, $placeholders);
		
		$count = $db->num_rows();
		// return :
		
		return [
			"status" => "ok",
			"err" => "",
			"count" => $count,
			"exaples" => $ret,			
			//"orders" => $ords,			
			"time" => date("d.m.Y H:i:s"),		
		];
		
		
		
	}
	
	
	
	
}



?>