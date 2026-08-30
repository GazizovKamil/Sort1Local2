<?php

namespace Sort1API\Components\Models;

use Sort1API\Components\DB;
use Sort1API\Components\Functions;
use Sort1API\Components\Notify;
use Sort1API\Components\Config;
use Sort1API\Components\HTTPRequest\MainhostHTTPRequest;

//use Sort1API\Components\Models\Brand_plugins;

class Tooltips extends Model {
	
	public static function get_tooltip($str) {
		
		$tips = self::_get_tips_from_base($str);
		
		
		
		
		
		
		
	}
	
	
	
	public static function get_tooltip_by_str($str, $is_exact = true, $limit = false) {
		
		if ((int)$limit >0)
			$sql_limit = " LIMIT ".intval($limit);
		
		$db = DB::getInstance();
		$sql = "SELECT DISTINCT article FROM articles WHERE article LIKE ?s ORDER BY CHAR_LENGTH(article) $sql_limit";
		
		if (!$is_exact)
			$str .= '%';
		
		$tps = $db->getCol($sql, $str);
		
		if (count($tps) >0) {
			$sql = "SELECT * FROM articles WHERE article IN (?a)";
			$ret = $db->getAll($sql, $tps);		
			
		} else 
			return [];	
		
		
		return $ret;
		
	}
	
	
	public static function update_tooltips($params) {

		if (empty($params['searchstr']))
			return self::_error_arr("Пустая строка");


		$article = Functions::convert_article($params['searchstr']);
				
		$resp = self::_get_brands_multi($article, false, true);
		
	
		return [
					"status" => "ok",
					"err" => "",
					"response" => $resp,	
					"time" => date("d.m.Y H:i:s"),			
				];
	}
	
	
	/**
	* 
	*	Function for old version tooltips:
	* 
	* @return 
	*/
	public static function get_tooltips_old_ver($params) {
		$art = Functions::convert_article($params['searchstr']);
		
		//повторяет все что было на MHs:
		
		$plugins = Config::get("plugins-for-tips");	
		foreach ($plugins as $k=>$v) 
			$plugins[$k] = [];
		
		
		self::_get_tips_from_base_4old($art, $plugins);
			
		$wl = [];			
			
		//Смотрим в какие плагины надо сделать запрос:
		foreach ($plugins as $k => $v) {
			if (empty($v)|| empty($v[$art]) || empty($v[$art]['is_exact'])) {
				$wl[] = $k;				
			} 
		}
		
		
		//Делаем курлы:
		if (!empty($wl)) {
			$resp = self::_get_brands_multi($art, $wl);
		}
		
		//соединяем данные из базы с курлами:
		$plugins = array_merge($plugins, $resp);	
		
		//приводим к одному виду:		
		foreach ($plugins as $k => $v) {
			foreach ($v as $art1 => $val1) {
				if (isset($val1['response'])) $plugins[$k][$art1] = $val1['response']; 	
			}
		}
		
		//берем и добавляем из нашей базы:
		$plugins['laximo'] = Details::get_details_for_tips_old_ver($art);
		
		
		
		// состаляем результат как требует MH:
		$return = array();
		
		foreach ($plugins as $plugin) {
			foreach ($plugin as $article => $brand_arr) {
				foreach ($brand_arr as $brand) {
					if ((isset($return[$article]) && !in_array($brand, $return[$article]))|| !isset($return[$article]))
						$return[$article][] = $brand; 
				}					
			}
		}
		
		
		return $return;
	}
	
	
	
	
	
	private static function _get_tips_from_base($str) {
		return [];		
	}
	
