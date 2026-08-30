<?php

namespace Sort1API\Components;

class Zakaz
{
	private $_zakaz_arr=array();
	private $_zakaz_arr_old=array();

    private function create_new_zakaz(){
		$db= DB::getInstance();
		$res=$db->getAll("describe zakaz");
		foreach($res as $key=>$val){
			if ($val['Field']=="create_date") $this->_zakaz_arr[$val['Field']]=date("Y-m-d H:i:s");
			else {
				if (preg_match("/int/",$val['Type']) || preg_match("/float/",$val['Type'])) {
					if(!empty($val['Default'])) $this->_zakaz_arr[$val['Field']]=$val['Default'];
					else $this->_zakaz_arr[$val['Field']]=0;
				}
				else {
					if(!empty($val['Default'])) $this->_zakaz_arr[$val['Field']]=$val['Default'];
					else $this->_zakaz_arr[$val['Field']]="";
				}
			}
			if ($val['Field']=="user_id") $this->_zakaz_arr[$val['Field']]=$_SESSION['user_id'];
			if ($val['Field']=="main_company_id") $this->_zakaz_arr[$val['Field']]=$_SESSION['main_company'];
		}
		$this->Save();
    } 

    function __construct($zakaz_id = 0){
        if ($zakaz_id>0)
    	    $this->Load($zakaz_id);
	else {
	    $this->create_new_zakaz();
	}
    }

    public function Load($zakaz_id)
    {
        $db = DB::getInstance();
        if ($zakaz_id>0) {
            $zakaz_data=$db->getRow("select * from zakaz where id=?i",$zakaz_id);
            //print_r($user_data);
            //echo "select username,roles,create_date,company_id,name,lastname,email,phone,mphone,avatar from users where id=$user_id";
            if (count((array)$zakaz_data)>0){
		foreach($zakaz_data as $key=>$val){
		    $this->_zakaz_arr[$key]=$val;
		}
            }
        }
    }

	public function __get($name) {
		if (isset($this->_zakaz_arr[$name])) {
			return $this->_zakaz_arr[$name];
		} else {
			return null;
		}
	}

	public function __isset($name){
	    return isset($this->_zakaz_arr[$name]);
	}

	public function __set($name,$val) {
		if (isset($this->_zakaz_arr[$name])) {
			$this->_zakaz_arr_old[$name]=$this->_zakaz_arr[$name];
			$this->_zakaz_arr[$name]=$val;
		}
	}

	private function do_status_action(){
		$db= DB::getInstance();
    	$err=1;
		if(isset($this->_zakaz_arr_old['status']) && (int)$this->_zakaz_arr_old['status']!=(int)$this->status){
			switch((int)$this->status){
				case 37:
				case 40:
					if(($this->delivery_type==2 || $this->delivery_type==4) && $this->logistic_order_id==0){
						$log_order = $db->getAll('SELECT * FROM logistic_orders lo where lo.zakaz_id = ?i', $this->id);
						if (count((array)$log_order)==0) {//сказать про такую проверку
							$logistic_order=new LogisticOrder();
							$logistic_order->from_company_id=$this->main_company_id;
							$logistic_order->to_company_id=$this->company_id;
							$logistic_order->from_sklad_id=$this->fullfilment_id;
							$logistic_order->to_sklad_id=$this->delivery_type_id;
							$logistic_order->zakaz_id = $this->id;//для zakaz_id
							if ($this->delivery_type==2) $logistic_order->logistic_order_type=2;
							else if ($this->delivery_type==4) $logistic_order->logistic_order_type=4;
							$logistic_order->status=10;
							$logistic_order->save();
							$zakaz_details=$db->getAll("select id from zakaz_details where zakaz_id=?i",$this->id);
							foreach($zakaz_details as $zd_key=>$zd_val){
								$logistic_order_detail=new LogisticOrderDetail();
								$logistic_order_detail->logistic_order_id=$logistic_order->id;
								$logistic_order_detail->zakaz_detail_id=$zd_val["id"];
								$logistic_order_detail->zakaz_id=$this->id;
								$logistic_order_detail->status=10;
								$logistic_order_detail->save();
							}
							$this->logistic_order_id=$logistic_order->id;
							//$zakaz->save();
						}
					}
			}
		}
	}

    public function Save(){
		if($this->company_id==0) return 0;
        $db = DB::getInstance();
        if ($this->id>0) {
			$this->do_status_action();
			$this->update_date=date("Y-m-d H:i:s");
            $sql="update zakaz set ?u where id=?i";
            $db->query($sql,$this->_zakaz_arr,$this->id);
            //echo "db->query($sql,".print_r($this->_sklad_arr,true).",".$this->id.");";
            if ($db->affectedRows()>0) { return 1;}
	          else { return 1; }
        }
        else {
            $this->create_date=date("Y-m-d H:i:s");
			$this->update_date='0000-00-00';
            $save_data['create_date']=$this->create_date;
            $sql="insert ignore into zakaz set ?u";
	    //echo $sql." ".print_r($this->_sklad_arr,true);
            $db->query($sql,$this->_zakaz_arr);
            if ($db->affectedRows()>0) {
		            $this->id=$db->insertId();
		            return 1;
	          }
	          else return 0;
        }
        //echo "db->query($sql,".print_r($save_data,true).",".$this->user_id.");\n";
        return $db->error;
    }

}
?>
