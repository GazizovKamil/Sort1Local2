<?php

namespace Sort1API\Components\Models;

use Sort1API\Components\DB;
use Sort1API\Components\PriceTypeDifferencialValue;

/**
* 	Model Class to work with Orders instatnces (orders table)
*   Class can use inner Database (DB::getInstance())
*   or
* 	external api to receive data (using HTTPRequest class or extened)
* 
*/


class PriceTypeDifferencialValues extends Model {

        public static function check_roles($role){
                $main_user=new User((int)$_SESSION['user_id']);
                if ($main_user->roles<=$role) return $role;
                else return $main_user->roles;
            }

        public static function save_price_type_differencial_value($request) {
            $db = DB::getInstance();
	    if (isset($request->id)) $id=(int)$request->id;
	    if (isset($id) && $id>0) {
		$diff_val=new PriceTypeDifferencialValue($id);
		$diff_val->update_date=date("Y-m-d H:i:s");
	    }
	    else {
		$diff_val=new PriceTypeDifferencialValue();
		$diff_val->create_date=date("Y-m-d H:i:s");
	    }
	    if (isset($request->min_sum)) $diff_val->min_sum=(float)$request->min_sum;
	    if (isset($request->max_sum)) $diff_val->max_sum=(float)$request->max_sum;
	    if (isset($request->value)) $diff_val->value=(float)$request->value;
		if (isset($request->round_for)) $diff_val->round_for=(float)$request->round_for;
	    if (isset($request->descr)) $diff_val->descr=$request->descr;
	    if (isset($request->price_type_id)) $diff_val->dict_price_type_id=$request->price_type_id;
	    $err=$diff_val->save();
	    //echo print_r($price_type,true);
	    switch($err) {
		case 10: $status="err"; $msg="Данные не изменились\n"; break;
		case 1: if (isset($request->price_type_id) && (int)$request->price_type_id>0){
                		$status="ok"; $msg="";//"Данные успешно изменены";
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


	public static function get_price_type_differencial_values($request) {
	    $db = DB::getInstance();
	    if(isset($request->price_type_id) && (int)$request->price_type_id>0) $price_type_id=(int)$request->price_type_id;
	    $sql="select * from dict_price_type_differencial_values where dict_price_type_id=?i";
	    $res=$db->getAll($sql,$price_type_id);
	    if (is_array($res) && count($res)>0){
		$ret['status']="ok";
		$ret['err']="";
		$ret['price_type_differencial_values']=$res;
		$ret['msg']="";
	    }
	    else {
		$ret['status']="ok";
		$ret['err']="";
		$ret['price_type_differencial_values']=array();
		$ret['msg']="";
	    }
	    if ($ret['status']=="err") return self::_error_arr($ret['err']);
	    else return $ret;
	}

	public static function get_price_type_differencial_value($request) {
	    $db = DB::getInstance();
	    $sql="select * from dict_price_type_differencial_values where id=?i";
	    $res=$db->getRow($sql,(int)$request->id);
	    if (is_array($res) && count($res)>0){
		$ret['status']="ok";
		$ret['err']="";
		$ret['price_type_differencial_value'][0]=$res;
		$ret['msg']="";
	    }
	    else {
		$ret['status']="ok";
		$ret['err']="";
		$ret['price_type_differencial_value']=array();
		$ret['msg']="";
	    }
	    if ($ret['status']=="err") return self::_error_arr($ret['err']);
	    else return $ret;
	}

	public static function delete_price_type_differencial_value($request) {
	    $db = DB::getInstance();
	    if ((int)$_SESSION['roles']!=1) {
		return self::_error_arr("У Вас нет прав для удаления");
	    }
	    if (isset($request->price_type_differencial_value_id)) {$ptdv_id=(int)$request->price_type_differencial_value_id;}
	    if (isset($ptdv_id) && $ptdv_id>0){
		$res2=$db->query("delete from dict_price_type_differencial_values where id=?i and dict_price_type_id in (select id from dict_price_type where main_company=?i)",$ptdv_id,$_SESSION['main_company']);
		//echo "delete from sklad where id=".$sklad_id." and company_id in (select company_id from user_companys where main_company_id=0 and user_id=".$_SESSION['user_id'].")";
		if ($res2){
		    $ret['status']="ok";
		    $ret['msg']="Дифференциальное значение типа цены успешно удален";
		}
		else {
		    $ret['status']="err";
		    $ret['err']="не удалось удалить Дифференциальное значение типа цены";
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