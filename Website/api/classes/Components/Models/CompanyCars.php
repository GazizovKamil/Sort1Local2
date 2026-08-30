<?php

namespace Sort1API\Components\Models;

use Sort1API\Components\DB;
use Sort1API\Components\CompanyCar;
use Sort1API\Components\Models\Laximo;

/**
* 	Model Class to work with Orders instatnces (orders table)
*   Class can use inner Database (DB::getInstance())
*   or
* 	external api to receive data (using HTTPRequest class or extened)
*
*/


class CompanyCars extends Model {

	public static function save_company_car($request) {
	    $fields="";
	    $db = DB::getInstance();
	    if (isset($request->company_car_id) && (int)$request->company_car_id>0) {$company_car_id=(int)$request->company_car_id;}
	    if(isset($company_car_id) && $company_car_id>0) {
				$company_car=new CompanyCar($company_car_id);
	    }
	    else {
				$company_car=new CompanyCar();
	    }
	    //if (isset($request->default_driver_user_id)) {$company_car->default_driver_user_id=(int)$request->default_driver_user_id;}
		if (isset($request->auto_maker_id) && (int)$request->auto_maker_id>0) {
			$company_car->auto_maker_id=$request->auto_maker_id;
			$auto_maker_name=$db->getOne("select name from auto_makers where id=?i",$company_car->auto_maker_id);
			if($auto_maker_name) $company_car->auto_maker_name=$auto_maker_name;
		}
		else {
			if (isset($request->auto_maker) && mb_strlen($request->auto_maker)>0) {
				$company_car->auto_maker_name=$request->auto_maker;
				$auto_maker_id=$db->getOne("select id from auto_makers where name=?s",strtoupper($request->auto_maker));
				if($auto_maker_id) $company_car->auto_maker_id=$auto_maker_id;
			}
		}
	    if (isset($request->auto_model)) {$company_car->auto_model=$request->auto_model;}
		if (isset($request->auto_motor_id)) {$company_car->auto_motor_id=(int)$request->auto_motor_id;}
	    if (isset($request->auto_gov_num)) {$company_car->auto_gov_num=$request->auto_gov_num;}
	    //if (isset($request->auto_model)) {$company_car->auto_model=$request->auto_model;}
	    if (isset($request->auto_doc_num)) {$company_car->auto_doc_num=$request->auto_doc_num;}
	    if (isset($request->company_id)) {$company_car->company_id=(int)$request->company_id;}
	    if (isset($request->vin)) {$company_car->vin=mb_strtoupper($request->vin); }
		if (isset($request->engine_num)) {$company_car->engine_num=$request->engine_num; }
		if (isset($request->chassi)) {$company_car->chassi=$request->chassi; }
		if (isset($request->kuzov_num)) {$company_car->kuzov_num=$request->kuzov_num; }
		if (isset($request->made_year)) {$company_car->made_year=(int)$request->made_year; }
		if (isset($request->probeg)) {$company_car->probeg=(int)$request->probeg; }
			//if (isset($request->engine_num)) {$company_car->engine_num=$request->engine_num; }
	    //print_r($_GET);
	    //echo $company->kpp;
		if(!empty($company_car->vin) && empty($company_car->auto_model) && strlen($company_car->vin)==17){
			Laximo::getCarByVin($company_car->vin,$company_car);
		}
		if(!empty($company_car->auto_gov_num) && empty($company_car->auto_model) && strlen($company_car->auto_gov_num)>=8){
			Laximo::getCarByPlateNumber($company_car->auto_gov_num,$company_car);
		}
	    $company_car_saved=$company_car->save();
		    if($company_car_saved){
					$ret['status']="ok";
					$ret['msg']="Данные успешно изменены";
					$ret['company_car_id']=$company_car->id;
		    }
		    else {
					$ret['status']="err";
					$ret['err']="Данные не менялись";
		    }
	    if ($ret['status']=="err") return self::_error_arr($ret['err']);
	    else return $ret;
	} // action_save_company


	public static function delete_company_car($request) {
	    $fields="";
	    $db = DB::getInstance();
	    if ((int)$_SESSION['roles']!=1) {
				//return self::_error_arr("У Вас нет прав для удаления");
	    }
	    if (isset($request->company_car_id) && (int)$request->company_car_id>0){
				$res2=$db->query("update company_cars set deleted=1 where id=?i and main_company_id=?i",(int)$request->company_car_id,(int)$_SESSION['main_company']);
				if ($res2){
				    $ret['status']="ok";
				    $ret['msg']="";
				}
				else {
				    $ret['status']="err";
				    $ret['err']="не удалось удалить автомобиль";
				}
	    }
	    else {
				$ret['status']="err";
				$ret['err']="нет данных";
	    }
	    if ($ret['status']=="err") return self::_error_arr($ret['err']);
	    else return array("status"=>$ret['status'],"msg"=>$ret['msg'],"err"=>"");
	}

	public static function get_company_cars($request) {
	    $db = DB::getInstance();
		if(!isset($request->company_id) || (int)$request->company_id==0) return self::_error_arr("Не указана компания");
	    $sql="select * from company_cars where company_id=?i and main_company_id=?i and deleted=0";
	    $res=$db->getAll($sql,(int)$request->company_id,$_SESSION['main_company']);
	    if (is_array($res) && count($res)>0){
				$ret['status']="ok";
				$ret['err']="";
				$ret['company_cars']=$res;
				$ret['msg']="";
	    }
			else {
				$ret['status']="ok";
				$ret['err']="";
				$ret['company_cars']=[];
				$ret['msg']="";
			}
	    if ($ret['status']=="err") return self::_error_arr($ret['err']);
	    else return $ret;
	}

	public static function get_company_car($request) {
	    $db = DB::getInstance();
			if(empty($request->company_car_id) || (int)$request->company_car_id==0){
				return self::_error_arr('нет данных');
			}
	    $sql="select * from company_cars where id=?i and main_company_id=?i and deleted=0";
	    $res=$db->getRow($sql,(int)$request->company_car_id,$_SESSION['main_company']);
	    if ($res){
				$ret['status']="ok";
				$ret['err']="";
				$ret['company_car']=$res;
				$ret['msg']="";
	    }
			else {
				$ret['status']="ok";
				$ret['err']="";
				$ret['company_car']=[];
				$ret['msg']="";
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

	public static function get_company_companys($request){
		$db = DB::getInstance();

	}
}



?>
