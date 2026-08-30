<?php

namespace Sort1API\Components\Models;

use Sort1API\Components\DB;
use Sort1API\Components\PriceType;

/**
* 	Model Class to work with Orders instatnces (orders table)
*   Class can use inner Database (DB::getInstance())
*   or
* 	external api to receive data (using HTTPRequest class or extened)
*
*/


class PriceTypes extends Model {

        public static function check_roles($role){
                $main_user=new User((int)$_SESSION['user_id']);
                if ($main_user->roles<=$role) return $role;
                else return $main_user->roles;
            }

        public static function save_price_type($request) {
            $db = DB::getInstance();
      	    if (isset($request->price_type_id)) $price_id=(int)$request->price_type_id;
      	    if (isset($price_id) && $price_id>0) {
          		$price_type=new PriceType($price_id);
          		$price_type->update_date=date("Y-m-d H:i:s");
      	    }
      	    else {
          		$price_type=new PriceType();
          		$price_type->create_date=date("Y-m-d H:i:s");
      	    }
      	    if (isset($request->proc)) {
          		if(isset($price_id) && $price_type->proc!=$request->proc){
          		    $price_lists=$db->getCol("select id from price_list where price_type=?i",$price_id);
          		    $sklads=$db->getCol("select id from sklad where price_type=?i",$price_id);
          		    $db->query("update price_list set default_markup=?i where price_type=?i",$request->proc,$price_id);
          		    $db->query("update sklad set default_markup=?i where price_type=?i",$request->proc,$price_id);
          		    $db->query("update price_list_details set default_markup=?i where price_list_id in (?b)",$request->proc,$price_lists);
          		    $db->query("update sklad_details set default_markup=?i where sklad_id in (?b)",$request->proc,$sklads);
          		}
          		$price_type->proc=$request->proc;
      	    }
      	    if (isset($request->type)) $price_type->type=(int)$request->type;
			else {
				return array("status"=>"ok","msg"=>"","price_type_id"=>0);
			}
			if (isset($request->round_for)) $price_type->round_for=(int)$request->round_for;
			if (isset($request->use_sum_trade) && $request->use_sum_trade=="on") $price_type->use_sum_trade=1;
			else $price_type->use_sum_trade=0;
      	    if (isset($request->descr)) $price_type->descr=$request->descr;
      	    $price_type->main_company=(int)$_SESSION['main_company'];
      	    $err=$price_type->save();
      	    //echo print_r($price_type,true);
      	    switch($err) {
          		case 10: $status="err"; $msg="Данные не изменились\n"; break;
          		case 1: if (isset($request->price_type_id) && (int)$request->price_type_id>0){
                          		$status="ok"; $msg="Данные успешно изменены";
                      		}
                      		else {
                          	    $status="ok"; $msg="";
                      		}
          			break;
          		default: $status="err"; $msg="error: ".$err."\n";
      	    }
            if ($status=="err") return self::_error_arr($msg);
            else return array("status"=>$status,"msg"=>$msg,"price_type_id"=>$price_type->id);
        }


	public static function get_price_types($request) {
	    $db = DB::getInstance();
	    if(isset($request->price_type) && (int)$request->price_type==2) $price_type_type=1;
	    else $price_type_type=-1;
      if($_SESSION['roles']<10){
	       $sql="select * from dict_price_type where type in (select id from dict_price_type_type where type=?i) and main_company=?i and deleted=0";
	       $res=$db->getAll($sql,$price_type_type,$_SESSION['company_id']);
      }
      else {
        $sql="select * from dict_price_type where type in (select id from dict_price_type_type where type=?i) and main_company=?i and deleted=0";
        $res=$db->getAll($sql,$price_type_type,$_SESSION['main_company']);
      }
	    if (is_array($res) && count($res)>0){
    		$ret['status']="ok";
    		$ret['err']="";
    		$ret['price_types']=$res;
    		$ret['msg']="";
	    }
	    else {
    		$ret['status']="ok";
    		$ret['err']="";
    		$ret['price_types']=array();
    		$ret['msg']="";
	    }
	    if ($ret['status']=="err") return self::_error_arr($ret['err']);
	    else return $ret;
	}

	public static function get_price_type($request) {
	    $db = DB::getInstance();
		if($_SESSION['roles']<10){
			$sql="select * from dict_price_type where id=?i and main_company=?i and deleted=0";
			$res=$db->getRow($sql,(int)$request->price_type_id,$_SESSION['company_id']);
		}
		else {
			$sql="select * from dict_price_type where id=?i and main_company=?i and deleted=0";
			$res=$db->getRow($sql,(int)$request->price_type_id,$_SESSION['main_company']);
		}
		if(isset($request->company_id) && (int)$request->company_id>0){
			$ret['company_balance']=$db->getRow("select * from company_balance where company_id=?i and main_company_id=?i",(int)$request->company_id,$_SESSION['main_company']);
		}
	    if (is_array($res) && count($res)>0){
    		$ret['status']="ok";
    		$ret['err']="";
			$ret['price_type']=$res;
			if((int)$res['type']==3 || (int)$res['type']==4){
				$ret['price_type']['differencial_values']=$db->getAll("select min_sum,max_sum,value,round_for from dict_price_type_differencial_values where dict_price_type_id=?i",$res['id']);
			}
    		$ret['msg']="";
	    }
	    else {
			$ret['status']="ok";
			$ret['price_type']['proc']=0;
			$ret['price_type']['descr']="Без скидки";
			$ret['msg']="";
    		//$ret['status']="err";
    		//$ret['err']="Невозможно найти тип цен";
	    }
	    if ($ret['status']=="err") return self::_error_arr($ret['err']);
	    else return $ret;
	}

	public static function delete_price_type($request) {
	    $db = DB::getInstance();
	    if ((int)$_SESSION['roles']!=1) {
		return self::_error_arr("У Вас нет прав для удаления");
	    }
	    if (isset($request->price_type_id)) {$pt_id=(int)$request->price_type_id;}
	    if (isset($pt_id) && $pt_id>0){
		$res2=$db->query("update dict_price_type set deleted=1 where id=?i and main_company in (select company_id from user_companys where main_company_id=0 and user_id=?i)",$pt_id,$_SESSION['user_id']);
		//echo "delete from sklad where id=".$sklad_id." and company_id in (select company_id from user_companys where main_company_id=0 and user_id=".$_SESSION['user_id'].")";
		if ($res2){
		    $ret['status']="ok";
		    $ret['msg']="Тип цены успешно удален";
		}
		else {
		    $ret['status']="err";
		    $ret['err']="не удалось удалить тип цены";
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
