<?php
namespace Sort1API\Components\Models;

use Sort1API\Components\DB;
use Sort1API\Components\Request;
use Sort1API\Components\Config;
use Sort1API\Components\Functions;
use Sort1API\Components\HTTPRequest\MainhostHTTPRequest;


class Details extends Model {
	
	public static function get_details($params) {

		$db = DB::getInstance();
		
		/*
		//test:
		$req = Request::getInstance();
		$req_ip = $req->state("client_ip");
		
		echo $req_ip;
		
		$mh = new MainhostHTTPRequest($req_ip);
		
		print_r($mh);
		die();
		//end test...
		*/
		
		$ret = ["details"=>[]];
		
		switch ($params['type']) {
			case 1:
			//незаконченный ввод:
				
				/////////////////
				//DISABLED
				/////////////////
			
			break;	
			
			case 2:
			//законченный ввод:
			if (!empty($params['searchstr'])) {
					
				$dets = self::get_datail_by_art($params['searchstr']);

				if (count($dets)>0) {
					$is_all_empty = true;
					$min_life = time();
					foreach ($dets as $det) {
						if (!empty($det['brand_id'])) {
							$is_all_empty = false;
							break;
						}		
						if (strtotime($det['last_update']) < $min_life)
							$min_life = strtotime($det['last_update']);
					}
					if (!$is_all_empty)
						$ret['details'] = self::_filter_non_empty($dets, 'brand_id');
					else {
						if ($min_life < (time() - Config::get('api-details-lifetime'))) {
							$go_to_laximo = true;							
						}
					}
					
					
				} else {
					//идем за подсказками:
					$tips = Tooltips::get_tooltip_by_str($params['searchstr']);
					if (count($tips) > 0) {
						//есть подсказки,смотрим пустоту и lifetime:
						$min_life = time();
						$is_all_empty = true;
						foreach ($tips as $tip) {
							if (!empty($tip['response']) && ($tip['response']!= "[]")) {
								$is_all_empty = false;
								break;
							}		
							if (strtotime($tip['sync_date']) < $min_life)
								$min_life = strtotime($tip['sync_date']);
						}
						if (!$is_all_empty)
							$go_to_laximo = true;
						
					}
					if ((count($tips) == 0) || (isset($is_all_empty,$min_life) && $is_all_empty && ($min_life < (time() - Config::get('api-tooltips-lifetime'))))) {
							// нет подсказок ИЛИ
							//пусто и время подошло еще раз сходить:
							//echo "$min_life|";
														
							$tips = Tooltips::update_tooltips(["searchstr" => $params['searchstr']]);
							$is_all_empty2 = true;
							foreach ($tips['response'] as $tip) {
								if (!empty($tip['response']) && ($tip['response']!="[]"))
								{
									$is_all_empty2 = false;
									break;	
								}							
							}
							if (!$is_all_empty2)
								$go_to_laximo = true;
							
					} else {
						//return empty
					}
					
				} // if count(det)>0
				
			
				if ($go_to_laximo) {
					$dets = Crosses::go_to_laximo($params['searchstr']);
					
					if (count($dets) > 0)
						$ret['details'] = self::_filter_non_empty($dets, 'brand_id');
									
				}
				
			} // if empty searchstr	
						
			
			
			
			break;	
			
			case 3:
			//выбрана подсказка:
			
				/////////////////
				//DISABLED
				/////////////////			
			
			
			break;	
			
			
		}
		
		
		
		if (!empty($ret['details'])) {
			//Узнаем бренды:
			$brand_ids = array_unique(array_column($ret['details'], "brand_id"));
			
			$brs = $db->getAll("SELECT brand_id, brand_raw FROM brands WHERE brand_id IN (?a) AND is_main=1", $brand_ids);
			
			$brands = [];
			foreach ($brs as $br) {
				$brands[$br['brand_id']] = explode(";", $br['brand_raw'])[0];				
			}			
			$ret['brands'] = $brands;
		}
		
		
		return $ret;
		
	}
	

//new version (added ABCP support)

