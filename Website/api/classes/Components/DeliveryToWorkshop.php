<?php

namespace Sort1API\Components;
use Sort1API\Components\ZakazDetail;
use Sort1API\Components\Zakaz;
use Sort1API\Components\Models\Zakazs;

class DeliveryToWorkshop
{
	private $_delivery_to_workshop_arr=array();
	private $_delivery_to_workshop_arr_old=array();

    private function create_new_delivery_to_workshop(){
    	$db= DB::getInstance();
    	$res=$db->getAll("describe delivery_to_workshop");
    	foreach($res as $key=>$val){
    	    if ($val['Field']=="create_date") $this->_delivery_to_workshop_arr[$val['Field']]=date("Y-m-d H:i:s");
    	    else {
        		if (preg_match("/int/",$val['Type']) || preg_match("/float/",$val['Type'])) {
        		    if(!empty($val['Default'])) $this->_delivery_to_workshop_arr[$val['Field']]=$val['Default'];
        		    else $this->_delivery_to_workshop_arr[$val['Field']]=0;
        		}
        		else {
        		    if(!empty($val['Default'])) $this->_delivery_to_workshop_arr[$val['Field']]=$val['Default'];
        		    else $this->_delivery_to_workshop_arr[$val['Field']]="";
        		}
    	    }
    	}
      $this->_delivery_to_workshop_arr['main_company_id']=$_SESSION['main_company'];
      $this->_delivery_to_workshop_arr['user_id']=$_SESSION['user_id'];
	  $this->_delivery_to_workshop_arr['service_id']=$_SESSION['my_service_id'];
    }

    function __construct($delivery_to_workshop_id = 0, $sklad_id=0, $zakaz_id=0, $employee_id=0){
        if ($delivery_to_workshop_id>0)
    	    $this->Load($delivery_to_workshop_id);
      	else{
		  if($sklad_id>0 && $zakaz_id>0 && $employee_id>0){
			$this->LoadExist($sklad_id,$zakaz_id,$employee_id);
		  }
		  else {
      	  	$this->create_new_delivery_to_workshop();
		  }
		}
    }

    public function Load($delivery_to_workshop_id)
    {
        $db = DB::getInstance();
        if ($delivery_to_workshop_id>0) {
            $delivery_to_workshop_data=$db->getRow("select * from delivery_to_workshop where id=?i",$delivery_to_workshop_id);
            //print_r($user_data);
            //echo "select username,roles,create_date,company_id,name,lastname,email,phone,mphone,avatar from users where id=$user_id";
            if (count((array)$delivery_to_workshop_data)>0){
          		foreach($delivery_to_workshop_data as $key=>$val){
          		    if(!empty($val) || $val == 0) $this->_delivery_to_workshop_arr[$key]=$val;
          		    else $this->_delivery_to_workshop_arr[$key]="";
          		}
            }
        }
    }

	public function LoadExist($sklad_id,$zakaz_id,$employee_id)
    {
        $db = DB::getInstance();
        $delivery_to_workshop_data=$db->getRow("select * from delivery_to_workshop where sklad_id=?i and zakaz_id=?i and employee_id=?i",$sklad_id,$zakaz_id,$employee_id);
        //print_r($user_data);
        //echo "select username,roles,create_date,company_id,name,lastname,email,phone,mphone,avatar from users where id=$user_id";
        if (count((array)$delivery_to_workshop_data)>0){
        	foreach($delivery_to_workshop_data as $key=>$val){
        	    if(!empty($val)) $this->_delivery_to_workshop_arr[$key]=$val;
        	    else $this->_delivery_to_workshop_arr[$key]="";
        	}
        }
		else {
			$this->create_new_delivery_to_workshop();
			$this->sklad_id=$sklad_id;
			$this->zakaz_id=$zakaz_id;
			$this->employee_id=$employee_id;
		}
    }

	public function __get($name) {
		if (isset($this->_delivery_to_workshop_arr[$name])) {
			return $this->_delivery_to_workshop_arr[$name];
		} else {
			return null;
		}
	}

	public function __isset($name){
	    return isset($this->_delivery_to_workshop_arr[$name]);
	}

	public function __set($name,$val) {
		if (isset($this->_delivery_to_workshop_arr[$name])) {
			$this->_delivery_to_workshop_arr_old[$name]=$this->_delivery_to_workshop_arr[$name];
			$this->_delivery_to_workshop_arr[$name]=$val;
		}
	}

