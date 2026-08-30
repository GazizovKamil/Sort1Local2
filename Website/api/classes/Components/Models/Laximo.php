<?php

namespace Sort1API\Components\Models;

use Sort1API\Components\DB;
use Sort1API\Components\Config;
use Sort1API\Components\Logger;
use Sort1API\Components\Notify;
use Sort1API\Components\Functions;

//include('Laximo'.DIRECTORY_SEPARATOR.'guayaquillib'.DIRECTORY_SEPARATOR.'data'.DIRECTORY_SEPARATOR.'requestAm.php');

//use Sort1API\Components\Models\Laximo\guayaquillib\data\GuayaquilRequestAM;
//use Sort1API\Components\Models\Laximo\guayaquillib\data\GuayaquilRequestOEM;

use GuayaquilLib\ServiceOem;

require_once '../vendor/autoload.php';

class Laximo extends Model {
	
	public static function getCarByVin($vin,&$company_car){
		$db = DB::getInstance();
		$start = microtime(true);
		$request = new ServiceOem(Config::get("laximo-user-login"), Config::get("laximo-user-key"));
		//$request->setUserAuthorizationMethod(Config::get("laximo-user-login"), Config::get("laximo-user-key"));
		$data=$request->findVehicleByVIN($vin);
		$vehicles=$data->getVehicles();
		//$data = $request->query(); 		
		file_put_contents("/var/log/sort1/laximo_request.log","New Laximo.OEM request user_id=".$_SESSION['user_id'].",\n Vehicles:\n".print_r($vehicles,true)."\n OEM: $vin, time: ".(microtime(true)-$start)."\ndata:\n".print_r($data,true), FILE_APPEND);
		Logger::log("New Laximo.OEM request,\n Request:\n".print_r($request,true)."\n OEM: $vin, time: ".(microtime(true)-$start)."\ndata:\n".print_r($data,true), "laximo_request");
		if ($request->error != '')
		{
			Logger::log("error in laximo query: ".$request->error."\n", "laximo_error");
			return array("status"=>"err","err"=>$request->error);
		}
		else
		{
			if(count($vehicles)==0){
				$ret_car=array(
					"auto_maker_id"=>"",
					"auto_maker"=>"",
					"auto_model"=>"",
					"made_year"=>"",
					"made_date"=>"",
					"engine_num"=>"",
					"vin"=>$vin,
				);
				return array("status"=>"ok","car"=>$ret_car);
			}
			$auto_maker_id=$db->getOne("select id from auto_makers where name=?s",$vehicles[0]->getBrand());
			if($auto_maker_id) $company_car->auto_maker_id=$auto_maker_id;
			$company_car->auto_maker_name=$vehicles[0]->getBrand();
			$company_car->auto_model=$vehicles[0]->getName();
			foreach($vehicles[0]->getAttributes() as $attr){				
				//Logger::log("dop_attr=".print_r($dop_attr,true)."\ndarr=".print_r($darr,true), "laximo_request");
				if($attr->getKey()=='date' || $attr->getKey()=='production_date') {
					if(preg_match("/^(\d+)\.(\d+)$/",$attr->getValue(),$darr_spl)) $company_car->made_year=$darr_spl[2];
					else {
						$company_car->made_date=date("Y-m-d",strtotime($attr->getValue()));
						$company_car->made_year=date("Y",strtotime($attr->getValue()));
					}
				}
				if($attr->getKey()=='manufactured') {
					if(preg_match("/(\d+)\s+(\d+)/",$attr->getValue(),$darr_spl)) $company_car->made_year=$darr_spl[2];
					else { 
						$company_car->made_year=$attr->getValue();
					}
				}
				if($attr->getKey()=='engine') {
					$engine=$attr->getValue();
				}
				if($attr->getKey()=='engineno') {
					$engineno=$attr->getValue();
				}
				
			}
			$ret_car=array(
					"auto_maker_id"=>$company_car->auto_maker_id,
					"auto_maker"=>((array)$company_car->auto_maker_name)[0],
					"auto_model"=>((array)$company_car->auto_model)[0],
					"made_year"=>((array)$company_car->made_year)[0],
					"made_date"=>$company_car->made_date,
					"engine_num"=>$engine.' '.$engineno,
					"vin"=>$vin,
				);
			return array("status"=>"ok","car"=>$ret_car);
		}
	}
    
