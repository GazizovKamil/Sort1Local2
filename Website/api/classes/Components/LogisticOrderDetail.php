<?php

namespace Sort1API\Components;

class LogisticOrderDetail
{
	private $_logistic_order_det_arr=array();
	private $_logistic_order_det_arr_old=array();

    private function create_new_logistic_order_detail(){
    	$db= DB::getInstance();
    	$res=$db->getAll("describe logistic_order_details");
    	foreach($res as $key=>$val){
    	    if ($val['Field']=="create_date") $this->_logistic_order_det_arr[$val['Field']]=date("Y-m-d H:i:s");
    	    else {
        		if (preg_match("/int/",$val['Type']) || preg_match("/float/",$val['Type'])) {
        		    if(!empty($val['Default'])) $this->_logistic_order_det_arr[$val['Field']]=$val['Default'];
        		    else $this->_logistic_order_det_arr[$val['Field']]=0;
        		}
        		else {
        		    if(!empty($val['Default'])) $this->_logistic_order_det_arr[$val['Field']]=$val['Default'];
        		    else $this->_logistic_order_det_arr[$val['Field']]="";
        		}
    	    }
    	}
      //$this->_logistic_order_det_arr['main_company_id']=$_SESSION['main_company'];
    }

    function __construct($logistic_order_detail_id = 0){
        if ($logistic_order_detail_id>0)
    	    $this->Load($logistic_order_detail_id);
      	else
      	  $this->create_new_logistic_order_detail();
    }

    public function Load($logistic_order_detail_id)
    {
        $db = DB::getInstance();
        if ($logistic_order_detail_id>0) {
            $logistic_order_detail_data=$db->getRow("select * from logistic_order_details where id=?i",$logistic_order_detail_id);
            //print_r($user_data);
            //echo "select username,roles,create_date,company_id,name,lastname,email,phone,mphone,avatar from users where id=$user_id";
            if (count((array)$logistic_order_detail_data)>0){
          		foreach($logistic_order_detail_data as $key=>$val){
          		    if(!empty($val) || $val == 0) $this->_logistic_order_det_arr[$key]=$val;
          		    else $this->_logistic_order_det_arr[$key]="";
          		}
            }
        }
    }

	public function __get($name) {
		if (isset($this->_logistic_order_det_arr[$name])) {
			return $this->_logistic_order_det_arr[$name];
		} else {
			return null;
		}
	}

	public function __isset($name){
	    return isset($this->_logistic_order_det_arr[$name]);
	}

	public function __set($name,$val) {
		if (isset($this->_logistic_order_det_arr[$name])) {
			$this->_logistic_order_det_arr_old[$name]=$this->_logistic_order_det_arr[$name];
			$this->_logistic_order_det_arr[$name]=$val;
		}
	}

	private function do_status_action(){
		if(isset($this->_logistic_order_det_arr_old['status']) && $this->_logistic_order_det_arr_old['status']!=$this->_logistic_order_det_arr['status']){
			switch($this->_logistic_order_det_arr['status']){
				case 1: //Выбрана для инвентаризации
					
					break;
				case 20: //Идет инвентаризация
					break;
				case 30: //Инвентаризация закончена, кол-во подсчитано
					break;
			}
		}
	}

    public function Save(){
        $db = DB::getInstance();
        if ($this->id>0) {
			$this->update_date=date("Y-m-d H:i:s");
			$this->do_status_action();
            $sql="update logistic_order_details set ?u where id=?i";
            $db->query($sql,$this->_logistic_order_det_arr,$this->id);
            //echo "db->query($sql,".print_r($this->_sklad_arr,true).",".$this->id.");";
            if ($db->affectedRows()>0) { return 1;}
	          else { return 10; }
        }
        else {
            $this->create_date=date("Y-m-d H:i:s");
            $save_data['create_date']=$this->create_date;
            $sql="insert ignore into logistic_order_details set ?u";
	          //echo $sql." ".print_r($this->_sklad_arr,true);
            $db->query($sql,$this->_logistic_order_det_arr);
            if ($db->affectedRows()>0) {
          		$this->id=$db->insertId();
          		return 1;
      	    }
      	    else return 0;
        }
        //echo "db->query($sql,".print_r($save_data,true).",".$this->user_id.");\n";
        return $db->error;
	}
	
	public function Delete(){
		$db = DB::getInstance();
		if ($this->id>0) {
			$res=$db->query("delete from logistic_order_details where id=?i",$this->id);
			if($res){
				return 1;
			}
			else return 0;
		}
	}
}
?>
