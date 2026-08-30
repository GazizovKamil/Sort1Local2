<?php

namespace Sort1API\Components;

class FindToVinConfig
{
    private $_ftv_arr=array();

    private function create_new_ftv_config($find_to_vin_id){
    	$db= DB::getInstance();
    	$res=$db->getAll("describe find_to_vin_config");
    	foreach($res as $key=>$val){
    	    if ($val['Field']=="create_date") $this->_ftv_arr[$val['Field']]=date("Y-m-d H:i:s");
    	    else {
        		if (preg_match("/int/",$val['Type']) || preg_match("/float/",$val['Type'])) {
        		    if(!empty($val['Default'])) $this->_ftv_arr[$val['Field']]=$val['Default'];
        		    else $this->_ftv_arr[$val['Field']]=0;
        		}
        		else {
        		    if(!empty($val['Default'])) $this->_ftv_arr[$val['Field']]=$val['Default'];
        		    else $this->_ftv_arr[$val['Field']]="";
        		}
    	    }
        }
        $this->_ftv_arr['find_to_vin_id']=$find_to_vin_id;
        $this->_ftv_arr['main_company_id']=$_SESSION['main_company'];
        $this->_ftv_arr['user_id']=$_SESSION['user_id'];
    }

    function __construct($find_to_vin_id,$ftv_id){
        if ($ftv_id>0)
    	    $this->Load($ftv_id);
      	else
      	  $this->create_new_ftv_config($find_to_vin_id);
    }

    public function Load($ftv_id){
        $db = DB::getInstance();
        if ($ftv_id>0) {
            $data=$db->getRow("select * from find_to_vin_config where id=?i",$ftv_id);
            if (count((array)$data)>0){
          		foreach($data as $key=>$val){
          		    if(!empty($val) || $val == 0) $this->_ftv_arr[$key]=$val;
          		    else $this->_ftv_arr[$key]="";
          		}
            }
        }
    }

    public function __get($name) {
		if (isset($this->_ftv_arr[$name])) {
			return $this->_ftv_arr[$name];
		} else {
			return null; 
		}
	}

	public function __isset($name){
	    return isset($this->_ftv_arr[$name]);
	}

	public function __set($name,$val) {
		if (isset($this->_ftv_arr[$name])) {
			$this->_ftv_arr[$name]=$val;
		}
	}

    public function Save(){
        $db = DB::getInstance();
        //unset($this->_acquiring_arr['kassa_config']);
        if ($this->id>0) {
            $sql="update find_to_vin_config set ?u where id=?i";
            $db->query($sql,$this->_ftv_arr,$this->id);
            //echo "db->query($sql,".print_r($this->_sklad_arr,true).",".$this->id.");";
            if ($db->affectedRows()>0) { return 1;}
	          else { return 10; }
        }
        else {
            $this->create_date=date("Y-m-d H:i:s");
            $save_data['create_date']=$this->create_date;
            $sql="insert ignore into find_to_vin_config set ?u";
	          //echo $sql." ".print_r($this->_sklad_arr,true);
            $db->query($sql,$this->_ftv_arr);
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