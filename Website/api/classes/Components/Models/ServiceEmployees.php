<?php

namespace Sort1API\Components\Models;

use Sort1API\Components\DB;
use Sort1API\Components\ServiceEmployee;

/**
* 	Model Class to work with Orders instatnces (orders table)
*   Class can use inner Database (DB::getInstance())
*   or
* 	external api to receive data (using HTTPRequest class or extened)
* 
*/


class ServiceEmployees extends Model {

        public static function check_roles($role){
                $main_user=new User((int)$_SESSION['user_id']);
                if ($main_user->roles<=$role) return $role;
                else return $main_user->roles;
            }

        public static function save_service_employee($request) {
            $db = DB::getInstance();
			if (isset($request->service_employee_id)) $service_employee_id=(int)$request->service_employee_id;
			if (isset($service_employee_id) && $service_employee_id>0) {
				$service_employee=new ServiceEmployee($service_employee_id);
				if($service_employee->main_company_id!=$_SESSION['main_company']) return self::_error_arr("Это не ваш работник");
			}
			else {
				$service_employee=new ServiceEmployee();
				//$service_emplyee->main_company_id=$_SESSION['main_company']; // добавляется в самом классе
			}
			if (isset($request->name)) {
				$service_employee->name=$request->name;
			}
			if (isset($request->lastname)){
				$service_employee->lastname=$request->lastname;
			}
			if (isset($request->surname)) $service_employee->surname=$request->surname;
			if (isset($request->service_employee_phone)) $service_employee->phone=$request->service_employee_phone;
			if (isset($request->descr)) $service_employee->descr=$request->descr;
			//if (isset($request->job_code)) $service_emplyee->job_code=$request->job_code;
			//if (isset($request->default_employee)) $service_emplyee->default_employee=$request->default_employee;
			$err=$service_employee->save();
			switch($err) {
			case 10: $status="err"; $msg="Данные не изменились\n"; break;
			case 1: if (isset($request->service_employee_id) && (int)$request->service_employee_id>0){
							$status="ok"; $msg="Данные успешно изменены";
						}
						else {
							$status="ok"; $msg="Новый работник добавлен";
						}
				break;
			default: $status="err"; $msg="error: ".$err."\n";
			}
            if ($status=="err") return self::_error_arr($msg);
            else return array("status"=>$status,"msg"=>$msg);
        }

	public static function get_service_employee($request) {
	    $db = DB::getInstance();
	    if (isset($request->service_employee_id) && (int)$request->service_employee_id>0) $service_employee_id=(int)$request->service_employee_id;
	    else return self::_error_arr("Не указан id работника");
	    $sql="select * from service_employees where id=?i and main_company_id=?i and deleted=0";
	    $res=$db->getRow($sql,$service_employee_id,$_SESSION['main_company']);
	    if ($res['id']>0){
		$ret['status']="ok";
		$ret['err']="";
		$ret['service_employee']=$res;
		if ($res['status']==0) {$ret['service_emplyee']['status_name']="Уволен";}
		if ($res['status']==1) {$ret['service_emplyee']['status_name']="Работает";}
			$ret['msg']="";
	    }
	    if ($ret['status']=="err") return self::_error_arr($ret['err']);
	    else return $ret;
	}

	public static function get_service_employees($request) {
	    $db = DB::getInstance();
		$sql="select sj.* from service_employees sj 
			where sj.main_company_id=?i and sj.deleted=0";
		if(!empty($request->search_service_employees)) {
			$sql.=" and (sj.lastname like ?s or sj.name like ?s)";
			$res=$db->getAll($sql,$_SESSION['company_id'],"%".$request->search_service_employees."%","%".$request->search_service_employees."%");
		}
		else
	    	$res=$db->getAll($sql,$_SESSION['company_id']);
	    if (is_array($res) && count($res)>0){
			$ret['status']="ok";
			$ret['err']="";
			$ret['service_employees']=$res;
			$ret['msg']="";
	    }
	    else {
			$ret['status']="ok";
			$ret['err']="";
			$ret['service_employees']=array();
			$ret['msg']="";
	    }
	    if ($ret['status']=="err") return self::_error_arr($ret['err']);
	    else return $ret;
	}

	public static function delete_service_employee($request) {
	    $fields="";
	    $db = DB::getInstance();
	    if ((int)$_SESSION['roles']>2) {
			return self::_error_arr("У Вас нет прав для удаления");
	    }
	    if (isset($request->service_employee_id)) {$service_employee_id=(int)$request->service_employee_id;}
	    if (isset($service_employee_id) && $service_employee_id>0){
			$res2=$db->query("update service_employees set deleted=1 where id=?i and main_company_id in (select company_id from user_companys where main_company_id=0 and user_id=?i)",$service_employee_id,$_SESSION['user_id']);
			//echo "delete from sklad where id=".$sklad_id." and company_id in (select company_id from user_companys where main_company_id=0 and user_id=".$_SESSION['user_id'].")";
			if ($res2){
				$ret['status']="ok";
				$ret['msg']="Работник успешно удален";
			}
			else {
				$ret['status']="err";
				$ret['err']="не удалось удалить Работника";
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