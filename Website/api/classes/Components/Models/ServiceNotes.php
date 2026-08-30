<?php

namespace Sort1API\Components\Models;

use Sort1API\Components\DB;
use Sort1API\Components\ServiceNote;

/**
* 	Model Class to work with Orders instatnces (orders table)
*   Class can use inner Database (DB::getInstance())
*   or
* 	external api to receive data (using HTTPRequest class or extened)
* 
*/


class ServiceNotes extends Model {

        public static function check_roles($role){
                $main_user=new User((int)$_SESSION['user_id']);
                if ($main_user->roles<=$role) return $role;
                else return $main_user->roles;
            }

        public static function save_service_note($request) {
            $db = DB::getInstance();
			if (isset($request->service_note_id)) $service_note_id=(int)$request->service_note_id;
			if (isset($service_note_id) && $service_note_id>0) {
				$service_note=new ServiceNote($service_note_id);
				if($service_note->main_company_id!=$_SESSION['main_company']) return self::_error_arr("это не ваша запись");
			}
			else {
				$service_note=new ServiceNote();
				$service_note->main_company_id=$_SESSION['main_company'];
			}
			if (isset($request->company_id)) {

				$service_note->company_id=(int)$request->company_id;
			}
			if (isset($request->note_car_id)) $service_note->car_id=(int)$request->note_car_id;
			else $service_note->car_id=0;
			if (isset($request->note_employee_id)) $service_note->employee_id=$request->note_employee_id;
			if (isset($request->workplace_id)) $service_note->workplace_id=$request->workplace_id;
			if (isset($request->start_date)) $service_note->start_date=$request->start_date;
			if (isset($request->stop_date)) $service_note->stop_date=$request->stop_date;
			if(strtotime($request->start_date)>strtotime($request->stop_date)){
				return self::_error_arr("Время начала работ больше времени окончания работ");
			}
			if (isset($request->recommendations)) $service_note->recommendations=$request->recommendations;
			if (isset($request->cause)) $service_note->cause=$request->cause;
			if (isset($request->problems)) $service_note->problems=$request->problems;
			if (isset($request->mileage)) $service_note->mileage=(int)$request->mileage; else $service_note->mileage=0;
			if (isset($request->note_employee_id)) $service_note->employee_id=$request->note_employee_id;
			if (isset($request->zakaz_id)) $service_note->zakaz_id=(int)$request->zakaz_id; else $service_note->zakaz_id=0;
			if (isset($request->note_status)) $service_note->status=$request->note_status;
			//$service_note->user_id=$_SESSION['user_id'];
			$sql_add="";
			if($service_note->id>0) $sql_add=$db->parse(" and id<>?i",$service_note->id);
			$notes_in_time_start=$db->getAll("select id from service_notes where service_id=?i and main_company_id=?i and workplace_id=?i and start_date<=?s and stop_date>?s and deleted=0 ?p",$_SESSION['my_service_id'],$_SESSION['main_company'],$service_note->workplace_id,$service_note->start_date,$service_note->start_date,$sql_add);
			$notes_in_time_stop=$db->getAll("select id from service_notes where service_id=?i and main_company_id=?i and workplace_id=?i and start_date<?s and stop_date>=?s and deleted=0 ?p",$_SESSION['my_service_id'],$_SESSION['main_company'],$service_note->workplace_id,$service_note->stop_date,$service_note->stop_date,$sql_add);
			$note_in_this=$db->getAll("select id from service_notes where service_id=?i and main_company_id=?i and workplace_id=?i and start_date>=?s and stop_date<=?s and deleted=0 ?p",$_SESSION['my_service_id'],$_SESSION['main_company'],$service_note->workplace_id,$service_note->start_date,$service_note->stop_date,$sql_add);
			if($notes_in_time_start || $notes_in_time_stop || $note_in_this) return self::_error_arr("Идет наложение по времени с другой записью в сервис. Откорректируйте время");
			$service_note->service_id=(int)$_SESSION['my_service_id'];
			$err=$service_note->save();
			switch($err) {
			case 10: $status="err"; $msg="Данные не изменились\n"; break;
			case 1: if (isset($request->service_note_id) && (int)$request->service_note_id>0){
							$status="ok"; $msg="Данные успешно изменены";
						}
						else {
							$status="ok"; $msg="Новая запись добавлена";
						}
				break;
			default: $status="err"; $msg="error: ".$err."\n";
			}
            if ($status=="err") return self::_error_arr($msg);
            else return array("status"=>$status,"msg"=>$msg);
        }

