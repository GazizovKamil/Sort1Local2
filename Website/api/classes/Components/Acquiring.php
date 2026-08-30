<?php

namespace Sort1API\Components;

class Acquiring
{
    private $_acquiring_arr=array();

    private function create_new_acquiring($acquiring_operator_id){
    	$db= DB::getInstance();
    	$res=$db->getAll("describe acquiring_config");
    	foreach($res as $key=>$val){
    	    if ($val['Field']=="create_date") $this->_acquiring_arr[$val['Field']]=date("Y-m-d H:i:s");
    	    else {
        		if (preg_match("/int/",$val['Type']) || preg_match("/float/",$val['Type'])) {
        		    if(!empty($val['Default'])) $this->_acquiring_arr[$val['Field']]=$val['Default'];
        		    else $this->_acquiring_arr[$val['Field']]=0;
        		}
        		else {
        		    if(!empty($val['Default'])) $this->_acquiring_arr[$val['Field']]=$val['Default'];
        		    else $this->_acquiring_arr[$val['Field']]="";
        		}
    	    }
        }
        $this->_acquiring_arr['acquiring_operator_id']=$acquiring_operator_id;
        $this->_acquiring_arr['company_id']=$_SESSION['main_company'];
        // $this->_acquiring_arr['user_id']=$_SESSION['user_id'];
        $this->_acquiring_arr['acquiring_config']=$db->getOne("select acquiring_operator_config from acquiring_operators where id=?i",$acquiring_operator_id);
     // $this->_ofd_arr['main_company_id']=$_SESSION['main_company'];
    }

    function __construct($acquiring_operator_id,$acquiring_id){
        if ($acquiring_id>0)
    	    $this->Load($acquiring_id);
      	else
      	  $this->create_new_acquiring($acquiring_operator_id);
    }

    public function Load($acquiring_id)
    {
        $db = DB::getInstance();
        if ($acquiring_id>0) {
            $acquiring_data=$db->getRow("select * from acquiring_config where id=?i",$acquiring_id);
            //print_r($user_data);
            //echo "select username,roles,create_date,company_id,name,lastname,email,phone,mphone,avatar from users where id=$user_id";
            if (count((array)$acquiring_data)>0){
          		foreach($acquiring_data as $key=>$val){
          		    if(!empty($val) || $val == 0) $this->_acquiring_arr[$key]=$val;
          		    else $this->_acquiring_arr[$key]="";
          		}
            }
            $this->_acquiring_arr['acquiring_config']=$db->getOne("select acquiring_operator_config from acquiring_operators where id=?i",$acquiring_data['acquiring_operator_id']);
        }
    }

	public function __get($name) {
		if (isset($this->_acquiring_arr[$name])) {
			return $this->_acquiring_arr[$name];
		} else {
			return null; 
		}
	}

	public function __isset($name){
	    return isset($this->_acquiring_arr[$name]);
	}

	public function __set($name,$val) {
		if (isset($this->_acquiring_arr[$name])) {
			$this->_acquiring_arr[$name]=$val;
		}
	}

    public function Save(){
        $db = DB::getInstance();
        //unset($this->_acquiring_arr['kassa_config']);
        if ($this->id>0) {
            $sql="update acquiring_config set ?u where id=?i";
            $db->query($sql,$this->_acquiring_arr,$this->id);
            //echo "db->query($sql,".print_r($this->_sklad_arr,true).",".$this->id.");";
            if ($db->affectedRows()>0) { return 1;}
	          else { return 10; }
        }
        else {
            $this->create_date=date("Y-m-d H:i:s");
            $save_data['create_date']=$this->create_date;
            $sql="insert ignore into acquiring_config set ?u";
	          //echo $sql." ".print_r($this->_sklad_arr,true);
            $db->query($sql,$this->_acquiring_arr);
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
            //$sql="delete from gtd where id=?i";
			//$db->query($sql,$this->id);
			$sql="delete from acquiring_config where id=?i";
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
