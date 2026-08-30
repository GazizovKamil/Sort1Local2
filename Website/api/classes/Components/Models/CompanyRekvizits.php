<?php

namespace Sort1API\Components\Models;

use Sort1API\Components\DB;
use Sort1API\Components\CompanyRekvizit;

/**
* 	Model Class to work with Orders instatnces (orders table)
*   Class can use inner Database (DB::getInstance())
*   or
* 	external api to receive data (using HTTPRequest class or extened)
*
*/


class CompanyRekvizits extends Model {

        public static function check_roles($role){
                $main_user=new User((int)$_SESSION['user_id']);
                if ($main_user->roles<=$role) return $role;
                else return $main_user->roles;
            }

        public static function save_company_rekvizit($request) {
            $db = DB::getInstance();
	    if (isset($request->id)) $id=(int)$request->id;
	    if (isset($id) && $id>0) {
			$da=new CompanyRekvizit($id);
			$da->update_date=date("Y-m-d H:i:s");
	    }
	    else {
			$da=new CompanyRekvizit();
			$da->create_date=date("Y-m-d H:i:s");
			$da->main_company=$_SESSION['main_company'];
	    }
	    if (isset($request->company_id)) $da->company_id=$request->company_id;
	    if (isset($request->rs)) $da->rs=$request->rs;
	    if (isset($request->ks)) $da->ks=$request->ks;
	    if (isset($request->bik)) $da->bik=$request->bik;
	    if (isset($request->bank)) $da->bank=$request->bank;
	    $da->user_id=$_SESSION['user_id'];
	    $err=$da->save();
	    //echo print_r($price_type,true);
	    switch($err) {
		case 10: $status="err"; $msg="Данные не изменились\n"; break;
		case 1: if (isset($request->id) && (int)$request->id>0){
                		$status="ok"; $msg="Данные успешно изменены";
            		}
            		else {
                	    $status="ok"; $msg="Новый расчетный счет добавлен";
            		}
			break;
		default: $status="err"; $msg="error: ".$err."\n";
	    }
            if ($status=="err") return self::_error_arr($msg);
            else return array("status"=>$status,"msg"=>$msg,"id"=>$da->id);
        }


	public static function get_company_rekvizits($request) {
	    $db = DB::getInstance();
	    if(empty($request->company_id)) return self::_error_arr("Не указана компания");
	    $sql="select * from company_rekvizits where company_id=?i and deleted=0 and main_company=?i";
	    $res=$db->getAll($sql,$request->company_id,$_SESSION['main_company']);
	    if (is_array($res) && count($res)>0){
    		$ret['status']="ok";
    		$ret['err']="";
    		$ret['rekvizits']=$res;
    		$ret['msg']="";
    	    }
    	    else {
    		$ret['status']="ok";
    		$ret['err']="";
    		$ret['rekvizits']=array();
    		$ret['msg']="";
	    }
	    if ($ret['status']=="err") return self::_error_arr($ret['err']);
	    else return $ret;
	}

	public static function get_company_rekvizit($request) {
	    $db = DB::getInstance();
	    $sql="select * from company_rekvizits where id=?i and deleted=0";
	    $res=$db->getRow($sql,(int)$request->id);
	    if (is_array($res) && count($res)>0){
    		$ret['status']="ok";
    		$ret['err']="";
    		$ret['rekvizits']=$res;
    		$ret['msg']="";
	    }
	    else {
    		$ret['status']="err";
    		$ret['err']="Невозможно найти адрес доставки";
	    }
	    if ($ret['status']=="err") return self::_error_arr($ret['err']);
	    else return $ret;
	}

	public static function delete_company_rekvizit($request) {
	    $db = DB::getInstance();
	    $client_companys=$db->getCol("select company_id from user_companys where main_company_id=?i",$_SESSION['main_company']);
		$client_companys[]=$_SESSION['main_company'];
	    if (isset($request->id)) {$da_id=(int)$request->id;}
	    if (isset($da_id) && $da_id>0){
        $res2=$db->query("update company_rekvizits set deleted=1 where id=?i and company_id in (?a)",$da_id,$client_companys);
    		//echo "delete from sklad where id=".$sklad_id." and company_id in (select company_id from user_companys where main_company_id=0 and user_id=".$_SESSION['user_id'].")";
    		if ($res2){
    		    $ret['status']="ok";
    		    $ret['msg']="Расчетный счет успешно удален";
    		}
    		else {
    		    $ret['status']="err";
    		    $ret['err']="не удалось удалить расчетный счет";
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
