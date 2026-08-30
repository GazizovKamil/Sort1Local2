<?php

namespace Sort1API\Components;

class PriceTypeDifferencialValue
{
    private $_ptdv_arr=array();

    private function create_new_ptdv(){
	$db= DB::getInstance();
	$res=$db->getAll("describe dict_price_type_differencial_values");
	foreach($res as $key=>$val){
	    if ($val['Field']=="create_date") $this->_ptdv_arr[$val['Field']]=date("Y-m-d H:i:s");
	    else {
		if (preg_match("/int/",$val['Type']) || preg_match("/float/",$val['Type'])) {
		    if(!empty($val['Default'])) $this->_ptdv_arr[$val['Field']]=$val['Default'];
		    else $this->_ptdv_arr[$val['Field']]=0;
		}
		else {
		    if(!empty($val['Default'])) $this->_ptdv_arr[$val['Field']]=$val['Default'];
		    else $this->_ptdv_arr[$val['Field']]="";
		}
	    }
	}
    }

    function __construct($ptdv_id = 0){
        if ($ptdv_id>0)
    	    $this->Load($ptdv_id);
	else {
	    $this->create_new_ptdv();
	}
    }

    public function Load($ptdv_id)
    {
        $db = DB::getInstance();
        if ($ptdv_id>0) {
            $ptdv_data=$db->getRow("select * from dict_price_type_differencial_values where id=?i",$ptdv_id);
            if (count((array)$ptdv_data)>0){
		foreach($ptdv_data as $key=>$val){
		    $this->_ptdv_arr[$key]=$val;
		}
            }
	    else {
		$this->create_new_ptdv();
	    }
        }
    }

	public function __get($name) {
		if (isset($this->_ptdv_arr[$name])) {
			return $this->_ptdv_arr[$name];
		} else {
			return null;
		}
	}

	public function __isset($name){
	    return (isset($this->_ptdv_arr[$name]));
	}

	public function __set($name,$val) {
		if (isset($this->_ptdv_arr[$name])) {
			$this->_ptdv_arr[$name]=$val;
		}
	}

    public function Save(){
        $db = DB::getInstance();
        if ($this->id>0) {
            $sql="update dict_price_type_differencial_values set ?u where id=?i and dict_price_type_id in (select id from dict_price_type where main_company=?i)";
            $db->query($sql,$this->_ptdv_arr,$this->id,$_SESSION['main_company']);
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
            $sql="insert ignore into dict_price_type_differencial_values set ?u";
            $db->query($sql,$this->_ptdv_arr);
            if ($db->affectedRows()>0) {
		$this->id=$db->insertId();
		return 1;
	    }
	    else return 10;
        }
        //echo "db->query($sql,".print_r($save_data,true).",".$this->user_id.");\n";
        return $db->error;
    }

    public function Delete(){
	$db = DB::getInstance();
	$sql="delete from dict_price_type_differencial_values where id=?i and dict_price_type_id in (select id from dict_price_type where main_company=?i)";
	$res=$db->query($sql,$this->id,$_SESSION['main_company']);
	if($db->affectedRows()>0) return 1;
	else return 0;
    }

}
?>
