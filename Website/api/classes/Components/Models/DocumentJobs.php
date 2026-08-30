<?php

namespace Sort1API\Components\Models;

use Sort1API\Components\DB;
use Sort1API\Components\DocumentJob;
use Sort1API\Components\ZakazJob;
use Sort1API\Components\ServiceJob;
use Sort1API\Components\Document;
use Sort1API\Components\SkladDetail;
use Sort1API\Components\Config;
use Sort1API\Components\GTD;
use Sort1API\Components\Models\Search;
use Sort1API\Components\Models\LocalDetails;

/**
* 	Model Class to work with Orders instatnces (orders table)
*   Class can use inner Database (DB::getInstance())
*   or
* 	external api to receive data (using HTTPRequest class or extened)
*
*/


class DocumentJobs extends Model {

        public static function check_roles($role){
                $main_user=new User((int)$_SESSION['user_id']);
                if ($main_user->roles<=$role) return $role;
                else return $main_user->roles;
        }

		public static function save_document_jobs($request){
			if ((int)$_SESSION['roles']!=6 && (int)$_SESSION['roles']!=1 && (int)$_SESSION['roles']!=2 && $znak=="+") {
				//return self::_error_arr("У Вас нет прав для данного действия");
			}
			$ret=array();
			foreach($request->jobs as $key=>$val){
				$job_request=$val;
				$job_request['document_id']=$request->document_id;
				$job_request['sklad_id']=$request->sklad_id;
				$job_request['subaction']="add";
				$res=self::save_document_job((object)$job_request);
				if($res['status']=="err"){
					if(!isset($ret['not_saved_jobs'])) $ret['not_saved_documents']=array();
					$job_request['err_reason']=$res;
					$ret['not_saved_documents'][]=(array)$job_request;
				}
			}
			$ret['status']="ok";
			$ret['msg']="";
			return $ret;
		} 
		
