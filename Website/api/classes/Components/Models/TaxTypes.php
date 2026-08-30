<?php

namespace Sort1API\Components\Models;

use Sort1API\Components\DB;
use Sort1API\Components\TaxType;

/**
* 	Model Class to work with Orders instatnces (orders table)
*   Class can use inner Database (DB::getInstance())
*   or
* 	external api to receive data (using HTTPRequest class or extened)
* 
*/


class TaxTypes extends Model {

        public static function check_roles($role){
                $main_user=new User((int)$_SESSION['user_id']);
                if ($main_user->roles<=$role) return $role;
                else return $main_user->roles;
            }

        public static function save_tax_type($request) {
			$db = DB::getInstance();
		if($_SESSION['user_id']!=66) return self::_error_arr("Система налогообложения меняется администратором системы");
	    if (isset($request->tax_type_id)) $tax_id=(int)$request->tax_type_id;
	    if (isset($tax_id) && $tax_id>0) {
		$tax_type=new TaxType($tax_id);
		$tax_type->update_date=date("Y-m-d H:i:s");
	    }
	    else {
		$tax_type=new TaxType();
		$tax_type->create_date=date("Y-m-d H:i:s");
	    }
	    if (isset($request->name)) $tax_type->name=$request->name;
	    if (isset($request->tax_rate)) $tax_type->tax_rate=(int)$request->tax_rate;
	    if (isset($request->is_add) && $request->is_add=="on") $tax_type->is_add=1;
	    $err=$tax_type->save();
	    //echo print_r($price_type,true);
	    switch($err) {
		case 10: $status="err"; $msg="Данные не изменились\n"; break;
		case 1: if (isset($request->tax_type_id) && (int)$request->tax_type_id>0){
                		$status="ok"; $msg="Данные успешно изменены";
            		}
            		else {
                	    $status="ok"; $msg="Новый тип цен добавлен";
            		}
			break;
		default: $status="err"; $msg="error: ".$err."\n";
	    }
            if ($status=="err") return self::_error_arr($msg);
            else return array("status"=>$status,"msg"=>$msg);
        }


	public static function get_tax_types($request) {
	    $db = DB::getInstance();
	    $sql="select * from tax_type where deleted=0";
	    $res=$db->getAll($sql);
	    if (is_array($res) && count($res)>0){
		$ret['status']="ok";
		$ret['err']="";
		$ret['tax_types']=$res;
		$ret['msg']="";
	    }
	    else {
		$ret['status']="ok";
		$ret['err']="";
		$ret['tax_types']=array();
		$ret['msg']="";
	    }
	    if ($ret['status']=="err") return self::_error_arr($ret['err']);
	    else return $ret;
	}

	public static function get_tax_type($request) {
	    $db = DB::getInstance();
	    $sql="select * from tax_type where id=?i and deleted=0";
	    $res=$db->getRow($sql,(int)$request->tax_type_id);
	    if (is_array($res) && count($res)>0){
		$ret['status']="ok";
		$ret['err']="";
		$ret['tax_type']=$res;
		$ret['msg']="";
	    }
	    else {
		$ret['status']="err";
		$ret['err']="Невозможно найти тип цен";
	    }
	    if ($ret['status']=="err") return self::_error_arr($ret['err']);
	    else return $ret;
	}

	public static function delete_tax_type($request) {
		$db = DB::getInstance();
		if($_SESSION['user_id']!=66) return self::_error_arr("Система налогообложения меняется администратором системы");
	    if ((int)$_SESSION['roles']!=1) {
		return self::_error_arr("У Вас нет прав для удаления");
	    }
	    if (isset($request->tax_type_id)) {$tt_id=(int)$request->tax_type_id;}
	    if (isset($tt_id) && $tt_id>0){
		$res2=$db->query("update tax_type set deleted=1 where id=?i",$tt_id);
		//echo "delete from sklad where id=".$sklad_id." and company_id in (select company_id from user_companys where main_company_id=0 and user_id=".$_SESSION['user_id'].")";
		if ($res2){
		    $ret['status']="ok";
		    $ret['msg']="Тип налогообложения успешно удален";
		}
		else {
		    $ret['status']="err";
		    $ret['err']="не удалось удалить тип налогообложения";
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