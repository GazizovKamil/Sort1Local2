<?php

namespace Sort1API\Components\Models\Brand_plugins;

use Sort1API\Components\HTTPRequest\MainhostHTTPRequest;


abstract class Plugin_brands {
	
	public $response; //что ответил сайт
	public $brands; // готовый результат-массив
	public $plname; //имя плагина
	
	public $site_problem = false;
	
	abstract protected function __construct($art , $plname);
	abstract protected function get_curl_handler($force_new = false);
	abstract protected function callback($data);
	
	
	protected function get_cookie_from_mh($plid, $hwid, $ip, $profile_id) {
		
		$mh_req = new MainhostHTTPRequest($ip);
		
		$body = ["action" => "get_cookies",
				 "plid" => $plid,
				 "hwid" => $hwid,
		];
		
		$mh_req->set_body(json_encode($body));
		
		$result = $mh_req->make();
						
		$json = json_decode($result['body'], true);
		
		if (!empty($json['cookies'])) {
			file_put_contents("/var/www/sort1/cookies/".$plid."_".$profile_id."_".md5($hwid), $json['cookies']);
			return $json['cookies'];
		}
		else
			return false;
		
	}
	
	
	
}


?>