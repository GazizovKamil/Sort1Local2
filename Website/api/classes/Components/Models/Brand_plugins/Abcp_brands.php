<?php

namespace Sort1API\Components\Models\Brand_plugins;

use Sort1API\Components\Request;
use Sort1API\Components\DB;
use Sort1API\Components\Functions;
use Sort1API\Components\HTTPRequest\MainhostHTTPRequest;


class Abcp_brands extends Plugin_brands {
	
	public $ch = null;
	
	public $art;
	
	public $has_second_chance = false;
	
	public function __construct($art, $plname) {
		
		$this->art = $art;
		$this->plname = $plname;
	}
	
	
	
	public function get_curl_handler($force_new = false) {
			
		//return curl_descriptor or null/false
		if (method_exists(self::class, "get_cookie"))
			$is_launch = call_user_func([self::class, "get_cookie"], $force_new);
		else 		
			$is_launch = true;
			
		if ($is_launch) {
			$url = "http://admin.nodacdn.net/ajax/modules/search/get.search.tips.php?term=".urlencode($this->art)."&is4mycar=0&isMy=0&_=".(int)(microtime(true)*1000);
			
			$this->ch = curl_init();
			curl_setopt($this->ch, CURLOPT_URL, $url);
			curl_setopt($this->ch, CURLOPT_HEADER, true);
			curl_setopt($this->ch, CURLOPT_RETURNTRANSFER, true);		
			curl_setopt($this->ch, CURLOPT_CONNECTTIMEOUT, 3);
			curl_setopt($this->ch, CURLOPT_TIMEOUT, 3);
			curl_setopt($this->ch, CURLOPT_SSL_VERIFYPEER, false);
			curl_setopt($this->ch, CURLOPT_SSL_VERIFYHOST, false);
			curl_setopt($this->ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
			curl_setopt($this->ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/45.0.2454.101 Safari/537.36');
			
			
			$db = DB::getInstance();
			
			$sql = "SELECT * FROM proxy ORDER BY RAND() LIMIT 1";
			$pr = $db->getAll($sql);
						
			if (count($pr) > 0 ) {
			    curl_setopt($this->ch, CURLOPT_PROXY, $pr[0]['proxy']);
			    if ($pr[0]['login']!="") curl_setopt($this->ch, CURLOPT_PROXYUSERPWD, $pr[0]['login'].":".$pr[0]['pass']);
			}			
			
			
			
			
		}			
		
		return true;
		
	}
	
	//disabled for abcp:
	//public function get_cookie() { } 
	
	
	
	public function callback($data) {
				
	    $body = substr($data,curl_getinfo($this->ch,CURLINFO_HEADER_SIZE));
	    
	    $info = curl_getinfo($this->ch);
		
		$arr = array();
		
		if ((int)$info['http_code'] == 200) {
			$json = json_decode($body);
			if (!is_null($json)) {
				foreach ($json as $ar) {
					$arr[Functions::convert_article($ar->number)][] = $ar->brand;
				}
			}
		} 
		
		if (((int)$info['http_code'] != 200) || (is_null($json))) {
			//ошибка сайта:
			return ['site_error' => true];
		}
		
		return json_encode($arr);	
	
	}
	
	
	
	
	
	
	
}



?>