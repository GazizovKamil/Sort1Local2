<?php

namespace Sort1API\Components;

class Service
{
    private $_service_arr=array();

    private function create_new_service(){
    	$db= DB::getInstance();
    	$res=$db->getAll("describe services");
    	foreach($res as $key=>$val){
    	    if ($val['Field']=="create_date") $this->_service_arr[$val['Field']]=date("Y-m-d H:i:s");
    	    else {
        		if (preg_match("/int/",$val['Type']) || preg_match("/float/",$val['Type'])) {
        		    if(!empty($val['Default'])) $this->_service_arr[$val['Field']]=$val['Default'];
        		    else $this->_service_arr[$val['Field']]=0;
        		}
        		else {
        		    if(!empty($val['Default'])) $this->_service_arr[$val['Field']]=$val['Default'];
        		    else $this->_service_arr[$val['Field']]="";
        		}
    	    }
    	}
		$this->main_company_id=$_SESSION['main_company'];
    }

    function __construct($service_id = 0){
        if ($service_id>0)
    	    $this->Load($service_id);
      	else
      	  $this->create_new_service();
    }

    public function Load($service_id)
    {
        $db = DB::getInstance();
        if ($service_id>0) {
            $service_data=$db->getRow("select * from services where id=?i",$service_id);
            //print_r($user_data);
            //echo "select username,roles,create_date,company_id,name,lastname,email,phone,mphone,avatar from users where id=$user_id";
            if (count((array)$service_data)>0){
          		foreach($service_data as $key=>$val){
          		    if(!empty($val) || $val == 0) $this->_service_arr[$key]=$val;
          		    else $this->_service_arr[$key]="";
          		}
            }
        }
    }

	public function __get($name) {
		if (isset($this->_service_arr[$name])) {
			return $this->_service_arr[$name];
		} else {
			return null;
		}
	}

	public function __isset($name){
	    return isset($this->_service_arr[$name]);
	}

	public function __set($name,$val) {
		if (isset($this->_service_arr[$name])) {
			$this->_service_arr[$name]=$val;
		}
	}

    public function Save(){
        $db = DB::getInstance();
        if ($this->id>0) {
            $this->update_date=date("Y-m-d H:i:s");
            $sql="update services set ?u where id=?i";
            $db->query($sql,$this->_service_arr,$this->id);
            //echo "db->query($sql,".print_r($this->_service_arr,true).",".$this->id.");";
            if ($db->affectedRows()>0) { return 1;}
	    else { return 10; }
        }
        else {
            $this->create_date=date("Y-m-d H:i:s");
            $save_data['create_date']=$this->create_date;
            $sql="insert ignore into services set ?u";
	    //echo $sql." ".print_r($this->_service_arr,true);
            $db->query($sql,$this->_service_arr);
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
