<?php

namespace Sort1API\Components\Models\Brand_plugins;

use Sort1API\Components\Request;
use Sort1API\Components\DB;
use Sort1API\Components\Functions;
use Sort1API\Components\HTTPRequest\MainhostHTTPRequest;


class Autokontinent_brands extends Plugin_brands {
	
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
			//$is_launch = call_user_func([$this, "get_cookie"],$force_new);
			$is_launch = $this->get_cookie($force_new);
		else 		
			$is_launch = true;
			
		if ($is_launch) {
			$url = "http://autokontinent.ru/search.php";
			$body = "------WebKitFormBoundaryoOKCT3Ikd8OMSyis
Content-Disposition: form-data; name=\"act\"

searchByText
------WebKitFormBoundaryoOKCT3Ikd8OMSyis
Content-Disposition: form-data; name=\"text\"

".$this->art."
------WebKitFormBoundaryoOKCT3Ikd8OMSyis--";
		
			$headers = array("Content-Type: multipart/form-data; boundary=----WebKitFormBoundaryoOKCT3Ikd8OMSyis",
							 "Content-Length: ".strlen($body),
							 "X-Ajax-Request: 1"				 
							);			
			
			
			$this->ch = curl_init();
			curl_setopt($this->ch, CURLOPT_URL, $url);
			curl_setopt($this->ch, CURLOPT_HEADER, true);
			curl_setopt($this->ch, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($this->ch, CURLOPT_COOKIE,$is_launch);
			curl_setopt($this->ch, CURLOPT_HTTPHEADER, $headers);
			curl_setopt($this->ch, CURLOPT_CONNECTTIMEOUT, 3);
			curl_setopt($this->ch, CURLOPT_TIMEOUT, 3);
			curl_setopt($this->ch, CURLOPT_SSL_VERIFYPEER, false);
			curl_setopt($this->ch, CURLOPT_SSL_VERIFYHOST, false);
			curl_setopt($this->ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
			curl_setopt($this->ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/45.0.2454.101 Safari/537.36');
			curl_setopt($this->ch, CURLOPT_POST, 1);
			curl_setopt($this->ch, CURLOPT_POSTFIELDS, $body);
			
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
	

	public function get_cookie($force_new = false) { 
		$plid = 61;
	
		$request = Request::getInstance();
		
		$hwid = $request->hwid;
		$profile_id = $request->profile_id;
		
		$ip = $request->state("client_ip");
		
				
		if (empty($hwid) || empty($profile_id)) 
			return false; 
	
		//Смотрим файл с куками:
		
		if (file_exists("/var/www/sort1/cookies/".$plid."_".$profile_id."_".md5($hwid)) && !$force_new) {
			$cooks = file_get_contents("/var/www/sort1/cookies/".$plid."_".$profile_id."_".md5($hwid));
			$this->has_second_chance = true;
		} else {
			$cooks = $this->get_cookie_from_mh($plid, $hwid, $ip, $profile_id);
		}
	
		return $cooks;
	
	} 
	
	
	
	public function callback($data) {
		$header = substr($data,0,curl_getinfo($this->ch,CURLINFO_HEADER_SIZE));
	    $body = substr($data,curl_getinfo($this->ch,CURLINFO_HEADER_SIZE));
	    
	    $info = curl_getinfo($this->ch);
		
		$arr = array();
		
		if ((int)$info['http_code'] == 200) {
			$json = json_decode($body);
					
			foreach ($json->data as $ar) {
				
				$arr[Functions::convert_article($ar->part_code)][] = $ar->brand_name;
				
				
	    //		$br = array();
		//		$br['brand'] = $ar->brand;
		//		$br['article'] = $ar->number;
		//		$br['is_exact'] = true;	
		//		$arr[] = $br;
			}
		} elseif ((int)$info['http_code'] == 302 ) {
			
				return ['sort1_action'=>'need_to_reload','site_error' => true];			
			
		} else {
			return ['site_error' => true];
		}
		
		
		return json_encode($arr);		
		
	}
	
	
	
	
	
	
	
}



?>