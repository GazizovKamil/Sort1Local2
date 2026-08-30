<?php

namespace Sort1API\Components\Models;

use Sort1API\Components\DB;
use Sort1API\Components\LogisticCar;

/**
* 	Model Class to work with Orders instatnces (orders table)
*   Class can use inner Database (DB::getInstance())
*   or
* 	external api to receive data (using HTTPRequest class or extened)
*
*/


class LogisticCars extends Model {

	public static function save_logistic_car($request) {
	    $fields="";
	    $db = DB::getInstance();
	    if (isset($request->logistic_car_id) && (int)$request->logistic_car_id>0) {$logistic_car_id=(int)$request->logistic_car_id;}
	    if(isset($logistic_car_id) && $logistic_car_id>0) {
				$logistic_car=new LogisticCar($logistic_car_id);
	    }
	    else {
				$logistic_car=new LogisticCar();
	    }
	    if (isset($request->default_driver_user_id)) {$logistic_car->default_driver_user_id=(int)$request->default_driver_user_id;}
			if (isset($request->auto_maker_id) && (int)$request->auto_maker_id>0) {
				$logistic_car->auto_maker_id=$request->auto_maker_id;
				$auto_maker_name=$db->getOne("select name from auto_makers where id=?i",$logistic_car->auto_maker_id);
				if($auto_maker_name) $logistic_car->auto_maker_name=$auto_maker_name;
			}
	    if (isset($request->auto_model)) {$logistic_car->auto_model=$request->auto_model;}
	    if (isset($request->auto_gov_num)) {$logistic_car->auto_gov_num=$request->auto_gov_num;}
	    if (isset($request->default_driver_licence_num)) {$logistic_car->default_driver_licence_num=$request->default_driver_licence_num;}
	    if (isset($request->auto_doc_num)) {$logistic_car->auto_doc_num=$request->auto_doc_num;}
	    if (isset($request->logistic_company_id)) {$logistic_car->logistic_company_id=$request->logistic_company_id;}
	    if (isset($request->driver_type)) {$logistic_car->driver_type=$request->driver_type; }
			if (isset($request->load_capacity)) {$logistic_car->load_capacity=$request->load_capacity; }
	    //print_r($_GET);
	    //echo $company->kpp;
	    $logistic_car_saved=$logistic_car->save();
		    if($logistic_car_saved){
					$ret['status']="ok";
					$ret['msg']="Данные успешно изменены";
		    }
		    else {
					$ret['status']="err";
					$ret['err']="Данные не менялись";
		    }
	    if ($ret['status']=="err") return self::_error_arr($ret['err']);
	    else return $ret;
	} // action_save_company


	public static function delete_logistic_car($request) {
	    $fields="";
	    $db = DB::getInstance();
	    if ((int)$_SESSION['roles']!=1) {
				return self::_error_arr("У Вас нет прав для удаления");
	    }
	    if (isset($request->logistic_car_id) && (int)$request->logistic_car_id>0){
			$res2=$db->query("delete from logistic_cars where id=?i and main_company_id=?i",(int)$request->logistic_car_id,(int)$_SESSION['main_company']);
				if ($res2){
				    $ret['status']="ok";
				    //$res3=$db->query("update logistic_cars set deleted=1 where id=?i and main_company=?i",(int)$request->logistic_car_id,(int)$_SESSION['main_company']);
				    $ret['msg']="Транспорт успешно удален";
				}
				else {
				    $ret['status']="err";
				    $ret['err']="не удалось удалить транспорт";
				}
	    }
	    else {
				$ret['status']="err";
				$ret['err']="нет данных";
	    }
	    if ($ret['status']=="err") return self::_error_arr($ret['err']);
	    else return array("status"=>$ret['status'],"msg"=>$ret['msg'],"err"=>"");
	}

