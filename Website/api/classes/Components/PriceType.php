<?php

namespace Sort1API\Components;

class PriceType
{
    private $_pt_arr=array();

    private function create_new_pt(){
	$db= DB::getInstance();
	$res=$db->getAll("describe dict_price_type");
	foreach($res as $key=>$val){
	    if ($val['Field']=="create_date") $this->_pt_arr[$val['Field']]=date("Y-m-d H:i:s");
	    else {
		if (preg_match("/int/",$val['Type']) || preg_match("/float/",$val['Type'])) {
		    if(!empty($val['Default'])) $this->_pt_arr[$val['Field']]=$val['Default'];
		    else $this->_pt_arr[$val['Field']]=0;
		}
		else {
		    if(!empty($val['Default'])) $this->_pt_arr[$val['Field']]=$val['Default'];
		    else $this->_pt_arr[$val['Field']]="";
		}
	    }
	}
    }

    function __construct($pt_id = 0){
        if ($pt_id>0)
    	    $this->Load($pt_id);
	else {
	    $this->create_new_pt();
	}
    }

    public function Load($pt_id)
    {
        $db = DB::getInstance();
        if ($pt_id>0) {
            $pt_data=$db->getRow("select * from dict_price_type where id=?i",$pt_id);
            if (count((array)$pt_data)>0){
		foreach($pt_data as $key=>$val){
		    $this->_pt_arr[$key]=$val;
		}
            }
	    else {
		$this->create_new_pt();
	    }
        }
    }

	public function __get($name) {
		if (isset($this->_pt_arr[$name])) {
			return $this->_pt_arr[$name];
		} else {
			return null;
		}
	}

	public function __isset($name){
	    return (isset($this->_pt_arr[$name]));
	}

	public function __set($name,$val) {
		if (isset($this->_pt_arr[$name])) {
			$this->_pt_arr[$name]=$val;
		}
	}

    public function Save(){
        $db = DB::getInstance();
        if ($this->id>0) {
            $sql="update dict_price_type set ?u where id=?i and main_company in (select company_id from user_companys where main_company_id=0 and user_id=?i)";
            $db->query($sql,$this->_pt_arr,$this->id,$_SESSION['user_id']);
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
            $sql="insert ignore into dict_price_type set ?u";
            $db->query($sql,$this->_pt_arr);
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
