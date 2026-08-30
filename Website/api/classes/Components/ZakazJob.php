<?php

namespace Sort1API\Components;
use Sort1API\Components\Skladjob;
use Sort1API\Components\Document;
use Sort1API\Components\LogisticOrderjob;
use Sort1API\Components\LogisticOrder;
use Sort1API\Components\Models\DocumentJobs;
use Sort1API\Components\ZakazDetail;
use Sort1API\Components\ZakazJob;
use Sort1API\Components\Zakaz;

class Zakazjob
{
    private $_zakaz_job_arr=array();
    private $_zakaz_job_arr_old=array();
    public $is_exist=0;



  private function create_document_job($db,$type,$sklad_id=0){
    if($sklad_id==0){
      $sklad_res=$db->getRow("select delivery_type,delivery_type_id,fullfilment_id from zakaz where id=?i",$this->zakaz_id);
      if($sklad_res['delivery_type']==1) $sklad_id=$sklad_res['delivery_type_id'];
      else {
        if($sklad_res['fullfilment_id']!=0) $sklad_id=$sklad_res['fullfilment_id'];
        else {

        }
      }
    }
    $sql="select id from document where zakaz_id=?i and type_id=?i and deleted=0";
    $document_id=$db->getOne($sql,$this->zakaz_id,$type);
    if($document_id)
      $document=new Document($document_id);
    else {
      $document=new Document(); 
      $document->zakaz_id=$this->zakaz_id;
      $document->comment="Заказ № ".$this->zakaz_id;
      $document->document_date=date("Y-m-d H:i:s");
      $document->company_id=$db->getOne("select company_id from zakaz where id=?i",$this->zakaz_id);
      $document->main_company=$_SESSION['main_company'];
      $document->type_id=$type;
      $document->sklad_id=$sklad_id;
      $document->save(); 
    }
    //if((int)$this->status==200 && (float)$this->dealer_price>0) $price=$this->dealer_price;
    //else $price=$this->price;
    //$document_job=new Documentjob(0,$document->id,$this->job_id,$price);
    $doc_job=array();
      $doc_job['document_id']=$document->id;
      $doc_job['job_id']=$this->job_id;
      if($type==6)
        $doc_job['count']=($this->returned_count-$this->_zakaz_job_arr_old['returned_count']);
      else
        $doc_job['count']=$this->count;
      $doc_job['price']=($this->price*$this->difficult_co); 
      $doc_job['subaction']="add";
      $doc_job['service_id']=$this->service_id;
      $doc_job['car_id']=$this->car_id;
      $doc_job['workplace_id']=$this->workplace_id;
      //$doc_job['coefficient']=$this->difficult_co;
      $saved=DocumentJobs::save_document_job((object)$doc_job);
      //echo "saved=".print_r($saved,true)."\n";
      return $saved;
  }


    private function create_new_zakaz_job($zakaz_id){
    	$db= DB::getInstance();
    	$res=$db->getAll("describe zakaz_jobs");
    	foreach($res as $key=>$val){
    	    if ($val['Field']=="create_date") $this->_zakaz_job_arr[$val['Field']]=date("Y-m-d H:i:s");
    	    else {
    		if (preg_match("/int/",$val['Type']) || preg_match("/float/",$val['Type'])) {
    		    if(!empty($val['Default'])) $this->_zakaz_job_arr[$val['Field']]=$val['Default'];
    		    else $this->_zakaz_job_arr[$val['Field']]=0;
    		}
    		else {
    		    if(!empty($val['Default'])) $this->_zakaz_job_arr[$val['Field']]=$val['Default'];
    		    else $this->_zakaz_job_arr[$val['Field']]="";
    		}
    	    }
    	}
    	$this->zakaz_id=$zakaz_id;
    	$this->is_exist=0;
      $this->user_id=$_SESSION['user_id'];
    }

    function __construct($zakaz_jobs_id=0,$zakaz_id=0,$job_id=0){
        if($zakaz_jobs_id>0){
          $this->LoadById($zakaz_jobs_id);
        }
        else{
          //if ($zakaz_id>0 && $job_id!=0 && $deliverer_type>0 && $deliverer_id>0)
      	  //  $this->Load($zakaz_id,$job_id,$deliverer_type,$deliverer_id);
        	//else {
        	    if($zakaz_id>0){
        		      $this->create_new_zakaz_job($zakaz_id);
                  $this->deliverer_type=$deliverer_type;
                  $this->deliverer_id=$deliverer_id;
                  $this->job_id=$job_id;
        	    }
              else {
                $this->create_new_zakaz_job($zakaz_id);
                $this->deliverer_type=$deliverer_type;
                $this->deliverer_id=$deliverer_id;
                $this->job_id=$job_id;
              }
        	//}
        }
    }

