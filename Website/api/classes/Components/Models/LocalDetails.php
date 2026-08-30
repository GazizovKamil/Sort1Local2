<?php

namespace Sort1API\Components\Models;

use Sort1API\Components\DB;
use Sort1API\Components\Config;
use Sort1API\Components\Functions;

/**
* 	Model Class to work with Orders instatnces (orders table)
*   Class can use inner Database (DB::getInstance())
*   or
* 	external api to receive data (using HTTPRequest class or extened)
*
*/


class LocalDetails extends Model {

	public static function convert_article($art){
		return Functions::convert_article($art);
	    //$new_art=mb_strtoupper(str_replace(array("[","-","+","=","/","\\","'","\"","]"," ",".","#","$","%","^","&","*","(",")","\.","\t","\n"),"",$art));
	    //return $new_art;
	}

	public static function get_local_details($detail){
	    $db = DB::getInstance();
		//include "/var/www/html1/include/lib.php";
		if($detail['brand']=="") $detail['brand']="Unknown";
	    //$brand_id_arr=$db->getRow("select id,brand_id from local_brands where brand=?s",self::convert_article($detail['brand']));
	    //if($brand_id_arr['brand_id']==0) $brand_id=-$brand_id_arr['id'];
		//else $brand_id=$brand_id_arr['brand_id'];
		$brand_id=self::get_brand_id($detail['brand'],$db);
		//file_put_contents("/var/log/shop/api/get_local_details.log","brand_id=$brand_id detail=".print_r($detail,true)."\n",FILE_APPEND);
	    $art_id=$db->getOne("select id from local_details where article=?s and brand_id=?i",self::convert_article($detail['article']),$brand_id);
	    if ($art_id) { $detail["detail_id"]=-$art_id; $detail["brand_id"]=$brand_id; }
	    else {
				if ($brand_id!=0) {
				    $sql="insert ignore into local_details set article=?s,article_row=?s,brand_id=?s,create_date=?s,update_date=?s,name=?s";
				    $id=$db->query($sql,self::convert_article($detail['article']),$detail['article'],$brand_id,date("Y-m-d H:i:s"),date("Y-m-d H:i:s"),$detail['name']);
				    //file_put_contents("/var/log/shop/api/local_details.log","$sql,self::convert_article(".$detail['article']."),".$detail['article'].",$brand_id,".date("Y-m-d H:i:s").",".date("Y-m-d H:i:s").",".$detail['name']."\n",FILE_APPEND);
				    $detail['detail_id']=-$db->insertId();
				    $detail['brand_id']=$brand_id;
				}
				else {
				   
				}
		}
		//file_put_contents("/var/log/shop/api/get_local_details.log","return ".print_r($detail,true)."\n",FILE_APPEND);
	    return $detail;
	}

	public static function get_local_details_from_object($detail){
	    $db = DB::getInstance();
		//include "/var/www/html1/include/lib.php";
		if($detail->brand=="") $detail->brand="Unknown";
	    //$brand_id_arr=$db->getRow("select id,brand_id from local_brands where brand=?s",self::convert_article($detail->brand));
	    //if($brand_id_arr['brand_id']==0) $brand_id=-$brand_id_arr['id'];
		//else $brand_id=$brand_id_arr['brand_id'];
		$brand_id=self::get_brand_id($detail->brand,$db);
	    $art_id=$db->getOne("select id from local_details where article=?s and brand_id=?i",self::convert_article($detail->article),$brand_id);
	    if ((int)$art_id) { $detail->detail_id=-$art_id; $detail->brand_id=$brand_id; }
	    else {
			if ($brand_id!=0) {
				$sql="insert ignore into local_details set article=?s,article_row=?s,brand_id=?s,create_date=?s,update_date=?s,name=?s,ean13=?s";
				$id=$db->query($sql,self::convert_article($detail->article),$detail->article,$brand_id,date("Y-m-d H:i:s"),date("Y-m-d H:i:s"),$detail->name,$detail->ean13);
				//file_put_contents("/var/log/shop/api/local_details.log","$sql,self::convert_article(".$detail['article']."),".$detail['article'].",$brand_id,".date("Y-m-d H:i:s").",".date("Y-m-d H:i:s").",".$detail['name']."\n",FILE_APPEND);
				$detail->detail_id=-$db->insertId();
				$detail->brand_id=$brand_id;
			}
			else {
				
			}
	    }
	    return $detail;
	}

