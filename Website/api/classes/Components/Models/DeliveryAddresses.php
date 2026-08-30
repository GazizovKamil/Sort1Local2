<?php

namespace Sort1API\Components\Models;

use Sort1API\Components\DB;
use Sort1API\Components\DeliveryAddress;

/**
* 	Model Class to work with Orders instatnces (orders table)
*   Class can use inner Database (DB::getInstance())
*   or
* 	external api to receive data (using HTTPRequest class or extened)
* 
*/


class DeliveryAddresses extends Model {

        public static function check_roles($role){
                $main_user=new User((int)$_SESSION['user_id']);
                if ($main_user->roles<=$role) return $role;
                else return $main_user->roles;
            }

        public static function save_delivery_address($request) {
            $db = DB::getInstance();
	    if (isset($request->id)) $id=(int)$request->id;
	    if (isset($id) && $id>0) {
		$da=new DeliveryAddress($id);
		$da->update_date=date("Y-m-d H:i:s");
	    }
	    else {
		$da=new DeliveryAddress();
		$da->create_date=date("Y-m-d H:i:s");
	    }
	    if (isset($request->company_id)) $da->company_id=$request->company_id;
	    if (isset($request->delivery_address)) $da->delivery_address=$request->delivery_address;
	    if (isset($request->delivery_days)) $da->delivery_days=$request->delivery_days;
	    if (isset($request->delivery_time_start)) $da->delivery_time_start=$request->delivery_time_start;
	    if (isset($request->delivery_time_stop)) $da->delivery_time_stop=$request->delivery_time_stop;
	    $err=$da->save();
	    //echo print_r($price_type,true);
	    switch($err) {
		case 10: $status="err"; $msg="Данные не изменились\n"; break;
		case 1: if (isset($request->id) && (int)$request->id>0){
                		$status="ok"; $msg="Данные успешно изменены";
            		}
            		else {
                	    $status="ok"; $msg="Новый адрес доставки добавлен";
            		}
			break;
		default: $status="err"; $msg="error: ".$err."\n";
	    }
            if ($status=="err") return self::_error_arr($msg);
            else return array("status"=>$status,"msg"=>$msg,"id"=>$da->id);
        }


	public static function get_delivery_addresses($request) {
	    $db = DB::getInstance();
	    if(!isset($request->company_id)) return self::_error_arr("Не указана компания");
	    $sql="select * from delivery_address where company_id=?i and company_id in (select company_id from user_companys where main_company_id=?i) and deleted=0";
	    $res=$db->getAll($sql,$request->company_id,$_SESSION['main_company']);
	    if (is_array($res) && count($res)>0){
		$ret['status']="ok";
		$ret['err']="";
		$ret['delivery_addresses']=$res;
		$ret['msg']="";
	    }
	    else {
		$ret['status']="ok";
		$ret['err']="";
		$ret['delivery_addresses']=array();
		$ret['msg']="";
	    }
	    if ($ret['status']=="err") return self::_error_arr($ret['err']);
	    else return $ret;
	}

	public static function get_my_delivery_addresses($request) {
	    $db = DB::getInstance();
	    //if(!isset($request->company_id)) return self::_error_arr("Не указана компания");
	    $sql="select * from delivery_address where company_id=?i and company_id in (select company_id from user_companys where main_company_id=?i) and deleted=0";
	    $res=$db->getAll($sql,$_SESSION['company_id'],$_SESSION['main_company']);
	    if (is_array($res) && count($res)>0){
		$ret['status']="ok";
		$ret['err']="";
		$ret['delivery_addresses']=$res;
		$ret['msg']="";
	    }
	    else {
		$ret['status']="ok";
		$ret['err']="";
		$ret['delivery_addresses']=array();
		$ret['msg']="";
	    }
	    if ($ret['status']=="err") return self::_error_arr($ret['err']);
	    else return $ret;
	}

	public static function get_delivery_address($request) {
	    $db = DB::getInstance();
	    $sql="select * from delivery_address where id=?i and deleted=0";
	    $res=$db->getRow($sql,(int)$request->id);
	    if (is_array($res) && count($res)>0){
		$ret['status']="ok";
		$ret['err']="";
		$ret['delivery_address']=$res;
		$ret['msg']="";
	    }
	    else {
		$ret['status']="err";
		$ret['err']="Невозможно найти адрес доставки";
	    }
	    if ($ret['status']=="err") return self::_error_arr($ret['err']);
	    else return $ret;
	}

	public static function delete_delivery_address($request) {
	    $db = DB::getInstance();
	    $client_companys=$db->getCol("select company_id from user_companys where user_id=?i",$_SESSION['user_id']);
	    if (isset($request->id)) {$da_id=(int)$request->id;}
	    if (isset($da_id) && $da_id>0){
		$res2=$db->query("update delivery_address set deleted=1 where id=?i and company_id in (?b)",$da_id,$client_companys);
		//echo "delete from sklad where id=".$sklad_id." and company_id in (select company_id from user_companys where main_company_id=0 and user_id=".$_SESSION['user_id'].")";
		if ($res2){
		    $ret['status']="ok";
		    $ret['msg']="Адрес доставки успешно удален";
		}
		else {
		    $ret['status']="err";
		    $ret['err']="не удалось удалить адрес доставки";
		}
	    }
	    else {
		$ret['status']="err";
		$ret['err']="нет данных";
	    }
	    if ($ret['status']=="err") return self::_error_arr($ret['err']);
	    else return $ret;
	}

}



?>