        public static function save_document_job($request){ //$document_id,$sklad_id,$znak,$detail) {
      	    if ((int)$_SESSION['roles']!=6 && (int)$_SESSION['roles']!=1 && (int)$_SESSION['roles']!=2 && $znak=="+") {
      		   //   return self::_error_arr("У Вас нет прав для данного действия");
      	    }
            $db = DB::getInstance(); 
      	    if (isset($request->document_id)) {
				$document=new Document((int)$request->document_id);
				// если так то не дает добавлять сука if($document->type_id==6) return self::_error_arr("Документ создан автоматически, его нельзя редактировать");
          		$document_id=(int)$request->document_id;
				  $znak_data=$db->getRow("select id,znak from document_types where id=(select type_id from document where id=?i)",$document_id);
				  $znak=$znak_data['znak'];
				  $document_type=$znak_data['id'];
			}
			else {
				return self::_error_arr("Не указан номер документа");
			}
			if (isset($request->id)) $id=(int)$request->id;
			else $id=0;
			if (isset($request->job_id)) $job_id=(int)$request->job_id;
			if (isset($request->price)) $price=(float)$request->price;
      	    if (isset($job_id) && $job_id!=0) {
          		if (isset($document_id) && $document_id>0) {
          		    $doc_job=new DocumentJob($id,$document_id,$job_id,$price);
          		} 
          		else
					$doc_job=new DocumentJob(); //сюда никогда не дойдет
  	        }
      	    else {
      		      // Надо завести работу????
					if($document_type==1) $job_type=3;
					else $job_type=1;
					$exist_serv_jobs=$db->getAll("select * from service_jobs where main_company_id=?i and name=?s and job_type=?i",$_SESSION['main_company'],trim($request->job_name),$job_type);
					if(count((array)$exist_serv_jobs)>0){
						if(count((array)$exist_serv_jobs)==1){
							$service_job=new ServiceJob($exist_serv_jobs[0]['id']);
						}
						else return self::_error_arr("Такая услуга уже существует, выберите из списка");
					}
					else {
						$service_job=new ServiceJob();
						$service_job->job_type=$job_type;
						$service_job->name=$request->job_name;
						$service_job->price=$price;
						$service_job->main_company_id=$_SESSION['main_company'];
						$service_job->save();
					}
                if (isset($document_id) && $document_id>0) {
            		    $doc_job=new DocumentJob();
                        $doc_job->document_id=$document_id;
                        $doc_job->price=$price;
						$doc_job->job_id=$service_job->id;
            	}
            	else {
					return self::_error_arr("Непонятно в какой документ добавлять услугу");
				}
			}
			if($id>0 && (int)$doc_job->job_id>0 && (int)$doc_job->job_id!=(int)$job_id){
				//редактирование детали и смена детали
				return self::_error_arr("Нельзя в документе менять услугу. Если вы ошиблись при вводе удалите сначала эту услугу, затем добавьте новую");
			}
      	    if (isset($document_id) && (int)$document_id>0) {
          		$documents=$db->getCol("select id from document where deleted=0 and main_company in (select company_id from user_companys where user_id=?i and main_company_id=0)",$_SESSION['user_id']);
          		//echo "document $document_id,".print_r($documents,true)." sklad $sklad_id,".print_r($sklads,true);
          		if ($documents && in_array($document_id,$documents)){
          		    $doc_job->document_id=(int)$document_id;
          		}
          		else {
          		    return self::_error_arr("Нельзя добавлять запись в чужой документ <br>");
          		}
      	    }
      	    else
      		    return self::_error_arr("Не выбран документ");
      	    if (isset($request->price)) {
				//echo "znak=$znak\n";
				$doc_job->price=$request->price;
      	    }
      	    if (isset($request->count)) {
                // если добавление услуги и она существует то необходимо увеличить кол-во
                if($doc_job->is_exist && $request->subaction=="add"){
                    $request->count+=$doc_job->count;
                    $ret['subaction']=$request->subaction;
                    $ret['request_count']=$request->count;
                }
          		//echo print_r($sklad_det,true);
          		$doc_job->count=$request->count;
      	    }
      	    if(isset($request->tax) && (int)$request->tax>0){
          		$doc_job->tax=$request->tax;
			}
			else {
				if($znak=="-"){
					$my_company=$db->getRow("select c.*,tt.is_nds,tt.tax_rate from company c left join tax_type tt on (c.tax_type=tt.id) where c.id=?i",$_SESSION['main_company']);
					if((int)$my_company['is_nds']==1) {
						$doc_job->tax=$my_company['tax_rate'];
					}
				}
				if($znak=="+"){
					$my_company=$db->getRow("select c.*,tt.is_nds,tt.tax_rate from company c left join tax_type tt on (c.tax_type=tt.id) where c.id=(select company_id from document where id=?i)",$request->document_id);
					if((int)$my_company['is_nds']==1) {
						$doc_job->tax=$my_company['tax_rate'];
					}
				}
			}
      	    $doc_job->user_id=$_SESSION['user_id'];
            if(isset($request->service_id)) $doc_job->service_id=(int)$request->service_id;
            if(isset($request->car_id)) $doc_job->car_id=(int)$request->car_id;
            if(isset($request->workplace_id)) $doc_job->workplace_id=(int)$request->workplace_id;
          	$doc_err=$doc_job->save();
			//}
			
			if ($znak=="+") {
				if(isset($request->zakaz_id) && (int)$request->zakaz_id>0 && isset($request->zakaz_jobs_id) && (int)$request->zakaz_jobs_id>0)
					$link_ret=self::link_job_in_zakaz($doc_job,(int)$request->zakaz_id,(int)$request->zakaz_jobs_id);
				else
					$link_ret=self::link_job_in_zakaz($doc_job,0,0);
			}
				switch($doc_err) {
					case 10: $status="ok"; $msg="Данные не изменились\n"; break;
					case 1: if ($request->subaction=="edit"){
								$status="ok"; $msg="Данные успешно изменены";
							}
							else {
								$status="ok"; $msg="Новая услуга добавлена в документ";
							}
							
						break;
					default: $status="err"; $msg="error: ".$sklad_err."\n";
				}
            if ($status=="err") return self::_error_arr($msg);
            else return array("status"=>$status,"msg"=>$msg,"err"=>"","link_ret"=>$link_ret,"document_job_id"=>$doc_job->id);
        }


