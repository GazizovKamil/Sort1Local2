<?php

namespace Sort1API\Components;
use Sort1API\Components\ZakazDetail;
use Sort1API\Components\Zakaz;
use Sort1API\Components\Models\Zakazs;

class LogisticOrder
{
	private $_logistic_order_arr=array();
	private $_logistic_order_arr_old=array();

    private function create_new_logistic_order(){
    	$db= DB::getInstance();
    	$res=$db->getAll("describe logistic_orders");
    	foreach($res as $key=>$val){
    	    if ($val['Field']=="create_date") $this->_logistic_order_arr[$val['Field']]=date("Y-m-d H:i:s");
    	    else {
        		if (preg_match("/int/",$val['Type']) || preg_match("/float/",$val['Type'])) {
        		    if(!empty($val['Default'])) $this->_logistic_order_arr[$val['Field']]=$val['Default'];
        		    else $this->_logistic_order_arr[$val['Field']]=0;
        		}
        		else {
        		    if(!empty($val['Default'])) $this->_logistic_order_arr[$val['Field']]=$val['Default'];
        		    else $this->_logistic_order_arr[$val['Field']]="";
        		}
    	    }
    	}
      $this->_logistic_order_arr['main_company_id']=$_SESSION['main_company'];
    }

    function __construct($logistic_order_id = 0){
        if ($logistic_order_id>0)
    	    $this->Load($logistic_order_id);
      	else
      	  $this->create_new_logistic_order();
    }

    public function Load($logistic_order_id)
    {
        $db = DB::getInstance();
        if ($logistic_order_id>0) {
            $logistic_order_data=$db->getRow("select * from logistic_orders where id=?i",$logistic_order_id);
            //print_r($user_data);
            //echo "select username,roles,create_date,company_id,name,lastname,email,phone,mphone,avatar from users where id=$user_id";
            if (count((array)$logistic_order_data)>0){
          		foreach($logistic_order_data as $key=>$val){
          		    if(!empty($val) || $val == 0) $this->_logistic_order_arr[$key]=$val;
          		    else $this->_logistic_order_arr[$key]="";
          		}
            }
        }
    }

	public function __get($name) {
		if (isset($this->_logistic_order_arr[$name])) {
			return $this->_logistic_order_arr[$name];
		} else {
			return null;
		}
	}

	public function __isset($name){
	    return isset($this->_logistic_order_arr[$name]);
	}

	public function __set($name,$val) {
		if (isset($this->_logistic_order_arr[$name])) {
			$this->_logistic_order_arr_old[$name]=$this->_logistic_order_arr[$name];
			$this->_logistic_order_arr[$name]=$val;
		}
	}