    public function Load($zakaz_id,$job_id)
    {
        $db = DB::getInstance();
        if ($zakaz_id>0) {
            $zakaz_data=$db->getRow("select * from zakaz_jobs where zakaz_id=?i and id=?i",$zakaz_id,$job_id);
            //print_r($user_data);
            //echo "select username,roles,create_date,company_id,name,lastname,email,phone,mphone,avatar from users where id=$user_id";
            if (count((array)$zakaz_data)>0){
          		foreach($zakaz_data as $key=>$val){
          		    $this->_zakaz_job_arr[$key]=$val;
          		}
          		$this->is_exist=1;
            }
      	    else {
          		$this->create_new_zakaz_job($zakaz_id);
          		$this->job_id=$job_id;
              $this->deliverer_type=$deliverer_type;
              $this->deliverer_id=$deliverer_id;
          		$this->is_exist=0;
      	    }
        }
    }

    public function LoadById($zakaz_jobs_id)
    {
        $db = DB::getInstance();
        if ($zakaz_jobs_id>0) {
            $zakaz_data=$db->getRow("select * from zakaz_jobs where id=?i",$zakaz_jobs_id);
            //print_r($user_data);
            //echo "select username,roles,create_date,company_id,name,lastname,email,phone,mphone,avatar from users where id=$user_id";
            if ($zakaz_data){
          		foreach($zakaz_data as $key=>$val){
          		    $this->_zakaz_job_arr[$key]=$val;
          		}
          		$this->is_exist=1;
            }
            else {
              $this->is_exist=0;
            }
        }
    }

	public function __get($name) {
		if (isset($this->_zakaz_job_arr[$name])) {
			return $this->_zakaz_job_arr[$name];
		} else {
			return null;
		}
	}

	public function __isset($name){
	    return isset($this->_zakaz_job_arr[$name]);
	}

	public function __set($name,$val) {
		if (isset($this->_zakaz_job_arr[$name])) {
			$this->_zakaz_job_arr_old[$name]=$this->_zakaz_job_arr[$name];
			$this->_zakaz_job_arr[$name]=$val;
		}
	}

    private function convert_string($str){
      $del=array("/","\\","-","+"," ","\xC2\xA0","\n","\t","\r",".","_","*","%","$","#","@","!","&","^");
      return mb_strtoupper(str_replace($del,"",$str));
    }

    private function do_status_action(){
      $db = DB::getInstance();
      if(isset($this->_zakaz_job_arr['status']) && $this->_zakaz_job_arr_old['status']!=$this->_zakaz_job_arr['status']){
        $db->query("insert into zakaz_job_status_log (create_date,zakaz_job_id,status,change_by_user_id) values (?s,?i,?i,?i)",date("Y-m-d H:i:s"),$this->id,$this->_zakaz_job_arr['status'],$_SESSION['user_id']);
        switch((int)$this->status){
          case 10: //Новая
            break;
          case 20: //Выполняется
            if($this->_zakaz_job_arr_old['status']==30){
              
            }
            break;
          case 52: //работа выполнена
            $zakaz_details=$db->getAll("select * from zakaz_details where zakaz_job_id=?i and zakaz_id=?i",$this->id,$this->zakaz_id);
            foreach($zakaz_details as $zd_key=>$zd_val){
              $zakaz_detail=new ZakazDetail($zd_val['id']);
              $zakaz_detail->status=52; // поменяем статус детали на "Установлен в автомобиль"
              $zakaz_detail->save();
              //Найдем выдачу детали и в ней тоже поменяем статус на установлен в автомобиль
              $db->query("update delivery_to_workshop_details set status=30 where zakaz_detail_id=?i",$zakaz_detail->id);
              $delivery_to_workshop=$db->getOne("select delivery_to_workshop_id from delivery_to_workshop_details where zakaz_detail_id=?i",$zakaz_detail->id);
              $db->query("update delivery_to_workshop set status=(select min(status) from delivery_to_workshop_details where delivery_to_workshop_id=?i) where id=?i",$delivery_to_workshop,$delivery_to_workshop);
            }
            break;
          case 70: //работа закрыта и выписан документ (акт выполненных работ или УПД)
            $company_id=$db->getOne("select company_id from zakaz where id=?i", $this->zakaz_id);
              if((int)$company_id==$_SESSION['main_company']){
                //если себе на склад не надо изменять баланс и убирать резерв, не надо создавать документы на выдачу

              }
              else {
                $company_balance=new CompanyBalance($company_id);
                if($company_balance->rezerv<0) $company_balance->rezerv=0;
                $res=$company_balance->save();
                $document_err=$this->create_document_job($db,2);
                if($document_err['status']=="err"){
                  //echo "document_err=".print_r($document_err,true)."\n";
                  return $document_err;
                  //$db->query("update zakaz_jobs set status=?i where id=?i",(int)$this->_zakaz_job_arr_old['status'],$this->id);
                }
                if ($res==1 || $res==10) {
                  //$db->query("update zakaz_details set rezerved=0,supplied_count=?i where id=?i",$this->count,$this->id);
                  $min_zakaz_detail_status=$db->getOne("select min(status) from zakaz_details where zakaz_id=?i and reorder_detail_id=0",$this->zakaz_id);
                  if((int)$min_zakaz_detail_status==0) $min_zakaz_detail_status=1000;
                  $min_zakaz_job_status=$db->getOne("select min(status) from zakaz_jobs where zakaz_id=?i",$this->zakaz_id);
                  if((int)$min_zakaz_job_status==0) $min_zakaz_job_status=1000;
                  if($min_zakaz_job_status<1000 || $min_zakaz_detail_status<1000){
                    $sql_zakaz_status="update zakaz set status=(select max(id) from zakaz_statuses where id<=?i) where id=?i";
                    $db->query($sql_zakaz_status,min($min_zakaz_detail_status,$min_zakaz_job_status),$this->zakaz_id);
                  }

                  //return array("status"=>"ok","msg"=>"");
                }
                else {
                  return array("status"=>"err","err"=>"Не обновились данные в балансе");
                }
              }
            break;
        }
        $min_zakaz_detail_status=$db->getOne("select min(status) from zakaz_details where zakaz_id=?i and reorder_detail_id=0",$this->zakaz_id);
        if((int)$min_zakaz_detail_status==0) $min_zakaz_detail_status=1000;
        $min_zakaz_job_status=$db->getOne("select min(status) from zakaz_jobs where zakaz_id=?i",$this->zakaz_id);
        if((int)$min_zakaz_job_status==0) $min_zakaz_job_status=1000;
        if($min_zakaz_job_status<1000 || $min_zakaz_detail_status<1000){
          $sql_zakaz_status="update zakaz set status=(select max(id) from zakaz_statuses where id<=?i) where id=?i";
          $db->query($sql_zakaz_status,min($min_zakaz_detail_status,$min_zakaz_job_status),$this->zakaz_id);
        }
      }
      return array("status"=>"ok","msg"=>"");
    }