	public static function get_document_job($request) {
	    if ((int)$_SESSION['roles']!=6 && (int)$_SESSION['roles']!=1 && (int)$_SESSION['roles']!=2) {
		    //  return self::_error_arr("У Вас нет прав для данного действия");
	    }
	    $db = DB::getInstance();
	    $sql="select dj.*,sj.name as job_name,
            ifnull(concat(se.name,' ',se.lastname),'') as employee_name,
            ifnull(s.name,'') as service_name,
            ifnull(sw.name,'') as workplace_name 
        from document_jobs dj 
        left join service_jobs sj on (sj.id=dj.job_id)
        left join services s on (s.id=dj.service_id)
        left join service_employees se on (se.id=dj.employee_id)
        left join service_workplaces sw on (sw.id=dj.workplace_id)
        where dj.deleted=0 and dj.id=?i";
	    $res=$db->getAll($sql,(int)$request->document_job_id);
	    if (is_array($res) && count($res)>0){
    		$ret['status']="ok";
    		$ret['err']="";
    		$ret['document_id']=(int)$request->document_id;
    		//$ret['sklad_name']=$db->getOne("select name from sklad where id=?i",(int)$request->sklad_id);
			$ret['document_jobs']=$res;
    		$ret['msg']="";
	    }
	    if ($ret['status']=="err") return self::_error_arr($ret['err']);
	    else return $ret;
	}

