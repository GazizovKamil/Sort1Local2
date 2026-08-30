<?php

namespace Sort1API\Components\Models;

use Sort1API\Components\DB;
use Sort1API\Components\Config;
use Sort1API\Components\Logger;
use Sort1API\Components\Notify;
use Sort1API\Components\Functions;


class Abcp extends Model {
	
	public static function perform_abcp_brands($str) {
		$db = DB::getInstance();
		
		$start = microtime(true);
		
		$data = self::abcp_get_details($str);
		//data [brand=> , article=>, name=>]
			
		Logger::log("New ABCP API search/brands request, OEM: $str, time: ".(microtime(true)-$start), "abcp_request");
	
		
		if (empty($data)) {
			$sql_good="insert into details(article,article_raw,brand_id,name,scan,scan_crosses) 
				values (?s, ?s, 0, '', 1) ON DUPLICATE KEY UPDATE scan=scan|2";
				
			$db->query($sql_good, Functions::convert_article($str) , $str);
		} else {
			foreach ($data as $detail) {
				$brand_id = self::get_abcp_brand_id($detail['brand']);
				
				if (empty($brand_id))
					continue;
				
				$sql_good1 = "INSERT INTO details(article, article_raw, brand_id, name, scan, scan_crosses) VALUES (?s, ?s, ?i, ?s, 2, 0) ON DUPLICATE KEY UPDATE id=LAST_INSERT_ID(id), scan=scan | 2, article_raw=?s, last_update=NOW()";
				
				if (!empty((string)$detail['name'])) {
					$sql_good1 .= ", name=?s";
					$res_good1 = $db->query($sql_good1, Functions::convert_article((string)$detail['article']),(string)$detail['article'], $brand_id,(string)$detail['name'],(string)$detail['article'],(string)$detail['name']);
				} else {
					$res_good1 = $db->query($sql_good1, Functions::convert_article((string)$detail['article']),(string)$detail['article'], $brand_id,(string)$detail['name'],(string)$detail['article']);
				}
				
				$detail_id = $db->insertId();
				
			}
		}
		
	}
	
	public static function get_abcp_brand_id($brand) {
		$db = DB::getInstance();
		
		$sql = "SELECT brand_id FROM brands WHERE brand=?s";
		$brid = $db->getOne($sql, Functions::convert_article($brand));
		
		if ($brid > 0)
			return $brid;
		
		
		/// TODO: добавление нового бренда abcp ///
		//не нашли, надо добавить:
		//это очень плохо, отправим письмо:
		
		Notify::mail("Library: Warning!","ABCP brand not found in DB: $brand ");
		return false;		
				
		
	}
	
	public static function abcp_get_details($art) {
		
		if (empty(Functions::convert_article($art)))
			return self::_error_arr("Пустой артикул");
		
		$json = json_decode(self::_abcp_request("search/brands",["number"=>Functions::convert_article($art)]), true);
		
		$ret = [];

		foreach ($json as $v) {
			$ret[] = ["brand" => $v['brand'], 
					  "article" => $v['numberFix'],
					  "name" => $v['description'],			
			];			
		}	
		
		
		return $ret;
		
		
	}
	
	public static function abcp_get_crosses($params) {
	
		if (empty($params['brand']) || empty($params['number']))
			return self::_error_arr("Bad params");
		
		$json = json_decode(self::_abcp_request("articles/info", ["brand"=> $params['brand'], "number"=>$params['number'], "format"=>"bnphic", "locale"=>"ru_RU" ]), true);
		
		$ret = [];
		$ret['crosses'] = [];
		$ret['props'] = [];
		$ret['images'] = [];
		
		foreach ($json['crosses'] as $c) {
			$ret['crosses'][] = ["brand" => $c['brand'], "article" => $c['numberFix']];			
		}
		foreach ($json['properties'] as $pn => $pv) {
			$ret['props'][] = ["name" => $pn, "value" => $pv];			
		}
		if (!empty($json['images_count'])) {
			$ims = array_column($json['images'], "name");
			$ret['images'] = implode("|", $ims);			
		}
		
		return $ret;
		
	}
	
	
	private static function _abcp_request($method, $params) {
		
		//allowed {$method}s:
		if (!in_array($method, ['articles/info', 'search/brands'])) {
			return false;				
		}
		
		$opts = Config::get_section("abcp-", true);
		$url = $opts['host'].$method."/?userlogin=".urlencode($opts['login'])."&userpsw=".md5($opts['password']);
		$url .= "&".http_build_query($params);
		
		$ctx = stream_context_create(array(
	        'http' => array(
	            'timeout' => 2,
	            'header' => "Accept: application/json\r\n",
	            )
	        )
    	);
		
		//die($url);
		
		$resp = file_get_contents($url, false, $ctx);
		
		return $resp;
		
	}	
	
	
}




?>