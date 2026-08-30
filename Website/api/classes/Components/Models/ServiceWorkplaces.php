<?php

namespace Sort1API\Components\Models;

use Sort1API\Components\DB;
use Sort1API\Components\ServiceWorkplace;

/**
* 	Model Class to work with Orders instatnces (orders table)
*   Class can use inner Database (DB::getInstance())
*   or
* 	external api to receive data (using HTTPRequest class or extened)
* 
*/


class ServiceWorkplaces extends Model {

        public static function check_roles($role){
                $main_user=new User((int)$_SESSION['user_id']);
                if ($main_user->roles<=$role) return $role;
                else return $main_user->roles;
            }

        public static function save_service_workplace($request) {
            $db = DB::getInstance();
			if (isset($request->service_workplace_id)) $service_workplace_id=(int)$request->service_workplace_id;
			if (isset($service_workplace_id) && $service_workplace_id>0) {
				$service_workplace=new ServiceWorkplace($service_workplace_id);
				if($service_workplace->main_company_id!=$_SESSION['main_company']) return self::_error_arr("это не ваше рабочее место");
			}
			else {
				$service_workplace=new ServiceWorkplace();
				$service_workplace->main_company_id=$_SESSION['main_company'];
			}
			if (isset($request->name)) {
				$service_workplace->name=$request->name;
			}
			if (isset($request->shtrih_code)) $service_workplace->shtrih_code=$request->shtrih_code;
			if (isset($request->descr)) $service_workplace->descr=$request->descr;
			$service_workplace->service_id=(int)$_SESSION['my_service_id'];
			$err=$service_workplace->save();
			switch($err) {
			case 10: $status="err"; $msg="Данные не изменились\n"; break;
			case 1: if (isset($request->service_workplace_id) && (int)$request->service_workplace_id>0){
							$status="ok"; $msg="Данные успешно изменены";
						}
						else {
							$status="ok"; $msg="Новое рабочее место добавлено";
						}
				break;
			default: $status="err"; $msg="error: ".$err."\n";
			}
            if ($status=="err") return self::_error_arr($msg);
            else return array("status"=>$status,"msg"=>$msg);
        }

	public static function get_service_workplace($request) {
	    $db = DB::getInstance();
	    if (isset($request->service_workplace_id) && (int)$request->service_workplace_id>0) $service_workplace_id=(int)$request->service_workplace_id;
	    else return self::_error_arr("Не указан id рабочего места");
	    $sql="select * from service_workplaces where id=?i and main_company_id=?i and deleted=0 and service_id=?i";
	    $res=$db->getRow($sql,$service_workplace_id,$_SESSION['main_company'],(int)$_SESSION['my_service_id']);
	    if ($res['id']>0){
		$ret['status']="ok";
		$ret['err']="";
		$ret['service_workplace']=$res;
		if ($res['status']==0) {$ret['service_workplace']['status_name']="Неактивен";}
		if ($res['status']==1) {$ret['service_workplace']['status_name']="Активен";}
			$ret['msg']="";
	    }
	    if ($ret['status']=="err") return self::_error_arr($ret['err']);
	    else return $ret;
	}

	public static function get_service_workplaces($request) {
	    $db = DB::getInstance();
		$sql="select * from service_workplaces 
			where main_company_id=?i and deleted=0 and service_id=?i";
		if(!empty($request->search_service_workplaces)) {
			$sql.=" and sj.name like ?s";
			$res=$db->getAll($sql,$_SESSION['main_company'],(int)$_SESSION['my_service_id'],"%".$request->search_service_workplaces."%");
		}
		else
	    	$res=$db->getAll($sql,$_SESSION['main_company'],(int)$_SESSION['my_service_id']);
	    if (is_array($res) && count($res)>0){
			$ret['status']="ok";
			$ret['err']="";
			$ret['service_workplaces']=$res;
			$ret['msg']="";
	    }
	    else {
			$ret['status']="ok";
			$ret['err']="";
			$ret['service_workplaces']=array();
			$ret['msg']="";
	    }
	    if ($ret['status']=="err") return self::_error_arr($ret['err']);
	    else return $ret;
	}

	public static function delete_service_workplace($request) {
	    $fields="";
	    $db = DB::getInstance();
	    if ((int)$_SESSION['roles']>2) {
			return self::_error_arr("У Вас нет прав для удаления");
	    }
	    if (isset($request->service_workplace_id)) {$service_workplace_id=(int)$request->service_workplace_id;}
	    if (isset($service_workplace_id) && $service_workplace_id>0){
			$res2=$db->query("update service_workplaces set deleted=1 where id=?i and main_company_id in (select company_id from user_companys where main_company_id=0 and user_id=?i)",$service_workplace_id,$_SESSION['user_id']);
			//echo "delete from sklad where id=".$sklad_id." and company_id in (select company_id from user_companys where main_company_id=0 and user_id=".$_SESSION['user_id'].")";
			if ($res2){
				$ret['status']="ok";
				$ret['msg']="Работа успешно удалена";
			}
			else {
				$ret['status']="err";
				$ret['err']="не удалось удалить Работу";
			}
	    }
	    else {
		$ret['status']="err";
		$ret['err']="нет данных";
	    }
	    if ($ret['status']=="err") return self::_error_arr($ret['err']);
	    else return array("status"=>$ret['status'],"msg"=>$ret['msg'],"err"=>"");
	}

	public static function get_zakaz_workplaces($request){
		$db = DB::getInstance();
		$sql="select zj.*,sj.name,sj.price as job_price from zakaz_jobs zj 
		left join service_workplaces sj on (zj.job_id=sj.id)
		where zj.zakaz_id=?i and zj.zakaz_id in (select id from zakaz where id=?i and main_company_id=?i)";
		$res=$db->getAll($sql,$request->zakaz_id,$request->zakaz_id,$_SESSION['main_company']);
		if($res){
			return array("status"=>"ok","zakaz_jobs"=>$res,"msg"=>"");
		}
		else return array("status"=>"ok","zakaz_jobs"=>array(),"msg"=>"");
	}
}



?>