    public function Save(){
        $db = DB::getInstance();
	      //$this->do_status_actions($db);

        if (isset($this->is_exist) && $this->is_exist>0) {
	          $this->_zakaz_job_arr['update_date']=date("Y-m-d H:i:s");
            $status_err=$this->do_status_action();
            if($status_err['status']=="err") return 0;
            $sql="update zakaz_jobs set ?u where id=?i";
            $db->query($sql,$this->_zakaz_job_arr,$this->id);
            
            //echo "db->query($sql,".print_r($this->_sklad_job_arr,true).",".$this->id.");";
            if ($db->affectedRows()>0) {
                $this->recalculate_pozition_count();
                return 1;
	          }
	          else { return 10; }
        }
        else {
            $this->create_date=date("Y-m-d H:i:s");
            $save_data['create_date']=$this->create_date;
            $sql="insert ignore into zakaz_jobs set ?u";
	          //echo $sql." ".print_r($this->_sklad_job_arr,true);
            $db->query($sql,$this->_zakaz_job_arr);
            if ($db->affectedRows()>0) {
          	  $this->id=$db->insertId();
              $this->recalculate_pozition_count();
                return 1;
      	    }
	          else return 0;
        }
        return $db->error;
    }

    public function delete(){
      if(!empty($this->id) && (int)$this->id>0){
          $db = DB::getInstance();
          $sql="delete from zakaz_jobs
          where zakaz_id in (select id from zakaz where id=?i and main_company_id=?i) and id=?i";
          $res=$db->query($sql,$this->zakaz_id,$_SESSION['main_company'],(int)$this->id);
          if($res){
            $this->recalculate_pozition_count();
            return array("status"=>"ok","zakaz_jobs"=>$res,"msg"=>"");
          }
          else return array("status"=>"err","err"=>"Не удается удалить работу из заказа","msg"=>"");
      }
      else return array("status"=>"err","err"=>"Не указан id работы","msg"=>"");
      //else return self::_error_arr("Не указан id работы");
}

    public function recalculate_pozition_count(){
      $db = DB::getInstance();
      $sql="select count(id) as count,sum(count*price) as sum from zakaz_details where zakaz_id=?i and reorder_detail_id=0 and (status<100 or status>199)";
      $pos_count=$db->getRow($sql,$this->zakaz_id);
      $sql="select count(id) as count,sum(count*price*difficult_co) as sum from zakaz_jobs where zakaz_id=?i";
      $jobs_count=$db->getRow($sql,$this->zakaz_id);
      $zakaz=new Zakaz($this->zakaz_id);
      $zakaz->pozition_count=$pos_count['count']+$jobs_count['count'];
      $zakaz->zakaz_sum=round($pos_count['sum']+$jobs_count['sum'],2);
      $zakaz->save();
    }
}
?>