	public static function get_details2($params) {

		$db = DB::getInstance();
	
		$ret = ["details"=>[]];
		
		switch ($params['type']) {
			case 1:
			//незаконченный ввод:
				
				/////////////////
				//DISABLED
				/////////////////
			
			break;	
			
			case 2:
			//законченный ввод:
			if (!empty($params['searchstr'])) {
					
				$dets = self::get_datail_by_art($params['searchstr']);

				if (count($dets)>0) {
					$is_all_empty_lax = true;
					$nothing_lax = true;
					
					$is_all_empty_abcp = true;
					$nothing_abcp = true;
					
					$min_life_lax = time();
					$min_life_abcp = time();
					foreach ($dets as $det) {
						if (($det['scan']&1)===1) {  // 1 is for laximo (2^0)
							$nothing_lax = false;
							//All about laximo:
							if (!empty($det['brand_id']))
								$is_all_empty_lax = false;						
							if (strtotime($det['last_update']) < $min_life_lax)
								$min_life_lax = strtotime($det['last_update']);
						}

						if (($det['scan']&2)===2) { // 2 is for abcp (2^1) (newer providers will be:4,8,16,etc (2^2,2^3,2^4,...))
							$nothing_abcp = false;
							//All about laximo:
							if (!empty($det['brand_id']))
								$is_all_empty_abcp = false;						
							if (strtotime($det['last_update']) < $min_life_abcp)
								$min_life_abcp = strtotime($det['last_update']);
						}
						
						
					}
					
					
					if (!$is_all_empty_lax && !$is_all_empty_abcp) {
						//уже ходили и в лаксимо и в абцп:
						$ret['details'] = self::_filter_non_empty($dets, 'brand_id');
						break;
					}
					else {
						// надо хотя бы в одну из сходить
						if ($nothing_lax ||  ($is_all_empty_lax && ($min_life_lax < (time() - Config::get('api-details-lifetime'))))) {
							$go_to_laximo = true;
						}
						if ($nothing_abcp || ($is_all_empty_abcp && ($min_life_abcp < (time() - Config::get('api-details-lifetime'))))) {
							$go_to_abcp = true;
						}
					}
					
					
				} else {
					//еще никуда ни разу не ходили, идем везде, только если есть подсказки
					
					//идем за подсказками:
					$tips = Tooltips::get_tooltip_by_str($params['searchstr']);
					if (count($tips) > 0) {
						//есть подсказки,смотрим пустоту и lifetime:
						$min_life = time();
						$is_all_empty = true;
						foreach ($tips as $tip) {
							if (!empty($tip['response']) && ($tip['response']!= "[]")) {
								$is_all_empty = false;
								break;
							}		
							if (strtotime($tip['sync_date']) < $min_life)
								$min_life = strtotime($tip['sync_date']);
						}
						if (!$is_all_empty) {
							$go_to_laximo = true;
							$go_to_abcp = true;
						}
						
					}
					if ((count($tips) == 0) || (isset($is_all_empty,$min_life) && $is_all_empty && ($min_life < (time() - Config::get('api-tooltips-lifetime'))))) {
							// нет подсказок ИЛИ
							//пусто и время подошло еще раз сходить:
							//echo "$min_life|";
														
							$tips = Tooltips::update_tooltips(["searchstr" => $params['searchstr']]);
							$is_all_empty2 = true;
							foreach ($tips['response'] as $tip) {
								if (!empty($tip['response']) && ($tip['response']!="[]"))
								{
									$is_all_empty2 = false;
									break;	
								}							
							}
							if (!$is_all_empty2) {
								$go_to_laximo = true;
								$go_to_abcp = true;
							}
							
					} else {
						//return empty
					}
					
				} // if count(det)>0
				
								
			
				if (!empty($go_to_laximo)) {
					$dets = Crosses::go_to_laximo($params['searchstr'], false);
				}
				if (!empty($go_to_abcp)) {
					$dets = Crosses::go_to_abcp_brands($params['searchstr'], false);
				}
				
				if (!empty($go_to_laximo) || !empty($go_to_abcp)) {
					$dets = self::get_datail_by_art($params['searchstr']);
				}
				
				if (count($dets) > 0)
					$ret['details'] = self::_filter_non_empty($dets, 'brand_id');
									
				
				
			} // if empty searchstr	
						
			
			
			
			break;	
			
			case 3:
			//выбрана подсказка:
			
				/////////////////
				//DISABLED
				/////////////////			
			
			
			break;	
			
			
		}
		
		
		
		if (!empty($ret['details'])) {
			//Узнаем бренды:
			$brand_ids = array_unique(array_column($ret['details'], "brand_id"));
			
			$brs = $db->getAll("SELECT brand_id, brand_raw FROM brands WHERE brand_id IN (?a) AND is_main=1", $brand_ids);
			
			$brands = [];
			foreach ($brs as $br) {
				$brands[$br['brand_id']] = explode(";", $br['brand_raw'])[0];				
			}			
			$ret['brands'] = $brands;
		}
		
		
		return $ret;
		
	}



	
	public static function get_datail_by_art($art) {
		$db = DB::getInstance();
		
		$dets = $db->getAll("SELECT * FROM details WHERE article=?s", Functions::convert_article($art));
		
		return $dets;
		
	}
	
	
	/**
	* 
	* FOR OLD VERSION OF TOOLTIPS (like in MHs)
	* 
	* @return
	*/
	
