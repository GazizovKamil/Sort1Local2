<?php

namespace Sort1API\Components\Models;

use Sort1API\Components\DB;
use Sort1API\Components\LocalCross;
use Sort1API\Components\LocalCrossBlack;
use Sort1API\Components\Models\LocalDetails;
use Sort1API\Components\Models\Search;
use Sort1API\Components\Config;

/**
* 	Model Class to work with Orders instatnces (orders table)
*   Class can use inner Database (DB::getInstance())
*   or
* 	external api to receive data (using HTTPRequest class or extened)
* 
*/


class LocalCrosses extends Model {

        public static function check_roles($role){
                $main_user=new User((int)$_SESSION['user_id']);
                if ($main_user->roles<=$role) return $role;
                else return $main_user->roles;
            }

		public static function save_local_crosses($request){
			$local_crosses=$request->local_crosses;
			$get_detail_ids=array(
				"action"=>"get_details",
				"brands_aliases"=>true,
				"offline"=>true,
				"detail"=>array()
	    	);
			$oem_details=array();
			$cross_details=array();
			$i=0;
			foreach($local_crosses as $key=>$val){
				$oem_details[$key]['a']=$val['oem_article'];
				$oem_details[$key]['k']=$key+1;
				$oem_details[$key]['b']=$val['oem_brand'];
				$cross_details[$key]['a']=$val['cross_article'];
				$cross_details[$key]['k']=$key+1;
				$cross_details[$key]['b']=$val['cross_brand'];
			}
			$get_detail_ids['detail']=$oem_details;
					$send=json_encode($get_detail_ids);
					//echo $send;
					//$json_data=json_encode($get_brand_ids);
					//echo $send;
					$context = stream_context_create([
						'http' => [
							'method' => 'POST',
							'header' => "Content-type: application/json\r\n" .
								"Accept: application/json\r\n" .
								"Connection: close\r\n" .
								"Content-length: " . strlen($send) . "\r\n",
							'protocol_version' => 1.1,
							'content' => $send
						],
						'ssl' => [
							'verify_peer' => false,
							'verify_peer_name' => false
						]
					]);
					$url="http://".Config::get("library_ip")."/api/v2/index.php";
					$res=file_get_contents($url,false,$context);
				    //$res=post_curl("","http://".Config::get("library_ip")."/api/v2/index.php",array("Content-type: application/json"),$send);
				    //echo print_r($res['body'],true);
					//$r=json_decode($res['body'],true);
					$r=json_decode($res,true);
				    //file_put_contents("/var/log/shop/api/get_brands.log",print_r($get_detail_ids,true).print_r($r,true),FILE_APPEND);
				    foreach($r['details'] as $r_key=>$r_val){
						//file_put_contents("/var/log/shop/api/cross_to_base.log","get from libr: \n".print_r($r_val,true)."\n",FILE_APPEND);
							if((int)$r_val['errcode']==0 || (int)$r_val['errcode']==2){
								if(isset($r_val['data'])){
									$local_crosses[$r_key-1]['oem_detail_id']=$r_val['data'][0]['detail_id'];
									//$request->local_crosses['brand_id']=$r_val['data'][0]['brand_id'];
									//if($request->local_crosses[$r_key-1]['brand']=="Unknown") {
									//	$request->local_crosses[$r_key-1]['brand']=$r['brands_aliases'][$request->local_crosses[$r_key-1]['brand_id']]['main']['brand'];
									//}
								}
								else {
									$send=array("article"=>$local_crosses[$r_key-1]['oem_article'],"brand"=>$local_crosses[$r_key-1]['oem_brand']);
									$localdet=LocalDetails::get_local_details($send);
									$local_crosses[$r_key-1]['oem_detail_id']=$localdet['detail_id'];
								}
							}
							else {
								$send=array("article"=>$local_crosses[$r_key-1]['oem_article'],"brand"=>$local_crosses[$r_key-1]['oem_brand']);
								$localdet=LocalDetails::get_local_details($send);
								$local_crosses[$r_key-1]['oem_detail_id']=$localdet['detail_id'];
							}
		    		}
				$get_detail_ids['detail']=$cross_details;
					$send=json_encode($get_detail_ids);
					//echo $send;
					//$json_data=json_encode($get_brand_ids);
					//echo $send;
					$context = stream_context_create([
						'http' => [
							'method' => 'POST',
							'header' => "Content-type: application/json\r\n" .
								"Accept: application/json\r\n" .
								"Connection: close\r\n" .
								"Content-length: " . strlen($send) . "\r\n",
							'protocol_version' => 1.1,
							'content' => $send
						],
						'ssl' => [
							'verify_peer' => false,
							'verify_peer_name' => false
						]
					]);
					$url="http://".Config::get("library_ip")."/api/v2/index.php";
					$res=file_get_contents($url,false,$context);
				    //$res=post_curl("","http://".Config::get("library_ip")."/api/v2/index.php",array("Content-type: application/json"),$send);
				    //echo print_r($res['body'],true);
					//$r=json_decode($res['body'],true);
					$r=json_decode($res,true);
				    //file_put_contents("/var/log/shop/api/get_brands.log",print_r($get_detail_ids,true).print_r($r,true),FILE_APPEND);
				    foreach($r['details'] as $r_key=>$r_val){
						//file_put_contents("/var/log/shop/api/cross_to_base.log","get from libr: \n".print_r($r_val,true)."\n",FILE_APPEND);
							if((int)$r_val['errcode']==0 || (int)$r_val['errcode']==2){
								if(isset($r_val['data'])){
									$local_crosses[$r_key-1]['cross_detail_id']=$r_val['data'][0]['detail_id'];
									//$request->local_crosses['brand_id']=$r_val['data'][0]['brand_id'];
									//if($request->local_crosses[$r_key-1]['brand']=="Unknown") {
									//	$request->local_crosses[$r_key-1]['brand']=$r['brands_aliases'][$request->local_crosses[$r_key-1]['brand_id']]['main']['brand'];
									//}
								}
								else {
									$send=array("article"=>$local_crosses[$r_key-1]['cross_article'],"brand"=>$local_crosses[$r_key-1]['cross_brand']);
									$localdet=LocalDetails::get_local_details($send);
									$local_crosses[$r_key-1]['cross_detail_id']=$localdet['detail_id'];
								}
							}
							else {
								$send=array("article"=>$local_crosses[$r_key-1]['cross_article'],"brand"=>$local_crosses[$r_key-1]['cross_brand']);
								$localdet=LocalDetails::get_local_details($send);
								$local_crosses[$r_key-1]['cross_detail_id']=$localdet['detail_id'];
								//echo "local_crosses=".print_r($local_crosses[$r_key-1],true)."\n";
								//echo "localdet=".print_r($localdet,true)."\n";
							}
		    		}
				$sql="insert ignore into local_cross (main_company_id,user_id,oem_detail_id,oem_article,oem_brand,cross_detail_id,cross_article,cross_brand,cross_name,create_date) values ";
				$i=0;
				$db = DB::getInstance();
				foreach($local_crosses as $key=>$val){
					if($i>0) $sql.=",";
					$sql.=$db->parse("(?i,?i,?i,?s,?s,?i,?s,?s,?s,?s)",$_SESSION['main_company'],$_SESSION['user_id'],$val['oem_detail_id'],$val['oem_article'],$val['oem_brand'],$val['cross_detail_id'],$val['cross_article'],$val['cross_brand'],$val['cross_name'],date("Y-m-d H:i:s"));
					$i++;
				}
				
				if($db->query($sql)){
					return array("status"=>"ok","msg"=>"Данные успешно импортированы");
				}
				else {
					return array("status"=>"err","err"=>"err","msg"=>"","obrabotano"=>$local_crosses);
				}
		}