	public static function get_logistic_cars($request) {
	    $db = DB::getInstance();
		$sql="select * from logistic_cars where main_company_id=?i and deleted=0";
		if(isset($request->logistic_company_id) && (int)$request->logistic_company_id>0) $sql.=" and logistic_company_id=".(int)$request->logistic_company_id;
	    $res=$db->getAll($sql,$_SESSION['main_company']);
			$drivers=array();
			$companys=array();
			foreach($res as $res_key=>$res_data){
				if($res_data['default_driver_user_id']>0) $cars_default_drivers[]=$res_data['default_driver_user_id'];
				if($res_data['logistic_company_id']>0) $cars_logistic_companys[]=$res_data['logistic_company_id'];
			}
	    if (is_array($res) && count($res)>0){
				$ret['status']="ok";
				$ret['err']="";
				$ret['logistic_cars']=$res;
				$ret['msg']="";
				if(count((array)$cars_default_drivers)>0) {
					$sqld="select * from logistic_drivers where id in (?b)";
					$drivers_res=$db->getAll($sqld,$cars_default_drivers);
					foreach($drivers_res as $bkey=>$bval){
				    $drivers[$bval['id']]=$bval['lastname']." ".$bval['name']." ".$bval['surname'];
					}
					$drivers[0]="Не определен";
					//$ret['drivers']=$drivers;
				}
				$ret['drivers']=$drivers;
				if(count((array)$cars_logistic_companys)>0) {
					$sqlc="select * from company where id in (?b)";
					$companys_res=$db->getAll($sqlc,$cars_logistic_companys);
					foreach($companys_res as $ckey=>$cval){
				    $companys[$cval['id']]=$cval;
					}
					$companys[0]="Не определен";
					
				}
				$ret['companys']=$companys;
	    }
		else {
			$ret['status']="ok";
			$ret['err']="";
			$ret['logistic_cars']=[];
			$ret['drivers']=[];
			$ret['companys']=[];
			$ret['msg']="";
		}
	    if ($ret['status']=="err") return self::_error_arr($ret['err']);
	    else return $ret;
	}

	public static function get_logistic_car($request) {
	    $db = DB::getInstance();
			if(empty($request->logistic_car_id) || (int)$request->logistic_car_id==0){
				return self::_error_arr('нет данных');
			}
	    $sql="select * from logistic_cars where id=?i and main_company_id=?i and deleted=0";
	    $res=$db->getRow($sql,(int)$request->logistic_car_id,$_SESSION['main_company']);
			$drivers=array();
			$companys=array();
			//foreach($res as $res_key=>$res_data){
				if((int)$res['default_driver_user_id']>0) {
					$cars_default_driver=(int)$res['default_driver_user_id'];
					$sqld="select * from logistic_drivers where id = ?i";
					$drivers_res=$db->getAll($sqld,$cars_default_driver);
					foreach($drivers_res as $bkey=>$bval){
				    $drivers[$bval['id']]=$bval['lastname']." ".$bval['name']." ".$bval['surname'];
					}
					//$drivers[0]="Не определен";
					$ret['drivers']=$drivers;
				}
				if((int)$res['logistic_company_id']>0) {
					$cars_logistic_company=(int)$res['logistic_company_id'];
					$sqlc="select * from company where id = ?i";
					$companys_res=$db->getAll($sqlc,$cars_logistic_company);
					foreach($companys_res as $ckey=>$cval){
				    $companys[$cval['id']]=$cval;
					}
					//$companys[0]="Не определен";
					$ret['companys']=$companys;
				}
			//}
	    if ($res){
				$ret['status']="ok";
				$ret['err']="";
				$ret['logistic_car']=$res;
				$ret['msg']="";
				$ret['cars_logistic_companys']=$cars_logistic_companys;
				if(count((array)$cars_logistic_companys)>0) {

				}
	    }
			else {
				$ret['status']="ok";
				$ret['err']="";
				$ret['logistic_car']=[];
				$ret['msg']="Список автомобилей пуст";
			}
	    if ($ret['status']=="err") return self::_error_arr($ret['err']);
	    else return $ret;
	}

	public static function get_auto_makers($request){
		$db = DB::getInstance();
		$auto_makers=$db->getAll("select * from auto_makers order by name");
		$ret['auto_makers']=$auto_makers;
		$ret['status']="ok";
		$ret['msg']="";
		$ret['err']="";
		return $ret;
	}

	public static function get_auto_models($request){
		if(empty($request->auto_maker_id) || (int)$request->auto_maker_id<1){
			return array("status"=>"err","err"=>"Не выбран производитель");
		}
		$db = DB::getInstance();
		$auto_models=$db->getAll("select * from auto_motors where carId=?i order by model",$request->auto_maker_id);
		$ret['auto_models']=$auto_models;
		$ret['status']="ok";
		$ret['msg']="";
		$ret['err']="";
		return $ret;
	}

	public static function get_logistic_companys($request){
		$db = DB::getInstance();

	}
}



?>