	private function do_status_action(){ 
		$db = DB::getInstance();
		if(isset($this->_delivery_to_workshop_arr_old['status']) && (int)$this->_delivery_to_workshop_arr_old['status']!=(int)$this->status){
			//статус сменился
			switch((int)$this->status){
				case 1: //зарегистрирована
					if($this->_delivery_to_workshop_arr_old['status']>1) return array("status"=>0,"msg"=>"Недоступно");
				    break;
				case 10:
					if($this->_delivery_to_workshop_arr_old['status']>10) return array("status"=>0,"msg"=>"Недоступно");
					break;
				case 20: // Выдана
					$this->update_date=date("Y-m-d H:i:s");
					if($this->_delivery_to_workshop_arr_old['status']!=20){
						if($this->_delivery_to_workshop_arr_old['status']==30) return array("status"=>0,"msg"=>"Нельзя выдать то, что уже установлено в автомобиль");
						if($this->_delivery_to_workshop_arr_old['status']==1) return array("status"=>0,"msg"=>"Заявка еще не сформирована");
						//if($this->_delivery_to_workshop_arr_old['status']==10) return array("status"=>0,"msg"=>"Нельзя установить то, что еще не забрали");
					}
						//if($this->_delivery_to_workshop_arr_old['status']!=10 && $this->_delivery_to_workshop_arr_old['status']!=50)
						//	return array("status"=>0,"msg"=>"Заявка еще не сформирована, принимать можно только заявки со статусом сформировано");
						$delivery_to_workshop_details=$db->getAll("select * from delivery_to_workshop_details where delivery_to_workshop_id=?i",(int)$this->id);
						foreach($delivery_to_workshop_details as $lod_key=>$lod_val){
							$sklad_details_data=$db->getRow("select count,reserved_count from sklad_details where detail_id=?i and sklad_id=?i",$lod_val['detail_id'],$this->sklad_id);
							if(($sklad_details_data['count']-$lod_val['count'])<0){
								return array("status"=>0,"msg"=>"На складе не хватает деталей для выдачи, на складе:".$sklad_details_data['count'].", необходимо:".$lod_val['count']);
							}
							$upd_res=$db->query("update sklad_details set count=?i where detail_id=?i and sklad_id=?i",
										$sklad_details_data['count']-$lod_val['count'],
										$lod_val['detail_id'],
										$this->sklad_id
										);
                            if($upd_res){
								$this->delivered_count=$this->count;
                                //изменить статус детали в заказе
                                if(!$db->query("update zakaz_details set status=51 where id=?i",$lod_val['zakaz_detail_id']))
                                    return array("status"=>0,"msg"=>"Не удается выдать деталь, невозможно изменить статус детали в заказе");
                                $db->query("update delivery_to_workshop_details set status=20 where delivery_to_workshop_id=?i",$this->id);
                            }
                            else {
                                return array("status"=>0,"msg"=>"Не удается вернуть деталь, невозможно изменить количество деталей в заказе");
                            }
						}
				    break;
				case 30: // Установлена в авто
					$this->update_date=date("Y-m-d H:i:s");
					if($this->_delivery_to_workshop_arr_old['status']!=20){
						if($this->_delivery_to_workshop_arr_old['status']==50) return array("status"=>0,"msg"=>"Нельзя установить то, что возвращено на склад");
						if($this->_delivery_to_workshop_arr_old['status']==1) return array("status"=>0,"msg"=>"Нельзя установить то, что еще не забрали");
						if($this->_delivery_to_workshop_arr_old['status']==10) return array("status"=>0,"msg"=>"Нельзя установить то, что еще не забрали");
					}	
					//if($this->_delivery_to_workshop_arr_old['status']!=10 && $this->_delivery_to_workshop_arr_old['status']!=50)
					//	return array("status"=>0,"msg"=>"Заявка еще не сформирована, принимать можно только заявки со статусом сформировано");
					$delivery_to_workshop_details=$db->getAll("select * from delivery_to_workshop_details where delivery_to_workshop_id=?i",(int)$this->id);
					foreach($delivery_to_workshop_details as $lod_key=>$lod_val){
						//изменить статус детали в заказе
						if(!$db->query("update zakaz_details set status=52 where id=?i",$lod_val['zakaz_detail_id']))
							return array("status"=>0,"msg"=>"Не удается выдать деталь, невозможно изменить статус детали в заказе");
						$db->query("update delivery_to_workshop_details set status=30 where delivery_to_workshop_id=?i",$this->id);
					}
					break;
				case 50: //Возвращена на склад
					//проверим предыдущий статус
					if($this->_delivery_to_workshop_arr_old['status']!=20){
						if($this->_delivery_to_workshop_arr_old['status']==30) return array("status"=>0,"msg"=>"Нельзя вернуть то, что уже установлено в автомобиль");
						if($this->_delivery_to_workshop_arr_old['status']==1) return array("status"=>0,"msg"=>"Нельзя вернуть то, что еще не забрали");
						if($this->_delivery_to_workshop_arr_old['status']==10) return array("status"=>0,"msg"=>"Нельзя вернуть то, что еще не забрали");
					}
					//проверим указан ли водитель
					if($this->employee_id<=0)
						return array("status"=>0,"msg"=>"Не указан работник кому выдается деталь");
					//надо возвратить на склад с которого забирались детали
					$delivery_to_workshop_details=$db->getAll("select * from delivery_to_workshop_details where delivery_to_workshop_id=?i",(int)$this->id);
					foreach($delivery_to_workshop_details as $lod_key=>$lod_val){
						$detail_data=$db->getRow("select detail_id,count from zakaz_details where id=?i and zakaz_id=?i",$lod_val['zakaz_detail_id'],$lod_val['zakaz_id']);
						//возвращаем на склад с которого забирали
						$sklad_details_data=$db->getRow("select count,reserved_count from sklad_details where detail_id=?i and sklad_id=?i",$detail_data['detail_id'],$this->from_sklad_id);
								$upd_res=$db->query("update sklad_details set count=count+?i where detail_id=?i and sklad_id=?i",
									$lod_val['count'],
									$lod_val['detail_id'],
									$this->sklad_id
								);
                                if($upd_res){
									//$this->returned_count=$this->count;
									//$this->delivered_count=0;
									$db->query("update delivery_to_workshop_details set returned_count=?i,delivered_count=?i where id=?i",$lod_val['count'],0,$lod_val['id']);
                                    //изменить статус детали в заказе
                                    if(!$db->query("update zakaz_details set status=40 where id=?i",$lod_val['zakaz_detail_id']))
                                        return array("status"=>0,"msg"=>"Не удается вернуть деталь, невозможно изменить статус детали в заказе");
                                    $db->query("update delivery_to_workshop_details set status=50 where delivery_to_workshop_id=?i",$this->id);    
                                }
                                else {
                                    return array("status"=>0,"msg"=>"Не удается вернуть деталь, невозможно изменить количество деталей в заказе");
                                }
                                
					}
					return array("status"=>1,"msg"=>"");
					break;
			}
		}
		return array("status"=>1,"msg"=>"");
	}

