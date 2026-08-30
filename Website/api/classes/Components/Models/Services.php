<?php

namespace Sort1API\Components\Models;

use Sort1API\Components\DB;
use Sort1API\Components\Service;
//require 'vendor/autoload.php';

/**
* 	Model Class to work with Orders instatnces (orders table)
*   Class can use inner Database (DB::getInstance())
*   or
* 	external api to receive data (using HTTPRequest class or extened)
*
*/


class Services extends Model {

        public static function check_roles($role){
                $main_user=new User((int)$_SESSION['user_id']);
                if ($main_user->roles<=$role) return $role;
                else return $main_user->roles;
            }

        public static function save_service($request) {
          $db = DB::getInstance();
    	    if (isset($request->service_id)) $service_id=(int)$request->service_id;
    	    if (isset($service_id) && $service_id>0) {
    		      $service=new Service($service_id);
    	    }
    	    else
    		    $service=new Service(); 
    	    if (isset($request->address)) $service->address=$request->address;
    	    if (isset($request->name)) $service->name=$request->name;
			if (isset($request->sklad_id)) $service->sklad_id=(int)$request->sklad_id;
    	    if (isset($request->city_id)) $service->city_id=(int)$request->city_id;
    	    if (isset($request->city_name)) $service->city_name=$request->city_name;
			if ($service->sklad_id==0) return self::_error_arr("Не выбран склад с которого выдаются детали для автосервиса");
      	    if ($service->name=="") return self::_error_arr("Укажите наименование сервиса");
      	    $err=$service->save();
      	    switch($err) {
          		case 10: $status="err"; $msg="Данные не изменились\n"; break;
          		case 1: if (isset($request->service_id) && (int)$request->service_id>0){
                          		$status="ok"; $msg="";
                      		}
                      		else {
                          	    $status="ok"; $msg="";
                      		}
          			break;
          		default: $status="err"; $msg="error: ".$err."\n";
      	    }
            if ($status=="err") return self::_error_arr($msg);
            else return array("status"=>$status,"msg"=>$msg);
        }


	public static function get_services($request) {
	    $db = DB::getInstance();
	    $sql="SELECT s.*,sk.name as sklad_name
            FROM services s
			left join sklad sk on (sk.id=s.sklad_id)
            WHERE s.main_company_id=?i AND s.deleted=0";
	    $res=$db->getAll($sql,$_SESSION['main_company']);
	    if (is_array($res) && count($res)>0){
    		$ret['status']="ok";
    		$ret['err']="";
    		$ret['services']=$res;
			$ret['my_service_id']=$db->getOne("select my_service_id from users where id=?i",$_SESSION['user_id']);
    		$ret['msg']="";
		}
		else {
			$ret['status']="ok";
    		$ret['err']="";
    		$ret['services']=array();
    		$ret['msg']="";
		}
	    if ($ret['status']=="err") return self::_error_arr($ret['err']);
	    else return $ret;
	}

	public static function get_delivery_services($request) {
	    $db = DB::getInstance();
	    $sql="select id,descr,address,name from services where company_id=?i and deleted=0 and punkt_vydachi=1";
	    $res=$db->getAll($sql,$_SESSION['main_company']);
	    if (is_array($res) && count($res)>0){
		$ret['status']="ok";
		$ret['err']="";
		$ret['services']=$res;
		$ret['msg']="";
	    }
	    if ($ret['status']=="err") return self::_error_arr($ret['err']);
	    else return $ret;
	}

	public static function get_service($request) {
	    $db = DB::getInstance();
	    if (isset($request->service_id) && (int)$request->service_id>0){
			$service_id=(int)$request->service_id;
			$service=new Service($service_id);
	    }
	    else {
			return self::_error_arr("не указан id сервиса");
	    }
		$sql="select s.*,sk.name as sklad_name from services s 
		left join sklad sk on (sk.id=s.sklad_id)
		where s.id=?i and s.main_company_id in (select company_id from user_companys where user_id=?i and main_company_id=0) and s.deleted=0";
	    $res=$db->getRow($sql,$service_id,(int)$_SESSION['user_id']);
	    if ($res['id']>0){
    		$ret['status']="ok";
    		$ret['err']="";
    		$ret['service']=$res;
    		$ret['msg']="";
	    }
	    if ($ret['status']=="err") return self::_error_arr($ret['err']);
	    else return $ret;
	}

	public static function delete_service($request) {
	    $fields="";
	    $db = DB::getInstance();
	    if ((int)$_SESSION['roles']!=1) {
		      return self::_error_arr("У Вас нет прав для удаления");
	    }
	    if (isset($request->service_id)) {$service_id=(int)$request->service_id;}
	    if (isset($service_id) && $service_id>0){
    		$res2=$db->query("update services set deleted=1 where id=?i and main_company_id in (select company_id from user_companys where main_company_id=0 and user_id=?i)",$service_id,$_SESSION['user_id']);
    		//echo "delete from service where id=".$service_id." and company_id in (select company_id from user_companys where main_company_id=0 and user_id=".$_SESSION['user_id'].")";
    		if ($res2){
    		    $ret['status']="ok";
    		    $ret['msg']="";
    		}
    		else {
    		    $ret['status']="err";
    		    $ret['err']="не удалось удалить Сервис";
    		}
	    }
	    else {
    		$ret['status']="err";
    		$ret['err']="нет данных";
	    }
		if((int)$_SESSION['my_service_id']==(int)$request->service_id){
			unset($_SESSION['my_service_id']);
			session_write_close();
			$db->query("update users set my_service_id=0 where my_service_id=?i",(int)$request->service_id);
		}
	    if ($ret['status']=="err") return self::_error_arr($ret['err']);
	    else return array("status"=>$ret['status'],"msg"=>$ret['msg'],"err"=>"");
	}


}



?>