	public static function getCarByPlateNumber($plateNumber,&$company_car){
		$db = DB::getInstance();
		$start = microtime(true);
			
		$request = new ServiceOem(Config::get("laximo-user-login"), Config::get("laximo-user-key"));
		//$request->setUserAuthorizationMethod(Config::get("laximo-user-login"), Config::get("laximo-user-key"));
		$data=$request->findVehicleByPlateNumber($plateNumber);
		$vehicles=$data->getVehicles();
		Logger::log("New Laximo.OEM request,\n Request:\n".print_r($request,true)."\n OEM: $vin, time: ".(microtime(true)-$start)."\ndata:\n".print_r($data,true), "laximo_request");
		if ($request->error != '')
		{
			Logger::log("error in laximo query: ".$request->error."\n", "laximo_error");
			return array("status"=>"err","err"=>$request->error);
		}
		else
		{
			if(count($vehicles)==0){
				$ret_car=array(
					"auto_maker_id"=>"",
					"auto_maker"=>"",
					"auto_model"=>"",
					"made_year"=>"",
					"made_date"=>"",
					"engine_num"=>"",
					"auto_gov_num"=>$plateNumber,
				);
				return array("status"=>"ok","car"=>$ret_car);
			}
			$auto_maker_id=$db->getOne("select id from auto_makers where name=?s",$vehicles[0]->getBrand());
			if($auto_maker_id) $company_car->auto_maker_id=$auto_maker_id;
			$company_car->auto_maker_name=$vehicles[0]->getBrand();
			$company_car->auto_model=$vehicles[0]->getName();
			foreach($vehicles[0]->getAttributes() as $attr){				
				//Logger::log("dop_attr=".print_r($dop_attr,true)."\ndarr=".print_r($darr,true), "laximo_request");
				if($attr->getKey()=='date' || $attr->getKey()=='production_date') {
					if(preg_match("/^(\d+)\.(\d+)$/",$attr->getValue(),$darr_spl)) $company_car->made_year=$darr_spl[2];
					else {
						$company_car->made_date=date("Y-m-d",strtotime($attr->getValue()));
						$company_car->made_year=date("Y",strtotime($attr->getValue()));
					}
				}
				if($attr->getKey()=='manufactured') {
					if(preg_match("/(\d+)\s+(\d+)/",$attr->getValue(),$darr_spl)) $company_car->made_year=$darr_spl[2];
					else { 
						$company_car->made_year=$attr->getValue();
					}
				}
				if($attr->getKey()=='engine') {
					$engine=$attr->getValue();
				}
				if($attr->getKey()=='engineno') {
					$engineno=$attr->getValue();
				}
				
			}
			$ret_car=array(
					"auto_maker_id"=>$company_car->auto_maker_id,
					"auto_maker"=>((array)$company_car->auto_maker_name)[0],
					"auto_model"=>((array)$company_car->auto_model)[0],
					"made_year"=>((array)$company_car->made_year)[0],
					"made_date"=>$company_car->made_date,
					"engine_num"=>$engine.' '.$engineno,
					"auto_gov_num"=>$plateNumber,
					//"data"=>$data
				);
			return array("status"=>"ok","car"=>$ret_car,"car_full"=>(array)$company_car,"laximo_data"=>(array)$data);
		}
	}

