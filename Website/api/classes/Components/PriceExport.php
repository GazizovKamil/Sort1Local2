<?php

namespace Sort1API\Components;

class PriceExport
{
    private $_pe_arr=array();

    private function create_new_pe(){
	$db= DB::getInstance();
	$res=$db->getAll("describe price_exports");
	foreach($res as $key=>$val){
	    if ($val['Field']=="create_date") $this->_pe_arr[$val['Field']]=date("Y-m-d H:i:s");
	    else {
            if (preg_match("/int/",$val['Type']) || preg_match("/float/",$val['Type'])) {
                if(!empty($val['Default'])) $this->_pe_arr[$val['Field']]=$val['Default']; 
                else $this->_pe_arr[$val['Field']]=0;
            }
            else {
                if(!empty($val['Default'])) $this->_pe_arr[$val['Field']]=$val['Default'];
                else $this->_pe_arr[$val['Field']]="";
            }
	    }
        if($val['Field']=="selected_cols") $this->_pe_arr[$val['Field']]='';
	}
    }

    function __construct($pe_id = 0){
        if ($pe_id>0)
    	    $this->Load($pe_id);
        else {
            $this->create_new_pe();
        }
        $this->selected_cols='';
    }

    public function Load($pe_id)
    {
        $db = DB::getInstance();
        if ($pe_id>0) {
            $pe_data=$db->getRow("select * from price_exports where id=?i",$pe_id);
            if (count((array)$pe_data)>0){
                foreach($pe_data as $key=>$val){
                    $this->_pe_arr[$key]=$val;
                    if($key=="selected_cols" && $val===null) $this->_pe_arr[$key]='';
                }
            }
	    else {
		    $this->create_new_pe();
	    }
        }
    }

	public function __get($name) {
		if (isset($this->_pe_arr[$name])) {
			return $this->_pe_arr[$name];
		} else {
			return null;
		}
	}

	public function __isset($name){
	    return (isset($this->_pe_arr[$name]));
	}

	public function __set($name,$val) {
		if (isset($this->_pe_arr[$name])) {
			$this->_pe_arr[$name]=$val;
		}
	}

    public function Save(){
        $db = DB::getInstance();
        if ($this->id>0) {
            $sql="update price_exports set ?u where id=?i";
            $db->query($sql,$this->_pe_arr,$this->id);
            //echo "db->query($sql,".print_r($this->_pe_arr,true).",".$this->id.");";
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
            $sql="insert ignore into price_exports set ?u";
            $db->query($sql,$this->_pe_arr);
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