	private static function get_brand_from_lib($brand){
		$url="http://".Config::get("library_ip")."/api/v2/index.php";
		$get_brand_ids=array(
			"action"=>"get_brand_id",
			"brand"=> $brand
		);
		$json_data=json_encode($get_brand_ids);
		//echo $send;
		$context = stream_context_create([
			'http' => [
				'method' => 'POST',
				'header' => "Content-type: application/json\r\n" .
					"Accept: application/json\r\n" .
					"Connection: close\r\n" .
					"Content-length: " . strlen($json_data) . "\r\n",
				'protocol_version' => 1.1,
				'content' => $json_data
			],
			'ssl' => [
				'verify_peer' => false,
				'verify_peer_name' => false
			]
		]);
		$res=file_get_contents($url,false,$context);
		$r=json_decode($res,true);
		if(count((array)$r['brand_ids'][$brand])>0){
			//Нашли бренды
			return $r['brand_ids'][$brand][0];
		}
		else {
			return false;
		}
		//return $r;
	}

	public static function get_brand_id($brand,$db){
		$brand=self::convert_article($brand);
		$brand_id_arr=$db->getRow("select id,brand_id from local_brands where brand=?s",$brand);
		//file_put_contents("/var/log/shop/api/get_local_details.log","select id,brand_id from local_brands where brand='$brand'\nbrand_id_arr=".print_r($brand_id_arr,true)."\n",FILE_APPEND);
	    if($brand_id_arr){
			if((int)$brand_id_arr['brand_id']==0) {
				//может в билиотеке есть бренд
				$r=self::get_brand_from_lib($brand);
				//echo "r=".print_r($r,true);
				if((int)$r>0){
					//есть
					self::save_local_brand($brand,$r,$db);
					return $r;
				}
				else {
					//нет
					$brand_id=-(int)$brand_id_arr['id'];
				}
				
			}
			else $brand_id=$brand_id_arr['brand_id'];
			return $brand_id;
		}
		else {
			//нет в нашей базе надо посмотреть в библиотеке
			$r=self::get_brand_from_lib($brand);
			//echo "r=".print_r($r,true);
			if((int)$r>0){
				//нашли
				self::save_local_brand($brand,$r,$db);
				return $r;
			}
			else {
				//надо записать
				$r=self::save_local_brand($brand,$r,$db);
				//echo " brand=$brand,r=$r after write r=".$r;
				return -$r;
			}
			//$db->query("insert into local_brands set ")
		}	
	}

	private static function save_local_brand($name,$id,$db){
		$res=$db->query("insert into local_brands (brand_id,brand,brand_row,create_date) values (?i,?s,?s,?s) on duplicate key update brand_id=values(brand_id),update_date=?s",(int)$id,$name,$name,date("Y-m-d H:i:s"),date("Y-m-d H:i:s"));
		if((int)$id!=0){
			//file_put_contents("/var/log/shop/api/save_local_brand","sql:"."insert into local_brands (brand_id,brand,brand_row,create_date) values (?i,?s,?s,?s) on duplicate key update brand_id=values(brand_id),update_date=?s,".$id.",".$name.",".$name.",".date("Y-m-d H:i:s").",".date("Y-m-d H:i:s")."\ninsert_id=".$db->insertId()."\n",FILE_APPEND);
		}
		else {
			file_put_contents("/var/log/shop/api/save_local_brand","sql:"."insert into local_brands (brand_id,brand,brand_row,create_date) values (?i,?s,?s,?s) on duplicate key update brand_id=values(brand_id),update_date=?s,".$id.",".$name.",".$name.",".date("Y-m-d H:i:s").",".date("Y-m-d H:i:s")."\ninsert_id=".$db->insertId()."\n",FILE_APPEND);
		}
		return $db->insertId();
	}
}
?>
