<?php

namespace Sort1API\Components\Models;

use Sort1API\Components\DB;
use Sort1API\Components\Functions;


class Crosses extends Model {
	
	public static function get_crosses($params) {

		//settings:
		$field_aliases = [
			"detail_id" => "did",
			"cross_article" => "ca",
			"cross_brand_id" => "cbrid",
		];

		//$db = DB::getInstance();
		
		$add_fields = $params['add_fields'];
		$rep = [];
		$detail_ids = [];
		
		//В приоритете $details:
		
		
		if (isset($params['detail'])) {
			$d = $params['detail'];
			
			
			
			if (is_int($d))
				$mode = 1;
			elseif (Functions::is_array_of_int($d))
				$mode = 2;
			elseif (Functions::is_array_of_object($d))
				$mode = 3;
			
			if (!isset($mode))
				return self::_error_arr("Неверный формат параметра detail");
			
			
			if (in_array($mode,[1,2]))
				$detail_ids = $d;
			
			if ($mode === 3) {
				
				$detail_ids_table = [];
				
				// array of objects:
				foreach ($d as $det) {					
					if (!isset($det['k']) || empty($det['a']))
						continue;					
					
					$tmp = self::_get_detail_ids_by_art_br($det);
					
					$detail_ids_table[$det['k']] = $tmp;
					$detail_ids = array_merge($detail_ids, $tmp);
					
				}
			}
		}
		else {
		
				if (isset($params['detail_id'])) {
					$detail_ids = $params['detail_id'];
				} else {
					
					$detail_ids = self::_get_detail_ids_by_art_br(["a"=> $params['searchstr'],"b"=> $params['brands']]);
					

			/*
					//определяем всевозможные detail_ids:
					
					if (!empty($params['brands'])) {
						$brand_ids = Details::get_similar_brand_ids($params['brands']);
						
					}
										
					// Получаем detail_ids
					$details = Details::get_datail_by_art($params['searchstr']);
					
					if (empty($details))
						Laximo::perform_laximo(Functions::convert_article($params['searchstr']));
					
					$details = Details::get_datail_by_art($params['searchstr']);
					
					
					if (!empty($brand_ids)) {
						//если есть бренды фильтруем детали по ним:
										
						$details_filtered = [];
						
						foreach ($details as $k => $v) {
							if (in_array($v['brand_id'], $brand_ids))
								$details_filtered[] = $v;
						}
						
						if (empty($details_filtered))
							$details_filtered = $details;				
					} else {
						
						$details_filtered = $details;
						
					}
					
					
					//выбираем id-шники деталей в массив:
					
					$detail_ids = array_column($details_filtered, "id");
					
			*/
					
				}
		}
		
		if (!isset($mode)) {
			//old support:			
				
			$crs = self::get_crosses_by_id($detail_ids, $add_fields);
			

			
			$brs = [];
			
			if (!empty($params['include_aliases']) && (count((array)$crs)>0) ) {
				//Нужно вернуть алиасы брендов
				
				$brids = array_unique(array_column($crs, ($field_aliases["cross_brand_id"]??"cross_brand_id")));
				
				$db = DB::getInstance();
			
				if (count((array)$brids)>0)
					$brs = $db->getAll("SELECT * FROM brands WHERE brand_id IN (?a)", $brids);
						
			}
			
			
			
			$ret = ["status" => "ok",
					"err" => "",
					"crosses" => $crs,				
					"count" => count((array)$crs),
					"brands" => $brs,
				];	
			
			return $ret;
		
		} else {
			//detail multi-type support:	
			/** resopnse:
			* 
			*  "crosses" => [
			*			"unique_key1": [
			*					"detail_id" : [
			*						{ list of crosses }
			*					]
			*				]
			*		],
			* 
			*	"brands" => $brs
			*/
			
			$crs = self::get_crosses_by_id($detail_ids, $add_fields);
			
			//Бренды для всех кроссов:
			$brs = [];
			
			if (!empty($params['include_aliases']) && (count((array)$crs)>0) ) {
				//Нужно вернуть алиасы брендов
				
				$brids = array_unique(array_column($crs, ($field_aliases["cross_brand_id"]??"cross_brand_id")));
				
				$db = DB::getInstance();
			
				if (count((array)$brids)>0)
					$brs = $db->getAll("SELECT * FROM brands WHERE brand_id IN (?a)", $brids);
						
			}	
			
			//приводим кроссы в порядок:
			$tbl = [];
			foreach ($crs as $cr) {
				$tbl[$cr['did']][] = $cr;				
			}
			
			$result = [];
			
			foreach ($detail_ids_table as $k => $arr) {
				$result[$k] = [];
				foreach ($arr as $did) {
					$result[$k][$did] = [];
					if (isset($tbl[$did]))
						$result[$k][$did][] = $tbl[$did];					
				}
			}
			
			$ret = ["status" => "ok",
					"err" => "",
					"crosses" => $result,					
					"brands" => $brs,
					"date" => date("Y-m-d H:i:s"),
				];	
			
			return $ret;
			
		}
	}
	
	
	public static function get_crosses_by_id($detail_ids, $add_fields = null) {
		
		$db = DB::getInstance();
		
		$add_f = "";
		if (!empty($add_fields)) {
			$allowed = ["id","cross_brand","cross_name","client_id","author_id","disabled"];
			$add2 = $db->filterArray(array_flip($add_fields), $allowed);
			if (!empty($add2))
				$add_f = " ,`".implode("`,`",array_keys($add2))."` ";			
		}
		
		
		if (empty($detail_ids))
			return [];
		
		$detail_ids = (array)$detail_ids;
		

		
		$sql = "SELECT detail_id as did, cross_article as ca, cross_brand_id as cbrid $add_f FROM crosses WHERE detail_id IN (?a) AND disabled=0";
		
		$crs = $db->getAll($sql, $detail_ids);
				
		return $crs;		
	}
	
	
	public static function go_to_laximo($searchstr, $return=true) {
		$art = Functions::convert_article($searchstr);
		
		Laximo::perform_laximo($art);
		
		if (!$return)
			return true;
			
		$details = Details::get_datail_by_art($art);
		
		return $details;		
	}
	
	public static function go_to_abcp_brands($searchstr, $return=true) {
		$art = Functions::convert_article($searchstr);
		
		Abcp::perform_abcp_brands($art);
		
		if (!$return)
			return true;		
		
		$details = Details::get_datail_by_art($art);
		
		return $details;		
	}	
		
	private static function _get_detail_ids_by_art_br($params) {
		
		$detail_ids = [];
	
		//определяем всевозможные detail_ids:
		
		if (!empty($params['b'])) {
			$brand_ids = Details::get_similar_brand_ids($params['b']);			
		}
							
		// Получаем detail_ids
		$details = Details::get_datail_by_art($params['a']);
		
		if (empty($details))
			Laximo::perform_laximo(Functions::convert_article($params['a']));
		
		$details = Details::get_datail_by_art($params['a']);
		
		
		if (!empty($brand_ids)) {
			//если есть бренды фильтруем детали по ним:
							
			$details_filtered = [];
			
			foreach ($details as $k => $v) {
				if (in_array($v['brand_id'], $brand_ids))
					$details_filtered[] = $v;
			}
			
			if (empty($details_filtered))
				$details_filtered = $details;				
		} else {
			
			$details_filtered = $details;
			
		}
		
		
		//выбираем id-шники деталей в массив:
		
		$detail_ids = array_column($details_filtered, "id");	
		
		return $detail_ids;
	}
	
	
	
	
}



?>