	public static function get_service_note($request) {
	    $db = DB::getInstance();
	    if (isset($request->service_note_id) && (int)$request->service_note_id>0) {
			$service_note_id=(int)$request->service_note_id;
			$sql="select * from service_notes where id=?i and main_company_id=?i and deleted=0 and service_id=?i";
	    	$res=$db->getRow($sql,$service_note_id,$_SESSION['main_company'],(int)$_SESSION['my_service_id']);
		}
	    else {
			if (isset($request->workplace_id) && (int)$request->workplace_id>0){
				if(!empty($request->hour)){
					$start_date=date("Y-m-d", strtotime(str_replace('/','.',$request->date)))." ".(int)$request->hour.":".(int)$request->minute;
					$sql="select * from service_notes where workplace_id=?i and start_date=?s and main_company_id=?i and deleted=0 and service_id=?i";
					$res=$db->getRow($sql,$request->workplace_id,$start_date,$_SESSION['main_company'],(int)$_SESSION['my_service_id']);
				}
				else return self::_error_arr("Не указано время начала");
			}
			else return self::_error_arr("Не указан id рабочего места");
		} 
		//return self::_error_arr("Не указан id");
	    if ($res['id']>0){
            $ret['status']="ok";
            $ret['err']="";
            $ret['service_note']=$res;
			$ret['companys']=$db->getInd("id","select * from company where id=?i",$res['company_id']);
			$ret['statuses']=$db->getInd("id","select * from service_note_statuses");
			$ret['workplaces']=$db->getInd("id","select * from service_workplaces where main_company_id=?i and deleted=0",$_SESSION['main_company']);
			$ret['cars']=$db->getInd("id","select * from company_cars where id=?i",$res['car_id']);
			if((int)$res['employee_id']>0) $ret['employees']=$db->getInd("id","select * from service_employees where id=?i",$res['employee_id']);
			if((int)$res['zakaz_id']>0) $ret['zakaz_status']=$db->getOne("select status from zakaz where id=?i",(int)$res['zakaz_id']);
			$ret['msg']="";
	    }
        else {
            $ret['status']="ok";
            $ret['err']="";
			$ret['service_note']=array();
			$ret['statuses']=$db->getInd("id","select * from service_note_statuses");
			$ret['workplaces']=$db->getInd("id","select * from service_workplaces where main_company_id=?i and deleted=0",$_SESSION['main_company']);
			$ret['msg']="";
        }
	    if ($ret['status']=="err") return self::_error_arr($ret['err']);
	    else return $ret;
	}

	public static function get_service_notes($request) {
	    $db = DB::getInstance();
		$sql="select * from service_notes 
			where main_company_id=?i and deleted=0 and service_id=?i ?p";
        $sql_parsed="";
		if(!empty($request->search_service_notes)) {
			$sql_parsed.=$db->parse(" and company_id in 
                (select id from company where name like ?s and id in 
                    (select company_id from user_companys where main_company_id=?i))","%".$request->search_service_notes."%",$_SESSION['main_company_id']);
			//$res=$db->getAll($sql,$_SESSION['main_company'],"%".$request->search_service_notes."%");
		}
		//else
	    $res=$db->getAll($sql,$_SESSION['main_company'],(int)$_SESSION['my_service_id'],$sql_parsed);
		$company_ids=array();
		$car_ids=array();
		$zakaz_ids=array();
		foreach($res as $res_key=>$res_val){
			$company_ids[]=$res_val['company_id'];
			$car_ids[]=$res_val['car_id'];
			$zakaz_ids[]=$res_val['zakaz_id'];
		}
	    if (is_array($res) && count($res)>0){
			$ret['status']="ok";
			$ret['err']="";
			$ret['service_notes']=$res;
			$ret['companys']=$db->getInd("id","select * from company where id in (?b)",$company_ids);
			$ret['cars']=$db->getInd("id","select * from company_cars where id in (?b)",$car_ids);
			$ret['zakaz_statuses']=$db->getInd("id","select id,status from zakaz where id in (?b)",$zakaz_ids);
			$ret['msg']="";
	    }
	    else {
			$ret['status']="ok";
			$ret['err']="";
			$ret['service_notes']=array();
			$ret['companys']=array();
			$ret['cars']=array();
			$ret['msg']="";
	    }
	    if ($ret['status']=="err") return self::_error_arr($ret['err']);
	    else return $ret;
	}

	public static function delete_service_note($request) {
	    $fields="";
	    $db = DB::getInstance();
	    /*if ((int)$_SESSION['roles']>2) {
			return self::_error_arr("У Вас нет прав для удаления");
	    }*/
	    if (isset($request->service_note_id)) {$service_note_id=(int)$request->service_note_id;}
	    if (isset($service_note_id) && $service_note_id>0){
			$service_note=new ServiceNote($service_note_id);
			$res3=$db->query("delete from service_notes where workplace_id=?i and start_date=?s and deleted=1",$service_note->workplace_id,$service_note->start_date);
			$res2=$db->query("update service_notes set deleted=1 where id=?i and main_company_id=?i and service_id=?i",$service_note_id,$_SESSION['main_company'],(int)$_SESSION['my_service_id']);
			//echo "delete from sklad where id=".$sklad_id." and company_id in (select company_id from user_companys where main_company_id=0 and user_id=".$_SESSION['user_id'].")";
			if ($res2){
				$ret['status']="ok";
				$ret['msg']="";
			}
			else {
				$ret['status']="err";
				$ret['err']="не удалось удалить Запись";
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
		left join service_notes sj on (zj.job_id=sj.id)
		where zj.zakaz_id=?i and zj.zakaz_id in (select id from zakaz where id=?i and main_company_id=?i)";
		$res=$db->getAll($sql,$request->zakaz_id,$request->zakaz_id,$_SESSION['main_company']);
		if($res){
			return array("status"=>"ok","zakaz_jobs"=>$res,"msg"=>"");
		}
		else return array("status"=>"ok","zakaz_jobs"=>array(),"msg"=>"");
	}
}



?>