        public static function save_local_cross($request) {
            $db = DB::getInstance(); 
			if(empty($request->cross_article)) return self::_error_arr("Не указан артикульный номер кросса");
			if(empty($request->cross_brand)) return self::_error_arr("Не указан бренд кросса");
			if(empty($request->oem_article)) return self::_error_arr("Не указан артикульный номер оригинала");
			if(empty($request->oem_brand)) return self::_error_arr("Не указан бренд оригинала");
            if (isset($request->local_cross_id) && (int)$request->local_cross_id>0) $local_cross_id=(int)$request->local_cross_id;
			
            if(isset($local_cross_id) && $local_cross_id>0) {
				if(isset($request->local_cross_type) && $request->local_cross_type=="black"){
					$local_cross=new LocalCrossBlack($local_cross_id);
				}
				else {
                	$local_cross=new LocalCross($local_cross_id);
				}
            }
			else {
				if(isset($request->local_cross_type) && $request->local_cross_type=="black"){
					$local_cross=new LocalCrossBlack();
				}
				else {
					$local_cross=new LocalCross();
				}
			}
			if(isset($request->oem_detail_id) && (int)$request->oem_detail_id!=0){
				$local_cross->oem_detail_id=(int)$request->oem_detail_id;
			}
			else {
				$send=array("article"=>$request->oem_article,"brand"=>$request->oem_brand);
				$details=Search::get_brands_online((object)$send);
				$oem_brand_ids=Search::get_brand_id((object)$send);
				$oem_brand_id=$oem_brand_ids['brands']['brand_ids'][LocalDetails::convert_article($request->oem_brand)][0];
				if(count((array)$details['brands'])==1){
					$local_cross->oem_detail_id=$details['brands'][0]['detail_id'];
				}
				else {
					if(count((array)$details['brands'])>1){
						foreach($details['brands'] as $detkey=>$detval){
							if((int)$detval['brand_id']==(int)$oem_brand_id){
								$local_cross->oem_detail_id=$detval['detail_id'];
								break;	
							}
						}
					}
					else {
						$localdet=LocalDetails::get_local_details($send);
						$local_cross->oem_detail_id=$localdet['detail_id'];
					}
				}
			}
			if(isset($request->cross_detail_id) && (int)$request->cross_detail_id!=0){
				$local_cross->cross_detail_id=(int)$request->cross_detail_id;
			}
			else {
				$send=array("article"=>$request->cross_article,"brand"=>$request->cross_brand);
				$details=Search::get_brands_online((object)$send);
				$debug['details_from_get_brands_online']=$details;
				$cross_details1=$details;
				$cross_brand_ids=Search::get_brand_id((object)$send);
				foreach($cross_brand_ids['brands']['brand_ids'] as $cb_key=>$cb_val){
					$cross_brand_ids['brands']['brand_ids'][LocalDetails::convert_article($cb_key)]=$cross_brand_ids['brands']['brand_ids'][$cb_key];
				}
				$debug['cross_from_get_brand_id']=$cross_brand_ids;
				$cross_brand_id=$cross_brand_ids['brands']['brand_ids'][LocalDetails::convert_article($request->cross_brand)][0];
				$debug['converted_cross_brand']=LocalDetails::convert_article($request->cross_brand);
				$debug['cross_brand_id']=$cross_brand_id;
				if(count((array)$details['brands'])==1){
					$local_cross->cross_detail_id=$details['brands'][0]['detail_id'];
				}
				else {
					if(count((array)$details['brands'])>1){
						foreach($details['brands'] as $detkey=>$detval){
							if((int)$detval['brand_id']==(int)$cross_brand_id){
								$local_cross->cross_detail_id=$detval['detail_id'];
								break;	
							}
						}
					}
					else {
						$localdet=LocalDetails::get_local_details($send);
						$local_cross->cross_detail_id=$localdet['detail_id'];
					}
				}
			}
			$local_cross->oem_article=LocalDetails::convert_article($request->oem_article);
			$local_cross->oem_brand=LocalDetails::convert_article($request->oem_brand);
			$local_cross->cross_article=LocalDetails::convert_article($request->cross_article);
			$local_cross->cross_brand=LocalDetails::convert_article($request->cross_brand);
			$local_cross->cross_name=$request->cross_name;
			$is_in=$db->getAll("select id from local_cross".((isset($request->local_cross_type) && $request->local_cross_type=="black")?"_black":"")." where main_company_id=?i and oem_detail_id=?i and cross_detail_id=?i",$_SESSION['main_company'],$local_cross->oem_detail_id,$local_cross->cross_detail_id);
			if(count((array)$is_in)>0) return array("status"=>"err",
			"err"=>"Этот кросс уже существует",
			"debug"=>$debug,
			"local_cross_is_in"=>$is_in,
			"oem_detail_id"=>$local_cross->oem_detail_id,
			"cross_detail_id"=>$local_cross->cross_detail_id);
            $err=$local_cross->save();
            switch($err) {
            case 10: $status="err"; $msg="Данные не изменились\n"; break;
            case 1: if (isset($request->sklad_id) && (int)$request->sklad_id>0){
                            $status="ok"; $msg="Данные успешно изменены";
                        }
                        else {
                            $status="ok"; $msg="Новый кросс добавлен";
                        }
                break;
            default: $status="err"; $msg="error: ".$err."\n";
            }
            if ($status=="err") return self::_error_arr($msg);
            else return array("status"=>$status,"msg"=>$msg,"err"=>"","debug"=>$debug);//,"cross_details"=>$cross_details1,"cross_brand_ids"=>$cross_brand_ids);
        }


