<?php

namespace Sort1API\Components;

use Sort1API\Components\SkladDetail;
use Sort1API\Components\ZakazDetail;

class DeliveryToWorkshopDetail
{
	private $_delivery_to_workshop_det_arr=array();
	private $_delivery_to_workshop_det_arr_old=array();

    private function create_new_delivery_to_workshop_detail(){
    	$db= DB::getInstance();
    	$res=$db->getAll("describe delivery_to_workshop_details");
    	foreach($res as $key=>$val){
    	    if ($val['Field']=="create_date") $this->_delivery_to_workshop_det_arr[$val['Field']]=date("Y-m-d H:i:s");
    	    else {
        		if (preg_match("/int/",$val['Type']) || preg_match("/float/",$val['Type'])) {
        		    if(!empty($val['Default'])) $this->_delivery_to_workshop_det_arr[$val['Field']]=$val['Default'];
        		    else $this->_delivery_to_workshop_det_arr[$val['Field']]=0;
        		}
        		else {
        		    if(!empty($val['Default'])) $this->_delivery_to_workshop_det_arr[$val['Field']]=$val['Default'];
        		    else $this->_delivery_to_workshop_det_arr[$val['Field']]="";
        		}
    	    }
    	}
      //$this->_delivery_to_workshop_det_arr['main_company_id']=$_SESSION['main_company'];
    }

    function __construct($delivery_to_workshop_detail_id = 0,$delivery_to_workshop_id=0, $zakaz_detail_id=0){
        if ($delivery_to_workshop_detail_id>0)
    	    $this->Load($delivery_to_workshop_detail_id);
      	else {
			if($delivery_to_workshop_id>0 && $zakaz_detail_id>0){
				$db= DB::getInstance();
				$id=$db->getOne("select id from delivery_to_workshop_details where delivery_to_workshop_id=?i and zakaz_detail_id=?i",$delivery_to_workshop_id,$zakaz_detail_id);
				if((int)$id>0)
					$this->Load($id);
				else{
					$this->create_new_delivery_to_workshop_detail();
					$this->delivery_to_workshop_id=(int)$delivery_to_workshop_id;
					$this->zakaz_detail_id=(int)$zakaz_detail_id;
				}
			}
			else 
      			$this->create_new_delivery_to_workshop_detail();
		}
    }

    public function Load($delivery_to_workshop_detail_id)
    {
        $db = DB::getInstance();
        if ($delivery_to_workshop_detail_id>0) {
            $delivery_to_workshop_detail_data=$db->getRow("select * from delivery_to_workshop_details where id=?i",$delivery_to_workshop_detail_id);
            //print_r($user_data);
            //echo "select username,roles,create_date,company_id,name,lastname,email,phone,mphone,avatar from users where id=$user_id";
            if (count((array)$delivery_to_workshop_detail_data)>0){
          		foreach($delivery_to_workshop_detail_data as $key=>$val){
          		    if(!empty($val) || $val == 0) $this->_delivery_to_workshop_det_arr[$key]=$val;
          		    else $this->_delivery_to_workshop_det_arr[$key]="";
          		}
            }
        }
    }

	public function __get($name) {
		if (isset($this->_delivery_to_workshop_det_arr[$name])) {
			return $this->_delivery_to_workshop_det_arr[$name];
		} else {
			return null;
		}
	}

	public function __isset($name){
	    return isset($this->_delivery_to_workshop_det_arr[$name]);
	}

	public function __set($name,$val) {
		if (isset($this->_delivery_to_workshop_det_arr[$name])) {
			$this->_delivery_to_workshop_det_arr_old[$name]=$this->_delivery_to_workshop_det_arr[$name];
			$this->_delivery_to_workshop_det_arr[$name]=$val;
		}
	}

