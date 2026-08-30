<?php

namespace Sort1API\Components;

class DeliveryAddress
{
    private $_da_arr=array();

    private function create_new_da(){
	$db= DB::getInstance();
	$res=$db->getAll("describe delivery_address");
	foreach($res as $key=>$val){
	    if ($val['Field']=="create_date") $this->_tt_arr[$val['Field']]=date("Y-m-d H:i:s");
	    else {
		if (preg_match("/int/",$val['Type']) || preg_match("/float/",$val['Type'])) {
		    if(!empty($val['Default'])) $this->_da_arr[$val['Field']]=$val['Default'];
		    else $this->_da_arr[$val['Field']]=0;
		}
		else {
		    if(!empty($val['Default'])) $this->_da_arr[$val['Field']]=$val['Default'];
		    else $this->_da_arr[$val['Field']]="";
		}
	    }
	}
    }

    function __construct($da_id = 0){
        if ($da_id>0)
    	    $this->Load($da_id);
	else {
	    $this->create_new_da();
	}
    }

    public function Load($da_id)
    {
        $db = DB::getInstance();
        if ($da_id>0) {
            $da_data=$db->getRow("select * from delivery_address where id=?i",$da_id);
            if (count((array)$da_data)>0){
		foreach($da_data as $key=>$val){
		    $this->_da_arr[$key]=$val;
		}
            }
	    else {
		$this->create_new_da();
	    }
        }
    }

	public function __get($name) {
		if (isset($this->_da_arr[$name])) {
			return $this->_da_arr[$name];
		} else {
			return null;
		}
	}

	public function __isset($name){
	    return (isset($this->_da_arr[$name]));
	}

	public function __set($name,$val) {
		if (isset($this->_da_arr[$name])) {
			$this->_da_arr[$name]=$val;
		}
	}

    public function Save(){
        $db = DB::getInstance();
        if ($this->id>0) {
            $sql="update delivery_address set ?u where id=?i";
            $db->query($sql,$this->_da_arr,$this->id);
            //echo "db->query($sql,".print_r($this->_comp_arr,true).",".$this->id."); comp_rekvizits=".print_r($this->_comp_rekvizits,true);
            if ($db->affectedRows()>0) { 
		return 1;
	    }
	    else { 
		return 10;
	    }
        }
        else {
            $this->create_date=date("Y-m-d H:i:s");
            $save_data['create_date']=$this->create_date;
            $sql="insert ignore into delivery_address set ?u";
            $db->query($sql,$this->_da_arr);
            if ($db->affectedRows()>0) {
		$this->id=$db->insertId();
		return 1;
	    }
	    else return 10;
        }
        //echo "db->query($sql,".print_r($save_data,true).",".$this->user_id.");\n";
        return $db->error;
    }
}
?>
