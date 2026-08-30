<?php

namespace Sort1API\Components;

class Marketplace
{
    private $_marketplace_arr=array();

    private function create_new_marketplace_config($marketplace_id){
    	$db= DB::getInstance();
    	$res=$db->getAll("describe marketplaces_configs");
    	foreach($res as $key=>$val){
    	    if ($val['Field']=="create_date") $this->_marketplace_arr[$val['Field']]=date("Y-m-d H:i:s");
    	    else {
        		if (preg_match("/int/",$val['Type']) || preg_match("/float/",$val['Type'])) {
        		    if(!empty($val['Default'])) $this->_marketplace_arr[$val['Field']]=$val['Default'];
        		    else $this->_marketplace_arr[$val['Field']]=0;
        		}
        		else {
        		    if(!empty($val['Default'])) $this->_marketplace_arr[$val['Field']]=$val['Default'];
        		    else $this->_marketplace_arr[$val['Field']]="";
        		}
    	    }
        }
        $this->_marketplace_arr['marketplace_id']=$marketplace_id;
        $this->_marketplace_arr['company_id']=$_SESSION['main_company'];
        $this->_marketplace_arr['user_id']=$_SESSION['user_id'];
    }

    function __construct($marketplace_id,$marketplace_configs_id){
        if ($marketplace_configs_id>0)
    	    $this->Load($marketplace_configs_id);
      	else
      	  $this->create_new_marketplace_config($marketplace_id);
    }

    public function Load($marketplace_configs_id)
    {
        $db = DB::getInstance();
        if ($marketplace_configs_id>0) {
            $marketplace_data=$db->getRow("select * from marketplaces_configs where id=?i",$marketplace_configs_id);
            // print_r($marketplace_data);
            //echo "select username,roles,create_date,company_id,name,lastname,email,phone,mphone,avatar from users where id=$user_id";
            if (count((array)$marketplace_data)>0){
          		foreach($marketplace_data as $key=>$val){
          		    if(!empty($val) || $val == 0) $this->_marketplace_arr[$key]=$val;
          		    else $this->_marketplace_arr[$key]="";
          		}
            }
        }
    }

	public function __get($name) {
		if (isset($this->_marketplace_arr[$name])) {
			return $this->_marketplace_arr[$name];
		} else {
			return null; 
		}
	}

	public function __isset($name){
	    return isset($this->_marketplace_arr[$name]);
	}

	public function __set($name,$val) {
		if (isset($this->_marketplace_arr[$name])) {
			$this->_marketplace_arr[$name]=$val;
		}
	}

    public function Save(){
        $db = DB::getInstance();
        //unset($this->_acquiring_arr['kassa_config']);
        if ($this->id>0) {
            $sql="update marketplaces_configs set ?u where id=?i";
            $db->query($sql,$this->_marketplace_arr,$this->id);
            //echo "db->query($sql,".print_r($this->_sklad_arr,true).",".$this->id.");";
            if ($db->affectedRows()>0) { return 1;}
	          else { return 10; }
        }
        else {
            $this->create_date=date("Y-m-d H:i:s");
            $save_data['create_date']=$this->create_date;
            $sql="insert ignore into marketplaces_configs set ?u";
	          //echo $sql." ".print_r($this->_sklad_arr,true);
            $db->query($sql,$this->_marketplace_arr);
            if ($db->affectedRows()>0) {
          		$this->id=$db->insertId();
          		return 1;
      	    }
      	    else return 0;
        }
        //echo "db->query($sql,".print_r($save_data,true).",".$this->user_id.");\n";
        return $db->error;
	}
	
	public function Delete(){
        $db = DB::getInstance();
        if ($this->id>0) {
			$sql="delete from marketplaces_configs where id=?i";
            $db->query($sql,$this->id);
            //echo "db->query($sql,".print_r($this->_sklad_arr,true).",".$this->id.");";
            if ($db->affectedRows()>0) { 
                return 1;
            }
	        else { 
                return 10; 
            }
		}
	}
}
?>
