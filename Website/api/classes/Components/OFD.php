<?php

namespace Sort1API\Components;

class OFD
{
    private $_ofd_kassa_arr=array();
    private $_ofd_arr=array();

    private function create_new_ofd_kassa($ofd_id){
    	$db= DB::getInstance();
    	$res=$db->getAll("describe ofd_kassa_config");
    	foreach($res as $key=>$val){
    	    if ($val['Field']=="create_date") $this->_ofd_kassa_arr[$val['Field']]=date("Y-m-d H:i:s");
    	    else {
        		if (preg_match("/int/",$val['Type']) || preg_match("/float/",$val['Type'])) {
        		    if(!empty($val['Default'])) $this->_ofd_kassa_arr[$val['Field']]=$val['Default'];
        		    else $this->_ofd_kassa_arr[$val['Field']]=0;
        		}
        		else {
        		    if(!empty($val['Default'])) $this->_ofd_kassa_arr[$val['Field']]=$val['Default'];
        		    else $this->_ofd_kassa_arr[$val['Field']]="";
        		}
    	    }
        }
        $this->_ofd_kassa_arr['ofd_operator_id']=$ofd_id;
        $this->_ofd_kassa_arr['company_id']=$_SESSION['main_company'];
        $this->_ofd_kassa_arr['kassa_config']=$db->getOne("select ofd_config from ofd_operators where id=?i",$ofd_id);
     // $this->_ofd_arr['main_company_id']=$_SESSION['main_company'];
    }

    function __construct($ofd_id,$ofd_kassa_id){
        if ($ofd_kassa_id>0)
    	    $this->Load($ofd_kassa_id);
      	else
      	  $this->create_new_ofd_kassa($ofd_id);
    }

    public function Load($ofd_kassa_id)
    {
        $db = DB::getInstance();
        if ($ofd_kassa_id>0) {
            $ofd_kassa_data=$db->getRow("select * from ofd_kassa_config where id=?i",$ofd_kassa_id);
            //print_r($user_data);
            //echo "select username,roles,create_date,company_id,name,lastname,email,phone,mphone,avatar from users where id=$user_id";
            if (count((array)$ofd_kassa_data)>0){
          		foreach($ofd_kassa_data as $key=>$val){
          		    if(!empty($val) || $val == 0) $this->_ofd_kassa_arr[$key]=$val;
          		    else $this->_ofd_kassa_arr[$key]="";
          		}
            }
            if(empty($this->_ofd_kassa_arr['kassa_config'])) $this->_ofd_kassa_arr['kassa_config']=$db->getOne("select ofd_config from ofd_operators where id=?i",$ofd_kassa_data['ofd_operator_id']);
        }
    }

	public function __get($name) {
		if (isset($this->_ofd_kassa_arr[$name])) {
			return $this->_ofd_kassa_arr[$name];
		} else {
			return null; 
		}
	}

	public function __isset($name){
	    return isset($this->_ofd_kassa_arr[$name]);
	}

	public function __set($name,$val) {
		if (isset($this->_ofd_kassa_arr[$name])) {
			$this->_ofd_kassa_arr[$name]=$val;
		}
	}

    public function Save(){
        $db = DB::getInstance();
        //unset($this->_ofd_kassa_arr['kassa_config']);
        if ($this->id>0) {
            $sql="update ofd_kassa_config set ?u where id=?i";
            $db->query($sql,$this->_ofd_kassa_arr,$this->id);
            //echo "db->query($sql,".print_r($this->_sklad_arr,true).",".$this->id.");";
            if ($db->affectedRows()>0) { return 1;}
	          else { return 10; }
        }
        else {
            $this->create_date=date("Y-m-d H:i:s");
            $save_data['create_date']=$this->create_date;
            $sql="insert ignore into ofd_kassa_config set ?u";
	          //echo $sql." ".print_r($this->_sklad_arr,true);
            $db->query($sql,$this->_ofd_kassa_arr);
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
			$sql="delete from ofd_kassa_config where id=?i";
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
