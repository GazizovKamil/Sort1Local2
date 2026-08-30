<?php

namespace Sort1API\Components\Models;

use Sort1API\Components\DB;
use Sort1API\Components\Basket;

/**
* 	Model Class to work with Orders instatnces (orders table)
*   Class can use inner Database (DB::getInstance())
*   or
* 	external api to receive data (using HTTPRequest class or extened)
* 
*/

// NOT USED
class Baskets extends Model {

        public static function check_roles($role){
                $main_user=new User((int)$_SESSION['user_id']);
                if ($main_user->roles<=$role) return $role;
                else return $main_user->roles;
            }

    public static function save_basket($request) {
            $db = DB::getInstance();
	    if (isset($request->sklad_id)) $sklad_id=(int)$request->sklad_id;
	    if (isset($sklad_id) && $sklad_id>0) {
		$sklad=new Sklad($sklad_id);
	    }
	    else 
		$sklad=new Sklad();
	    if (isset($request->company_id) && (int)$request->company_id>0) {
		$companys=$db->getCol("select company_id from user_companys where user_id=?i and main_company_id=0",$_SESSION['user_id']);
		if ($companys && in_array($request->company_id,$companys))
		    $sklad->company_id=(int)$request->company_id;
		else {
		    return self::_error_arr("Нельзя добавить склад к чужой компании");
		}
	    }
	    else $sklad->company_id=$_SESSION['main_company'];
	    if (isset($request->address)) $sklad->address=$request->address;
	    if (isset($request->descr)) $sklad->descr=$request->descr;
	    if (isset($request->name)) $sklad->name=$request->name;
	    if (isset($request->status)) $sklad->status=$request->status;
	    if (isset($request->default_markup)) {
		if ($sklad->default_markup!=$request->default_markup){
		    $sql="update sklad_details set default_markup=?i where sklad_id=?i";
		    $res=$db->query($sql,$request->default_markup,$sklad->id);
		    // poka ne znau
		}
		$db->query("update sklad_details set default_markup=?i where sklad_id=?i",$request->default_markup,$sklad->id);
		$sklad->default_markup=$request->default_markup;
	    }
	    if ($sklad->name=="") return self::_error_arr("Укажите наименование склада");
	    $err=$sklad->save();
	    switch($err) {
			case 10: $status="ок"; $msg="Данные не изменились\n"; break;
			case 1: if (isset($request->sklad_id) && (int)$request->sklad_id>0){
							$status="ok"; $msg="";
						}
						else {
							$status="ok"; $msg="Новый склад добавлен";
						}
				break;
			default: $status="err"; $msg="error: ".$err."\n";
	    }
        if ($status=="err") return self::_error_arr($msg);
        else return array("status"=>$status,"msg"=>$msg);
    }


	public static function get_sklads($request) {
	    $db = DB::getInstance();
	    $sql="select * from sklad where company_id=?i and deleted=0";
	    $res=$db->getAll($sql,$_SESSION['main_company']);
	    if (is_array($res) && count($res)>0){
		$ret['status']="ok";
		$ret['err']="";
		$ret['sklads']=$res;
		$ret['msg']="";
	    }
	    if ($ret['status']=="err") return self::_error_arr($ret['err']);
	    else return $ret;
	}

	public static function get_sklad($request) {
	    $db = DB::getInstance();
	    $sql="select * from sklad where id=?i and company_id in (select company_id from user_companys where user_id=?i and main_company_id=0) and deleted=0";
	    if (isset($request->sklad_id) && (int)$request->sklad_id>0){
		$sklad_id=(int)$request->sklad_id;
		$sklad=new Sklad($sklad_id);
	    }
	    else {
		return self::_error_arr("не указан id склада");
	    }
	    $res=$db->getRow($sql,$sklad_id,(int)$_SESSION['user_id']);
	    if ($res['id']>0){
		$ret['status']="ok";
		$ret['err']="";
		$ret['sklad']=$res;
		$ret['msg']="";
	    }
	    if ($ret['status']=="err") return self::_error_arr($ret['err']);
	    else return $ret;
	}

	public static function delete_sklad($request) {
	    $fields="";
	    $db = DB::getInstance();
	    if ((int)$_SESSION['roles']!=1) {
		return self::_error_arr("У Вас нет прав для удаления");
	    }
	    if (isset($request->sklad_id)) {$sklad_id=(int)$request->sklad_id;}
	    if (isset($sklad_id) && $sklad_id>0){
		$res2=$db->query("update sklad set deleted=1 where id=?i and company_id in (select company_id from user_companys where main_company_id=0 and user_id=?i)",$sklad_id,$_SESSION['user_id']);
		//echo "delete from sklad where id=".$sklad_id." and company_id in (select company_id from user_companys where main_company_id=0 and user_id=".$_SESSION['user_id'].")";
		if ($res2){
		    $ret['status']="ok";
		    $ret['msg']="Склад успешно удален";
		}
		else {
		    $ret['status']="err";
		    $ret['err']="не удалось удалить Склад";
		}
	    }
	    else {
		$ret['status']="err";
		$ret['err']="нет данных";
	    }
	    if ($ret['status']=="err") return self::_error_arr($ret['err']);
	    else return array("status"=>$ret['status'],"msg"=>$ret['msg'],"err"=>"");
	}

}



?>