<?php

namespace Sort1API\Components\Models;

use Sort1API\Components\DB;
use Sort1API\Components\ServiceJob;

/**
* 	Model Class to work with Orders instatnces (orders table)
*   Class can use inner Database (DB::getInstance())
*   or
* 	external api to receive data (using HTTPRequest class or extened)
* 
*/


class ServiceJobs extends Model {

        public static function check_roles($role){
                $main_user=new User((int)$_SESSION['user_id']);
                if ($main_user->roles<=$role) return $role;
                else return $main_user->roles;
            }

		public static function save_jobes($request){
			if(isset($request->jobes)){
				foreach($request->jobes as $jkey=>$jval){
					$ret[$jval['name']]=self::save_service_job((object)$jval);
				}
				return array("status"=>"ok","err"=>"","ret"=>$ret);
			}
		}

        public static function save_service_job($request) {
            $db = DB::getInstance();
			if (isset($request->service_job_id)) $service_job_id=(int)$request->service_job_id;
			if (isset($service_job_id) && $service_job_id>0) {
				$service_job=new ServiceJob($service_job_id);
				if($service_job->main_company_id!=$_SESSION['main_company']) return self::_error_arr("Не ваша эта работа");
			}
			else {
				$service_job=new ServiceJob();
				$service_job->main_company_id=$_SESSION['main_company'];
			}
			if (isset($request->name)) {
				$service_job->name=$request->name;
			}
			if (isset($request->price)){
				$service_job->price=$request->price;
			}
			if (isset($request->shtrih_code)) $service_job->shtrih_code=$request->shtrih_code;
			if (isset($request->job_type)) $service_job->job_type=$request->job_type;
			else $service_job->job_type=1;
			if (isset($request->descr)) $service_job->descr=$request->descr;
			if (isset($request->job_code)) $service_job->job_code=$request->job_code;
			if (isset($request->default_employee)) $service_job->default_employee=$request->default_employee;
			if(isset($request->only_in_this_service) && $request->only_in_this_service=="on") $service_job->service_id=$_SESSION['my_service_id'];
			else $service_job->service_id=0;
			//$service_job->service_id=$_SESSION['my_service_id'];
			$err=$service_job->save();
			switch($err) {
			case 10: $status="err"; $msg="Данные не изменились\n"; break;
			case 1: if (isset($request->service_job_id) && (int)$request->service_job_id>0){
							$status="ok"; $msg="";
						}
						else {
							$status="ok"; $msg="";
						}
				break;
			default: $status="err"; $msg="error: ".$err."\n";
			}
            if ($status=="err") return self::_error_arr($msg);
            else return array("status"=>$status,"msg"=>$msg,"service_job_id"=>(int)$service_job->id);
        }

	public static function get_service_job($request) {
	    $db = DB::getInstance();
	    if (isset($request->service_job_id) && (int)$request->service_job_id>0) $service_job_id=(int)$request->service_job_id;
	    else return self::_error_arr("Не указан id работы");
	    $sql="select * from service_jobs where id=?i and main_company_id=?i and job_type=?i and deleted=0";
		if(empty($request->job_type)) $job_type=1;
		else $job_type=$request->job_type;
	    $res=$db->getRow($sql,$service_job_id,$_SESSION['main_company'],$job_type);
	    if ($res['id']>0){
		$ret['status']="ok";
		$ret['err']="";
		$ret['service_job']=$res;
		$service_employees=$db->getInd("id","select * from service_employees where main_company_id=?i",$_SESSION['main_company']);
		if($service_employees) $ret['service_employees']=$service_employees;
		else $ret['service_employees']=array();
		if ($res['status']==0) {$ret['service_job']['status_name']="Неактивен";}
		if ($res['status']==1) {$ret['service_job']['status_name']="Активен";}
			$ret['msg']="";
	    }
	    if ($ret['status']=="err") return self::_error_arr($ret['err']);
	    else return $ret;
	}

	public static function get_service_jobs($request) {
	    $db = DB::getInstance();
		$sql="select sj.*,se.lastname as employee_lastname,se.surname as employee_surname,se.name as employee_name from service_jobs sj 
			left join service_employees se on (se.id=sj.default_employee)
			where sj.main_company_id=?i and sj.deleted=0 and (sj.service_id=0 or sj.service_id=?i) and job_type=?i ?p";
		$parsed="";
		if(!empty($request->search_service_jobs)) {
			$parsed.=$db->parse(" and sj.name like ?s","%".$request->search_service_jobs."%");
		}
	    if(empty($request->job_type)) $job_type=1;
		else $job_type=$request->job_type;
		$res=$db->getAll($sql,$_SESSION['main_company'],$_SESSION['my_service_id'],$job_type,$parsed);
	    if (is_array($res) && count($res)>0){
			$ret['status']="ok";
			$ret['err']="";
			$ret['service_jobs']=$res;
			$ret['msg']="";
	    }
	    else {
			$ret['status']="ok";
			$ret['err']="";
			$ret['service_jobs']=array();
			$ret['msg']="";
	    }
	    if ($ret['status']=="err") return self::_error_arr($ret['err']);
	    else return $ret;
	}

	public static function delete_service_job($request) {
	    $fields="";
	    $db = DB::getInstance();
	    if ((int)$_SESSION['roles']>2) {
			return self::_error_arr("У Вас нет прав для удаления");
	    }
	    if (isset($request->service_job_id)) {$service_job_id=(int)$request->service_job_id;}
	    if (isset($service_job_id) && $service_job_id>0){
			$res2=$db->query("update service_jobs set deleted=1 where id=?i and main_company_id in (select company_id from user_companys where main_company_id=0 and user_id=?i)",$service_job_id,$_SESSION['user_id']);
			//echo "delete from sklad where id=".$sklad_id." and company_id in (select company_id from user_companys where main_company_id=0 and user_id=".$_SESSION['user_id'].")";
			if ($res2){
				$ret['status']="ok";
				$ret['msg']="";
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

	public static function get_zakaz_jobs($request){
		$db = DB::getInstance();
		$sql="select zj.*,sj.name,sj.price as job_price from zakaz_jobs zj 
		left join service_jobs sj on (zj.job_id=sj.id)
		where zj.zakaz_id=?i and zj.zakaz_id in (select id from zakaz where id=?i and main_company_id=?i)";
		$res=$db->getAll($sql,$request->zakaz_id,$request->zakaz_id,$_SESSION['main_company']);
		if($res){
			return array("status"=>"ok","zakaz_jobs"=>$res,"msg"=>"");
		}
		else return array("status"=>"ok","zakaz_jobs"=>array(),"msg"=>"");
	}
}



?>