	public static function get_details_for_tips_old_ver($art) {

		$db = DB::getInstance();

	    $laximo = array();
	    
	  	$sql = "SELECT DISTINCT article FROM details WHERE article LIKE ?s ORDER BY CHAR_LENGTH(article) LIMIT 10";
	  	
	  	$arts = $db->getCol($sql, Functions::convert_article($art).'%');
	  	
		if (!empty($arts)) {
			$sql2 = "SELECT DISTINCT d.brand_id, d.article, b.brand, b.brand_raw FROM details d
						left join brands b on b.brand_id = d.brand_id
						WHERE b.is_main = 1 and
						article IN (?a)";
			$res2 = $db->getAll($sql2, $arts);
			
			//Достанем главные бренды:			
						
			foreach ($res2 as $arr) {
				$br_tmp = explode(";", $arr['brand_raw'])[0];
				if (!isset($laximo[$arr['article']]) || (isset($laximo[$arr['article']]) && !in_array($br_tmp, $laximo[$arr['article']]))) {
					$laximo[$arr['article']][] = $br_tmp;
				}
				
			}
		}
			
	    
	    return $laximo;	
		
	}
	
	
	public static function get_similar_brand_ids($brands) {
		
		$brs = [];
		
		$brands = (array)$brands;		
		foreach ($brands as $br) {
			$tmp = preg_split("/(\\\\|\/)+/", $br);
			foreach ($tmp as $t)
				$brs[] = Functions::convert_article($t);
		}
				
		$db = DB::getInstance();
				
		$brids = $db->getCol("SELECT DISTINCT brand_id FROM brands WHERE brand IN (?a)", $brs);
		
		return $brids;
		
	}
	
	
	private static function _filter_non_empty($arr, $field) {
		$ret = [];
		
		foreach ($arr as $a) {
			if (!empty($a[$field]))
				$ret[] = $a;		
		}
		
		return $ret;
	}

	public static function save_detail_group($request){
		$db = DB::getInstance();
		if(empty($request->group_name)) return array("status"=>"err","err"=>"наименование группы пустое");
		if(isset($request->group_id) && (int)$request->group_id==0){
			// вставляем новую группу
			$res=$db->query("insert into detail_groups (group_name,in_group,create_date,main_company_id) values(?s,?i,?s,?i)",
							$request->group_name,$request->in_group_id,date("Y-m-d H:i:s"),$_SESSION['main_company']);
			if($db->affectedRows()>0){
				return array("status"=>"ok","msg"=>"");
			}
			else {
				return array("status"=>"err","err"=>"Не удалось добавить группу товаров affected_rows: ".$db->affectedRows()." 
				sql: insert into detail_groups (group_name,in_group,create_date,main_company_id) values(".$request->group_name.",".$request->in_group_id.",".date("Y-m-d H:i:s").",".$_SESSION['main_company'].")");
			}
				
		}
		elseif((int)$request->group_id>0) {
			$res=$db->query("update detail_groups set group_name=?s,in_group=?i,update_date=?s where id=?i and main_company_id=?i",
							$request->group_name,$request->in_group_id,date("Y-m-d H:i:s"),(int)$request->group_id,$_SESSION['main_company']);
			if($db->affectedRows()>0){
				return array("status"=>"ok","msg"=>"");
			}
		}
	}

	public static function get_detail_groups($request){
		$db = DB::getInstance();
		if(!empty($request->in_group) && (int)$request->in_group>0) $in_group=$request->in_group;
		else $in_group=0;
		
		$res=$db->getAll("select * from detail_groups where main_company_id=?i and in_group=?i",$_SESSION['main_company'],$in_group);
		return array("status"=>"ok","msg"=>"","detail_groups"=>$res);
		
	}
	
	public static function delete_detail_group($request){
		$db = DB::getInstance();
		if(empty($request->group_id) || (int)$request->group_id<=0){
			return array("status"=>"err","err"=>"Не указан id группы");
		}
		$res=$db->query("delete from detail_groups where id=?i",$request->group_id);
		return array("status"=>"ok","msg"=>"");
	}	
	
}


?>