<?php

namespace Sort1API\Components;

class LogisticCar
{
    private $_logistic_car_arr=array();

    private function create_new_logistic_car(){
    	$db= DB::getInstance();
    	$res=$db->getAll("describe logistic_cars");
    	foreach($res as $key=>$val){
    	    if ($val['Field']=="create_date") $this->_logistic_car_arr[$val['Field']]=date("Y-m-d H:i:s");
    	    else {
        		if (preg_match("/int/",$val['Type']) || preg_match("/float/",$val['Type'])) {
        		    if(!empty($val['Default'])) $this->_logistic_car_arr[$val['Field']]=$val['Default'];
        		    else $this->_logistic_car_arr[$val['Field']]=0;
        		}
        		else {
        		    if(!empty($val['Default'])) $this->_logistic_car_arr[$val['Field']]=$val['Default'];
        		    else $this->_logistic_car_arr[$val['Field']]="";
        		}
    	    }
    	}
      $this->_logistic_car_arr['main_company_id']=$_SESSION['main_company'];
    }

    function __construct($logistic_car_id = 0){
        if ($logistic_car_id>0)
    	    $this->Load($logistic_car_id);
      	else
      	  $this->create_new_logistic_car();
    }

    public function Load($logistic_car_id)
    {
        $db = DB::getInstance();
        if ($logistic_car_id>0) {
            $logistic_car_data=$db->getRow("select * from logistic_cars where id=?i",$logistic_car_id);
            //print_r($user_data);
            //echo "select username,roles,create_date,company_id,name,lastname,email,phone,mphone,avatar from users where id=$user_id";
            if (count((array)$logistic_car_data)>0){
          		foreach($logistic_car_data as $key=>$val){
          		    if(!empty($val) || $val == 0) $this->_logistic_car_arr[$key]=$val;
          		    else $this->_logistic_car_arr[$key]="";
          		}
            }
        }
    }

	public function __get($name) {
		if (isset($this->_logistic_car_arr[$name])) {
			return $this->_logistic_car_arr[$name];
		} else {
			return null;
		}
	}

	public function __isset($name){
	    return isset($this->_logistic_car_arr[$name]);
	}

	public function __set($name,$val) {
		if (isset($this->_logistic_car_arr[$name])) {
			$this->_logistic_car_arr[$name]=$val;
		}
	}

    public function Save(){
        $db = DB::getInstance();
        if ($this->id>0) {
            $sql="update logistic_cars set ?u where id=?i";
            $db->query($sql,$this->_logistic_car_arr,$this->id);
            //echo "db->query($sql,".print_r($this->_sklad_arr,true).",".$this->id.");";
            if ($db->affectedRows()>0) { return 1;}
	          else { return 10; }
        }
        else {
            $this->create_date=date("Y-m-d H:i:s");
            $save_data['create_date']=$this->create_date;
            $sql="insert ignore into logistic_cars set ?u";
	          //echo $sql." ".print_r($this->_sklad_arr,true);
            $db->query($sql,$this->_logistic_car_arr);
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
