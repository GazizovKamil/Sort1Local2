<?php

namespace Sort1API\Components\Models;

use Sort1API\Components\DB;
use Sort1API\Components\Config;
use Sort1API\Components\ZakazJob;
use Sort1API\Components\Zakaz;
use Sort1API\Components\CompanyBalance;
use Sort1API\Components\Models\Payments;

/**
* 	Model Class to work with Orders instatnces (orders table)
*   Class can use inner Database (DB::getInstance())
*   or
* 	external api to receive data (using HTTPRequest class or extened)
*
*/


class ZakazJobs extends Model {

    public static function save_zakaz_job($request){
        $db = DB::getInstance();
        //return self::_error_arr("Не указан номер заказа № заказа: ".(int)$request->zakaz_id." zakaz_id=".var_dump($request));
        if(!empty($request->id) && (int)$request->id>0){
            $zakaz_job=new ZakazJob((int)$request->id);
        }
        else {
            $zakaz_job=new ZakazJob(0,$request->zakaz_id,$request->job_id);
        }
        if(!empty($request->zakaz_id) && (int)$request->zakaz_id>0) $zakaz_job->zakaz_id=(int)$request->zakaz_id;
        else return self::_error_arr("Не указан номер заказа ");
        if(!empty($request->count)) $zakaz_job->count=(int)$request->count;
        if(!empty($request->price)) $zakaz_job->price=(int)$request->price;
        if(!empty($request->difficult_co)) $zakaz_job->difficult_co=(float)str_replace(",",".",$request->difficult_co);
        if(!empty($request->descr)) $zakaz_job->descr=(int)$request->descr;
        if(!empty($request->status)) $zakaz_job->status=(int)$request->status;
        //if(!empty($request->zakaz_job_employee_id)) $zakaz_job->service_employee_id=(int)$request->zakaz_job_employee_id;
        if(!empty($request->job_id) && (int)$request->job_id>0) $zakaz_job->job_id=(int)$request->job_id;
        else return self::_error_arr("Не указана услуга");
        $service_data=$db->getRow("select company_id,car_id,workplace_id,service_id from service_notes where zakaz_id=?i",(int)$request->zakaz_id);
        if($service_data){
            if((int)$service_data['company_id']) $zakaz_job->client_id=(int)$service_data['company_id'];
            if((int)$service_data['workplace_id']) $zakaz_job->workplace_id=(int)$service_data['workplace_id'];
            if((int)$service_data['service_id']) $zakaz_job->service_id=(int)$service_data['service_id'];
            if((int)$service_data['car_id']) $zakaz_job->company_car_id=(int)$service_data['car_id'];
        }
        $zakaz_job->user_id=$_SESSION['user_id'];
        $err=$zakaz_job->save();
      	    switch($err) {
          		case 10: $status="ok"; $msg="Данные не изменились\n"; break;
          		case 1:
                    
                    if((int)$request->id>0){
                        $db->query("delete from zakaz_job_employees where zakaz_job_id=?i",(int)$request->id);
                    }
                    if(count((array)$request->job_employees)>0){
                        foreach($request->job_employees as $job_empl_key=>$job_empl_val){
                            $db->query("insert into zakaz_job_employees (zakaz_job_id,employee_id,proc,create_date) values(?i,?i,?i,?s)",$zakaz_job->id,$job_empl_val['id'],$job_empl_val['proc'],date("Y-m-d H:i:s"));
                        }
                    }
                        
                    
          			if (isset($request->zakaz_jobs_id) && (int)$request->zakaz_jobs_id>0){
                          		$status="ok"; $msg="Данные успешно изменены";
                      		}
                      		else {
                          	    $status="ok";
          			    if(empty($msg)) $msg="";
                      		}
          			break;
          		default: $status="err"; $msg="error: ".$err."\n";
      	    }
            if ($status=="err") return self::_error_arr($msg);
            else return array("status"=>$status,"msg"=>$msg,"err"=>"");
    }

