<?php

namespace Sort1API\Components;

class LogisticsCompanyConfig
{
    private $_logistics_arr=array();

    private function create_new_logistic_config($logistics_id){
    	$db= DB::getInstance();
    	$res=$db->getAll("describe logistics_company_configs");
    	foreach($res as $key=>$val){
    	    if ($val['Field']=="create_date") $this->_logistics_arr[$val['Field']]=date("Y-m-d H:i:s");
    	    else {
        		if (preg_match("/int/",$val['Type']) || preg_match("/float/",$val['Type'])) {
        		    if(!empty($val['Default'])) $this->_logistics_arr[$val['Field']]=$val['Default'];
        		    else $this->_logistics_arr[$val['Field']]=0;
        		}
        		else {
        		    if(!empty($val['Default'])) $this->_logistics_arr[$val['Field']]=$val['Default'];
        		    else $this->_logistics_arr[$val['Field']]="";
        		}
    	    }
        }
        $this->_logistics_arr['logistics_id']=$logistics_id;
        $this->_logistics_arr['main_company_id']=$_SESSION['main_company'];
        $this->_logistics_arr['user_id']=$_SESSION['user_id'];
    }

    function __construct($logistics_id,$logistics_configs_id){
        if ($logistics_configs_id>0)
    	    $this->Load($logistics_configs_id);
      	else
      	  $this->create_new_logistic_config($logistics_id);
    }

    public function Load($logistics_configs_id){
        $db = DB::getInstance();
        if ($logistics_configs_id>0) {
            $logistics_data=$db->getRow("select * from logistics_company_configs where id=?i",$logistics_configs_id);
            // print_r($marketplace_data);
            //echo "select username,roles,create_date,company_id,name,lastname,email,phone,mphone,avatar from users where id=$user_id";
            if (count((array)$logistics_data)>0){
          		foreach($logistics_data as $key=>$val){
          		    if(!empty($val) || $val == 0) $this->_logistics_arr[$key]=$val;
          		    else $this->_logistics_arr[$key]="";
          		}
            }
        }
    }

    public function __get($name) {
		if (isset($this->_logistics_arr[$name])) {
			return $this->_logistics_arr[$name];
		} else {
			return null; 
		}
	}

	public function __isset($name){
	    return isset($this->_logistics_arr[$name]);
	}

	public function __set($name,$val) {
		if (isset($this->_logistics_arr[$name])) {
			$this->_logistics_arr[$name]=$val;
		}
	}

    public function Save(){
        $db = DB::getInstance();
        //unset($this->_acquiring_arr['kassa_config']);
        if ($this->id>0) {
            $sql="update logistics_company_configs set ?u where id=?i";
            $db->query($sql,$this->_logistics_arr,$this->id);
            //echo "db->query($sql,".print_r($this->_sklad_arr,true).",".$this->id.");";
            if ($db->affectedRows()>0) { return 1;}
	          else { return 10; }
        }
        else {
            $this->create_date=date("Y-m-d H:i:s");
            $save_data['create_date']=$this->create_date;
            $sql="insert ignore into logistics_company_configs set ?u";
	          //echo $sql." ".print_r($this->_sklad_arr,true);
            $db->query($sql,$this->_logistics_arr);
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