	private function do_status_action(){
		if(isset($this->_delivery_to_workshop_det_arr_old['status']) && $this->_delivery_to_workshop_det_arr_old['status']!=$this->_delivery_to_workshop_det_arr['status']){
			$db = DB::getInstance();
			switch($this->_delivery_to_workshop_det_arr['status']){
				case 1: //Зарегистрирована
					
					break;
				case 10: //Сформирована
					break;
				case 20: //Выдана сотруднику в ремзону
					$sklad_id=$db->getOne("select sklad_id from delivery_to_workshop where id=?i",$this->delivery_to_workshop_id);
					$sklad_det=new SkladDetail($sklad_id,$this->detail_id);
					//echo "sklad_det->is_exist=".$sklad_det->is_exist."\n";
					if($sklad_det->is_exist){
						$sklad_det->count-=$this->delivered_count;
						//echo "detail_id=".$this->detail_id." sklad_det->count=".$sklad_det->count."\n";
						$sklad_det->reserved_count-=$this->delivered_count;
						if($sklad_det->reserved_count<=0) $sklad_det->reserved_count=0;
						if($sklad_det->count<0) return array("status"=>"err","err"=>"На складе не хватает количества деталей");
						$sklad_det->save();
						$zakaz_det=new ZakazDetail($this->zakaz_detail_id);
						$zakaz_det->status=51;
						$zakaz_det->zakaz_job_id=$this->zakaz_detail_job_id;
						$zakaz_det->save();
						return array("status"=>"ok","err"=>"");
					}
					else {
						return array("status"=>"err","err"=>"На складе нет такой детали");
					}
					break;
				case 30: //Установлена в автомобиль
					break;
				case 50: //Возвращена на склад
					$delivery_to_workshop=$db->getRow("select * from delivery_to_workshop where id=?i",$this->delivery_to_workshop_id);
					$detail_data=$db->getRow("select detail_id,count,status from zakaz_details where id=?i and zakaz_id=?i",$this->zakaz_detail_id,$this->zakaz_id);
					if($detail_data['status']<70){
						//возвращаем на склад с которого забирали
						$sklad_details_data=$db->getRow("select count,reserved_count from sklad_details where detail_id=?i and sklad_id=?i",$detail_data['detail_id'],$delivery_to_workshop['from_sklad_id']);
						$upd_res=$db->query("update sklad_details set count=count+?i where detail_id=?i and sklad_id=?i",
							$this->returned_count,
							$this->detail_id,
							$delivery_to_workshop['sklad_id']
							);
					}
					else {
						return array("status"=>"err","err"=>"Не удается вернуть деталь, деталь уже выдана");
					}
                    if($upd_res){
                        if(!$db->query("update zakaz_details set status=40 where id=?i",$this->zakaz_detail_id))
                            return array("status"=>"err","err"=>"Не удается вернуть деталь, невозможно изменить статус детали в заказе");
                    }
                    else {
                        return array("status"=>"err","err"=>"Не удается вернуть деталь, невозможно изменить количество деталей в заказе");
                    }
					break;
			}
		}
	}

    public function Save(){
        $db = DB::getInstance();
        if ($this->id>0) {
			$this->update_date=date("Y-m-d H:i:s");
			if($this->_delivery_to_workshop_det_arr_old['status']!=20 && $this->_delivery_to_workshop_det_arr['status']==50){
				if($this->_delivery_to_workshop_det_arr_old['status']==30) return array("status"=>"err","err"=>"Нельзя вернуть то, что уже установлено в автомобиль");
				if($this->_delivery_to_workshop_det_arr_old['status']==1) return array("status"=>"err","err"=>"Нельзя вернуть то, что еще не забрали");
				if($this->_delivery_to_workshop_det_arr_old['status']==10) return array("status"=>"err","err"=>"Нельзя вернуть то, что еще не забрали");
			}
			$do_status=$this->do_status_action();
			if($do_status['status']=="err") return $do_status;
            $sql="update delivery_to_workshop_details set ?u where id=?i";
            $db->query($sql,$this->_delivery_to_workshop_det_arr,$this->id);
			//echo "db->query($sql,".print_r($this->_sklad_arr,true).",".$this->id.");";
            if ($db->affectedRows()>0) { 
				$this->set_delivery_to_workshop_status();
				return array("status"=>"ok","err"=>"");
			}
	        else { 
				return array("status"=>"ok","err"=>""); 
			}
        }
        else {
            $this->create_date=date("Y-m-d H:i:s");
            $save_data['create_date']=$this->create_date;
            $sql="insert ignore into delivery_to_workshop_details set ?u";
	          //echo $sql." ".print_r($this->_sklad_arr,true);
            $db->query($sql,$this->_delivery_to_workshop_det_arr);
			
            if ($db->affectedRows()>0) {
          		$this->id=$db->insertId();
				$this->set_delivery_to_workshop_status();
          		return array("status"=>"ok","err"=>"");
      	    }
      	    else return array("status"=>"ok","err"=>"");
        }
        //echo "db->query($sql,".print_r($save_data,true).",".$this->user_id.");\n";
        return array("status"=>"err","err"=>$db->error);
	}
	
	public function Delete(){
		$db = DB::getInstance();
		if ($this->id>0) {
			$res=$db->query("delete from delivery_to_workshop_details where id=?i",$this->id);
			if($res){
				return 1;
			}
			else return 0;
		}
	}

	private function set_delivery_to_workshop_status(){
		$db = DB::getInstance();
		$db->query("update delivery_to_workshop set status=(select min(status) from delivery_to_workshop_details where delivery_to_workshop_id=?i) where id=?i",$this->delivery_to_workshop_id,$this->delivery_to_workshop_id);
	}
}
?>