    public static function get_zakaz_jobs($request){
		$db = DB::getInstance();
		$sql="select zj.*,sj.name,sj.price as job_price from zakaz_jobs zj 
		left join service_jobs sj on (zj.job_id=sj.id)
		where zj.zakaz_id=?i and zj.zakaz_id in (select id from zakaz where id=?i and main_company_id=?i)";
		$res=$db->getAll($sql,$request->zakaz_id,$request->zakaz_id,$_SESSION['main_company']);
        
		if($res){
            $job_empl=array();
            foreach($res as $key=>$sdoc_job){
                $job_empl[$sdoc_job['id']]=$db->getAll(
                            "SELECT zje.*,se.name,se.surname,se.lastname FROM zakaz_job_employees zje 
                            LEFT JOIN service_employees se ON (se.id=zje.employee_id)
                            WHERE zje.zakaz_job_id=?i",$sdoc_job['id']);
            }
			return array("status"=>"ok","zakaz_jobs"=>$res,"msg"=>"","zakaz_job_statuses"=>$db->getInd("id","select * from zakaz_job_statuses"),"job_empl"=>$job_empl);
		}
		else return array("status"=>"ok","zakaz_jobs"=>array(),"msg"=>"","zakaz_job_statuses"=>$db->getInd("id","select * from zakaz_job_statuses"));
	}

    public static function get_zakaz_job($request){
        if(!empty($request->zakaz_jobs_id) && (int)$request->zakaz_jobs_id>0){
            $db = DB::getInstance();
            $sql="select zj.*,sj.name,sj.price as job_price,se.lastname as service_employee_name from zakaz_jobs zj 
            left join service_jobs sj on (zj.job_id=sj.id)
            left join service_employees se on (se.id=zj.service_employee_id)
            where zj.zakaz_id in (select id from zakaz where id=?i and main_company_id=?i) and zj.id=?i";
            $res=$db->getAll($sql,$request->zakaz_id,$_SESSION['main_company'],(int)$request->zakaz_jobs_id);
            if($res){
                foreach($res as $key=>$val){
                    $job_employees=$db->getAll("select zje.*,se.lastname,se.name,se.surname,se.profession from zakaz_job_employees zje
                    left join service_employees se on (se.id=zje.employee_id)
                    where zje.zakaz_job_id=?i",$val['id']);
                    if($job_employees) {
                        foreach($job_employees as $je_key=>$je_val){
                            $res[$key]['job_employees'][$je_key]['id']=$je_val['employee_id'];
                            $res[$key]['job_employees'][$je_key]['name']=$je_val['lastname'].' '.$je_val['name'].' '.$je_val['surname'];
                            $res[$key]['job_employees'][$je_key]['proc']=$je_val['proc'];
                            $res[$key]['job_employees'][$je_key]['profession']=$je_val['profession'];
                        }
                    }
                    else $res[$key]['job_employees']=array();
                }
                return array("status"=>"ok","zakaz_jobs"=>$res,"msg"=>"","zakaz_job_statuses"=>$db->getInd("id","select * from zakaz_job_statuses"));
            }
            else return array("status"=>"ok","zakaz_jobs"=>array(),"msg"=>"","zakaz_job_statuses"=>$db->getInd("id","select * from zakaz_job_statuses"));
        }
        else return self::_error_arr("Не указан id работы");
	}

    public static function delete_zakaz_job($request){
        $db = DB::getInstance();
        $is_your=$db->getOne("select count(id) from zakaz where id=?i and main_company_id=?i",$request->zakaz_id,$_SESSION['main_company']);
        if((int)$is_your==0)
            return array("status"=>"err","err"=>"Не ваш заказ","msg"=>"");
        if(!empty($request->zakaz_jobs_id) && (int)$request->zakaz_jobs_id>0){
            $zakaz_job=new ZakazJob((int)$request->zakaz_jobs_id);
            /*$db = DB::getInstance();
            $sql="delete from zakaz_jobs
            where zakaz_id in (select id from zakaz where id=?i and main_company_id=?i) and id=?i";
            $res=$db->query($sql,$request->zakaz_id,$_SESSION['main_company'],(int)$request->zakaz_jobs_id);*/
            $res=$zakaz_job->delete();
            if($res['status']="ok"){
                return $res;
            }
            else return $res;
        }
        else return self::_error_arr("Не указан id работы");
	}

    public static function get_zakaz_job_statuses(){
        $db = DB::getInstance();
        return array("status"=>"ok","msg"=>"","zakaz_job_statuses"=>$db->getInd("id","select * from zakaz_job_statuses"));
    }
}