	private static function _get_brands_multi($art, $wl = false, $write_if_empty = false) {
		
		$plugins = Config::get("plugins-for-tips");
		
		if ($wl) {
			foreach ($plugins as $k=>$v) {
				if (!in_array($k, $wl))
					unset($plugins[$k]);			
			}
		}
		
		
		
		$pls = [];
		
		$i=0;
		//определяем куда можем пойти:
		foreach ($plugins as $plname => $plclass) {
			//1) существует класс:
			if(class_exists("Sort1API\\Components\\Models\\Brand_plugins\\".$plclass)) {
				
				$clname = "Sort1API\\Components\\Models\\Brand_plugins\\$plclass";				
				$pls[$i] = new $clname($art, $plname);
				$pls[$i]->get_curl_handler();
				
				if (!$pls[$i]->ch)
					unset($pls[$i]);
				else				
					$i++;
				
			}
		}
		
		//print_r($pls);
		
		//выполняем multi_curl
		if (!empty($pls)) {
			$mh = curl_multi_init();
			
			//if (!$mh) echo "curl multi init error";
			
			foreach($pls as $pl) {
				curl_multi_add_handle($mh, $pl->ch);
			}
			
			$running = NULL;
					
	        do {
	        //  usleep(10000);
	            curl_multi_exec($mh,$running);
	        } while($running > 0);		
			
			$second_ids = [];
			
			foreach ($pls as $key => $pl) {
				$pls[$key]->response = curl_multi_getcontent($pl->ch);
				if (method_exists(get_class($pl), 'callback')) {
					$pls[$key]->brands = call_user_func([$pl, 'callback'], $pls[$key]->response);
					
					if (!empty($pls[$key]->brands['site_error'])) {
						$pls[$key]->site_problem = true;						
						unset($pls[$key]->brands['site_error']);
					}
					
					if (isset($pls[$key]->brands['sort1_action']) && ($pls[$key]->brands['sort1_action'] == 'need_to_reload') && $pls[$key]->has_second_chance)
						$second_ids[] = $key;
					if (isset($pls[$key]->brands['sort1_action']))
						unset($pls[$key]->brands['sort1_action']);					
				}
			}	
			
			foreach ($pls as $pl) {
				curl_multi_remove_handle($mh, $pl->ch);
			}	
			
			curl_multi_close($mh);
			
			
			//2nd Attemp:
			
			if (count($second_ids)>0) {
				
				$mh = curl_multi_init();
												
				foreach ($second_ids as $id) {
					//сменим куки: 					
					unset($pls[$id]->ch);					
					$pls[$id]->get_curl_handler(true);
					curl_multi_add_handle($mh, $pls[$id]->ch);					
					
				}
				
				$running = NULL;
				
				do {
		        //  usleep(10000);
		            curl_multi_exec($mh,$running);
		        } while($running > 0);	
				
				foreach ($second_ids as $id) {
					$pls[$id]->response = curl_multi_getcontent($pls[$id]->ch);
					$pls[$id]->brands = call_user_func([$pls[$id], 'callback'], $pls[$id]->response);
					if (isset($pls[$id]->brands['sort1_action']))
						unset($pls[$id]->brands['sort1_action']);				
					curl_multi_remove_handle($mh, $pls[$id]->ch);
					
					if (!empty($pls[$key]->brands['site_error'])) {
						$pls[$key]->site_problem = true;						
						unset($pls[$key]->brands['site_error']);
					}
				}

				curl_multi_close($mh);
			}
			
			
			
		}	
		
		$plugins = [];	
		
		foreach ($pls as $pl) {
			if (!empty($pl->brands) || ($write_if_empty && !$pl->site_problem)) {
			
				self::_write_brands_to_base($art, json_decode($pl->brands,true), $pl->plname);			
				
				$plugins[$pl->plname] = json_decode($pl->brands, true);

			}
		}	
		
		return $plugins;
		/*
		return [
					"status" => "ok",
					"err" => "",
					"response" => $plugins,
					"time" => date("d.m.Y H:i:s"),		
		];
		*/
	}
	
	
	private static function _write_brands_to_base($art, $json, $plugin) {

		$db = DB::getInstance();
		
		$values = array();
		
		//$arr = json_decode($json);
		foreach ($json as $k => $v) {
			$values[] = $db->parse("(?s,?s,?s,".(($art==$k)?1:0).")" ,$k,json_encode($v), $plugin);
		}
		
		if (empty($values)) {
			//нужно записать пустоту:
			$values[] = $db->parse("(?s,'',?s, 1)", $art, $plugin);
		}
		
		if (!empty($values)) {
			$sql = "INSERT INTO articles(article,response,plugin,is_exact) VALUES ".implode(",",$values).
				   "ON DUPLICATE KEY UPDATE 
				   response = IF(is_exact=1, response, VALUES(response)),
				   is_exact = IF(is_exact=1, is_exact, VALUES(is_exact)),
				   sync_date=NOW()
				   ";
			$res = $db->query($sql);
		}
				
	}
	
	
	private static function _get_tips_from_base_4old ($art, &$plugins) {
		
		$db = DB::getInstance();
				
		$sql = "SELECT DISTINCT article FROM articles WHERE article LIKE ?s ORDER BY CHAR_LENGTH(article) LIMIT 10";
		$res = $db->getCol($sql, $art."%");
		
		/*
		$arts = array();
		foreach ($res as $ar)
			$arts[]="'".$ar['article']."'";
		*/
		
		if (!empty($arts)) {
			$sql = "SELECT * FROM articles WHERE article IN (?a)"; //(".implode(",",$arts).")";
			$res2 = $db->getAll($sql, $res);
			while ($arr = mysqli_fetch_assoc($res)) {
				if (isset($plugins[$arr["plugin"]])) {
					$plugins[$arr["plugin"]][$arr["article"]]["response"] = json_decode($arr["response"], true);
					$plugins[$arr["plugin"]][$arr["article"]]["is_exact"] = $arr['is_exact'];
				}
			}
		}
	}
	
	
}



?>