	private function commit_logistic_order(){
		$db = DB::getInstance();
		$this->update_date=date("Y-m-d H:i:s");
					if((int)$this->logistic_order_type==1 || (int)$this->logistic_order_type==2 || (int)$this->logistic_order_type==4){
						// проверим хватает ли денег на балансе для данной перевозки
						$logistic_order_zakaz_sum=0;
						$logistic_order_zakazs=$db->getAll("select distinct(zakaz_id) from logistic_order_details where logistic_order_id=?i",(int)$this->id);
						foreach($logistic_order_zakazs as $loz_key=>$loz_val){
							$zakaz=new Zakaz($loz_val['zakaz_id']);
							$zakaz_close_status=Zakazs::check_balance_for_close($zakaz);
							$logistic_order_zakaz_sum+=$zakaz_close_status['zakaz_sum'];
							$client_balance=$zakaz_close_status['company_balance'];
						}
						//echo $logistic_order_zakaz_sum.">=".$client_balance."\n";
						if($logistic_order_zakaz_sum>$client_balance){
							return array("status"=>0,"msg"=>"на балансе пользователя не хватает денег для оплаты деталей в данной заявке, не хватает ".($logistic_order_zakaz_sum-$client_balance)." руб.");
						}
						//проверим предыдущий статус
						if($this->_logistic_order_arr_old['status']!=10 && $this->_logistic_order_arr_old['status']!=50)
							return array("status"=>0,"msg"=>"Заявка еще не сформирована, принимать можно только заявки со статусом сформировано");
						//проверим указан ли водитель
						if($this->logistic_driver_id<=0 && (int)$this->logistic_order_type!=4)
							return array("status"=>0,"msg"=>"Не указан водитель");
						//проверим тип доставки и баланс в случае доставки до клиента
						//пока не знаю как потому что могут быть детали из разных заказов
						/*if((int)$this->logistic_order_type==2){
							$balance_check=Zakazs::check_balance_for_close($zakaz_id);
							if($balance_check['status']=="err"){
								$balance_check['err_code']=1002;
								return array("status"=>0,"data"=>$balance_check,"msg"=>"Невозможно выдать заказ! Не хватает денег на счете клиента");
							}
						} */
						//надо списать со склада с которого забираются детали
						$logistic_order_details=$db->getAll("select * from logistic_order_details where logistic_order_id=?i",(int)$this->id);
						foreach($logistic_order_details as $lod_key=>$lod_val){
							$detail_data=$db->getRow("select detail_id,count from zakaz_details where id=?i and zakaz_id=?i",$lod_val['zakaz_detail_id'],$lod_val['zakaz_id']);
							//списываем со склада
							$sklad_details_data=$db->getRow("select count,reserved_count from sklad_details where detail_id=?i and sklad_id=?i",$detail_data['detail_id'],$this->from_sklad_id);
							if($sklad_details_data['count']>=$detail_data['count']){
								switch((int)$this->logistic_order_type){
									case 1:
										$db->query("update sklad_details set count=?i,reserved_count=?i where detail_id=?i and sklad_id=?i",
										($sklad_details_data['count']-$detail_data['count']),
										($sklad_details_data['reserved_count']-$detail_data['count']),
										$detail_data['detail_id'],
										$this->from_sklad_id
										);
										//надо изменить статус в заказе у детали на в пути на склад
										$db->query("update zakaz_details set status=30 where zakaz_id=?i and id=?i and status<30",$lod_val['zakaz_id'],$lod_val['zakaz_detail_id']);
										//$db->query("update zakaz set status=(select max(id) from zakaz_statuses where id<=(select min(status) from zakaz_details where zakaz_id=?i and reorder_detail_id=0)) where id=?i",$lod_val['zakaz_id'],$lod_val['zakaz_id']);
										$zakaz_status=$db->getOne("select max(id) from zakaz_statuses where id<=(select min(status) from zakaz_details where zakaz_id=?i and reorder_detail_id=0)",$lod_val['zakaz_id']);
										$zakaz=new Zakaz($lod_val['zakaz_id']);
										$zakaz->status=$zakaz_status;
										$zakaz->save();
										break;
									case 2: 
										$db->query("update sklad_details set count=?i,reserved_count=?i where detail_id=?i and sklad_id=?i",
										$sklad_details_data['count']-$detail_data['count'],
										$sklad_details_data['reserved_count']-$detail_data['count'],
										$detail_data['detail_id'],
										$this->from_sklad_id
										);
										//надо изменить статус в заказе у детали на доставляется
										$db->query("update zakaz_details set status=60 where zakaz_id=?i and id=?i and status<60",$lod_val['zakaz_id'],$lod_val['zakaz_detail_id']);
										//add to log change status
										$sql_stat="insert into zakaz_detail_status_log (create_date,zakaz_detail_id,status,change_by_user_id) values (?s,?i,?i,?i)";
										$db->query($sql_stat,date("Y-m-d H:i:s"),$lod_val['zakaz_detail_id'],60,$_SESSION['user_id']);
										//$db->query("update zakaz set status=(select max(id) from zakaz_statuses where id<=(select min(status) from zakaz_details where zakaz_id=?i and reorder_detail_id=0)) where id=?i",$lod_val['zakaz_id'],$lod_val['zakaz_id']);
										$zakaz_status=$db->getOne("select max(id) from zakaz_statuses where id<=(select min(status) from zakaz_details where zakaz_id=?i and reorder_detail_id=0)",$lod_val['zakaz_id']);
										$zakaz=new Zakaz($lod_val['zakaz_id']);
										$zakaz->status=$zakaz_status;
										$zakaz->save();
										break;
									case 4:
										$db->query("update sklad_details set count=?i,reserved_count=?i where detail_id=?i and sklad_id=?i",
										$sklad_details_data['count']-$detail_data['count'],
										$sklad_details_data['reserved_count']-$detail_data['count'],
										$detail_data['detail_id'],
										$this->from_sklad_id
										);
										//надо изменить статус в заказе у детали на доставляется
										$db->query("update zakaz_details set status=60 where zakaz_id=?i and id=?i and status<60",$lod_val['zakaz_id'],$lod_val['zakaz_detail_id']);
										//add to log change status
										$sql_stat="insert into zakaz_detail_status_log (create_date,zakaz_detail_id,status,change_by_user_id) values (?s,?i,?i,?i)";
										$db->query($sql_stat,date("Y-m-d H:i:s"),$lod_val['zakaz_detail_id'],60,$_SESSION['user_id']);
										//$db->query("update zakaz set status=(select max(id) from zakaz_statuses where id<=(select min(status) from zakaz_details where zakaz_id=?i and reorder_detail_id=0)) where id=?i",$lod_val['zakaz_id'],$lod_val['zakaz_id']);
										$zakaz_status=$db->getOne("select max(id) from zakaz_statuses where id<=(select min(status) from zakaz_details where zakaz_id=?i and reorder_detail_id=0)",$lod_val['zakaz_id']);
										$zakaz=new Zakaz($lod_val['zakaz_id']);
										$zakaz->status=$zakaz_status;
										$zakaz->save();//скопировал с 2 статуса
										break;
								}

							}
						}
						return array("status"=>1,"msg"=>"");
					}
					else { // внутреннее перемещение
						if($this->_logistic_order_arr_old['status']!=10 && $this->_logistic_order_arr_old['status']!=50)
							return array("status"=>0,"msg"=>"Заявка еще не сформирована, принимать можно только заявки со статусом сформировано");
						$logistic_order_details=$db->getAll("select * from logistic_order_details where logistic_order_id=?i",(int)$this->id);
						foreach($logistic_order_details as $lod_key=>$lod_val){
							$sklad_details_data=$db->getRow("select count,reserved_count from sklad_details where detail_id=?i and sklad_id=?i",$lod_val['detail_id'],$this->from_sklad_id);
							if(($sklad_details_data['count']-$lod_val['count'])<0){
								return array("status"=>0,"msg"=>"На складе не хватает деталей для перевозки, на складе:".$sklad_details_data['count'].", необходимо:".$lod_val['count']);
							}
							$db->query("update sklad_details set count=?i where detail_id=?i and sklad_id=?i",
										$sklad_details_data['count']-$lod_val['count'],
										$lod_val['detail_id'],
										$this->from_sklad_id
										);
						}
						return array("status"=>1,"msg"=>"");
					}
	}