	public static function get_local_cross($request) {
	    if ((int)$_SESSION['roles']!=6 && (int)$_SESSION['roles']!=1 && (int)$_SESSION['roles']!=2) {
		//return self::_error_arr("У Вас нет прав для данного действия");
	    }
	    $db = DB::getInstance();
	    $sql="select * from local_cross".((isset($request->local_cross_type) && $request->local_cross_type=="black")?"_black":"")." where main_company_id=?i and id=?i";
	    $res=$db->getAll($sql,$_SESSION['main_company'],(int)$request->local_cross_id);
	    if (is_array($res) && count($res)>0){
		$ret['status']="ok";
		$ret['err']="";
		$ret['local_cross_id']=(int)$request->local_cross_id;
		//$ret['sklad_name']=$db->getOne("select name from sklad where id=?i",(int)$request->sklad_id);
		$ret['local_crosses']=$res;
		$ret['msg']="";
	    }
	    if ($ret['status']=="err") return self::_error_arr($ret['err']);
	    else return $ret;
	}

	public static function get_local_crosses($request) {
	    if ((int)$_SESSION['roles']!=6 && (int)$_SESSION['roles']!=1 && (int)$_SESSION['roles']!=2) {
		//return self::_error_arr("У Вас нет прав для данного действия");
	    }
	    $db = DB::getInstance();
	    $sql_count="select count(id) from local_cross".((isset($request->local_cross_type) && $request->local_cross_type=="black")?"_black":"")." where main_company_id=?i";
	    if (!empty($request->search) && $request->search!="undefined") $sql_count.=" and (oem_article like ?s or cross_name like ?s or cross_article like ?s)";
	    if (!empty($request->search) && $request->search!="undefined") $details_count=$db->getOne($sql_count,$_SESSION['main_company'],'%'.trim($request->search).'%','%'.trim($request->search).'%','%'.trim($request->search).'%');
	    else $details_count=$db->getOne($sql_count,$_SESSION['main_company']);
	    $sql="select * from local_cross".((isset($request->local_cross_type) && $request->local_cross_type=="black")?"_black":"")." where main_company_id=?i ";
	    if (!empty($request->search) && $request->search!="undefined") $sql.=" and (oem_article like ?s or cross_name like ?s or cross_article like ?s)";
	    $sql.=" order by cross_name";
	    if(isset($request->page_size)) $page_size=$request->page_size;
	    else $page_size=20;
	    $pages=ceil($details_count/$page_size);
	    if(isset($request->page)) {
			$sql.=" limit ".$page_size*($request->page-1).",".$page_size;
	    }
	    else 
			$sql.=" limit 0,".$page_size;
	    if (!empty($request->search) && $request->search!="undefined") {
			$res=$db->getAll($sql,$_SESSION['main_company'],'%'.trim($request->search).'%','%'.trim($request->search).'%','%'.trim($request->search).'%');
			$ret['search']=$request->search;
	    }
	    else 
			$res=$db->getAll($sql,$_SESSION['main_company']);
	    if (is_array($res) && count($res)>0){
			$ret['status']="ok";
			$ret['err']="";
			$ret['local_crosses']=$res;
			$ret['local_crosses_pages']=$pages;
			$ret['local_cross_count']=(int)$details_count;
			if (isset($request->page)) $ret['selected_page']=$request->page;
			$ret['msg']="";
	    }
	    else {
			$ret['status']="ok";
			$ret['msg']="";
			$ret['err']="";
			$ret['local_crosses']=[];
			$ret['local_crosses_pages']=1;
			$ret['details_count']=0;
	    }
	    if ($ret['status']=="err") return self::_error_arr($ret['err']);
	    else return $ret;
	}

	public static function delete_local_cross($request) {
	    $fields="";
	    $db = DB::getInstance();
	    if ((int)$_SESSION['roles']!=6 && (int)$_SESSION['roles']!=1 && (int)$_SESSION['roles']!=2) {
			//return self::_error_arr("У Вас нет прав для удаления");
	    }
	    if (isset($request->local_cross_id)) {$local_cross_id=(int)$request->local_cross_id;}
		else {
			return self::_error_arr("Не указан номер");
		}
		$res2=$db->query("delete from local_cross".((isset($request->local_cross_type) && $request->local_cross_type=="black")?"_black":"")." where id=?i and main_company_id=?i",$local_cross_id,$_SESSION['main_company']);
		//echo "delete from sklad where id=".$sklad_id." and company_id in (select company_id from user_companys where main_company_id=0 and user_id=".$_SESSION['user_id'].")";
		if ($res2){
		    $ret['status']="ok";
		    $ret['msg']="Кросс успешно удален";
		}
		else {
		    $ret['status']="err";
		    $ret['err']="не удалось удалить кросс";
		}
	    if ($ret['status']=="err") return self::_error_arr($ret['err']);
	    else return array("status"=>$ret['status'],"msg"=>$ret['msg'],"err"=>"");
	}

}
?>