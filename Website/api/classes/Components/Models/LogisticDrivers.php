<?php

namespace Sort1API\Components\Models;

use Sort1API\Components\DB;
use Sort1API\Components\LogisticDriver;

/**
* 	Model Class to work with Orders instatnces (orders table)
*   Class can use inner Database (DB::getInstance())
*   or
* 	external api to receive data (using HTTPRequest class or extened)
*
*/


class LogisticDrivers extends Model {

	public static function save_logistic_driver($request) {
	    $fields="";
	    $db = DB::getInstance();
	    if (isset($request->logistic_driver_id) && (int)$request->logistic_driver_id>0) {$logistic_driver_id=(int)$request->logistic_driver_id;}
	    if(isset($logistic_driver_id) && $logistic_driver_id>0) {
				$logistic_driver=new LogisticDriver($logistic_driver_id);
	    }
	    else {
				$logistic_driver=new LogisticDriver();
	    }
	    if (isset($request->default_car_id)) {$logistic_driver->default_car_id=(int)$request->default_car_id;}
		if (isset($request->name)) $logistic_driver->name=$request->name;
	    if (isset($request->surname)) {$logistic_driver->surname=$request->surname;}
	    if (isset($request->lastname)) {$logistic_driver->lastname=$request->lastname;}
	    if (isset($request->driver_licence_num)) {$logistic_driver->driver_licence_num=$request->driver_licence_num;}
	    if (isset($request->mphone)) {$logistic_driver->mphone=$request->mphone;}
	    if (isset($request->default_car_id)) {$logistic_driver->default_car_id=$request->default_car_id;}
	    //if (isset($request->)) {$logistic_car->driver_type=$request->driver_type; }
	    //print_r($_GET);
	    //echo $company->kpp;
	    $logistic_driver_saved=$logistic_driver->save();
		    if($logistic_driver_saved){
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


	public static function delete_logistic_driver($request) {
	    $fields="";
	    $db = DB::getInstance();
	    if ((int)$_SESSION['roles']!=1) {
				return self::_error_arr("У Вас нет прав для удаления");
	    }
	    if (isset($request->logistic_driver_id) && (int)$request->logistic_driver_id>0){
				if ($res2){
				    $ret['status']="ok";
				    $res3=$db->query("update logistic_drivers set deleted=1 where id=?i and main_company=?i",(int)$request->logistic_driver_id,(int)$_SESSION['main_company']);
				    $ret['msg']="Водитель успешно удален";
				}
				else {
				    $ret['status']="err";
				    $ret['err']="не удалось удалить водителя";
				}
	    }
	    else {
				$ret['status']="err";
				$ret['err']="нет данных";
	    }
	    if ($ret['status']=="err") return self::_error_arr($ret['err']);
	    else return array("status"=>$ret['status'],"msg"=>$ret['msg'],"err"=>"");
	}

	public static function get_logistic_drivers($request) {
	    $db = DB::getInstance();
	    $sql="select * from logistic_drivers where main_company_id=?i and deleted=0";
	    $res=$db->getAll($sql,$_SESSION['main_company']);
			$cars=array();
			foreach($res as $res_key=>$res_data){
				if($res_data['default_car_id']>0) $default_cars[]=$res_data['default_car_id'];
			}
	    if (is_array($res) && count($res)>0){
				$ret['status']="ok";
				$ret['err']="";
				$ret['logistic_drivers']=$res;
				$ret['msg']="";
				if(count((array)$default_cars)>0) {
					$sqld="select * from logistic_cars where id in (?b)";
					$cars_res=$db->getAll($sqld,$default_cars);
					foreach($cars_res as $bkey=>$bval){
				    $cars[$bval['id']]=$bval['auto_maker_name']." ".$bval['auto_model']." ".$bval['auto_gov_num'];
					}
				}
				$cars[0]="Не определен";
				$ret['cars']=$cars;
	    }
			else {
				$ret['status']="ok";
				$ret['err']="";
				$ret['logistic_drivers']=[];
				$ret['msg']="";
			}
	    if ($ret['status']=="err") return self::_error_arr($ret['err']);
	    else return $ret;
	}

	public static function get_logistic_driver($request) {
	    $db = DB::getInstance();
			if(empty($request->logistic_driver_id) || (int)$request->logistic_driver_id==0){
				return self::_error_arr('нет данных');
			}
	    $sql="select * from logistic_drivers where id=?i and main_company_id=?i and deleted=0";
	    $res=$db->getRow($sql,(int)$request->logistic_driver_id,$_SESSION['main_company']);
			$cars=array();
			//foreach($res as $res_key=>$res_data){
				if((int)$res['default_car_id']>0) {
					$default_car_id=(int)$res['default_car_id'];
					$sqld="select * from logistic_cars where id = ?i";
					$cars_res=$db->getAll($sqld,$default_car_id);
					foreach($cars_res as $bkey=>$bval){
				    $cars[$bval['id']]=$bval['auto_maker_name']." ".$bval['auto_model']." ".$bval['auto_gov_num'];
					}
					//$drivers[0]="Не определен";
					$ret['cars']=$cars;
				}
			//}
	    if ($res){
				$ret['status']="ok";
				$ret['err']="";
				$ret['logistic_driver']=$res;
				$ret['msg']="";
				$ret['logistic_cars']=$cars;
	    }
			else {
				$ret['status']="ok";
				$ret['err']="";
				$ret['logistic_driver']=[];
				$ret['msg']="Не могу найти водителя";
			}
	    if ($ret['status']=="err") return self::_error_arr($ret['err']);
	    else return $ret;
	}

}



?>