	private function do_status_action(){ 
		$db = DB::getInstance();
		if(isset($this->_logistic_order_arr_old['status']) && (int)$this->_logistic_order_arr_old['status']!=(int)$this->status){
			//статус сменился
			switch((int)$this->status){
				case 1: //зарегистрирована
				break;
				case 20: // принята
					$status=$this->commit_logistic_order();
					//echo print_r($status,true);
					if($status['status']==0) return $status;
					else return $status;
				break;
				case 40: //выполнена
					if((int)$this->_logistic_order_arr_old['status']<20){
						$status_done=$this->commit_logistic_order();
						if($status_done['status']==0) return $status_done;
					}
					$this->closed_date=date("Y-m-d H:i:s");
					if((int)$this->logistic_order_type==1){
						// это внутреннее перемещение
						
						// списать со склада водителя - склад решил не делать что у водителя смотреть по статусу принята
						//проверим предыдущий статус
						if($this->_logistic_order_arr_old['status']!=20)
							return array("status"=>0,"msg"=>"Статусы меняются по порядку, нельзя перескакивать");
						//проверим указан ли водитель
						if($this->logistic_driver_id<=0)
							return array("status"=>0,"msg"=>"Не указан водитель");
						// приписать на склад прибытия
						$logistic_order_details=$db->getAll("select * from logistic_order_details where logistic_order_id=?i",(int)$this->id);
						foreach($logistic_order_details as $lod_key=>$lod_val){
							$detail_data=$db->getRow("select detail_id,count from zakaz_details where id=?i and zakaz_id=?i",$lod_val['zakaz_detail_id'],$lod_val['zakaz_id']);
							//списываем со склада
							$sklad_details_data=$db->getRow("select * from sklad_details where detail_id=?i and sklad_id=?i",$detail_data['detail_id'],$this->to_sklad_id);
							//проверим есть ли на на складе данная деталь
							if(isset($sklad_details_data['count'])){
								$from_sklad_details_price=$db->getOne("select price from sklad_details where detail_id=?i and sklad_id=?i",$detail_data['detail_id'],$this->from_sklad_id);
								if($sklad_details_data['price']<=0){
									$db->query("update sklad_details set price=?s where detail_id=?i and sklad_id=?i",$from_sklad_details_price,$detail_data['detail_id'],$this->to_sklad_id);
								}
								switch((int)$this->logistic_order_type){
									case 1: $zakaz_detail=new ZakazDetail($lod_val['zakaz_detail_id']);
										if($zakaz_detail->status<40){
											$zakaz_detail->status=40;
											$zakaz_detail->save();
										}
										if(($sklad_details_data['count']+$detail_data['count'])>0)
											$sklad_details_data['price']=(($sklad_details_data['price']*$sklad_details_data['count'])+($from_sklad_details_price*$detail_data['count']))/($sklad_details_data['count']+$detail_data['count']);
										$db->query("update sklad_details set count=?i,reserved_count=?i,price=?s where detail_id=?i and sklad_id=?i",
											$sklad_details_data['count']+$detail_data['count'],
											$sklad_details_data['reserved_count']+$detail_data['count'],
											$sklad_details_data['price'],
											$detail_data['detail_id'],
											$this->to_sklad_id
										);
										break;
									case 3:
										$db->query("update sklad_details set count=?i where detail_id=?i and sklad_id=?i",
											$sklad_details_data['count']+$detail_data['count'],
											//$sklad_details_data['reserved_count']+$detail_data['count'],
											$detail_data['detail_id'],
											$this->to_sklad_id
										);
										break;
								}

							}
							else { 
								//детали нет на складе надо завести
								$sklad_details_data=$db->getRow("select * from sklad_details where detail_id=?i and sklad_id=?i",$detail_data['detail_id'],$this->from_sklad_id);
								$sklad_details_data['count']=$detail_data['count'];
								$sklad_details_data['reserved_count']=$detail_data['count'];
								$sklad_details_data['sklad_id']=$this->to_sklad_id;
								//$sklad_details_data['status']=40; //готов к выдаче
								$db->query("update zakaz_details set status=40 where zakaz_id=?i and id=?i and status<40",$lod_val['zakaz_id'],$lod_val['zakaz_detail_id']);
								if($db->affectedRows()>0){
									$sql_stat="insert into zakaz_detail_status_log (create_date,zakaz_detail_id,status,change_by_user_id) values (?s,?i,?i,?i)";
									$db->query($sql_stat,date("Y-m-d H:i:s"),$lod_val['zakaz_detail_id'],40,$_SESSION['user_id']);
								}
								$sql="insert ignore into sklad_details set ?u";
								$db->query($sql,$sklad_details_data);
								if($db->affectedRows()>0){
									//надо делать обновление статуса через класс Zakaz чтобы создавалась заявка на перемещение до клиента
									$zakaz_status=$db->getOne("select max(id) from zakaz_statuses where id<=(select min(status) from zakaz_details where zakaz_id=?i and reorder_detail_id=0)",$lod_val['zakaz_id']);
									$zakaz=new Zakaz($lod_val['zakaz_id']);
									$zakaz->status=$zakaz_status;
									$zakaz->save();
								} 
							}
						}
						return array("status"=>1,"msg"=>"");
					}
					if((int)$this->logistic_order_type==2){
						//доставка до адресата
						if($this->_logistic_order_arr_old['status']!=20)
							return array("status"=>0,"msg"=>"Статусы меняются по порядку, нельзя перескакивать");
						//проверим указан ли водитель
						if($this->logistic_driver_id<=0)
							return array("status"=>0,"msg"=>"Не указан водитель");
						// приписать на склад прибытия
						$logistic_order_details=$db->getAll("select distinct(zakaz_id) from logistic_order_details where logistic_order_id=?i",(int)$this->id);
						foreach($logistic_order_details as $lod_key=>$lod_val){
							$request=json_decode(json_encode(array("zakaz_id"=>$lod_val['zakaz_id'])));
							$zakaz_close_status=Zakazs::close_zakaz($request,$db,1);
							if($zakaz_close_status['status']=="err")
								return array("status"=>0,"msg"=>$zakaz_close_status['error']);
						}
						return array("status"=>1,"msg"=>"");
					}
					if((int)$this->logistic_order_type==3){ // внутреннее перемещение
						$logistic_order_details=$db->getAll("select * from logistic_order_details where logistic_order_id=?i",(int)$this->id);
						foreach($logistic_order_details as $lod_key=>$lod_val){
							$sklad_details_data=$db->getRow("select * from sklad_details where detail_id=?i and sklad_id=?i",$lod_val['detail_id'],$this->to_sklad_id);
							if(!isset($sklad_details_data['count'])){
								$sklad_details_data=$db->getRow("select * from sklad_details where detail_id=?i and sklad_id=?i",$lod_val['detail_id'],$this->from_sklad_id);
								$sklad_details_data['count']=$lod_val['count'];
								//$sklad_details_data['reserved_count']=$lod_val['count'];
								$sklad_details_data['sklad_id']=$this->to_sklad_id;
								$sklad_details_data['invent_blocked']=0;
								$sql="insert ignore into sklad_details set ?u";
								$db->query($sql,$sklad_details_data);
							}
							else {
								//$sklad_details_data=$db->getRow("select count,reserved_count from sklad_details where detail_id=?i and sklad_id=?i",$lod_val['detail_id'],$this->to_sklad_id);
								$from_sklad_details_price=$db->getOne("select price from sklad_details where detail_id=?i and sklad_id=?i",$lod_val['detail_id'],$this->from_sklad_id);
								if(($sklad_details_data['count']+$lod_val['count'])>0)
									$sklad_details_data['price']=(($sklad_details_data['price']*$sklad_details_data['count'])+($from_sklad_details_price*$lod_val['count']))/($sklad_details_data['count']+$lod_val['count']);
								$db->query("update sklad_details set count=?i,price=?s where detail_id=?i and sklad_id=?i",
									$sklad_details_data['count']+$lod_val['count'],
									$sklad_details_data['price'],
									$lod_val['detail_id'],
									$this->to_sklad_id
								);
							}
						}
						return array("status"=>1,"msg"=>"");
					}
					break;
				case 50: //Возвращена на склад
					//проверим предыдущий статус
					if($this->_logistic_order_arr_old['status']!=20){
						if($this->_logistic_order_arr_old['status']==40) return array("status"=>0,"msg"=>"Нельзя вернуть то, что уже оприходовано на другом складе");
						if($this->_logistic_order_arr_old['status']==1) return array("status"=>0,"msg"=>"Нельзя вернуть то, что еще не забрали");
						if($this->_logistic_order_arr_old['status']==10) return array("status"=>0,"msg"=>"Нельзя вернуть то, что еще не забрали");
					}
					//проверим указан ли водитель
					if($this->logistic_driver_id<=0)
						return array("status"=>0,"msg"=>"Не указан водитель");
					//надо возвратить на склад с которого забирались детали
					$logistic_order_details=$db->getAll("select * from logistic_order_details where logistic_order_id=?i",(int)$this->id);
					foreach($logistic_order_details as $lod_key=>$lod_val){
						$detail_data=$db->getRow("select detail_id,count from zakaz_details where id=?i and zakaz_id=?i",$lod_val['zakaz_detail_id'],$lod_val['zakaz_id']);
						//возвращаем на склад с которого забирали
						$sklad_details_data=$db->getRow("select count,reserved_count from sklad_details where detail_id=?i and sklad_id=?i",$detail_data['detail_id'],$this->from_sklad_id);
						switch((int)$this->logistic_order_type){
							case 1:
							case 2:
								$db->query("update sklad_details set count=?i,reserved_count=?i where detail_id=?i and sklad_id=?i",
									$sklad_details_data['count']+$detail_data['count'],
									$sklad_details_data['reserved_count']+$detail_data['count'],
									$detail_data['detail_id'],
									$this->from_sklad_id
								);
								//надо изменить статус в заказе у детали на формируется доставка
								$db->query("update zakaz_details set status=40 where zakaz_id=?i and id=?i and status<40",$lod_val['zakaz_id'],$lod_val['zakaz_detail_id']);
								//add to log change status
								$sql_stat="insert into zakaz_detail_status_log (create_date,zakaz_detail_id,status,change_by_user_id) values (?s,?i,?i,?i)";
								$db->query($sql_stat,date("Y-m-d H:i:s"),$lod_val['zakaz_detail_id'],40,$_SESSION['user_id']);
								//$db->query("update zakaz set status=(select max(id) from zakaz_statuses where id<=(select min(status) from zakaz_details where zakaz_id=?i and reorder_detail_id=0)) where id=?i",$lod_val['zakaz_id'],$lod_val['zakaz_id']);
								$zakaz_status=$db->getOne("select max(id) from zakaz_statuses where id<=(select min(status) from zakaz_details where zakaz_id=?i and reorder_detail_id=0)",$lod_val['zakaz_id']);
								$zakaz=new Zakaz($lod_val['zakaz_id']);
								$zakaz->status=$zakaz_status;
								$zakaz->save();
							break;
							case 3:
								//$sklad_details_data=$db->getRow("select count,reserved_count from sklad_details where detail_id=?i and sklad_id=?i",$detail_data['detail_id'],$this->from_sklad_id);
								$db->query("update sklad_details set count=count+?i where detail_id=?i and sklad_id=?i",
									$lod_val['count'],
									$lod_val['detail_id'],
									$this->from_sklad_id
								);
							break;
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
            $sql="update logistic_orders set ?u where id=?i";
            $db->query($sql,$this->_logistic_order_arr,$this->id);
            //echo "db->query($sql,".print_r($this->_sklad_arr,true).",".$this->id.");";
            if ($db->affectedRows()>0) { return array("status"=>1,"msg"=>"");}
	          else { return array("status"=>10,"msg"=>""); }
        }
        else {
            $this->create_date=date("Y-m-d H:i:s");
            $save_data['create_date']=$this->create_date;
            $sql="insert ignore into logistic_orders set ?u";
	          //echo $sql." ".print_r($this->_sklad_arr,true);
            $db->query($sql,$this->_logistic_order_arr);
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