    public function Save(){
        $db = DB::getInstance();
        if ($this->id>0) {
			$status_action=$this->do_status_action();
			if($status_action['status']<=0)
				return array("status"=>$status_action['status'],"msg"=>$status_action['msg']);
            $sql="update delivery_to_workshop set ?u where id=?i";
            $db->query($sql,$this->_delivery_to_workshop_arr,$this->id);
            //echo "db->query($sql,".print_r($this->_sklad_arr,true).",".$this->id.");";
            if ($db->affectedRows()>0) { return array("status"=>1,"msg"=>"");}
	          else { return array("status"=>10,"msg"=>""); }
        }
        else {
            $this->create_date=date("Y-m-d H:i:s");
            $save_data['create_date']=$this->create_date;
            $sql="insert ignore into delivery_to_workshop set ?u";
	          //echo $sql." ".print_r($this->_sklad_arr,true);
            $db->query($sql,$this->_delivery_to_workshop_arr);
            if ($db->affectedRows()>0) {
          		$this->id=$db->insertId();
          		return array("status"=>1,"msg"=>"");
      	    }
      	    else return array("status"=>0,"msg"=>"Ошибка при сохранении");
        }
        //echo "db->query($sql,".print_r($save_data,true).",".$this->user_id.");\n";
        return $db->error;
    }
}
?>
