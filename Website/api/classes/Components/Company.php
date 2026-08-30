<?php

namespace Sort1API\Components;

class Company
{
    private $_comp_arr=array();
    private $_comp_rekvizits=array();

    private function create_new_comp(){
    	$db= DB::getInstance();
    	$res=$db->getAll("describe company");
    	foreach($res as $key=>$val){
    	    if ($val['Field']=="create_date") $this->_comp_arr[$val['Field']]=date("Y-m-d H:i:s");
    	    else {
    		if (preg_match("/int/",$val['Type']) || preg_match("/float/",$val['Type'])) $this->_comp_arr[$val['Field']]=0;
    		else $this->_comp_arr[$val['Field']]="";
    	    }
    	}
    }

    private function create_new_comp_rek(){
    	$db= DB::getInstance();
    	$res=$db->getAll("describe company_rekvizits");
    	foreach($res as $key=>$val){
    	    if ($val['Field']!="company_id" && $val['Field']!="user_id" && $val['Field']!="main_company")
          		if ($val['Field']=="create_date") $this->_comp_rekvizits[$val['Field']]=date("Y-m-d H:i:s");
          		else {
          		    if (preg_match("/int/",$val['Type']) || preg_match("/float/",$val['Type'])) $this->_comp_rekvizits[$val['Field']]=0;
          		    else $this->_comp_rekvizits[$val['Field']]="";
          		}
    	}
    }

    function __construct($company_id = 0){
        if ($company_id>0)
    	    $this->Load($company_id);
      	else {
      	    $this->create_new_comp();
      	    $this->create_new_comp_rek();
      	}
    }

    public function Load($company_id)
    {
        $db = DB::getInstance();
        if ($company_id>0) {
            $company_data=$db->getRow("select * from company where id=?i",$company_id);
	          $comp_rek_data=$db->getRow("select ks,rs,bank,bik from company_rekvizits where company_id=?i and main_company=?i",$company_id,(int)$_SESSION['main_company']);
            if (is_array($company_data) && count($company_data)>0){
          		foreach($company_data as $key=>$val){
          		    $this->_comp_arr[$key]=(!empty($val) || $val == 0)?$val:'';
          		}
            }
      	    else {
      		       $this->create_new_comp();
      	    }
            if (is_array($comp_rek_data) && count($comp_rek_data)>0){
          		foreach($comp_rek_data as $key=>$val){
          		   $this->_comp_rekvizits[$key]=(!empty($val) || $val == 0)?$val:'';
          		}
            }
	          else {
      		      $this->create_new_comp_rek();
      	    }
        }
    }

	public function __get($name) {
		if (isset($this->_comp_arr[$name])) {
			return $this->_comp_arr[$name];
		} else {
			if (isset($this->_comp_rekvizits[$name])) {
			    return $this->_comp_rekvizits[$name];
			}
			return null;
		}
	}

	public function __isset($name){
	    return (isset($this->_comp_arr[$name]) || isset($this->_comp_rekvizits[$name]));
	}

	public function __set($name,$val) {
		if (isset($this->_comp_arr[$name])) {
			$this->_comp_arr[$name]=$val;
		}
		else {
		    if (isset($this->_comp_rekvizits[$name])) {
			$this->_comp_rekvizits[$name]=$val;
		    }
		}
	}

    public function Save(){
        $db = DB::getInstance();
        if ($this->id>0) {
            $sql="update company set ?u where id=?i";
            $db->query($sql,$this->_comp_arr,$this->id);
            //echo "db->query($sql,".print_r($this->_comp_arr,true).",".$this->id."); comp_rekvizits=".print_r($this->_comp_rekvizits,true);
            if ($db->affectedRows()>0) {
          		$db->query("insert ignore into company_rekvizits set ?u,company_id=?i,user_id=?i,main_company=?i",$this->_comp_rekvizits,$this->id,(int)$_SESSION['user_id'],(int)$_SESSION['main_company']);
          		return 1;
	          }
      	    else {
          		$db->query("insert ignore into company_rekvizits set ?u,company_id=?i,user_id=?i,main_company=?i",$this->_comp_rekvizits,$this->id,(int)$_SESSION['user_id'],(int)$_SESSION['main_company']);
          		if ($db->affectedRows()>0) return 1;
          		else return 0;
      	    }
        }
        else {
            $this->create_date=date("Y-m-d H:i:s");
            $save_data['create_date']=$this->create_date;
            $sql="insert ignore into company set ?u";
            $db->query($sql,$this->_comp_arr);
            if ($db->affectedRows()>0) {
				$this->id=$db->insertId();
	        }
            else {
				if(!empty($this->mphone)){
					$this->id=$db->getOne("select id from company where inn=?i and kpp=?i and name=?s and mphone=?s",(int)$this->inn,(int)$this->kpp,$this->name,$this->mphone);
				}
              	else
			  		$this->id=$db->getOne("select id from company where inn=?i and kpp=?i and name=?s and mphone=?s and email=?s",(int)$this->inn,(int)$this->kpp,$this->name,$this->mphone,$this->email);
            }
			if(empty($this->id)){
				return 0;
			}
			else{
	        	$db->query("insert ignore into company_rekvizits set ?u,company_id=?i,main_company=?i,user_id=?i",$this->_comp_rekvizits,$this->id,(int)$_SESSION['main_company'],(int)$_SESSION['user_id']);
				return 1;
			}
        }
        //echo "db->query($sql,".print_r($save_data,true).",".$this->user_id.");\n";
        return $db->error;
    }
}
?>
