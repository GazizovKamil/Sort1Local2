<?php

namespace Sort1API\Components\Models;

use Sort1API\Components\DB;
use Sort1API\Components\City;

/**
* 	Model Class to work with Orders instatnces (orders table)
*   Class can use inner Database (DB::getInstance())
*   or
* 	external api to receive data (using HTTPRequest class or extened)
* 
*/


class Citys extends Model {

        public static function check_roles($role){
                $main_user=new User((int)$_SESSION['user_id']);
                if ($main_user->roles<=$role) return $role;
                else return $main_user->roles;
            }

        public static function get_city($request) {
            $db = DB::getInstance();
			$sql="select * from city where city like ?s";
			$our_citys=$db->getAll($sql,$request->city_name."%");
			if(count((array)$our_citys)>0) {
				$i=0;
				foreach($our_citys as $c_key=>$c_val){
					$citys[$i]['id']=$c_val['id'];
					$citys[$i]['city']=$c_val['city'];
					$citys[$i]['from']="our";
					$i++;
				}
				return array("status"=>"ok","msg"=>"","citys"=>$citys);
			}
			$header=array(
			"Content-type: application/json",
			"Accept: application/json",
			"Authorization: Token 4e4c3f5a453e7eae95343a3c88f7518a20d31af3"
			);
			$send=array("query"=>$request->city_name);
			//include "/var/www/html1/include/lib.php";
			$res=post_curl("","https://suggestions.dadata.ru/suggestions/api/4_1/rs/suggest/address",$header,json_encode($send));
			file_put_contents("/var/log/shop/api/citys.log",print_r($res,true)."\n",FILE_APPEND);
			$result=json_decode($res['body']);
			$i=0;
			foreach($result->suggestions as $sug_key=>$sug_val){
				if(!empty($sug_val->data->city_fias_id) && ($sug_val->data->fias_level==3 || $sug_val->data->fias_level==4 || $sug_val->data->fias_level==6)) {
					$city=new City($sug_val->data->city_fias_id);
					file_put_contents("/var/log/shop/api/citys.log",print_r($sug_val,true)."\n",FILE_APPEND);
					$city->request=$request->city_name;
					//$city->data=$res['body'];
					$city->city_fias_id=$sug_val->data->city_fias_id;
					$city->city_kladr_id=$sug_val->data->city_kladr_id;
					$city->city_with_type=$sug_val->data->city_with_type;
					$city->city_type=$sug_val->data->city_type;
					$city->city_type_full=$sug_val->data->city_type_full;
					$city->city=$sug_val->data->city;
					//print_r($city);
					$city->Save();
					//print_r($city);
					//print_r($db->getStats());
					$citys[$i]['id']=$city->id;
					$citys[$i]['city']=$city->city;
					$i++;
				}
			}
			return array("status"=>"ok","msg"=>"","citys"=>$citys);
		}

		public static function get_countrys($request) {
            $db = DB::getInstance();
			$sql="select * from oksm_country where is_producer=1";
			$countrys=$db->getAll($sql);
			if(count((array)$countrys)>0) {
				return array("status"=>"ok","msg"=>"","countrys"=>$countrys);
			}
		}

		public static function get_citys($request){
			$db = DB::getInstance();
			$sql="select id, city from city ORDER BY city";
			$city=$db->getAll($sql);
			if(count((array)$city)>0) {
				return array("status"=>"ok","msg"=>"","city"=>$city);
			}
			else {
				return array("status"=>"ok","msg"=>"","city"=>array());
			}
		}
}
?>