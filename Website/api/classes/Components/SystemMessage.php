<?php

namespace Sort1API\Components;

class SystemMessage
{
    private $_system_message_arr=array();

    private function create_new_system_message(){
    	$db= DB::getInstance();
    	$res=$db->getAll("describe system_message");
    	foreach($res as $key=>$val){
    	    if ($val['Field']=="create_date") $this->_system_message_arr[$val['Field']]=date("Y-m-d H:i:s");
    	    else {
        		if (preg_match("/int/",$val['Type']) || preg_match("/float/",$val['Type'])) {
        		    if(!empty($val['Default'])) $this->_system_message_arr[$val['Field']]=$val['Default'];
        		    else $this->_system_message_arr[$val['Field']]=0;
        		}
        		else {
        		    if(!empty($val['Default'])) $this->_system_message_arr[$val['Field']]=$val['Default'];
        		    else $this->_system_message_arr[$val['Field']]="";
        		}
    	    }
    	}
    }

    function __construct($system_message_id = 0){
        if ($system_message_id>0)
    	    $this->Load($system_message_id);
      	else
      	  $this->create_new_system_message();
    }

    public function Load($system_message_id)
    {
        $db = DB::getInstance();
        if ($system_message_id>0) {
            $system_message_data=$db->getRow("select * from system_message where id=?i",$system_message_id);
            //print_r($user_data);
            //echo "select username,roles,create_date,company_id,name,lastname,email,phone,mphone,avatar from users where id=$user_id";
            if (count((array)$system_message_data)>0){
          		foreach($system_message_data as $key=>$val){
          		    if(!empty($val) || $val == 0) $this->_system_message_arr[$key]=$val;
          		    else $this->_system_message_arr[$key]="";
          		}
            }
        }
    }

	public function __get($name) {
		if (isset($this->_system_message_arr[$name])) {
			return $this->_system_message_arr[$name];
		} else {
			return null;
		}
	}

	public function __isset($name){
	    return isset($this->_system_message_arr[$name]);
	}

	public function __set($name,$val) {
		if (isset($this->_system_message_arr[$name])) {
			$this->_system_message_arr[$name]=$val;
		}
	}

    public function Save(){
        $db = DB::getInstance();
        if ($this->id>0) {
            $sql="update system_message set ?u where id=?i";
            $db->query($sql,$this->_system_message_arr,$this->id);
            //echo "db->query($sql,".print_r($this->_system_message_arr,true).",".$this->id.");";
            if ($db->affectedRows()>0) { return 1;}
	    else { return 10; }
        }
        else {
            $this->create_date=date("Y-m-d H:i:s");
            $save_data['create_date']=$this->create_date;
            $sql="insert ignore into system_message set ?u";
	    //echo $sql." ".print_r($this->_system_message_arr,true);
            $db->query($sql,$this->_system_message_arr);
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
