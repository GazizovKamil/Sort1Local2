<?php

namespace Sort1API\Components;

class EmailConfig
{
    private $_email_config_arr=array();

    private function create_new_email_config(){
    	$db= DB::getInstance();
    	$res=$db->getAll("describe email_configs");
    	foreach($res as $key=>$val){
    	    if ($val['Field']=="create_date") $this->_email_config_arr[$val['Field']]=date("Y-m-d H:i:s");
    	    else {
        		if (preg_match("/int/",$val['Type']) || preg_match("/float/",$val['Type'])) {
        		    if(!empty($val['Default'])) $this->_email_config_arr[$val['Field']]=$val['Default'];
        		    else $this->_email_config_arr[$val['Field']]=0;
        		}
        		else {
        		    if(!empty($val['Default'])) $this->_email_config_arr[$val['Field']]=$val['Default'];
        		    else $this->_email_config_arr[$val['Field']]="";
        		}
    	    }
    	}
        $this->_email_config_arr['user_id']=$_SESSION['user_id'];
        $this->_email_config_arr['main_company_id']=$_SESSION['main_company'];
    }

    function __construct($email_config_id = 0){
        if ($email_config_id>0)
    	    $this->Load($email_config_id);
      	else
      	  $this->create_new_email_config();
    }

    public function Load($email_config_id)
    {
        $db = DB::getInstance();
        if ($email_config_id>0) {
            $email_config_data=$db->getRow("select * from email_configs where id=?i",$email_config_id);
            //print_r($user_data);
            //echo "select username,roles,create_date,company_id,name,lastname,email,phone,mphone,avatar from users where id=$user_id";
            if (count((array)$email_config_data)>0){
          		foreach($email_config_data as $key=>$val){
          		    if(!empty($val) || $val == 0) $this->_email_config_arr[$key]=$val;
          		    else $this->_email_config_arr[$key]="";
          		}
            }
        }
    }

	public function __get($name) {
		if (isset($this->_email_config_arr[$name])) {
			return $this->_email_config_arr[$name];
		} else {
			return null;
		}
	}

	public function __isset($name){
	    return isset($this->_email_config_arr[$name]);
	}

	public function __set($name,$val) {
		if (isset($this->_email_config_arr[$name])) {
			$this->_email_config_arr[$name]=$val;
		}
	}

    public function Save(){
        $db = DB::getInstance();
        if ($this->id>0) {
	    $this->update_date=date("Y-m-d H:i:s");
	    $this->update_by=$_SESSION['user_id'];
            $sql="update email_configs set ?u where id=?i";
            $db->query($sql,$this->_email_config_arr,$this->id);
            //echo "db->query($sql,".print_r($this->_email_config_arr,true).",".$this->id.");";
            if ($db->affectedRows()>0) { return 1;}
	    else { return 10; }
        }
        else {
            $this->create_date=date("Y-m-d H:i:s");
            //$save_data['create_date']=$this->create_date;
            $sql="insert ignore into email_configs set ?u";
	    //echo $sql." ".print_r($this->_email_config_arr,true);
            $db->query($sql,$this->_email_config_arr);
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