	public static function get_document_jobs($request) {
	    if ((int)$_SESSION['roles']!=6 && (int)$_SESSION['roles']!=1 && (int)$_SESSION['roles']!=2) {
		    //  return self::_error_arr("У Вас нет прав для данного действия");
	    }
	    $db = DB::getInstance();
	    $sql_count="select count(job_id) from document_jobs where deleted=0 and document_id=?i ";
        $jobs_count=$db->getOne($sql_count,$request->document_id); 
	    $sql="select dj.*,sj.name as job_name from document_jobs dj 
            left join service_jobs sj on (sj.id=dj.job_id)
            where dj.deleted=0 and dj.document_id=?i ";
		$parsed="";
		if(!empty($request->search)) $parsed=$db->parse(" and sj.name like ?s",'%'.$request->search.'%');
	    $sql.=" ?p order by dj.create_date desc";
		$res=$db->getAll($sql,(int)$request->document_id,$parsed);
		$job_empl=array();
		foreach($res as $key=>$sdoc_job){
			$job_empl[$sdoc_job['id']]=$db->getAll(
                        "SELECT zje.*,se.name,se.surname,se.lastname FROM zakaz_job_employees zje 
                        LEFT JOIN service_employees se ON (se.id=zje.employee_id)
                        WHERE zje.zakaz_job_id in (select id from zakaz_jobs where zakaz_id in (select zakaz_id from document where id=?i and zakaz_id<>0) and job_id=?i)",$sdoc_job['document_id'],$sdoc_job['job_id']);
		}
	    if (is_array($res) && count($res)>0){
            $ret['status']="ok";
            $ret['err']="";
            $ret['document_id']=(int)$request->document_id;
            $ret['document_comment']=$db->getOne("select comment from document where id=?i",(int)$request->document_id);
            $ret['document_jobs']=$res;
			$ret['job_empl']=$job_empl;
            $ret['jobs_count']=(int)$jobs_count;
			$ret['search']=$request->search;
            $ret['msg']="";
	    }
	    else {
            $ret['status']="ok";
            $ret['msg']="";
            $ret['err']="";
            $ret['document_id']=(int)$request->document_id;
            $ret['document_jobs']=[];
            $ret['jobs_count']=0;
			$ret['search']=$request->search;
            $ret['document_comment']=$db->getOne("select comment from document where id=?i",(int)$request->document_id);
	    }
	    if ($ret['status']=="err") return self::_error_arr($ret['err']);
	    else return $ret;
	}

	public static function delete_document_job($request) {
	    $fields="";
	    $db = DB::getInstance();
	    if ((int)$_SESSION['roles']!=6 && (int)$_SESSION['roles']!=1 && (int)$_SESSION['roles']!=2) {
		      //return self::_error_arr("У Вас нет прав для удаления");
	    } 
	    if (isset($request->document_job_id)) {$document_job_id=(int)$request->document_job_id;}
		else return self::_error_arr("Не указан id детали");
	    if (isset($document_job_id) && $document_job_id>0){
    		$document_job=new DocumentJob($document_job_id);
            //echo print_r($document_job,true)."\n";
    		if($document_job->is_exist) $deleted=$document_job->Delete();
    		//echo "delete from sklad where id=".$sklad_id." and company_id in (select company_id from user_companys where main_company_id=0 and user_id=".$_SESSION['user_id'].")";
    		if ($deleted){
    		    $ret['status']="ok";
    		    $ret['msg']="Услуга успешно удалена";
    		}
    		else {
    		    $ret['status']="err";
    		    $ret['err']="не удалось удалить услугу";
    		}
	    }
	    else {
    		$ret['status']="err";
    		$ret['err']="нет данных";
	    }
	    if ($ret['status']=="err") return self::_error_arr($ret['err']);
	    else return array("status"=>$ret['status'],"msg"=>$ret['msg'],"err"=>"");
	}

	private static function link_job_in_zakaz($doc_job,$req_zakaz_id,$req_zakaz_jobs_id){
		$db=DB::getInstance();
		if($req_zakaz_jobs_id>0){
			$sql="select zj.*,z.delivery_type,z.delivery_type_id,z.fullfilment_id from zakaz_jobs zj 
			left join zakaz z on (zd.zakaz_id=z.id)
			where zj.job_id=?i and zj.status<37 and zj.status>=2  
			AND z.main_company_id=?i and z.deleted=0 and zj.id=?i order by zj.create_date";
			$zakaz_jobs=$db->getAll($sql,$doc_job->job_id,$_SESSION['main_company'],$req_zakaz_jobs_id);
		}
		else {
			$sql="select zj.*,z.delivery_type,z.delivery_type_id,z.fullfilment_id from zakaz_jobs zj 
			left join zakaz z on (zj.zakaz_id=z.id)
			where zj.job_id=?i and zj.status<37 and zj.status>=2 
			AND z.main_company_id=?i and z.deleted=0 order by create_date";
			$zakaz_jobs=$db->getAll($sql,$doc_det->detail_id,$_SESSION['main_company']);
		}
		if(count((array)$zakaz_jobs)==1){
			return self::save_link($doc_job,$zakaz_jobs);
		}
		else {
			//анализируем дальше
			//$document=new Document($doc_job->document_id);
			// company_id=$document->company_id
        }
		return $zakaz_details;
	}

	private static function save_link($doc_job,$zakaz_jobs){
		$db=DB::getInstance();
		$sql="insert into doc_job_to_zakaz_job (document_id,document_jobs_id,zakaz_id,zakaz_jobs_id,count,descr,create_date) values (?i,?i,?i,?i,?i,?s,?s) on duplicate key 
			update count=values(count),update_date=?s";
			if($doc_job->count>=$zakaz_jobs[0]['count']){
				$ins_res=$db->query($sql,$doc_job->document_id,$doc_job->id,$zakaz_jobs[0]['zakaz_id'],$zakaz_jobs[0]['id'],$zakaz_jobs[0]['count'],"",date("Y-m-d H:i:s"),date("Y-m-d H:i:s"));
				$inserted_count=$zakaz_jobs[0]['count'];
			}
			else {
				if($doc_job->count<$zakaz_jobs[0]['count']){
					$ins_res=$db->query($sql,$doc_job->document_id,$doc_job->id,$zakaz_jobs[0]['zakaz_id'],$zakaz_jobs[0]['id'],$doc_job->count,"",date("Y-m-d H:i:s"),date("Y-m-d H:i:s"));
					$inserted_count=$doc_job->count;
				}
			}
			if($inserted_count==$zakaz_jobs[0]['count']){
				$zakaz_job=new ZakazJob($zakaz_jobs[0]['id']);
				$zakaz_job->status=52;//оприходован на склад
				$zakaz_job->save();
			}
			return array("status"=>"ok","inserted_count"=>$inserted_count,"zakaz_id"=>$zakaz_jobs[0]['zakaz_id'],"zakaz_jobs_id"=>$zakaz_jobs[0]['id']);
	}

}
?>
