<?php

namespace Sort1API\Components;

class City
{
    private $_city_arr=array();

    private function create_new_city(){
		$db= DB::getInstance();
		$res=$db->getAll("describe city");
		foreach($res as $key=>$val){
			if (preg_match("/int/",$val['Type']) || preg_match("/float/",$val['Type'])) {
				if(!empty($val['Default'])) $this->_city_arr[$val['Field']]=$val['Default'];
				else $this->_city_arr[$val['Field']]=0;
			}
			else {
				if(!empty($val['Default'])) $this->_city_arr[$val['Field']]=$val['Default'];
				else $this->_city_arr[$val['Field']]="";
			}
		}
		// $this->Save();
    }

    function __construct($city_id = ""){
        if ($city_id!=""){
    	    $this->Load($city_id);
		}
		else {
			$this->create_new_city();
		}
    }

    public function Load($city_id)
    {
        $db = DB::getInstance();
        if ($city_id!="") {
            $city_data=$db->getRow("select * from city where city_fias_id=?s",$city_id);1;
            //echo "select username,roles,create_date,company_id,name,lastname,email,phone,mphone,avatar from users where id=$user_id";
            if (count((array)$city_data)>0){
				foreach($city_data as $key=>$val){
					$this->_city_arr[$key]=$val;
				}
        	}
	    	else $this->create_new_city();
        }
    }

	public function __get($name) {
		if (isset($this->_city_arr[$name])) {
			return $this->_city_arr[$name];
		} else {
			return null;			
		}
	}

	public function __isset($name){
	    return isset($this->_city_arr[$name]);
	}

	public function __set($name,$val) {
		if (isset($this->_city_arr[$name])) {
			$this->_city_arr[$name]=$val;
		}
	}

    public function Save(){
        $db = DB::getInstance();
        if ($this->id>0) {
            $sql="update city set ?u where id=?i";
            $db->query($sql,$this->_city_arr,$this->id);
            //echo "db->query($sql,".print_r($this->_sklad_arr,true).",".$this->id.");";
            if ($db->affectedRows()>0) { return 1;}
	    	else { return 10; }
        }
        else {
            $sql="insert ignore into city set ?u";
	    	// echo $sql." ".print_r($this->_city_arr,true);
            $db->query($sql,$this->_city_arr);
            if ($db->affectedRows()>0) {
				$this->id=$db->insertId();
				return 1;
			}
	    	else return 0;
        }
        return $db->error;
    }
}
?>