	public static function perform_laximo($str) {
		
		$db = DB::getInstance();
		
		$start = microtime(true);
		
		$request = new GuayaquilRequestAM('ru_RU');
	    $request->setUserAuthorizationMethod(Config::get("laximo-user-login"), Config::get("laximo-user-key"));
		
		$options='crosses,images';
		$brand = null;
		$replacementtypes='Replacement,Bidirectional';
		
		$request->appendFindOEM($str, $options, $brand, $replacementtypes);
		$data = $request->query(); 		
		
		Logger::log("New Laximo.AM request, OEM: $str, time: ".(microtime(true)-$start), "laximo_request");
		
		// to return:
		$replacements = [];
		
		
		/////////////////////		
		if ($request->error != '')
		{
			Logger::log("error in laximo query: ".$request->error."\n", "laximo_error");
		}
		else
		{
		    $data = simplexml_load_string($data);
		    $data = $data[0]->FindOEM->detail;
			
			
		    if (!$data || (!(string)$data['manufacturerid'])) {
					$sql_good="insert into details(article,article_raw,brand_id,name,scan,scan_crosses) 
					values (?s, ?s, 0, '', 1) ON DUPLICATE KEY UPDATE scan=scan|1, scan_crosses=scan_crosses|1";
					
					$db->query($sql_good, Functions::convert_article($str) , $str);

		    }
			else 
			{
		        foreach ($data as $detail)
		        {
					$volume = (float)$detail['volume'];
					$weight = (float)$detail['weight'];
					$image = '';			
					foreach ($detail->images->image as $image) {
						$image=(string)$image['filename'];
					}
					
					//$detail_id=get_detail_id(convert_article($detail['formattedoem']),$detail['manufacturer']);
					
					$laximo_brand_id = (string)$detail['manufacturerid'];
					$laximo_brand = (string)$detail['manufacturer'];
					
					$real_brand_id = self::get_real_brand_id_by_laximo_brand_id($laximo_brand_id, $laximo_brand);
					
					
					$sql_good1 = "INSERT INTO details(article, article_raw, brand_id, name, scan, scan_crosses) VALUES (?s, ?s, ?i, ?s, 1, 1) ON DUPLICATE KEY UPDATE id=LAST_INSERT_ID(id), scan=scan | 1, scan_crosses=scan_crosses|1, article_raw=?s, last_update=NOW()";
					
					if (!empty((string)$detail['name'])) {
						$sql_good1 .= ", name=?s";
						$res_good1 = $db->query($sql_good1, Functions::convert_article((string)$detail['formattedoem']),(string)$detail['formattedoem'], $real_brand_id,(string)$detail['name'],(string)$detail['formattedoem'],(string)$detail['name']);
					} else {
						$res_good1 = $db->query($sql_good1, Functions::convert_article((string)$detail['formattedoem']),(string)$detail['formattedoem'], $real_brand_id,(string)$detail['name'],(string)$detail['formattedoem']);
					}
					
					$detail_id = $db->insertId();

					//properties:
					$prop = [];
					if ($volume > 0)
						$prop[]  = $db->parse("(?i,'volume', ?s, 1)",$detail_id, $volume);
					if ($weight > 0)
						$prop[]  = $db->parse("(?i,'weight', ?s, 1)",$detail_id, $weight);
					if (!empty($image))
						$prop[]  = $db->parse("(?i,'image', ?s, 1)",$detail_id, $image);
					
					if (!empty($prop)) {
						$sql_prop = "INSERT IGNORE INTO details_info(detail_id,name,value,author_id) VALUES ".implode(",", $prop);
						$db->query($sql_prop);
					}
					
					


					foreach ($detail->replacements->replacement as $replacement) {
						$sql_cross = "INSERT IGNORE INTO crosses(detail_id,cross_article,cross_brand,cross_brand_id,cross_name,client_id,author_id,disabled) VALUES (?i,?s,?s,?i,?s,0,1,0)";
						$cross_brand_id = self::get_real_brand_id_by_laximo_brand_id((string)$replacement->detail['manufacturerid'], (string)$replacement->detail['manufacturer']);
						
						$db->query($sql_cross, $detail_id, Functions::convert_article((string)$replacement->detail['formattedoem']), Functions::convert_article((string)$replacement->detail['manufacturer']), $cross_brand_id, (string)$replacement->detail['name']);
												
						
					}
					
		        }
		    }
		}
				
		/////////////////////		
		
	}
	
	
	public static function get_real_brand_id_by_laximo_brand_id($laximo_brand_id, $laximo_brand) {
		$db = DB::getInstance();
		
		$sql = "SELECT brand_id FROM brands_laximo WHERE laximo_brand_id=?i";
		$brid = $db->getOne($sql, $laximo_brand_id);
		
		if ($brid > 0) 
			return $brid;
		
		//не нашли, надо добавить:
					
		$lb = Functions::convert_article($laximo_brand);
		
		//ищем похожий бренд в таблице:
		
		$sql1 = "SELECT brand_id FROM brands WHERE brand=?s";
		$brid1 = $db->getOne($sql1, $lb);
		
		if ($brid1 > 0 ) {
			//нашли, добавляем связь в таблицу и возвращаем найденный:
			$db->query("INSERT INTO brands_laximo(laximo_brand_id, brand_id) VALUES (?i,?i)", $laximo_brand_id, $brid1);
			
			return $brid1;
			
		} else {
			// не нашли, добавляем новый, оповещаем о добавлении на почту и вовзращаем новый brand_id:
			
			//смотрим макс brand_id и вставляем ++-нутый
			$mbrid = $db->getOne("SELECT max(brand_id) FROM brand");	
			$mbrid++;
			
			$sql2 = "INSERT INTO brands (brand_id, brand, brand_raw) VALUES (?i, ?s, ?s)";
			$db->query($sql2, $mbrid,  $lb, $laximo_brand);
			$id = $db->insertId();
			
			//telegram not work on this server:
			//Notify::telegram("New brand from Laximo inserted to brands: ($id, $mbrid, $lb, $laximo_brand)");
			Notify::mail("Library: new brand added!","New brand from Laximo inserted to brands: ($id, $mbrid, $lb, $laximo_brand)");
			
			
			return $mbrid;
		}
		
		
	}
	
