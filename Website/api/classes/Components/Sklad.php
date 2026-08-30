<?php

namespace Sort1API\Components;

class Sklad
{
    private $_sklad_arr=array();

    private function create_new_sklad(){
    	$db= DB::getInstance();
    	$res=$db->getAll("describe sklad");
    	foreach($res as $key=>$val){
    	    if ($val['Field']=="create_date") $this->_sklad_arr[$val['Field']]=date("Y-m-d H:i:s");
    	    else {
        		if (preg_match("/int/",$val['Type']) || preg_match("/float/",$val['Type'])) {
        		    if(!empty($val['Default'])) $this->_sklad_arr[$val['Field']]=$val['Default'];
        		    else $this->_sklad_arr[$val['Field']]=0;
        		}
        		else {
        		    if(!empty($val['Default'])) $this->_sklad_arr[$val['Field']]=$val['Default'];
        		    else $this->_sklad_arr[$val['Field']]="";
        		}
    	    }
    	}
    }

    function __construct($sklad_id = 0){
        if ($sklad_id>0)
    	    $this->Load($sklad_id);
      	else
      	  $this->create_new_sklad();
    }

    public function Load($sklad_id)
    {
        $db = DB::getInstance();
        if ($sklad_id>0) {
            $sklad_data=$db->getRow("select * from sklad where id=?i",$sklad_id);
            //print_r($user_data);
            //echo "select username,roles,create_date,company_id,name,lastname,email,phone,mphone,avatar from users where id=$user_id";
            if (count((array)$sklad_data)>0){
          		foreach($sklad_data as $key=>$val){
          		    if(!empty($val) || $val == 0) $this->_sklad_arr[$key]=$val;
          		    else $this->_sklad_arr[$key]="";
          		}
            }
        }
    }

	public function __get($name) {
		if (isset($this->_sklad_arr[$name])) {
			return $this->_sklad_arr[$name];
		} else {
			return null;
		}
	}

	public function __isset($name){
	    return isset($this->_sklad_arr[$name]);
	}

	public function __set($name,$val) {
		if (isset($this->_sklad_arr[$name])) {
			$this->_sklad_arr[$name]=$val;
		}
	}

    public function Save(){
        $db = DB::getInstance();
        if ($this->id>0) {
            $sql="update sklad set ?u where id=?i";
            $db->query($sql,$this->_sklad_arr,$this->id);
            //echo "db->query($sql,".print_r($this->_sklad_arr,true).",".$this->id.");";
            if ($db->affectedRows()>0) { return 1;}
	    else { return 10; }
        }
        else {
            $this->create_date=date("Y-m-d H:i:s");
            $save_data['create_date']=$this->create_date;
            $sql="insert ignore into sklad set ?u";
	    //echo $sql." ".print_r($this->_sklad_arr,true);
            $db->query($sql,$this->_sklad_arr);
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
