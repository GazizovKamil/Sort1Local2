<?php

namespace Sort1API\Components;

class TaxType
{
    private $_tt_arr=array();

    private function create_new_tt(){
	$db= DB::getInstance();
	$res=$db->getAll("describe tax_type");
	foreach($res as $key=>$val){
	    if ($val['Field']=="create_date") $this->_tt_arr[$val['Field']]=date("Y-m-d H:i:s");
	    else {
		if (preg_match("/int/",$val['Type']) || preg_match("/float/",$val['Type'])) {
		    if(!empty($val['Default'])) $this->_tt_arr[$val['Field']]=$val['Default']; 
		    else $this->_tt_arr[$val['Field']]=0;
		}
		else {
		    if(!empty($val['Default'])) $this->_tt_arr[$val['Field']]=$val['Default'];
		    else $this->_tt_arr[$val['Field']]="";
		}
	    }
	}
    }

    function __construct($tt_id = 0){
        if ($tt_id>0)
    	    $this->Load($tt_id);
	else {
	    $this->create_new_tt();
	}
    }

    public function Load($tt_id)
    {
        $db = DB::getInstance();
        if ($tt_id>0) {
            $tt_data=$db->getRow("select * from tax_type where id=?i",$tt_id);
            if (count((array)$tt_data)>0){
		foreach($tt_data as $key=>$val){
		    $this->_tt_arr[$key]=$val;
		}
            }
	    else {
		$this->create_new_tt();
	    }
        }
    }

	public function __get($name) {
		if (isset($this->_tt_arr[$name])) {
			return $this->_tt_arr[$name];
		} else {
			return null;
		}
	}

	public function __isset($name){
	    return (isset($this->_tt_arr[$name]));
	}

	public function __set($name,$val) {
		if (isset($this->_tt_arr[$name])) {
			$this->_tt_arr[$name]=$val;
		}
	}

    public function Save(){
        $db = DB::getInstance();
        if ($this->id>0) {
            $sql="update tax_type set ?u where id=?i";
            $db->query($sql,$this->_tt_arr,$this->id);
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
            $sql="insert ignore into tax_type set ?u";
            $db->query($sql,$this->_tt_arr);
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