	public static function increase_request_sort1() {
		$db = DB::getInstance();
		
		$sql = "Select * from laximo_users_count where main_company_id=?i and create_date=?s";
		$res = $db->getRow($sql,$_SESSION['main_company'], date('Y-m-d'));
		if($res){
			$count = (int)$res['laximo_count'];
			$sql="update laximo_users_count set laximo_count=?i where main_company_id=?i and create_date=?s";
			$db->query($sql,$count+1, $_SESSION['main_company'], date('Y-m-d'));
			if ($db->affectedRows()>0) {
				$status = 1;
			}
			else { $status = 0; }
		}
		else{
			$laximo_users_count = array();
			$laximo_users_count['main_company_id'] = $_SESSION['main_company'];
			$laximo_users_count['company_id'] = $_SESSION['company_id'];
			$laximo_users_count['user_id'] = $_SESSION['user_id'];
			$laximo_users_count['laximo_count'] = 1;
			$laximo_users_count['create_date'] = date('Y-m-d');

			$sql = "insert ignore into laximo_users_count set ?u";
			$db->query($sql,$laximo_users_count);
			if ($db->affectedRows()>0) {
				$status = 1;
			}
			else { $status = 0; }
		}

		if($status == 1){
			$ret['status']="ok";
    		$ret['msg']= "Количество увеличилось";
		}
		else {
    		$ret['status']="err";
    		$ret['msg']= "Ошибка запроса";
		}
	    if ($ret['status']=="err") return self::_error_arr($res['error']);
	    else return $ret;
	}
	
}



?>